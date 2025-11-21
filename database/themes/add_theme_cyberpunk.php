<?php
/**
 * Add Cyberpunk Theme
 * 
 * A futuristic theme with neon colors, gradients, and glowing effects
 * Features:
 * - Electric cyan, hot pink, purple, and electric blue color palette
 * - Multiple gradient backgrounds
 * - Glowing widget borders and effects
 * - Futuristic typography
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎨 Cyberpunk Theme Installer\n";
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
    'name' => 'Cyberpunk Neon',
    'legacy_colors' => [
        'primary' => '#00F5FF', // Electric cyan
        'secondary' => '#FF00FF', // Hot pink/magenta
        'accent' => '#7B2CBF' // Purple
    ],
    'fonts' => [
        'heading' => 'Orbitron',
        'body' => 'Rajdhani'
    ],
    'page_primary_font' => 'Orbitron',
    'page_secondary_font' => 'Rajdhani',
    'widget_primary_font' => 'Rajdhani',
    'widget_secondary_font' => 'Rajdhani',
    'widget_styles' => [
        'border_width' => 'regular',
        'border_effect' => 'glow',
        'border_glow_intensity' => 'pronounced',
        'border_shadow_intensity' => 'none',
        'glow_color' => '#00F5FF',
        'glow_width' => 12,
        'glow_intensity' => 0.8,
        'spacing' => 'compact',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => 'linear-gradient(135deg, #0A0A1A 0%, #1A0A2E 25%, #16213E 50%, #0F3460 75%, #0A0A1A 100%)',
    'widget_background' => 'linear-gradient(145deg, rgba(0, 245, 255, 0.08) 0%, rgba(123, 44, 191, 0.12) 50%, rgba(255, 0, 255, 0.08) 100%)',
    'widget_border_color' => 'rgba(0, 245, 255, 0.6)',
    'color_tokens' => [
        'background' => [
            'frame' => '#000000',
            'base' => 'linear-gradient(135deg, #0A0A1A 0%, #1A0A2E 25%, #16213E 50%, #0F3460 75%, #0A0A1A 100%)',
            'surface' => 'linear-gradient(145deg, rgba(0, 245, 255, 0.08) 0%, rgba(123, 44, 191, 0.12) 50%, rgba(255, 0, 255, 0.08) 100%)',
            'surface_translucent' => 'rgba(0, 245, 255, 0.15)',
            'surface_raised' => 'linear-gradient(160deg, rgba(0, 245, 255, 0.12) 0%, rgba(123, 44, 191, 0.18) 100%)',
            'overlay' => 'rgba(0, 0, 0, 0.85)'
        ],
        'text' => [
            'primary' => '#00F5FF', // Electric cyan
            'secondary' => '#B8B8FF', // Light purple-blue
            'inverse' => '#000000'
        ],
        'border' => [
            'default' => 'rgba(0, 245, 255, 0.6)',
            'focus' => '#FF00FF' // Hot pink
        ],
        'accent' => [
            'primary' => '#00F5FF', // Electric cyan
            'muted' => 'rgba(0, 245, 255, 0.3)',
            'alt' => '#FF00FF', // Hot pink
            'highlight' => '#7B2CBF' // Purple
        ],
        'stroke' => [
            'subtle' => 'rgba(0, 245, 255, 0.4)'
        ],
        'state' => [
            'success' => '#00FF88', // Neon green
            'warning' => '#FFB800', // Neon yellow
            'danger' => '#FF006E' // Hot pink-red
        ],
        'text_state' => [
            'success' => '#003D22',
            'warning' => '#4A2E00',
            'danger' => '#4A0018'
        ],
        'shadow' => [
            'ambient' => 'rgba(0, 245, 255, 0.3)',
            'focus' => 'rgba(255, 0, 255, 0.5)'
        ],
        'gradient' => [
            'page' => 'linear-gradient(135deg, #0A0A1A 0%, #1A0A2E 25%, #16213E 50%, #0F3460 75%, #0A0A1A 100%)',
            'accent' => 'linear-gradient(135deg, #00F5FF 0%, #7B2CBF 50%, #FF00FF 100%)',
            'widget' => 'linear-gradient(145deg, rgba(0, 245, 255, 0.15) 0%, rgba(123, 44, 191, 0.2) 50%, rgba(255, 0, 255, 0.15) 100%)',
            'podcast' => 'linear-gradient(180deg, #000000 0%, #1A0A2E 50%, #16213E 100%)'
        ],
        'glow' => [
            'primary' => 'rgba(0, 245, 255, 0.6)',
            'secondary' => 'rgba(255, 0, 255, 0.5)',
            'accent' => 'rgba(123, 44, 191, 0.4)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Orbitron',
            'body' => 'Rajdhani',
            'widget_heading' => 'Rajdhani',
            'widget_body' => 'Rajdhani',
            'metatext' => 'Rajdhani'
        ],
        'color' => [
            'heading' => '#00F5FF', // Electric cyan
            'body' => '#B8B8FF', // Light purple-blue
            'widget_heading' => '#00F5FF',
            'widget_body' => '#B8B8FF'
        ],
        'effect' => [
            'border' => [
                'color' => '#00F5FF', // Blue (cyan)
                'width' => 2 // 2px border
            ],
            'glow' => [
                'color' => '#4B0082', // Dark purple (indigo)
                'width' => 12 // 12px glow width
            ]
        ],
        'scale' => [
            'xl' => 2.6,
            'lg' => 1.9,
            'md' => 1.35,
            'sm' => 1.1,
            'xs' => 0.95
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
        'density' => 'compact',
        'vertical_spacing' => '24px',
        'base_scale' => [
            '2xs' => 0.2,
            'xs' => 0.4,
            'sm' => 0.65,
            'md' => 0.9,
            'lg' => 1.3,
            'xl' => 1.8,
            '2xl' => 2.6
        ],
        'density_multipliers' => [
            'compact' => [
                '2xs' => 0.8,
                'xs' => 0.9,
                'sm' => 1.0,
                'md' => 1.05,
                'lg' => 1.1,
                'xl' => 1.15,
                '2xl' => 1.2
            ],
            'comfortable' => [
                '2xs' => 1.0,
                'xs' => 1.05,
                'sm' => 1.1,
                'md' => 1.2,
                'lg' => 1.3,
                'xl' => 1.4,
                '2xl' => 1.5
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
            'level_1' => '0 8px 24px rgba(0, 245, 255, 0.3), 0 0 16px rgba(0, 245, 255, 0.2)',
            'level_2' => '0 16px 48px rgba(0, 245, 255, 0.4), 0 0 32px rgba(255, 0, 255, 0.3)',
            'focus' => '0 0 0 4px rgba(255, 0, 255, 0.6), 0 0 16px rgba(255, 0, 255, 0.4)'
        ]
    ],
    'motion_tokens' => [
        'duration' => [
            'momentary' => '80ms',
            'fast' => '120ms',
            'standard' => '200ms'
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
    'layout_density' => 'compact'
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

echo "\n🚀 Cyberpunk theme is ready! Activate it from the Themes tab!\n";
echo "   Features: Neon colors, gradients, glowing effects, futuristic fonts\n";
echo "   - 2px blue border on page title (via typography_tokens)\n";
echo "   - Profile image settings need to be applied to pages manually:\n";
echo "     * 3px cyan border (#00F5FF)\n";
echo "     * 18px purple glow effect (#7B2CBF)\n";
echo "\n";
echo "📝 To apply profile image settings to all pages using this theme, run:\n";
echo "   php database/themes/apply_cyberpunk_profile_settings.php\n";

