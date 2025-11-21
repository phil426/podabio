<?php
/**
 * Add 20 Subtle Themes (10 Light + 10 Dark)
 * Batch installer for all subtle medium contrast themes
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎨 Subtle Themes Batch Installer\n";
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

// Light themes (subtle, medium contrast)
$lightThemes = [
    [
        'name' => 'Cream Soda',
        'page_bg' => '#FEF9F3',
        'widget_bg' => '#FFFFFF',
        'border' => '#F5E6D3',
        'text_primary' => '#2D1B0E',
        'text_secondary' => '#5C4A3A',
        'accent' => '#D4A574'
    ],
    [
        'name' => 'Lavender Fields',
        'page_bg' => '#F8F6FF',
        'widget_bg' => '#FFFFFF',
        'border' => '#E8E0F5',
        'text_primary' => '#2D1B3D',
        'text_secondary' => '#5C4A6B',
        'accent' => '#9B7EDE'
    ],
    [
        'name' => 'Sage Garden',
        'page_bg' => '#F5F7F4',
        'widget_bg' => '#FFFFFF',
        'border' => '#E0E6DC',
        'text_primary' => '#1B2D1B',
        'text_secondary' => '#4A5C4A',
        'accent' => '#8FA68F'
    ],
    [
        'name' => 'Peach Blush',
        'page_bg' => '#FFF5F0',
        'widget_bg' => '#FFFFFF',
        'border' => '#F5E0D3',
        'text_primary' => '#3D1B0E',
        'text_secondary' => '#6B4A3A',
        'accent' => '#E8A87C'
    ],
    [
        'name' => 'Sky Light',
        'page_bg' => '#F0F7FF',
        'widget_bg' => '#FFFFFF',
        'border' => '#D3E5F5',
        'text_primary' => '#0E1B2D',
        'text_secondary' => '#3A4A5C',
        'accent' => '#7CA8E8'
    ],
    [
        'name' => 'Vanilla Bean',
        'page_bg' => '#FFFEF8',
        'widget_bg' => '#FFFFFF',
        'border' => '#F5F3E6',
        'text_primary' => '#2D2B1B',
        'text_secondary' => '#5C5A4A',
        'accent' => '#D4C8A5'
    ],
    [
        'name' => 'Rose Quartz',
        'page_bg' => '#FFF5F5',
        'widget_bg' => '#FFFFFF',
        'border' => '#F5E0E0',
        'text_primary' => '#3D1B1B',
        'text_secondary' => '#6B4A4A',
        'accent' => '#E8A8A8'
    ],
    [
        'name' => 'Mint Fresh',
        'page_bg' => '#F0FFF5',
        'widget_bg' => '#FFFFFF',
        'border' => '#D3F5E0',
        'text_primary' => '#0E2D1B',
        'text_secondary' => '#3A5C4A',
        'accent' => '#7CE8A8'
    ],
    [
        'name' => 'Sand Dollar',
        'page_bg' => '#FDF9F5',
        'widget_bg' => '#FFFFFF',
        'border' => '#F0E6DC',
        'text_primary' => '#2D1B0E',
        'text_secondary' => '#5C4A3A',
        'accent' => '#C8A574'
    ],
    [
        'name' => 'Cloud White',
        'page_bg' => '#FAFAFA',
        'widget_bg' => '#FFFFFF',
        'border' => '#E8E8E8',
        'text_primary' => '#1B1B1B',
        'text_secondary' => '#4A4A4A',
        'accent' => '#8A8A8A'
    ]
];

// Dark themes (medium contrast)
$darkThemes = [
    [
        'name' => 'Midnight Blue',
        'page_bg' => '#1A1F2E',
        'widget_bg' => '#252B3D',
        'border' => '#3A4A5C',
        'text_primary' => '#E8F0F8',
        'text_secondary' => '#B8C8D8',
        'accent' => '#5A8AE8'
    ],
    [
        'name' => 'Charcoal',
        'page_bg' => '#1F1F1F',
        'widget_bg' => '#2A2A2A',
        'border' => '#404040',
        'text_primary' => '#F0F0F0',
        'text_secondary' => '#C8C8C8',
        'accent' => '#8A8A8A'
    ],
    [
        'name' => 'Forest Night',
        'page_bg' => '#1A2E1F',
        'widget_bg' => '#253D2B',
        'border' => '#3A5C4A',
        'text_primary' => '#E8F8F0',
        'text_secondary' => '#B8D8C8',
        'accent' => '#5AE8A8'
    ],
    [
        'name' => 'Wine Dark',
        'page_bg' => '#2E1A1F',
        'widget_bg' => '#3D252B',
        'border' => '#5C3A4A',
        'text_primary' => '#F8E8F0',
        'text_secondary' => '#D8B8C8',
        'accent' => '#E85A8A'
    ],
    [
        'name' => 'Obsidian',
        'page_bg' => '#0F0F0F',
        'widget_bg' => '#1A1A1A',
        'border' => '#2F2F2F',
        'text_primary' => '#F5F5F5',
        'text_secondary' => '#C5C5C5',
        'accent' => '#7A7A7A'
    ],
    [
        'name' => 'Deep Ocean',
        'page_bg' => '#1A2E2E',
        'widget_bg' => '#253D3D',
        'border' => '#3A5C5C',
        'text_primary' => '#E8F8F8',
        'text_secondary' => '#B8D8D8',
        'accent' => '#5AE8E8'
    ],
    [
        'name' => 'Twilight',
        'page_bg' => '#2E1A2E',
        'widget_bg' => '#3D253D',
        'border' => '#5C3A5C',
        'text_primary' => '#F8E8F8',
        'text_secondary' => '#D8B8D8',
        'accent' => '#E85AE8'
    ],
    [
        'name' => 'Ember',
        'page_bg' => '#2E1F1A',
        'widget_bg' => '#3D2B25',
        'border' => '#5C4A3A',
        'text_primary' => '#F8F0E8',
        'text_secondary' => '#D8C8B8',
        'accent' => '#E8A85A'
    ],
    [
        'name' => 'Slate Night',
        'page_bg' => '#1F2528',
        'widget_bg' => '#2A3235',
        'border' => '#404A4D',
        'text_primary' => '#F0F5F8',
        'text_secondary' => '#C8D0D3',
        'accent' => '#8AA0A8'
    ],
    [
        'name' => 'Velvet',
        'page_bg' => '#2A1F2E',
        'widget_bg' => '#352A3D',
        'border' => '#4A3A5C',
        'text_primary' => '#F8F0F8',
        'text_secondary' => '#D8C8D8',
        'accent' => '#A85AE8'
    ]
];

function createTheme($pdo, $themeData, $isDark, $hasColorTokens, $hasTypographyTokens, $hasSpacingTokens, $hasShapeTokens, $hasMotionTokens, $hasLayoutDensity) {
    $name = $themeData['name'];
    
    $widgetStyles = [
        'border_width' => 'regular',
        'border_effect' => 'none',
        'glow_width' => 0,
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ];
    
    $colorTokens = [
        'background' => [
            'frame' => $themeData['page_bg'],
            'base' => $themeData['page_bg'],
            'surface' => $themeData['widget_bg'],
            'surface_translucent' => $isDark ? 'rgba(37, 43, 61, 0.8)' : 'rgba(255, 255, 255, 0.8)',
            'surface_raised' => $themeData['widget_bg'],
            'overlay' => $isDark ? 'rgba(26, 31, 46, 0.9)' : 'rgba(249, 250, 251, 0.9)'
        ],
        'text' => [
            'primary' => $themeData['text_primary'],
            'secondary' => $themeData['text_secondary'],
            'inverse' => $isDark ? '#1F2937' : '#FFFFFF'
        ],
        'border' => [
            'default' => $themeData['border'],
            'focus' => $themeData['accent']
        ],
        'accent' => [
            'primary' => $themeData['accent'],
            'muted' => $isDark ? 'rgba(90, 138, 232, 0.2)' : 'rgba(107, 114, 128, 0.2)',
            'alt' => $themeData['accent'],
            'highlight' => $isDark ? '#3A4A5C' : '#F3F4F6'
        ],
        'stroke' => ['subtle' => $themeData['border']],
        'state' => [
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444'
        ],
        'text_state' => [
            'success' => $isDark ? '#34D399' : '#065F46',
            'warning' => $isDark ? '#FBBF24' : '#92400E',
            'danger' => $isDark ? '#F87171' : '#991B1B'
        ],
        'shadow' => [
            'ambient' => $isDark ? 'rgba(0, 0, 0, 0.3)' : 'rgba(0, 0, 0, 0.05)',
            'focus' => $isDark ? 'rgba(90, 138, 232, 0.4)' : 'rgba(107, 114, 128, 0.2)'
        ],
        'gradient' => [
            'page' => $themeData['page_bg'],
            'accent' => $isDark ? "linear-gradient(135deg, {$themeData['page_bg']} 0%, {$themeData['widget_bg']} 100%)" : "linear-gradient(135deg, {$themeData['page_bg']} 0%, #E5E7EB 100%)",
            'widget' => $themeData['widget_bg'],
            'podcast' => $themeData['page_bg']
        ],
        'glow' => [
            'primary' => $isDark ? 'rgba(90, 138, 232, 0.1)' : 'rgba(107, 114, 128, 0.1)',
            'secondary' => $isDark ? 'rgba(138, 160, 168, 0.1)' : 'rgba(156, 163, 175, 0.1)',
            'accent' => $isDark ? 'rgba(58, 74, 92, 0.1)' : 'rgba(243, 244, 246, 0.1)'
        ]
    ];
    
    $typographyTokens = [
        'font' => [
            'heading' => 'Inter',
            'body' => 'Inter',
            'widget_heading' => 'Inter',
            'widget_body' => 'Inter',
            'metatext' => 'Inter'
        ],
        'color' => [
            'heading' => $themeData['text_primary'],
            'body' => $themeData['text_secondary'],
            'widget_heading' => $themeData['text_primary'],
            'widget_body' => $themeData['text_secondary']
        ],
        'effect' => [
            'border' => ['color' => 'transparent', 'width' => 0],
            'shadow' => ['color' => 'transparent', 'intensity' => 0, 'depth' => 0, 'blur' => 0],
            'glow' => ['color' => 'transparent', 'width' => 0]
        ],
        'scale' => ['xl' => 2.5, 'lg' => 2.0, 'md' => 1.5, 'sm' => 1.25, 'xs' => 1.1],
        'line_height' => ['tight' => 1.25, 'normal' => 1.6, 'relaxed' => 1.8],
        'weight' => ['normal' => 400, 'medium' => 600, 'bold' => 700]
    ];
    
    $spacingTokens = [
        'density' => 'comfortable',
        'vertical_spacing' => '24px',
        'base_scale' => ['2xs' => 0.25, 'xs' => 0.5, 'sm' => 0.75, 'md' => 1.0, 'lg' => 1.5, 'xl' => 2.0, '2xl' => 3.0],
        'density_multipliers' => [
            'compact' => ['2xs' => 0.75, 'xs' => 0.85, 'sm' => 0.9, 'md' => 1.0, 'lg' => 1.0, 'xl' => 1.0, '2xl' => 1.0],
            'comfortable' => ['2xs' => 1.0, 'xs' => 1.0, 'sm' => 1.1, 'md' => 1.25, 'lg' => 1.3, 'xl' => 1.35, '2xl' => 1.4]
        ],
        'modifiers' => []
    ];
    
    $shapeTokens = [
        'corner' => ['none' => '0px', 'sm' => '0.5rem', 'md' => '1rem', 'lg' => '1.5rem', 'pill' => '9999px'],
        'border_width' => ['hairline' => '1px', 'regular' => '2px', 'bold' => '3px'],
        'shadow' => [
            'level_1' => $isDark ? '0 2px 4px rgba(0, 0, 0, 0.3)' : '0 1px 3px rgba(0, 0, 0, 0.05)',
            'level_2' => $isDark ? '0 4px 8px rgba(0, 0, 0, 0.4)' : '0 4px 6px rgba(0, 0, 0, 0.1)',
            'focus' => $isDark ? "0 0 0 3px rgba({$themeData['accent']}, 0.4)" : "0 0 0 3px rgba(107, 114, 128, 0.2)"
        ]
    ];
    
    $motionTokens = [
        'duration' => ['momentary' => '100ms', 'fast' => '150ms', 'standard' => '280ms'],
        'easing' => ['standard' => 'cubic-bezier(0.4, 0, 0.2, 1)', 'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'],
        'focus' => ['ring_width' => '3px', 'ring_offset' => '2px']
    ];
    
    $fieldValues = [
        'name' => $name,
        'colors' => json_encode(['primary' => $themeData['accent'], 'secondary' => $themeData['border'], 'accent' => $themeData['accent']], JSON_UNESCAPED_SLASHES),
        'fonts' => json_encode(['heading' => 'Inter', 'body' => 'Inter'], JSON_UNESCAPED_SLASHES),
        'page_background' => $themeData['page_bg'],
        'widget_styles' => json_encode($widgetStyles, JSON_UNESCAPED_SLASHES),
        'spatial_effect' => 'none',
        'widget_background' => $themeData['widget_bg'],
        'widget_border_color' => $themeData['border'],
        'widget_primary_font' => 'Inter',
        'widget_secondary_font' => 'Inter',
        'page_primary_font' => 'Inter',
        'page_secondary_font' => 'Inter'
    ];
    
    if ($hasColorTokens) $fieldValues['color_tokens'] = json_encode($colorTokens, JSON_UNESCAPED_SLASHES);
    if ($hasTypographyTokens) $fieldValues['typography_tokens'] = json_encode($typographyTokens, JSON_UNESCAPED_SLASHES);
    if ($hasSpacingTokens) $fieldValues['spacing_tokens'] = json_encode($spacingTokens, JSON_UNESCAPED_SLASHES);
    if ($hasShapeTokens) $fieldValues['shape_tokens'] = json_encode($shapeTokens, JSON_UNESCAPED_SLASHES);
    if ($hasMotionTokens) $fieldValues['motion_tokens'] = json_encode($motionTokens, JSON_UNESCAPED_SLASHES);
    if ($hasLayoutDensity) $fieldValues['layout_density'] = 'comfortable';
    
    try {
        $existing = fetchOne("SELECT * FROM themes WHERE name = ? LIMIT 1", [$name]);
    } catch (PDOException $e) {
        echo "   ❌ Failed to lookup theme '{$name}': " . $e->getMessage() . "\n";
        return false;
    }
    
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
            echo "   ✅ Updated {$name} (ID: {$existing['id']})\n";
            return $existing['id'];
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
            echo "   ✅ Created {$name} (ID: {$newId})\n";
            return $newId;
        }
    } catch (PDOException $e) {
        echo "   ❌ Failed to upsert theme '{$name}': " . $e->getMessage() . "\n";
        return false;
    }
}

echo "Creating 10 Light Themes...\n";
foreach ($lightThemes as $theme) {
    createTheme($pdo, $theme, false, $hasColorTokens, $hasTypographyTokens, $hasSpacingTokens, $hasShapeTokens, $hasMotionTokens, $hasLayoutDensity);
}

echo "\nCreating 10 Dark Themes...\n";
foreach ($darkThemes as $theme) {
    createTheme($pdo, $theme, true, $hasColorTokens, $hasTypographyTokens, $hasSpacingTokens, $hasShapeTokens, $hasMotionTokens, $hasLayoutDensity);
}

echo "\n==========================================\n";
echo "✅ All 20 subtle themes created!\n";
echo "==========================================\n";

