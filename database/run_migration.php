<?php
/**
 * Migration Script: Rename podcast_directories to social_icons
 * Run this script once to update your database
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    
    // Check if podcast_directories table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'podcast_directories'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Check if social_icons already exists (migration already run)
        $newTableExists = $pdo->query("SHOW TABLES LIKE 'social_icons'")->rowCount() > 0;
        if ($newTableExists) {
            echo "✓ Migration already completed - social_icons table exists.\n";
            exit(0);
        } else {
            echo "✗ Error: podcast_directories table not found and social_icons doesn't exist.\n";
            echo "Please run the schema.sql file first to create the tables.\n";
            exit(1);
        }
    }
    
    echo "Starting migration: podcast_directories → social_icons\n";
    echo "---\n";
    
    // Get count before migration
    $count = $pdo->query("SELECT COUNT(*) as count FROM podcast_directories")->fetch()['count'];
    echo "Found $count records to migrate\n";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Rename the table
        $pdo->exec("RENAME TABLE podcast_directories TO social_icons");
        
        // Update table comment
        $pdo->exec("ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages'");
        
        // Commit transaction
        $pdo->commit();
        
        echo "✓ Table renamed successfully\n";
        echo "✓ Migration completed successfully!\n";
        echo "---\n";
        echo "Next steps:\n";
        echo "1. Verify data: SELECT COUNT(*) FROM social_icons;\n";
        echo "2. Test the editor to ensure social icons are working\n";
        echo "3. Delete this migration script for security\n";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}

