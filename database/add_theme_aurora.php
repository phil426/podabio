<?php
/**
 * Add or Update Aurora Skies Theme
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
    $existing = fetchOne("SELECT * FROM themes WHERE name = ? LIMIT 1", [$themeName]);
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
    'border_glow_intensity' => 'subtle',
    'border_shadow_intensity' => 'none',
    'glow_color' => '#7AFFD8',
    'spacing' => 'comfortable',
    'shape' => 'rounded'
];

$colorTokens = [
    'background' => [
        'base' => '#030616',
        'surface' => 'rgba(13, 18, 36, 0.88)',
        'surface_raised' => 'rgba(16, 22, 42, 0.95)',
        'overlay' => 'rgba(3, 7, 22, 0.75)'
    ],
    'gradient' => [
        'page' => 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)',
        'accent' => 'linear-gradient(120deg, #7affd8 0%, #5b9cff 55%, #a875ff 100%)',
        'widget' => 'linear-gradient(135deg, rgba(122,255,216,0.18) 0%, rgba(91,156,255,0.14) 50%, rgba(168,117,255,0.24) 100%)',
        'podcast' => 'linear-gradient(135deg, #040610 0%, #111b3a 60%, #1c2854 100%)'
    ],
    'text' => [
        'primary' => '#F6FAFF',
        'secondary' => '#A6B7FF',
        'inverse' => '#070812'
    ],
    'accent' => [
        'primary' => '#7AFFD8',
        'muted' => 'rgba(122,255,216,0.18)'
    ],
    'border' => [
        'default' => 'rgba(122,255,216,0.32)',
        'focus' => '#7AFFD8'
    ],
    'state' => [
        'success' => '#16d6a9',
        'warning' => '#f7b84d',
        'danger' => '#ff6f91'
    ],
    'text_state' => [
        'success' => '#06372a',
        'warning' => '#5c310f',
        'danger' => '#4f071c'
    ],
    'shadow' => [
        'ambient' => 'rgba(6, 12, 35, 0.28)',
        'focus' => 'rgba(122,255,216,0.35)'
    ],
    'glow' => [
        'primary' => 'rgba(122,255,216,0.55)'
    ]
];

$typographyTokens = [
    'font' => [
        'heading' => 'Poppins',
        'body' => 'Inter',
        'metatext' => 'Inter'
    ],
    'scale' => [
        'xl' => 2.55,
        'lg' => 1.9,
        'md' => 1.32,
        'sm' => 1.08,
        'xs' => 0.9
    ],
    'line_height' => [
        'tight' => 1.2,
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
        'sm' => '0.4rem',
        'md' => '0.9rem',
        'lg' => '1.6rem',
        'pill' => '999px'
    ],
    'shadow' => [
        'level_1' => '0 16px 38px rgba(6, 10, 45, 0.32)',
        'level_2' => '0 24px 60px rgba(6, 10, 45, 0.38)',
        'focus' => '0 0 0 3px rgba(122,255,216,0.35)'
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

$pageBackground = 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)';
$widgetBackground = 'rgba(13, 18, 36, 0.88)';
$widgetBorderColor = 'rgba(122,255,216,0.32)';
$spatialEffect = 'none';

$fields = [
    'colors' => json_encode($colors),
    'fonts' => json_encode($fonts),
    'page_background' => $pageBackground,
    'widget_styles' => json_encode($widgetStyles),
    'spatial_effect' => $spatialEffect,
    'widget_background' => $widgetBackground,
    'widget_border_color' => $widgetBorderColor,
    'widget_primary_font' => 'Poppins',
    'widget_secondary_font' => 'Inter',
    'page_primary_font' => 'Poppins',
    'page_secondary_font' => 'Inter',
    'color_tokens' => json_encode($colorTokens),
    'typography_tokens' => json_encode($typographyTokens),
    'spacing_tokens' => json_encode($spacingTokens),
    'shape_tokens' => json_encode($shapeTokens),
    'motion_tokens' => json_encode($motionTokens),
    'layout_density' => 'comfortable'
];

try {
    if ($existing) {
        $setParts = [];
        $values = [];
        foreach ($fields as $column => $value) {
            if ($column === 'layout_density' || $column === 'spatial_effect') {
                $setParts[] = "$column = ?";
            } else {
                $setParts[] = "$column = ?";
            }
            $values[] = $value;
        }
        $values[] = $existing['id'];

        $sql = "UPDATE themes SET name = ?, " . implode(', ', $setParts) . " WHERE id = ?";
        array_unshift($values, $themeName);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        echo "✅ Aurora theme updated (ID: {$existing['id']})\n";
    } else {
        $columns = array_keys($fields);
        $placeholders = implode(', ', array_fill(0, count($columns) + 2, '?'));
        $sql = "INSERT INTO themes (user_id, name, " . implode(', ', $columns) . ", is_active) VALUES (NULL, ?, " . implode(', ', array_fill(0, count($columns), '?')) . ", 1)";
        $stmt = $pdo->prepare($sql);
        $values = array_merge([$themeName], array_values($fields));
        $stmt->execute($values);
        $newId = $pdo->lastInsertId();
        echo "✅ Aurora theme created (ID: {$newId})\n";
    }

    $themeRow = fetchOne("SELECT id, name, page_background FROM themes WHERE name = ? LIMIT 1", [$themeName]);
    if ($themeRow) {
        echo "   ID: {$themeRow['id']}\n";
        echo "   Background: {$themeRow['page_background']}\n";
    }
    echo "   Fonts: Poppins + Inter\n";
    echo "\nActivate it from the theme selector to experience the refreshed design.\n";
} catch (PDOException $e) {
    echo "❌ Failed to upsert Aurora theme: " . $e->getMessage() . "\n";
}

