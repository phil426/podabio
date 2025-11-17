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
    } elseif ($action === 'link_analytics') {
        // Get page for current user
        $pageClass = new Page();
        $page = $pageClass->getByUserId($currentUser['id']);
        
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Page not found']);
            exit;
        }
        
        $period = sanitizeInput($_GET['period'] ?? $_POST['period'] ?? 'month');
        $analytics = new Analytics();
        
        // Build date filter
        $dateFilter = '';
        switch ($period) {
            case 'day':
                $dateFilter = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $dateFilter = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'all':
                $dateFilter = '';
                break;
            default:
                $dateFilter = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
        
        // Get summary stats
        try {
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
            
            // Calculate CTR
            $ctr = $pageViews > 0 ? round(($totalClicks / $pageViews) * 100, 2) : 0;
        } catch (Exception $e) {
            // Fallback without date filter
            $pageViews = (int)fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'view'",
                [$page['id']]
            )['count'] ?? 0;
            
            $totalClicks = (int)fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'click'",
                [$page['id']]
            )['count'] ?? 0;
            
            $ctr = $pageViews > 0 ? round(($totalClicks / $pageViews) * 100, 2) : 0;
        }
        
        // Get time-series data (clicks per day)
        $timeSeries = [];
        try {
            $timeSeriesData = fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as clicks
                 FROM analytics 
                 WHERE page_id = ? AND event_type = 'click' $dateFilter
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC",
                [$page['id']]
            );
            
            foreach ($timeSeriesData as $row) {
                $timeSeries[] = [
                    'date' => $row['date'],
                    'clicks' => (int)$row['clicks']
                ];
            }
        } catch (Exception $e) {
            // Fallback without date filter
            $timeSeriesData = fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as clicks
                 FROM analytics 
                 WHERE page_id = ? AND event_type = 'click'
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC",
                [$page['id']]
            );
            
            foreach ($timeSeriesData as $row) {
                $timeSeries[] = [
                    'date' => $row['date'],
                    'clicks' => (int)$row['clicks']
                ];
            }
        }
        
        // Get top links/widgets by clicks
        $topLinks = [];
        try {
            // Get widgets with click counts
            $widgetClicks = fetchAll(
                "SELECT w.id, w.title, w.widget_type,
                        COALESCE((
                            SELECT COUNT(*) 
                            FROM analytics a 
                            WHERE a.link_id = w.id 
                              AND a.event_type = 'click' 
                              AND a.page_id = ?
                              $dateFilter
                        ), 0) as click_count,
                        (
                            SELECT config_data FROM widgets WHERE id = w.id
                        ) as config_data
                 FROM widgets w
                 WHERE w.page_id = ? AND w.is_active = 1
                 HAVING click_count > 0
                 ORDER BY click_count DESC
                 LIMIT 20",
                [$page['id'], $page['id']]
            );
            
            foreach ($widgetClicks as $widget) {
                $configData = is_string($widget['config_data'] ?? '') 
                    ? json_decode($widget['config_data'], true) 
                    : ($widget['config_data'] ?? []);
                $url = $configData['url'] ?? '';
                
                $topLinks[] = [
                    'id' => (int)$widget['id'],
                    'title' => $widget['title'],
                    'type' => $widget['widget_type'],
                    'url' => $url,
                    'clicks' => (int)$widget['click_count'],
                    'ctr' => $pageViews > 0 ? round(($widget['click_count'] / $pageViews) * 100, 2) : 0
                ];
            }
        } catch (Exception $e) {
            // Fallback without date filter
            $widgetClicks = fetchAll(
                "SELECT w.id, w.title, w.widget_type,
                        COALESCE((
                            SELECT COUNT(*) 
                            FROM analytics a 
                            WHERE a.link_id = w.id 
                              AND a.event_type = 'click' 
                              AND a.page_id = ?
                        ), 0) as click_count,
                        (
                            SELECT config_data FROM widgets WHERE id = w.id
                        ) as config_data
                 FROM widgets w
                 WHERE w.page_id = ? AND w.is_active = 1
                 HAVING click_count > 0
                 ORDER BY click_count DESC
                 LIMIT 20",
                [$page['id'], $page['id']]
            );
            
            foreach ($widgetClicks as $widget) {
                $configData = is_string($widget['config_data'] ?? '') 
                    ? json_decode($widget['config_data'], true) 
                    : ($widget['config_data'] ?? []);
                $url = $configData['url'] ?? '';
                
                $topLinks[] = [
                    'id' => (int)$widget['id'],
                    'title' => $widget['title'],
                    'type' => $widget['widget_type'],
                    'url' => $url,
                    'clicks' => (int)$widget['click_count'],
                    'ctr' => $pageViews > 0 ? round(($widget['click_count'] / $pageViews) * 100, 2) : 0
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'page_views' => $pageViews,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,
            'time_series' => $timeSeries,
            'top_links' => $topLinks
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

