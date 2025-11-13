<?php
/**
 * Migration: Add podcast_player_enabled column to pages table
 * Run this once to add the column if it doesn't exist
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM pages LIKE 'podcast_player_enabled'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✅ Column 'podcast_player_enabled' already exists in 'pages' table.\n";
        exit(0);
    }
    
    // Add the column (TINYINT(1) for boolean, default 0/false)
    // Try to add after footer_visible, but if that doesn't exist, just add it at the end
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN podcast_player_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER footer_visible");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            // footer_visible doesn't exist, just add the column without specifying position
            $pdo->exec("ALTER TABLE pages ADD COLUMN podcast_player_enabled TINYINT(1) NOT NULL DEFAULT 0");
        } else {
            throw $e;
        }
    }
    
    echo "✅ Successfully added 'podcast_player_enabled' column to 'pages' table.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

