<?php
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/../config/database.php';

// Mock context if needed, or just fetch directly
$pdo = getDB();

// Get the specific page
$pageId = 7; // From previous check
$page = fetchOne("SELECT * FROM pages WHERE id = ?", [$pageId]);

echo "Page Settings:\n";
echo "Image URL: " . ($page['page_background_image_url'] ?? 'NULL') . "\n";
echo "Page BG Color (DB): " . ($page['page_background'] ?? 'NULL') . "\n";

// Get theme
$theme = fetchOne("SELECT * FROM themes WHERE id = ?", [$page['theme_id']]);

// Initialize generator
$cssGen = new ThemeCSSGenerator($page, $theme);
$css = $cssGen->generateCompleteStyleBlock();

// Check for relevant parts
echo "\n--- Generated CSS Checks ---\n";
if (strpos($css, '--page-background-image-url:') !== false) {
    echo "✅ Variable --page-background-image-url found\n";
    preg_match('/--page-background-image-url: (.*?);/', $css, $matches);
    echo "   Value: " . ($matches[1] ?? 'ERROR') . "\n";
} else {
    echo "❌ Variable --page-background-image-url MISSING\n";
}

if (strpos($css, 'background: transparent !important') !== false) {
    echo "✅ 'background: transparent !important' found\n";
} else {
    echo "❌ 'background: transparent !important' MISSING\n";
}

// Extract body block
preg_match('/body \{([^}]+)\}/s', $css, $bodyMatches);
echo "\n--- Body Block ---\n";
echo $bodyMatches[0] ?? "Body block not found";
echo "\n";

// Extract html block
preg_match('/html \{([^}]+)\}/s', $css, $htmlMatches);
echo "\n--- HTML Block ---\n";
echo $htmlMatches[0] ?? "HTML block not found";
echo "\n";
