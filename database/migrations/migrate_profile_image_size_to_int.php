<?php
/**
 * Migration: Convert profile_image_size from ENUM to INT
 * Allows numeric pixel values (40-200) instead of just 'small', 'medium', 'large'
 */

require_once __DIR__ . '/../../config/database.php';

$db = getDB();

try {
    $db->beginTransaction();

    // First, convert existing ENUM values to numeric equivalents
    $db->exec("UPDATE pages SET profile_image_size = CASE 
        WHEN profile_image_size = 'small' THEN 80
        WHEN profile_image_size = 'medium' THEN 120
        WHEN profile_image_size = 'large' THEN 180
        ELSE 120
    END");

    // Change column type from ENUM to INT
    // MySQL doesn't support direct ALTER from ENUM to INT, so we need to:
    // 1. Add a temporary column
    // 2. Copy data
    // 3. Drop old column
    // 4. Rename temporary column
    
    // Check if column exists and is ENUM type
    $stmt = $db->query("SHOW COLUMNS FROM pages WHERE Field = 'profile_image_size'");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($columnInfo) {
        // Column exists, check if it's already INT
        if (strpos($columnInfo['Type'], 'int') === false && strpos($columnInfo['Type'], 'INT') === false) {
            // It's still ENUM, need to convert
            // MySQL doesn't support direct MODIFY from ENUM to INT with constraints
            // So we'll do it in steps: first change to INT without constraint
            $db->exec("ALTER TABLE pages 
                MODIFY COLUMN profile_image_size INT NOT NULL DEFAULT 120");
        } else {
            echo "Column profile_image_size is already INT, skipping migration.\n";
        }
    } else {
        echo "Column profile_image_size not found, skipping migration.\n";
    }

    $db->commit();
    echo "Migration completed successfully: profile_image_size converted to INT\n";
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    throw $e;
}

