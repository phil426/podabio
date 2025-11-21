<?php
/**
 * Migration: Add Advanced Profile Image Styling Fields
 * Adds numeric/color fields for profile image customization
 */

require_once __DIR__ . '/../../config/database.php';

$db = getDB();

try {
    $db->beginTransaction();

    // Add new profile image fields
    $migrations = [
        "ALTER TABLE pages ADD COLUMN profile_image_radius INT NULL DEFAULT NULL AFTER profile_image_border",
        "ALTER TABLE pages ADD COLUMN profile_image_effect ENUM('none','glow','shadow') NOT NULL DEFAULT 'none' AFTER profile_image_radius",
        "ALTER TABLE pages ADD COLUMN profile_image_shadow_color VARCHAR(7) NULL DEFAULT NULL AFTER profile_image_effect",
        "ALTER TABLE pages ADD COLUMN profile_image_shadow_intensity DECIMAL(3,1) NULL DEFAULT NULL AFTER profile_image_shadow_color",
        "ALTER TABLE pages ADD COLUMN profile_image_shadow_depth INT NULL DEFAULT NULL AFTER profile_image_shadow_intensity",
        "ALTER TABLE pages ADD COLUMN profile_image_shadow_blur INT NULL DEFAULT NULL AFTER profile_image_shadow_depth",
        "ALTER TABLE pages ADD COLUMN profile_image_glow_color VARCHAR(7) NULL DEFAULT NULL AFTER profile_image_shadow_blur",
        "ALTER TABLE pages ADD COLUMN profile_image_glow_width INT NULL DEFAULT NULL AFTER profile_image_glow_color",
        "ALTER TABLE pages ADD COLUMN profile_image_border_color VARCHAR(7) NULL DEFAULT NULL AFTER profile_image_glow_width",
        "ALTER TABLE pages ADD COLUMN profile_image_border_width DECIMAL(3,1) NULL DEFAULT NULL AFTER profile_image_border_color",
        "ALTER TABLE pages ADD COLUMN profile_image_spacing_top INT NULL DEFAULT NULL AFTER profile_image_border_width",
        "ALTER TABLE pages ADD COLUMN profile_image_spacing_bottom INT NULL DEFAULT NULL AFTER profile_image_spacing_top"
    ];

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
            echo "✓ Executed: " . substr($sql, 0, 80) . "...\n";
        } catch (PDOException $e) {
            // Check if column already exists
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠ Column already exists, skipping: " . substr($sql, 0, 80) . "...\n";
            } else {
                throw $e;
            }
        }
    }

    if ($db->inTransaction()) {
        $db->commit();
    }
    echo "\n✅ Migration completed successfully!\n";
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

