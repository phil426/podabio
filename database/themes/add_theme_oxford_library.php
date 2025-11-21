<?php
/**
 * Add Oxford Library Theme
 * 
 * A sophisticated, academic theme inspired by classic libraries
 * Features:
 * - Rich serif typography throughout
 * - Extensive use of shadow effects (no glows)
 * - Warm, elegant color palette (browns, golds, deep reds, cream)
 * - Classic, refined styling
 * - Multiple shadow layers for depth
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "📚 Oxford Library Theme Installer\n";
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
    'name' => 'Oxford Library',
    'legacy_colors' => [
        'primary' => '#3E2723', // Dark brown
        'secondary' => '#D7CCC8', // Light beige
        'accent' => '#8B4513' // Saddle brown
    ],
    'fonts' => [
        'heading' => 'Playfair Display',
        'body' => 'Cormorant Garamond'
    ],
    'page_primary_font' => 'Playfair Display',
    'page_secondary_font' => 'Cormorant Garamond',
    'widget_primary_font' => 'Playfair Display',
    'widget_secondary_font' => 'Cormorant Garamond',
    'widget_styles' => [
        'border_width' => 'regular', // 2px
        'border_effect' => 'shadow', // Use shadow, not glow
        'border_shadow_intensity' => 'pronounced', // Strong shadow
        'border_glow_intensity' => 'none', // No glow
        'glow_color' => '#8B4513', // Not used, but set for compatibility
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(180deg, #F5F1EB 0%, #E8DFD5 25%, #DDD5CC 50%, #D7CCC8 75%, #F5F1EB 100%)',
    'widget_background' => 'rgba(255, 250, 240, 0.95)',
    'widget_border_color' => 'rgba(62, 39, 35, 0.4)',
    'color_tokens' => [
        'background' => [
            'frame' => '#3E2723',
            'base' => 'linear-gradient(180deg, #F5F1EB 0%, #E8DFD5 25%, #DDD5CC 50%, #D7CCC8 75%, #F5F1EB 100%)',
            'surface' => 'rgba(255, 250, 240, 0.95)',
            'surface_translucent' => 'rgba(139, 69, 19, 0.12)',
            'surface_raised' => 'rgba(255, 255, 255, 0.98)',
            'overlay' => 'rgba(62, 39, 35, 0.75)'
        ],
        'text' => [
            'primary' => '#3E2723', // Dark brown
            'secondary' => '#5D4037', // Medium brown
            'inverse' => '#F5F1EB' // Cream
        ],
        'border' => [
            'default' => 'rgba(62, 39, 35, 0.4)',
            'focus' => '#8B4513' // Saddle brown
        ],
        'accent' => [
            'primary' => '#8B4513', // Saddle brown
            'muted' => 'rgba(139, 69, 19, 0.2)',
            'alt' => '#A0522D', // Sienna
            'highlight' => '#CD853F' // Peru
        ],
        'stroke' => [
            'subtle' => 'rgba(62, 39, 35, 0.3)'
        ],
        'state' => [
            'success' => '#6B8E23', // Olive drab
            'warning' => '#DAA520', // Goldenrod
            'danger' => '#8B4513' // Saddle brown
        ],
        'text_state' => [
            'success' => '#2F4F2F',
            'warning' => '#6B5B00',
            'danger' => '#4A2C1A'
        ],
        'shadow' => [
            'ambient' => 'rgba(62, 39, 35, 0.35)',
            'focus' => 'rgba(139, 69, 19, 0.4)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(180deg, #F5F1EB 0%, #E8DFD5 25%, #DDD5CC 50%, #D7CCC8 75%, #F5F1EB 100%)',
            'accent' => 'linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #CD853F 100%)',
            'widget' => 'linear-gradient(180deg, rgba(255, 250, 240, 0.95) 0%, rgba(255, 255, 255, 0.98) 100%)',
            'podcast' => 'linear-gradient(180deg, #3E2723 0%, #5D4037 50%, #8B4513 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(139, 69, 19, 0.3)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Playfair Display',
            'body' => 'Cormorant Garamond',
            'widget_heading' => 'Playfair Display',
            'widget_body' => 'Cormorant Garamond',
            'metatext' => 'Cormorant Garamond'
        ],
        'color' => [
            'heading' => '#3E2723', // Dark brown
            'body' => '#5D4037', // Medium brown
            'widget_heading' => '#3E2723',
            'widget_body' => '#5D4037'
        ],
        'effect' => [
            'border' => [
                'color' => '#8B4513', // Saddle brown border
                'width' => 2 // 2px border
            ],
            'shadow' => [
                'color' => '#3E2723', // Dark brown shadow
                'intensity' => 0.7,
                'depth' => 5, // Deeper shadow
                'blur' => 10 // More blur for softer shadow
            ]
        ],
        'scale' => [
            'xl' => 2.8,
            'lg' => 2.1,
            'md' => 1.5,
            'sm' => 1.2,
            'xs' => 1.0
        ],
        'line_height' => [
            'tight' => 1.3,
            'normal' => 1.65,
            'relaxed' => 1.85
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 500,
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
            'sm' => '0.5rem',
            'md' => '1rem',
            'lg' => '1.5rem',
            'pill' => '9999px'
        ],
        'border_width' => [
            'hairline' => '1px',
            'regular' => '2px',
            'bold' => '3px'
        ],
        'shadow' => [
            'level_1' => '0 8px 24px rgba(62, 39, 35, 0.25), 0 4px 12px rgba(62, 39, 35, 0.15)',
            'level_2' => '0 16px 48px rgba(62, 39, 35, 0.35), 0 8px 24px rgba(62, 39, 35, 0.25)',
            'focus' => '0 0 0 3px rgba(139, 69, 19, 0.5), 0 4px 12px rgba(139, 69, 19, 0.3)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '120ms',
            'fast' => '180ms',
            'standard' => '300ms'
        ],
        'easing' => [
            'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
            'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
        ],
        'focus' => [
            'ring_width' => '3px',
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

echo "\n🚀 Oxford Library theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Classic serif typography (Playfair Display + Cormorant Garamond)\n";
echo "   - Warm, elegant color palette (browns, golds, creams)\n";
echo "   - Page title: 2px brown border + deep shadow (5px depth, 10px blur)\n";
echo "   - Widgets: 2px borders with pronounced shadow effects\n";
echo "   - Profile image: Deep shadow effect (apply via helper script)\n";
echo "   - Extensive use of multi-layer shadows throughout\n";
echo "   - Comfortable spacing for readability\n";
echo "\n";
echo "📝 To apply profile image shadow settings, run:\n";
echo "   php database/themes/apply_oxford_library_profile_settings.php\n";

