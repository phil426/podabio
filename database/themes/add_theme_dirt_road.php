<?php
/**
 * Add Dirt Road Theme
 * 
 * An earthy, rustic theme inspired by country roads
 * Features:
 * - Browns, tans, and earth tones with rustic colors
 * - Extensive use of BOTH shadow AND glow effects
 * - Rustic typography
 * - Multiple gradient backgrounds
 * - Natural, earthy aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🛤️ Dirt Road Theme Installer\n";
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
    'name' => 'Dirt Road',
    'legacy_colors' => [
        'primary' => '#8B4513', // Saddle brown
        'secondary' => '#D2691E', // Chocolate
        'accent' => '#CD853F' // Peru
    ],
    'fonts' => [
        'heading' => 'Merriweather',
        'body' => 'Lora'
    ],
    'page_primary_font' => 'Merriweather',
    'page_secondary_font' => 'Lora',
    'widget_primary_font' => 'Merriweather',
    'widget_secondary_font' => 'Lora',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#CD853F', // Peru glow
        'glow_width' => 19, // Large glow
        'glow_intensity' => 0.94, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #F5DEB3 0%, #DEB887 20%, #CD853F 35%, #8B4513 50%, #CD853F 65%, #DEB887 80%, #F5DEB3 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(245, 222, 179, 0.93) 0%, rgba(205, 133, 63, 0.17) 50%, rgba(139, 69, 19, 0.13) 100%)',
    'widget_border_color' => 'rgba(205, 133, 63, 0.78)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F5DEB3',
            'base' => 'linear-gradient(135deg, #F5DEB3 0%, #DEB887 20%, #CD853F 35%, #8B4513 50%, #CD853F 65%, #DEB887 80%, #F5DEB3 100%)',
            'surface' => 'linear-gradient(145deg, rgba(245, 222, 179, 0.93) 0%, rgba(205, 133, 63, 0.17) 50%, rgba(139, 69, 19, 0.13) 100%)',
            'surface_translucent' => 'rgba(205, 133, 63, 0.27)',
            'surface_raised' => 'linear-gradient(160deg, rgba(245, 222, 179, 0.97) 0%, rgba(205, 133, 63, 0.2) 100%)',
            'overlay' => 'rgba(245, 222, 179, 0.87)'
        ],
        'text' => [
            'primary' => '#3E2723', // Dark brown
            'secondary' => '#5D4037', // Brown
            'inverse' => '#F5DEB3'
        ],
        'border' => [
            'default' => 'rgba(205, 133, 63, 0.78)',
            'focus' => '#8B4513' // Saddle brown focus
        ],
        'accent' => [
            'primary' => '#CD853F', // Peru
            'muted' => 'rgba(205, 133, 63, 0.37)',
            'alt' => '#8B4513', // Saddle brown
            'highlight' => '#F5DEB3' // Wheat
        ],
        'stroke' => [
            'subtle' => 'rgba(205, 133, 63, 0.58)'
        ],
        'state' => [
            'success' => '#6B8E23', // Olive drab
            'warning' => '#DAA520', // Goldenrod
            'danger' => '#A0522D' // Sienna
        ],
        'text_state' => [
            'success' => '#2E4A1F',
            'warning' => '#6B5B00',
            'danger' => '#5D2A1A'
        ],
        'shadow' => [
            'ambient' => 'rgba(62, 39, 35, 0.28)',
            'focus' => 'rgba(139, 69, 19, 0.62)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #F5DEB3 0%, #DEB887 20%, #CD853F 35%, #8B4513 50%, #CD853F 65%, #DEB887 80%, #F5DEB3 100%)',
            'accent' => 'linear-gradient(135deg, #CD853F 0%, #8B4513 50%, #F5DEB3 100%)',
            'widget' => 'linear-gradient(145deg, rgba(245, 222, 179, 0.97) 0%, rgba(205, 133, 63, 0.2) 50%, rgba(139, 69, 19, 0.17) 100%)',
            'podcast' => 'linear-gradient(180deg, #F5DEB3 0%, #CD853F 50%, #8B4513 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(205, 133, 63, 0.78)',
            'secondary' => 'rgba(139, 69, 19, 0.68)',
            'accent' => 'rgba(245, 222, 179, 0.58)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Merriweather',
            'body' => 'Lora',
            'widget_heading' => 'Merriweather',
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
                'color' => '#8B4513', // Saddle brown border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#3E2723', // Dark brown shadow
                'intensity' => 0.66,
                'depth' => 6, // Deep shadow
                'blur' => 14 // Wide blur
            ],
            'glow' => [
                'color' => '#CD853F', // Peru glow
                'width' => 19 // 19px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.0,
            'lg' => 2.2,
            'md' => 1.6,
            'sm' => 1.25,
            'xs' => 1.1
        ],
        'line_height' => [
            'tight' => 1.2,
            'normal' => 1.55,
            'relaxed' => 1.75
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
            'level_1' => '0 14px 40px rgba(62, 39, 35, 0.28), 0 0 28px rgba(205, 133, 63, 0.48)',
            'level_2' => '0 22px 64px rgba(62, 39, 35, 0.38), 0 0 44px rgba(205, 133, 63, 0.58)',
            'focus' => '0 0 0 4px rgba(139, 69, 19, 0.72), 0 0 28px rgba(139, 69, 19, 0.52)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '100ms',
            'fast' => '150ms',
            'standard' => '280ms'
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

echo "\n🚀 Dirt Road theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Browns, tans, and earth tones with rustic colors\n";
echo "   - Rustic typography (Merriweather + Lora)\n";
echo "   - Page title: 3px saddle brown border + deep shadow + 19px peru glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced peru glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (brown/tan tones)\n";
echo "   - Natural, earthy aesthetic\n";
echo "\n";

