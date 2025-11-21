<?php
/**
 * Add New Linen Theme
 * 
 * A creamy, simple theme inspired by fresh linen
 * Features:
 * - Soft cream and linen colors
 * - Minimal, elegant effects
 * - Clean, refined typography
 * - Simple gradient backgrounds
 * - Sophisticated, understated design
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🛏️ New Linen Theme Installer\n";
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
    'name' => 'New Linen',
    'legacy_colors' => [
        'primary' => '#F5F5DC', // Beige
        'secondary' => '#FAF0E6', // Linen
        'accent' => '#D2B48C' // Tan
    ],
    'fonts' => [
        'heading' => 'Cormorant Garamond',
        'body' => 'Lato'
    ],
    'page_primary_font' => 'Cormorant Garamond',
    'page_secondary_font' => 'Lato',
    'widget_primary_font' => 'Cormorant Garamond',
    'widget_secondary_font' => 'Lato',
    'widget_styles' => [
        'border_width' => 'regular', // 2px - subtle
        'border_effect' => 'shadow', // Use shadow, not glow
        'border_shadow_intensity' => 'subtle', // Subtle shadow
        'border_glow_intensity' => 'none', // No glow
        'glow_color' => '#D2B48C', // Not used
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(180deg, #FAF0E6 0%, #F5F5DC 25%, #FFF8DC 50%, #F5F5DC 75%, #FAF0E6 100%)',
    'widget_background' => 'rgba(255, 255, 255, 0.9)',
    'widget_border_color' => 'rgba(210, 180, 140, 0.25)',
    'color_tokens' => [
        'background' => [
            'frame' => '#FAF0E6',
            'base' => 'linear-gradient(180deg, #FAF0E6 0%, #F5F5DC 25%, #FFF8DC 50%, #F5F5DC 75%, #FAF0E6 100%)',
            'surface' => 'rgba(255, 255, 255, 0.9)',
            'surface_translucent' => 'rgba(210, 180, 140, 0.06)',
            'surface_raised' => 'rgba(255, 255, 255, 0.98)',
            'overlay' => 'rgba(250, 240, 230, 0.75)'
        ],
        'text' => [
            'primary' => '#4A4A4A', // Dark gray
            'secondary' => '#6B6B6B', // Medium gray
            'inverse' => '#FAF0E6' // Linen for inverse
        ],
        'border' => [
            'default' => 'rgba(210, 180, 140, 0.25)',
            'focus' => '#D2B48C' // Tan focus
        ],
        'accent' => [
            'primary' => '#D2B48C', // Tan
            'muted' => 'rgba(210, 180, 140, 0.15)',
            'alt' => '#BC9A6A', // Darker tan
            'highlight' => '#F5F5DC' // Beige
        ],
        'stroke' => [
            'subtle' => 'rgba(210, 180, 140, 0.2)'
        ],
        'state' => [
            'success' => '#8FBC8F', // Dark sea green
            'warning' => '#DAA520', // Goldenrod
            'danger' => '#CD5C5C' // Indian red
        ],
        'text_state' => [
            'success' => '#2F4F2F',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(74, 74, 74, 0.1)',
            'focus' => 'rgba(210, 180, 140, 0.25)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(180deg, #FAF0E6 0%, #F5F5DC 25%, #FFF8DC 50%, #F5F5DC 75%, #FAF0E6 100%)',
            'accent' => 'linear-gradient(135deg, #D2B48C 0%, #BC9A6A 100%)',
            'widget' => 'linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.98) 100%)',
            'podcast' => 'linear-gradient(180deg, #FAF0E6 0%, #F5F5DC 50%, #D2B48C 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(210, 180, 140, 0.15)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Cormorant Garamond',
            'body' => 'Lato',
            'widget_heading' => 'Cormorant Garamond',
            'widget_body' => 'Lato',
            'metatext' => 'Lato'
        ],
        'color' => [
            'heading' => '#4A4A4A', // Dark gray
            'body' => '#6B6B6B', // Medium gray
            'widget_heading' => '#4A4A4A',
            'widget_body' => '#6B6B6B'
        ],
        'effect' => [
            'border' => [
                'color' => '#D2B48C', // Tan border
                'width' => 1 // 1px border - very subtle
            ],
            'shadow' => [
                'color' => '#4A4A4A', // Dark gray shadow
                'intensity' => 0.25,
                'depth' => 2, // Subtle depth
                'blur' => 3 // Soft blur
            ]
        ],
        'scale' => [
            'xl' => 2.7,
            'lg' => 2.0,
            'md' => 1.4,
            'sm' => 1.15,
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
            'lg' => '1.5rem',
            'pill' => '9999px'
        ],
        'border_width' => [
            'hairline' => '1px',
            'regular' => '2px',
            'bold' => '3px'
        ],
        'shadow' => [
            'level_1' => '0 3px 10px rgba(74, 74, 74, 0.1)',
            'level_2' => '0 6px 20px rgba(74, 74, 74, 0.15)',
            'focus' => '0 0 0 3px rgba(210, 180, 140, 0.25)'
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

echo "\n🚀 New Linen theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Creamy, simple color palette (linen, beige, tan)\n";
echo "   - Elegant typography (Cormorant Garamond + Lato)\n";
echo "   - Page title: 1px tan border + subtle shadow (no glow)\n";
echo "   - Widgets: 2px borders with subtle shadow only\n";
echo "   - Profile image: Shadow effect (apply via helper script)\n";
echo "   - Minimal, sophisticated design\n";
echo "   - Clean gradient backgrounds (linen, beige, cream tones)\n";
echo "\n";
echo "📝 To apply profile image shadow settings, run:\n";
echo "   php database/themes/apply_new_linen_profile_settings.php\n";

