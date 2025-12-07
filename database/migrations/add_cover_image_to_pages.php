<?php
/**
 * Migration: Add cover_image column to pages table
 * Adds cover_image field for theme generation (separate from profile_image and cover_image_url)
 * 
 * This field is used for:
 * - Theme generation and color extraction in Theme Wizard
 * - Separate from profile_image (page profile display)
 * - Separate from cover_image_url (podcast artwork)
 */

require_once __DIR__ . '/../../config/database.php';

$db = getDB();

try {
    $db->beginTransaction();

    // Add cover_image column for theme generation
    $sql = "ALTER TABLE pages ADD COLUMN cover_image VARCHAR(255) NULL DEFAULT NULL AFTER profile_image";
    
    try {
        $db->exec($sql);
        echo "✓ Executed: Added cover_image column to pages table\n";
    } catch (PDOException $e) {
        // Check if column already exists
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠ Column cover_image already exists, skipping...\n";
        } else {
            throw $e;
        }
    }

    // Add index for cover_image (optional, for performance if needed)
    // Only add index if we expect frequent queries filtering by cover_image
    // For now, we'll skip the index since cover_image will mostly be NULL initially

    if ($db->inTransaction()) {
        $db->commit();
    }
    echo "\n✅ Migration completed successfully!\n";
    echo "Note: cover_image is separate from:\n";
    echo "  - profile_image (page profile display)\n";
    echo "  - cover_image_url (podcast artwork)\n";
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

