<?php
/**
 * Add Aqaba Theme
 * 
 * A simple, elegant theme inspired by the Red Sea and desert
 * Features:
 * - Minimal color palette (sea blues, sandy beiges, coral accents)
 * - Subtle, refined effects
 * - Clean, elegant typography
 * - Simple gradient backgrounds
 * - Sophisticated, understated design
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🏖️ Aqaba Theme Installer\n";
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
    'name' => 'Aqaba',
    'legacy_colors' => [
        'primary' => '#4682B4', // Steel blue
        'secondary' => '#F5F5DC', // Beige
        'accent' => '#FF7F50' // Coral
    ],
    'fonts' => [
        'heading' => 'Playfair Display',
        'body' => 'Lato'
    ],
    'page_primary_font' => 'Playfair Display',
    'page_secondary_font' => 'Lato',
    'widget_primary_font' => 'Playfair Display',
    'widget_secondary_font' => 'Lato',
    'widget_styles' => [
        'border_width' => 'regular', // 2px - subtle
        'border_effect' => 'shadow', // Use shadow, not glow
        'border_shadow_intensity' => 'subtle', // Subtle shadow
        'border_glow_intensity' => 'none', // No glow
        'glow_color' => '#4682B4', // Not used
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(180deg, #F5F5DC 0%, #E6E6FA 25%, #B0E0E6 50%, #E6E6FA 75%, #F5F5DC 100%)',
    'widget_background' => 'rgba(255, 255, 255, 0.85)',
    'widget_border_color' => 'rgba(70, 130, 180, 0.3)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F5F5DC',
            'base' => 'linear-gradient(180deg, #F5F5DC 0%, #E6E6FA 25%, #B0E0E6 50%, #E6E6FA 75%, #F5F5DC 100%)',
            'surface' => 'rgba(255, 255, 255, 0.85)',
            'surface_translucent' => 'rgba(70, 130, 180, 0.08)',
            'surface_raised' => 'rgba(255, 255, 255, 0.95)',
            'overlay' => 'rgba(245, 245, 220, 0.75)'
        ],
        'text' => [
            'primary' => '#2F4F4F', // Dark slate gray
            'secondary' => '#708090', // Slate gray
            'inverse' => '#F5F5DC' // Beige for inverse
        ],
        'border' => [
            'default' => 'rgba(70, 130, 180, 0.3)',
            'focus' => '#FF7F50' // Coral focus
        ],
        'accent' => [
            'primary' => '#4682B4', // Steel blue
            'muted' => 'rgba(70, 130, 180, 0.2)',
            'alt' => '#FF7F50', // Coral
            'highlight' => '#B0E0E6' // Powder blue
        ],
        'stroke' => [
            'subtle' => 'rgba(70, 130, 180, 0.25)'
        ],
        'state' => [
            'success' => '#20B2AA', // Light sea green
            'warning' => '#FFA500', // Orange
            'danger' => '#DC143C' // Crimson
        ],
        'text_state' => [
            'success' => '#0A3D3A',
            'warning' => '#6B5B00',
            'danger' => '#7A0F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(47, 79, 79, 0.15)',
            'focus' => 'rgba(255, 127, 80, 0.3)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(180deg, #F5F5DC 0%, #E6E6FA 25%, #B0E0E6 50%, #E6E6FA 75%, #F5F5DC 100%)',
            'accent' => 'linear-gradient(135deg, #4682B4 0%, #FF7F50 100%)',
            'widget' => 'linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.95) 100%)',
            'podcast' => 'linear-gradient(180deg, #F5F5DC 0%, #B0E0E6 50%, #4682B4 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(70, 130, 180, 0.2)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Playfair Display',
            'body' => 'Lato',
            'widget_heading' => 'Playfair Display',
            'widget_body' => 'Lato',
            'metatext' => 'Lato'
        ],
        'color' => [
            'heading' => '#2F4F4F', // Dark slate gray
            'body' => '#708090', // Slate gray
            'widget_heading' => '#2F4F4F',
            'widget_body' => '#708090'
        ],
        'effect' => [
            'border' => [
                'color' => '#4682B4', // Steel blue border
                'width' => 1 // 1px border - very subtle
            ],
            'shadow' => [
                'color' => '#2F4F4F', // Dark slate gray shadow
                'intensity' => 0.3,
                'depth' => 2, // Subtle depth
                'blur' => 4 // Soft blur
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
            'normal' => 1.6,
            'relaxed' => 1.8
        ],
        'weight' => [
            'normal' => 400,
            'medium' => 500,
            'bold' => 700
        ]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '32px',
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
            'level_1' => '0 4px 12px rgba(47, 79, 79, 0.15)',
            'level_2' => '0 8px 24px rgba(47, 79, 79, 0.2)',
            'focus' => '0 0 0 3px rgba(255, 127, 80, 0.3)'
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

echo "\n🚀 Aqaba theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Simple, elegant color palette (steel blue, beige, coral)\n";
echo "   - Refined typography (Playfair Display + Lato)\n";
echo "   - Page title: 1px steel blue border + subtle shadow (no glow)\n";
echo "   - Widgets: 2px borders with subtle shadow only\n";
echo "   - Profile image: Shadow effect (apply via helper script)\n";
echo "   - Minimal, sophisticated design\n";
echo "   - Clean gradient backgrounds (beige, lavender, powder blue)\n";
echo "\n";
echo "📝 To apply profile image shadow settings, run:\n";
echo "   php database/themes/apply_aqaba_profile_settings.php\n";

