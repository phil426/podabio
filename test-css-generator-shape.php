<?php
/**
 * Test CSS Generator Shape Logic
 * 
 * This script tests the CSS generator's border-radius resolution logic.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

echo "========================================\n";
echo "TESTING CSS GENERATOR SHAPE LOGIC\n";
echo "========================================\n\n";

$themeClass = new Theme();

// Test 1: Single corner value (from theme)
echo "TEST 1: Single corner value (theme has corner)\n";
echo "----------------------------------------\n";
$page = ['id' => 1, 'username' => 'test', 'theme_id' => 1, 'shape_tokens' => null];
$theme = [
    'id' => 1,
    'shape_tokens' => json_encode([
        'corner' => [
            'none' => '0px'
        ]
    ])
];

$cssGenerator = new ThemeCSSGenerator($page, $theme);
// Call generateCSSVariables to set resolvedBorderRadius
$cssGenerator->generateCSSVariables();

$reflection = new ReflectionClass($cssGenerator);
$shapeTokensProp = $reflection->getProperty('shapeTokens');
$shapeTokensProp->setAccessible(true);
$shapeTokens = $shapeTokensProp->getValue($cssGenerator);

$resolvedBorderRadiusProp = $reflection->getProperty('resolvedBorderRadius');
$resolvedBorderRadiusProp->setAccessible(true);
$resolvedBorderRadius = $resolvedBorderRadiusProp->getValue($cssGenerator);

echo "shapeTokens.corner: " . json_encode($shapeTokens['corner'] ?? null) . "\n";
echo "resolvedBorderRadius: " . ($resolvedBorderRadius ?? 'NULL') . "\n";
echo "Expected: 0px\n";

if ($resolvedBorderRadius === '0px') {
    echo "✓ PASS: Square corner correctly resolved\n\n";
} else {
    echo "✗ FAIL: Square corner not correctly resolved\n\n";
}

// Test 2: Multiple corner values (from defaults)
echo "TEST 2: Multiple corner values (theme has no corner)\n";
echo "----------------------------------------\n";
$theme = [
    'id' => 1,
    'shape_tokens' => null
];

$cssGenerator = new ThemeCSSGenerator($page, $theme);
// Call generateCSSVariables to set resolvedBorderRadius
$cssGenerator->generateCSSVariables();

$shapeTokens = $shapeTokensProp->getValue($cssGenerator);
$resolvedBorderRadius = $resolvedBorderRadiusProp->getValue($cssGenerator);

echo "shapeTokens.corner: " . json_encode($shapeTokens['corner'] ?? null) . "\n";
echo "resolvedBorderRadius: " . ($resolvedBorderRadius ?? 'NULL') . "\n";
echo "Expected: 0.75rem (md, rounded default)\n";

if ($resolvedBorderRadius === '0.75rem') {
    echo "✓ PASS: Default rounded corner correctly resolved\n\n";
} else {
    echo "✗ FAIL: Default rounded corner not correctly resolved\n\n";
}

// Test 3: Pill corner
echo "TEST 3: Pill corner\n";
echo "----------------------------------------\n";
$theme = [
    'id' => 1,
    'shape_tokens' => json_encode([
        'corner' => [
            'pill' => '9999px'
        ]
    ])
];

$cssGenerator = new ThemeCSSGenerator($page, $theme);
// Call generateCSSVariables to set resolvedBorderRadius
$cssGenerator->generateCSSVariables();

$shapeTokens = $shapeTokensProp->getValue($cssGenerator);
$resolvedBorderRadius = $resolvedBorderRadiusProp->getValue($cssGenerator);

echo "shapeTokens.corner: " . json_encode($shapeTokens['corner'] ?? null) . "\n";
echo "resolvedBorderRadius: " . ($resolvedBorderRadius ?? 'NULL') . "\n";
echo "Expected: 9999px\n";

if ($resolvedBorderRadius === '9999px') {
    echo "✓ PASS: Pill corner correctly resolved\n\n";
} else {
    echo "✗ FAIL: Pill corner not correctly resolved\n\n";
}

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "If all tests pass, the CSS generator should work correctly.\n";

