<?php
/**
 * Test script to verify glow CSS is generated correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

// Get page and theme
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pages LIMIT 1");
$stmt->execute();
$pageData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pageData) {
    echo "❌ No page found\n";
    exit(1);
}

$theme = new Theme();
$themeData = $theme->getTheme($pageData['theme_id'] ?? 0);

if (!$themeData) {
    echo "❌ Theme not found\n";
    exit(1);
}

echo "=== GLOW CSS GENERATION TEST ===\n";
echo "Page ID: " . $pageData['id'] . "\n";
echo "Theme ID: " . $themeData['id'] . "\n";
echo "Theme Name: " . $themeData['name'] . "\n";
echo "\n";

// Check widget_styles
$widgetStyles = json_decode($themeData['widget_styles'] ?? '{}', true);
echo "Widget Styles:\n";
print_r($widgetStyles);
echo "\n";

// Generate CSS
$cssGenerator = new ThemeCSSGenerator($pageData, $themeData);
$css = $cssGenerator->generateCompleteStyleBlock();

// Check for glow CSS
echo "=== CSS CHECK ===\n";
if (strpos($css, 'glow-pulse') !== false) {
    echo "✅ glow-pulse animation found in CSS\n";
} else {
    echo "❌ glow-pulse animation NOT found in CSS\n";
}

if (strpos($css, 'box-shadow') !== false && strpos($css, 'rgba') !== false) {
    echo "✅ box-shadow with rgba found in CSS\n";
    // Extract the glow box-shadow line
    $lines = explode("\n", $css);
    foreach ($lines as $line) {
        if (strpos($line, 'box-shadow') !== false && strpos($line, 'rgba') !== false && strpos($line, '.widget-item') !== false) {
            echo "   Found: " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ box-shadow with rgba NOT found in CSS\n";
}

// Check for border_effect
$borderEffect = $widgetStyles['border_effect'] ?? 'NOT SET';
echo "\nBorder Effect: " . $borderEffect . "\n";

if ($borderEffect === 'glow') {
    echo "✅ Border effect is 'glow'\n";
    echo "   Glow should be applied!\n";
} else {
    echo "❌ Border effect is NOT 'glow' (it's '" . $borderEffect . "')\n";
}

