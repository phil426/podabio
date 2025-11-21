<?php
/**
 * Add Chicago Deep Dish Theme
 * 
 * A rich, bold theme inspired by Chicago's famous deep dish pizza
 * Features:
 * - Warm, hearty colors (tomato red, cheese yellow, crust brown, basil green)
 * - Extensive use of BOTH shadow AND glow effects
 * - Bold, hearty typography
 * - Multiple gradient backgrounds
 * - Rich, saturated colors with depth
 * - Comfortable, generous spacing
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🍕 Chicago Deep Dish Theme Installer\n";
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
    'name' => 'Chicago Deep Dish',
    'legacy_colors' => [
        'primary' => '#C41E3A', // Tomato red
        'secondary' => '#FFD700', // Cheese gold
        'accent' => '#8B4513' // Crust brown
    ],
    'fonts' => [
        'heading' => 'Oswald',
        'body' => 'Roboto Slab'
    ],
    'page_primary_font' => 'Oswald',
    'page_secondary_font' => 'Roboto Slab',
    'widget_primary_font' => 'Oswald',
    'widget_secondary_font' => 'Roboto Slab',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#C41E3A', // Tomato red glow
        'glow_width' => 20, // Large glow
        'glow_intensity' => 0.95, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(180deg, #2C1810 0%, #8B4513 15%, #C41E3A 30%, #FF6347 45%, #FFD700 60%, #8B4513 75%, #2C1810 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(196, 30, 58, 0.18) 0%, rgba(255, 215, 0, 0.15) 50%, rgba(139, 69, 19, 0.12) 100%)',
    'widget_border_color' => 'rgba(196, 30, 58, 0.95)',
    'color_tokens' => [
        'background' => [
            'frame' => '#1A0F08',
            'base' => 'linear-gradient(180deg, #2C1810 0%, #8B4513 15%, #C41E3A 30%, #FF6347 45%, #FFD700 60%, #8B4513 75%, #2C1810 100%)',
            'surface' => 'linear-gradient(145deg, rgba(196, 30, 58, 0.18) 0%, rgba(255, 215, 0, 0.15) 50%, rgba(139, 69, 19, 0.12) 100%)',
            'surface_translucent' => 'rgba(196, 30, 58, 0.25)',
            'surface_raised' => 'linear-gradient(160deg, rgba(196, 30, 58, 0.22) 0%, rgba(255, 215, 0, 0.2) 100%)',
            'overlay' => 'rgba(26, 15, 8, 0.88)'
        ],
        'text' => [
            'primary' => '#FFFFFF', // White for contrast
            'secondary' => '#FFF8DC', // Cornsilk
            'inverse' => '#1A0F08' // Dark for inverse
        ],
        'border' => [
            'default' => 'rgba(196, 30, 58, 0.95)',
            'focus' => '#FFD700' // Gold focus
        ],
        'accent' => [
            'primary' => '#C41E3A', // Tomato red
            'muted' => 'rgba(196, 30, 58, 0.4)',
            'alt' => '#FFD700', // Cheese gold
            'highlight' => '#8B4513' // Crust brown
        ],
        'stroke' => [
            'subtle' => 'rgba(196, 30, 58, 0.6)'
        ],
        'state' => [
            'success' => '#228B22', // Forest green (basil)
            'warning' => '#FFD700', // Gold
            'danger' => '#DC143C' // Crimson
        ],
        'text_state' => [
            'success' => '#0A3D0A',
            'warning' => '#6B5B00',
            'danger' => '#7A0F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(26, 15, 8, 0.55)',
            'focus' => 'rgba(255, 215, 0, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(180deg, #2C1810 0%, #8B4513 15%, #C41E3A 30%, #FF6347 45%, #FFD700 60%, #8B4513 75%, #2C1810 100%)',
            'accent' => 'linear-gradient(135deg, #C41E3A 0%, #FFD700 50%, #8B4513 100%)',
            'widget' => 'linear-gradient(145deg, rgba(196, 30, 58, 0.22) 0%, rgba(255, 215, 0, 0.2) 50%, rgba(139, 69, 19, 0.18) 100%)',
            'podcast' => 'linear-gradient(180deg, #1A0F08 0%, #8B4513 50%, #C41E3A 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(196, 30, 58, 0.85)',
            'secondary' => 'rgba(255, 215, 0, 0.75)',
            'accent' => 'rgba(139, 69, 19, 0.65)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Oswald',
            'body' => 'Roboto Slab',
            'widget_heading' => 'Oswald',
            'widget_body' => 'Roboto Slab',
            'metatext' => 'Roboto Slab'
        ],
        'color' => [
            'heading' => '#FFFFFF', // White
            'body' => '#FFF8DC', // Cornsilk
            'widget_heading' => '#FFFFFF',
            'widget_body' => '#FFF8DC'
        ],
        'effect' => [
            'border' => [
                'color' => '#FFD700', // Gold border
                'width' => 4 // 4px border
            ],
            'shadow' => [
                'color' => '#1A0F08', // Dark shadow
                'intensity' => 0.85,
                'depth' => 7, // Very deep shadow
                'blur' => 14 // Wide blur
            ],
            'glow' => [
                'color' => '#C41E3A', // Tomato red glow
                'width' => 18 // 18px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.2,
            'lg' => 2.4,
            'md' => 1.6,
            'sm' => 1.25,
            'xs' => 1.1
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
            'level_1' => '0 14px 42px rgba(26, 15, 8, 0.55), 0 0 28px rgba(196, 30, 58, 0.45)',
            'level_2' => '0 24px 72px rgba(26, 15, 8, 0.65), 0 0 48px rgba(196, 30, 58, 0.55)',
            'focus' => '0 0 0 4px rgba(255, 215, 0, 0.75), 0 0 28px rgba(255, 215, 0, 0.55)'
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

echo "\n🚀 Chicago Deep Dish theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Rich pizza colors (tomato red, cheese gold, crust brown) with vibrant gradients\n";
echo "   - Bold typography (Oswald + Roboto Slab)\n";
echo "   - Page title: 4px gold border + very deep shadow + 18px tomato red glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced red glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (crust, tomato, cheese)\n";
echo "   - Generous, comfortable spacing\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_chicago_deep_dish_profile_settings.php\n";

