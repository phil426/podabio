<?php
/**
 * Add or Update Maui Sundown Theme
 * Warm gradient theme with sunset-inspired colors
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$themeName = 'Maui Sundown';

echo "==========================================\n";
echo "🌅 Maui Sundown Theme Installer\n";
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
    'primary' => '#FF6B6B',
    'secondary' => '#1A1A2E',
    'accent' => '#FFA500'
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
    'glow_color' => '#FF6B6B',
    'spacing' => 'comfortable',
    'shape' => 'rounded'
];

$colorTokens = [
    'background' => [
        'base' => '#1A1A2E',
        'surface' => 'rgba(30, 25, 45, 0.88)',
        'surface_raised' => 'rgba(35, 30, 50, 0.95)',
        'overlay' => 'rgba(15, 15, 25, 0.75)'
    ],
    'gradient' => [
        'page' => 'linear-gradient(135deg, #1A1A2E 0%, #2D1B3D 25%, #8B3A5C 50%, #FF6B6B 75%, #FFA500 100%)',
        'accent' => 'linear-gradient(120deg, #FF6B6B 0%, #FF8E53 40%, #FFA500 80%, #FFD700 100%)',
        'widget' => 'linear-gradient(135deg, rgba(255,107,107,0.18) 0%, rgba(255,142,83,0.15) 50%, rgba(255,165,0,0.20) 100%)',
        'podcast' => 'linear-gradient(135deg, #1A1A2E 0%, #2D1B3D 40%, #8B3A5C 80%, #1A1A2E 100%)'
    ],
    'text' => [
        'primary' => '#FFFFFF',
        'secondary' => '#FFE5CC',
        'inverse' => '#1A1A2E'
    ],
    'accent' => [
        'primary' => '#FF6B6B',
        'muted' => 'rgba(255,107,107,0.25)'
    ],
    'border' => [
        'default' => 'rgba(255,107,107,0.35)',
        'focus' => '#FF6B6B'
    ],
    'state' => [
        'success' => '#4ECDC4',
        'warning' => '#FFA500',
        'danger' => '#FF4444'
    ],
    'text_state' => [
        'success' => '#1A1A2E',
        'warning' => '#1A1A2E',
        'danger' => '#FFFFFF'
    ],
    'shadow' => [
        'ambient' => 'rgba(26, 26, 46, 0.4)',
        'focus' => 'rgba(255,107,107,0.4)'
    ],
    'glow' => [
        'primary' => 'rgba(255,107,107,0.6)'
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
        'level_1' => '0 16px 38px rgba(26, 26, 46, 0.4)',
        'level_2' => '0 24px 60px rgba(26, 26, 46, 0.45)',
        'focus' => '0 0 0 3px rgba(255,107,107,0.4)'
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

$pageBackground = 'linear-gradient(135deg, #1A1A2E 0%, #2D1B3D 25%, #8B3A5C 50%, #FF6B6B 75%, #FFA500 100%)';
$widgetBackground = 'rgba(30, 25, 45, 0.88)';
$widgetBorderColor = 'rgba(255,107,107,0.35)';
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
        echo "✅ Maui Sundown theme updated (ID: {$themeId})\n";
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
        echo "✅ Maui Sundown theme created (ID: {$themeId})\n";
    }

    $themeRow = fetchOne("SELECT id, page_background FROM themes WHERE name = ? LIMIT 1", [$themeName]);
    if ($themeRow) {
        echo "   ID: {$themeRow['id']}\n";
        echo "   Background: {$themeRow['page_background']}\n";
    }
    echo "   Fonts: Inter\n";
    echo "\nActivate it from the theme selector to experience the warm sunset design.\n";
} catch (PDOException $e) {
    echo "❌ Failed to upsert Maui Sundown theme: " . $e->getMessage() . "\n";
}

