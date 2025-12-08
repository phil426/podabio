<?php
/**
 * Migration: Add avatar_url column to users table
 */

require_once __DIR__ . '/../../config/database.php';

$db = getDB();

try {
    $sql = "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL DEFAULT NULL AFTER email";
    $db->exec($sql);
    echo "✓ Added 'avatar_url' column to 'users' table.\n";
    echo "\n✅ Migration completed successfully!\n";
} catch (PDOException $e) {
    // Check if column already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "⚠ Column 'avatar_url' already exists, skipping migration.\n";
        exit(0); // Exit successfully if column already exists
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

