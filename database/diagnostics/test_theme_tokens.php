<?php
/**
 * Theme Token Save/Load Smoke Test
 * 
 * Tests the complete theme token save/load cycle to verify:
 * 1. All tokens are saved correctly
 * 2. All tokens are loaded correctly
 * 3. Token structure matches expected format
 * 4. CSS generation works with all tokens
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/ThemeCSSGenerator.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../includes/helpers.php';

echo "Theme Token Save/Load Smoke Test\n";
echo "=================================\n\n";

$testUserId = 1; // Adjust as needed
$testThemeName = 'Token Test Theme ' . date('Y-m-d H:i:s');

try {
    $themeClass = new Theme();
    
    // Clean up any old test themes first
    echo "0. Cleaning up old test themes...\n";
    $oldThemes = fetchAll("SELECT id FROM themes WHERE user_id = ? AND name LIKE 'Token Test Theme%'", [$testUserId]);
    foreach ($oldThemes as $oldTheme) {
        $themeClass->deleteUserTheme($oldTheme['id'], $testUserId);
    }
    if (count($oldThemes) > 0) {
        echo "   ✅ Cleaned up " . count($oldThemes) . " old test theme(s)\n\n";
    } else {
        echo "   ✅ No old test themes to clean up\n\n";
    }
    
    // Test data with all token types
    $testThemeData = [
        'name' => $testThemeName,
        'colors' => [
            'primary' => '#111827',
            'secondary' => '#f5f7fa',
            'accent' => '#2563eb'
        ],
        'fonts' => [
            'heading' => 'Inter',
            'body' => 'Inter'
        ],
        'color_tokens' => [
            'background' => [
                'base' => '#ffffff',
                'surface' => '#f5f7fa',
                'surface_raised' => '#f9fafb',
                'overlay' => 'rgba(15, 23, 42, 0.6)'
            ],
            'text' => [
                'primary' => '#111827',
                'secondary' => '#4b5563',
                'inverse' => '#ffffff'
            ],
            'accent' => [
                'primary' => '#2563eb',
                'secondary' => '#3b82f6',
                'muted' => '#e0edff'
            ],
            'border' => [
                'default' => '#d1d5db',
                'focus' => '#2563eb'
            ],
            'state' => [
                'success' => '#12b76a',
                'warning' => '#f59e0b',
                'danger' => '#ef4444'
            ],
            'text_state' => [
                'success' => '#0f5132',
                'warning' => '#7c2d12',
                'danger' => '#7f1d1d'
            ],
            'shadow' => [
                'ambient' => 'rgba(15, 23, 42, 0.12)',
                'focus' => 'rgba(37, 99, 235, 0.35)'
            ],
            'gradient' => [
                'page' => null,
                'accent' => null,
                'widget' => null,
                'podcast' => null
            ],
            'glow' => [
                'primary' => null
            ]
        ],
        'typography_tokens' => [
            'font' => [
                'heading' => 'Inter',
                'body' => 'Inter',
                'metatext' => 'Inter'
            ],
            'scale' => [
                'xl' => 2.488,
                'lg' => 1.777,
                'md' => 1.333,
                'sm' => 1.111,
                'xs' => 0.889
            ],
            'line_height' => [
                'tight' => 1.2,
                'normal' => 1.5,
                'relaxed' => 1.7
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 500,
                'bold' => 600
            ]
        ],
        'spacing_tokens' => [
            'density' => 'cozy',
            'base_scale' => [
                '2xs' => 0.25,
                'xs' => 0.5,
                'sm' => 0.75,
                'md' => 1.0,
                'lg' => 1.5,
                'xl' => 2.0,
                '2xl' => 3.0
            ],
            'density_multipliers' => [
                'compact' => [
                    '2xs' => 0.75,
                    'xs' => 0.85,
                    'sm' => 0.9,
                    'md' => 1.0,
                    'lg' => 1.0,
                    'xl' => 1.0,
                    '2xl' => 1.0
                ],
                'comfortable' => [
                    '2xs' => 1.0,
                    'xs' => 1.0,
                    'sm' => 1.1,
                    'md' => 1.25,
                    'lg' => 1.3,
                    'xl' => 1.35,
                    '2xl' => 1.4
                ]
            ],
            'modifiers' => []
        ],
        'shape_tokens' => [
            'corner' => [
                'md' => '0.75rem'
            ],
            'border_width' => [
                'hairline' => '1px'
            ],
            'shadow' => [
                'level_1' => '0 2px 6px rgba(15, 23, 42, 0.12)',
                'focus' => '0 0 0 4px rgba(37, 99, 235, 0.35)'
            ]
        ],
        'motion_tokens' => [
            'duration' => [
                'fast' => '150ms',
                'standard' => '250ms'
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
            ],
            'focus' => [
                'ring_width' => '3px',
                'ring_offset' => '2px'
            ]
        ],
        'page_background' => '#ffffff',
        'widget_background' => '#f5f7fa',
        'widget_border_color' => '#d1d5db',
        'page_primary_font' => 'Inter',
        'page_secondary_font' => 'Inter',
        'widget_primary_font' => 'Inter',
        'widget_secondary_font' => 'Inter',
        'spatial_effect' => 'none'
    ];
    
    echo "1. Creating test theme...\n";
    $createResult = $themeClass->createTheme($testUserId, $testThemeName, $testThemeData);
    
    if (!$createResult['success']) {
        die("❌ Failed to create theme: " . ($createResult['error'] ?? 'Unknown error') . "\n");
    }
    
    $themeId = $createResult['theme_id'];
    echo "   ✅ Theme created with ID: $themeId\n\n";
    
    echo "2. Loading theme from database...\n";
    $loadedTheme = $themeClass->getTheme($themeId);
    
    if (!$loadedTheme) {
        die("❌ Failed to load theme\n");
    }
    
    echo "   ✅ Theme loaded successfully\n\n";
    
    echo "3. Verifying token structure...\n";
    
    // Verify color_tokens
    $colorTokens = is_string($loadedTheme['color_tokens']) 
        ? json_decode($loadedTheme['color_tokens'], true) 
        : $loadedTheme['color_tokens'];
    
    $colorChecks = [
        'background.base' => $colorTokens['background']['base'] ?? null,
        'background.surface' => $colorTokens['background']['surface'] ?? null,
        'text.primary' => $colorTokens['text']['primary'] ?? null,
        'accent.primary' => $colorTokens['accent']['primary'] ?? null,
        'border.default' => $colorTokens['border']['default'] ?? null,
        'state.success' => $colorTokens['state']['success'] ?? null,
        'shadow.ambient' => $colorTokens['shadow']['ambient'] ?? null
    ];
    
    // gradient.page can be null, so check that it exists in the structure
    $gradientStructure = isset($colorTokens['gradient']) && is_array($colorTokens['gradient']);
    
    $colorFailures = [];
    foreach ($colorChecks as $path => $value) {
        if ($value === null) {
            $colorFailures[] = $path;
        }
    }
    
    if (empty($colorFailures) && $gradientStructure) {
        echo "   ✅ Color tokens structure correct\n";
    } else {
        if (!empty($colorFailures)) {
            echo "   ❌ Missing color tokens: " . implode(', ', $colorFailures) . "\n";
        }
        if (!$gradientStructure) {
            echo "   ❌ Missing gradient structure\n";
        }
    }
    
    // Verify typography_tokens
    $typographyTokens = is_string($loadedTheme['typography_tokens']) 
        ? json_decode($loadedTheme['typography_tokens'], true) 
        : $loadedTheme['typography_tokens'];
    
    $typographyChecks = [
        'font.heading' => $typographyTokens['font']['heading'] ?? null,
        'font.body' => $typographyTokens['font']['body'] ?? null,
        'scale.md' => $typographyTokens['scale']['md'] ?? null,
        'line_height.normal' => $typographyTokens['line_height']['normal'] ?? null,
        'weight.bold' => $typographyTokens['weight']['bold'] ?? null
    ];
    
    $typographyFailures = [];
    foreach ($typographyChecks as $path => $value) {
        if ($value === null) {
            $typographyFailures[] = $path;
        }
    }
    
    if (empty($typographyFailures)) {
        echo "   ✅ Typography tokens structure correct\n";
    } else {
        echo "   ❌ Missing typography tokens: " . implode(', ', $typographyFailures) . "\n";
    }
    
    // Verify spacing_tokens
    $spacingTokens = is_string($loadedTheme['spacing_tokens']) 
        ? json_decode($loadedTheme['spacing_tokens'], true) 
        : $loadedTheme['spacing_tokens'];
    
    $spacingChecks = [
        'density' => $spacingTokens['density'] ?? null,
        'base_scale.md' => $spacingTokens['base_scale']['md'] ?? null,
        'density_multipliers.compact.2xs' => $spacingTokens['density_multipliers']['compact']['2xs'] ?? null
    ];
    
    $spacingFailures = [];
    foreach ($spacingChecks as $path => $value) {
        if ($value === null) {
            $spacingFailures[] = $path;
        }
    }
    
    if (empty($spacingFailures)) {
        echo "   ✅ Spacing tokens structure correct\n";
    } else {
        echo "   ❌ Missing spacing tokens: " . implode(', ', $spacingFailures) . "\n";
    }
    
    // Verify shape_tokens
    $shapeTokens = is_string($loadedTheme['shape_tokens']) 
        ? json_decode($loadedTheme['shape_tokens'], true) 
        : $loadedTheme['shape_tokens'];
    
    $shapeChecks = [
        'corner.md' => $shapeTokens['corner']['md'] ?? null,
        'border_width.hairline' => $shapeTokens['border_width']['hairline'] ?? null,
        'shadow.level_1' => $shapeTokens['shadow']['level_1'] ?? null,
        'shadow.focus' => $shapeTokens['shadow']['focus'] ?? null
    ];
    
    $shapeFailures = [];
    foreach ($shapeChecks as $path => $value) {
        if ($value === null) {
            $shapeFailures[] = $path;
        }
    }
    
    if (empty($shapeFailures)) {
        echo "   ✅ Shape tokens structure correct\n";
    } else {
        echo "   ❌ Missing shape tokens: " . implode(', ', $shapeFailures) . "\n";
    }
    
    // Verify motion_tokens
    $motionTokens = is_string($loadedTheme['motion_tokens']) 
        ? json_decode($loadedTheme['motion_tokens'], true) 
        : $loadedTheme['motion_tokens'];
    
    $motionChecks = [
        'duration.standard' => $motionTokens['duration']['standard'] ?? null,
        'easing.standard' => $motionTokens['easing']['standard'] ?? null,
        'focus.ring_width' => $motionTokens['focus']['ring_width'] ?? null
    ];
    
    $motionFailures = [];
    foreach ($motionChecks as $path => $value) {
        if ($value === null) {
            $motionFailures[] = $path;
        }
    }
    
    if (empty($motionFailures)) {
        echo "   ✅ Motion tokens structure correct\n";
    } else {
        echo "   ❌ Missing motion tokens: " . implode(', ', $motionFailures) . "\n";
    }
    
    echo "\n";
    
    echo "4. Verifying database columns...\n";
    $columnChecks = [
        'page_background' => $loadedTheme['page_background'] ?? null,
        'widget_background' => $loadedTheme['widget_background'] ?? null,
        'widget_border_color' => $loadedTheme['widget_border_color'] ?? null,
        'page_primary_font' => $loadedTheme['page_primary_font'] ?? null,
        'page_secondary_font' => $loadedTheme['page_secondary_font'] ?? null
    ];
    
    $columnFailures = [];
    foreach ($columnChecks as $column => $value) {
        if ($value === null) {
            $columnFailures[] = $column;
        }
    }
    
    if (empty($columnFailures)) {
        echo "   ✅ Database columns populated\n";
    } else {
        echo "   ❌ Missing database columns: " . implode(', ', $columnFailures) . "\n";
    }
    
    echo "\n";
    
    echo "5. Testing CSS generation...\n";
    $pageClass = new Page();
    $testPage = $pageClass->getByUserId($testUserId);
    
    if ($testPage) {
        // Temporarily set theme on page
        $originalThemeId = $testPage['theme_id'] ?? null;
        
        try {
            $cssGenerator = new ThemeCSSGenerator($testPage, $loadedTheme);
            $css = $cssGenerator->generateCompleteStyleBlock();
            $cssVariables = $cssGenerator->generateCSSVariables();
            
            if (!empty($css) && !empty($cssVariables)) {
                echo "   ✅ CSS generation successful\n";
                echo "   CSS Variables length: " . strlen($cssVariables) . " bytes\n";
                echo "   CSS Block length: " . strlen($css) . " bytes\n";
            } else {
                echo "   ❌ CSS generation failed (empty output)\n";
            }
        } catch (Exception $e) {
            echo "   ❌ CSS generation error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  No test page found, skipping CSS generation test\n";
    }
    
    echo "\n";
    
    echo "6. Testing token update...\n";
    // Update theme - updateUserTheme replaces entire token fields
    // Token preservation is handled at the frontend level (ThemeEditorPanel loads existing tokens first)
    $updatedColorTokens = $colorTokens;
    $updatedColorTokens['background']['base'] = '#000000';
    
    $updateData = [
        'color_tokens' => $updatedColorTokens
    ];
    
    $updateResult = $themeClass->updateUserTheme($themeId, $testUserId, null, $updateData);
    
    if ($updateResult) {
        $reloadedTheme = $themeClass->getTheme($themeId);
        $reloadedColorTokens = is_string($reloadedTheme['color_tokens']) 
            ? json_decode($reloadedTheme['color_tokens'], true) 
            : $reloadedTheme['color_tokens'];
        
        // Check that the update worked
        if (($reloadedColorTokens['background']['base'] ?? null) === '#000000') {
            echo "   ✅ Token update works correctly\n";
            echo "      Note: updateUserTheme replaces entire fields (preservation handled in frontend)\n";
        } else {
            echo "   ❌ Token update failed\n";
            echo "      Expected background.base to be #000000, got: " . ($reloadedColorTokens['background']['base'] ?? 'null') . "\n";
        }
    } else {
        echo "   ❌ Theme update failed\n";
    }
    
    echo "\n";
    
    echo "7. Cleaning up test theme...\n";
    // Delete test theme
    $deleteResult = $themeClass->deleteUserTheme($themeId, $testUserId);
    
    if ($deleteResult) {
        echo "   ✅ Test theme deleted\n";
    } else {
        echo "   ⚠️  Failed to delete test theme (ID: $themeId)\n";
    }
    
    echo "\n";
    echo "=================================\n";
    echo "✅ Smoke test complete!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>

