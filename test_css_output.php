<?php
/**
 * Quick CSS Output Test
 * Shows what CSS variables are being generated for a specific page
 * 
 * Usage: php test_css_output.php [username]
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

$username = $argv[1] ?? null;

if (!$username) {
    echo "Usage: php test_css_output.php [username]\n";
    exit(1);
}

$pageClass = new Page();
$page = $pageClass->getByUsername($username);

if (!$page) {
    echo "Page not found for username: {$username}\n";
    exit(1);
}

$themeClass = new Theme();
$theme = null;
if ($page['theme_id']) {
    $theme = $themeClass->getTheme($page['theme_id']);
}

echo "========================================\n";
echo "CSS Output Test for: {$username}\n";
echo "========================================\n";
echo "Page ID: {$page['id']}\n";
echo "Theme ID: " . ($theme['id'] ?? 'null') . "\n";
echo "Theme Name: " . ($theme['name'] ?? 'null') . "\n\n";

// Get typography tokens
$typographyTokens = $themeClass->getTypographyTokens($page, $theme);
echo "Typography Tokens from Database:\n";
echo json_encode($typographyTokens, JSON_PRETTY_PRINT) . "\n\n";

// Check if colors are in typography_tokens
if (isset($typographyTokens['color'])) {
    echo "✅ typography_tokens.color found:\n";
    echo "  heading: " . ($typographyTokens['color']['heading'] ?? 'NOT SET') . "\n";
    echo "  body: " . ($typographyTokens['color']['body'] ?? 'NOT SET') . "\n\n";
} else {
    echo "❌ typography_tokens.color NOT FOUND\n\n";
}

// Generate CSS and extract just the color variables
$cssGenerator = new ThemeCSSGenerator($page, $theme);
$css = $cssGenerator->generateCSSVariables();

// Extract color variables from CSS
echo "CSS Variables Generated:\n";
if (preg_match('/--heading-font-color:\s*([^;]+);/', $css, $matches)) {
    echo "✅ --heading-font-color: " . trim($matches[1]) . "\n";
} else {
    echo "❌ --heading-font-color NOT FOUND\n";
}

if (preg_match('/--body-font-color:\s*([^;]+);/', $css, $matches)) {
    echo "✅ --body-font-color: " . trim($matches[1]) . "\n";
} else {
    echo "❌ --body-font-color NOT FOUND\n";
}

if (preg_match('/--page-title-color:\s*([^;]+);/', $css, $matches)) {
    echo "✅ --page-title-color: " . trim($matches[1]) . "\n";
} else {
    echo "❌ --page-title-color NOT FOUND\n";
}

if (preg_match('/--page-description-color:\s*([^;]+);/', $css, $matches)) {
    echo "✅ --page-description-color: " . trim($matches[1]) . "\n";
} else {
    echo "❌ --page-description-color NOT FOUND\n";
}

echo "\n========================================\n";
echo "Check complete!\n";
echo "========================================\n";

