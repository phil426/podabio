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
require_once __DIR__ . '/../includes/theme-helpers.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/RSSParser.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/APIResponse.php';
require_once __DIR__ . '/../classes/WidgetStyleManager.php';

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
        
        // Handle page fonts (new separate columns)
        if (isset($_POST['page_primary_font'])) {
            $updateData['page_primary_font'] = sanitizeInput($_POST['page_primary_font']);
        }
        if (isset($_POST['page_secondary_font'])) {
            $updateData['page_secondary_font'] = sanitizeInput($_POST['page_secondary_font']);
        }
        
        // Handle legacy custom fonts (for backward compatibility)
        $fonts = [];
        if (isset($_POST['custom_heading_font'])) {
            $fonts['heading'] = sanitizeInput($_POST['custom_heading_font']);
            // Also update page_primary_font if not already set
            if (!isset($updateData['page_primary_font'])) {
                $updateData['page_primary_font'] = $fonts['heading'];
            }
        }
        if (isset($_POST['custom_body_font'])) {
            $fonts['body'] = sanitizeInput($_POST['custom_body_font']);
            // Also update page_secondary_font if not already set
            if (!isset($updateData['page_secondary_font'])) {
                $updateData['page_secondary_font'] = $fonts['body'];
            }
        }
        if (!empty($fonts)) {
            $updateData['fonts'] = $fonts; // Keep for backward compatibility
        }
        
        // Handle widget fonts
        if (isset($_POST['widget_primary_font'])) {
            $updateData['widget_primary_font'] = sanitizeInput($_POST['widget_primary_font']);
        }
        if (isset($_POST['widget_secondary_font'])) {
            $updateData['widget_secondary_font'] = sanitizeInput($_POST['widget_secondary_font']);
        }
        
        // Handle page background
        if (isset($_POST['page_background'])) {
            $updateData['page_background'] = sanitizeInput($_POST['page_background']);
        }
        
        // Handle widget background
        if (isset($_POST['widget_background'])) {
            $updateData['widget_background'] = sanitizeInput($_POST['widget_background']);
        }
        
        // Handle widget border color
        if (isset($_POST['widget_border_color'])) {
            $updateData['widget_border_color'] = sanitizeInput($_POST['widget_border_color']);
        }
        
        // Handle widget styles
        if (isset($_POST['widget_styles'])) {
            $widgetStylesJson = $_POST['widget_styles'];
            if (is_string($widgetStylesJson)) {
                $widgetStyles = json_decode($widgetStylesJson, true);
            } else {
                $widgetStyles = $widgetStylesJson;
            }
            
            if (is_array($widgetStyles)) {
                // Sanitize and merge with defaults
                $updateData['widget_styles'] = WidgetStyleManager::sanitize($widgetStyles);
            }
        }
        
        // Handle spatial effect
        if (isset($_POST['spatial_effect'])) {
            $spatialEffect = sanitizeInput($_POST['spatial_effect']);
            $validEffects = ['none', 'glass', 'depth', 'floating', 'tilt'];
            if (in_array($spatialEffect, $validEffects, true)) {
                $updateData['spatial_effect'] = $spatialEffect;
            }
        }
        
        // Handle page name effect
        if (isset($_POST['page_name_effect'])) {
            $pageNameEffect = sanitizeInput($_POST['page_name_effect']);
            error_log("API: page_name_effect received: " . var_export($pageNameEffect, true));
            error_log("API: POST data contains page_name_effect: " . var_export(isset($_POST['page_name_effect']), true));
            $validEffects = ['', 'sweet-title', 'long-shadow', '3d-extrude', 'jello', 'neon', 'gummy', 'water', 'outline', 'rainbow', 'badge-shield', 'isometric-3d', 'geometric-cutout', 'layered-badge', 'stencil', 'pattern-fill', 'corporate-block', 'negative-space', 'depth-layers'];
            if (in_array($pageNameEffect, $validEffects, true)) {
                $updateData['page_name_effect'] = $pageNameEffect === '' ? null : $pageNameEffect;
                error_log("API: page_name_effect validated and added to updateData: " . var_export($updateData['page_name_effect'], true));
            } else {
                error_log("API: page_name_effect validation FAILED. Value: " . var_export($pageNameEffect, true) . ", Valid effects: " . implode(', ', $validEffects));
            }
        } else {
            error_log("API: page_name_effect NOT SET in POST data. Available POST keys: " . implode(', ', array_keys($_POST)));
        }
        
        $result = $page->update($pageId, $updateData);
        if ($result) {
            echo APIResponse::success(null, 'Appearance updated successfully');
        } else {
            echo APIResponse::error('Failed to update appearance');
        }
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
        
        if (empty($platformName)) {
            echo json_encode(['success' => false, 'error' => 'Platform name is required']);
            exit;
        }
        
        // Allow empty URL for placeholder icons (will be created with is_active = 0)
        if (empty($url)) {
            $url = ''; // Explicitly set to empty string
        }
        
        $result = $page->addSocialIcon($pageId, $platformName, $url);
        echo json_encode($result);
        break;
        
    case 'update_directory':
        $iconId = (int)($_POST['directory_id'] ?? 0);
        $platformName = sanitizeInput($_POST['platform_name'] ?? '');
        $url = sanitizeUrl($_POST['url'] ?? '');
        
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        if (empty($platformName) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Platform name and URL are required']);
            exit;
        }
        
        // Validate URL format (must be valid http/https URL)
        $isValidUrl = filter_var($url, FILTER_VALIDATE_URL) !== false && 
                      (strpos(strtolower($url), 'http://') === 0 || strpos(strtolower($url), 'https://') === 0);
        
        // If URL is invalid, ensure is_active is set to 0
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;
        if ($isActive === 1 && !$isValidUrl) {
            $isActive = 0; // Cannot be active with invalid URL
        }
        
        $result = $page->updateSocialIcon($iconId, $pageId, $platformName, $url, $isActive);
        echo json_encode($result);
        break;
        
    case 'update_social_icon_visibility':
        $iconId = (int)($_POST['icon_id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : false;
        
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        $result = $page->toggleSocialIconVisibility($iconId, $pageId, $isActive);
        echo json_encode($result);
        break;
        
    case 'delete_directory':
        $iconId = (int)($_POST['directory_id'] ?? 0);
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        $result = $page->deleteSocialIcon($iconId, $pageId);
        echo json_encode($result);
        break;
        
    case 'reorder_social_icons':
        $iconOrdersJson = $_POST['icon_orders'] ?? '';
        if (empty($iconOrdersJson)) {
            echo json_encode(['success' => false, 'error' => 'Icon orders required']);
            exit;
        }
        
        $iconOrders = json_decode($iconOrdersJson, true);
        if (!is_array($iconOrders)) {
            echo json_encode(['success' => false, 'error' => 'Invalid icon orders format']);
            exit;
        }
        
        $result = $page->reorderSocialIcons($pageId, $iconOrders);
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
        
    case 'save_theme':
        $themeName = trim(sanitizeInput($_POST['theme_name'] ?? ''));
        
        if (empty($themeName) || strlen($themeName) > 100) {
            echo APIResponse::error('Theme name must be 1-100 characters', 400);
            break;
        }
        
        // Collect current theme configuration
        $themeData = [
            'colors' => [],
            'fonts' => [],
            'page_background' => null,
            'widget_styles' => null,
            'spatial_effect' => 'none'
        ];
        
        // Get current colors
        if (isset($_POST['custom_primary_color'])) {
            $themeData['colors']['primary'] = sanitizeInput($_POST['custom_primary_color']);
        }
        if (isset($_POST['custom_secondary_color'])) {
            $themeData['colors']['secondary'] = sanitizeInput($_POST['custom_secondary_color']);
        }
        if (isset($_POST['custom_accent_color'])) {
            $themeData['colors']['accent'] = sanitizeInput($_POST['custom_accent_color']);
        }
        
        // Get current fonts (legacy support)
        if (isset($_POST['custom_heading_font'])) {
            $themeData['fonts']['heading'] = sanitizeInput($_POST['custom_heading_font']);
        }
        if (isset($_POST['custom_body_font'])) {
            $themeData['fonts']['body'] = sanitizeInput($_POST['custom_body_font']);
        }
        
        // Get page fonts (new separate columns)
        if (isset($_POST['page_primary_font'])) {
            $themeData['page_primary_font'] = sanitizeInput($_POST['page_primary_font']);
        }
        if (isset($_POST['page_secondary_font'])) {
            $themeData['page_secondary_font'] = sanitizeInput($_POST['page_secondary_font']);
        }
        
        // Get widget fonts
        if (isset($_POST['widget_primary_font'])) {
            $themeData['widget_primary_font'] = sanitizeInput($_POST['widget_primary_font']);
        }
        if (isset($_POST['widget_secondary_font'])) {
            $themeData['widget_secondary_font'] = sanitizeInput($_POST['widget_secondary_font']);
        }
        
        // Get page background
        if (isset($_POST['page_background'])) {
            $themeData['page_background'] = sanitizeInput($_POST['page_background']);
        }
        
        // Get widget background
        if (isset($_POST['widget_background'])) {
            $themeData['widget_background'] = sanitizeInput($_POST['widget_background']);
        }
        
        // Get widget border color
        if (isset($_POST['widget_border_color'])) {
            $themeData['widget_border_color'] = sanitizeInput($_POST['widget_border_color']);
        }
        
        // Get widget styles
        if (isset($_POST['widget_styles'])) {
            $widgetStylesJson = $_POST['widget_styles'];
            if (is_string($widgetStylesJson)) {
                $widgetStyles = json_decode($widgetStylesJson, true);
            } else {
                $widgetStyles = $widgetStylesJson;
            }
            
            if (is_array($widgetStyles)) {
                $themeData['widget_styles'] = WidgetStyleManager::sanitize($widgetStyles);
            }
        }
        
        // Get spatial effect
        if (isset($_POST['spatial_effect'])) {
            $spatialEffect = sanitizeInput($_POST['spatial_effect']);
            $validEffects = ['none', 'glass', 'depth', 'floating'];
            if (in_array($spatialEffect, $validEffects, true)) {
                $themeData['spatial_effect'] = $spatialEffect;
            }
        }
        
        // Create theme
        $theme = new Theme();
        $result = $theme->createTheme($userId, $themeName, $themeData);
        
        if ($result['success']) {
            echo APIResponse::success([
                'theme_id' => $result['theme_id'],
                'theme_name' => $themeName
            ], 'Theme saved successfully');
        } else {
            echo APIResponse::error($result['error'] ?? 'Failed to save theme');
        }
        break;
        
    case 'delete_theme':
        $themeId = (int)($_POST['theme_id'] ?? 0);
        
        if (!$themeId) {
            echo APIResponse::error('Theme ID required', 400);
            break;
        }
        
        $theme = new Theme();
        $result = $theme->deleteUserTheme($themeId, $userId);
        
        if ($result) {
            echo APIResponse::success(null, 'Theme deleted successfully');
        } else {
            echo APIResponse::error('Failed to delete theme or theme not found');
        }
        break;
        
    default:
        echo APIResponse::error('Invalid action', 400);
        break;
}

