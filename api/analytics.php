<?php
/**
 * API: Widget Analytics
 * Returns widget performance analytics data
 */

// Suppress warnings/errors that might corrupt JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/Page.php';

header('Content-Type: application/json');

try {
    // Check authentication
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');

    if ($action === 'widget_analytics') {
        // Get page for current user
        $pageClass = new Page();
        $page = $pageClass->getByUserId($currentUser['id']);
        
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Page not found']);
            exit;
        }
        
        $period = sanitizeInput($_GET['period'] ?? 'month');
        $analytics = new Analytics();
        
        // Get widget analytics
        $widgetData = $analytics->getWidgetAnalytics($page['id'], $period);
        
        // Get summary data
        $pageViews = 0;
        $totalClicks = 0;
        
        // Calculate page views for the period
        $dateFilter = '';
        switch ($period) {
            case 'day':
                $dateFilter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $dateFilter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $dateFilter = '';
        }
        
        $pageViewsResult = fetchOne(
            "SELECT COUNT(*) as count FROM analytics 
             WHERE page_id = ? AND event_type = 'view' $dateFilter",
            [$page['id']]
        );
        $pageViews = (int)($pageViewsResult['count'] ?? 0);
        
        $clicksResult = fetchOne(
            "SELECT COUNT(*) as count FROM analytics 
             WHERE page_id = ? AND event_type = 'click' $dateFilter",
            [$page['id']]
        );
        $totalClicks = (int)($clicksResult['count'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'widgets' => $widgetData ? $widgetData : [],
            'page_views' => $pageViews,
            'total_clicks' => $totalClicks
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

