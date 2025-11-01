<?php
/**
 * List all pages in database
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "Pages in Database\n";
echo "=================\n\n";

$pages = $pdo->query("SELECT id, user_id, username, theme_id, is_active FROM pages LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

if (empty($pages)) {
    echo "No pages found in database.\n";
} else {
    foreach ($pages as $page) {
        echo "ID: {$page['id']}, Username: {$page['username']}, Theme ID: " . ($page['theme_id'] ?: 'NULL') . ", Active: " . ($page['is_active'] ? 'Yes' : 'No') . "\n";
    }
}

