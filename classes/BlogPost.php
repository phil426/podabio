<?php
/**
 * Blog Post Class
 * PodaBio - Manages blog posts for marketing content
 */

class BlogPost {
    
    /**
     * Get all published posts
     * @param int|null $categoryId Filter by category
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getPublished($categoryId = null, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       u.email as author_email
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.published = 1";
        $params = [];
        
        if ($categoryId !== null) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get all posts (including unpublished) for admin
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAll($limit = 100, $offset = 0) {
        return fetchAll(
            "SELECT p.*, c.name as category_name, u.email as author_email
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             LEFT JOIN users u ON p.author_id = u.id
             ORDER BY p.updated_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    /**
     * Get post by ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        return fetchOne(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    u.email as author_email
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.id = ?",
            [$id]
        );
    }
    
    /**
     * Get post by slug
     * @param string $slug
     * @param bool $publishedOnly
     * @return array|null
     */
    public static function getBySlug($slug, $publishedOnly = true) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       u.email as author_email
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id
                WHERE p.slug = ?";
        
        if ($publishedOnly) {
            $sql .= " AND p.published = 1";
        }
        
        return fetchOne($sql, [$slug]);
    }
    
    /**
     * Create a new post
     * @param array $data
     * @return int|false Post ID or false on failure
     */
    public static function create($data) {
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $excerpt = trim($data['excerpt'] ?? '');
        $slug = $data['slug'] ?? self::generateSlug($title);
        $authorId = !empty($data['author_id']) ? (int)$data['author_id'] : null;
        $authorName = trim($data['author_name'] ?? '');
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $tags = trim($data['tags'] ?? '');
        $featuredImage = trim($data['featured_image'] ?? '');
        $featured = isset($data['featured']) ? (int)$data['featured'] : 0;
        $published = isset($data['published']) ? (int)$data['published'] : 0;
        
        if (empty($title) || empty($content)) {
            return false;
        }
        
        // Auto-generate excerpt if not provided
        if (empty($excerpt)) {
            $excerpt = self::generateExcerpt($content);
        }
        
        // Ensure slug is unique
        $slug = self::ensureUniqueSlug($slug);
        
        $result = execute(
            "INSERT INTO blog_posts (title, slug, content, excerpt, author_id, category_id, tags, featured_image, published) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $slug, $content, $excerpt, $authorId, $categoryId, $tags, $featuredImage ?: null, $published]
        );
        
        if ($result) {
            return lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update a post
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        $post = self::getById($id);
        if (!$post) {
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
        
        if (isset($data['excerpt'])) {
            $updates[] = 'excerpt = ?';
            $params[] = trim($data['excerpt']);
        }
        
        if (array_key_exists('category_id', $data)) {
            $updates[] = 'category_id = ?';
            $params[] = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        }
        
        if (isset($data['tags'])) {
            $updates[] = 'tags = ?';
            $params[] = trim($data['tags']);
        }
        
        if (array_key_exists('featured_image', $data)) {
            $updates[] = 'featured_image = ?';
            $params[] = !empty($data['featured_image']) ? trim($data['featured_image']) : null;
        }
        
        if (isset($data['published'])) {
            $updates[] = 'published = ?';
            $params[] = (int)$data['published'];
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $params[] = $id;
        
        return execute(
            "UPDATE blog_posts SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
    }
    
    /**
     * Delete a post
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        return execute("DELETE FROM blog_posts WHERE id = ?", [$id]);
    }
    
    /**
     * Increment view count
     * @param int $id
     * @return bool
     */
    public static function incrementViewCount($id) {
        return execute(
            "UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?",
            [$id]
        );
    }
    
    /**
     * Search posts
     * @param string $query
     * @param bool $publishedOnly
     * @return array
     */
    public static function search($query, $publishedOnly = true) {
        $searchTerm = '%' . trim($query) . '%';
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                WHERE (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ? OR p.tags LIKE ?)";
        
        if ($publishedOnly) {
            $sql .= " AND p.published = 1";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT 50";
        
        return fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Get popular posts
     * @param int $limit
     * @return array
     */
    public static function getPopular($limit = 5) {
        return fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             WHERE p.published = 1 
             ORDER BY p.view_count DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Get recent posts
     * @param int $limit
     * @return array
     */
    public static function getRecent($limit = 5) {
        return fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             WHERE p.published = 1 
             ORDER BY p.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Get related posts by tags
     * @param int $postId
     * @param int $limit
     * @return array
     */
    public static function getRelated($postId, $limit = 3) {
        $post = self::getById($postId);
        if (!$post || empty($post['tags'])) {
            return self::getRecent($limit);
        }
        
        $tags = array_map('trim', explode(',', $post['tags']));
        $tagConditions = [];
        $params = [];
        
        foreach ($tags as $tag) {
            $tagConditions[] = "p.tags LIKE ?";
            $params[] = '%' . $tag . '%';
        }
        
        $params[] = $postId;
        $params[] = $limit;
        
        return fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             WHERE p.published = 1 AND (" . implode(' OR ', $tagConditions) . ") AND p.id != ?
             ORDER BY p.view_count DESC 
             LIMIT ?",
            $params
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
        return $slug ?: 'post';
    }
    
    /**
     * Generate excerpt from content
     * @param string $content
     * @param int $length
     * @return string
     */
    public static function generateExcerpt($content, $length = 160) {
        // Strip HTML tags
        $text = strip_tags($content);
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        // Cut at word boundary
        $text = substr($text, 0, $length);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }
        
        return $text . '...';
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
            $sql = "SELECT id FROM blog_posts WHERE slug = ?";
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
     * Get post count
     * @param bool $publishedOnly
     * @return int
     */
    public static function count($publishedOnly = false) {
        $sql = "SELECT COUNT(*) as count FROM blog_posts";
        if ($publishedOnly) {
            $sql .= " WHERE published = 1";
        }
        $result = fetchOne($sql);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Get posts by author
     * @param int $authorId
     * @param bool $publishedOnly
     * @param int $limit
     * @return array
     */
    public static function getByAuthor($authorId, $publishedOnly = true, $limit = 20) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                WHERE p.author_id = ?";
        
        if ($publishedOnly) {
            $sql .= " AND p.published = 1";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ?";
        
        return fetchAll($sql, [$authorId, $limit]);
    }
}


