<?php
/**
 * Add South Pole Theme
 * 
 * A cool, icy theme inspired by Antarctica
 * Features:
 * - Cool colors (icy blues, whites, aurora greens/purples)
 * - Extensive use of BOTH shadow AND glow effects
 * - Clean, crisp typography
 * - Multiple gradient backgrounds
 * - Cool, frosty tones with aurora accents
 * - Polar and aurora-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🧊 South Pole Theme Installer\n";
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
    'name' => 'South Pole',
    'legacy_colors' => [
        'primary' => '#00CED1', // Dark turquoise
        'secondary' => '#B0E0E6', // Powder blue
        'accent' => '#7B68EE' // Medium slate blue
    ],
    'fonts' => [
        'heading' => 'Montserrat',
        'body' => 'Open Sans'
    ],
    'page_primary_font' => 'Montserrat',
    'page_secondary_font' => 'Open Sans',
    'widget_primary_font' => 'Montserrat',
    'widget_secondary_font' => 'Open Sans',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#00CED1', // Dark turquoise glow
        'glow_width' => 17, // Large glow
        'glow_intensity' => 0.88, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 20%, #4DD0E1 35%, #00BCD4 50%, #0097A7 65%, #006064 80%, #E0F7FA 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(0, 206, 209, 0.16) 0%, rgba(123, 104, 238, 0.14) 50%, rgba(176, 224, 230, 0.12) 100%)',
    'widget_border_color' => 'rgba(0, 206, 209, 0.85)',
    'color_tokens' => [
        'background' => [
            'frame' => '#004D40',
            'base' => 'linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 20%, #4DD0E1 35%, #00BCD4 50%, #0097A7 65%, #006064 80%, #E0F7FA 100%)',
            'surface' => 'linear-gradient(145deg, rgba(0, 206, 209, 0.16) 0%, rgba(123, 104, 238, 0.14) 50%, rgba(176, 224, 230, 0.12) 100%)',
            'surface_translucent' => 'rgba(0, 206, 209, 0.22)',
            'surface_raised' => 'linear-gradient(160deg, rgba(0, 206, 209, 0.2) 0%, rgba(123, 104, 238, 0.18) 100%)',
            'overlay' => 'rgba(0, 77, 64, 0.85)'
        ],
        'text' => [
            'primary' => '#004D40', // Dark teal
            'secondary' => '#00695C', // Medium teal
            'inverse' => '#E0F7FA' // Light cyan for inverse
        ],
        'border' => [
            'default' => 'rgba(0, 206, 209, 0.85)',
            'focus' => '#7B68EE' // Medium slate blue focus
        ],
        'accent' => [
            'primary' => '#00CED1', // Dark turquoise
            'muted' => 'rgba(0, 206, 209, 0.4)',
            'alt' => '#7B68EE', // Medium slate blue
            'highlight' => '#B0E0E6' // Powder blue
        ],
        'stroke' => [
            'subtle' => 'rgba(0, 206, 209, 0.5)'
        ],
        'state' => [
            'success' => '#00CED1', // Dark turquoise
            'warning' => '#FFD700', // Gold
            'danger' => '#FF6B6B' // Coral red
        ],
        'text_state' => [
            'success' => '#003D32',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(0, 77, 64, 0.4)',
            'focus' => 'rgba(123, 104, 238, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 20%, #4DD0E1 35%, #00BCD4 50%, #0097A7 65%, #006064 80%, #E0F7FA 100%)',
            'accent' => 'linear-gradient(135deg, #00CED1 0%, #7B68EE 50%, #B0E0E6 100%)',
            'widget' => 'linear-gradient(145deg, rgba(0, 206, 209, 0.2) 0%, rgba(123, 104, 238, 0.18) 50%, rgba(176, 224, 230, 0.16) 100%)',
            'podcast' => 'linear-gradient(180deg, #004D40 0%, #00BCD4 50%, #7B68EE 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(0, 206, 209, 0.75)',
            'secondary' => 'rgba(123, 104, 238, 0.65)',
            'accent' => 'rgba(176, 224, 230, 0.55)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Montserrat',
            'body' => 'Open Sans',
            'widget_heading' => 'Montserrat',
            'widget_body' => 'Open Sans',
            'metatext' => 'Open Sans'
        ],
        'color' => [
            'heading' => '#004D40', // Dark teal
            'body' => '#00695C', // Medium teal
            'widget_heading' => '#004D40',
            'widget_body' => '#00695C'
        ],
        'effect' => [
            'border' => [
                'color' => '#7B68EE', // Medium slate blue border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#004D40', // Dark teal shadow
                'intensity' => 0.7,
                'depth' => 5, // Deep shadow
                'blur' => 11 // Wide blur
            ],
            'glow' => [
                'color' => '#00CED1', // Dark turquoise glow
                'width' => 15 // 15px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.0,
            'lg' => 2.2,
            'md' => 1.55,
            'sm' => 1.22,
            'xs' => 1.08
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
            'level_1' => '0 12px 36px rgba(0, 77, 64, 0.4), 0 0 24px rgba(0, 206, 209, 0.4)',
            'level_2' => '0 20px 60px rgba(0, 77, 64, 0.5), 0 0 40px rgba(0, 206, 209, 0.5)',
            'focus' => '0 0 0 4px rgba(123, 104, 238, 0.7), 0 0 24px rgba(123, 104, 238, 0.5)'
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

echo "\n🚀 South Pole theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Cool polar colors (icy blues, turquoise, aurora purple) with frosty gradients\n";
echo "   - Clean typography (Montserrat + Open Sans)\n";
echo "   - Page title: 3px aurora purple border + dark teal shadow + 15px turquoise glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced turquoise glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (ice, turquoise, aurora colors)\n";
echo "   - Cool, crisp polar palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_south_pole_profile_settings.php\n";

