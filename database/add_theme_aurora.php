<?php
/**
 * Add Aurora Skies Theme
 *
 * Inserts the Aurora Skies system theme with advanced token definitions.
 * Safe to run multiple times – skips if the theme already exists.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$themeName = 'Aurora Skies';

echo "==========================================\n";
echo "🌌 Aurora Theme Installer\n";
echo "==========================================\n";

try {
    $existing = fetchOne("SELECT id FROM themes WHERE name = ? LIMIT 1", [$themeName]);
    if ($existing) {
        echo "⚠️  Theme '{$themeName}' already exists (ID: {$existing['id']}).\n";
        echo "   No changes were made.\n";
        return;
    }
} catch (PDOException $e) {
    echo "❌ Failed to query existing themes: " . $e->getMessage() . "\n";
    return;
}

$colors = [
    'primary' => '#7AFFD8',
    'secondary' => '#050619',
    'accent' => '#64A0FF'
];

$fonts = [
    'heading' => 'Poppins',
    'body' => 'Inter'
];

$widgetStyles = [
    'border_width' => 'thin',
    'border_effect' => 'glow',
    'border_glow_intensity' => 'pronounced',
    'border_shadow_intensity' => 'none',
    'glow_color' => '#7AFFD8',
    'spacing' => 'comfortable',
    'shape' => 'rounded'
];

$colorTokens = [
    'background' => [
        'base' => '#030618',
        'surface' => 'rgba(10, 16, 45, 0.88)',
        'surface_raised' => 'rgba(21, 28, 64, 0.95)',
        'overlay' => 'rgba(3, 6, 23, 0.72)'
    ],
    'gradient' => [
        'page' => 'linear-gradient(145deg, #050919 0%, #18093E 40%, #041438 100%), radial-gradient(circle at 20% 20%, rgba(32,132,255,0.45) 0%, rgba(3,6,24,0.0) 55%)',
        'accent' => 'linear-gradient(120deg, #7AFFD8 0%, #64A0FF 50%, #B174FF 100%)',
        'widget' => 'linear-gradient(135deg, rgba(122,255,216,0.16) 0%, rgba(100,160,255,0.12) 45%, rgba(177,116,255,0.24) 100%)',
        'podcast' => 'linear-gradient(135deg, rgba(122,255,216,0.25) 0%, rgba(100,160,255,0.32) 50%, rgba(177,116,255,0.28) 100%)'
    ],
    'text' => [
        'primary' => '#F7FBFF',
        'secondary' => '#96A8FF',
        'inverse' => '#050612'
    ],
    'accent' => [
        'primary' => '#7AFFD8',
        'muted' => 'rgba(122, 255, 216, 0.18)'
    ],
    'border' => [
        'default' => 'rgba(122, 255, 216, 0.42)',
        'focus' => '#7AFFD8'
    ],
    'state' => [
        'success' => '#13E29A',
        'warning' => '#F8C13F',
        'danger' => '#FF5A8D'
    ],
    'text_state' => [
        'success' => '#053F2B',
        'warning' => '#5A2E0C',
        'danger' => '#4F041C'
    ],
    'shadow' => [
        'ambient' => 'rgba(5, 12, 38, 0.22)',
        'focus' => 'rgba(122, 255, 216, 0.35)'
    ],
    'glow' => [
        'primary' => 'rgba(122, 255, 216, 0.6)'
    ]
];

$typographyTokens = [
    'font' => [
        'heading' => 'Poppins',
        'body' => 'Inter',
        'metatext' => 'Inter'
    ],
    'scale' => [
        'xl' => 2.65,
        'lg' => 1.9,
        'md' => 1.38,
        'sm' => 1.1,
        'xs' => 0.9
    ],
    'line_height' => [
        'tight' => 1.18,
        'normal' => 1.55,
        'relaxed' => 1.8
    ],
    'weight' => [
        'normal' => 400,
        'medium' => 500,
        'bold' => 700
    ]
];

$spacingTokens = [
    'density' => 'comfortable',
    'base_scale' => [
        '2xs' => 0.25,
        'xs' => 0.55,
        'sm' => 0.85,
        'md' => 1.1,
        'lg' => 1.6,
        'xl' => 2.4,
        '2xl' => 3.4
    ]
];

$shapeTokens = [
    'corner' => [
        'none' => '0px',
        'sm' => '0.5rem',
        'md' => '1.25rem',
        'lg' => '2.25rem',
        'pill' => '999px'
    ],
    'shadow' => [
        'level_1' => '0 22px 65px rgba(6, 10, 45, 0.5)',
        'level_2' => '0 35px 120px rgba(4, 9, 35, 0.6)',
        'focus' => '0 0 0 4px rgba(122, 255, 216, 0.4)'
    ]
];

$motionTokens = [
    'duration' => [
        'fast' => '160ms',
        'standard' => '260ms'
    ],
    'easing' => [
        'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
        'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
    ],
    'focus' => [
        'ring_width' => '3px',
        'ring_offset' => '2px'
    ]
];

$pageBackground = 'linear-gradient(145deg, #050919 0%, #18093E 40%, #041438 100%)';
$widgetBackground = 'rgba(10, 16, 45, 0.88)';
$widgetBorderColor = 'rgba(122, 255, 216, 0.42)';
$spatialEffect = 'floating';

try {
    $columns = $pdo->query("SHOW COLUMNS FROM themes")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "❌ Unable to discover theme columns: " . $e->getMessage() . "\n";
    return;
}

$fields = [
    'user_id',
    'name',
    'colors',
    'fonts',
    'page_background',
    'widget_styles',
    'spatial_effect',
    'widget_background',
    'widget_border_color',
    'widget_primary_font',
    'widget_secondary_font',
    'page_primary_font',
    'page_secondary_font',
    'is_active'
];

$values = [
    null,
    $themeName,
    json_encode($colors),
    json_encode($fonts),
    $pageBackground,
    json_encode($widgetStyles),
    $spatialEffect,
    $widgetBackground,
    $widgetBorderColor,
    'Poppins',
    'Inter',
    'Poppins',
    'Inter',
    1
];

if (in_array('color_tokens', $columns, true)) {
    $fields[] = 'color_tokens';
    $values[] = json_encode($colorTokens);
}

if (in_array('typography_tokens', $columns, true)) {
    $fields[] = 'typography_tokens';
    $values[] = json_encode($typographyTokens);
}

if (in_array('spacing_tokens', $columns, true)) {
    $fields[] = 'spacing_tokens';
    $values[] = json_encode($spacingTokens);
}

if (in_array('shape_tokens', $columns, true)) {
    $fields[] = 'shape_tokens';
    $values[] = json_encode($shapeTokens);
}

if (in_array('motion_tokens', $columns, true)) {
    $fields[] = 'motion_tokens';
    $values[] = json_encode($motionTokens);
}

if (in_array('layout_density', $columns, true)) {
    $fields[] = 'layout_density';
    $values[] = 'comfortable';
}

$fieldSql = implode(', ', $fields);
$placeholderSql = implode(', ', array_fill(0, count($fields), '?'));

try {
    $stmt = $pdo->prepare("INSERT INTO themes ({$fieldSql}) VALUES ({$placeholderSql})");
    $stmt->execute($values);
    $insertedId = $pdo->lastInsertId();

    echo "✅ Aurora theme created successfully!\n";
    echo "   ID: {$insertedId}\n";
    echo "   Background: {$pageBackground}\n";
    echo "   Accent Gradient: {$colorTokens['gradient']['accent']}\n";
    echo "   Fonts: Poppins + Inter\n";
    echo "\nActivate it from the theme selector to experience the full design.\n";
} catch (PDOException $e) {
    echo "❌ Failed to insert Aurora theme: " . $e->getMessage() . "\n";
}

