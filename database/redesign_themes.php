<?php
/**
 * Theme Redesign Script
 * Deletes all existing themes and creates 12 new themes (8 gradient, 4 solid)
 * incorporating all new features (page background, widget styles, spatial effects)
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Theme.php';

echo "Theme Redesign Script\n";
echo "=====================\n\n";

try {
    $pdo = getDB();
    
    // Delete all existing themes (except user themes - they have user_id set)
    echo "1. Deleting system themes...\n";
    $stmt = $pdo->prepare("DELETE FROM themes WHERE user_id IS NULL");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    echo "   ✓ Deleted {$deletedCount} system themes\n\n";
    
    // Define 12 new themes
    $themes = [
        // 8 Gradient Themes
        [
            'name' => 'Ocean Breeze',
            'is_gradient' => true,
            'colors' => ['primary' => '#1e3a5f', 'secondary' => '#ffffff', 'accent' => '#4facfe'],
            'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'widget_background' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'widget_border_color' => '#667eea',
            'page_primary_font' => 'Poppins',
            'page_secondary_font' => 'Open Sans',
            'widget_primary_font' => 'Poppins',
            'widget_secondary_font' => 'Open Sans',
            'spatial_effect' => 'glass',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#4facfe',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Sunset Glow',
            'is_gradient' => true,
            'colors' => ['primary' => '#2d1b3d', 'secondary' => '#ffffff', 'accent' => '#ff6b6b'],
            'page_background' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'widget_background' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
            'widget_border_color' => '#ff6b6b',
            'page_primary_font' => 'Montserrat',
            'page_secondary_font' => 'Lato',
            'widget_primary_font' => 'Montserrat',
            'widget_secondary_font' => 'Lato',
            'spatial_effect' => 'floating',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'spacing' => 'spacious',
                'shape' => 'round'
            ]
        ],
        [
            'name' => 'Forest Canopy',
            'is_gradient' => true,
            'colors' => ['primary' => '#1a472a', 'secondary' => '#ffffff', 'accent' => '#2d8659'],
            'page_background' => 'linear-gradient(135deg, #134e5e 0%, #71b280 100%)',
            'widget_background' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'widget_border_color' => '#2d8659',
            'page_primary_font' => 'Raleway',
            'page_secondary_font' => 'Source Sans Pro',
            'widget_primary_font' => 'Raleway',
            'widget_secondary_font' => 'Source Sans Pro',
            'spatial_effect' => 'depth',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#2d8659',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Midnight Sky',
            'is_gradient' => true,
            'colors' => ['primary' => '#1a1a2e', 'secondary' => '#ffffff', 'accent' => '#6c5ce7'],
            'page_background' => 'linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%)',
            'widget_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'widget_border_color' => '#6c5ce7',
            'page_primary_font' => 'Roboto',
            'page_secondary_font' => 'Roboto',
            'widget_primary_font' => 'Roboto',
            'widget_secondary_font' => 'Roboto',
            'spatial_effect' => 'none',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#6c5ce7',
                'spacing' => 'tight',
                'shape' => 'square'
            ]
        ],
        [
            'name' => 'Coral Reef',
            'is_gradient' => true,
            'colors' => ['primary' => '#8b2635', 'secondary' => '#ffffff', 'accent' => '#ff7f50'],
            'page_background' => 'linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%)',
            'widget_background' => 'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)',
            'widget_border_color' => '#ff7f50',
            'page_primary_font' => 'Nunito',
            'page_secondary_font' => 'Merriweather',
            'widget_primary_font' => 'Nunito',
            'widget_secondary_font' => 'Merriweather',
            'spatial_effect' => 'glass',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Purple Dream',
            'is_gradient' => true,
            'colors' => ['primary' => '#4b0082', 'secondary' => '#ffffff', 'accent' => '#9370db'],
            'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'widget_background' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'widget_border_color' => '#9370db',
            'page_primary_font' => 'Playfair Display',
            'page_secondary_font' => 'PT Sans',
            'widget_primary_font' => 'Playfair Display',
            'widget_secondary_font' => 'PT Sans',
            'spatial_effect' => 'floating',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#9370db',
                'spacing' => 'spacious',
                'shape' => 'round'
            ]
        ],
        [
            'name' => 'Arctic Frost',
            'is_gradient' => true,
            'colors' => ['primary' => '#1e3c72', 'secondary' => '#ffffff', 'accent' => '#2a5298'],
            'page_background' => 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)',
            'widget_background' => 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)',
            'widget_border_color' => '#2a5298',
            'page_primary_font' => 'Oswald',
            'page_secondary_font' => 'Lora',
            'widget_primary_font' => 'Oswald',
            'widget_secondary_font' => 'Lora',
            'spatial_effect' => 'depth',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Golden Hour',
            'is_gradient' => true,
            'colors' => ['primary' => '#8b6914', 'secondary' => '#ffffff', 'accent' => '#ffa500'],
            'page_background' => 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
            'widget_background' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
            'widget_border_color' => '#ffa500',
            'page_primary_font' => 'Crimson Text',
            'page_secondary_font' => 'Crimson Text',
            'widget_primary_font' => 'Crimson Text',
            'widget_secondary_font' => 'Crimson Text',
            'spatial_effect' => 'none',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#ffa500',
                'spacing' => 'tight',
                'shape' => 'square'
            ]
        ],
        // 4 Solid Themes
        [
            'name' => 'Classic Minimal',
            'is_gradient' => false,
            'colors' => ['primary' => '#000000', 'secondary' => '#ffffff', 'accent' => '#0066ff'],
            'page_background' => '#ffffff',
            'widget_background' => '#f8f9fa',
            'widget_border_color' => '#000000',
            'page_primary_font' => 'Inter',
            'page_secondary_font' => 'Inter',
            'widget_primary_font' => 'Inter',
            'widget_secondary_font' => 'Inter',
            'spatial_effect' => 'none',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Dark Mode',
            'is_gradient' => false,
            'colors' => ['primary' => '#ffffff', 'secondary' => '#1a1a1a', 'accent' => '#00d4ff'],
            'page_background' => '#1a1a1a',
            'widget_background' => '#2d2d2d',
            'widget_border_color' => '#00d4ff',
            'page_primary_font' => 'Roboto',
            'page_secondary_font' => 'Roboto',
            'widget_primary_font' => 'Roboto',
            'widget_secondary_font' => 'Roboto',
            'spatial_effect' => 'depth',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#00d4ff',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ]
        ],
        [
            'name' => 'Pastel Dreams',
            'is_gradient' => false,
            'colors' => ['primary' => '#4a5568', 'secondary' => '#f7fafc', 'accent' => '#ed64a6'],
            'page_background' => '#f7fafc',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#ed64a6',
            'page_primary_font' => 'Comfortaa',
            'page_secondary_font' => 'Quicksand',
            'widget_primary_font' => 'Comfortaa',
            'widget_secondary_font' => 'Quicksand',
            'spatial_effect' => 'glass',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'spacing' => 'spacious',
                'shape' => 'round'
            ]
        ],
        [
            'name' => 'Bold Contrast',
            'is_gradient' => false,
            'colors' => ['primary' => '#ffffff', 'secondary' => '#000000', 'accent' => '#ff00ff'],
            'page_background' => '#000000',
            'widget_background' => '#1a1a1a',
            'widget_border_color' => '#ff00ff',
            'page_primary_font' => 'Bebas Neue',
            'page_secondary_font' => 'Roboto Condensed',
            'widget_primary_font' => 'Bebas Neue',
            'widget_secondary_font' => 'Roboto Condensed',
            'spatial_effect' => 'floating',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#ff00ff',
                'spacing' => 'tight',
                'shape' => 'square'
            ]
        ]
    ];
    
    echo "2. Creating 12 new themes...\n";
    $themeClass = new Theme();
    
    foreach ($themes as $index => $themeData) {
        $themeName = $themeData['name'];
        $colors = $themeData['colors'];
        $fonts = [
            'heading' => $themeData['page_primary_font'],
            'body' => $themeData['page_secondary_font']
        ];
        
        $themeConfig = [
            'colors' => $colors,
            'fonts' => $fonts,
            'page_primary_font' => $themeData['page_primary_font'],
            'page_secondary_font' => $themeData['page_secondary_font'],
            'widget_primary_font' => $themeData['widget_primary_font'],
            'widget_secondary_font' => $themeData['widget_secondary_font'],
            'page_background' => $themeData['page_background'],
            'widget_background' => $themeData['widget_background'],
            'widget_border_color' => $themeData['widget_border_color'],
            'widget_styles' => $themeData['widget_styles'],
            'spatial_effect' => $themeData['spatial_effect']
        ];
        
        $result = $themeClass->createTheme(null, $themeName, $themeConfig);
        
        if ($result) {
            echo "   ✓ Created theme: {$themeName}\n";
        } else {
            echo "   ✗ Failed to create theme: {$themeName}\n";
        }
    }
    
    echo "\n3. Verification...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM themes WHERE user_id IS NULL");
    $count = $stmt->fetch()['count'];
    echo "   ✓ Total system themes: {$count}\n";
    
    echo "\n✅ Theme redesign complete!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

