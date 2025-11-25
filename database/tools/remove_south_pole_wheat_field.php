<?php
/**
 * Remove South Pole and Wheat Field Themes from Database
 * This script removes the specified themes from the themes table
 * and updates any pages that are using them to use the default theme
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

$pdo = getDB();

// List of themes to delete
$themesToDelete = [
    'South Pole',
    'Wheat Field'
];

try {
    $pdo->beginTransaction();
    
    echo "🗑️  Removing Themes...\n\n";
    
    // Step 1: Find default theme (first system theme)
    echo "Step 1: Finding default theme...\n";
    $stmt = $pdo->prepare("SELECT id, name FROM themes WHERE user_id IS NULL ORDER BY id ASC LIMIT 1");
    $stmt->execute();
    $defaultTheme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$defaultTheme) {
        echo "   ⚠️  No default theme found. Creating fallback...\n";
        // Use theme ID 1 as fallback
        $defaultThemeId = 1;
        $defaultThemeName = "Fallback Theme";
    } else {
        $defaultThemeId = $defaultTheme['id'];
        $defaultThemeName = $defaultTheme['name'];
    }
    echo "   ✅ Default theme: $defaultThemeName (ID: $defaultThemeId)\n\n";
    
    $totalDeleted = 0;
    $totalPagesUpdated = 0;
    $notFound = [];
    $deleted = [];
    
    // Process each theme
    foreach ($themesToDelete as $themeName) {
        echo "==========================================\n";
        echo "Processing: $themeName\n";
        echo "==========================================\n\n";
        
        // Step 2: Find the theme ID
        echo "Step 1: Finding theme...\n";
        $stmt = $pdo->prepare("SELECT id, name, user_id FROM themes WHERE name = ?");
        $stmt->execute([$themeName]);
        $theme = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$theme) {
            echo "   ℹ️  Theme '$themeName' not found in database.\n";
            $notFound[] = $themeName;
            echo "\n";
            continue;
        }
        
        $themeId = $theme['id'];
        $isSystemTheme = ($theme['user_id'] === null);
        echo "   ✅ Found theme: {$theme['name']} (ID: $themeId, " . ($isSystemTheme ? 'System' : 'User') . ")\n\n";
        
        // Step 3: Count pages using this theme
        echo "Step 2: Checking pages using this theme...\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pages WHERE theme_id = ?");
        $stmt->execute([$themeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $pageCount = $result['count'];
        echo "   ℹ️  $pageCount page(s) using this theme\n\n";
        
        // Step 4: Update pages using this theme to use default theme
        if ($pageCount > 0) {
            echo "Step 3: Updating pages to use default theme...\n";
            $stmt = $pdo->prepare("UPDATE pages SET theme_id = ? WHERE theme_id = ?");
            $stmt->execute([$defaultThemeId, $themeId]);
            $updatedPages = $stmt->rowCount();
            echo "   ✅ Updated $updatedPages page(s) to use default theme\n\n";
            $totalPagesUpdated += $updatedPages;
        } else {
            echo "Step 3: No pages to update\n\n";
        }
        
        // Step 5: Delete the theme
        echo "Step 4: Deleting theme...\n";
        if ($isSystemTheme) {
            // System themes don't have user_id, so delete without user_id check
            $stmt = $pdo->prepare("DELETE FROM themes WHERE id = ? AND name = ?");
            $stmt->execute([$themeId, $themeName]);
        } else {
            // User themes need user_id check (though we'll delete anyway if it's in our list)
            $stmt = $pdo->prepare("DELETE FROM themes WHERE id = ? AND name = ?");
            $stmt->execute([$themeId, $themeName]);
        }
        $deletedCount = $stmt->rowCount();
        
        if ($deletedCount > 0) {
            echo "   ✅ Deleted theme: $themeName (ID: $themeId)\n\n";
            $totalDeleted++;
            $deleted[] = $themeName;
        } else {
            echo "   ⚠️  Theme deletion failed\n\n";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n";
    echo "==========================================\n";
    echo "✅ Theme removal complete!\n";
    echo "==========================================\n";
    echo "   Total themes deleted: $totalDeleted\n";
    echo "   Total pages updated: $totalPagesUpdated\n";
    echo "\n";
    
    if (count($deleted) > 0) {
        echo "Deleted themes:\n";
        foreach ($deleted as $name) {
            echo "   ✅ $name\n";
        }
        echo "\n";
    }
    
    if (count($notFound) > 0) {
        echo "Themes not found:\n";
        foreach ($notFound as $name) {
            echo "   ℹ️  $name\n";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

