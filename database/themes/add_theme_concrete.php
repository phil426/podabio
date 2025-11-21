<?php
/**
 * Add Concrete Theme
 * 
 * An industrial, urban theme inspired by concrete and cityscapes
 * Features:
 * - Industrial colors (grays, concrete tones, steel, urban accents)
 * - Extensive use of BOTH shadow AND glow effects
 * - Bold, modern typography
 * - Multiple gradient backgrounds
 * - Rich, textured tones with depth
 * - Urban and industrial-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🏗️ Concrete Theme Installer\n";
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
    'name' => 'Concrete',
    'legacy_colors' => [
        'primary' => '#708090', // Slate gray
        'secondary' => '#2F4F4F', // Dark slate gray
        'accent' => '#FF6347' // Tomato (urban accent)
    ],
    'fonts' => [
        'heading' => 'Roboto',
        'body' => 'Open Sans'
    ],
    'page_primary_font' => 'Roboto',
    'page_secondary_font' => 'Open Sans',
    'widget_primary_font' => 'Roboto',
    'widget_secondary_font' => 'Open Sans',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#708090', // Slate gray glow
        'glow_width' => 19, // Large glow
        'glow_intensity' => 0.91, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #2F4F4F 0%, #556B2F 15%, #708090 30%, #778899 45%, #696969 60%, #2F4F4F 75%, #1C1C1C 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(112, 128, 144, 0.19) 0%, rgba(47, 79, 79, 0.17) 50%, rgba(105, 105, 105, 0.15) 100%)',
    'widget_border_color' => 'rgba(112, 128, 144, 0.9)',
    'color_tokens' => [
        'background' => [
            'frame' => '#1C1C1C',
            'base' => 'linear-gradient(135deg, #2F4F4F 0%, #556B2F 15%, #708090 30%, #778899 45%, #696969 60%, #2F4F4F 75%, #1C1C1C 100%)',
            'surface' => 'linear-gradient(145deg, rgba(112, 128, 144, 0.19) 0%, rgba(47, 79, 79, 0.17) 50%, rgba(105, 105, 105, 0.15) 100%)',
            'surface_translucent' => 'rgba(112, 128, 144, 0.26)',
            'surface_raised' => 'linear-gradient(160deg, rgba(112, 128, 144, 0.23) 0%, rgba(47, 79, 79, 0.21) 100%)',
            'overlay' => 'rgba(28, 28, 28, 0.88)'
        ],
        'text' => [
            'primary' => '#F5F5F5', // White smoke
            'secondary' => '#D3D3D3', // Light gray
            'inverse' => '#1C1C1C' // Dark for inverse
        ],
        'border' => [
            'default' => 'rgba(112, 128, 144, 0.9)',
            'focus' => '#FF6347' // Tomato focus
        ],
        'accent' => [
            'primary' => '#708090', // Slate gray
            'muted' => 'rgba(112, 128, 144, 0.4)',
            'alt' => '#FF6347', // Tomato
            'highlight' => '#778899' // Light slate gray
        ],
        'stroke' => [
            'subtle' => 'rgba(112, 128, 144, 0.6)'
        ],
        'state' => [
            'success' => '#32CD32', // Lime green
            'warning' => '#FFD700', // Gold
            'danger' => '#FF6347' // Tomato
        ],
        'text_state' => [
            'success' => '#0A3D0A',
            'warning' => '#6B5B00',
            'danger' => '#7A0F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(28, 28, 28, 0.55)',
            'focus' => 'rgba(255, 99, 71, 0.65)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #2F4F4F 0%, #556B2F 15%, #708090 30%, #778899 45%, #696969 60%, #2F4F4F 75%, #1C1C1C 100%)',
            'accent' => 'linear-gradient(135deg, #708090 0%, #FF6347 50%, #778899 100%)',
            'widget' => 'linear-gradient(145deg, rgba(112, 128, 144, 0.23) 0%, rgba(47, 79, 79, 0.21) 50%, rgba(105, 105, 105, 0.19) 100%)',
            'podcast' => 'linear-gradient(180deg, #1C1C1C 0%, #708090 50%, #2F4F4F 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(112, 128, 144, 0.82)',
            'secondary' => 'rgba(255, 99, 71, 0.7)',
            'accent' => 'rgba(119, 136, 153, 0.6)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Roboto',
            'body' => 'Open Sans',
            'widget_heading' => 'Roboto',
            'widget_body' => 'Open Sans',
            'metatext' => 'Open Sans'
        ],
        'color' => [
            'heading' => '#F5F5F5', // White smoke
            'body' => '#D3D3D3', // Light gray
            'widget_heading' => '#F5F5F5',
            'widget_body' => '#D3D3D3'
        ],
        'effect' => [
            'border' => [
                'color' => '#FF6347', // Tomato border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#1C1C1C', // Very dark shadow
                'intensity' => 0.85,
                'depth' => 7, // Very deep shadow
                'blur' => 15 // Wide blur
            ],
            'glow' => [
                'color' => '#708090', // Slate gray glow
                'width' => 17 // 17px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.1,
            'lg' => 2.3,
            'md' => 1.6,
            'sm' => 1.25,
            'xs' => 1.1
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
        'vertical_spacing' => '30px',
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
            'level_1' => '0 14px 42px rgba(28, 28, 28, 0.55), 0 0 28px rgba(112, 128, 144, 0.45)',
            'level_2' => '0 24px 72px rgba(28, 28, 28, 0.65), 0 0 48px rgba(112, 128, 144, 0.55)',
            'focus' => '0 0 0 4px rgba(255, 99, 71, 0.75), 0 0 28px rgba(255, 99, 71, 0.55)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '95ms',
            'fast' => '140ms',
            'standard' => '260ms'
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

echo "\n🚀 Concrete theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Industrial colors (slate gray, dark gray, concrete tones, tomato accent) with urban gradients\n";
echo "   - Modern typography (Roboto + Open Sans)\n";
echo "   - Page title: 3px tomato border + very deep shadow + 17px slate gray glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced gray glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (concrete, steel, urban tones)\n";
echo "   - Bold, industrial palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_concrete_profile_settings.php\n";

