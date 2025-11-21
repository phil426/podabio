<?php
/**
 * Apply Notebook theme profile image settings to all pages using the theme
 * 
 * Settings applied:
 * - Very subtle shadow effect
 * - Elegant, refined shadow
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "📓 Apply Notebook Profile Settings\n";
echo "==========================================\n\n";

// Find the Notebook theme
try {
    $theme = fetchOne("SELECT id, name FROM themes WHERE name = 'Notebook' LIMIT 1");
    if (!$theme) {
        echo "❌ Notebook theme not found. Please create it first.\n";
        exit(1);
    }
    echo "✅ Found theme: {$theme['name']} (ID: {$theme['id']})\n\n";
} catch (PDOException $e) {
    echo "❌ Failed to find theme: " . $e->getMessage() . "\n";
    exit(1);
}

// Find all pages using this theme
try {
    $pages = fetchAll("SELECT id, username, podcast_name FROM pages WHERE theme_id = ?", [$theme['id']]);
    echo "📄 Found " . count($pages) . " page(s) using this theme\n\n";
    
    if (count($pages) === 0) {
        echo "ℹ️  No pages are using this theme yet. Apply the theme to a page first.\n";
        exit(0);
    }
} catch (PDOException $e) {
    echo "❌ Failed to find pages: " . $e->getMessage() . "\n";
    exit(1);
}

// Apply profile image settings to each page
$updated = 0;
$skipped = 0;

foreach ($pages as $page) {
    try {
        // Check if settings are already applied
        $currentEffect = $page['profile_image_effect'] ?? 'none';
        $currentShadowColor = $page['profile_image_shadow_color'] ?? '';
        $currentShadowDepth = $page['profile_image_shadow_depth'] ?? 0;
        $currentShadowBlur = $page['profile_image_shadow_blur'] ?? 0;
        $currentShadowIntensity = $page['profile_image_shadow_intensity'] ?? 0;
        
        $needsUpdate = false;
        
        // Check if shadow needs updating
        if ($currentEffect !== 'shadow' || 
            $currentShadowColor !== '#2C2C2C' || 
            $currentShadowDepth != 2 || 
            $currentShadowBlur != 5 ||
            abs($currentShadowIntensity - 0.2) > 0.01) {
            $needsUpdate = true;
        }
        
        if (!$needsUpdate) {
            echo "⏭️  Page '{$page['username']}' ({$page['id']}) already has correct settings\n";
            $skipped++;
            continue;
        }
        
        // Update the page with very subtle shadow effect
        $updateData = [
            'profile_image_effect' => 'shadow',
            'profile_image_shadow_color' => '#2C2C2C', // Charcoal
            'profile_image_shadow_depth' => 2, // Very subtle depth
            'profile_image_shadow_blur' => 5, // Soft blur
            'profile_image_shadow_intensity' => 0.2 // Low intensity
        ];
        
        $setSql = [];
        $values = [];
        foreach ($updateData as $column => $value) {
            $setSql[] = "{$column} = ?";
            $values[] = $value;
        }
        $values[] = $page['id'];
        
        $sql = "UPDATE pages SET " . implode(', ', $setSql) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        echo "✅ Updated page '{$page['username']}' ({$page['id']})\n";
        echo "   - Very subtle shadow effect with charcoal (#2C2C2C)\n";
        echo "   - Depth: 2px, Blur: 5px, Intensity: 0.2\n";
        $updated++;
        
    } catch (PDOException $e) {
        echo "❌ Failed to update page '{$page['username']}' ({$page['id']}): " . $e->getMessage() . "\n";
        continue;
    }
}

echo "\n";
echo "==========================================\n";
echo "✅ Complete!\n";
echo "   Updated: {$updated} page(s)\n";
echo "   Skipped: {$skipped} page(s) (already correct)\n";
echo "==========================================\n";

