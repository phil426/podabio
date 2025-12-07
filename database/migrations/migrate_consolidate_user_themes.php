<?php
/**
 * Consolidate Multiple User Themes to Single Theme Per User
 * Ensures each user has only one active theme
 * Version: 1.0.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "Starting user theme consolidation migration...\n\n";
    
    // Step 1: Find all users with multiple themes
    echo "Step 1: Finding users with multiple themes...\n";
    $usersWithMultipleThemes = fetchAll("
        SELECT user_id, COUNT(*) as theme_count 
        FROM themes 
        WHERE user_id IS NOT NULL AND is_active = 1
        GROUP BY user_id 
        HAVING COUNT(*) > 1
    ");
    
    echo "   Found " . count($usersWithMultipleThemes) . " users with multiple themes\n\n";
    
    // Step 2: For each user, keep the most recently used theme and delete others
    echo "Step 2: Consolidating themes per user...\n";
    $consolidatedCount = 0;
    $deletedCount = 0;
    
    foreach ($usersWithMultipleThemes as $userData) {
        $userId = $userData['user_id'];
        $themeCount = $userData['theme_count'];
        
        echo "   Processing user ID: $userId (has $themeCount themes)\n";
        
        // Find the theme that the user's page is currently using
        $pageTheme = fetchOne("
            SELECT theme_id 
            FROM pages 
            WHERE user_id = ? AND theme_id IS NOT NULL
            LIMIT 1
        ", [$userId]);
        
        $preferredThemeId = $pageTheme ? $pageTheme['theme_id'] : null;
        
        // Get all user themes, ordered by preference:
        // 1. Theme currently used by page (if exists)
        // 2. Most recently updated theme
        // 3. First theme by ID
        $allThemes = fetchAll("
            SELECT id, updated_at 
            FROM themes 
            WHERE user_id = ? AND is_active = 1
            ORDER BY 
                CASE WHEN id = ? THEN 0 ELSE 1 END,
                updated_at DESC,
                id ASC
        ", [$userId, $preferredThemeId]);
        
        if (count($allThemes) === 0) {
            echo "     ⚠️  No themes found for user $userId, skipping\n";
            continue;
        }
        
        // Keep the first theme (most preferred)
        $keepThemeId = $allThemes[0]['id'];
        echo "     ✓ Keeping theme ID: $keepThemeId\n";
        
        // Delete all other themes
        $otherThemeIds = array_slice(array_column($allThemes, 'id'), 1);
        if (count($otherThemeIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($otherThemeIds), '?'));
            $deleteStmt = $pdo->prepare("DELETE FROM themes WHERE id IN ($placeholders)");
            $deleteStmt->execute($otherThemeIds);
            $deletedCount += count($otherThemeIds);
            echo "     ✓ Deleted " . count($otherThemeIds) . " duplicate theme(s)\n";
        }
        
        // Update all pages for this user to point to the kept theme
        $updateStmt = $pdo->prepare("UPDATE pages SET theme_id = ? WHERE user_id = ?");
        $updateStmt->execute([$keepThemeId, $userId]);
        $updatedPages = $updateStmt->rowCount();
        if ($updatedPages > 0) {
            echo "     ✓ Updated $updatedPages page(s) to use theme $keepThemeId\n";
        }
        
        $consolidatedCount++;
    }
    
    echo "\nStep 3: Verifying consolidation...\n";
    $remainingMultiple = fetchAll("
        SELECT user_id, COUNT(*) as theme_count 
        FROM themes 
        WHERE user_id IS NOT NULL AND is_active = 1
        GROUP BY user_id 
        HAVING COUNT(*) > 1
    ");
    
    if (count($remainingMultiple) === 0) {
        echo "   ✅ All users now have at most one theme\n";
    } else {
        echo "   ⚠️  Warning: " . count($remainingMultiple) . " users still have multiple themes\n";
    }
    
    $pdo->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nSummary:\n";
    echo "  - Users consolidated: $consolidatedCount\n";
    echo "  - Themes deleted: $deletedCount\n";
    echo "  - Remaining users with multiple themes: " . count($remainingMultiple) . "\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

