<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

// Get the most recently updated page
$stmt = $pdo->query("SELECT id, username, page_background, page_background_image_url, page_background_image_scale, page_background_image_focal_x, updated_at FROM pages ORDER BY updated_at DESC LIMIT 1");
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if ($page) {
    echo "Page ID: " . $page['id'] . "\n";
    echo "Username: " . $page['username'] . "\n";
    echo "Updated: " . $page['updated_at'] . "\n";
    echo "Background: " . $page['page_background'] . "\n";
    echo "Image URL: " . ($page['page_background_image_url'] ? $page['page_background_image_url'] : "[EMPTY]") . "\n";
    echo "Scale: " . $page['page_background_image_scale'] . "\n";
    echo "Focal X: " . $page['page_background_image_focal_x'] . "\n";
} else {
    echo "No pages found.\n";
}
