<?php
/**
 * Quick Database Check Script
 * Checks if typography_tokens.color.heading and color.body are being saved
 * 
 * Usage: php check_theme_colors.php [user_id]
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Get user ID from command line or use current user
$userId = isset($argv[1]) ? (int)$argv[1] : null;

if (!$userId) {
    // Try to get from session
    require_once __DIR__ . '/includes/session.php';
    require_once __DIR__ . '/includes/auth.php';
    
    if (isLoggedIn()) {
        $user = getCurrentUser();
        $userId = $user['id'];
    } else {
        echo "Error: No user ID provided and not logged in.\n";
        echo "Usage: php check_theme_colors.php [user_id]\n";
        exit(1);
    }
}

$pdo = getDB();

echo "========================================\n";
echo "Theme Colors Database Check\n";
echo "========================================\n";
echo "User ID: {$userId}\n\n";

// Get all themes for this user
$themes = fetchAll(
    "SELECT id, name, typography_tokens, color_tokens, created_at, updated_at 
     FROM themes 
     WHERE user_id = ? 
     ORDER BY updated_at DESC 
     LIMIT 10",
    [$userId]
);

if (empty($themes)) {
    echo "No themes found for user {$userId}.\n";
    exit(0);
}

echo "Found " . count($themes) . " theme(s):\n\n";

foreach ($themes as $theme) {
    echo "----------------------------------------\n";
    echo "Theme ID: {$theme['id']}\n";
    echo "Theme Name: {$theme['name']}\n";
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
    
    // Parse color_tokens
    $colorTokens = null;
    if (!empty($theme['color_tokens'])) {
        if (is_string($theme['color_tokens'])) {
            $colorTokens = json_decode($theme['color_tokens'], true);
        } else {
            $colorTokens = $theme['color_tokens'];
        }
    }
    
    // Check typography_tokens.color
    echo "Typography Tokens:\n";
    if ($typographyTokens && isset($typographyTokens['color'])) {
        $headingColor = $typographyTokens['color']['heading'] ?? 'NOT SET';
        $bodyColor = $typographyTokens['color']['body'] ?? 'NOT SET';
        
        echo "  ✅ color.heading: " . ($headingColor !== 'NOT SET' ? $headingColor : '❌ NOT SET') . "\n";
        echo "  ✅ color.body: " . ($bodyColor !== 'NOT SET' ? $bodyColor : '❌ NOT SET') . "\n";
        
        if ($headingColor === 'NOT SET' || $bodyColor === 'NOT SET') {
            echo "  ⚠️  WARNING: Colors not set in typography_tokens!\n";
        }
    } else {
        echo "  ❌ typography_tokens.color NOT FOUND\n";
        if ($typographyTokens) {
            echo "  Available keys: " . implode(', ', array_keys($typographyTokens)) . "\n";
        } else {
            echo "  typography_tokens is NULL or empty\n";
        }
    }
    
    // Check color_tokens.text (for comparison)
    echo "\nColor Tokens (for comparison):\n";
    if ($colorTokens && isset($colorTokens['text'])) {
        $textPrimary = $colorTokens['text']['primary'] ?? 'NOT SET';
        $textSecondary = $colorTokens['text']['secondary'] ?? 'NOT SET';
        
        echo "  text.primary: " . ($textPrimary !== 'NOT SET' ? $textPrimary : 'NOT SET') . "\n";
        echo "  text.secondary: " . ($textSecondary !== 'NOT SET' ? $textSecondary : 'NOT SET') . "\n";
    } else {
        echo "  color_tokens.text NOT FOUND\n";
    }
    
    // Show full typography_tokens structure (first 500 chars)
    if ($typographyTokens) {
        $json = json_encode($typographyTokens, JSON_PRETTY_PRINT);
        echo "\nFull typography_tokens (first 500 chars):\n";
        echo substr($json, 0, 500) . (strlen($json) > 500 ? '...' : '') . "\n";
    }
    
    echo "\n";
}

// Check which pages use these themes
echo "========================================\n";
echo "Pages using these themes:\n";
echo "========================================\n";

$pages = fetchAll(
    "SELECT p.id, p.username, p.theme_id, t.name as theme_name
     FROM pages p
     LEFT JOIN themes t ON p.theme_id = t.id
     WHERE p.user_id = ?
     ORDER BY p.id DESC",
    [$userId]
);

if (empty($pages)) {
    echo "No pages found for user {$userId}.\n";
} else {
    foreach ($pages as $page) {
        echo "Page: {$page['username']} (ID: {$page['id']}) - Theme: {$page['theme_name']} (ID: {$page['theme_id']})\n";
    }
}

echo "\n========================================\n";
echo "Check complete!\n";
echo "========================================\n";

