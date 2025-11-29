<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../includes/helpers.php';

echo "Testing Theme CSS Generation\n";
echo "============================\n\n";

$username = 'phil624';

try {
    $pageClass = new Page();
    $themeClass = new Theme();
    
    $page = $pageClass->getByUsername($username);
    
    if (!$page) {
        die("Page not found\n");
    }
    
    echo "Page: {$page['username']}\n";
    echo "Theme ID: " . ($page['theme_id'] ?? 'NULL') . "\n";
    echo "Page spatial_effect field: " . ($page['spatial_effect'] ?? 'NOT SET') . "\n\n";
    
    $theme = null;
    if ($page['theme_id']) {
        $theme = $themeClass->getTheme($page['theme_id']);
        echo "Theme: {$theme['name']}\n";
        echo "Theme spatial_effect field: " . ($theme['spatial_effect'] ?? 'NOT SET') . "\n\n";
    }
    
    echo "--- Theme Data Retrieval ---\n";
    $pageBg = $themeClass->getPageBackground($page, $theme);
    echo "Page Background: " . ($pageBg ?: 'NULL') . "\n";
    
    $widgetBg = $themeClass->getWidgetBackground($page, $theme);
    echo "Widget Background: " . ($widgetBg ?: 'NULL') . "\n";
    
    $widgetBorder = $themeClass->getWidgetBorderColor($page, $theme);
    echo "Widget Border: " . ($widgetBorder ?: 'NULL') . "\n";
    
    $spatialEffect = $themeClass->getSpatialEffect($page, $theme);
    echo "Spatial Effect: " . ($spatialEffect ?: 'NULL') . "\n\n";
    
    echo "--- CSS Generation ---\n";
    $cssGenerator = new ThemeCSSGenerator($page, $theme);
    $css = $cssGenerator->generateCompleteStyleBlock();
    
    echo "Generated CSS:\n";
    echo "==============\n";
    echo $css;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>

