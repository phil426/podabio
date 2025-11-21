<?php
/**
 * Apply Arizona Desert Bloom theme profile image settings to all pages using the theme
 * 
 * Settings applied:
 * - Glow effect with sunset orange color
 * - Vibrant, warm glow
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🌵 Apply Arizona Desert Bloom Profile Settings\n";
echo "==========================================\n\n";

// Find the Arizona Desert Bloom theme
try {
    $theme = fetchOne("SELECT id, name FROM themes WHERE name = 'Arizona Desert Bloom' LIMIT 1");
    if (!$theme) {
        echo "❌ Arizona Desert Bloom theme not found. Please create it first.\n";
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
        $currentGlowColor = $page['profile_image_glow_color'] ?? '';
        $currentGlowWidth = $page['profile_image_glow_width'] ?? 0;
        
        $needsUpdate = false;
        
        // Check if glow needs updating
        if ($currentEffect !== 'glow' || 
            $currentGlowColor !== '#FF6B35' || 
            $currentGlowWidth != 20) {
            $needsUpdate = true;
        }
        
        if (!$needsUpdate) {
            echo "⏭️  Page '{$page['username']}' ({$page['id']}) already has correct settings\n";
            $skipped++;
            continue;
        }
        
        // Update the page with glow effect
        $updateData = [
            'profile_image_effect' => 'glow',
            'profile_image_glow_color' => '#FF6B35', // Sunset orange
            'profile_image_glow_width' => 20 // Vibrant glow
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
        echo "   - Glow effect with sunset orange (#FF6B35)\n";
        echo "   - Glow width: 20px\n";
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

