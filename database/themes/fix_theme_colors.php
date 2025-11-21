<?php
/**
 * Fix Theme Colors
 * - Replace #2563EB social icon colors with theme-appropriate colors
 * - Soften dramatic backgrounds
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "==========================================\n";
echo "🎨 Theme Color Fixer\n";
echo "==========================================\n\n";

// Get all themes
try {
    $themes = fetchAll("SELECT id, name, iconography_tokens, page_background, widget_background, color_tokens FROM themes WHERE is_active = 1");
    echo "Found " . count($themes) . " active themes\n\n";
} catch (PDOException $e) {
    echo "❌ Failed to fetch themes: " . $e->getMessage() . "\n";
    exit(1);
}

$updated = 0;
$skipped = 0;

foreach ($themes as $theme) {
    $themeId = $theme['id'];
    $themeName = $theme['name'];
    $needsUpdate = false;
    $updates = [];
    
    // Parse iconography_tokens
    $iconographyTokens = [];
    if (!empty($theme['iconography_tokens'])) {
        if (is_string($theme['iconography_tokens'])) {
            $iconographyTokens = json_decode($theme['iconography_tokens'], true) ?: [];
        } else {
            $iconographyTokens = $theme['iconography_tokens'];
        }
    }
    
    // Initialize iconography_tokens if empty
    if (empty($iconographyTokens)) {
        $iconographyTokens = [
            'color' => '#2563EB', // Will be replaced below
            'size' => '48px',
            'spacing' => '0.75rem'
        ];
    }
    
    // Check social icon color - update if #2563EB or if not set (defaults to #2563EB)
    $currentIconColor = $iconographyTokens['color'] ?? '#2563EB';
    if ($currentIconColor === '#2563EB' || $currentIconColor === '#2563eb' || empty($currentIconColor)) {
        // Determine better color based on theme
        $newIconColor = getThemeAppropriateIconColor($theme);
        $iconographyTokens['color'] = $newIconColor;
        // Ensure size and spacing are set
        if (!isset($iconographyTokens['size'])) {
            $iconographyTokens['size'] = '48px';
        }
        if (!isset($iconographyTokens['spacing'])) {
            $iconographyTokens['spacing'] = '0.75rem';
        }
        $updates['iconography_tokens'] = json_encode($iconographyTokens, JSON_UNESCAPED_SLASHES);
        $needsUpdate = true;
        echo "  📌 {$themeName}: Icon color {$currentIconColor} → {$newIconColor}\n";
    }
    
    // Check and soften dramatic backgrounds
    $pageBg = $theme['page_background'] ?? '';
    $widgetBg = $theme['widget_background'] ?? '';
    
    // Check page background
    if (!empty($pageBg) && isDramaticBackground($pageBg)) {
        $softened = softenBackground($pageBg);
        if ($softened !== $pageBg) {
            $updates['page_background'] = $softened;
            $needsUpdate = true;
            echo "  🎨 {$themeName}: Softened page background\n";
        }
    }
    
    // Check widget background
    if (!empty($widgetBg) && isDramaticBackground($widgetBg)) {
        $softened = softenBackground($widgetBg);
        if ($softened !== $widgetBg) {
            $updates['widget_background'] = $softened;
            $needsUpdate = true;
            echo "  🎨 {$themeName}: Softened widget background\n";
        }
    }
    
    // Check color_tokens for dramatic backgrounds
    $colorTokens = [];
    if (!empty($theme['color_tokens'])) {
        if (is_string($theme['color_tokens'])) {
            $colorTokens = json_decode($theme['color_tokens'], true) ?: [];
        } else {
            $colorTokens = $theme['color_tokens'];
        }
    }
    
    if (!empty($colorTokens)) {
        $colorTokensUpdated = false;
        
        // Check gradient backgrounds
        if (isset($colorTokens['gradient'])) {
            foreach ($colorTokens['gradient'] as $key => $gradient) {
                if (is_string($gradient) && isDramaticBackground($gradient)) {
                    $colorTokens['gradient'][$key] = softenBackground($gradient);
                    $colorTokensUpdated = true;
                }
            }
        }
        
        // Check background.base
        if (isset($colorTokens['background']['base']) && isDramaticBackground($colorTokens['background']['base'])) {
            $colorTokens['background']['base'] = softenBackground($colorTokens['background']['base']);
            $colorTokensUpdated = true;
        }
        
        if ($colorTokensUpdated) {
            $updates['color_tokens'] = json_encode($colorTokens, JSON_UNESCAPED_SLASHES);
            $needsUpdate = true;
            echo "  🎨 {$themeName}: Softened color_tokens backgrounds\n";
        }
    }
    
    // Update theme if needed
    if ($needsUpdate) {
        try {
            $setSql = [];
            $values = [];
            foreach ($updates as $column => $value) {
                $setSql[] = "{$column} = ?";
                $values[] = $value;
            }
            $values[] = $themeId;
            
            $sql = "UPDATE themes SET " . implode(', ', $setSql) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $updated++;
            echo "  ✅ Updated {$themeName}\n\n";
        } catch (PDOException $e) {
            echo "  ❌ Failed to update {$themeName}: " . $e->getMessage() . "\n\n";
        }
    } else {
        $skipped++;
    }
}

echo "==========================================\n";
echo "✅ Complete!\n";
echo "   Updated: {$updated} theme(s)\n";
echo "   Skipped: {$skipped} theme(s)\n";
echo "==========================================\n";

/**
 * Get theme-appropriate icon color based on theme colors
 */
function getThemeAppropriateIconColor($theme) {
    // Parse color_tokens to find accent colors
    $colorTokens = [];
    if (!empty($theme['color_tokens'])) {
        if (is_string($theme['color_tokens'])) {
            $colorTokens = json_decode($theme['color_tokens'], true) ?: [];
        } else {
            $colorTokens = $theme['color_tokens'];
        }
    }
    
    // Try to get accent color from color_tokens
    $accentColor = null;
    if (isset($colorTokens['accent']['primary'])) {
        $accentColor = $colorTokens['accent']['primary'];
    } elseif (isset($colorTokens['accent']['alt'])) {
        $accentColor = $colorTokens['accent']['alt'];
    }
    
    // If we have an accent color, use it
    if ($accentColor && isHexColor($accentColor)) {
        return $accentColor;
    }
    
    // Try to extract color from page background
    $pageBg = $theme['page_background'] ?? '';
    if (!empty($pageBg)) {
        $extracted = extractColorFromBackground($pageBg);
        if ($extracted) {
            return $extracted;
        }
    }
    
    // Try widget background
    $widgetBg = $theme['widget_background'] ?? '';
    if (!empty($widgetBg)) {
        $extracted = extractColorFromBackground($widgetBg);
        if ($extracted) {
            return $extracted;
        }
    }
    
    // Default based on theme name (theme-specific colors)
    $themeName = strtolower($theme['name'] ?? '');
    
    // Light themes - use muted colors
    if (strpos($themeName, 'cream') !== false || strpos($themeName, 'vanilla') !== false) {
        return '#B8860B'; // Dark goldenrod
    }
    if (strpos($themeName, 'lavender') !== false) {
        return '#9370DB'; // Medium purple
    }
    if (strpos($themeName, 'sage') !== false || strpos($themeName, 'mint') !== false) {
        return '#6B8E23'; // Olive drab
    }
    if (strpos($themeName, 'peach') !== false || strpos($themeName, 'rose') !== false) {
        return '#CD5C5C'; // Indian red
    }
    if (strpos($themeName, 'sky') !== false) {
        return '#4682B4'; // Steel blue
    }
    if (strpos($themeName, 'sand') !== false) {
        return '#BC8F8F'; // Rosy brown
    }
    
    // Dark themes - use lighter accent colors
    if (strpos($themeName, 'midnight') !== false || strpos($themeName, 'ocean') !== false) {
        return '#5F9EA0'; // Cadet blue
    }
    if (strpos($themeName, 'charcoal') !== false || strpos($themeName, 'obsidian') !== false || strpos($themeName, 'slate') !== false) {
        return '#A9A9A9'; // Dark gray
    }
    if (strpos($themeName, 'forest') !== false) {
        return '#8FBC8F'; // Dark sea green
    }
    if (strpos($themeName, 'wine') !== false || strpos($themeName, 'velvet') !== false || strpos($themeName, 'twilight') !== false) {
        return '#BA55D3'; // Medium orchid
    }
    if (strpos($themeName, 'ember') !== false) {
        return '#FF7F50'; // Coral
    }
    
    // Dramatic themes - use theme-appropriate colors
    if (strpos($themeName, 'cyberpunk') !== false) {
        return '#9D4EDD'; // Purple
    }
    if (strpos($themeName, 'oxford') !== false) {
        return '#8B4513'; // Saddle brown
    }
    if (strpos($themeName, 'bourbon') !== false) {
        return '#CD853F'; // Peru
    }
    if (strpos($themeName, 'chicago') !== false) {
        return '#DC143C'; // Crimson
    }
    if (strpos($themeName, 'dry creek') !== false || strpos($themeName, 'arizona') !== false) {
        return '#D2691E'; // Chocolate
    }
    if (strpos($themeName, 'south pole') !== false || strpos($themeName, 'white sand') !== false || strpos($themeName, 'mount shasta') !== false) {
        return '#87CEEB'; // Sky blue
    }
    if (strpos($themeName, 'wheat') !== false || strpos($themeName, 'legal pad') !== false) {
        return '#DAA520'; // Goldenrod
    }
    if (strpos($themeName, 'moonlight') !== false) {
        return '#C0C0C0'; // Silver
    }
    if (strpos($themeName, 'vodka') !== false) {
        return '#FF69B4'; // Hot pink
    }
    if (strpos($themeName, 'dirt road') !== false) {
        return '#CD853F'; // Peru
    }
    if (strpos($themeName, 'lake tahoe') !== false) {
        return '#00CED1'; // Dark turquoise
    }
    if (strpos($themeName, 'canvas sail') !== false) {
        return '#4169E1'; // Royal blue
    }
    
    // Default fallback - use a neutral gray
    return '#6B7280'; // Gray-500
}

/**
 * Check if background is too dramatic (has many color stops or high contrast)
 */
function isDramaticBackground($bg) {
    if (empty($bg)) return false;
    
    // Check if it's a gradient
    if (strpos($bg, 'gradient') === false) {
        return false; // Solid colors are fine
    }
    
    // Count color stops (indicated by %)
    $percentCount = substr_count($bg, '%');
    
    // If more than 4 color stops, it's probably too dramatic
    if ($percentCount > 8) {
        return true;
    }
    
    // Check for very long gradients (many colors) - more than 150 chars suggests complexity
    if (strlen($bg) > 150) {
        return true;
    }
    
    // Check for gradients with 5+ distinct color values (indicated by # or rgb)
    preg_match_all('/(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))/i', $bg, $colorMatches);
    if (count($colorMatches[0]) > 4) {
        return true;
    }
    
    return false;
}

/**
 * Soften a dramatic background by reducing color stops
 */
function softenBackground($bg) {
    if (empty($bg) || strpos($bg, 'gradient') === false) {
        return $bg; // Not a gradient, return as-is
    }
    
    // Extract gradient type
    preg_match('/(linear|radial)-gradient\(([^)]+)\)/', $bg, $matches);
    if (empty($matches)) {
        return $bg;
    }
    
    $gradientType = $matches[1];
    $gradientContent = $matches[2];
    
    // Extract direction if linear
    $direction = '135deg';
    if ($gradientType === 'linear') {
        preg_match('/(\d+deg|to\s+\w+)/', $gradientContent, $dirMatch);
        if (!empty($dirMatch)) {
            $direction = $dirMatch[1];
            $gradientContent = preg_replace('/(\d+deg|to\s+\w+)\s*,?\s*/', '', $gradientContent);
        }
    }
    
    // Extract color stops
    preg_match_all('/(rgba?\([^)]+\)|#[0-9a-fA-F]{3,6}|[a-z]+)\s+(\d+)%?/i', $gradientContent, $colorMatches, PREG_SET_ORDER);
    
    if (count($colorMatches) < 2) {
        return $bg; // Can't parse, return as-is
    }
    
    // If we have more than 4 stops, reduce to 3-4
    if (count($colorMatches) > 4) {
        // Take first, middle, and last
        $first = $colorMatches[0];
        $last = $colorMatches[count($colorMatches) - 1];
        $middleIndex = floor(count($colorMatches) / 2);
        $middle = $colorMatches[$middleIndex];
        
        // Build simpler gradient
        $newGradient = "{$gradientType}-gradient({$direction}, {$first[1]} 0%, {$middle[1]} 50%, {$last[1]} 100%)";
        return $newGradient;
    }
    
    // If 3-4 stops, just simplify percentages
    $simplified = [];
    foreach ($colorMatches as $i => $match) {
        $color = $match[1];
        $percent = $i === 0 ? 0 : ($i === count($colorMatches) - 1 ? 100 : round(100 * $i / (count($colorMatches) - 1)));
        $simplified[] = "{$color} {$percent}%";
    }
    
    return "{$gradientType}-gradient({$direction}, " . implode(', ', $simplified) . ")";
}

/**
 * Extract a color from a background (gradient or solid)
 */
function extractColorFromBackground($bg) {
    if (empty($bg)) return null;
    
    // If it's a solid color (hex or rgb)
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/i', $bg)) {
        return $bg;
    }
    
    // If it's a gradient, extract the first color
    if (preg_match('/(rgba?\([^)]+\)|#[0-9a-fA-F]{3,6}|[a-z]+)\s+0%/i', $bg, $matches)) {
        $color = $matches[1];
        // Convert named colors to hex if needed
        if (!strpos($color, '#') && !strpos($color, 'rgb')) {
            $colorMap = [
                'white' => '#FFFFFF',
                'black' => '#000000',
                'red' => '#FF0000',
                'blue' => '#0000FF',
                'green' => '#00FF00'
            ];
            $color = $colorMap[strtolower($color)] ?? $color;
        }
        return $color;
    }
    
    // Try to extract any hex color
    if (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/i', $bg, $matches)) {
        return '#' . $matches[1];
    }
    
    return null;
}

/**
 * Check if string is a valid hex color
 */
function isHexColor($color) {
    return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/i', $color);
}

