<?php
/**
 * Theme Collection Redesign V2
 * Creates 12 professionally curated themes (1 Classic Minimal + 11 new themes)
 * All themes showcase full feature set with conservative but polished design
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = getDB();

try {
    $pdo->beginTransaction();
    
    echo "🎨 Theme Collection Redesign V2\n";
    echo "================================\n\n";
    
    // Step 1: Delete all existing system themes
    echo "Step 1: Deleting existing system themes...\n";
    $deleted = $pdo->exec("DELETE FROM themes WHERE user_id IS NULL");
    echo "   ✅ Deleted $deleted existing themes\n\n";
    
    // Step 2: Define 12 professional themes
    echo "Step 2: Creating 12 new professional themes...\n\n";
    
    $themes = [
        // Theme 1: Classic Minimal (Solid)
        [
            'name' => 'Classic Minimal',
            'colors' => [
                'primary' => '#111827',
                'secondary' => '#ffffff',
                'accent' => '#0066ff'
            ],
            'fonts' => [
                'heading' => 'Inter',
                'body' => 'Inter'
            ],
            'page_background' => '#ffffff',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#e5e7eb',
            'page_primary_font' => 'Inter',
            'page_secondary_font' => 'Inter',
            'widget_primary_font' => 'Inter',
            'widget_secondary_font' => 'Inter',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_glow_intensity' => 'none',
                'glow_color' => '#0066ff',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 2: Sunset Boulevard (Gradient)
        [
            'name' => 'Sunset Boulevard',
            'colors' => [
                'primary' => '#1a1a2e',
                'secondary' => '#ffffff',
                'accent' => '#ff6b6b'
            ],
            'fonts' => [
                'heading' => 'Playfair Display',
                'body' => 'Lato'
            ],
            'page_background' => 'linear-gradient(135deg, #ff6b6b 0%, #f093fb 50%, #4facfe 100%)',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#ff6b6b',
            'page_primary_font' => 'Playfair Display',
            'page_secondary_font' => 'Lato',
            'widget_primary_font' => 'Playfair Display',
            'widget_secondary_font' => 'Lato',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_glow_intensity' => 'none',
                'glow_color' => '#ff6b6b',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 3: Ocean Depths (Gradient)
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
            'widget_background' => '#f0f7ff',
            'widget_border_color' => '#00d4ff',
            'page_primary_font' => 'Montserrat',
            'page_secondary_font' => 'Open Sans',
            'widget_primary_font' => 'Montserrat',
            'widget_secondary_font' => 'Open Sans',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'glow',
                'border_shadow_intensity' => 'none',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#00d4ff',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'glass'
        ],
        
        // Theme 4: Forest Canopy (Gradient)
        [
            'name' => 'Forest Canopy',
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
            'widget_background' => '#ffffff',
            'widget_border_color' => '#4ade80',
            'page_primary_font' => 'Merriweather',
            'page_secondary_font' => 'Source Sans Pro',
            'widget_primary_font' => 'Merriweather',
            'widget_secondary_font' => 'Source Sans Pro',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'border_glow_intensity' => 'none',
                'glow_color' => '#4ade80',
                'spacing' => 'spacious',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 5: Midnight Elegance (Gradient)
        [
            'name' => 'Midnight Elegance',
            'colors' => [
                'primary' => '#0f172a',
                'secondary' => '#ffffff',
                'accent' => '#fbbf24'
            ],
            'fonts' => [
                'heading' => 'Cormorant Garamond',
                'body' => 'Crimson Pro'
            ],
            'page_background' => 'linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%)',
            'widget_background' => 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)',
            'widget_border_color' => '#fbbf24',
            'page_primary_font' => 'Cormorant Garamond',
            'page_secondary_font' => 'Crimson Pro',
            'widget_primary_font' => 'Cormorant Garamond',
            'widget_secondary_font' => 'Crimson Pro',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'glow',
                'border_shadow_intensity' => 'none',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#fbbf24',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'depth'
        ],
        
        // Theme 6: Aurora Sky (Gradient)
        [
            'name' => 'Aurora Sky',
            'colors' => [
                'primary' => '#1e1b4b',
                'secondary' => '#ffffff',
                'accent' => '#a78bfa'
            ],
            'fonts' => [
                'heading' => 'Space Grotesk',
                'body' => 'Inter'
            ],
            'page_background' => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 50%, #3b82f6 100%)',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#a78bfa',
            'page_primary_font' => 'Space Grotesk',
            'page_secondary_font' => 'Inter',
            'widget_primary_font' => 'Space Grotesk',
            'widget_secondary_font' => 'Inter',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_shadow_intensity' => 'none',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#a78bfa',
                'spacing' => 'comfortable',
                'shape' => 'round'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 7: Warm Nostalgia (Gradient - Vintage Retro)
        [
            'name' => 'Warm Nostalgia',
            'colors' => [
                'primary' => '#4a2c2a',
                'secondary' => '#f5e6d3',
                'accent' => '#d2691e'
            ],
            'fonts' => [
                'heading' => 'Satisfy',
                'body' => 'Satisfy'
            ],
            'page_background' => 'linear-gradient(135deg, #d2691e 0%, #cd853f 50%, #daa520 100%)',
            'widget_background' => '#f5e6d3',
            'widget_border_color' => '#4a2c2a',
            'page_primary_font' => 'Satisfy',
            'page_secondary_font' => 'Satisfy',
            'widget_primary_font' => 'Six Caps',
            'widget_secondary_font' => 'Satisfy',
            'widget_styles' => [
                'border_width' => 'thick',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'pronounced',
                'border_glow_intensity' => 'none',
                'glow_color' => '#d2691e',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 8: Cyber Wave (Gradient)
        [
            'name' => 'Cyber Wave',
            'colors' => [
                'primary' => '#0f172a',
                'secondary' => '#ffffff',
                'accent' => '#00f0ff'
            ],
            'fonts' => [
                'heading' => 'Orbitron',
                'body' => 'Rajdhani'
            ],
            'page_background' => 'linear-gradient(135deg, #00f0ff 0%, #7c3aed 50%, #ec4899 100%)',
            'widget_background' => '#0f172a',
            'widget_border_color' => '#00f0ff',
            'page_primary_font' => 'Orbitron',
            'page_secondary_font' => 'Rajdhani',
            'widget_primary_font' => 'Orbitron',
            'widget_secondary_font' => 'Rajdhani',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'glow',
                'border_shadow_intensity' => 'none',
                'border_glow_intensity' => 'pronounced',
                'glow_color' => '#00f0ff',
                'spacing' => 'comfortable',
                'shape' => 'square'
            ],
            'spatial_effect' => 'glass'
        ],
        
        // Theme 9: Lavender Dreams (Gradient)
        [
            'name' => 'Lavender Dreams',
            'colors' => [
                'primary' => '#4c1d95',
                'secondary' => '#faf5ff',
                'accent' => '#a855f7'
            ],
            'fonts' => [
                'heading' => 'Lora',
                'body' => 'Lato'
            ],
            'page_background' => 'linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 50%, #a78bfa 100%)',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#a855f7',
            'page_primary_font' => 'Lora',
            'page_secondary_font' => 'Lato',
            'widget_primary_font' => 'Lora',
            'widget_secondary_font' => 'Lato',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_glow_intensity' => 'none',
                'glow_color' => '#a855f7',
                'spacing' => 'comfortable',
                'shape' => 'round'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 10: Pure Professional (Solid)
        [
            'name' => 'Pure Professional',
            'colors' => [
                'primary' => '#1e3a8a',
                'secondary' => '#ffffff',
                'accent' => '#3b82f6'
            ],
            'fonts' => [
                'heading' => 'Roboto Slab',
                'body' => 'Roboto'
            ],
            'page_background' => '#ffffff',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#1e3a8a',
            'page_primary_font' => 'Roboto Slab',
            'page_secondary_font' => 'Roboto',
            'widget_primary_font' => 'Roboto Slab',
            'widget_secondary_font' => 'Roboto',
            'widget_styles' => [
                'border_width' => 'medium',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_glow_intensity' => 'none',
                'glow_color' => '#3b82f6',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 11: Dark Mode Pro (Solid)
        [
            'name' => 'Dark Mode Pro',
            'colors' => [
                'primary' => '#ffffff',
                'secondary' => '#111827',
                'accent' => '#60a5fa'
            ],
            'fonts' => [
                'heading' => 'Inter',
                'body' => 'Inter'
            ],
            'page_background' => '#111827',
            'widget_background' => '#1f2937',
            'widget_border_color' => '#60a5fa',
            'page_primary_font' => 'Inter',
            'page_secondary_font' => 'Inter',
            'widget_primary_font' => 'Inter',
            'widget_secondary_font' => 'Inter',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'glow',
                'border_shadow_intensity' => 'none',
                'border_glow_intensity' => 'subtle',
                'glow_color' => '#60a5fa',
                'spacing' => 'comfortable',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ],
        
        // Theme 12: Warm Minimalist (Solid)
        [
            'name' => 'Warm Minimalist',
            'colors' => [
                'primary' => '#78350f',
                'secondary' => '#fef3c7',
                'accent' => '#d97706'
            ],
            'fonts' => [
                'heading' => 'Cormorant',
                'body' => 'Libre Franklin'
            ],
            'page_background' => '#fef3c7',
            'widget_background' => '#ffffff',
            'widget_border_color' => '#d97706',
            'page_primary_font' => 'Cormorant',
            'page_secondary_font' => 'Libre Franklin',
            'widget_primary_font' => 'Cormorant',
            'widget_secondary_font' => 'Libre Franklin',
            'widget_styles' => [
                'border_width' => 'thin',
                'border_effect' => 'shadow',
                'border_shadow_intensity' => 'subtle',
                'border_glow_intensity' => 'none',
                'glow_color' => '#d97706',
                'spacing' => 'spacious',
                'shape' => 'rounded'
            ],
            'spatial_effect' => 'none'
        ]
    ];
    
    // Step 3: Insert themes into database
    $inserted = 0;
    
    foreach ($themes as $theme) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO themes (
                    user_id, name, colors, fonts, page_background, 
                    widget_styles, spatial_effect, widget_background, 
                    widget_border_color, widget_primary_font, widget_secondary_font,
                    page_primary_font, page_secondary_font, is_active
                )
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $theme['name'],
                json_encode($theme['colors']),
                json_encode($theme['fonts']),
                $theme['page_background'],
                json_encode($theme['widget_styles']),
                $theme['spatial_effect'],
                $theme['widget_background'],
                $theme['widget_border_color'],
                $theme['widget_primary_font'],
                $theme['widget_secondary_font'],
                $theme['page_primary_font'],
                $theme['page_secondary_font']
            ]);
            
            $themeType = strpos($theme['page_background'], 'gradient') !== false ? 'Gradient' : 'Solid';
            $bgPreview = strlen($theme['page_background']) > 50 ? substr($theme['page_background'], 0, 50) . '...' : $theme['page_background'];
            
            echo "✅ {$themeType}: {$theme['name']}\n";
            echo "   Background: {$bgPreview}\n";
            echo "   Colors: {$theme['colors']['primary']} / {$theme['colors']['secondary']} / {$theme['colors']['accent']}\n";
            echo "   Fonts: {$theme['page_primary_font']} + {$theme['page_secondary_font']}\n";
            echo "   Widget Effect: {$theme['widget_styles']['border_effect']} ({$theme['widget_styles']['border_width']}, {$theme['widget_styles']['shape']})\n";
            echo "   Spatial: {$theme['spatial_effect']}\n\n";
            
            $inserted++;
            
        } catch (PDOException $e) {
            echo "❌ Error adding theme '{$theme['name']}': " . $e->getMessage() . "\n\n";
        }
    }
    
    $pdo->commit();
    
    echo "==========================================\n";
    echo "✅ Theme Collection Redesign Complete!\n";
    echo "   Deleted: $deleted old themes\n";
    echo "   Created: $inserted new themes\n";
    
    // Count gradient vs solid
    $gradientCount = 0;
    $solidCount = 0;
    foreach ($themes as $theme) {
        if (strpos($theme['page_background'], 'gradient') !== false) {
            $gradientCount++;
        } else {
            $solidCount++;
        }
    }
    
    echo "   - $gradientCount Gradient themes\n";
    echo "   - $solidCount Solid color themes\n";
    echo "==========================================\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
