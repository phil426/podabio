<?php
/**
 * Check Themes Database
 * Verifies that themes have background values
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "Theme Background Check\n";
echo "=====================\n\n";

$themes = $pdo->query("SELECT id, name, page_background, widget_background, widget_border_color FROM themes LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

foreach ($themes as $theme) {
    echo "Theme: {$theme['name']}\n";
    echo "  ID: {$theme['id']}\n";
    echo "  Page Background: " . ($theme['page_background'] ?: 'NULL') . "\n";
    echo "  Widget Background: " . ($theme['widget_background'] ?: 'NULL') . "\n";
    echo "  Widget Border Color: " . ($theme['widget_border_color'] ?: 'NULL') . "\n";
    echo "\n";
}

