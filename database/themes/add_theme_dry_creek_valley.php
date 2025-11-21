<?php
/**
 * Add Dry Creek Valley Theme
 * 
 * A sophisticated, wine country-inspired theme
 * Features:
 * - Rich wine colors (deep reds, purples, golds, earth tones, vineyard greens)
 * - Extensive use of BOTH shadow AND glow effects
 * - Elegant, refined typography
 * - Multiple gradient backgrounds
 * - Warm, earthy tones with depth
 * - Sophisticated color combinations
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🍷 Dry Creek Valley Theme Installer\n";
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
    'name' => 'Dry Creek Valley',
    'legacy_colors' => [
        'primary' => '#722F37', // Wine red
        'secondary' => '#D4AF37', // Gold
        'accent' => '#6B4423' // Earth brown
    ],
    'fonts' => [
        'heading' => 'Cinzel',
        'body' => 'Lora'
    ],
    'page_primary_font' => 'Cinzel',
    'page_secondary_font' => 'Lora',
    'widget_primary_font' => 'Cinzel',
    'widget_secondary_font' => 'Lora',
    'widget_styles' => [
        'border_width' => 'bold', // 3px
        'border_effect' => 'glow', // Use glow effect
        'border_glow_intensity' => 'pronounced', // Strong glow
        'border_shadow_intensity' => 'pronounced', // ALSO use shadow for depth
        'glow_color' => '#722F37', // Wine red glow
        'glow_width' => 16, // Large glow
        'glow_intensity' => 0.85, // High intensity
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #2C1A1A 0%, #4A2C2C 20%, #722F37 35%, #8B4C5A 50%, #6B4423 65%, #4A2C2C 80%, #2C1A1A 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(114, 47, 55, 0.16) 0%, rgba(212, 175, 55, 0.14) 50%, rgba(107, 68, 35, 0.12) 100%)',
    'widget_border_color' => 'rgba(114, 47, 55, 0.85)',
    'color_tokens' => [
        'background' => [
            'frame' => '#1A0F0F',
            'base' => 'linear-gradient(135deg, #2C1A1A 0%, #4A2C2C 20%, #722F37 35%, #8B4C5A 50%, #6B4423 65%, #4A2C2C 80%, #2C1A1A 100%)',
            'surface' => 'linear-gradient(145deg, rgba(114, 47, 55, 0.16) 0%, rgba(212, 175, 55, 0.14) 50%, rgba(107, 68, 35, 0.12) 100%)',
            'surface_translucent' => 'rgba(114, 47, 55, 0.22)',
            'surface_raised' => 'linear-gradient(160deg, rgba(114, 47, 55, 0.2) 0%, rgba(212, 175, 55, 0.18) 100%)',
            'overlay' => 'rgba(26, 15, 15, 0.85)'
        ],
        'text' => [
            'primary' => '#F5E6D3', // Cream
            'secondary' => '#E8D5C4', // Light cream
            'inverse' => '#1A0F0F' // Dark for inverse
        ],
        'border' => [
            'default' => 'rgba(114, 47, 55, 0.85)',
            'focus' => '#D4AF37' // Gold focus
        ],
        'accent' => [
            'primary' => '#722F37', // Wine red
            'muted' => 'rgba(114, 47, 55, 0.4)',
            'alt' => '#D4AF37', // Gold
            'highlight' => '#6B4423' // Earth brown
        ],
        'stroke' => [
            'subtle' => 'rgba(114, 47, 55, 0.5)'
        ],
        'state' => [
            'success' => '#556B2F', // Dark olive (vineyard green)
            'warning' => '#D4AF37', // Gold
            'danger' => '#8B0000' // Dark red
        ],
        'text_state' => [
            'success' => '#2A3517',
            'warning' => '#6B5B00',
            'danger' => '#4A0000'
        ],
        'shadow' => [
            'ambient' => 'rgba(26, 15, 15, 0.5)',
            'focus' => 'rgba(212, 175, 55, 0.6)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #2C1A1A 0%, #4A2C2C 20%, #722F37 35%, #8B4C5A 50%, #6B4423 65%, #4A2C2C 80%, #2C1A1A 100%)',
            'accent' => 'linear-gradient(135deg, #722F37 0%, #D4AF37 50%, #6B4423 100%)',
            'widget' => 'linear-gradient(145deg, rgba(114, 47, 55, 0.2) 0%, rgba(212, 175, 55, 0.18) 50%, rgba(107, 68, 35, 0.16) 100%)',
            'podcast' => 'linear-gradient(180deg, #1A0F0F 0%, #722F37 50%, #6B4423 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(114, 47, 55, 0.75)',
            'secondary' => 'rgba(212, 175, 55, 0.65)',
            'accent' => 'rgba(107, 68, 35, 0.55)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Cinzel',
            'body' => 'Lora',
            'widget_heading' => 'Cinzel',
            'widget_body' => 'Lora',
            'metatext' => 'Lora'
        ],
        'color' => [
            'heading' => '#F5E6D3', // Cream
            'body' => '#E8D5C4', // Light cream
            'widget_heading' => '#F5E6D3',
            'widget_body' => '#E8D5C4'
        ],
        'effect' => [
            'border' => [
                'color' => '#D4AF37', // Gold border
                'width' => 3 // 3px border
            ],
            'shadow' => [
                'color' => '#1A0F0F', // Dark shadow
                'intensity' => 0.75,
                'depth' => 6, // Deep shadow
                'blur' => 12 // Wide blur
            ],
            'glow' => [
                'color' => '#722F37', // Wine red glow
                'width' => 14 // 14px glow width
            ]
        ],
        'scale' => [
            'xl' => 2.9,
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
        'vertical_spacing' => '34px',
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
            'level_1' => '0 12px 36px rgba(26, 15, 15, 0.5), 0 0 24px rgba(114, 47, 55, 0.4)',
            'level_2' => '0 20px 60px rgba(26, 15, 15, 0.6), 0 0 40px rgba(114, 47, 55, 0.5)',
            'focus' => '0 0 0 4px rgba(212, 175, 55, 0.7), 0 0 24px rgba(212, 175, 55, 0.5)'
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

echo "\n🚀 Dry Creek Valley theme is ready! Activate it from the Themes tab!\n";
echo "   Features:\n";
echo "   - Rich wine country colors (wine red, gold, earth brown) with elegant gradients\n";
echo "   - Sophisticated typography (Cinzel + Lora)\n";
echo "   - Page title: 3px gold border + deep shadow + 14px wine red glow\n";
echo "   - Widgets: 3px borders with BOTH pronounced wine red glow AND shadow\n";
echo "   - Profile image: Glow effect (apply via helper script)\n";
echo "   - Extensive use of BOTH shadows AND glows throughout\n";
echo "   - Multi-color gradient backgrounds (wine, earth, gold tones)\n";
echo "   - Warm, sophisticated color palette\n";
echo "\n";
echo "📝 To apply profile image glow settings, run:\n";
echo "   php database/themes/apply_dry_creek_valley_profile_settings.php\n";

