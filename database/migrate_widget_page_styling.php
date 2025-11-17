<?php
/**
 * Widget and Page Styling Migration
 * Adds separate widget and page styling fields
 * Version: 2.0.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "Starting widget and page styling migration...\n\n";
    
    // 1. Update themes table
    echo "1. Updating themes table...\n";
    
    // Add widget_background column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN widget_background VARCHAR(500) NULL AFTER spatial_effect");
        echo "   ✓ Added widget_background column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_background column already exists\n";
    }
    
    // Add widget_border_color column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN widget_border_color VARCHAR(500) NULL AFTER widget_background");
        echo "   ✓ Added widget_border_color column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_border_color column already exists\n";
    }
    
    // Add widget_primary_font column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN widget_primary_font VARCHAR(100) NULL AFTER widget_border_color");
        echo "   ✓ Added widget_primary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_primary_font column already exists\n";
    }
    
    // Add widget_secondary_font column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN widget_secondary_font VARCHAR(100) NULL AFTER widget_primary_font");
        echo "   ✓ Added widget_secondary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_secondary_font column already exists\n";
    }
    
    // Add page_primary_font column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN page_primary_font VARCHAR(100) NULL AFTER widget_secondary_font");
        echo "   ✓ Added page_primary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! page_primary_font column already exists\n";
    }
    
    // Add page_secondary_font column
    try {
        $pdo->exec("ALTER TABLE themes ADD COLUMN page_secondary_font VARCHAR(100) NULL AFTER page_primary_font");
        echo "   ✓ Added page_secondary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! page_secondary_font column already exists\n";
    }
    
    // Migrate existing fonts JSON to separate columns for themes
    echo "\n2. Migrating existing theme fonts from JSON...\n";
    $themes = $pdo->query("SELECT id, fonts FROM themes WHERE fonts IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $migrated = 0;
    foreach ($themes as $theme) {
        $fonts = json_decode($theme['fonts'], true);
        if ($fonts && (isset($fonts['heading']) || isset($fonts['body']))) {
            $pagePrimary = $fonts['heading'] ?? null;
            $pageSecondary = $fonts['body'] ?? null;
            
            if ($pagePrimary || $pageSecondary) {
                $stmt = $pdo->prepare("UPDATE themes SET page_primary_font = ?, page_secondary_font = ? WHERE id = ?");
                $stmt->execute([$pagePrimary, $pageSecondary, $theme['id']]);
                $migrated++;
            }
        }
    }
    echo "   ✓ Migrated fonts for {$migrated} themes\n";
    
    echo "\n3. Updating pages table...\n";
    
    // Add widget_background column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN widget_background VARCHAR(500) NULL AFTER spatial_effect");
        echo "   ✓ Added widget_background column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_background column already exists\n";
    }
    
    // Add widget_border_color column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN widget_border_color VARCHAR(500) NULL AFTER widget_background");
        echo "   ✓ Added widget_border_color column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_border_color column already exists\n";
    }
    
    // Add widget_primary_font column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN widget_primary_font VARCHAR(100) NULL AFTER widget_border_color");
        echo "   ✓ Added widget_primary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_primary_font column already exists\n";
    }
    
    // Add widget_secondary_font column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN widget_secondary_font VARCHAR(100) NULL AFTER widget_primary_font");
        echo "   ✓ Added widget_secondary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! widget_secondary_font column already exists\n";
    }
    
    // Add page_primary_font column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN page_primary_font VARCHAR(100) NULL AFTER widget_secondary_font");
        echo "   ✓ Added page_primary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! page_primary_font column already exists\n";
    }
    
    // Add page_secondary_font column
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN page_secondary_font VARCHAR(100) NULL AFTER page_primary_font");
        echo "   ✓ Added page_secondary_font column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ! page_secondary_font column already exists\n";
    }
    
    // Migrate existing fonts JSON to separate columns for pages
    echo "\n4. Migrating existing page fonts from JSON...\n";
    $pages = $pdo->query("SELECT id, fonts FROM pages WHERE fonts IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $migrated = 0;
    foreach ($pages as $page) {
        $fonts = json_decode($page['fonts'], true);
        if ($fonts && (isset($fonts['heading']) || isset($fonts['body']))) {
            $pagePrimary = $fonts['heading'] ?? null;
            $pageSecondary = $fonts['body'] ?? null;
            
            if ($pagePrimary || $pageSecondary) {
                $stmt = $pdo->prepare("UPDATE pages SET page_primary_font = ?, page_secondary_font = ? WHERE id = ?");
                $stmt->execute([$pagePrimary, $pageSecondary, $page['id']]);
                $migrated++;
            }
        }
    }
    echo "   ✓ Migrated fonts for {$migrated} pages\n";
    
    $pdo->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNew capabilities:\n";
    echo "  - Widget-specific background colors/gradients\n";
    echo "  - Widget-specific border colors/gradients\n";
    echo "  - Widget-specific fonts (primary/secondary)\n";
    echo "  - Page-specific fonts (primary/secondary) - separated from widgets\n";
    
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

