<?php
/**
 * Apply Amazon Tree Frog theme profile image settings to all pages using the theme
 * 
 * Settings applied:
 * - Shadow effect with dark green color
 * - Moderate depth and blur for natural look
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🐸 Apply Amazon Tree Frog Profile Settings\n";
echo "==========================================\n\n";

// Find the Amazon Tree Frog theme
try {
    $theme = fetchOne("SELECT id, name FROM themes WHERE name = 'Amazon Tree Frog' LIMIT 1");
    if (!$theme) {
        echo "❌ Amazon Tree Frog theme not found. Please create it first.\n";
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
            $currentShadowColor !== '#0A3D2A' || 
            $currentShadowDepth != 6 || 
            $currentShadowBlur != 12 ||
            abs($currentShadowIntensity - 0.6) > 0.01) {
            $needsUpdate = true;
        }
        
        if (!$needsUpdate) {
            echo "⏭️  Page '{$page['username']}' ({$page['id']}) already has correct settings\n";
            $skipped++;
            continue;
        }
        
        // Update the page with shadow effect
        $updateData = [
            'profile_image_effect' => 'shadow',
            'profile_image_shadow_color' => '#0A3D2A', // Dark green
            'profile_image_shadow_depth' => 6, // Moderate depth
            'profile_image_shadow_blur' => 12, // Soft blur
            'profile_image_shadow_intensity' => 0.6 // Moderate intensity
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
        echo "   - Shadow effect with dark green (#0A3D2A)\n";
        echo "   - Depth: 6px, Blur: 12px, Intensity: 0.6\n";
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

