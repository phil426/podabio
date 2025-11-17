<?php
/**
 * Test Widget Glow Functionality
 * 
 * This script tests the widget glow flow from database → Theme class → CSS generator → output
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/includes/theme-helpers.php';

echo "========================================\n";
echo "TESTING WIDGET GLOW FUNCTIONALITY\n";
echo "========================================\n\n";

// Test 1: Check convertEnumToCSS for glow
echo "TEST 1: convertEnumToCSS for glow\n";
echo "----------------------------------------\n";
$subtleBlur = convertEnumToCSS('subtle', 'glow_blur');
$pronouncedBlur = convertEnumToCSS('pronounced', 'glow_blur');
$subtleOpacity = convertEnumToCSS('subtle', 'glow_opacity');
$pronouncedOpacity = convertEnumToCSS('pronounced', 'glow_opacity');

echo "subtle blur: {$subtleBlur} (expected: 8px)\n";
echo "pronounced blur: {$pronouncedBlur} (expected: 16px)\n";
echo "subtle opacity: {$subtleOpacity} (expected: 0.5)\n";
echo "pronounced opacity: {$pronouncedOpacity} (expected: 0.8)\n";

$pass1 = ($subtleBlur === '8px' && $pronouncedBlur === '16px' && $subtleOpacity === '0.5' && $pronouncedOpacity === '0.8');
if ($pass1) {
    echo "✓ PASS: convertEnumToCSS works correctly\n\n";
} else {
    echo "✗ FAIL: convertEnumToCSS not working correctly\n\n";
}

// Test 2: CSS Generator with glow effect
echo "TEST 2: CSS Generator with glow effect\n";
echo "----------------------------------------\n";
$page = ['id' => 1, 'username' => 'test', 'theme_id' => 1];
$theme = [
    'id' => 1,
    'widget_styles' => json_encode([
        'border_effect' => 'glow',
        'border_glow_intensity' => 'subtle',
        'glow_color' => '#ff00ff'
    ])
];

$cssGenerator = new ThemeCSSGenerator($page, $theme);
$css = $cssGenerator->generateCompleteStyleBlock();

// Check if glow CSS is generated
$hasGlowBoxShadow = strpos($css, 'box-shadow: 0 0') !== false && strpos($css, '#ff00ff') !== false;
$hasGlowAnimation = strpos($css, 'glow-pulse') !== false;
$hasGlowKeyframes = strpos($css, '@keyframes glow-pulse') !== false;

echo "Has glow box-shadow: " . ($hasGlowBoxShadow ? 'YES' : 'NO') . "\n";
echo "Has glow animation: " . ($hasGlowAnimation ? 'YES' : 'NO') . "\n";
echo "Has glow keyframes: " . ($hasGlowKeyframes ? 'YES' : 'NO') . "\n";

$pass2 = $hasGlowBoxShadow && $hasGlowAnimation && $hasGlowKeyframes;
if ($pass2) {
    echo "✓ PASS: Glow CSS generated correctly\n\n";
} else {
    echo "✗ FAIL: Glow CSS not generated correctly\n";
    // Show relevant CSS snippet
    if (preg_match('/\.widget-item\s*\{[^}]*box-shadow[^}]*\}/s', $css, $matches)) {
        echo "Found widget-item CSS:\n" . $matches[0] . "\n";
    }
    echo "\n";
}

// Test 3: Check CSS variables
echo "TEST 3: CSS Variables for glow\n";
echo "----------------------------------------\n";
$cssVars = $cssGenerator->generateCSSVariables();
$hasGlowColorVar = strpos($cssVars, '--widget-glow-color') !== false;
$hasGlowBlurVar = strpos($cssVars, '--widget-glow-blur') !== false;
$hasGlowOpacityVar = strpos($cssVars, '--widget-glow-opacity') !== false;

echo "Has --widget-glow-color: " . ($hasGlowColorVar ? 'YES' : 'NO') . "\n";
echo "Has --widget-glow-blur: " . ($hasGlowBlurVar ? 'YES' : 'NO') . "\n";
echo "Has --widget-glow-opacity: " . ($hasGlowOpacityVar ? 'YES' : 'NO') . "\n";

$pass3 = $hasGlowColorVar && $hasGlowBlurVar && $hasGlowOpacityVar;
if ($pass3) {
    echo "✓ PASS: Glow CSS variables generated correctly\n\n";
} else {
    echo "✗ FAIL: Glow CSS variables not generated correctly\n\n";
}

// Test 4: Pronounced glow
echo "TEST 4: Pronounced glow intensity\n";
echo "----------------------------------------\n";
$theme2 = [
    'id' => 1,
    'widget_styles' => json_encode([
        'border_effect' => 'glow',
        'border_glow_intensity' => 'pronounced',
        'glow_color' => '#00ff00'
    ])
];

$cssGenerator2 = new ThemeCSSGenerator($page, $theme2);
$css2 = $cssGenerator2->generateCompleteStyleBlock();

$hasPronouncedBlur = strpos($css2, '16px') !== false;
$hasPronouncedColor = strpos($css2, '#00ff00') !== false;

echo "Has 16px blur (pronounced): " . ($hasPronouncedBlur ? 'YES' : 'NO') . "\n";
echo "Has correct color: " . ($hasPronouncedColor ? 'YES' : 'NO') . "\n";

$pass4 = $hasPronouncedBlur && $hasPronouncedColor;
if ($pass4) {
    echo "✓ PASS: Pronounced glow works correctly\n\n";
} else {
    echo "✗ FAIL: Pronounced glow not working correctly\n\n";
}

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
$allPass = $pass1 && $pass2 && $pass3 && $pass4;
if ($allPass) {
    echo "✓ ALL TESTS PASSED - Glow functionality is working!\n";
} else {
    echo "✗ SOME TESTS FAILED - Check the output above\n";
}

