<?php
/**
 * Add or Update Energy Drink Theme
 * Vibrant gradient theme with energy drink-inspired colors
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$themeName = 'Energy Drink';

echo "==========================================\n";
echo "⚡ Energy Drink Theme Installer\n";
echo "==========================================\n";

try {
    $existing = fetchOne("SELECT * FROM themes WHERE name = ? LIMIT 1", [$themeName]);
    $columns = $pdo->query("SHOW COLUMNS FROM themes")->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_map('strtolower', $columns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect themes table: " . $e->getMessage() . "\n";
    return;
}

$colors = [
    'primary' => '#00FF88',
    'secondary' => '#0A0E1A',
    'accent' => '#FFD700'
];

$fonts = [
    'heading' => 'Inter',
    'body' => 'Inter'
];

$widgetStyles = [
    'border_width' => 'thin',
    'border_effect' => 'glow',
    'border_glow_intensity' => 'subtle',
    'border_shadow_intensity' => 'none',
    'glow_color' => '#00FF88',
    'spacing' => 'comfortable',
    'shape' => 'rounded'
];

$colorTokens = [
    'background' => [
        'base' => '#0A0E1A',
        'surface' => 'rgba(15, 20, 35, 0.88)',
        'surface_raised' => 'rgba(20, 25, 40, 0.95)',
        'overlay' => 'rgba(5, 8, 15, 0.75)'
    ],
    'gradient' => [
        'page' => 'linear-gradient(135deg, #0A0E1A 0%, #1A2E3A 30%, #2A4E5A 60%, #0A0E1A 100%)',
        'accent' => 'linear-gradient(120deg, #00FF88 0%, #00D4FF 50%, #FFD700 100%)',
        'widget' => 'linear-gradient(135deg, rgba(0,255,136,0.15) 0%, rgba(0,212,255,0.12) 50%, rgba(255,215,0,0.18) 100%)',
        'podcast' => 'linear-gradient(135deg, #0A0E1A 0%, #1A2E3A 50%, #2A4E5A 100%)'
    ],
    'text' => [
        'primary' => '#FFFFFF',
        'secondary' => '#B0E0FF',
        'inverse' => '#0A0E1A'
    ],
    'accent' => [
        'primary' => '#00FF88',
        'muted' => 'rgba(0,255,136,0.25)'
    ],
    'border' => [
        'default' => 'rgba(0,255,136,0.35)',
        'focus' => '#00FF88'
    ],
    'state' => [
        'success' => '#00FF88',
        'warning' => '#FFD700',
        'danger' => '#FF4444'
    ],
    'text_state' => [
        'success' => '#0A0E1A',
        'warning' => '#0A0E1A',
        'danger' => '#FFFFFF'
    ],
    'shadow' => [
        'ambient' => 'rgba(0, 0, 0, 0.35)',
        'focus' => 'rgba(0,255,136,0.4)'
    ],
    'glow' => [
        'primary' => 'rgba(0,255,136,0.6)'
    ]
];

$typographyTokens = [
    'font' => [
        'heading' => 'Inter',
        'body' => 'Inter',
        'metatext' => 'Inter'
    ],
    'scale' => [
        'xl' => 2.5,
        'lg' => 1.85,
        'md' => 1.35,
        'sm' => 1.1,
        'xs' => 0.9
    ],
    'line_height' => [
        'tight' => 1.2,
        'normal' => 1.5,
        'relaxed' => 1.75
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
        'xs' => 0.5,
        'sm' => 0.85,
        'md' => 1.1,
        'lg' => 1.6,
        'xl' => 2.2,
        '2xl' => 3.2
    ],
    'modifiers' => []
];

$shapeTokens = [
    'corner' => [
        'none' => '0px',
        'sm' => '0.5rem',
        'md' => '1rem',
        'lg' => '1.5rem',
        'pill' => '999px'
    ],
    'shadow' => [
        'level_1' => '0 16px 38px rgba(0, 0, 0, 0.4)',
        'level_2' => '0 24px 60px rgba(0, 0, 0, 0.45)',
        'focus' => '0 0 0 3px rgba(0,255,136,0.4)'
    ]
];

$motionTokens = [
    'duration' => [
        'fast' => '150ms',
        'standard' => '250ms'
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

$pageBackground = 'linear-gradient(135deg, #0A0E1A 0%, #1A2E3A 30%, #2A4E5A 60%, #0A0E1A 100%)';
$widgetBackground = 'rgba(15, 20, 35, 0.88)';
$widgetBorderColor = 'rgba(0,255,136,0.35)';
$spatialEffect = 'none';

$fieldValues = [
    'name' => $themeName,
    'colors' => json_encode($colors),
    'fonts' => json_encode($fonts),
    'page_background' => $pageBackground,
    'widget_styles' => json_encode($widgetStyles),
    'spatial_effect' => $spatialEffect,
    'widget_background' => $widgetBackground,
    'widget_border_color' => $widgetBorderColor,
    'widget_primary_font' => 'Inter',
    'widget_secondary_font' => 'Inter',
    'page_primary_font' => 'Inter',
    'page_secondary_font' => 'Inter'
];

if (in_array('color_tokens', $columns, true)) {
    $fieldValues['color_tokens'] = json_encode($colorTokens);
}
if (in_array('typography_tokens', $columns, true)) {
    $fieldValues['typography_tokens'] = json_encode($typographyTokens);
}
if (in_array('spacing_tokens', $columns, true)) {
    $fieldValues['spacing_tokens'] = json_encode($spacingTokens);
}
if (in_array('shape_tokens', $columns, true)) {
    $fieldValues['shape_tokens'] = json_encode($shapeTokens);
}
if (in_array('motion_tokens', $columns, true)) {
    $fieldValues['motion_tokens'] = json_encode($motionTokens);
}
if (in_array('layout_density', $columns, true)) {
    $fieldValues['layout_density'] = 'comfortable';
}

try {
    if ($existing) {
        $setSql = ['name = ?'];
        $values = [$themeName];
        foreach ($fieldValues as $column => $value) {
            if ($column === 'name') {
                continue;
            }
            $setSql[] = "$column = ?";
            $values[] = $value;
        }
        $values[] = $existing['id'];
        $sql = "UPDATE themes SET " . implode(', ', $setSql) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $themeId = $existing['id'];
        echo "✅ Energy Drink theme updated (ID: {$themeId})\n";
    } else {
        $columnsSql = ['user_id', 'name'];
        $placeholders = ['NULL', '?'];
        $values = [$themeName];
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
        $themeId = $pdo->lastInsertId();
        echo "✅ Energy Drink theme created (ID: {$themeId})\n";
    }

    $themeRow = fetchOne("SELECT id, page_background FROM themes WHERE name = ? LIMIT 1", [$themeName]);
    if ($themeRow) {
        echo "   ID: {$themeRow['id']}\n";
        echo "   Background: {$themeRow['page_background']}\n";
    }
    echo "   Fonts: Inter\n";
    echo "\nActivate it from the theme selector to experience the energetic design.\n";
} catch (PDOException $e) {
    echo "❌ Failed to upsert Energy Drink theme: " . $e->getMessage() . "\n";
}

