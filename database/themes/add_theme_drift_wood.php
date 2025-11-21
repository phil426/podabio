<?php
/**
 * Add Drift Wood Theme
 * 
 * A natural, weathered theme inspired by driftwood and ocean shores
 * Features:
 * - Natural colors (weathered browns, tans, beiges, ocean blues/greens)
 * - Extensive use of BOTH shadow AND glow effects
 * - Warm, organic typography
 * - Multiple gradient backgrounds
 * - Rich, textured tones with depth
 * - Beach and driftwood-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🪵 Drift Wood Theme Installer\n";
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

$themeConfig = [
    'name' => 'Drift Wood',
    'legacy_colors' => [
        'primary' => '#8B7355', // Khaki
        'secondary' => '#D2B48C', // Tan
        'accent' => '#20B2AA' // Light sea green
    ],
    'fonts' => [
        'heading' => 'Cormorant',
        'body' => 'Lora'
    ],
    'page_primary_font' => 'Cormorant',
    'page_secondary_font' => 'Lora',
    'widget_primary_font' => 'Cormorant',
    'widget_secondary_font' => 'Lora',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#8B7355', // Khaki glow
        'glow_width' => 18, // Large glow
        'glow_intensity' => 0.89, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #F5E6D3 0%, #E6D5C4 20%, #D2B48C 35%, #8B7355 50%, #6B4423 65%, #8B7355 80%, #F5E6D3 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(139, 115, 85, 0.18) 0%, rgba(32, 178, 170, 0.15) 50%, rgba(210, 180, 140, 0.13) 100%)',
    'widget_border_color' => 'rgba(139, 115, 85, 0.88)',
    'color_tokens' => [
        'background' => [
            'frame' => '#6B4423',
            'base' => 'linear-gradient(135deg, #F5E6D3 0%, #E6D5C4 20%, #D2B48C 35%, #8B7355 50%, #6B4423 65%, #8B7355 80%, #F5E6D3 100%)',
            'surface' => 'linear-gradient(145deg, rgba(139, 115, 85, 0.18) 0%, rgba(32, 178, 170, 0.15) 50%, rgba(210, 180, 140, 0.13) 100%)',
            'surface_translucent' => 'rgba(139, 115, 85, 0.24)',
            'surface_raised' => 'linear-gradient(160deg, rgba(139, 115, 85, 0.21) 0%, rgba(32, 178, 170, 0.19) 100%)',
            'overlay' => 'rgba(107, 68, 35, 0.85)'
        ],
        'text' => [
            'primary' => '#3E2723', // Dark brown
            'secondary' => '#5D4037', // Brown
            'inverse' => '#F5E6D3' // Cream for inverse
        ],
        'border' => [
            'default' => 'rgba(139, 115, 85, 0.88)',
            'focus' => '#20B2AA' // Light sea green focus
        ],
        'accent' => [
            'primary' => '#8B7355', // Khaki
            'muted' => 'rgba(139, 115, 85, 0.4)',
            'alt' => '#20B2AA', // Light sea green
            'highlight' => '#D2B48C' // Tan
        ],
        'stroke' => [
            'subtle' => 'rgba(139, 115, 85, 0.55)'
        ],
        'state' => [
            'success' => '#20B2AA', // Light sea green
            'warning' => '#DAA520', // Goldenrod
            'danger' => '#CD5C5C' // Indian red
        ],
        'text_state' => [
            'success' => '#0A3D3A',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(107, 68, 35, 0.5)',
            'focus' => 'rgba(32, 178, 170, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #F5E6D3 0%, #E6D5C4 20%, #D2B48C 35%, #8B7355 50%, #6B4423 65%, #8B7355 80%, #F5E6D3 100%)',
            'accent' => 'linear-gradient(135deg, #8B7355 0%, #20B2AA 50%, #D2B48C 100%)',
            'widget' => 'linear-gradient(145deg, rgba(139, 115, 85, 0.21) 0%, rgba(32, 178, 170, 0.19) 50%, rgba(210, 180, 140, 0.17) 100%)',
            'podcast' => 'linear-gradient(180deg, #6B4423 0%, #8B7355 50%, #D2B48C 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(139, 115, 85, 0.78)',
            'secondary' => 'rgba(32, 178, 170, 0.68)',
            'accent' => 'rgba(210, 180, 140, 0.58)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Cormorant',
            'body' => 'Lora',
            'widget_heading' => 'Cormorant',
            'widget_body' => 'Lora',
            'metatext' => 'Lora'
        ],
        'color' => [
            'heading' => '#3E2723', // Dark brown
            'body' => '#5D4037', // Brown
            'widget_heading' => '#3E2723',
            'widget_body' => '#5D4037'
        ],
        'effect' => [
            'border' => [
                'color' => '#20B2AA', // Light sea green border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#6B4423', // Dark brown shadow
                'intensity' => 0.8,
                'depth' => 6, // Deep shadow
                'blur' => 13 // Wide blur
            ],
            'glow' => [
                'color' => '#8B7355', // Khaki glow
                'width' => 16 // 16px glow width
            ]
        ],
        'scale' => [
            'xl' => 2.95,
            'lg' => 2.15,
            'md' => 1.55,
            'sm' => 1.22,
            'xs' => 1.08
        ],
        'line_height' => [
            'tight' => 1.3,
            'normal' => 1.65,
            'relaxed' => 1.85
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 500,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '34px',
        'base_scale' => [
            '2xs' => 0.3,
            'xs' => 0.6,
            'sm' => 0.9,
            'md' => 1.2,
            'lg' => 1.8,
            'xl' => 2.4,
            '2xl' => 3.6
        ],
        'density_multipliers' => [
            'compact' => [
                '2xs' => 0.8,
                'xs' => 0.85,
                'sm' => 0.9,
                'md' => 1.0,
                'lg' => 1.0,
                'xl' => 1.0,
                '2xl' => 1.0
            ],
            'comfortable' => [
                '2xs' => 1.0,
                'xs' => 1.05,
                'sm' => 1.15,
                'md' => 1.3,
                'lg' => 1.4,
                'xl' => 1.5,
                '2xl' => 1.6
            ]
        ],
        'modifiers' => []
    ],
    'shape_tokens' => [
        'corner' => [
            'none' => '0px',
            'sm' => '0.75rem',
            'md' => '1.25rem',
            'lg' => '2rem',
            'pill' => '9999px'
        ],
        'border_width' => [
            'hairline' => '1px',
            'regular' => '2px',
            'bold' => '3px'
        ],
        'shadow' => [
            'level_1' => '0 12px 36px rgba(107, 68, 35, 0.5), 0 0 24px rgba(139, 115, 85, 0.45)',
            'level_2' => '0 20px 60px rgba(107, 68, 35, 0.6), 0 0 40px rgba(139, 115, 85, 0.55)',
            'focus' => '0 0 0 4px rgba(32, 178, 170, 0.7), 0 0 24px rgba(32, 178, 170, 0.5)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '110ms',
            'fast' => '160ms',
            'standard' => '300ms'
        ],
        'easing' => [
            'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
            'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
        ],
        'focus' => [
            'ring_width' => '4px',
            'ring_offset' => '2px'
        ]
    ],
    'layout_density' => 'comfortable'
];

$name = $themeConfig['name'];
echo "➡️  Processing {$name}...\n";

try {
    $existing = fetchOne("SELECT * FROM themes WHERE name = ? LIMIT 1", [$name]);
} catch (PDOException $e) {
    echo "   ❌ Failed to lookup theme '{$name}': " . $e->getMessage() . "\n";
    exit(1);
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
    exit(1);
}

echo "\n🚀 Drift Wood theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Natural driftwood colors (khaki, tan, brown, light sea green) with beach gradients\n";
echo "   - Warm typography (Cormorant + Lora)\n";
echo "   - Page title: 3px sea green border + deep brown shadow + 16px khaki glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced khaki glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (cream, tan, khaki, brown tones)\n";
echo "   - Warm, natural beach palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_drift_wood_profile_settings.php\n";

