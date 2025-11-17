<?php
/**
 * Test Widget Shape Fix
 * 
 * This script tests the widget shape flow to verify the fix works correctly.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Theme.php';

echo "========================================\n";
echo "TESTING WIDGET SHAPE FIX\n";
echo "========================================\n\n";

$themeClass = new Theme();

// Test 1: Theme with square corner (none: '0px')
echo "TEST 1: Theme with square corner\n";
echo "----------------------------------------\n";
$themeTokens1 = [
    'corner' => [
        'none' => '0px'
    ]
];
$defaults = [
    'corner' => [
        'none' => '0px',
        'sm' => '0.375rem',
        'md' => '0.75rem',
        'lg' => '1.5rem',
        'pill' => '9999px'
    ]
];
$page = ['shape_tokens' => null];
$theme = ['shape_tokens' => json_encode($themeTokens1)];

$shapeTokens1 = $themeClass->getShapeTokens($page, $theme);
echo "Expected: corner should have only 'none' => '0px'\n";
echo "Actual: " . json_encode($shapeTokens1['corner'] ?? null) . "\n";
$corner1 = $shapeTokens1['corner'] ?? [];
if (count($corner1) === 1 && isset($corner1['none']) && $corner1['none'] === '0px') {
    echo "✓ PASS: Square corner correctly isolated\n\n";
} else {
    echo "✗ FAIL: Square corner not correctly isolated\n\n";
}

// Test 2: Theme with rounded corner (md: '0.75rem')
echo "TEST 2: Theme with rounded corner\n";
echo "----------------------------------------\n";
$themeTokens2 = [
    'corner' => [
        'md' => '0.75rem'
    ]
];
$theme = ['shape_tokens' => json_encode($themeTokens2)];

$shapeTokens2 = $themeClass->getShapeTokens($page, $theme);
echo "Expected: corner should have only 'md' => '0.75rem'\n";
echo "Actual: " . json_encode($shapeTokens2['corner'] ?? null) . "\n";
$corner2 = $shapeTokens2['corner'] ?? [];
if (count($corner2) === 1 && isset($corner2['md']) && $corner2['md'] === '0.75rem') {
    echo "✓ PASS: Rounded corner correctly isolated\n\n";
} else {
    echo "✗ FAIL: Rounded corner not correctly isolated\n\n";
}

// Test 3: Theme with pill corner (pill: '9999px')
echo "TEST 3: Theme with pill corner\n";
echo "----------------------------------------\n";
$themeTokens3 = [
    'corner' => [
        'pill' => '9999px'
    ]
];
$theme = ['shape_tokens' => json_encode($themeTokens3)];

$shapeTokens3 = $themeClass->getShapeTokens($page, $theme);
echo "Expected: corner should have only 'pill' => '9999px'\n";
echo "Actual: " . json_encode($shapeTokens3['corner'] ?? null) . "\n";
$corner3 = $shapeTokens3['corner'] ?? [];
if (count($corner3) === 1 && isset($corner3['pill']) && $corner3['pill'] === '9999px') {
    echo "✓ PASS: Pill corner correctly isolated\n\n";
} else {
    echo "✗ FAIL: Pill corner not correctly isolated\n\n";
}

// Test 4: Theme with no corner (should use defaults)
echo "TEST 4: Theme with no corner (defaults)\n";
echo "----------------------------------------\n";
$theme = ['shape_tokens' => null];

$shapeTokens4 = $themeClass->getShapeTokens($page, $theme);
echo "Expected: corner should have all default values\n";
echo "Actual: " . json_encode($shapeTokens4['corner'] ?? null) . "\n";
$corner4 = $shapeTokens4['corner'] ?? [];
if (count($corner4) > 1 && isset($corner4['md'])) {
    echo "✓ PASS: Defaults correctly used when theme has no corner\n\n";
} else {
    echo "✗ FAIL: Defaults not correctly used\n\n";
}

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "If all tests pass, the fix should work correctly.\n";
echo "Check the PHP error log for detailed debug output.\n";

