<?php
/**
 * Test script to verify glow settings are saved correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Get the most recent theme update
$pdo = getDB();
$stmt = $pdo->query("SELECT id, name, widget_styles FROM themes WHERE user_id IS NOT NULL ORDER BY id DESC LIMIT 1");
$theme = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$theme) {
    echo "❌ No user themes found\n";
    exit(1);
}

echo "=== THEME GLOW TEST ===\n";
echo "Theme ID: " . $theme['id'] . "\n";
echo "Theme Name: " . $theme['name'] . "\n";
echo "\n";

$widgetStyles = json_decode($theme['widget_styles'] ?? '{}', true);
echo "Widget Styles JSON: " . ($theme['widget_styles'] ?? 'NULL') . "\n";
echo "\n";
echo "Parsed Widget Styles:\n";
print_r($widgetStyles);
echo "\n";

$borderEffect = $widgetStyles['border_effect'] ?? 'NOT SET';
echo "Border Effect: " . $borderEffect . "\n";

if ($borderEffect === 'glow') {
    echo "✅ Glow is ENABLED!\n";
    echo "  - Glow Intensity: " . ($widgetStyles['border_glow_intensity'] ?? 'NOT SET') . "\n";
    echo "  - Glow Color: " . ($widgetStyles['glow_color'] ?? 'NOT SET') . "\n";
} else {
    echo "❌ Glow is NOT enabled (border_effect = " . $borderEffect . ")\n";
    echo "   Expected: 'glow'\n";
}

