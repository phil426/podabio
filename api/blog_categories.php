<?php
/**
 * API: Get Blog Categories
 * Returns list of blog categories for widget configuration
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

try {
    $categories = fetchAll("SELECT id, name, slug FROM blog_categories ORDER BY display_order ASC, name ASC");
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load categories: ' . $e->getMessage()
    ]);
}

