<?php
/**
 * Migration: Add Social Icon Visibility Field
 * Adds is_active column to social_icons table
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Social Icon Visibility Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>Social Icon Visibility Migration</h1>";

try {
    // Check if column already exists
    $columns = $pdo->query("SHOW COLUMNS FROM social_icons LIKE 'is_active'")->fetchAll();
    if (!empty($columns)) {
        echo "<div class='info'><strong>Info:</strong> Column 'is_active' already exists. Skipping migration.</div>";
        exit;
    }
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Add is_active column with default value of 1 (all existing icons visible)
    $pdo->exec("ALTER TABLE social_icons ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER display_order");
    echo "<div class='success'>✓ Added 'is_active' column</div>";
    
    // Set all existing social icons to active (default)
    $pdo->exec("UPDATE social_icons SET is_active = 1 WHERE is_active IS NULL");
    echo "<div class='success'>✓ Set all existing social icons to active</div>";
    
    // Add index for performance
    $pdo->exec("CREATE INDEX idx_is_active ON social_icons(is_active)");
    echo "<div class='success'>✓ Added index on 'is_active'</div>";
    
    echo "<div class='success'><strong>Migration completed successfully!</strong></div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>✗ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(1);
}

echo "</body></html>";

