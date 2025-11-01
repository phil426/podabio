<?php
/**
 * Check specific page and theme data
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "Page and Theme Check\n";
echo "====================\n\n";

$username = $argv[1] ?? 'phil624';

$page = $pdo->prepare("SELECT * FROM pages WHERE username = ?");
$page->execute([$username]);
$pageData = $page->fetch(PDO::FETCH_ASSOC);

if (!$pageData) {
    echo "Page not found: {$username}\n";
    exit(1);
}

echo "Page Data:\n";
echo "  Username: {$pageData['username']}\n";
echo "  Theme ID: " . ($pageData['theme_id'] ?: 'NULL') . "\n";
echo "  Page Background: " . ($pageData['page_background'] ?: 'NULL') . "\n";
echo "  Widget Background: " . ($pageData['widget_background'] ?: 'NULL') . "\n";
echo "  Widget Border Color: " . ($pageData['widget_border_color'] ?: 'NULL') . "\n";
echo "\n";

if ($pageData['theme_id']) {
    $theme = $pdo->prepare("SELECT * FROM themes WHERE id = ?");
    $theme->execute([$pageData['theme_id']]);
    $themeData = $theme->fetch(PDO::FETCH_ASSOC);
    
    if ($themeData) {
        echo "Theme Data:\n";
        echo "  Name: {$themeData['name']}\n";
        echo "  Page Background: " . ($themeData['page_background'] ?: 'NULL') . "\n";
        echo "  Widget Background: " . ($themeData['widget_background'] ?: 'NULL') . "\n";
        echo "  Widget Border Color: " . ($themeData['widget_border_color'] ?: 'NULL') . "\n";
        echo "  Page Primary Font: " . ($themeData['page_primary_font'] ?: 'NULL') . "\n";
        echo "  Page Secondary Font: " . ($themeData['page_secondary_font'] ?: 'NULL') . "\n";
        echo "  Widget Primary Font: " . ($themeData['widget_primary_font'] ?: 'NULL') . "\n";
        echo "  Widget Secondary Font: " . ($themeData['widget_secondary_font'] ?: 'NULL') . "\n";
    } else {
        echo "Theme not found!\n";
    }
}

