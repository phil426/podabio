<?php
/**
 * Rebuild All 12 Themes with Proper Background Implementation
 * Ensures backgrounds are always applied and visible
 * 8 Gradient-oriented themes + 4 Solid color themes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "🎨 Rebuilding All Themes with Proper Backgrounds\n\n";
    
    // Step 1: Delete all existing system themes
    echo "Step 1: Deleting existing system themes...\n";
    $deleted = $pdo->exec("DELETE FROM themes WHERE user_id IS NULL");
    echo "   ✅ Deleted $deleted existing themes\n\n";
    
    // Step 2: Insert 12 new themes with guaranteed backgrounds
    echo "Step 2: Creating 12 new themes with proper backgrounds...\n\n";
    
    // 8 GRADIENT-ORIENTED THEMES (with full coverage gradients)
    $gradientThemes = [
        [
            'name' => 'Sunset Gradient',
            'colors' => [
                'primary' => '#1a1a2e',
                'secondary' => '#ffffff',
                'accent' => '#ff6b6b'
            ],
            'fonts' => [
                'heading' => 'Playfair Display',
                'body' => 'Lato'
            ],
            'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#ff6b6b',
                'border_color' => 'var(--primary-color)',
                'background_color' => 'rgba(255, 255, 255, 0.95)',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'glass'
        ],
        [
            'name' => 'Ocean Depths',
            'colors' => [
                'primary' => '#0a2540',
                'secondary' => '#f0f7ff',
                'accent' => '#00d4ff'
            ],
            'fonts' => [
                'heading' => 'Montserrat',
                'body' => 'Open Sans'
            ],
            'page_background' => 'linear-gradient(135deg, #0f3460 0%, #16213e 50%, #00d4ff 100%)',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'border_color' => 'var(--primary-color)',
                'background_color' => 'rgba(240, 247, 255, 0.95)',
                'spacing' => 'spacious',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'depth'
        ],
        [
            'name' => 'Forest Mist',
            'colors' => [
                'primary' => '#1a3a2e',
                'secondary' => '#f5faf7',
                'accent' => '#4ade80'
            ],
            'fonts' => [
                'heading' => 'Merriweather',
                'body' => 'Source Sans Pro'
            ],
            'page_background' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#4ade80',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(245, 250, 247, 0.95)',
                'spacing' => 'comfortable',
                'shape' => 'round'
            ],
            'spatial_effect' => 'floating'
        ],
        [
            'name' => 'Cosmic Purple',
            'colors' => [
                'primary' => '#2d1b3d',
                'secondary' => '#faf5ff',
                'accent' => '#a855f7'
            ],
            'fonts' => [
                'heading' => 'Poppins',
                'body' => 'Nunito'
            ],
            'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#a855f7',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(250, 245, 255, 0.95)',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'glass'
        ],
        [
            'name' => 'Golden Hour',
            'colors' => [
                'primary' => '#3d2c1b',
                'secondary' => '#fffbf0',
                'accent' => '#fbbf24'
            ],
            'fonts' => [
                'heading' => 'Crimson Text',
                'body' => 'Lora'
            ],
            'page_background' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #ffd700 100%)',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(255, 251, 240, 0.95)',
                'spacing' => 'spacious',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'floating'
        ],
        [
            'name' => 'Neon Dreams',
            'colors' => [
                'primary' => '#1a0033',
                'secondary' => '#ffffff',
                'accent' => '#00ff88'
            ],
            'fonts' => [
                'heading' => 'Orbitron',
                'body' => 'Roboto'
            ],
            'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #00ff88 100%)',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#00ff88',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(26, 0, 51, 0.9)',
                'spacing' => 'comfortable',
                'shape' => 'round'
            ],
            'spatial_effect' => 'glass'
        ],
        [
            'name' => 'Coral Reef',
            'colors' => [
                'primary' => '#2d1b1b',
                'secondary' => '#fff5f5',
                'accent' => '#ff6b9d'
            ],
            'fonts' => [
                'heading' => 'Crimson Text',
                'body' => 'Inter'
            ],
            'page_background' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%)',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#ff6b9d',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(255, 245, 245, 0.95)',
                'spacing' => 'tight',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        [
            'name' => 'Aurora Borealis',
            'colors' => [
                'primary' => '#0a0e27',
                'secondary' => '#f0f9ff',
                'accent' => '#22d3ee'
            ],
            'fonts' => [
                'heading' => 'Space Grotesk',
                'body' => 'Inter'
            ],
            'page_background' => 'linear-gradient(135deg, #0f3460 0%, #16213e 50%, #22d3ee 100%)',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#22d3ee',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(240, 249, 255, 0.95)',
                'spacing' => 'comfortable',
                'shape' => 'round'
            ],
            'spatial_effect' => 'depth'
        ]
    ];
    
    // 4 SOLID COLOR-ORIENTED THEMES (with full coverage solid backgrounds)
    $solidThemes = [
        [
            'name' => 'Midnight Blue',
            'colors' => [
                'primary' => '#0f172a',
                'secondary' => '#f8fafc',
                'accent' => '#3b82f6'
            ],
            'fonts' => [
                'heading' => 'Inter',
                'body' => 'Inter'
            ],
            'page_background' => '#0f172a',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_color' => 'var(--primary-color)',
                'background_color' => 'rgba(248, 250, 252, 0.95)',
                'spacing' => 'comfortable',
                'shape' => 'square'
            ],
            'spatial_effect' => 'none'
        ],
        [
            'name' => 'Ivory Elegance',
            'colors' => [
                'primary' => '#1f2937',
                'secondary' => '#ffffff',
                'accent' => '#8b5cf6'
            ],
            'fonts' => [
                'heading' => 'Cormorant Garamond',
                'body' => 'Lato'
            ],
            'page_background' => '#fefefe',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'border_color' => 'var(--primary-color)',
                'background_color' => '#ffffff',
                'spacing' => 'spacious',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'floating'
        ],
        [
            'name' => 'Emerald Classic',
            'colors' => [
                'primary' => '#064e3b',
                'secondary' => '#ecfdf5',
                'accent' => '#10b981'
            ],
            'fonts' => [
                'heading' => 'Roboto Slab',
                'body' => 'Roboto'
            ],
            'page_background' => '#ecfdf5',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#10b981',
                'border_color' => 'var(--accent-color)',
                'background_color' => '#ffffff',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'glass'
        ],
        [
            'name' => 'Charcoal Modern',
            'colors' => [
                'primary' => '#111827',
                'secondary' => '#f9fafb',
                'accent' => '#ef4444'
            ],
            'fonts' => [
                'heading' => 'Montserrat',
                'body' => 'Montserrat'
            ],
            'page_background' => '#111827',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'glow',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#ef4444',
                'border_color' => 'var(--accent-color)',
                'background_color' => 'rgba(249, 250, 251, 0.95)',
                'spacing' => 'tight',
                'shape' => 'square'
            ],
            'spatial_effect' => 'depth'
        ]
    ];
    
    // Combine all themes
    $allThemes = array_merge($gradientThemes, $solidThemes);
    
    $inserted = 0;
    
    foreach ($allThemes as $theme) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO themes (
                    user_id, name, colors, fonts, page_background, 
                    widget_styles, spatial_effect, is_active
                )
                VALUES (NULL, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $theme['name'],
                json_encode($theme['colors']),
                json_encode($theme['fonts']),
                $theme['page_background'],
                json_encode($theme['widget_styles']),
                $theme['spatial_effect']
            ]);
            
            $themeType = in_array($theme, $gradientThemes) ? 'Gradient' : 'Solid';
            $bgPreview = strlen($theme['page_background']) > 20 ? substr($theme['page_background'], 0, 30) . '...' : $theme['page_background'];
            echo "✅ {$themeType}: {$theme['name']}\n";
            echo "   Background: {$bgPreview}\n";
            echo "   Colors: {$theme['colors']['primary']} / {$theme['colors']['secondary']} / {$theme['colors']['accent']}\n";
            echo "   Widget Effect: {$theme['widget_styles']['border_effect']} ({$theme['widget_styles']['border_width']}, {$theme['widget_styles']['shape']})\n";
            echo "   Spatial: {$theme['spatial_effect']}\n\n";
            
            $inserted++;
            
        } catch (PDOException $e) {
            echo "❌ Error adding theme '{$theme['name']}': " . $e->getMessage() . "\n\n";
        }
    }
    
    $pdo->commit();
    
    echo "==========================================\n";
    echo "✅ Complete!\n";
    echo "   Deleted: $deleted old themes\n";
    echo "   Created: $inserted new themes\n";
    echo "   - 8 Gradient-oriented themes (full gradient backgrounds)\n";
    echo "   - 4 Solid color themes (full solid backgrounds)\n";
    echo "\n";
    echo "📋 Background Implementation:\n";
    echo "   - All themes have page_background set\n";
    echo "   - CSS uses background-attachment: fixed for full coverage\n";
    echo "   - Spatial effects now preserve background colors\n";
    echo "==========================================\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

