<?php
/**
 * Analytics Class
 * Podn.Bio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

class Analytics {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Track page view
     * @param int $pageId
     * @return bool
     */
    public function trackView($pageId) {
        try {
            executeQuery(
                "INSERT INTO analytics (page_id, event_type, ip_address, user_agent, referrer) 
                 VALUES (?, 'view', ?, ?, ?)",
                [
                    $pageId,
                    getClientIP(),
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['HTTP_REFERER'] ?? ''
                ]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Analytics tracking failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track link click
     * @param int $pageId
     * @param int $linkId
     * @return bool
     */
    public function trackClick($pageId, $linkId) {
        try {
            executeQuery(
                "INSERT INTO analytics (page_id, event_type, link_id, ip_address, user_agent, referrer) 
                 VALUES (?, 'click', ?, ?, ?, ?)",
                [
                    $pageId,
                    $linkId,
                    getClientIP(),
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['HTTP_REFERER'] ?? ''
                ]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Analytics tracking failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track email subscription
     * @param int $pageId
     * @return bool
     */
    public function trackEmailSubscribe($pageId) {
        try {
            executeQuery(
                "INSERT INTO analytics (page_id, event_type, ip_address, user_agent, referrer) 
                 VALUES (?, 'email_subscribe', ?, ?, ?)",
                [
                    $pageId,
                    getClientIP(),
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['HTTP_REFERER'] ?? ''
                ]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Analytics tracking failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get page analytics summary
     * @param int $pageId
     * @param string $period ('day', 'week', 'month', 'all')
     * @return array
     */
    public function getSummary($pageId, $period = 'month') {
        $dateFilter = '';
        
        // Only add date filter if period is not 'all'
        if ($period !== 'all') {
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
            }
        }
        
        // Try with date filter, fallback without if column doesn't exist
        try {
            $views = fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'view' $dateFilter",
                [$pageId]
            )['count'] ?? 0;
            
            $clicks = fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'click' $dateFilter",
                [$pageId]
            )['count'] ?? 0;
            
            $subscribers = fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'email_subscribe' $dateFilter",
                [$pageId]
            )['count'] ?? 0;
        } catch (Exception $e) {
            // If created_at column doesn't exist, query without date filter
            if (strpos($e->getMessage(), 'created_at') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
                $dateFilter = ''; // Clear date filter
                $views = fetchOne(
                    "SELECT COUNT(*) as count FROM analytics 
                     WHERE page_id = ? AND event_type = 'view'",
                    [$pageId]
                )['count'] ?? 0;
                
                $clicks = fetchOne(
                    "SELECT COUNT(*) as count FROM analytics 
                     WHERE page_id = ? AND event_type = 'click'",
                    [$pageId]
                )['count'] ?? 0;
                
                $subscribers = fetchOne(
                    "SELECT COUNT(*) as count FROM analytics 
                     WHERE page_id = ? AND event_type = 'email_subscribe'",
                    [$pageId]
                )['count'] ?? 0;
            } else {
                throw $e; // Re-throw if different error
            }
        }
        
        return [
            'views' => (int)$views,
            'clicks' => (int)$clicks,
            'subscribers' => (int)$subscribers
        ];
    }
    
    /**
     * Get top links by clicks
     * @param int $pageId
     * @param int $limit
     * @return array
     */
    public function getTopLinks($pageId, $limit = 10) {
        return fetchAll(
            "SELECT l.*, COUNT(a.id) as click_count 
             FROM links l
             LEFT JOIN analytics a ON l.id = a.link_id AND a.event_type = 'click'
             WHERE l.page_id = ? AND l.is_active = 1
             GROUP BY l.id
             ORDER BY click_count DESC
             LIMIT ?",
            [$pageId, $limit]
        );
    }
    
    /**
     * Get widget analytics (clicks, views, CTR)
     * @param int $pageId
     * @param string $period ('day', 'week', 'month', 'all')
     * @return array
     */
    public function getWidgetAnalytics($pageId, $period = 'month') {
        // Try to determine the timestamp column name - check common variations
        // Most likely: created_at, timestamp, date_created, or might not exist
        $dateFilter = '';
        
        // Build date filter using NOW() directly since we can't be sure of column name
        // We'll use a safer approach that works even if timestamp column doesn't exist
        if ($period !== 'all') {
            switch ($period) {
                case 'day':
                    $dateFilter = "AND DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case 'week':
                    $dateFilter = "AND DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                    break;
                case 'month':
                    $dateFilter = "AND DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                    break;
            }
        }
        
        // First, let's try without date filter if it fails, then fallback
        // Get widget clicks (link_id stores widget_id for widgets)
        try {
            $widgetClicks = fetchAll(
                "SELECT w.id, w.title, w.widget_type, 
                        COALESCE((
                            SELECT COUNT(*) 
                            FROM analytics a 
                            WHERE a.link_id = w.id 
                              AND a.event_type = 'click' 
                              AND a.page_id = ?
                              $dateFilter
                        ), 0) as click_count
                 FROM widgets w
                 WHERE w.page_id = ? AND w.is_active = 1
                 ORDER BY click_count DESC",
                [$pageId, $pageId]
            );
        } catch (Exception $e) {
            // If date filter fails (column doesn't exist), try without it
            if (strpos($e->getMessage(), 'created_at') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
                $dateFilter = ''; // Remove date filter
                $widgetClicks = fetchAll(
                    "SELECT w.id, w.title, w.widget_type, 
                            COALESCE((
                                SELECT COUNT(*) 
                                FROM analytics a 
                                WHERE a.link_id = w.id 
                                  AND a.event_type = 'click' 
                                  AND a.page_id = ?
                            ), 0) as click_count
                     FROM widgets w
                     WHERE w.page_id = ? AND w.is_active = 1
                     ORDER BY click_count DESC",
                    [$pageId, $pageId]
                );
            } else {
                throw $e; // Re-throw if it's a different error
            }
        }
        
        // Get page views for CTR calculation
        try {
            $pageViewsResult = fetchOne(
                "SELECT COUNT(*) as count FROM analytics 
                 WHERE page_id = ? AND event_type = 'view' $dateFilter",
                [$pageId]
            );
        } catch (Exception $e) {
            // If date filter fails, try without it
            if (strpos($e->getMessage(), 'created_at') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
                $pageViewsResult = fetchOne(
                    "SELECT COUNT(*) as count FROM analytics 
                     WHERE page_id = ? AND event_type = 'view'",
                    [$pageId]
                );
            } else {
                throw $e;
            }
        }
        $pageViews = (int)($pageViewsResult['count'] ?? 1); // Use 1 to avoid division by zero
        
        // Calculate CTR for each widget
        foreach ($widgetClicks as &$widget) {
            $widget['ctr'] = $pageViews > 0 ? round(($widget['click_count'] / $pageViews) * 100, 2) : 0;
            $widget['views'] = $pageViews; // All widgets get same page views (widgets rendered on page view)
        }
        
        return $widgetClicks;
    }
    
    /**
     * Track widget view (when widget is rendered)
     * @param int $pageId
     * @param int $widgetId
     * @return bool
     */
    public function trackWidgetView($pageId, $widgetId) {
        // Widget views are tracked as part of page views
        // This method exists for future use if we want separate widget view tracking
        return true;
    }
}


