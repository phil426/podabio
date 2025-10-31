<?php
/**
 * Page API Endpoint
 * Handles page update operations
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/RSSParser.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];

// Get user's page
$page = new Page();
$userPage = $page->getByUserId($userId);

if (!$userPage) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Page not found']);
    exit;
}

$pageId = $userPage['id'];
$action = $_POST['action'] ?? '';

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

switch ($action) {
    case 'update_settings':
        $updateData = [];
        
        if (isset($_POST['username'])) {
            $username = sanitizeInput($_POST['username']);
            // Check if username is available (if changed)
            if ($username !== $userPage['username']) {
                if (!$page->isUsernameAvailable($username)) {
                    echo json_encode(['success' => false, 'error' => 'Username already taken']);
                    exit;
                }
            }
            $updateData['username'] = $username;
        }
        
        if (isset($_POST['podcast_name'])) {
            $updateData['podcast_name'] = sanitizeInput($_POST['podcast_name']);
        }
        
        if (isset($_POST['podcast_description'])) {
            $updateData['podcast_description'] = sanitizeInput($_POST['podcast_description']);
        }
        
        // Handle custom domain
        if (isset($_POST['custom_domain'])) {
            $customDomain = trim(sanitizeInput($_POST['custom_domain']));
            
            if (empty($customDomain)) {
                // Allow removing custom domain
                $updateData['custom_domain'] = null;
            } else {
                // Validate domain format
                require_once __DIR__ . '/../classes/DomainVerifier.php';
                $verifier = new DomainVerifier();
                
                if (!$verifier->isValidDomain($customDomain)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid domain format. Please enter a valid domain (e.g., example.com)']);
                    exit;
                }
                
                // Check if domain is already taken by another page
                $existingPage = fetchOne("SELECT id FROM pages WHERE custom_domain = ? AND id != ?", [$customDomain, $pageId]);
                if ($existingPage) {
                    echo json_encode(['success' => false, 'error' => 'This domain is already in use by another page']);
                    exit;
                }
                
                $updateData['custom_domain'] = $customDomain;
            }
        }
        
        $result = $page->update($pageId, $updateData);
        echo json_encode(['success' => $result, 'error' => $result ? null : 'Failed to update settings']);
        break;
        
    case 'verify_domain':
        $domain = sanitizeInput($_POST['domain'] ?? '');
        
        if (empty($domain)) {
            echo json_encode(['success' => false, 'verified' => false, 'message' => 'Domain is required']);
            exit;
        }
        
        require_once __DIR__ . '/../classes/DomainVerifier.php';
        $verifier = new DomainVerifier();
        
        $result = $verifier->verifyDomain($domain);
        echo json_encode([
            'success' => true,
            'verified' => $result['verified'],
            'message' => $result['message'],
            'records' => $result['records']
        ]);
        break;
        
    case 'update_appearance':
        $updateData = [];
        
        if (isset($_POST['theme_id'])) {
            $updateData['theme_id'] = !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : null;
        }
        
        if (isset($_POST['layout_option'])) {
            $updateData['layout_option'] = sanitizeInput($_POST['layout_option']);
        }
        
        // Handle custom colors
        $colors = [];
        if (isset($_POST['custom_primary_color'])) {
            $colors['primary'] = sanitizeInput($_POST['custom_primary_color']);
        }
        if (isset($_POST['custom_secondary_color'])) {
            $colors['secondary'] = sanitizeInput($_POST['custom_secondary_color']);
        }
        if (isset($_POST['custom_accent_color'])) {
            $colors['accent'] = sanitizeInput($_POST['custom_accent_color']);
        }
        if (!empty($colors)) {
            $updateData['colors'] = $colors;
        }
        
        // Handle custom fonts
        $fonts = [];
        if (isset($_POST['custom_heading_font'])) {
            $fonts['heading'] = sanitizeInput($_POST['custom_heading_font']);
        }
        if (isset($_POST['custom_body_font'])) {
            $fonts['body'] = sanitizeInput($_POST['custom_body_font']);
        }
        if (!empty($fonts)) {
            $updateData['fonts'] = $fonts;
        }
        
        $result = $page->update($pageId, $updateData);
        echo json_encode(['success' => $result, 'error' => $result ? null : 'Failed to update appearance']);
        break;
        
    case 'update_email_settings':
        $updateData = [];
        
        if (isset($_POST['email_service_provider'])) {
            $updateData['email_service_provider'] = !empty($_POST['email_service_provider']) ? sanitizeInput($_POST['email_service_provider']) : null;
        }
        
        if (isset($_POST['email_service_api_key'])) {
            $updateData['email_service_api_key'] = sanitizeInput($_POST['email_service_api_key']);
        }
        
        if (isset($_POST['email_list_id'])) {
            $updateData['email_list_id'] = sanitizeInput($_POST['email_list_id']);
        }
        
        if (isset($_POST['email_double_optin'])) {
            $updateData['email_double_optin'] = (int)$_POST['email_double_optin'];
        } else {
            $updateData['email_double_optin'] = 0;
        }
        
        $result = $page->update($pageId, $updateData);
        echo json_encode(['success' => $result, 'error' => $result ? null : 'Failed to update email settings']);
        break;
        
    case 'add_directory':
        $platformName = sanitizeInput($_POST['platform_name'] ?? '');
        $url = sanitizeUrl($_POST['url'] ?? '');
        
        if (empty($platformName) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Platform name and URL are required']);
            exit;
        }
        
        $result = $page->addPodcastDirectory($pageId, $platformName, $url);
        echo json_encode($result);
        break;
        
    case 'delete_directory':
        $directoryId = (int)($_POST['directory_id'] ?? 0);
        if (!$directoryId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Directory ID required']);
            exit;
        }
        
        $result = $page->deletePodcastDirectory($directoryId, $pageId);
        echo json_encode($result);
        break;
        
    case 'remove_image':
        $imageType = sanitizeInput($_POST['type'] ?? '');
        
        if ($imageType === 'profile') {
            $updateField = 'profile_image';
        } elseif ($imageType === 'background') {
            $updateField = 'background_image';
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid image type']);
            exit;
        }
        
        // Get old image to delete
        $oldImage = $userPage[$updateField] ?? null;
        
        // Remove image from page
        $updateResult = $page->update($pageId, [$updateField => null]);
        
        if ($updateResult) {
            // Delete old image file
            if ($oldImage && strpos($oldImage, APP_URL) === 0) {
                require_once __DIR__ . '/../classes/ImageHandler.php';
                $imageHandler = new ImageHandler();
                $oldPath = str_replace(APP_URL, '', $oldImage);
                $imageHandler->deleteImage($oldPath);
            }
            
            echo json_encode(['success' => true, 'message' => ucfirst($imageType) . ' image removed successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove image']);
        }
        break;
        
    case 'import_rss':
        $rssUrl = sanitizeUrl($_POST['rss_feed_url'] ?? '');
        
        if (empty($rssUrl)) {
            echo json_encode(['success' => false, 'error' => 'RSS feed URL is required']);
            exit;
        }
        
        // Update page with RSS URL
        $page->update($pageId, ['rss_feed_url' => $rssUrl]);
        
        // Parse and import RSS feed
        $parser = new RSSParser();
        $feedResult = $parser->parseFeed($rssUrl);
        
        if (!$feedResult['success']) {
            echo json_encode(['success' => false, 'error' => $feedResult['error']]);
            exit;
        }
        
        // Save to page
        $saveResult = $parser->saveToPage($pageId, $feedResult['data']);
        
        if ($saveResult) {
            $episodeCount = count($feedResult['data']['episodes'] ?? []);
            echo json_encode([
                'success' => true,
                'message' => "RSS feed imported successfully. {$episodeCount} episodes imported.",
                'data' => [
                    'podcast_name' => $feedResult['data']['title'] ?? '',
                    'episode_count' => $episodeCount
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save RSS data']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

