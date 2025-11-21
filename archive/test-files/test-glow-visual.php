<?php
/**
 * Test Glow Visual Output
 * 
 * This script generates actual CSS output to verify glow is visible
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

echo "========================================\n";
echo "TESTING GLOW VISUAL OUTPUT\n";
echo "========================================\n\n";

$page = ['id' => 1, 'username' => 'test', 'theme_id' => 1];
$theme = [
    'id' => 1,
    'widget_styles' => json_encode([
        'border_effect' => 'glow',
        'border_glow_intensity' => 'pronounced',
        'glow_color' => '#ff00ff'
    ]),
    'widget_background' => '#ffffff',
    'page_background' => '#f5f7fa'
];

$cssGenerator = new ThemeCSSGenerator($page, $theme);
$css = $cssGenerator->generateCompleteStyleBlock();

// Extract .widget-item rules
echo "=== .widget-item CSS Rules ===\n";
preg_match_all('/\.widget-item\s*\{[^}]+\}/s', $css, $matches);
foreach ($matches[0] as $i => $rule) {
    echo "\nRule " . ($i + 1) . ":\n";
    echo $rule . "\n";
}

// Check for glow-specific rules
echo "\n=== Glow-Specific Rules ===\n";
if (preg_match('/\.widget-item[^{]*\{[^}]*box-shadow[^}]*glow[^}]*\}/is', $css, $glowMatch)) {
    echo "Found glow rule:\n" . $glowMatch[0] . "\n";
} else {
    // Check for any box-shadow with glow color
    if (preg_match('/\.widget-item[^{]*\{[^}]*box-shadow[^}]*#ff00ff[^}]*\}/is', $css, $colorMatch)) {
        echo "Found box-shadow with glow color:\n" . $colorMatch[0] . "\n";
    } else {
        echo "❌ No glow box-shadow found in CSS!\n";
    }
}

// Check animation
echo "\n=== Animation Rules ===\n";
if (strpos($css, 'glow-pulse') !== false) {
    echo "✅ glow-pulse animation found\n";
    if (preg_match('/@keyframes glow-pulse[^}]+}/s', $css, $animMatch)) {
        echo "Animation definition:\n" . $animMatch[0] . "\n";
    }
} else {
    echo "❌ glow-pulse animation NOT found\n";
}

// Check CSS variables
echo "\n=== CSS Variables ===\n";
$vars = $cssGenerator->generateCSSVariables();
if (preg_match('/--widget-glow-color:[^;]+;/', $vars, $varMatch)) {
    echo "✅ " . trim($varMatch[0]) . "\n";
} else {
    echo "❌ --widget-glow-color NOT found\n";
}
if (preg_match('/--widget-glow-blur:[^;]+;/', $vars, $varMatch)) {
    echo "✅ " . trim($varMatch[0]) . "\n";
} else {
    echo "❌ --widget-glow-blur NOT found\n";
}

// Check for conflicts
echo "\n=== Potential Conflicts ===\n";
$widgetItemCount = substr_count($css, '.widget-item {');
echo "Number of .widget-item { rules: {$widgetItemCount}\n";
if ($widgetItemCount > 1) {
    echo "⚠️  Multiple .widget-item rules - may have specificity issues\n";
}

// Check if shadow is also set
if (strpos($css, 'border_effect') === false && strpos($css, 'shadow') !== false) {
    echo "⚠️  Shadow rules also present - may conflict with glow\n";
}

