<?php
/**
 * Migration: Add Page Name Effect Field
 * Adds page_name_effect column to pages table
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Page Name Effect Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>Page Name Effect Migration</h1>";

try {
    // Check if column already exists
    $columns = $pdo->query("SHOW COLUMNS FROM pages LIKE 'page_name_effect'")->fetchAll();
    if (!empty($columns)) {
        echo "<div class='info'><strong>Info:</strong> Column 'page_name_effect' already exists. Skipping migration.</div>";
        exit;
    }
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Add page_name_effect column with default value of NULL (no effect)
    $pdo->exec("ALTER TABLE pages ADD COLUMN page_name_effect VARCHAR(50) DEFAULT NULL AFTER podcast_name");
    echo "<div class='success'>✓ Added 'page_name_effect' column</div>";
    
    // Add index for performance (optional, but useful if filtering by effect)
    try {
        $pdo->exec("CREATE INDEX idx_page_name_effect ON pages(page_name_effect)");
        echo "<div class='success'>✓ Added index on 'page_name_effect'</div>";
    } catch (PDOException $e) {
        // Index might already exist or creation failed, but that's okay
        echo "<div class='info'><strong>Info:</strong> Index creation skipped or already exists.</div>";
    }
    
    echo "<div class='success'><strong>Migration completed successfully!</strong></div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>✗ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(1);
}

echo "</body></html>";

