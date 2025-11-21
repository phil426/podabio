<?php
/**
 * Add or Update November 2025 Dynamic Themes
 *
 * Themes:
 *  - Sunveil Dawn
 *  - Spectrum Current
 *  - Terra Filigree
 *  - Monochrome Pulse
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎨 November 2025 Theme Installer\n";
echo "==========================================\n\n";

try {
    $columns = $pdo->query("SHOW COLUMNS FROM themes")->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_map('strtolower', $columns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect themes table: " . $e->getMessage() . "\n";
    exit(1);
}

$hasColorTokens = in_array('color_tokens', $columns, true);
$hasTypographyTokens = in_array('typography_tokens', $columns, true);
$hasSpacingTokens = in_array('spacing_tokens', $columns, true);
$hasShapeTokens = in_array('shape_tokens', $columns, true);
$hasMotionTokens = in_array('motion_tokens', $columns, true);
$hasLayoutDensity = in_array('layout_density', $columns, true);

$themes = [
    [
        'name' => 'Sunveil Dawn',
        'legacy_colors' => [
            'primary' => '#4C3B34',
            'secondary' => '#FFE8C7',
            'accent' => '#F26A4F'
        ],
        'fonts' => [
            'heading' => 'Playfair Display',
            'body' => 'Work Sans'
        ],
        'page_primary_font' => 'Playfair Display',
        'page_secondary_font' => 'Work Sans',
        'widget_primary_font' => 'Work Sans',
        'widget_secondary_font' => 'Work Sans',
        'widget_styles' => [
            'border_width' => 'thin',
            'border_effect' => 'shadow',
            'border_shadow_intensity' => 'subtle',
            'border_glow_intensity' => 'subtle',
            'glow_color' => '#F7B267',
            'spacing' => 'comfortable',
            'shape' => 'rounded'
        ],
        'spatial_effect' => 'none',
        'page_background' => 'linear-gradient(180deg, #FFE8C7 0%, #FDF5EC 100%)',
        'widget_background' => 'rgba(255, 248, 238, 0.82)',
        'widget_border_color' => 'rgba(244, 162, 89, 0.35)',
        'color_tokens' => [
            'background' => [
                'frame' => '#F4EFEA',
                'base' => 'linear-gradient(180deg, #FFE8C7 0%, #FDF5EC 100%)',
                'surface' => 'rgba(255, 248, 238, 0.82)',
                'surface_translucent' => 'rgba(255, 214, 178, 0.32)',
                'surface_raised' => 'rgba(255, 248, 238, 0.92)',
                'overlay' => 'rgba(76, 59, 52, 0.45)'
            ],
            'text' => [
                'primary' => '#4C3B34',
                'secondary' => '#7A655C',
                'inverse' => '#FFFFFF'
            ],
            'border' => [
                'default' => 'rgba(244, 162, 89, 0.35)',
                'focus' => '#F7B267'
            ],
            'accent' => [
                'primary' => '#F26A4F',
                'muted' => 'rgba(242, 106, 79, 0.18)',
                'alt' => '#F4A259',
                'highlight' => '#F7B267'
            ],
            'stroke' => [
                'subtle' => 'rgba(244, 162, 89, 0.35)'
            ],
            'state' => [
                'success' => '#7BC67E',
                'warning' => '#F2C35B',
                'danger' => '#D95F55'
            ],
            'text_state' => [
                'success' => '#1F3A1E',
                'warning' => '#4A3314',
                'danger' => '#3C1414'
            ],
            'shadow' => [
                'ambient' => 'rgba(76, 59, 52, 0.18)',
                'focus' => 'rgba(247, 178, 103, 0.45)'
            ],
            'gradient' => [
                'page' => 'linear-gradient(180deg, #FFE8C7 0%, #FDF5EC 100%)',
                'accent' => 'linear-gradient(135deg, #F26A4F 0%, #F4A259 100%)',
                'widget' => 'linear-gradient(180deg, rgba(255, 255, 255, 0.1) 0%, rgba(242, 106, 79, 0.08) 100%)',
                'podcast' => 'linear-gradient(140deg, #F26A4F 0%, #F4A259 65%, #F7B267 100%)'
            ],
            'glow' => [
                'primary' => 'rgba(247, 178, 103, 0.35)'
            ]
        ],
        'typography_tokens' => [
            'font' => [
                'heading' => 'Playfair Display',
                'body' => 'Work Sans',
                'metatext' => 'Work Sans'
            ],
            'scale' => [
                'xl' => 2.52,
                'lg' => 1.84,
                'md' => 1.32,
                'sm' => 1.12,
                'xs' => 0.92
            ],
            'line_height' => [
                'tight' => 1.24,
                'normal' => 1.58,
                'relaxed' => 1.76
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 500,
                'bold' => 700
            ]
        ],
        'spacing_tokens' => [
            'density' => 'comfortable',
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
                'none' => '0px',
                'sm' => '0.5rem',
                'md' => '1rem',
                'lg' => '1.75rem',
                'pill' => '9999px'
            ],
            'border_width' => [
                'hairline' => '1px',
                'regular' => '2px',
                'bold' => '3px'
            ],
            'shadow' => [
                'level_1' => '0 6px 18px rgba(244, 162, 89, 0.18)',
                'level_2' => '0 16px 38px rgba(242, 106, 79, 0.22)',
                'focus' => '0 0 0 4px rgba(247, 178, 103, 0.45)'
            ]
        ],
        'motion_tokens' => [
            'duration' => [
                'momentary' => '90ms',
                'fast' => '160ms',
                'standard' => '280ms'
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
        'layout_density' => 'comfortable'
    ],
    [
        'name' => 'Spectrum Current',
        'legacy_colors' => [
            'primary' => '#39AAE1',
            'secondary' => '#0A1826',
            'accent' => '#7D5BFF'
        ],
        'fonts' => [
            'heading' => 'Space Grotesk',
            'body' => 'Inter'
        ],
        'page_primary_font' => 'Space Grotesk',
        'page_secondary_font' => 'Inter',
        'widget_primary_font' => 'Inter',
        'widget_secondary_font' => 'Inter',
        'widget_styles' => [
            'border_width' => 'thin',
            'border_effect' => 'glow',
            'border_glow_intensity' => 'subtle',
            'border_shadow_intensity' => 'none',
            'glow_color' => '#3DC9FF',
            'spacing' => 'tight',
            'shape' => 'rounded'
        ],
        'spatial_effect' => 'none',
        'page_background' => 'linear-gradient(200deg, #05223C 0%, #112E5B 60%, #1B3F63 100%)',
        'widget_background' => 'rgba(14, 36, 61, 0.86)',
        'widget_border_color' => 'rgba(61, 201, 255, 0.32)',
        'color_tokens' => [
            'background' => [
                'frame' => '#0A1826',
                'base' => 'linear-gradient(200deg, #05223C 0%, #112E5B 60%, #1B3F63 100%)',
                'surface' => 'rgba(14, 36, 61, 0.86)',
                'surface_translucent' => 'rgba(57, 170, 225, 0.18)',
                'surface_raised' => 'rgba(21, 53, 86, 0.92)',
                'overlay' => 'rgba(4, 15, 34, 0.75)'
            ],
            'text' => [
                'primary' => '#EBF4FF',
                'secondary' => '#B4C9E6',
                'inverse' => '#070B17'
            ],
            'border' => [
                'default' => 'rgba(61, 201, 255, 0.32)',
                'focus' => '#7D5BFF'
            ],
            'accent' => [
                'primary' => '#39AAE1',
                'muted' => 'rgba(61, 201, 255, 0.18)',
                'alt' => '#7D5BFF',
                'highlight' => '#3DD9FF'
            ],
            'stroke' => [
                'subtle' => 'rgba(61, 201, 255, 0.32)'
            ],
            'state' => [
                'success' => '#3AD6B0',
                'warning' => '#FFC75F',
                'danger' => '#FF5C7A'
            ],
            'text_state' => [
                'success' => '#024C3D',
                'warning' => '#4A3110',
                'danger' => '#5A1126'
            ],
            'shadow' => [
                'ambient' => 'rgba(5, 26, 49, 0.28)',
                'focus' => 'rgba(125, 91, 255, 0.45)'
            ],
            'gradient' => [
                'page' => 'linear-gradient(200deg, #05223C 0%, #112E5B 60%, #1B3F63 100%)',
                'accent' => 'linear-gradient(130deg, #39AAE1 0%, #7D5BFF 100%)',
                'widget' => 'linear-gradient(160deg, rgba(61, 201, 255, 0.14) 0%, rgba(125, 91, 255, 0.18) 100%)',
                'podcast' => 'linear-gradient(180deg, #0C1E34 0%, #1B3F63 100%)'
            ],
            'glow' => [
                'primary' => 'rgba(61, 201, 255, 0.4)'
            ]
        ],
        'typography_tokens' => [
            'font' => [
                'heading' => 'Space Grotesk',
                'body' => 'Inter',
                'metatext' => 'Inter'
            ],
            'scale' => [
                'xl' => 2.4,
                'lg' => 1.75,
                'md' => 1.28,
                'sm' => 1.08,
                'xs' => 0.9
            ],
            'line_height' => [
                'tight' => 1.18,
                'normal' => 1.48,
                'relaxed' => 1.66
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 600,
                'bold' => 700
            ]
        ],
        'spacing_tokens' => [
            'density' => 'compact',
            'base_scale' => [
                '2xs' => 0.22,
                'xs' => 0.44,
                'sm' => 0.68,
                'md' => 0.92,
                'lg' => 1.3,
                'xl' => 1.8,
                '2xl' => 2.6
            ],
            'density_multipliers' => [
                'compact' => [
                    '2xs' => 0.85,
                    'xs' => 0.95,
                    'sm' => 1.0,
                    'md' => 1.05,
                    'lg' => 1.08,
                    'xl' => 1.12,
                    '2xl' => 1.15
                ],
                'comfortable' => [
                    '2xs' => 1.05,
                    'xs' => 1.08,
                    'sm' => 1.12,
                    'md' => 1.2,
                    'lg' => 1.28,
                    'xl' => 1.35,
                    '2xl' => 1.42
                ]
            ],
            'modifiers' => []
        ],
        'shape_tokens' => [
            'corner' => [
                'none' => '0px',
                'sm' => '0.4rem',
                'md' => '0.8rem',
                'lg' => '1.4rem',
                'pill' => '9999px'
            ],
            'border_width' => [
                'hairline' => '1px',
                'regular' => '2px',
                'bold' => '3px'
            ],
            'shadow' => [
                'level_1' => '0 10px 20px rgba(3, 16, 35, 0.35)',
                'level_2' => '0 20px 40px rgba(3, 16, 35, 0.45)',
                'focus' => '0 0 0 4px rgba(125, 91, 255, 0.45)'
            ]
        ],
        'motion_tokens' => [
            'duration' => [
                'momentary' => '90ms',
                'fast' => '140ms',
                'standard' => '240ms'
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.32, 0, 0.67, 1)',
                'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
            ],
            'focus' => [
                'ring_width' => '3px',
                'ring_offset' => '2px'
            ]
        ],
        'layout_density' => 'compact'
    ],
    [
        'name' => 'Terra Filigree',
        'legacy_colors' => [
            'primary' => '#35201A',
            'secondary' => '#F8F5F0',
            'accent' => '#C96B35'
        ],
        'fonts' => [
            'heading' => 'Cormorant Garamond',
            'body' => 'Source Sans Pro'
        ],
        'page_primary_font' => 'Cormorant Garamond',
        'page_secondary_font' => 'Source Sans Pro',
        'widget_primary_font' => 'Source Sans Pro',
        'widget_secondary_font' => 'Source Sans Pro',
        'widget_styles' => [
            'border_width' => 'thin',
            'border_effect' => 'shadow',
            'border_shadow_intensity' => 'pronounced',
            'border_glow_intensity' => 'none',
            'glow_color' => '#C96B35',
            'spacing' => 'comfortable',
            'shape' => 'rounded'
        ],
        'spatial_effect' => 'none',
        'page_background' => '#F8F5F0',
        'widget_background' => '#FEFBF6',
        'widget_border_color' => 'rgba(112, 84, 64, 0.28)',
        'color_tokens' => [
            'background' => [
                'frame' => '#201A1A',
                'base' => '#F8F5F0',
                'surface' => '#FEFBF6',
                'surface_translucent' => 'rgba(102, 70, 56, 0.12)',
                'surface_raised' => '#FFFFFF',
                'overlay' => 'rgba(32, 26, 26, 0.6)'
            ],
            'text' => [
                'primary' => '#35201A',
                'secondary' => '#72594B',
                'inverse' => '#FEFBF6'
            ],
            'border' => [
                'default' => 'rgba(112, 84, 64, 0.28)',
                'focus' => '#C96B35'
            ],
            'accent' => [
                'primary' => '#C96B35',
                'muted' => 'rgba(201, 107, 53, 0.18)',
                'alt' => '#6E9C7B',
                'highlight' => '#D8BFD8'
            ],
            'stroke' => [
                'subtle' => 'rgba(112, 84, 64, 0.28)'
            ],
            'state' => [
                'success' => '#7EB77F',
                'warning' => '#E0A458',
                'danger' => '#C44949'
            ],
            'text_state' => [
                'success' => '#1F3B29',
                'warning' => '#4A2F14',
                'danger' => '#4A1616'
            ],
            'shadow' => [
                'ambient' => 'rgba(53, 32, 26, 0.24)',
                'focus' => 'rgba(201, 107, 53, 0.35)'
            ],
            'gradient' => [
                'page' => '#F8F5F0',
                'accent' => 'linear-gradient(135deg, #C96B35 0%, #6E9C7B 100%)',
                'widget' => 'linear-gradient(180deg, rgba(201, 107, 53, 0.08) 0%, rgba(110, 156, 123, 0.12) 100%)',
                'podcast' => 'linear-gradient(160deg, #201A1A 0%, #35201A 100%)'
            ],
            'glow' => [
                'primary' => 'rgba(201, 107, 53, 0.28)'
            ]
        ],
        'typography_tokens' => [
            'font' => [
                'heading' => 'Cormorant Garamond',
                'body' => 'Source Sans Pro',
                'metatext' => 'Source Sans Pro'
            ],
            'scale' => [
                'xl' => 2.6,
                'lg' => 1.82,
                'md' => 1.28,
                'sm' => 1.08,
                'xs' => 0.9
            ],
            'line_height' => [
                'tight' => 1.26,
                'normal' => 1.6,
                'relaxed' => 1.82
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 500,
                'bold' => 700
            ]
        ],
        'spacing_tokens' => [
            'density' => 'comfortable',
            'base_scale' => [
                '2xs' => 0.28,
                'xs' => 0.52,
                'sm' => 0.78,
                'md' => 1.08,
                'lg' => 1.6,
                'xl' => 2.2,
                '2xl' => 3.4
            ],
            'density_multipliers' => [
                'compact' => [
                    '2xs' => 0.8,
                    'xs' => 0.9,
                    'sm' => 0.95,
                    'md' => 1.0,
                    'lg' => 1.05,
                    'xl' => 1.08,
                    '2xl' => 1.1
                ],
                'comfortable' => [
                    '2xs' => 1.0,
                    'xs' => 1.0,
                    'sm' => 1.12,
                    'md' => 1.24,
                    'lg' => 1.32,
                    'xl' => 1.38,
                    '2xl' => 1.44
                ]
            ],
            'modifiers' => []
        ],
        'shape_tokens' => [
            'corner' => [
                'none' => '0px',
                'sm' => '0.45rem',
                'md' => '0.95rem',
                'lg' => '1.6rem',
                'pill' => '9999px'
            ],
            'border_width' => [
                'hairline' => '1px',
                'regular' => '2px',
                'bold' => '3px'
            ],
            'shadow' => [
                'level_1' => '0 8px 24px rgba(53, 32, 26, 0.24)',
                'level_2' => '0 18px 44px rgba(53, 32, 26, 0.32)',
                'focus' => '0 0 0 4px rgba(201, 107, 53, 0.38)'
            ]
        ],
        'motion_tokens' => [
            'duration' => [
                'momentary' => '95ms',
                'fast' => '170ms',
                'standard' => '260ms'
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                'decelerate' => 'cubic-bezier(0.25, 0.1, 0.25, 1)'
            ],
            'focus' => [
                'ring_width' => '3px',
                'ring_offset' => '2px'
            ]
        ],
        'layout_density' => 'comfortable'
    ],
    [
        'name' => 'Monochrome Pulse',
        'legacy_colors' => [
            'primary' => '#FAFAFB',
            'secondary' => '#232326',
            'accent' => '#FCEE21'
        ],
        'fonts' => [
            'heading' => 'Manrope',
            'body' => 'IBM Plex Sans'
        ],
        'page_primary_font' => 'Manrope',
        'page_secondary_font' => 'IBM Plex Sans',
        'widget_primary_font' => 'IBM Plex Sans',
        'widget_secondary_font' => 'IBM Plex Sans',
        'widget_styles' => [
            'border_width' => 'thin',
            'border_effect' => 'glow',
            'border_glow_intensity' => 'pronounced',
            'border_shadow_intensity' => 'none',
            'glow_color' => '#FCEE21',
            'spacing' => 'tight',
            'shape' => 'rounded'
        ],
        'spatial_effect' => 'none',
        'page_background' => 'linear-gradient(160deg, #1F1F21 0%, #111112 100%)',
        'widget_background' => '#232326',
        'widget_border_color' => 'rgba(255, 255, 255, 0.08)',
        'color_tokens' => [
            'background' => [
                'frame' => '#161616',
                'base' => 'linear-gradient(160deg, #1F1F21 0%, #111112 100%)',
                'surface' => '#232326',
                'surface_translucent' => 'rgba(255, 255, 255, 0.06)',
                'surface_raised' => '#2C2C30',
                'overlay' => 'rgba(0, 0, 0, 0.7)'
            ],
            'text' => [
                'primary' => '#FAFAFB',
                'secondary' => '#BBBBBF',
                'inverse' => '#111112'
            ],
            'border' => [
                'default' => 'rgba(255, 255, 255, 0.08)',
                'focus' => '#FCEE21'
            ],
            'accent' => [
                'primary' => '#FCEE21',
                'muted' => 'rgba(252, 238, 33, 0.18)',
                'alt' => '#7F5AF0',
                'highlight' => '#FF8C6A'
            ],
            'stroke' => [
                'subtle' => 'rgba(255, 255, 255, 0.08)'
            ],
            'state' => [
                'success' => '#5CF2B5',
                'warning' => '#FFD369',
                'danger' => '#FF5470'
            ],
            'text_state' => [
                'success' => '#083925',
                'warning' => '#433515',
                'danger' => '#450C1A'
            ],
            'shadow' => [
                'ambient' => 'rgba(0, 0, 0, 0.4)',
                'focus' => 'rgba(127, 90, 240, 0.45)'
            ],
            'gradient' => [
                'page' => 'linear-gradient(160deg, #1F1F21 0%, #111112 100%)',
                'accent' => 'linear-gradient(135deg, #FCEE21 0%, #7F5AF0 100%)',
                'widget' => 'linear-gradient(180deg, rgba(252, 238, 33, 0.08) 0%, rgba(127, 90, 240, 0.12) 100%)',
                'podcast' => 'linear-gradient(180deg, #161616 0%, #232326 100%)'
            ],
            'glow' => [
                'primary' => 'rgba(252, 238, 33, 0.35)'
            ]
        ],
        'typography_tokens' => [
            'font' => [
                'heading' => 'Manrope',
                'body' => 'IBM Plex Sans',
                'metatext' => 'IBM Plex Sans'
            ],
            'scale' => [
                'xl' => 2.46,
                'lg' => 1.82,
                'md' => 1.28,
                'sm' => 1.08,
                'xs' => 0.9
            ],
            'line_height' => [
                'tight' => 1.22,
                'normal' => 1.52,
                'relaxed' => 1.7
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 600,
                'bold' => 700
            ]
        ],
        'spacing_tokens' => [
            'density' => 'compact',
            'base_scale' => [
                '2xs' => 0.24,
                'xs' => 0.48,
                'sm' => 0.72,
                'md' => 0.96,
                'lg' => 1.4,
                'xl' => 1.9,
                '2xl' => 2.8
            ],
            'density_multipliers' => [
                'compact' => [
                    '2xs' => 0.85,
                    'xs' => 0.95,
                    'sm' => 1.0,
                    'md' => 1.05,
                    'lg' => 1.08,
                    'xl' => 1.12,
                    '2xl' => 1.18
                ],
                'comfortable' => [
                    '2xs' => 1.05,
                    'xs' => 1.08,
                    'sm' => 1.15,
                    'md' => 1.25,
                    'lg' => 1.35,
                    'xl' => 1.42,
                    '2xl' => 1.5
                ]
            ],
            'modifiers' => []
        ],
        'shape_tokens' => [
            'corner' => [
                'none' => '0px',
                'sm' => '0.35rem',
                'md' => '0.9rem',
                'lg' => '1.5rem',
                'pill' => '9999px'
            ],
            'border_width' => [
                'hairline' => '1px',
                'regular' => '2px',
                'bold' => '3px'
            ],
            'shadow' => [
                'level_1' => '0 12px 28px rgba(0, 0, 0, 0.35)',
                'level_2' => '0 24px 48px rgba(0, 0, 0, 0.45)',
                'focus' => '0 0 0 4px rgba(127, 90, 240, 0.45)'
            ]
        ],
        'motion_tokens' => [
            'duration' => [
                'momentary' => '90ms',
                'fast' => '150ms',
                'standard' => '240ms'
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                'decelerate' => 'cubic-bezier(0.1, 0, 0.2, 1)'
            ],
            'focus' => [
                'ring_width' => '3px',
                'ring_offset' => '2px'
            ]
        ],
        'layout_density' => 'compact'
    ]
];

foreach ($themes as $themeConfig) {
    $name = $themeConfig['name'];
    echo "➡️  Processing {$name}...\n";

    try {
        $existing = fetchOne("SELECT * FROM themes WHERE name = ? LIMIT 1", [$name]);
    } catch (PDOException $e) {
        echo "   ❌ Failed to lookup theme '{$name}': " . $e->getMessage() . "\n";
        continue;
    }

    $fieldValues = [
        'name' => $name,
        'colors' => json_encode($themeConfig['legacy_colors'], JSON_UNESCAPED_SLASHES),
        'fonts' => json_encode($themeConfig['fonts'], JSON_UNESCAPED_SLASHES),
        'page_background' => $themeConfig['page_background'],
        'widget_styles' => json_encode($themeConfig['widget_styles'], JSON_UNESCAPED_SLASHES),
        'spatial_effect' => $themeConfig['spatial_effect'],
        'widget_background' => $themeConfig['widget_background'],
        'widget_border_color' => $themeConfig['widget_border_color'],
        'widget_primary_font' => $themeConfig['widget_primary_font'],
        'widget_secondary_font' => $themeConfig['widget_secondary_font'],
        'page_primary_font' => $themeConfig['page_primary_font'],
        'page_secondary_font' => $themeConfig['page_secondary_font']
    ];

    if ($hasColorTokens) {
        $fieldValues['color_tokens'] = json_encode($themeConfig['color_tokens'], JSON_UNESCAPED_SLASHES);
    }
    if ($hasTypographyTokens) {
        $fieldValues['typography_tokens'] = json_encode($themeConfig['typography_tokens'], JSON_UNESCAPED_SLASHES);
    }
    if ($hasSpacingTokens) {
        $fieldValues['spacing_tokens'] = json_encode($themeConfig['spacing_tokens'], JSON_UNESCAPED_SLASHES);
    }
    if ($hasShapeTokens) {
        $fieldValues['shape_tokens'] = json_encode($themeConfig['shape_tokens'], JSON_UNESCAPED_SLASHES);
    }
    if ($hasMotionTokens) {
        $fieldValues['motion_tokens'] = json_encode($themeConfig['motion_tokens'], JSON_UNESCAPED_SLASHES);
    }
    if ($hasLayoutDensity) {
        $fieldValues['layout_density'] = $themeConfig['layout_density'];
    }

    try {
        if ($existing) {
            $setSql = ['name = ?'];
            $values = [$name];
            foreach ($fieldValues as $column => $value) {
                if ($column === 'name') {
                    continue;
                }
                $setSql[] = "{$column} = ?";
                $values[] = $value;
            }
            $values[] = $existing['id'];
            $sql = "UPDATE themes SET " . implode(', ', $setSql) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            echo "   ✅ Updated theme (ID: {$existing['id']})\n";
        } else {
            $columnsSql = ['user_id', 'name'];
            $placeholders = ['NULL', '?'];
            $values = [$name];
            foreach ($fieldValues as $column => $value) {
                if ($column === 'name') {
                    continue;
                }
                $columnsSql[] = $column;
                $placeholders[] = '?';
                $values[] = $value;
            }
            $columnsSql[] = 'is_active';
            $placeholders[] = '1';
            $sql = "INSERT INTO themes (" . implode(', ', $columnsSql) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $newId = $pdo->lastInsertId();
            echo "   ✅ Created theme (ID: {$newId})\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Failed to upsert theme '{$name}': " . $e->getMessage() . "\n";
        continue;
    }
}

echo "\n🚀 Themes are ready. Activate them from the Appearance editor!\n";

