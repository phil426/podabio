<?php
/**
 * Test Glow Flow
 * Tests the complete glow feature flow from widget_styles to CSS output
 */

require_once __DIR__ . '/classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/includes/theme-helpers.php';

echo "=== GLOW FLOW TEST ===\n\n";

// Test 1: Check convertEnumToCSS
echo "1. Testing convertEnumToCSS:\n";
echo "   subtle blur: " . convertEnumToCSS('subtle', 'glow_blur') . "\n";
echo "   pronounced blur: " . convertEnumToCSS('pronounced', 'glow_blur') . "\n";
echo "   subtle opacity: " . convertEnumToCSS('subtle', 'glow_opacity') . "\n";
echo "   pronounced opacity: " . convertEnumToCSS('pronounced', 'glow_opacity') . "\n\n";

// Test 2: Create mock page and theme with glow settings
$mockPage = ['id' => 1, 'theme_id' => 1];
$mockTheme = [
    'id' => 1,
    'widget_styles' => json_encode([
        'border_effect' => 'glow',
        'border_glow_intensity' => 'pronounced',
        'glow_color' => '#00ff00'
    ])
];

echo "2. Testing ThemeCSSGenerator with glow:\n";
try {
    $generator = new ThemeCSSGenerator($mockPage, $mockTheme);
    $css = $generator->generateCompleteStyleBlock();
    
    // Check if glow CSS is present
    if (strpos($css, 'glow-pulse') !== false) {
        echo "   ✅ glow-pulse animation found\n";
    } else {
        echo "   ❌ glow-pulse animation NOT found\n";
    }
    
    if (strpos($css, 'box-shadow') !== false && strpos($css, 'rgba') !== false) {
        echo "   ✅ glow box-shadow with rgba found\n";
    } else {
        echo "   ❌ glow box-shadow NOT found\n";
    }
    
    // Extract glow box-shadow line
    preg_match('/\.widget-item\s*\{[^}]*box-shadow:[^;]+;/s', $css, $matches);
    if (!empty($matches)) {
        echo "   ✅ Widget glow CSS: " . trim($matches[0]) . "\n";
    } else {
        echo "   ❌ Widget glow CSS NOT found\n";
    }
    
    // Check for glow debug log
    echo "\n3. Checking PHP error log for glow debug messages...\n";
    echo "   (Check your PHP error log for 'GLOW DEBUG' messages)\n";
    
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";

