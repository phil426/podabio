<?php
/**
 * Add Page Background Animation Migration
 * Adds page_background_animate column to pages table
 * Version: 1.0.0
 */

require_once __DIR__ . '/../../config/database.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "Starting page background animation migration...\n\n";
    
    // Add page_background_animate column to pages table
    echo "1. Updating pages table...\n";
    
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN page_background_animate TINYINT(1) NOT NULL DEFAULT 0 AFTER page_background");
        echo "   ✓ Added page_background_animate column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
        echo "   ! page_background_animate column already exists\n";
    }
    
    $pdo->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew capabilities:\n";
    echo "  - Page background gradient animation toggle\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Check if error is due to column already existing
    if (strpos($e->getMessage(), 'Duplicate column') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        echo "\n⚠️  Migration already applied (column may already exist).\n";
        echo "   Error: " . $e->getMessage() . "\n";
        exit(0);
    }
    
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

