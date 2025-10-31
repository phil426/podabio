<?php
/**
 * Widgets API Endpoint
 * Handles CRUD operations for page widgets
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/WidgetRegistry.php';

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
        $widgetType = sanitizeInput($_POST['widget_type'] ?? '');
        $title = sanitizeInput($_POST['title'] ?? '');
        
        // Validate widget type
        if (!WidgetRegistry::widgetExists($widgetType)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid widget type']);
            exit;
        }
        
        // Build config_data from POST fields
        $widgetDef = WidgetRegistry::getWidget($widgetType);
        $configData = [];
        
        foreach ($widgetDef['config_fields'] as $fieldName => $fieldDef) {
            if (isset($_POST[$fieldName])) {
                if ($fieldDef['type'] === 'url') {
                    $configData[$fieldName] = sanitizeUrl($_POST[$fieldName]);
                } elseif ($fieldDef['type'] === 'textarea') {
                    $configData[$fieldName] = sanitizeInput($_POST[$fieldName]);
                } else {
                    $configData[$fieldName] = sanitizeInput($_POST[$fieldName]);
                }
            }
        }
        
        $result = $page->addWidget($pageId, $widgetType, $title, $configData);
        echo json_encode($result);
        break;
        
    case 'update':
        $widgetId = (int)($_POST['widget_id'] ?? 0);
        if (!$widgetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Widget ID required']);
            exit;
        }
        
        $updateData = [];
        
        if (isset($_POST['title'])) {
            $updateData['title'] = sanitizeInput($_POST['title']);
        }
        
        if (isset($_POST['widget_type'])) {
            $widgetType = sanitizeInput($_POST['widget_type']);
            if (!WidgetRegistry::widgetExists($widgetType)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid widget type']);
                exit;
            }
            $updateData['widget_type'] = $widgetType;
        }
        
        if (isset($_POST['config_data'])) {
            // Config data can be passed as JSON string or array
            $configData = $_POST['config_data'];
            if (is_string($configData)) {
                $configData = json_decode($configData, true);
            }
            
            // Alternatively, build from individual POST fields
            if (isset($_POST['widget_type'])) {
                $widgetType = sanitizeInput($_POST['widget_type']);
            } else {
                $existingWidget = $page->getWidget($widgetId, $pageId);
                $widgetType = $existingWidget['widget_type'] ?? 'custom_link';
            }
            
            $widgetDef = WidgetRegistry::getWidget($widgetType);
            if ($widgetDef) {
                $newConfigData = [];
                foreach ($widgetDef['config_fields'] as $fieldName => $fieldDef) {
                    if (isset($_POST[$fieldName])) {
                        if ($fieldDef['type'] === 'url') {
                            $newConfigData[$fieldName] = sanitizeUrl($_POST[$fieldName]);
                        } else {
                            $newConfigData[$fieldName] = sanitizeInput($_POST[$fieldName]);
                        }
                    } elseif (isset($configData[$fieldName])) {
                        $newConfigData[$fieldName] = $configData[$fieldName];
                    }
                }
                $configData = $newConfigData;
            }
            
            $updateData['config_data'] = $configData;
        }
        
        if (isset($_POST['is_active'])) {
            $updateData['is_active'] = (int)$_POST['is_active'];
        }
        
        $result = $page->updateWidget($widgetId, $pageId, $updateData);
        echo json_encode($result);
        break;
        
    case 'delete':
        $widgetId = (int)($_POST['widget_id'] ?? 0);
        if (!$widgetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Widget ID required']);
            exit;
        }
        
        $result = $page->deleteWidget($widgetId, $pageId);
        echo json_encode($result);
        break;
        
    case 'reorder':
        $widgetOrders = json_decode($_POST['widget_orders'] ?? '[]', true);
        
        if (!is_array($widgetOrders)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid widget orders format']);
            exit;
        }
        
        // Validate and sanitize widget orders
        $validatedOrders = [];
        foreach ($widgetOrders as $order) {
            if (isset($order['widget_id']) && isset($order['display_order'])) {
                $validatedOrders[] = [
                    'widget_id' => (int)$order['widget_id'],
                    'display_order' => (int)$order['display_order']
                ];
            }
        }
        
        $result = $page->updateWidgetOrder($pageId, $validatedOrders);
        echo json_encode($result);
        break;
        
    case 'get':
        $widgetId = (int)($_POST['widget_id'] ?? 0);
        if (!$widgetId) {
            $widgets = $page->getAllWidgets($pageId);
            echo json_encode(['success' => true, 'widgets' => $widgets]);
        } else {
            $widget = $page->getWidget($widgetId, $pageId);
            if ($widget) {
                echo json_encode(['success' => true, 'widget' => $widget]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Widget not found']);
            }
        }
        break;
        
    case 'get_available':
        // Return list of available widgets from registry
        $includeComingSoon = isset($_POST['include_coming_soon']) && $_POST['include_coming_soon'] == '1';
        $widgets = WidgetRegistry::getAvailableWidgets($includeComingSoon);
        echo json_encode(['success' => true, 'widgets' => $widgets]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

