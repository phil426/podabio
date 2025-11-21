<?php
/**
 * Add Arizona Desert Bloom Theme
 * 
 * A vibrant, desert-inspired theme with warm colors and blooming flowers
 * Features:
 * - Desert colors (sandy beiges, sunset oranges, cactus greens, bloom purples/pinks)
 * - Extensive use of BOTH shadow AND glow effects
 * - Warm, inviting typography
 * - Multiple gradient backgrounds
 * - Rich, earthy tones with vibrant accents
 * - Desert sunset and bloom-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🌵 Arizona Desert Bloom Theme Installer\n";
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
    'name' => 'Arizona Desert Bloom',
    'legacy_colors' => [
        'primary' => '#FF6B35', // Sunset orange
        'secondary' => '#F7931E', // Golden orange
        'accent' => '#9B59B6' // Bloom purple
    ],
    'fonts' => [
        'heading' => 'Raleway',
        'body' => 'Source Sans Pro'
    ],
    'page_primary_font' => 'Raleway',
    'page_secondary_font' => 'Source Sans Pro',
    'widget_primary_font' => 'Raleway',
    'widget_secondary_font' => 'Source Sans Pro',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#FF6B35', // Sunset orange glow
        'glow_width' => 18, // Large glow
        'glow_intensity' => 0.9, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #D4A574 0%, #F4A460 15%, #FF6B35 30%, #F7931E 45%, #9B59B6 60%, #E91E63 75%, #D4A574 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 107, 53, 0.18) 0%, rgba(247, 147, 30, 0.16) 50%, rgba(155, 89, 182, 0.14) 100%)',
    'widget_border_color' => 'rgba(255, 107, 53, 0.9)',
    'color_tokens' => [
        'background' => [
            'frame' => '#8B6F47',
            'base' => 'linear-gradient(135deg, #D4A574 0%, #F4A460 15%, #FF6B35 30%, #F7931E 45%, #9B59B6 60%, #E91E63 75%, #D4A574 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 107, 53, 0.18) 0%, rgba(247, 147, 30, 0.16) 50%, rgba(155, 89, 182, 0.14) 100%)',
            'surface_translucent' => 'rgba(255, 107, 53, 0.25)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 107, 53, 0.22) 0%, rgba(247, 147, 30, 0.2) 100%)',
            'overlay' => 'rgba(139, 111, 71, 0.85)'
        ],
        'text' => [
            'primary' => '#FFFFFF', // White for contrast
            'secondary' => '#FFF8DC', // Cornsilk
            'inverse' => '#8B6F47' // Sandy brown for inverse
        ],
        'border' => [
            'default' => 'rgba(255, 107, 53, 0.9)',
            'focus' => '#F7931E' // Golden orange focus
        ],
        'accent' => [
            'primary' => '#FF6B35', // Sunset orange
            'muted' => 'rgba(255, 107, 53, 0.4)',
            'alt' => '#F7931E', // Golden orange
            'highlight' => '#9B59B6' // Bloom purple
        ],
        'stroke' => [
            'subtle' => 'rgba(255, 107, 53, 0.6)'
        ],
        'state' => [
            'success' => '#2ECC71', // Cactus green
            'warning' => '#F7931E', // Golden orange
            'danger' => '#E74C3C' // Red
        ],
        'text_state' => [
            'success' => '#0A3D1A',
            'warning' => '#6B5B00',
            'danger' => '#7A0F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(139, 111, 71, 0.5)',
            'focus' => 'rgba(247, 147, 30, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #D4A574 0%, #F4A460 15%, #FF6B35 30%, #F7931E 45%, #9B59B6 60%, #E91E63 75%, #D4A574 100%)',
            'accent' => 'linear-gradient(135deg, #FF6B35 0%, #F7931E 50%, #9B59B6 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 107, 53, 0.22) 0%, rgba(247, 147, 30, 0.2) 50%, rgba(155, 89, 182, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #8B6F47 0%, #FF6B35 50%, #9B59B6 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(255, 107, 53, 0.8)',
            'secondary' => 'rgba(247, 147, 30, 0.7)',
            'accent' => 'rgba(155, 89, 182, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Raleway',
            'body' => 'Source Sans Pro',
            'widget_heading' => 'Raleway',
            'widget_body' => 'Source Sans Pro',
            'metatext' => 'Source Sans Pro'
        ],
        'color' => [
            'heading' => '#FFFFFF', // White
            'body' => '#FFF8DC', // Cornsilk
            'widget_heading' => '#FFFFFF',
            'widget_body' => '#FFF8DC'
        ],
        'effect' => [
            'border' => [
                'color' => '#F7931E', // Golden orange border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#8B6F47', // Sandy brown shadow
                'intensity' => 0.8,
                'depth' => 6, // Deep shadow
                'blur' => 13 // Wide blur
            ],
            'glow' => [
                'color' => '#FF6B35', // Sunset orange glow
                'width' => 16 // 16px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.1,
            'lg' => 2.3,
            'md' => 1.6,
            'sm' => 1.25,
            'xs' => 1.1
        ],
        'line_height' => [
            'tight' => 1.25,
            'normal' => 1.6,
            'relaxed' => 1.8
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 600,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '30px',
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
            'level_1' => '0 12px 36px rgba(139, 111, 71, 0.5), 0 0 24px rgba(255, 107, 53, 0.45)',
            'level_2' => '0 20px 60px rgba(139, 111, 71, 0.6), 0 0 40px rgba(255, 107, 53, 0.55)',
            'focus' => '0 0 0 4px rgba(247, 147, 30, 0.75), 0 0 24px rgba(247, 147, 30, 0.55)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '95ms',
            'fast' => '140ms',
            'standard' => '260ms'
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

echo "\n🚀 Arizona Desert Bloom theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Vibrant desert colors (sunset orange, golden orange, bloom purple/pink) with warm gradients\n";
echo "   - Clean typography (Raleway + Source Sans Pro)\n";
echo "   - Page title: 3px golden orange border + sandy shadow + 16px sunset orange glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced orange glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (sand, sunset, bloom colors)\n";
echo "   - Warm, vibrant desert palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_arizona_desert_bloom_profile_settings.php\n";

