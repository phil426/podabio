<?php
/**
 * Migration: Add Featured Widget Fields
 * Adds is_featured and featured_effect columns to widgets table
 * 
 * IMPORTANT: Run database_backup_widgets.php on the server BEFORE running this migration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Widget Featured Fields Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>Widget Featured Fields Migration</h1>";

try {
    // Check if columns already exist
    $columns = $pdo->query("SHOW COLUMNS FROM widgets LIKE 'is_featured'")->fetchAll();
    if (!empty($columns)) {
        echo "<div class='info'><strong>Info:</strong> Column 'is_featured' already exists. Skipping migration.</div>";
        exit;
    }
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Add is_featured column
    $pdo->exec("ALTER TABLE widgets ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active");
    echo "<div class='success'>✓ Added 'is_featured' column</div>";
    
    // Add featured_effect column
    $pdo->exec("ALTER TABLE widgets ADD COLUMN featured_effect VARCHAR(50) DEFAULT NULL AFTER is_featured");
    echo "<div class='success'>✓ Added 'featured_effect' column</div>";
    
    // Add index for featured widgets
    $pdo->exec("CREATE INDEX idx_is_featured ON widgets(is_featured)");
    echo "<div class='success'>✓ Added index on 'is_featured'</div>";
    
    echo "<div class='success'><strong>Migration completed successfully!</strong></div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>✗ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(1);
}

echo "</body></html>";

