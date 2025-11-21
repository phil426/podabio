<?php
/**
 * Migration: Add rss_feed_url column to pages table
 * Run this once to add the column if it doesn't exist
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM pages LIKE 'rss_feed_url'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✅ Column 'rss_feed_url' already exists in 'pages' table.\n";
        exit(0);
    }
    
    // Add the column
    $pdo->exec("ALTER TABLE pages ADD COLUMN rss_feed_url VARCHAR(500) NULL DEFAULT NULL AFTER custom_domain");
    
    echo "✅ Successfully added 'rss_feed_url' column to 'pages' table.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

