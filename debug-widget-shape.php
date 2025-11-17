<?php
/**
 * Widget Shape Debugging Script
 * 
 * This script traces the widget shape flow from database → Theme class → CSS generator → output
 * Run this to see exactly what's happening at each step.
 * 
 * Usage: php debug-widget-shape.php [theme_id]
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/classes/Page.php';

$themeId = isset($argv[1]) ? (int)$argv[1] : null;

if (!$themeId) {
    echo "Usage: php debug-widget-shape.php [theme_id]\n";
    echo "Example: php debug-widget-shape.php 1\n";
    exit(1);
}

echo "========================================\n";
echo "WIDGET SHAPE DEBUGGING SCRIPT\n";
echo "========================================\n\n";

// Step 1: Check database directly
echo "STEP 1: Database Values\n";
echo "----------------------------------------\n";
$theme = fetchOne("SELECT id, name, shape_tokens, widget_styles FROM themes WHERE id = ?", [$themeId]);
if (!$theme) {
    echo "ERROR: Theme not found!\n";
    exit(1);
}

echo "Theme ID: {$theme['id']}\n";
echo "Theme Name: {$theme['name']}\n";
echo "shape_tokens (raw): " . ($theme['shape_tokens'] ?? 'NULL') . "\n";
echo "widget_styles (raw): " . ($theme['widget_styles'] ?? 'NULL') . "\n\n";

$shapeTokens = json_decode($theme['shape_tokens'] ?? '{}', true);
$widgetStyles = json_decode($theme['widget_styles'] ?? '{}', true);

echo "shape_tokens (parsed):\n";
print_r($shapeTokens);
echo "\n";

echo "widget_styles (parsed):\n";
print_r($widgetStyles);
echo "\n";

// Step 2: Check Theme class
echo "STEP 2: Theme Class\n";
echo "----------------------------------------\n";
$themeClass = new Theme();
$themeData = $themeClass->getTheme($themeId);
if (!$themeData) {
    echo "ERROR: Could not load theme via Theme class!\n";
    exit(1);
}

$themeShapeTokens = json_decode($themeData['shape_tokens'] ?? '{}', true);
echo "Theme class shape_tokens:\n";
print_r($themeShapeTokens);
echo "\n";

// Step 3: Get a page using this theme
echo "STEP 3: Page Data\n";
echo "----------------------------------------\n";
$page = fetchOne("SELECT id, username, theme_id FROM pages WHERE theme_id = ? LIMIT 1", [$themeId]);
if (!$page) {
    echo "WARNING: No page found using this theme. Creating mock page...\n";
    $page = ['id' => 0, 'username' => 'test', 'theme_id' => $themeId];
}

echo "Page ID: {$page['id']}\n";
echo "Page Username: {$page['username']}\n";
echo "Page Theme ID: {$page['theme_id']}\n\n";

// Step 4: Check ThemeCSSGenerator
echo "STEP 4: CSS Generator\n";
echo "----------------------------------------\n";
$cssGenerator = new ThemeCSSGenerator($page, $themeData);

// Use reflection to access private properties
$reflection = new ReflectionClass($cssGenerator);
$shapeTokensProp = $reflection->getProperty('shapeTokens');
$shapeTokensProp->setAccessible(true);
$cssShapeTokens = $shapeTokensProp->getValue($cssGenerator);

$resolvedBorderRadiusProp = $reflection->getProperty('resolvedBorderRadius');
$resolvedBorderRadiusProp->setAccessible(true);
$resolvedBorderRadius = $resolvedBorderRadiusProp->getValue($cssGenerator);

echo "CSS Generator shapeTokens:\n";
print_r($cssShapeTokens);
echo "\n";

echo "CSS Generator resolvedBorderRadius: " . ($resolvedBorderRadius ?? 'NULL') . "\n\n";

// Step 5: Generate CSS and check output
echo "STEP 5: Generated CSS\n";
echo "----------------------------------------\n";
$css = $cssGenerator->generateCompleteStyleBlock();

// Extract widget-item border-radius
if (preg_match('/\.widget-item\s*\{[^}]*border-radius:\s*([^;]+);/', $css, $matches)) {
    echo "Found .widget-item border-radius: {$matches[1]}\n";
} else {
    echo "WARNING: Could not find .widget-item border-radius in CSS!\n";
}

// Extract CSS variable
if (preg_match('/--button-corner-radius:\s*([^;]+);/', $css, $matches)) {
    echo "Found --button-corner-radius: {$matches[1]}\n";
} else {
    echo "WARNING: Could not find --button-corner-radius in CSS!\n";
}

echo "\n";

// Step 6: Check widget_styles from Theme class
echo "STEP 6: Widget Styles from Theme Class\n";
echo "----------------------------------------\n";
$widgetStylesFromTheme = $themeClass->getWidgetStyles($page, $themeData);
echo "Widget styles:\n";
print_r($widgetStylesFromTheme);
echo "\n";

// Step 7: Summary
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Database shape_tokens.corner: " . json_encode($shapeTokens['corner'] ?? null) . "\n";
echo "CSS Generator resolvedBorderRadius: " . ($resolvedBorderRadius ?? 'NULL') . "\n";
echo "Expected values:\n";
echo "  - square: 0px\n";
echo "  - rounded: 0.75rem\n";
echo "  - pill: 9999px\n";
echo "\n";

// Check if values match
$corner = $shapeTokens['corner'] ?? [];
$expectedValues = [
    'none' => '0px',
    'md' => '0.75rem',
    'pill' => '9999px'
];

echo "Checking corner values:\n";
foreach ($expectedValues as $key => $expected) {
    $actual = $corner[$key] ?? null;
    $match = ($actual === $expected) ? '✓' : '✗';
    echo "  {$match} corner.{$key}: expected '{$expected}', got '" . ($actual ?? 'NULL') . "'\n";
}

echo "\n";
echo "If values don't match, check:\n";
echo "1. ThemeEditorPanel.tsx - is buttonRadius2 being saved correctly?\n";
echo "2. api/themes.php - is shape_tokens being passed correctly?\n";
echo "3. Theme.php - is shape_tokens being saved to database?\n";
echo "4. ThemeCSSGenerator.php - is corner being read correctly?\n";

