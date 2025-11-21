<?php
/**
 * Add My Diary Theme
 * 
 * A personal, intimate theme inspired by diary pages and journal entries
 * Features:
 * - Soft, personal colors (pink, cream, lavender, rose, sepia tones)
 * - Extensive use of BOTH shadow AND glow effects
 * - Elegant, handwritten-style typography
 * - Multiple gradient backgrounds
 * - Warm, nostalgic tones with depth
 * - Diary and journal-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "📔 My Diary Theme Installer\n";
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
    'name' => 'My Diary',
    'legacy_colors' => [
        'primary' => '#FFB6C1', // Light pink
        'secondary' => '#FFF0F5', // Lavender blush
        'accent' => '#DDA0DD' // Plum
    ],
    'fonts' => [
        'heading' => 'Dancing Script',
        'body' => 'Crimson Text'
    ],
    'page_primary_font' => 'Dancing Script',
    'page_secondary_font' => 'Crimson Text',
    'widget_primary_font' => 'Dancing Script',
    'widget_secondary_font' => 'Crimson Text',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#FFB6C1', // Light pink glow
        'glow_width' => 18, // Large glow
        'glow_intensity' => 0.87, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFF0F5 0%, #FFE4E1 20%, #FFB6C1 35%, #FFC0CB 50%, #DDA0DD 65%, #E6E6FA 80%, #FFF0F5 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 182, 193, 0.17) 0%, rgba(221, 160, 221, 0.15) 50%, rgba(255, 240, 245, 0.13) 100%)',
    'widget_border_color' => 'rgba(255, 182, 193, 0.85)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F5F5DC',
            'base' => 'linear-gradient(135deg, #FFF0F5 0%, #FFE4E1 20%, #FFB6C1 35%, #FFC0CB 50%, #DDA0DD 65%, #E6E6FA 80%, #FFF0F5 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 182, 193, 0.17) 0%, rgba(221, 160, 221, 0.15) 50%, rgba(255, 240, 245, 0.13) 100%)',
            'surface_translucent' => 'rgba(255, 182, 193, 0.23)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 182, 193, 0.2) 0%, rgba(221, 160, 221, 0.18) 100%)',
            'overlay' => 'rgba(245, 245, 220, 0.85)'
        ],
        'text' => [
            'primary' => '#8B4789', // Dark magenta
            'secondary' => '#9370DB', // Medium purple
            'inverse' => '#FFF0F5' // Lavender blush for inverse
        ],
        'border' => [
            'default' => 'rgba(255, 182, 193, 0.85)',
            'focus' => '#DDA0DD' // Plum focus
        ],
        'accent' => [
            'primary' => '#FFB6C1', // Light pink
            'muted' => 'rgba(255, 182, 193, 0.4)',
            'alt' => '#DDA0DD', // Plum
            'highlight' => '#FFC0CB' // Pink
        ],
        'stroke' => [
            'subtle' => 'rgba(255, 182, 193, 0.55)'
        ],
        'state' => [
            'success' => '#98FB98', // Pale green
            'warning' => '#FFD700', // Gold
            'danger' => '#FF69B4' // Hot pink
        ],
        'text_state' => [
            'success' => '#2F4F2F',
            'warning' => '#6B5B00',
            'danger' => '#7A1F3D'
        ],
        'shadow' => [
            'ambient' => 'rgba(245, 245, 220, 0.45)',
            'focus' => 'rgba(221, 160, 221, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFF0F5 0%, #FFE4E1 20%, #FFB6C1 35%, #FFC0CB 50%, #DDA0DD 65%, #E6E6FA 80%, #FFF0F5 100%)',
            'accent' => 'linear-gradient(135deg, #FFB6C1 0%, #DDA0DD 50%, #E6E6FA 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 182, 193, 0.2) 0%, rgba(221, 160, 221, 0.18) 50%, rgba(255, 240, 245, 0.16) 100%)',
            'podcast' => 'linear-gradient(180deg, #F5F5DC 0%, #FFB6C1 50%, #DDA0DD 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(255, 182, 193, 0.7)',
            'secondary' => 'rgba(221, 160, 221, 0.6)',
            'accent' => 'rgba(255, 192, 203, 0.55)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Dancing Script',
            'body' => 'Crimson Text',
            'widget_heading' => 'Dancing Script',
            'widget_body' => 'Crimson Text',
            'metatext' => 'Crimson Text'
        ],
        'color' => [
            'heading' => '#8B4789', // Dark magenta
            'body' => '#9370DB', // Medium purple
            'widget_heading' => '#8B4789',
            'widget_body' => '#9370DB'
        ],
        'effect' => [
            'border' => [
                'color' => '#DDA0DD', // Plum border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#F5F5DC', // Beige shadow
                'intensity' => 0.7,
                'depth' => 5, // Deep shadow
                'blur' => 11 // Wide blur
            ],
            'glow' => [
                'color' => '#FFB6C1', // Light pink glow
                'width' => 16 // 16px glow width
            ]
        ],
        'scale' => [
            'xl' => 2.85,
            'lg' => 2.1,
            'md' => 1.5,
            'sm' => 1.2,
            'xs' => 1.05
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
        'vertical_spacing' => '35px',
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
            'level_1' => '0 12px 36px rgba(245, 245, 220, 0.45), 0 0 24px rgba(255, 182, 193, 0.4)',
            'level_2' => '0 20px 60px rgba(245, 245, 220, 0.55), 0 0 40px rgba(255, 182, 193, 0.5)',
            'focus' => '0 0 0 4px rgba(221, 160, 221, 0.7), 0 0 24px rgba(221, 160, 221, 0.5)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '110ms',
            'fast' => '160ms',
            'standard' => '300ms'
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

echo "\n🚀 My Diary theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Soft personal colors (light pink, lavender, plum, rose) with diary gradients\n";
echo "   - Elegant typography (Dancing Script + Crimson Text)\n";
echo "   - Page title: 3px plum border + beige shadow + 16px light pink glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced pink glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (pink, lavender, cream tones)\n";
echo "   - Warm, intimate diary palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_my_diary_profile_settings.php\n";

