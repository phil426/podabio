<?php
/**
 * Add Notebook Theme
 * 
 * A simple, elegant theme inspired by classic notebooks
 * Features:
 * - Soft paper colors (cream, off-white, light gray)
 * - Minimal, refined effects
 * - Clean, readable typography
 * - Simple gradient backgrounds
 * - Sophisticated, understated design
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "📓 Notebook Theme Installer\n";
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
    'name' => 'Notebook',
    'legacy_colors' => [
        'primary' => '#2C2C2C', // Charcoal
        'secondary' => '#F8F8F8', // Off-white
        'accent' => '#808080' // Gray
    ],
    'fonts' => [
        'heading' => 'Merriweather',
        'body' => 'Source Sans Pro'
    ],
    'page_primary_font' => 'Merriweather',
    'page_secondary_font' => 'Source Sans Pro',
    'widget_primary_font' => 'Merriweather',
    'widget_secondary_font' => 'Source Sans Pro',
    'widget_styles' => [
        'border_width' => 'regular', // 2px - subtle
        'border_effect' => 'shadow', // Use shadow, not glow
        'border_shadow_intensity' => 'subtle', // Subtle shadow
        'border_glow_intensity' => 'none', // No glow
        'glow_color' => '#808080', // Not used
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(180deg, #FFFFFF 0%, #F8F8F8 25%, #F5F5F5 50%, #F8F8F8 75%, #FFFFFF 100%)',
    'widget_background' => 'rgba(255, 255, 255, 0.95)',
    'widget_border_color' => 'rgba(128, 128, 128, 0.2)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F5F5F5',
            'base' => 'linear-gradient(180deg, #FFFFFF 0%, #F8F8F8 25%, #F5F5F5 50%, #F8F8F8 75%, #FFFFFF 100%)',
            'surface' => 'rgba(255, 255, 255, 0.95)',
            'surface_translucent' => 'rgba(128, 128, 128, 0.05)',
            'surface_raised' => 'rgba(255, 255, 255, 0.98)',
            'overlay' => 'rgba(245, 245, 245, 0.75)'
        ],
        'text' => [
            'primary' => '#2C2C2C', // Charcoal
            'secondary' => '#555555', // Dark gray
            'inverse' => '#F8F8F8' // Off-white for inverse
        ],
        'border' => [
            'default' => 'rgba(128, 128, 128, 0.2)',
            'focus' => '#808080' // Gray focus
        ],
        'accent' => [
            'primary' => '#808080', // Gray
            'muted' => 'rgba(128, 128, 128, 0.15)',
            'alt' => '#2C2C2C', // Charcoal
            'highlight' => '#F8F8F8' // Off-white
        ],
        'stroke' => [
            'subtle' => 'rgba(128, 128, 128, 0.15)'
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
            'ambient' => 'rgba(44, 44, 44, 0.08)',
            'focus' => 'rgba(128, 128, 128, 0.2)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(180deg, #FFFFFF 0%, #F8F8F8 25%, #F5F5F5 50%, #F8F8F8 75%, #FFFFFF 100%)',
            'accent' => 'linear-gradient(135deg, #808080 0%, #2C2C2C 100%)',
            'widget' => 'linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 1) 100%)',
            'podcast' => 'linear-gradient(180deg, #F5F5F5 0%, #F8F8F8 50%, #FFFFFF 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(128, 128, 128, 0.1)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Merriweather',
            'body' => 'Source Sans Pro',
            'widget_heading' => 'Merriweather',
            'widget_body' => 'Source Sans Pro',
            'metatext' => 'Source Sans Pro'
        ],
        'color' => [
            'heading' => '#2C2C2C', // Charcoal
            'body' => '#555555', // Dark gray
            'widget_heading' => '#2C2C2C',
            'widget_body' => '#555555'
        ],
        'effect' => [
            'border' => [
                'color' => '#808080', // Gray border
                'width' => 1 // 1px border - very subtle
            ],
            'shadow' => [
                'color' => '#2C2C2C', // Charcoal shadow
                'intensity' => 0.2,
                'depth' => 1, // Very subtle depth
                'blur' => 2 // Soft blur
            ]
        ],
        'scale' => [
            'xl' => 2.6,
            'lg' => 1.9,
            'md' => 1.35,
            'sm' => 1.1,
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
            'level_1' => '0 2px 8px rgba(44, 44, 44, 0.08)',
            'level_2' => '0 4px 16px rgba(44, 44, 44, 0.12)',
            'focus' => '0 0 0 3px rgba(128, 128, 128, 0.2)'
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

echo "\n🚀 Notebook theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Simple, elegant color palette (white, off-white, gray, charcoal)\n";
echo "   - Clean typography (Merriweather + Source Sans Pro)\n";
echo "   - Page title: 1px gray border + very subtle shadow (no glow)\n";
echo "   - Widgets: 2px borders with subtle shadow only\n";
echo "   - Profile image: Shadow effect (apply via helper script)\n";
echo "   - Minimal, sophisticated design\n";
echo "   - Clean paper-like backgrounds (white, off-white tones)\n";
echo "\n";
echo "📝 To apply profile image shadow settings, run:\n";
echo "   php database/themes/apply_notebook_profile_settings.php\n";

