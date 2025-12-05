<?php
/**
 * Support Category Class
 * PodaBio - Manages support article categories
 */

class SupportCategory {
    
    /**
     * Get all categories
     * @return array
     */
    public static function getAll() {
        return fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM support_articles a WHERE a.category_id = c.id AND a.published = 1) as article_count
             FROM support_categories c 
             ORDER BY c.display_order ASC, c.name ASC"
        );
    }
    
    /**
     * Get categories with published articles
     * @return array
     */
    public static function getWithArticles() {
        return fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM support_articles a WHERE a.category_id = c.id AND a.published = 1) as article_count
             FROM support_categories c 
             WHERE EXISTS (SELECT 1 FROM support_articles a WHERE a.category_id = c.id AND a.published = 1)
             ORDER BY c.display_order ASC, c.name ASC"
        );
    }
    
    /**
     * Get category by ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        return fetchOne("SELECT * FROM support_categories WHERE id = ?", [$id]);
    }
    
    /**
     * Get category by slug
     * @param string $slug
     * @return array|null
     */
    public static function getBySlug($slug) {
        return fetchOne("SELECT * FROM support_categories WHERE slug = ?", [$slug]);
    }
    
    /**
     * Create a new category
     * @param array $data
     * @return int|false Category ID or false on failure
     */
    public static function create($data) {
        $name = trim($data['name'] ?? '');
        $slug = $data['slug'] ?? self::generateSlug($name);
        $description = trim($data['description'] ?? '');
        $displayOrder = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        
        if (empty($name)) {
            return false;
        }
        
        // Ensure slug is unique
        $slug = self::ensureUniqueSlug($slug);
        
        $result = execute(
            "INSERT INTO support_categories (name, slug, description, display_order) VALUES (?, ?, ?, ?)",
            [$name, $slug, $description, $displayOrder]
        );
        
        if ($result) {
            return lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update a category
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        $category = self::getById($id);
        if (!$category) {
            return false;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $params[] = trim($data['name']);
        }
        
        if (isset($data['slug'])) {
            $newSlug = self::ensureUniqueSlug(trim($data['slug']), $id);
            $updates[] = 'slug = ?';
            $params[] = $newSlug;
        }
        
        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $params[] = trim($data['description']);
        }
        
        if (isset($data['display_order'])) {
            $updates[] = 'display_order = ?';
            $params[] = (int)$data['display_order'];
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $params[] = $id;
        
        return execute(
            "UPDATE support_categories SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
    }
    
    /**
     * Delete a category
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        // Articles in this category will have category_id set to NULL due to ON DELETE SET NULL
        return execute("DELETE FROM support_categories WHERE id = ?", [$id]);
    }
    
    /**
     * Generate slug from name
     * @param string $name
     * @return string
     */
    public static function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'category';
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
            $sql = "SELECT id FROM support_categories WHERE slug = ?";
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
     * Reorder categories
     * @param array $orderMap Array of [id => display_order]
     * @return bool
     */
    public static function reorder($orderMap) {
        foreach ($orderMap as $id => $order) {
            execute(
                "UPDATE support_categories SET display_order = ? WHERE id = ?",
                [(int)$order, (int)$id]
            );
        }
        return true;
    }
}


