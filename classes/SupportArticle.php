<?php
/**
 * Support Article Class
 * PodaBio - Manages support/help documentation articles
 */

class SupportArticle {
    
    /**
     * Get all published articles
     * @param int|null $categoryId Filter by category
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getPublished($categoryId = null, $limit = 50, $offset = 0) {
        $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug 
                FROM support_articles a 
                LEFT JOIN support_categories c ON a.category_id = c.id 
                WHERE a.published = 1";
        $params = [];
        
        if ($categoryId !== null) {
            $sql .= " AND a.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get all articles (including unpublished) for admin
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAll($limit = 100, $offset = 0) {
        return fetchAll(
            "SELECT a.*, c.name as category_name 
             FROM support_articles a 
             LEFT JOIN support_categories c ON a.category_id = c.id 
             ORDER BY a.updated_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    /**
     * Get article by ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        return fetchOne(
            "SELECT a.*, c.name as category_name, c.slug as category_slug 
             FROM support_articles a 
             LEFT JOIN support_categories c ON a.category_id = c.id 
             WHERE a.id = ?",
            [$id]
        );
    }
    
    /**
     * Get article by slug
     * @param string $slug
     * @param bool $publishedOnly
     * @return array|null
     */
    public static function getBySlug($slug, $publishedOnly = true) {
        $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug 
                FROM support_articles a 
                LEFT JOIN support_categories c ON a.category_id = c.id 
                WHERE a.slug = ?";
        
        if ($publishedOnly) {
            $sql .= " AND a.published = 1";
        }
        
        return fetchOne($sql, [$slug]);
    }
    
    /**
     * Create a new article
     * @param array $data
     * @return int|false Article ID or false on failure
     */
    public static function create($data) {
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $slug = $data['slug'] ?? self::generateSlug($title);
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $tags = trim($data['tags'] ?? '');
        $published = isset($data['published']) ? (int)$data['published'] : 0;
        
        if (empty($title) || empty($content)) {
            return false;
        }
        
        // Ensure slug is unique
        $slug = self::ensureUniqueSlug($slug);
        
        $result = execute(
            "INSERT INTO support_articles (title, slug, content, category_id, tags, published) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$title, $slug, $content, $categoryId, $tags, $published]
        );
        
        if ($result) {
            return lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update an article
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        $article = self::getById($id);
        if (!$article) {
            return false;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updates[] = 'title = ?';
            $params[] = trim($data['title']);
        }
        
        if (isset($data['slug'])) {
            $newSlug = self::ensureUniqueSlug(trim($data['slug']), $id);
            $updates[] = 'slug = ?';
            $params[] = $newSlug;
        }
        
        if (isset($data['content'])) {
            $updates[] = 'content = ?';
            $params[] = trim($data['content']);
        }
        
        if (array_key_exists('category_id', $data)) {
            $updates[] = 'category_id = ?';
            $params[] = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        }
        
        if (isset($data['tags'])) {
            $updates[] = 'tags = ?';
            $params[] = trim($data['tags']);
        }
        
        if (isset($data['published'])) {
            $updates[] = 'published = ?';
            $params[] = (int)$data['published'];
        }
        
        if (empty($updates)) {
            return true; // Nothing to update
        }
        
        $params[] = $id;
        
        return execute(
            "UPDATE support_articles SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
    }
    
    /**
     * Delete an article
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        return execute("DELETE FROM support_articles WHERE id = ?", [$id]);
    }
    
    /**
     * Increment view count
     * @param int $id
     * @return bool
     */
    public static function incrementViewCount($id) {
        return execute(
            "UPDATE support_articles SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Search articles
     * @param string $query
     * @param bool $publishedOnly
     * @return array
     */
    public static function search($query, $publishedOnly = true) {
        $searchTerm = '%' . trim($query) . '%';
        
        $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug 
                FROM support_articles a 
                LEFT JOIN support_categories c ON a.category_id = c.id 
                WHERE (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)";
        
        if ($publishedOnly) {
            $sql .= " AND a.published = 1";
        }
        
        $sql .= " ORDER BY a.view_count DESC, a.updated_at DESC LIMIT 50";
        
        return fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Get popular articles
     * @param int $limit
     * @return array
     */
    public static function getPopular($limit = 10) {
        return fetchAll(
            "SELECT a.*, c.name as category_name, c.slug as category_slug 
             FROM support_articles a 
             LEFT JOIN support_categories c ON a.category_id = c.id 
             WHERE a.published = 1 
             ORDER BY a.view_count DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Get recent articles
     * @param int $limit
     * @return array
     */
    public static function getRecent($limit = 10) {
        return fetchAll(
            "SELECT a.*, c.name as category_name, c.slug as category_slug 
             FROM support_articles a 
             LEFT JOIN support_categories c ON a.category_id = c.id 
             WHERE a.published = 1 
             ORDER BY a.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Generate slug from title
     * @param string $title
     * @return string
     */
    public static function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'article';
    }
    
    /**
     * Ensure slug is unique
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    private static function ensureUniqueSlug($slug, $excludeId = null) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $sql = "SELECT id FROM support_articles WHERE slug = ?";
            $params = [$slug];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $existing = fetchOne($sql, $params);
            
            if (!$existing) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Get article count
     * @param bool $publishedOnly
     * @return int
     */
    public static function count($publishedOnly = false) {
        $sql = "SELECT COUNT(*) as count FROM support_articles";
        if ($publishedOnly) {
            $sql .= " WHERE published = 1";
        }
        $result = fetchOne($sql);
        return (int)($result['count'] ?? 0);
    }
}


