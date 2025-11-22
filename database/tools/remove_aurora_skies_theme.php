<?php
/**
 * Remove Aurora Skies Theme from Database
 * This script removes the "Aurora Skies" theme from the themes table
 * and updates any pages that are using it to use the default theme
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "🗑️  Removing Aurora Skies Theme...\n\n";
    
    // Step 1: Find the theme ID
    echo "Step 1: Finding Aurora Skies theme...\n";
    $stmt = $pdo->prepare("SELECT id, name FROM themes WHERE name = 'Aurora Skies'");
    $stmt->execute();
    $theme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$theme) {
        echo "   ℹ️  Aurora Skies theme not found in database.\n";
        echo "   ✅ No action needed.\n\n";
        $pdo->rollBack();
        exit(0);
    }
    
    $themeId = $theme['id'];
    echo "   ✅ Found theme: {$theme['name']} (ID: $themeId)\n\n";
    
    // Step 2: Count pages using this theme
    echo "Step 2: Checking pages using this theme...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pages WHERE theme_id = ?");
    $stmt->execute([$themeId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pageCount = $result['count'];
    echo "   ℹ️  $pageCount page(s) using this theme\n\n";
    
    // Step 3: Find default theme (first system theme or ID 1)
    echo "Step 3: Finding default theme...\n";
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
    
    // Step 4: Update pages using Aurora Skies to use default theme
    if ($pageCount > 0) {
        echo "Step 4: Updating pages to use default theme...\n";
        $stmt = $pdo->prepare("UPDATE pages SET theme_id = ? WHERE theme_id = ?");
        $stmt->execute([$defaultThemeId, $themeId]);
        $updatedPages = $stmt->rowCount();
        echo "   ✅ Updated $updatedPages page(s) to use default theme\n\n";
    } else {
        echo "Step 4: No pages to update\n\n";
    }
    
    // Step 5: Delete the Aurora Skies theme
    echo "Step 5: Deleting Aurora Skies theme...\n";
    $stmt = $pdo->prepare("DELETE FROM themes WHERE id = ? AND name = 'Aurora Skies'");
    $stmt->execute([$themeId]);
    $deleted = $stmt->rowCount();
    
    if ($deleted > 0) {
        echo "   ✅ Deleted Aurora Skies theme (ID: $themeId)\n\n";
    } else {
        echo "   ⚠️  Theme deletion failed\n\n";
        $pdo->rollBack();
        exit(1);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "==========================================\n";
    echo "✅ Aurora Skies theme removal complete!\n";
    echo "   Deleted theme: Aurora Skies (ID: $themeId)\n";
    if ($pageCount > 0) {
        echo "   Updated pages: $updatedPages\n";
    }
    echo "==========================================\n";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}












