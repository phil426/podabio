<?php
/**
 * Apply Cyberpunk Neon theme profile image settings to all pages using the theme
 * 
 * Settings applied:
 * - 3px cyan border (#00F5FF)
 * - Pink glow effect (#FF00FF)
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎨 Apply Cyberpunk Profile Image Settings\n";
echo "==========================================\n\n";

// Find the Cyberpunk Neon theme
try {
    $theme = fetchOne("SELECT id, name FROM themes WHERE name = 'Cyberpunk Neon' LIMIT 1");
    if (!$theme) {
        echo "❌ Cyberpunk Neon theme not found. Please create it first.\n";
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
        // Check if settings are already applied (to avoid unnecessary updates)
        $currentBorderWidth = $page['profile_image_border_width'] ?? 0;
        $currentBorderColor = $page['profile_image_border_color'] ?? '';
        $currentEffect = $page['profile_image_effect'] ?? 'none';
        $currentGlowColor = $page['profile_image_glow_color'] ?? '';
        
        $needsUpdate = false;
        
        // Check if border needs updating
        if ($currentBorderWidth != 3 || $currentBorderColor !== '#00F5FF') {
            $needsUpdate = true;
        }
        
        // Check if glow needs updating
        $currentGlowWidth = $page['profile_image_glow_width'] ?? 0;
        if ($currentEffect !== 'glow' || $currentGlowColor !== '#7B2CBF' || $currentGlowWidth != 18) {
            $needsUpdate = true;
        }
        
        if (!$needsUpdate) {
            echo "⏭️  Page '{$page['username']}' ({$page['id']}) already has correct settings\n";
            $skipped++;
            continue;
        }
        
        // Update the page
        $updateData = [
            'profile_image_border_width' => 3,
            'profile_image_border_color' => '#00F5FF', // Cyan
            'profile_image_effect' => 'glow',
            'profile_image_glow_color' => '#7B2CBF', // Purple
            'profile_image_glow_width' => 18 // 18px glow width
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
        echo "   - 3px cyan border (#00F5FF)\n";
        echo "   - 18px purple glow effect (#7B2CBF)\n";
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

