<?php
/**
 * Add Bourbon Street Theme
 * 
 * A vibrant, energetic theme inspired by New Orleans nightlife
 * Features:
 * - Mardi Gras colors (purple, green, gold) with vibrant accents
 * - Extensive use of BOTH shadow AND glow effects
 * - Bold, festive typography
 * - Multiple gradient backgrounds
 * - Neon-like effects throughout
 * - Rich, saturated colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎺 Bourbon Street Theme Installer\n";
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
    'name' => 'Bourbon Street',
    'legacy_colors' => [
        'primary' => '#9B59B6', // Purple
        'secondary' => '#F1C40F', // Gold
        'accent' => '#2ECC71' // Green
    ],
    'fonts' => [
        'heading' => 'Bebas Neue',
        'body' => 'Montserrat'
    ],
    'page_primary_font' => 'Bebas Neue',
    'page_secondary_font' => 'Montserrat',
    'widget_primary_font' => 'Bebas Neue',
    'widget_secondary_font' => 'Montserrat',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#9B59B6', // Purple glow
        'glow_width' => 18, // Large glow
        'glow_intensity' => 0.9, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #2C1810 0%, #6A1B9A 20%, #F1C40F 40%, #2ECC71 60%, #E74C3C 80%, #2C1810 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(155, 89, 182, 0.2) 0%, rgba(241, 196, 15, 0.18) 50%, rgba(46, 204, 113, 0.15) 100%)',
    'widget_border_color' => 'rgba(155, 89, 182, 0.9)',
    'color_tokens' => [
        'background' => [
            'frame' => '#1A0A00',
            'base' => 'linear-gradient(135deg, #2C1810 0%, #6A1B9A 20%, #F1C40F 40%, #2ECC71 60%, #E74C3C 80%, #2C1810 100%)',
            'surface' => 'linear-gradient(145deg, rgba(155, 89, 182, 0.2) 0%, rgba(241, 196, 15, 0.18) 50%, rgba(46, 204, 113, 0.15) 100%)',
            'surface_translucent' => 'rgba(155, 89, 182, 0.3)',
            'surface_raised' => 'linear-gradient(160deg, rgba(155, 89, 182, 0.25) 0%, rgba(241, 196, 15, 0.22) 100%)',
            'overlay' => 'rgba(26, 10, 0, 0.85)'
        ],
        'text' => [
            'primary' => '#FFFFFF', // White for contrast
            'secondary' => '#F8F9FA', // Off-white
            'inverse' => '#1A0A00' // Dark for inverse
        ],
        'border' => [
            'default' => 'rgba(155, 89, 182, 0.9)',
            'focus' => '#F1C40F' // Gold focus
        ],
        'accent' => [
            'primary' => '#9B59B6', // Purple
            'muted' => 'rgba(155, 89, 182, 0.4)',
            'alt' => '#F1C40F', // Gold
            'highlight' => '#2ECC71' // Green
        ],
        'stroke' => [
            'subtle' => 'rgba(155, 89, 182, 0.6)'
        ],
        'state' => [
            'success' => '#2ECC71', // Green
            'warning' => '#F1C40F', // Gold
            'danger' => '#E74C3C' // Red
        ],
        'text_state' => [
            'success' => '#0A3D1A',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(26, 10, 0, 0.5)',
            'focus' => 'rgba(241, 196, 15, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #2C1810 0%, #6A1B9A 20%, #F1C40F 40%, #2ECC71 60%, #E74C3C 80%, #2C1810 100%)',
            'accent' => 'linear-gradient(135deg, #9B59B6 0%, #F1C40F 50%, #2ECC71 100%)',
            'widget' => 'linear-gradient(145deg, rgba(155, 89, 182, 0.25) 0%, rgba(241, 196, 15, 0.22) 50%, rgba(46, 204, 113, 0.2) 100%)',
            'podcast' => 'linear-gradient(180deg, #1A0A00 0%, #6A1B9A 50%, #F1C40F 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(155, 89, 182, 0.8)',
            'secondary' => 'rgba(241, 196, 15, 0.7)',
            'accent' => 'rgba(46, 204, 113, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Bebas Neue',
            'body' => 'Montserrat',
            'widget_heading' => 'Bebas Neue',
            'widget_body' => 'Montserrat',
            'metatext' => 'Montserrat'
        ],
        'color' => [
            'heading' => '#FFFFFF', // White
            'body' => '#F8F9FA', // Off-white
            'widget_heading' => '#FFFFFF',
            'widget_body' => '#F8F9FA'
        ],
        'effect' => [
            'border' => [
                'color' => '#F1C40F', // Gold border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#1A0A00', // Dark shadow
                'intensity' => 0.8,
                'depth' => 6, // Deep shadow
                'blur' => 12 // Wide blur
            ],
            'glow' => [
                'color' => '#9B59B6', // Purple glow
                'width' => 16 // 16px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.0,
            'lg' => 2.2,
            'md' => 1.5,
            'sm' => 1.2,
            'xs' => 1.05
        ],
        'line_height' => [
            'tight' => 1.2,
            'normal' => 1.5,
            'relaxed' => 1.7
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 600,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '28px',
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
            'level_1' => '0 12px 36px rgba(26, 10, 0, 0.5), 0 0 24px rgba(155, 89, 182, 0.4)',
            'level_2' => '0 20px 60px rgba(26, 10, 0, 0.6), 0 0 40px rgba(155, 89, 182, 0.5)',
            'focus' => '0 0 0 4px rgba(241, 196, 15, 0.7), 0 0 24px rgba(241, 196, 15, 0.5)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '90ms',
            'fast' => '130ms',
            'standard' => '220ms'
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

echo "\n🚀 Bourbon Street theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Mardi Gras colors (purple, gold, green) with vibrant gradients\n";
echo "   - Bold typography (Bebas Neue + Montserrat)\n";
echo "   - Page title: 3px gold border + dark shadow + 16px purple glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced purple glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_bourbon_street_profile_settings.php\n";

