<?php
/**
 * Add Vodka Cran Theme
 * 
 * A vibrant cocktail-inspired theme
 * Features:
 * - Bright reds, pinks, and whites with cocktail colors
 * - Extensive use of BOTH shadow AND glow effects
 * - Playful typography
 * - Multiple gradient backgrounds
 * - Energetic, festive aesthetic
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🍹 Vodka Cran Theme Installer\n";
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
    'name' => 'Vodka Cran',
    'legacy_colors' => [
        'primary' => '#DC143C', // Crimson
        'secondary' => '#FF69B4', // Hot pink
        'accent' => '#FFFFFF' // White
    ],
    'fonts' => [
        'heading' => 'Montserrat',
        'body' => 'Nunito'
    ],
    'page_primary_font' => 'Montserrat',
    'page_secondary_font' => 'Nunito',
    'widget_primary_font' => 'Montserrat',
    'widget_secondary_font' => 'Nunito',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#FF69B4', // Hot pink glow
        'glow_width' => 22, // Large glow
        'glow_intensity' => 0.98, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFFFFF 0%, #FFE4E1 15%, #FF69B4 30%, #DC143C 45%, #FF69B4 60%, #FFE4E1 75%, #FFFFFF 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 105, 180, 0.2) 50%, rgba(220, 20, 60, 0.15) 100%)',
    'widget_border_color' => 'rgba(255, 105, 180, 0.85)',
    'color_tokens' => [
        'background' => [
            'frame' => '#FFE4E1',
            'base' => 'linear-gradient(135deg, #FFFFFF 0%, #FFE4E1 15%, #FF69B4 30%, #DC143C 45%, #FF69B4 60%, #FFE4E1 75%, #FFFFFF 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 105, 180, 0.2) 50%, rgba(220, 20, 60, 0.15) 100%)',
            'surface_translucent' => 'rgba(255, 105, 180, 0.3)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 105, 180, 0.25) 100%)',
            'overlay' => 'rgba(255, 228, 225, 0.88)'
        ],
        'text' => [
            'primary' => '#8B0000', // Dark red
            'secondary' => '#B22222', // Fire brick
            'inverse' => '#FFFFFF'
        ],
        'border' => [
            'default' => 'rgba(255, 105, 180, 0.85)',
            'focus' => '#DC143C' // Crimson focus
        ],
        'accent' => [
            'primary' => '#FF69B4', // Hot pink
            'muted' => 'rgba(255, 105, 180, 0.4)',
            'alt' => '#DC143C', // Crimson
            'highlight' => '#FFFFFF' // White
        ],
        'stroke' => [
            'subtle' => 'rgba(255, 105, 180, 0.6)'
        ],
        'state' => [
            'success' => '#32CD32', // Lime green
            'warning' => '#FFD700', // Gold
            'danger' => '#FF0000' // Red
        ],
        'text_state' => [
            'success' => '#006400',
            'warning' => '#8B6914',
            'danger' => '#8B0000'
        ],
        'shadow' => [
            'ambient' => 'rgba(139, 0, 0, 0.3)',
            'focus' => 'rgba(220, 20, 60, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFFFFF 0%, #FFE4E1 15%, #FF69B4 30%, #DC143C 45%, #FF69B4 60%, #FFE4E1 75%, #FFFFFF 100%)',
            'accent' => 'linear-gradient(135deg, #FF69B4 0%, #DC143C 50%, #FFFFFF 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 105, 180, 0.25) 50%, rgba(220, 20, 60, 0.2) 100%)',
            'podcast' => 'linear-gradient(180deg, #FFE4E1 0%, #FF69B4 50%, #DC143C 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(255, 105, 180, 0.85)',
            'secondary' => 'rgba(220, 20, 60, 0.75)',
            'accent' => 'rgba(255, 255, 255, 0.65)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Montserrat',
            'body' => 'Nunito',
            'widget_heading' => 'Montserrat',
            'widget_body' => 'Nunito',
            'metatext' => 'Nunito'
        ],
        'color' => [
            'heading' => '#8B0000', // Dark red
            'body' => '#B22222', // Fire brick
            'widget_heading' => '#8B0000',
            'widget_body' => '#B22222'
        ],
        'effect' => [
            'border' => [
                'color' => '#DC143C', // Crimson border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#8B0000', // Dark red shadow
                'intensity' => 0.68,
                'depth' => 6, // Deep shadow
                'blur' => 15 // Wide blur
            ],
            'glow' => [
                'color' => '#FF69B4', // Hot pink glow
                'width' => 22 // 22px glow width
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
            'level_1' => '0 15px 42px rgba(139, 0, 0, 0.3), 0 0 30px rgba(255, 105, 180, 0.5)',
            'level_2' => '0 23px 66px rgba(139, 0, 0, 0.4), 0 0 46px rgba(255, 105, 180, 0.6)',
            'focus' => '0 0 0 4px rgba(220, 20, 60, 0.8), 0 0 30px rgba(220, 20, 60, 0.6)'
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

echo "\n🚀 Vodka Cran theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Bright reds, pinks, and whites with cocktail colors\n";
echo "   - Playful typography (Montserrat + Nunito)\n";
echo "   - Page title: 3px crimson border + deep shadow + 22px hot pink glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced hot pink glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (red/pink/white tones)\n";
echo "   - Energetic, festive aesthetic\n";
echo "\n";

