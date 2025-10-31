<?php
/**
 * Links API Endpoint
 * Handles CRUD operations for page links
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';

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
    case 'add':
        $linkData = [
            'type' => sanitizeInput($_POST['type'] ?? ''),
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'url' => sanitizeUrl($_POST['url'] ?? ''),
            'thumbnail_image' => sanitizeUrl($_POST['thumbnail_image'] ?? null),
            'icon' => sanitizeInput($_POST['icon'] ?? null),
            'disclosure_text' => sanitizeInput($_POST['disclosure_text'] ?? null),
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
        ];
        
        $result = $page->addLink($pageId, $linkData);
        echo json_encode($result);
        break;
        
    case 'update':
        $linkId = (int)($_POST['link_id'] ?? 0);
        if (!$linkId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Link ID required']);
            exit;
        }
        
        $linkData = [];
        $allowedFields = ['type', 'title', 'url', 'thumbnail_image', 'icon', 'disclosure_text', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'url' || $field === 'thumbnail_image') {
                    $linkData[$field] = sanitizeUrl($_POST[$field]);
                } elseif ($field === 'is_active') {
                    $linkData[$field] = (int)$_POST[$field];
                } else {
                    $linkData[$field] = sanitizeInput($_POST[$field]);
                }
            }
        }
        
        $result = $page->updateLink($linkId, $pageId, $linkData);
        echo json_encode($result);
        break;
        
    case 'delete':
        $linkId = (int)($_POST['link_id'] ?? 0);
        if (!$linkId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Link ID required']);
            exit;
        }
        
        $result = $page->deleteLink($linkId, $pageId);
        echo json_encode($result);
        break;
        
    case 'reorder':
        $linkOrders = json_decode($_POST['link_orders'] ?? '[]', true);
        
        if (!is_array($linkOrders)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid link orders format']);
            exit;
        }
        
        // Validate and sanitize link orders
        $validatedOrders = [];
        foreach ($linkOrders as $order) {
            if (isset($order['link_id']) && isset($order['display_order'])) {
                $validatedOrders[] = [
                    'link_id' => (int)$order['link_id'],
                    'display_order' => (int)$order['display_order']
                ];
            }
        }
        
        $result = $page->updateLinkOrder($pageId, $validatedOrders);
        echo json_encode($result);
        break;
        
    case 'get':
        $linkId = (int)($_POST['link_id'] ?? 0);
        if (!$linkId) {
            $links = $page->getAllLinks($pageId);
            echo json_encode(['success' => true, 'links' => $links]);
        } else {
            $link = $page->getLink($linkId, $pageId);
            if ($link) {
                echo json_encode(['success' => true, 'link' => $link]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Link not found']);
            }
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

