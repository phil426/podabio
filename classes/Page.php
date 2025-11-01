<?php
/**
 * Page Class
 * Podn.Bio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Page {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get page by username
     * @param string $username
     * @return array|null
     */
    public function getByUsername($username) {
        return fetchOne(
            "SELECT p.*, u.email FROM pages p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.username = ? AND p.is_active = 1",
            [$username]
        );
    }
    
    /**
     * Get page by custom domain
     * @param string $domain
     * @return array|null
     */
    public function getByCustomDomain($domain) {
        return fetchOne(
            "SELECT p.*, u.email FROM pages p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.custom_domain = ? AND p.is_active = 1",
            [$domain]
        );
    }
    
    /**
     * Get page by user ID
     * @param int $userId
     * @return array|null
     */
    public function getByUserId($userId) {
        return fetchOne("SELECT * FROM pages WHERE user_id = ?", [$userId]);
    }
    
    /**
     * Create new page for user
     * @param int $userId
     * @param string $username
     * @return array ['success' => bool, 'page_id' => int|null, 'error' => string|null]
     */
    public function create($userId, $username) {
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
            return ['success' => false, 'page_id' => null, 'error' => 'Username must be 3-30 characters and contain only letters, numbers, underscores, and hyphens'];
        }
        
        // Check if username exists
        $existing = fetchOne("SELECT id FROM pages WHERE username = ?", [$username]);
        if ($existing) {
            return ['success' => false, 'page_id' => null, 'error' => 'Username already taken'];
        }
        
        // Check if user already has a page
        $userPage = $this->getByUserId($userId);
        if ($userPage) {
            return ['success' => false, 'page_id' => null, 'error' => 'You already have a page'];
        }
        
        // Create default subscription (free plan)
        require_once __DIR__ . '/Subscription.php';
        $subscription = new Subscription();
        $subResult = $subscription->createDefault($userId);
        if (!$subResult['success']) {
            error_log("Subscription creation failed during page creation: " . ($subResult['error'] ?? 'Unknown error'));
            // Continue anyway - subscription creation failure shouldn't block page creation
        }
        
        try {
            // Get first available theme or use NULL if none exist
            $defaultTheme = fetchOne("SELECT id FROM themes WHERE is_active = 1 ORDER BY id LIMIT 1");
            $themeId = $defaultTheme ? $defaultTheme['id'] : null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO pages (user_id, username, theme_id, layout_option)
                VALUES (?, ?, ?, 'layout1')
            ");
            $stmt->execute([$userId, $username, $themeId]);
            
            $pageId = $this->pdo->lastInsertId();
            
            return ['success' => true, 'page_id' => $pageId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Page creation failed: " . $e->getMessage());
            return ['success' => false, 'page_id' => null, 'error' => 'Failed to create page'];
        }
    }
    
    /**
     * Update page
     * @param int $pageId
     * @param array $data
     * @return bool
     */
    public function update($pageId, $data) {
        $allowedFields = [
            'username', 'custom_domain', 'rss_feed_url', 'podcast_name', 
            'podcast_description', 'cover_image_url', 'theme_id', 'colors', 
            'fonts', 'layout_option', 'background_image', 'profile_image',
            'email_service_provider', 'email_service_api_key', 'email_list_id', 'email_double_optin'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'colors' || $field === 'fonts') {
                    // JSON encode arrays
                    $updates[] = "$field = ?";
                    $params[] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
                } else {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $pageId;
        $sql = "UPDATE pages SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            executeQuery($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Page update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get page links (legacy method - use getWidgets instead)
     * @param int $pageId
     * @return array
     * @deprecated Use getWidgets() instead
     */
    public function getLinks($pageId) {
        // For backward compatibility, return widgets converted to link format
        $widgets = $this->getWidgets($pageId);
        $links = [];
        foreach ($widgets as $widget) {
            $config = is_string($widget['config_data']) ? json_decode($widget['config_data'], true) : ($widget['config_data'] ?? []);
            $links[] = [
                'id' => $widget['id'],
                'page_id' => $widget['page_id'],
                'type' => $widget['widget_type'],
                'title' => $widget['title'],
                'url' => $config['url'] ?? '',
                'thumbnail_image' => $config['thumbnail_image'] ?? null,
                'icon' => $config['icon'] ?? null,
                'display_order' => $widget['display_order'],
                'disclosure_text' => $config['disclosure_text'] ?? null,
                'is_active' => $widget['is_active']
            ];
        }
        return $links;
    }
    
    /**
     * Get page widgets
     * @param int $pageId
     * @return array
     */
    public function getWidgets($pageId) {
        return fetchAll(
            "SELECT * FROM widgets WHERE page_id = ? AND is_active = 1 ORDER BY display_order ASC",
            [$pageId]
        );
    }
    
    /**
     * Get all widgets for a page (including inactive)
     * @param int $pageId
     * @return array
     */
    public function getAllWidgets($pageId) {
        return fetchAll(
            "SELECT * FROM widgets WHERE page_id = ? ORDER BY display_order ASC",
            [$pageId]
        );
    }
    
    /**
     * Get page episodes
     * @param int $pageId
     * @param int $limit
     * @return array
     */
    public function getEpisodes($pageId, $limit = 10) {
        return fetchAll(
            "SELECT * FROM episodes WHERE page_id = ? AND is_visible = 1 
             ORDER BY pub_date DESC, created_at DESC LIMIT ?",
            [$pageId, $limit]
        );
    }
    
    /**
     * Get page theme
     * @param int $themeId
     * @return array|null
     */
    public function getTheme($themeId) {
        return fetchOne("SELECT * FROM themes WHERE id = ? AND is_active = 1", [$themeId]);
    }
    
    /**
     * Check if username is available
     * @param string $username
     * @return bool
     */
    public function isUsernameAvailable($username) {
        $existing = fetchOne("SELECT id FROM pages WHERE username = ?", [$username]);
        return $existing === false;
    }
    
    /**
     * Add link to page
     * @param int $pageId
     * @param array $data
     * @return array ['success' => bool, 'link_id' => int|null, 'error' => string|null]
     */
    public function addLink($pageId, $data) {
        $required = ['type', 'title', 'url'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'link_id' => null, 'error' => "Field '$field' is required"];
            }
        }
        
        // Get max display order
        $maxOrder = fetchOne("SELECT COALESCE(MAX(display_order), 0) as max_order FROM links WHERE page_id = ?", [$pageId]);
        $displayOrder = ($maxOrder['max_order'] ?? 0) + 1;
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO links (page_id, type, title, url, thumbnail_image, icon, display_order, disclosure_text, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $pageId,
                $data['type'],
                $data['title'],
                $data['url'],
                $data['thumbnail_image'] ?? null,
                $data['icon'] ?? null,
                $displayOrder,
                $data['disclosure_text'] ?? null,
                $data['is_active'] ?? 1
            ]);
            
            $linkId = $this->pdo->lastInsertId();
            return ['success' => true, 'link_id' => $linkId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Link creation failed: " . $e->getMessage());
            return ['success' => false, 'link_id' => null, 'error' => 'Failed to create link'];
        }
    }
    
    /**
     * Update link
     * @param int $linkId
     * @param int $pageId
     * @param array $data
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function updateLink($linkId, $pageId, $data) {
        // Verify link belongs to page
        $link = fetchOne("SELECT id FROM links WHERE id = ? AND page_id = ?", [$linkId, $pageId]);
        if (!$link) {
            return ['success' => false, 'error' => 'Link not found'];
        }
        
        $allowedFields = ['type', 'title', 'url', 'thumbnail_image', 'icon', 'display_order', 'disclosure_text', 'is_active'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $params[] = $linkId;
        $params[] = $pageId;
        $sql = "UPDATE links SET " . implode(', ', $updates) . " WHERE id = ? AND page_id = ?";
        
        try {
            executeQuery($sql, $params);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Link update failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update link'];
        }
    }
    
    /**
     * Delete link
     * @param int $linkId
     * @param int $pageId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteLink($linkId, $pageId) {
        // Verify link belongs to page
        $link = fetchOne("SELECT id FROM links WHERE id = ? AND page_id = ?", [$linkId, $pageId]);
        if (!$link) {
            return ['success' => false, 'error' => 'Link not found'];
        }
        
        try {
            executeQuery("DELETE FROM links WHERE id = ? AND page_id = ?", [$linkId, $pageId]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Link deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete link'];
        }
    }
    
    /**
     * Update link display order (for drag-and-drop)
     * @param int $pageId
     * @param array $linkOrders Array of ['link_id' => int, 'display_order' => int]
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function updateLinkOrder($pageId, $linkOrders) {
        if (empty($linkOrders)) {
            return ['success' => false, 'error' => 'No links provided'];
        }
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($linkOrders as $order) {
                $linkId = $order['link_id'] ?? null;
                $displayOrder = $order['display_order'] ?? null;
                
                if ($linkId === null || $displayOrder === null) {
                    continue;
                }
                
                // Verify link belongs to page
                $link = fetchOne("SELECT id FROM links WHERE id = ? AND page_id = ?", [$linkId, $pageId]);
                if (!$link) {
                    continue;
                }
                
                executeQuery("UPDATE links SET display_order = ? WHERE id = ? AND page_id = ?", 
                    [$displayOrder, $linkId, $pageId]);
            }
            
            $this->pdo->commit();
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Link order update failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update link order'];
        }
    }
    
    /**
     * Get all links for page (including inactive)
     * @param int $pageId
     * @return array
     */
    public function getAllLinks($pageId) {
        return fetchAll(
            "SELECT * FROM links WHERE page_id = ? ORDER BY display_order ASC, created_at ASC",
            [$pageId]
        );
    }
    
    /**
     * Get link by ID
     * @param int $linkId
     * @param int $pageId
     * @return array|null
     */
    public function getLink($linkId, $pageId) {
        return fetchOne(
            "SELECT * FROM links WHERE id = ? AND page_id = ?",
            [$linkId, $pageId]
        );
    }
    
    /**
     * Add social icon link
     * @param int $pageId
     * @param string $platformName
     * @param string $url
     * @return array ['success' => bool, 'icon_id' => int|null, 'error' => string|null]
     */
    public function addSocialIcon($pageId, $platformName, $url) {
        if (empty($platformName) || empty($url)) {
            return ['success' => false, 'icon_id' => null, 'error' => 'Platform name and URL are required'];
        }
        
        // Get max display order
        $maxOrder = fetchOne("SELECT COALESCE(MAX(display_order), 0) as max_order FROM social_icons WHERE page_id = ?", [$pageId]);
        $displayOrder = ($maxOrder['max_order'] ?? 0) + 1;
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO social_icons (page_id, platform_name, url, icon, display_order)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $pageId,
                $platformName,
                $url,
                null, // Icon can be determined by platform name
                $displayOrder
            ]);
            
            $iconId = $this->pdo->lastInsertId();
            return ['success' => true, 'icon_id' => $iconId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Social icon creation failed: " . $e->getMessage());
            return ['success' => false, 'icon_id' => null, 'error' => 'Failed to create social icon link'];
        }
    }
    
    /**
     * Get social icons for page
     * @param int $pageId
     * @return array
     */
    public function getSocialIcons($pageId) {
        return fetchAll(
            "SELECT * FROM social_icons WHERE page_id = ? ORDER BY display_order ASC",
            [$pageId]
        );
    }
    
    /**
     * Delete social icon
     * @param int $iconId
     * @param int $pageId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteSocialIcon($iconId, $pageId) {
        $icon = fetchOne("SELECT id FROM social_icons WHERE id = ? AND page_id = ?", [$iconId, $pageId]);
        if (!$icon) {
            return ['success' => false, 'error' => 'Social icon not found'];
        }
        
        try {
            executeQuery("DELETE FROM social_icons WHERE id = ? AND page_id = ?", [$iconId, $pageId]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Social icon deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete social icon'];
        }
    }
    
    /**
     * Legacy method: Add podcast directory link (for backwards compatibility)
     * @deprecated Use addSocialIcon() instead
     */
    public function addPodcastDirectory($pageId, $platformName, $url) {
        return $this->addSocialIcon($pageId, $platformName, $url);
    }
    
    /**
     * Legacy method: Get podcast directories (for backwards compatibility)
     * @deprecated Use getSocialIcons() instead
     */
    public function getPodcastDirectories($pageId) {
        return $this->getSocialIcons($pageId);
    }
    
    /**
     * Legacy method: Delete podcast directory (for backwards compatibility)
     * @deprecated Use deleteSocialIcon() instead
     */
    public function deletePodcastDirectory($iconId, $pageId) {
        return $this->deleteSocialIcon($iconId, $pageId);
    }
    
    /**
     * Add widget to page
     * @param int $pageId
     * @param string $widgetType
     * @param string $title
     * @param array $configData
     * @return array ['success' => bool, 'widget_id' => int|null, 'error' => string|null]
     */
    public function addWidget($pageId, $widgetType, $title, $configData = []) {
        if (empty($title) || empty($widgetType)) {
            return ['success' => false, 'widget_id' => null, 'error' => 'Title and widget type are required'];
        }
        
        // Get max display order
        $maxOrder = fetchOne("SELECT COALESCE(MAX(display_order), 0) as max_order FROM widgets WHERE page_id = ?", [$pageId]);
        $displayOrder = ($maxOrder['max_order'] ?? 0) + 1;
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO widgets (page_id, widget_type, title, config_data, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $pageId,
                $widgetType,
                $title,
                json_encode($configData),
                $displayOrder,
                1
            ]);
            
            $widgetId = $this->pdo->lastInsertId();
            return ['success' => true, 'widget_id' => $widgetId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Widget creation failed: " . $e->getMessage());
            return ['success' => false, 'widget_id' => null, 'error' => 'Failed to create widget'];
        }
    }
    
    /**
     * Update widget
     * @param int $widgetId
     * @param int $pageId
     * @param array $data
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function updateWidget($widgetId, $pageId, $data) {
        $updates = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
        }
        
        if (isset($data['widget_type'])) {
            $updates[] = "widget_type = ?";
            $params[] = $data['widget_type'];
        }
        
        if (isset($data['config_data'])) {
            $updates[] = "config_data = ?";
            $params[] = is_array($data['config_data']) ? json_encode($data['config_data']) : $data['config_data'];
        }
        
        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = (int)$data['is_active'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $params[] = $widgetId;
        $params[] = $pageId;
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE widgets SET " . implode(', ', $updates) . " 
                WHERE id = ? AND page_id = ?
            ");
            $stmt->execute($params);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Widget update failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update widget'];
        }
    }
    
    /**
     * Delete widget
     * @param int $widgetId
     * @param int $pageId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteWidget($widgetId, $pageId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM widgets WHERE id = ? AND page_id = ?");
            $stmt->execute([$widgetId, $pageId]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Widget deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete widget'];
        }
    }
    
    /**
     * Get widget by ID
     * @param int $widgetId
     * @param int $pageId
     * @return array|null
     */
    public function getWidget($widgetId, $pageId) {
        return fetchOne(
            "SELECT * FROM widgets WHERE id = ? AND page_id = ?",
            [$widgetId, $pageId]
        );
    }
    
    /**
     * Update widget display order
     * @param int $pageId
     * @param array $orders Array of ['widget_id' => int, 'display_order' => int]
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function updateWidgetOrder($pageId, $orders) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($orders as $order) {
                if (!isset($order['widget_id']) || !isset($order['display_order'])) {
                    continue;
                }
                
                $stmt = $this->pdo->prepare("
                    UPDATE widgets 
                    SET display_order = ? 
                    WHERE id = ? AND page_id = ?
                ");
                $stmt->execute([
                    (int)$order['display_order'],
                    (int)$order['widget_id'],
                    $pageId
                ]);
            }
            
            $this->pdo->commit();
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Widget order update failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update widget order'];
        }
    }
}

