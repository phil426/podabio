<?php
/**
 * Add Morning Mist Theme - Subtle Light Background, Medium Contrast
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

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
    'name' => 'Morning Mist',
    'legacy_colors' => ['primary' => '#E8E8E8', 'secondary' => '#F5F5F5', 'accent' => '#6B7280'],
    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
    'page_primary_font' => 'Inter',
    'page_secondary_font' => 'Inter',
    'widget_primary_font' => 'Inter',
    'widget_secondary_font' => 'Inter',
    'widget_styles' => [
        'border_width' => 'regular', // 2px
        'border_effect' => 'none',
        'glow_width' => 0,
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ],
    'spatial_effect' => 'none',
    'page_background' => '#F9FAFB',
    'widget_background' => '#FFFFFF',
    'widget_border_color' => '#E5E7EB',
    'color_tokens' => [
        'background' => [
            'frame' => '#F9FAFB',
            'base' => '#F9FAFB',
            'surface' => '#FFFFFF',
            'surface_translucent' => 'rgba(255, 255, 255, 0.8)',
            'surface_raised' => '#FFFFFF',
            'overlay' => 'rgba(249, 250, 251, 0.9)'
        ],
        'text' => [
            'primary' => '#1F2937',
            'secondary' => '#4B5563',
            'inverse' => '#FFFFFF'
        ],
        'border' => [
            'default' => '#E5E7EB',
            'focus' => '#6B7280'
        ],
        'accent' => [
            'primary' => '#6B7280',
            'muted' => 'rgba(107, 114, 128, 0.2)',
            'alt' => '#9CA3AF',
            'highlight' => '#F3F4F6'
        ],
        'stroke' => ['subtle' => '#E5E7EB'],
        'state' => [
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ],
        'text_state' => [
            'success' => '#065F46',
            'warning' => '#92400E',
            'danger' => '#991B1B'
        ],
        'shadow' => [
            'ambient' => 'rgba(0, 0, 0, 0.05)',
            'focus' => 'rgba(107, 114, 128, 0.2)'
        ],
        'gradient' => [
            'page' => '#F9FAFB',
            'accent' => 'linear-gradient(135deg, #F9FAFB 0%, #E5E7EB 100%)',
            'widget' => '#FFFFFF',
            'podcast' => '#F9FAFB'
        ],
        'glow' => [
            'primary' => 'rgba(107, 114, 128, 0.1)',
            'secondary' => 'rgba(156, 163, 175, 0.1)',
            'accent' => 'rgba(243, 244, 246, 0.1)'
        ]
    ],
    'typography_tokens' => [
        'font' => [
            'heading' => 'Inter',
            'body' => 'Inter',
            'widget_heading' => 'Inter',
            'widget_body' => 'Inter',
            'metatext' => 'Inter'
        ],
        'color' => [
            'heading' => '#1F2937',
            'body' => '#4B5563',
            'widget_heading' => '#1F2937',
            'widget_body' => '#4B5563'
        ],
        'effect' => [
            'border' => ['color' => 'transparent', 'width' => 0],
            'shadow' => ['color' => 'transparent', 'intensity' => 0, 'depth' => 0, 'blur' => 0],
            'glow' => ['color' => 'transparent', 'width' => 0]
        ],
        'scale' => ['xl' => 2.5, 'lg' => 2.0, 'md' => 1.5, 'sm' => 1.25, 'xs' => 1.1],
        'line_height' => ['tight' => 1.25, 'normal' => 1.6, 'relaxed' => 1.8],
        'weight' => ['normal' => 400, 'medium' => 600, 'bold' => 700]
    ],
    'spacing_tokens' => [
        'density' => 'comfortable',
        'vertical_spacing' => '24px',
        'base_scale' => ['2xs' => 0.25, 'xs' => 0.5, 'sm' => 0.75, 'md' => 1.0, 'lg' => 1.5, 'xl' => 2.0, '2xl' => 3.0],
        'density_multipliers' => [
            'compact' => ['2xs' => 0.75, 'xs' => 0.85, 'sm' => 0.9, 'md' => 1.0, 'lg' => 1.0, 'xl' => 1.0, '2xl' => 1.0],
            'comfortable' => ['2xs' => 1.0, 'xs' => 1.0, 'sm' => 1.1, 'md' => 1.25, 'lg' => 1.3, 'xl' => 1.35, '2xl' => 1.4]
        ],
        'modifiers' => []
    ],
    'shape_tokens' => [
        'corner' => ['none' => '0px', 'sm' => '0.5rem', 'md' => '1rem', 'lg' => '1.5rem', 'pill' => '9999px'],
        'border_width' => ['hairline' => '1px', 'regular' => '2px', 'bold' => '3px'],
        'shadow' => [
            'level_1' => '0 1px 3px rgba(0, 0, 0, 0.05)',
            'level_2' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'focus' => '0 0 0 3px rgba(107, 114, 128, 0.2)'
        ]
    ],
    'motion_tokens' => [
        'duration' => ['momentary' => '100ms', 'fast' => '150ms', 'standard' => '280ms'],
        'easing' => ['standard' => 'cubic-bezier(0.4, 0, 0.2, 1)', 'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'],
        'focus' => ['ring_width' => '3px', 'ring_offset' => '2px']
    ],
    'layout_density' => 'comfortable'
];

$name = $themeConfig['name'];
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

if ($hasColorTokens) $fieldValues['color_tokens'] = json_encode($themeConfig['color_tokens'], JSON_UNESCAPED_SLASHES);
if ($hasTypographyTokens) $fieldValues['typography_tokens'] = json_encode($themeConfig['typography_tokens'], JSON_UNESCAPED_SLASHES);
if ($hasSpacingTokens) $fieldValues['spacing_tokens'] = json_encode($themeConfig['spacing_tokens'], JSON_UNESCAPED_SLASHES);
if ($hasShapeTokens) $fieldValues['shape_tokens'] = json_encode($themeConfig['shape_tokens'], JSON_UNESCAPED_SLASHES);
if ($hasMotionTokens) $fieldValues['motion_tokens'] = json_encode($themeConfig['motion_tokens'], JSON_UNESCAPED_SLASHES);
if ($hasLayoutDensity) $fieldValues['layout_density'] = $themeConfig['layout_density'];

try {
    if ($existing) {
        $setSql = ['name = ?'];
        $values = [$name];
        foreach ($fieldValues as $column => $value) {
            if ($column === 'name') continue;
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
            if ($column === 'name') continue;
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

