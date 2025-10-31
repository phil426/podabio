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
}

