<?php
/**
 * Check Recent Theme Update Logs
 * This script checks the database for recent theme updates and shows what was saved
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Theme.php';

$pdo = getDB();
$themeClass = new Theme();

echo "========================================\n";
echo "Recent Theme Updates Check\n";
echo "========================================\n\n";

// Get the most recently updated theme
$theme = fetchOne(
    "SELECT id, name, typography_tokens, updated_at 
     FROM themes 
     WHERE user_id IS NOT NULL 
     ORDER BY updated_at DESC 
     LIMIT 1",
    []
);

if (!$theme) {
    echo "No user themes found.\n";
    exit(0);
}

echo "Most Recently Updated Theme:\n";
echo "ID: {$theme['id']}\n";
echo "Name: {$theme['name']}\n";
echo "Last Updated: {$theme['updated_at']}\n\n";

// Parse typography_tokens
$typographyTokens = null;
if (!empty($theme['typography_tokens'])) {
    if (is_string($theme['typography_tokens'])) {
        $typographyTokens = json_decode($theme['typography_tokens'], true);
    } else {
        $typographyTokens = $theme['typography_tokens'];
    }
}

echo "Typography Tokens:\n";
if ($typographyTokens) {
    echo json_encode($typographyTokens, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($typographyTokens['color'])) {
        echo "✅ COLOR FOUND:\n";
        echo "  heading: " . ($typographyTokens['color']['heading'] ?? 'NOT SET') . "\n";
        echo "  body: " . ($typographyTokens['color']['body'] ?? 'NOT SET') . "\n";
    } else {
        echo "❌ COLOR NOT FOUND in typography_tokens\n";
    }
} else {
    echo "❌ typography_tokens is NULL or empty\n";
}

echo "\n========================================\n";
echo "Check complete!\n";
echo "========================================\n";

