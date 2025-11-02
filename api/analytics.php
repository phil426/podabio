<?php
/**
 * API: Widget Analytics
 * Returns widget performance analytics data
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/Page.php';

header('Content-Type: application/json');

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
    
    // Get page views for the period
    $summary = $analytics->getSummary($page['id'], $period);
    
    echo json_encode([
        'success' => true,
        'widgets' => $widgetData,
        'page_views' => $summary['views'],
        'total_clicks' => $summary['clicks']
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

