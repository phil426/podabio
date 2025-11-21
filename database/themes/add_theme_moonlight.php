<?php
/**
 * Add Moonlight Theme
 * 
 * A dark, mysterious moonlight inspired theme
 * Features:
 * - Deep blues, purples, and silvers with moonlit tones
 * - Extensive use of BOTH shadow AND glow effects
 * - Elegant typography
 * - Multiple gradient backgrounds
 * - Mysterious, nocturnal aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🌙 Moonlight Theme Installer\n";
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
    'name' => 'Moonlight',
    'legacy_colors' => [
        'primary' => '#1E3A5F', // Deep blue
        'secondary' => '#2D4A6B', // Medium blue
        'accent' => '#C0C0C0' // Silver
    ],
    'fonts' => [
        'heading' => 'Cinzel',
        'body' => 'Lato'
    ],
    'page_primary_font' => 'Cinzel',
    'page_secondary_font' => 'Lato',
    'widget_primary_font' => 'Cinzel',
    'widget_secondary_font' => 'Lato',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#C0C0C0', // Silver glow
        'glow_width' => 21, // Large glow
        'glow_intensity' => 0.97, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #0F1B2E 0%, #1E3A5F 20%, #2D4A6B 35%, #1E3A5F 50%, #0F1B2E 65%, #1E3A5F 80%, #0F1B2E 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(30, 58, 95, 0.95) 0%, rgba(192, 192, 192, 0.18) 50%, rgba(45, 74, 107, 0.12) 100%)',
    'widget_border_color' => 'rgba(192, 192, 192, 0.75)',
    'color_tokens' => [
        'background' => [
            'frame' => '#0F1B2E',
            'base' => 'linear-gradient(135deg, #0F1B2E 0%, #1E3A5F 20%, #2D4A6B 35%, #1E3A5F 50%, #0F1B2E 65%, #1E3A5F 80%, #0F1B2E 100%)',
            'surface' => 'linear-gradient(145deg, rgba(30, 58, 95, 0.95) 0%, rgba(192, 192, 192, 0.18) 50%, rgba(45, 74, 107, 0.12) 100%)',
            'surface_translucent' => 'rgba(192, 192, 192, 0.28)',
            'surface_raised' => 'linear-gradient(160deg, rgba(30, 58, 95, 0.98) 0%, rgba(192, 192, 192, 0.22) 100%)',
            'overlay' => 'rgba(15, 27, 46, 0.9)'
        ],
        'text' => [
            'primary' => '#E8E8E8', // Light gray
            'secondary' => '#C0C0C0', // Silver
            'inverse' => '#0F1B2E'
        ],
        'border' => [
            'default' => 'rgba(192, 192, 192, 0.75)',
            'focus' => '#9B59B6' // Purple focus
        ],
        'accent' => [
            'primary' => '#C0C0C0', // Silver
            'muted' => 'rgba(192, 192, 192, 0.38)',
            'alt' => '#9B59B6', // Purple
            'highlight' => '#E8E8E8' // Light gray
        ],
        'stroke' => [
            'subtle' => 'rgba(192, 192, 192, 0.55)'
        ],
        'state' => [
            'success' => '#52C9A2', // Teal
            'warning' => '#F39C12', // Orange
            'danger' => '#E74C3C' // Red
        ],
        'text_state' => [
            'success' => '#1A5C47',
            'warning' => '#7D4A00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(15, 27, 46, 0.4)',
            'focus' => 'rgba(155, 89, 182, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #0F1B2E 0%, #1E3A5F 20%, #2D4A6B 35%, #1E3A5F 50%, #0F1B2E 65%, #1E3A5F 80%, #0F1B2E 100%)',
            'accent' => 'linear-gradient(135deg, #C0C0C0 0%, #9B59B6 50%, #1E3A5F 100%)',
            'widget' => 'linear-gradient(145deg, rgba(30, 58, 95, 0.98) 0%, rgba(192, 192, 192, 0.22) 50%, rgba(45, 74, 107, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #0F1B2E 0%, #C0C0C0 50%, #1E3A5F 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(192, 192, 192, 0.8)',
            'secondary' => 'rgba(155, 89, 182, 0.7)',
            'accent' => 'rgba(232, 232, 232, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Cinzel',
            'body' => 'Lato',
            'widget_heading' => 'Cinzel',
            'widget_body' => 'Lato',
            'metatext' => 'Lato'
        ],
        'color' => [
            'heading' => '#E8E8E8', // Light gray
            'body' => '#C0C0C0', // Silver
            'widget_heading' => '#E8E8E8',
            'widget_body' => '#C0C0C0'
        ],
        'effect' => [
            'border' => [
                'color' => '#9B59B6', // Purple border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#0F1B2E', // Dark blue shadow
                'intensity' => 0.7,
                'depth' => 7, // Deep shadow
                'blur' => 16 // Wide blur
            ],
            'glow' => [
                'color' => '#C0C0C0', // Silver glow
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
            'level_1' => '0 16px 44px rgba(15, 27, 46, 0.4), 0 0 32px rgba(192, 192, 192, 0.5)',
            'level_2' => '0 24px 68px rgba(15, 27, 46, 0.5), 0 0 48px rgba(192, 192, 192, 0.6)',
            'focus' => '0 0 0 4px rgba(155, 89, 182, 0.8), 0 0 32px rgba(155, 89, 182, 0.6)'
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

echo "\n🚀 Moonlight theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Deep blues, purples, and silvers with moonlit tones\n";
echo "   - Elegant typography (Cinzel + Lato)\n";
echo "   - Page title: 3px purple border + deep shadow + 21px silver glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced silver glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (dark blue tones)\n";
echo "   - Mysterious, nocturnal aesthetic\n";
echo "\n";

