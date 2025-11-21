<?php
/**
 * Add Scoop of Chocolate Scoop of Vanilla Theme
 * 
 * A rich, creamy theme inspired by ice cream
 * Features:
 * - Rich colors (chocolate brown, vanilla cream, caramel, cocoa)
 * - Extensive use of BOTH shadow AND glow effects
 * - Warm, indulgent typography
 * - Multiple gradient backgrounds
 * - Rich, decadent tones with depth
 * - Ice cream inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🍦 Scoop of Chocolate Scoop of Vanilla Theme Installer\n";
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
    'name' => 'Scoop of Chocolate Scoop of Vanilla',
    'legacy_colors' => [
        'primary' => '#3D2817', // Dark chocolate
        'secondary' => '#FFF8DC', // Cornsilk (vanilla)
        'accent' => '#D2691E' // Chocolate
    ],
    'fonts' => [
        'heading' => 'Cookie',
        'body' => 'Nunito'
    ],
    'page_primary_font' => 'Cookie',
    'page_secondary_font' => 'Nunito',
    'widget_primary_font' => 'Cookie',
    'widget_secondary_font' => 'Nunito',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#8B4513', // Saddle brown glow
        'glow_width' => 20, // Large glow
        'glow_intensity' => 0.9, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFF8DC 0%, #FFEBCD 20%, #DEB887 35%, #8B4513 50%, #3D2817 65%, #8B4513 80%, #FFF8DC 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(139, 69, 19, 0.18) 0%, rgba(210, 105, 30, 0.16) 50%, rgba(255, 248, 220, 0.14) 100%)',
    'widget_border_color' => 'rgba(139, 69, 19, 0.9)',
    'color_tokens' => [
        'background' => [
            'frame' => '#2C1810',
            'base' => 'linear-gradient(135deg, #FFF8DC 0%, #FFEBCD 20%, #DEB887 35%, #8B4513 50%, #3D2817 65%, #8B4513 80%, #FFF8DC 100%)',
            'surface' => 'linear-gradient(145deg, rgba(139, 69, 19, 0.18) 0%, rgba(210, 105, 30, 0.16) 50%, rgba(255, 248, 220, 0.14) 100%)',
            'surface_translucent' => 'rgba(139, 69, 19, 0.25)',
            'surface_raised' => 'linear-gradient(160deg, rgba(139, 69, 19, 0.22) 0%, rgba(210, 105, 30, 0.2) 100%)',
            'overlay' => 'rgba(44, 24, 16, 0.85)'
        ],
        'text' => [
            'primary' => '#3D2817', // Dark chocolate
            'secondary' => '#654321', // Dark brown
            'inverse' => '#FFF8DC' // Cornsilk for inverse
        ],
        'border' => [
            'default' => 'rgba(139, 69, 19, 0.9)',
            'focus' => '#D2691E' // Chocolate focus
        ],
        'accent' => [
            'primary' => '#8B4513', // Saddle brown
            'muted' => 'rgba(139, 69, 19, 0.4)',
            'alt' => '#D2691E', // Chocolate
            'highlight' => '#DEB887' // Burlywood
        ],
        'stroke' => [
            'subtle' => 'rgba(139, 69, 19, 0.6)'
        ],
        'state' => [
            'success' => '#8FBC8F', // Dark sea green
            'warning' => '#FFD700', // Gold
            'danger' => '#CD5C5C' // Indian red
        ],
        'text_state' => [
            'success' => '#2F4F2F',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(44, 24, 16, 0.5)',
            'focus' => 'rgba(210, 105, 30, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFF8DC 0%, #FFEBCD 20%, #DEB887 35%, #8B4513 50%, #3D2817 65%, #8B4513 80%, #FFF8DC 100%)',
            'accent' => 'linear-gradient(135deg, #8B4513 0%, #D2691E 50%, #DEB887 100%)',
            'widget' => 'linear-gradient(145deg, rgba(139, 69, 19, 0.22) 0%, rgba(210, 105, 30, 0.2) 50%, rgba(255, 248, 220, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #2C1810 0%, #8B4513 50%, #3D2817 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(139, 69, 19, 0.8)',
            'secondary' => 'rgba(210, 105, 30, 0.7)',
            'accent' => 'rgba(222, 184, 135, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Cookie',
            'body' => 'Nunito',
            'widget_heading' => 'Cookie',
            'widget_body' => 'Nunito',
            'metatext' => 'Nunito'
        ],
        'color' => [
            'heading' => '#3D2817', // Dark chocolate
            'body' => '#654321', // Dark brown
            'widget_heading' => '#3D2817',
            'widget_body' => '#654321'
        ],
        'effect' => [
            'border' => [
                'color' => '#D2691E', // Chocolate border
                'width' => 4 // 4px border
            ],
            'shadow' => [
                'color' => '#2C1810', // Very dark brown shadow
                'intensity' => 0.8,
                'depth' => 7, // Very deep shadow
                'blur' => 14 // Wide blur
            ],
            'glow' => [
                'color' => '#8B4513', // Saddle brown glow
                'width' => 18 // 18px glow width
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
        'vertical_spacing' => '36px',
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
            'md' => '1.5rem',
            'lg' => '2.25rem',
            'pill' => '9999px'
        ],
        'border_width' => [
            'hairline' => '1px',
            'regular' => '2px',
            'bold' => '3px'
        ],
        'shadow' => [
            'level_1' => '0 14px 42px rgba(44, 24, 16, 0.5), 0 0 28px rgba(139, 69, 19, 0.45)',
            'level_2' => '0 24px 72px rgba(44, 24, 16, 0.6), 0 0 48px rgba(139, 69, 19, 0.55)',
            'focus' => '0 0 0 4px rgba(210, 105, 30, 0.75), 0 0 28px rgba(210, 105, 30, 0.55)'
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

echo "\n🚀 Scoop of Chocolate Scoop of Vanilla theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Rich ice cream colors (chocolate brown, vanilla cream, caramel) with decadent gradients\n";
echo "   - Indulgent typography (Cookie + Nunito)\n";
echo "   - Page title: 4px chocolate border + very deep shadow + 18px saddle brown glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced brown glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (vanilla, caramel, chocolate tones)\n";
echo "   - Rich, decadent ice cream palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_scoop_chocolate_vanilla_profile_settings.php\n";

