<?php
/**
 * Add Mount Shasta Theme
 * 
 * A majestic snow-capped mountain inspired theme
 * Features:
 * - Whites, grays, and cool blues with alpine colors
 * - Extensive use of BOTH shadow AND glow effects
 * - Bold, majestic typography
 * - Multiple gradient backgrounds
 * - Pristine, alpine aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "⛰️ Mount Shasta Theme Installer\n";
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
    'name' => 'Mount Shasta',
    'legacy_colors' => [
        'primary' => '#708090', // Slate gray
        'secondary' => '#B0C4DE', // Light steel blue
        'accent' => '#FFFFFF' // White snow
    ],
    'fonts' => [
        'heading' => 'Oswald',
        'body' => 'Roboto'
    ],
    'page_primary_font' => 'Oswald',
    'page_secondary_font' => 'Roboto',
    'widget_primary_font' => 'Oswald',
    'widget_secondary_font' => 'Roboto',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#B0C4DE', // Light steel blue glow
        'glow_width' => 20, // Large glow
        'glow_intensity' => 0.95, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFFFFF 0%, #F0F8FF 20%, #B0C4DE 35%, #708090 50%, #B0C4DE 65%, #F0F8FF 80%, #FFFFFF 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(176, 196, 222, 0.18) 50%, rgba(112, 128, 144, 0.13) 100%)',
    'widget_border_color' => 'rgba(176, 196, 222, 0.82)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F0F8FF',
            'base' => 'linear-gradient(135deg, #FFFFFF 0%, #F0F8FF 20%, #B0C4DE 35%, #708090 50%, #B0C4DE 65%, #F0F8FF 80%, #FFFFFF 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(176, 196, 222, 0.18) 50%, rgba(112, 128, 144, 0.13) 100%)',
            'surface_translucent' => 'rgba(176, 196, 222, 0.3)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 255, 255, 0.98) 0%, rgba(176, 196, 222, 0.22) 100%)',
            'overlay' => 'rgba(240, 248, 255, 0.9)'
        ],
        'text' => [
            'primary' => '#2F4F4F', // Dark slate gray
            'secondary' => '#556B2F', // Dark olive green
            'inverse' => '#FFFFFF'
        ],
        'border' => [
            'default' => 'rgba(176, 196, 222, 0.82)',
            'focus' => '#708090' // Slate gray focus
        ],
        'accent' => [
            'primary' => '#B0C4DE', // Light steel blue
            'muted' => 'rgba(176, 196, 222, 0.4)',
            'alt' => '#708090', // Slate gray
            'highlight' => '#FFFFFF' // White
        ],
        'stroke' => [
            'subtle' => 'rgba(176, 196, 222, 0.62)'
        ],
        'state' => [
            'success' => '#48C9B0', // Turquoise
            'warning' => '#F4D03F', // Yellow
            'danger' => '#E74C3C' // Red
        ],
        'text_state' => [
            'success' => '#0A3D32',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(47, 79, 79, 0.32)',
            'focus' => 'rgba(112, 128, 144, 0.64)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFFFFF 0%, #F0F8FF 20%, #B0C4DE 35%, #708090 50%, #B0C4DE 65%, #F0F8FF 80%, #FFFFFF 100%)',
            'accent' => 'linear-gradient(135deg, #B0C4DE 0%, #708090 50%, #FFFFFF 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(176, 196, 222, 0.22) 50%, rgba(112, 128, 144, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #F0F8FF 0%, #B0C4DE 50%, #708090 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(176, 196, 222, 0.83)',
            'secondary' => 'rgba(112, 128, 144, 0.73)',
            'accent' => 'rgba(255, 255, 255, 0.63)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Oswald',
            'body' => 'Roboto',
            'widget_heading' => 'Oswald',
            'widget_body' => 'Roboto',
            'metatext' => 'Roboto'
        ],
        'color' => [
            'heading' => '#2F4F4F', // Dark slate gray
            'body' => '#556B2F', // Dark olive green
            'widget_heading' => '#2F4F4F',
            'widget_body' => '#556B2F'
        ],
        'effect' => [
            'border' => [
                'color' => '#708090', // Slate gray border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#2F4F4F', // Dark slate gray shadow
                'intensity' => 0.69,
                'depth' => 6, // Deep shadow
                'blur' => 15 // Wide blur
            ],
            'glow' => [
                'color' => '#B0C4DE', // Light steel blue glow
                'width' => 20 // 20px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.1,
            'lg' => 2.3,
            'md' => 1.65,
            'sm' => 1.28,
            'xs' => 1.12
        ],
        'line_height' => [
            'tight' => 1.22,
            'normal' => 1.58,
            'relaxed' => 1.82
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 600,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '31px',
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
            'sm' => '0.6rem',
            'md' => '1.1rem',
            'lg' => '1.9rem',
            'pill' => '9999px'
        ],
        'border_width' => [
            'hairline' => '1px',
            'regular' => '2px',
            'bold' => '3px'
        ],
        'shadow' => [
            'level_1' => '0 15px 42px rgba(47, 79, 79, 0.32), 0 0 30px rgba(176, 196, 222, 0.52)',
            'level_2' => '0 23px 66px rgba(47, 79, 79, 0.42), 0 0 46px rgba(176, 196, 222, 0.62)',
            'focus' => '0 0 0 4px rgba(112, 128, 144, 0.74), 0 0 30px rgba(112, 128, 144, 0.54)'
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

echo "\n🚀 Mount Shasta theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Whites, grays, and cool blues with alpine colors\n";
echo "   - Bold, majestic typography (Oswald + Roboto)\n";
echo "   - Page title: 3px slate gray border + deep shadow + 20px steel blue glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced steel blue glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (white/gray/blue tones)\n";
echo "   - Pristine, alpine aesthetic\n";
echo "\n";

