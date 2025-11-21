<?php
/**
 * Add Wheat Field Theme
 * 
 * A warm, golden theme inspired by rolling wheat fields
 * Features:
 * - Warm colors (golden yellows, ambers, wheat tones, earth browns)
 * - Extensive use of BOTH shadow AND glow effects
 * - Warm, inviting typography
 * - Multiple gradient backgrounds
 * - Rich, golden tones with depth
 * - Harvest and field-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🌾 Wheat Field Theme Installer\n";
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
    'name' => 'Wheat Field',
    'legacy_colors' => [
        'primary' => '#DAA520', // Goldenrod
        'secondary' => '#F4A460', // Sandy brown
        'accent' => '#CD853F' // Peru
    ],
    'fonts' => [
        'heading' => 'Merriweather',
        'body' => 'Lato'
    ],
    'page_primary_font' => 'Merriweather',
    'page_secondary_font' => 'Lato',
    'widget_primary_font' => 'Merriweather',
    'widget_secondary_font' => 'Lato',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#DAA520', // Goldenrod glow
        'glow_width' => 19, // Large glow
        'glow_intensity' => 0.92, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 20%, #DEB887 35%, #DAA520 50%, #CD853F 65%, #8B7355 80%, #FFF8DC 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(218, 165, 32, 0.18) 0%, rgba(244, 164, 96, 0.16) 50%, rgba(205, 133, 63, 0.14) 100%)',
    'widget_border_color' => 'rgba(218, 165, 32, 0.9)',
    'color_tokens' => [
        'background' => [
            'frame' => '#8B7355',
            'base' => 'linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 20%, #DEB887 35%, #DAA520 50%, #CD853F 65%, #8B7355 80%, #FFF8DC 100%)',
            'surface' => 'linear-gradient(145deg, rgba(218, 165, 32, 0.18) 0%, rgba(244, 164, 96, 0.16) 50%, rgba(205, 133, 63, 0.14) 100%)',
            'surface_translucent' => 'rgba(218, 165, 32, 0.25)',
            'surface_raised' => 'linear-gradient(160deg, rgba(218, 165, 32, 0.22) 0%, rgba(244, 164, 96, 0.2) 100%)',
            'overlay' => 'rgba(139, 115, 85, 0.85)'
        ],
        'text' => [
            'primary' => '#654321', // Dark brown
            'secondary' => '#8B4513', // Saddle brown
            'inverse' => '#FFF8DC' // Cornsilk for inverse
        ],
        'border' => [
            'default' => 'rgba(218, 165, 32, 0.9)',
            'focus' => '#F4A460' // Sandy brown focus
        ],
        'accent' => [
            'primary' => '#DAA520', // Goldenrod
            'muted' => 'rgba(218, 165, 32, 0.4)',
            'alt' => '#F4A460', // Sandy brown
            'highlight' => '#CD853F' // Peru
        ],
        'stroke' => [
            'subtle' => 'rgba(218, 165, 32, 0.6)'
        ],
        'state' => [
            'success' => '#9ACD32', // Yellow green
            'warning' => '#FFA500', // Orange
            'danger' => '#CD5C5C' // Indian red
        ],
        'text_state' => [
            'success' => '#2F4F2F',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(139, 115, 85, 0.5)',
            'focus' => 'rgba(244, 164, 96, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFF8DC 0%, #F5DEB3 20%, #DEB887 35%, #DAA520 50%, #CD853F 65%, #8B7355 80%, #FFF8DC 100%)',
            'accent' => 'linear-gradient(135deg, #DAA520 0%, #F4A460 50%, #CD853F 100%)',
            'widget' => 'linear-gradient(145deg, rgba(218, 165, 32, 0.22) 0%, rgba(244, 164, 96, 0.2) 50%, rgba(205, 133, 63, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #8B7355 0%, #DAA520 50%, #CD853F 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(218, 165, 32, 0.8)',
            'secondary' => 'rgba(244, 164, 96, 0.7)',
            'accent' => 'rgba(205, 133, 63, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Merriweather',
            'body' => 'Lato',
            'widget_heading' => 'Merriweather',
            'widget_body' => 'Lato',
            'metatext' => 'Lato'
        ],
        'color' => [
            'heading' => '#654321', // Dark brown
            'body' => '#8B4513', // Saddle brown
            'widget_heading' => '#654321',
            'widget_body' => '#8B4513'
        ],
        'effect' => [
            'border' => [
                'color' => '#F4A460', // Sandy brown border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#8B7355', // Dark tan shadow
                'intensity' => 0.75,
                'depth' => 6, // Deep shadow
                'blur' => 12 // Wide blur
            ],
            'glow' => [
                'color' => '#DAA520', // Goldenrod glow
                'width' => 17 // 17px glow width
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
            'medium' => 600,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '33px',
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
            'level_1' => '0 12px 36px rgba(139, 115, 85, 0.5), 0 0 24px rgba(218, 165, 32, 0.45)',
            'level_2' => '0 20px 60px rgba(139, 115, 85, 0.6), 0 0 40px rgba(218, 165, 32, 0.55)',
            'focus' => '0 0 0 4px rgba(244, 164, 96, 0.75), 0 0 24px rgba(244, 164, 96, 0.55)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '105ms',
            'fast' => '155ms',
            'standard' => '290ms'
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

echo "\n🚀 Wheat Field theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Warm golden colors (goldenrod, sandy brown, peru) with harvest gradients\n";
echo "   - Warm typography (Merriweather + Lato)\n";
echo "   - Page title: 3px sandy brown border + dark tan shadow + 17px goldenrod glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced goldenrod glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (wheat, golden, earth tones)\n";
echo "   - Warm, inviting harvest palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_wheat_field_profile_settings.php\n";

