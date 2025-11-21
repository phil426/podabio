<?php
/**
 * Add Canvas Sail Theme
 * 
 * A nautical sailing-inspired theme
 * Features:
 * - Navy blues, whites, and sail colors
 * - Extensive use of BOTH shadow AND glow effects
 * - Maritime typography
 * - Multiple gradient backgrounds
 * - Classic, nautical aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "⛵ Canvas Sail Theme Installer\n";
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
    'name' => 'Canvas Sail',
    'legacy_colors' => [
        'primary' => '#000080', // Navy blue
        'secondary' => '#4169E1', // Royal blue
        'accent' => '#F8F8FF' // Ghost white
    ],
    'fonts' => [
        'heading' => 'Bebas Neue',
        'body' => 'PT Sans'
    ],
    'page_primary_font' => 'Bebas Neue',
    'page_secondary_font' => 'PT Sans',
    'widget_primary_font' => 'Bebas Neue',
    'widget_secondary_font' => 'PT Sans',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#4169E1', // Royal blue glow
        'glow_width' => 21, // Large glow
        'glow_intensity' => 0.97, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #F8F8FF 0%, #E6E6FA 20%, #4169E1 35%, #000080 50%, #4169E1 65%, #E6E6FA 80%, #F8F8FF 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(248, 248, 255, 0.96) 0%, rgba(65, 105, 225, 0.2) 50%, rgba(0, 0, 128, 0.15) 100%)',
    'widget_border_color' => 'rgba(65, 105, 225, 0.83)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F8F8FF',
            'base' => 'linear-gradient(135deg, #F8F8FF 0%, #E6E6FA 20%, #4169E1 35%, #000080 50%, #4169E1 65%, #E6E6FA 80%, #F8F8FF 100%)',
            'surface' => 'linear-gradient(145deg, rgba(248, 248, 255, 0.96) 0%, rgba(65, 105, 225, 0.2) 50%, rgba(0, 0, 128, 0.15) 100%)',
            'surface_translucent' => 'rgba(65, 105, 225, 0.31)',
            'surface_raised' => 'linear-gradient(160deg, rgba(248, 248, 255, 0.99) 0%, rgba(65, 105, 225, 0.24) 100%)',
            'overlay' => 'rgba(248, 248, 255, 0.91)'
        ],
        'text' => [
            'primary' => '#000080', // Navy blue
            'secondary' => '#191970', // Midnight blue
            'inverse' => '#F8F8FF'
        ],
        'border' => [
            'default' => 'rgba(65, 105, 225, 0.83)',
            'focus' => '#000080' // Navy blue focus
        ],
        'accent' => [
            'primary' => '#4169E1', // Royal blue
            'muted' => 'rgba(65, 105, 225, 0.41)',
            'alt' => '#000080', // Navy blue
            'highlight' => '#F8F8FF' // Ghost white
        ],
        'stroke' => [
            'subtle' => 'rgba(65, 105, 225, 0.63)'
        ],
        'state' => [
            'success' => '#48C9B0', // Turquoise
            'warning' => '#FFD700', // Gold
            'danger' => '#DC143C' // Crimson
        ],
        'text_state' => [
            'success' => '#0A3D32',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(0, 0, 128, 0.33)',
            'focus' => 'rgba(0, 0, 128, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #F8F8FF 0%, #E6E6FA 20%, #4169E1 35%, #000080 50%, #4169E1 65%, #E6E6FA 80%, #F8F8FF 100%)',
            'accent' => 'linear-gradient(135deg, #4169E1 0%, #000080 50%, #F8F8FF 100%)',
            'widget' => 'linear-gradient(145deg, rgba(248, 248, 255, 0.99) 0%, rgba(65, 105, 225, 0.24) 50%, rgba(0, 0, 128, 0.2) 100%)',
            'podcast' => 'linear-gradient(180deg, #F8F8FF 0%, #4169E1 50%, #000080 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(65, 105, 225, 0.84)',
            'secondary' => 'rgba(0, 0, 128, 0.74)',
            'accent' => 'rgba(248, 248, 255, 0.64)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Bebas Neue',
            'body' => 'PT Sans',
            'widget_heading' => 'Bebas Neue',
            'widget_body' => 'PT Sans',
            'metatext' => 'PT Sans'
        ],
        'color' => [
            'heading' => '#000080', // Navy blue
            'body' => '#191970', // Midnight blue
            'widget_heading' => '#000080',
            'widget_body' => '#191970'
        ],
        'effect' => [
            'border' => [
                'color' => '#000080', // Navy blue border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#000080', // Navy blue shadow
                'intensity' => 0.7,
                'depth' => 7, // Deep shadow
                'blur' => 16 // Wide blur
            ],
            'glow' => [
                'color' => '#4169E1', // Royal blue glow
                'width' => 21 // 21px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.3,
            'lg' => 2.5,
            'md' => 1.7,
            'sm' => 1.3,
            'xs' => 1.15
        ],
        'line_height' => [
            'tight' => 1.25,
            'normal' => 1.6,
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
        'vertical_spacing' => '34px',
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
            'level_1' => '0 16px 44px rgba(0, 0, 128, 0.33), 0 0 32px rgba(65, 105, 225, 0.53)',
            'level_2' => '0 24px 68px rgba(0, 0, 128, 0.43), 0 0 48px rgba(65, 105, 225, 0.63)',
            'focus' => '0 0 0 4px rgba(0, 0, 128, 0.75), 0 0 32px rgba(0, 0, 128, 0.55)'
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

echo "\n🚀 Canvas Sail theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Navy blues, whites, and sail colors\n";
echo "   - Maritime typography (Bebas Neue + PT Sans)\n";
echo "   - Page title: 3px navy blue border + deep shadow + 21px royal blue glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced royal blue glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (navy/royal blue/white tones)\n";
echo "   - Classic, nautical aesthetic\n";
echo "\n";

