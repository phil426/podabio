<?php
/**
 * Check Theme Data Structure
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

// Get a few sample themes
$themes = fetchAll("SELECT id, name, iconography_tokens, page_background FROM themes WHERE is_active = 1 LIMIT 5");

foreach ($themes as $theme) {
    echo "Theme: {$theme['name']} (ID: {$theme['id']})\n";
    echo "  iconography_tokens: " . ($theme['iconography_tokens'] ?? 'NULL') . "\n";
    echo "  page_background: " . ($theme['page_background'] ?? 'NULL') . "\n";
    
    if (!empty($theme['iconography_tokens'])) {
        $parsed = json_decode($theme['iconography_tokens'], true);
        echo "  Parsed iconography_tokens: " . json_encode($parsed, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

// Check for themes with #2563EB
$allThemes = fetchAll("SELECT id, name, iconography_tokens FROM themes WHERE is_active = 1");
$found = 0;
foreach ($allThemes as $theme) {
    if (!empty($theme['iconography_tokens'])) {
        $parsed = json_decode($theme['iconography_tokens'], true);
        if (isset($parsed['color']) && ($parsed['color'] === '#2563EB' || $parsed['color'] === '#2563eb')) {
            echo "Found #2563EB in: {$theme['name']} (ID: {$theme['id']})\n";
            $found++;
        }
    }
}
echo "\nTotal themes with #2563EB: {$found}\n";

