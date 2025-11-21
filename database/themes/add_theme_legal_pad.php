<?php
/**
 * Add Legal Pad Theme
 * 
 * A classic yellow legal pad inspired theme
 * Features:
 * - Yellow legal pad colors with blue lines
 * - Extensive use of BOTH shadow AND glow effects
 * - Professional typography
 * - Multiple gradient backgrounds
 * - Classic office aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "📝 Legal Pad Theme Installer\n";
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
    'name' => 'Legal Pad',
    'legacy_colors' => [
        'primary' => '#FFEB3B', // Bright yellow
        'secondary' => '#FFF9C4', // Light yellow
        'accent' => '#2196F3' // Blue lines
    ],
    'fonts' => [
        'heading' => 'Roboto',
        'body' => 'Open Sans'
    ],
    'page_primary_font' => 'Roboto',
    'page_secondary_font' => 'Open Sans',
    'widget_primary_font' => 'Roboto',
    'widget_secondary_font' => 'Open Sans',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#2196F3', // Blue glow
        'glow_width' => 20, // Large glow
        'glow_intensity' => 0.95, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFF9C4 0%, #FFEB3B 15%, #FFF59D 30%, #FFF9C4 45%, #FFEB3B 60%, #FFF59D 75%, #FFF9C4 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 249, 196, 0.95) 0%, rgba(33, 150, 243, 0.15) 50%, rgba(255, 235, 59, 0.1) 100%)',
    'widget_border_color' => 'rgba(33, 150, 243, 0.8)',
    'color_tokens' => [
        'background' => [
            'frame' => '#FFF9C4',
            'base' => 'linear-gradient(135deg, #FFF9C4 0%, #FFEB3B 15%, #FFF59D 30%, #FFF9C4 45%, #FFEB3B 60%, #FFF59D 75%, #FFF9C4 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 249, 196, 0.95) 0%, rgba(33, 150, 243, 0.15) 50%, rgba(255, 235, 59, 0.1) 100%)',
            'surface_translucent' => 'rgba(33, 150, 243, 0.25)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 249, 196, 0.98) 0%, rgba(33, 150, 243, 0.2) 100%)',
            'overlay' => 'rgba(255, 249, 196, 0.85)'
        ],
        'text' => [
            'primary' => '#1A237E', // Dark blue
            'secondary' => '#283593', // Medium blue
            'inverse' => '#FFFFFF'
        ],
        'border' => [
            'default' => 'rgba(33, 150, 243, 0.8)',
            'focus' => '#FF6F00' // Orange focus
        ],
        'accent' => [
            'primary' => '#2196F3', // Blue
            'muted' => 'rgba(33, 150, 243, 0.35)',
            'alt' => '#FF6F00', // Orange
            'highlight' => '#FFEB3B' // Yellow
        ],
        'stroke' => [
            'subtle' => 'rgba(33, 150, 243, 0.5)'
        ],
        'state' => [
            'success' => '#4CAF50', // Green
            'warning' => '#FF9800', // Orange
            'danger' => '#F44336' // Red
        ],
        'text_state' => [
            'success' => '#1B5E20',
            'warning' => '#E65100',
            'danger' => '#B71C1C'
        ],
        'shadow' => [
            'ambient' => 'rgba(26, 35, 126, 0.25)',
            'focus' => 'rgba(255, 111, 0, 0.5)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFF9C4 0%, #FFEB3B 15%, #FFF59D 30%, #FFF9C4 45%, #FFEB3B 60%, #FFF59D 75%, #FFF9C4 100%)',
            'accent' => 'linear-gradient(135deg, #2196F3 0%, #FF6F00 50%, #FFEB3B 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 249, 196, 0.98) 0%, rgba(33, 150, 243, 0.2) 50%, rgba(255, 235, 59, 0.15) 100%)',
            'podcast' => 'linear-gradient(180deg, #FFF9C4 0%, #2196F3 50%, #FFEB3B 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(33, 150, 243, 0.75)',
            'secondary' => 'rgba(255, 111, 0, 0.65)',
            'accent' => 'rgba(255, 235, 59, 0.55)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Roboto',
            'body' => 'Open Sans',
            'widget_heading' => 'Roboto',
            'widget_body' => 'Open Sans',
            'metatext' => 'Open Sans'
        ],
        'color' => [
            'heading' => '#1A237E', // Dark blue
            'body' => '#283593', // Medium blue
            'widget_heading' => '#1A237E',
            'widget_body' => '#283593'
        ],
        'effect' => [
            'border' => [
                'color' => '#FF6F00', // Orange border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#1A237E', // Dark blue shadow
                'intensity' => 0.65,
                'depth' => 6, // Deep shadow
                'blur' => 14 // Wide blur
            ],
            'glow' => [
                'color' => '#2196F3', // Blue glow
                'width' => 20 // 20px glow width
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
            'level_1' => '0 14px 40px rgba(26, 35, 126, 0.25), 0 0 28px rgba(33, 150, 243, 0.45)',
            'level_2' => '0 22px 64px rgba(26, 35, 126, 0.35), 0 0 44px rgba(33, 150, 243, 0.55)',
            'focus' => '0 0 0 4px rgba(255, 111, 0, 0.75), 0 0 28px rgba(255, 111, 0, 0.55)'
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

echo "\n🚀 Legal Pad theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Classic yellow legal pad colors with blue lines\n";
echo "   - Professional typography (Roboto + Open Sans)\n";
echo "   - Page title: 3px orange border + deep shadow + 20px blue glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced blue glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (yellow tones)\n";
echo "   - Classic office aesthetic\n";
echo "\n";

