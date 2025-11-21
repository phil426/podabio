<?php
/**
 * Theme System Migration
 * Adds user themes, page backgrounds, widget styles, and spatial effects
 * Version: 1.1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "Starting theme system migration...\n\n";
    
    // 1. Update themes table
    echo "1. Updating themes table...\n";
    
    // Add user_id column (NULL for system themes, user_id for custom themes)
    $pdo->exec("ALTER TABLE themes ADD COLUMN user_id INT UNSIGNED NULL AFTER preview_image");
    echo "   ✓ Added user_id column\n";
    
    // Add page_background column
    $pdo->exec("ALTER TABLE themes ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts");
    echo "   ✓ Added page_background column\n";
    
    // Add widget_styles column (JSON)
    $pdo->exec("ALTER TABLE themes ADD COLUMN widget_styles JSON NULL AFTER page_background");
    echo "   ✓ Added widget_styles column\n";
    
    // Add spatial_effect column
    $pdo->exec("ALTER TABLE themes ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles");
    echo "   ✓ Added spatial_effect column\n";
    
    // Add index for user_id
    $pdo->exec("CREATE INDEX idx_user_id ON themes(user_id)");
    echo "   ✓ Added index on user_id\n";
    
    // Add foreign key constraint (only if users table exists and has id column)
    try {
        $pdo->exec("ALTER TABLE themes ADD CONSTRAINT fk_themes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        echo "   ✓ Added foreign key constraint\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false && strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
        echo "   ! Foreign key constraint may already exist\n";
    }
    
    // Ensure all existing themes have user_id = NULL (system themes)
    $pdo->exec("UPDATE themes SET user_id = NULL WHERE user_id IS NULL");
    echo "   ✓ Set existing themes as system themes (user_id = NULL)\n";
    
    echo "\n2. Updating pages table...\n";
    
    // Add page_background column
    $pdo->exec("ALTER TABLE pages ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts");
    echo "   ✓ Added page_background column\n";
    
    // Add widget_styles column (JSON)
    $pdo->exec("ALTER TABLE pages ADD COLUMN widget_styles JSON NULL AFTER page_background");
    echo "   ✓ Added widget_styles column\n";
    
    // Add spatial_effect column
    $pdo->exec("ALTER TABLE pages ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles");
    echo "   ✓ Added spatial_effect column\n";
    
    $pdo->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew capabilities:\n";
    echo "  - User-created custom themes (themes.user_id)\n";
    echo "  - Page background customization (solid/gradient)\n";
    echo "  - Widget styling system (border, glow, spacing, shape)\n";
    echo "  - Spatial page effects (none, glass, depth, floating)\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Check if error is due to column already existing
    if (strpos($e->getMessage(), 'Duplicate column') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        echo "\n⚠️  Migration already applied (columns may already exist).\n";
        echo "   Error: " . $e->getMessage() . "\n";
        exit(0);
    }
    
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

