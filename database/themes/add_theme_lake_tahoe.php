<?php
/**
 * Add Lake Tahoe Theme
 * 
 * A crystal clear alpine lake inspired theme
 * Features:
 * - Deep blues, turquoises, and whites with alpine colors
 * - Extensive use of BOTH shadow AND glow effects
 * - Clean, crisp typography
 * - Multiple gradient backgrounds
 * - Fresh, alpine aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🏔️ Lake Tahoe Theme Installer\n";
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
    'name' => 'Lake Tahoe',
    'legacy_colors' => [
        'primary' => '#0066CC', // Deep blue
        'secondary' => '#00CED1', // Dark turquoise
        'accent' => '#E0F6FF' // Light blue
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
        'glow_color' => '#00CED1', // Dark turquoise glow
        'glow_width' => 21, // Large glow
        'glow_intensity' => 0.96, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #E0F6FF 0%, #B0E0E6 20%, #00CED1 35%, #0066CC 50%, #00CED1 65%, #B0E0E6 80%, #E0F6FF 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(224, 246, 255, 0.94) 0%, rgba(0, 206, 209, 0.19) 50%, rgba(0, 102, 204, 0.14) 100%)',
    'widget_border_color' => 'rgba(0, 206, 209, 0.8)',
    'color_tokens' => [
        'background' => [
            'frame' => '#E0F6FF',
            'base' => 'linear-gradient(135deg, #E0F6FF 0%, #B0E0E6 20%, #00CED1 35%, #0066CC 50%, #00CED1 65%, #B0E0E6 80%, #E0F6FF 100%)',
            'surface' => 'linear-gradient(145deg, rgba(224, 246, 255, 0.94) 0%, rgba(0, 206, 209, 0.19) 50%, rgba(0, 102, 204, 0.14) 100%)',
            'surface_translucent' => 'rgba(0, 206, 209, 0.29)',
            'surface_raised' => 'linear-gradient(160deg, rgba(224, 246, 255, 0.98) 0%, rgba(0, 206, 209, 0.23) 100%)',
            'overlay' => 'rgba(224, 246, 255, 0.89)'
        ],
        'text' => [
            'primary' => '#003366', // Dark blue
            'secondary' => '#004080', // Medium blue
            'inverse' => '#E0F6FF'
        ],
        'border' => [
            'default' => 'rgba(0, 206, 209, 0.8)',
            'focus' => '#0066CC' // Deep blue focus
        ],
        'accent' => [
            'primary' => '#00CED1', // Dark turquoise
            'muted' => 'rgba(0, 206, 209, 0.39)',
            'alt' => '#0066CC', // Deep blue
            'highlight' => '#E0F6FF' // Light blue
        ],
        'stroke' => [
            'subtle' => 'rgba(0, 206, 209, 0.59)'
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
            'ambient' => 'rgba(0, 51, 102, 0.3)',
            'focus' => 'rgba(0, 102, 204, 0.63)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #E0F6FF 0%, #B0E0E6 20%, #00CED1 35%, #0066CC 50%, #00CED1 65%, #B0E0E6 80%, #E0F6FF 100%)',
            'accent' => 'linear-gradient(135deg, #00CED1 0%, #0066CC 50%, #E0F6FF 100%)',
            'widget' => 'linear-gradient(145deg, rgba(224, 246, 255, 0.98) 0%, rgba(0, 206, 209, 0.23) 50%, rgba(0, 102, 204, 0.19) 100%)',
            'podcast' => 'linear-gradient(180deg, #E0F6FF 0%, #00CED1 50%, #0066CC 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(0, 206, 209, 0.81)',
            'secondary' => 'rgba(0, 102, 204, 0.71)',
            'accent' => 'rgba(224, 246, 255, 0.61)'
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
            'heading' => '#003366', // Dark blue
            'body' => '#004080', // Medium blue
            'widget_heading' => '#003366',
            'widget_body' => '#004080'
        ],
        'effect' => [
            'border' => [
                'color' => '#0066CC', // Deep blue border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#003366', // Dark blue shadow
                'intensity' => 0.67,
                'depth' => 6, // Deep shadow
                'blur' => 15 // Wide blur
            ],
            'glow' => [
                'color' => '#00CED1', // Dark turquoise glow
                'width' => 21 // 21px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.2,
            'lg' => 2.4,
            'md' => 1.65,
            'sm' => 1.28,
            'xs' => 1.12
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
        'vertical_spacing' => '32px',
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
            'level_1' => '0 15px 42px rgba(0, 51, 102, 0.3), 0 0 30px rgba(0, 206, 209, 0.5)',
            'level_2' => '0 23px 66px rgba(0, 51, 102, 0.4), 0 0 46px rgba(0, 206, 209, 0.6)',
            'focus' => '0 0 0 4px rgba(0, 102, 204, 0.73), 0 0 30px rgba(0, 102, 204, 0.53)'
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

echo "\n🚀 Lake Tahoe theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Deep blues, turquoises, and whites with alpine colors\n";
echo "   - Clean, crisp typography (Raleway + Source Sans Pro)\n";
echo "   - Page title: 3px deep blue border + deep shadow + 21px turquoise glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced turquoise glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (blue/turquoise tones)\n";
echo "   - Fresh, alpine aesthetic\n";
echo "\n";

