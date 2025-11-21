<?php
/**
 * Add White Sand Theme
 * 
 * A bright, beach-inspired theme with white sand and ocean colors
 * Features:
 * - Bright colors (white, sand, light blues, coral accents)
 * - Extensive use of BOTH shadow AND glow effects
 * - Clean, fresh typography
 * - Multiple gradient backgrounds
 * - Bright, airy tones with depth
 * - Beach and sand-inspired colors
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🏖️ White Sand Theme Installer\n";
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
    'name' => 'White Sand',
    'legacy_colors' => [
        'primary' => '#87CEEB', // Sky blue
        'secondary' => '#FFF8DC', // Cornsilk
        'accent' => '#FF7F50' // Coral
    ],
    'fonts' => [
        'heading' => 'Poppins',
        'body' => 'Inter'
    ],
    'page_primary_font' => 'Poppins',
    'page_secondary_font' => 'Inter',
    'widget_primary_font' => 'Poppins',
    'widget_secondary_font' => 'Inter',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#87CEEB', // Sky blue glow
        'glow_width' => 19, // Large glow
        'glow_intensity' => 0.93, // Very high intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #FFFFFF 0%, #FFF8DC 20%, #F0F8FF 35%, #E0F6FF 50%, #87CEEB 65%, #F0F8FF 80%, #FFFFFF 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(135, 206, 235, 0.12) 50%, rgba(255, 248, 220, 0.1) 100%)',
    'widget_border_color' => 'rgba(135, 206, 235, 0.7)',
    'color_tokens' => [
        'background' => [
            'frame' => '#F5F5F5',
            'base' => 'linear-gradient(135deg, #FFFFFF 0%, #FFF8DC 20%, #F0F8FF 35%, #E0F6FF 50%, #87CEEB 65%, #F0F8FF 80%, #FFFFFF 100%)',
            'surface' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(135, 206, 235, 0.12) 50%, rgba(255, 248, 220, 0.1) 100%)',
            'surface_translucent' => 'rgba(135, 206, 235, 0.2)',
            'surface_raised' => 'linear-gradient(160deg, rgba(255, 255, 255, 0.95) 0%, rgba(135, 206, 235, 0.18) 100%)',
            'overlay' => 'rgba(245, 245, 245, 0.8)'
        ],
        'text' => [
            'primary' => '#2C3E50', // Dark blue-gray
            'secondary' => '#5D6D7E', // Medium blue-gray
            'inverse' => '#FFFFFF' // White for inverse
        ],
        'border' => [
            'default' => 'rgba(135, 206, 235, 0.7)',
            'focus' => '#FF7F50' // Coral focus
        ],
        'accent' => [
            'primary' => '#87CEEB', // Sky blue
            'muted' => 'rgba(135, 206, 235, 0.3)',
            'alt' => '#FF7F50', // Coral
            'highlight' => '#FFF8DC' // Cornsilk
        ],
        'stroke' => [
            'subtle' => 'rgba(135, 206, 235, 0.4)'
        ],
        'state' => [
            'success' => '#48C9B0', // Turquoise
            'warning' => '#F4D03F', // Yellow
            'danger' => '#EC7063' // Light red
        ],
        'text_state' => [
            'success' => '#0A3D32',
            'warning' => '#6B5B00',
            'danger' => '#7A1F1F'
        ],
        'shadow' => [
            'ambient' => 'rgba(44, 62, 80, 0.2)',
            'focus' => 'rgba(255, 127, 80, 0.4)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #FFFFFF 0%, #FFF8DC 20%, #F0F8FF 35%, #E0F6FF 50%, #87CEEB 65%, #F0F8FF 80%, #FFFFFF 100%)',
            'accent' => 'linear-gradient(135deg, #87CEEB 0%, #FF7F50 50%, #FFF8DC 100%)',
            'widget' => 'linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(135, 206, 235, 0.18) 50%, rgba(255, 248, 220, 0.15) 100%)',
            'podcast' => 'linear-gradient(180deg, #F5F5F5 0%, #87CEEB 50%, #FFF8DC 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(135, 206, 235, 0.7)',
            'secondary' => 'rgba(255, 127, 80, 0.6)',
            'accent' => 'rgba(255, 248, 220, 0.5)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Poppins',
            'body' => 'Inter',
            'widget_heading' => 'Poppins',
            'widget_body' => 'Inter',
            'metatext' => 'Inter'
        ],
        'color' => [
            'heading' => '#2C3E50', // Dark blue-gray
            'body' => '#5D6D7E', // Medium blue-gray
            'widget_heading' => '#2C3E50',
            'widget_body' => '#5D6D7E'
        ],
        'effect' => [
            'border' => [
                'color' => '#FF7F50', // Coral border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#2C3E50', // Dark blue-gray shadow
                'intensity' => 0.6,
                'depth' => 5, // Deep shadow
                'blur' => 12 // Wide blur
            ],
            'glow' => [
                'color' => '#87CEEB', // Sky blue glow
                'width' => 18 // 18px glow width
            ]
        ],
        'scale' => [
            'xl' => 3.2,
            'lg' => 2.4,
            'md' => 1.65,
            'sm' => 1.28,
            'xs' => 1.12
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
            'level_1' => '0 12px 36px rgba(44, 62, 80, 0.2), 0 0 24px rgba(135, 206, 235, 0.4)',
            'level_2' => '0 20px 60px rgba(44, 62, 80, 0.3), 0 0 40px rgba(135, 206, 235, 0.5)',
            'focus' => '0 0 0 4px rgba(255, 127, 80, 0.7), 0 0 24px rgba(255, 127, 80, 0.5)'
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

echo "\n🚀 White Sand theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Bright beach colors (white, sand, sky blue, coral) with ocean gradients\n";
echo "   - Clean typography (Poppins + Inter)\n";
echo "   - Page title: 3px coral border + deep shadow + 18px sky blue glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced sky blue glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (white, sand, sky blue tones)\n";
echo "   - Bright, airy beach palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_white_sand_profile_settings.php\n";

