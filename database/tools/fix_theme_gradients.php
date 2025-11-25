<?php
/**
 * Fix Harsh Gradient Transitions in Themes
 * 
 * This script smooths out harsh gradient transitions by adding intermediate color stops
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

/**
 * Calculate relative luminance of a color
 */
function getLuminance($hex) {
    $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
        return 0.5; // Default for invalid colors
    }
    
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
    
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Interpolate between two hex colors
 */
function interpolateColor($color1, $color2, $factor) {
    $hex1 = preg_replace('/[^0-9a-fA-F]/', '', $color1);
    $hex2 = preg_replace('/[^0-9a-fA-F]/', '', $color2);
    
    if (strlen($hex1) === 3) {
        $hex1 = $hex1[0] . $hex1[0] . $hex1[1] . $hex1[1] . $hex1[2] . $hex1[2];
    }
    if (strlen($hex2) === 3) {
        $hex2 = $hex2[0] . $hex2[0] . $hex2[1] . $hex2[1] . $hex2[2] . $hex2[2];
    }
    
    if (strlen($hex1) !== 6 || strlen($hex2) !== 6) {
        return $color1; // Return original if invalid
    }
    
    $r1 = hexdec(substr($hex1, 0, 2));
    $g1 = hexdec(substr($hex1, 2, 2));
    $b1 = hexdec(substr($hex1, 4, 2));
    
    $r2 = hexdec(substr($hex2, 0, 2));
    $g2 = hexdec(substr($hex2, 2, 2));
    $b2 = hexdec(substr($hex2, 4, 2));
    
    $r = round($r1 + ($r2 - $r1) * $factor);
    $g = round($g1 + ($g2 - $g1) * $factor);
    $b = round($b1 + ($b2 - $b1) * $factor);
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Smooth a gradient by adding intermediate colors where needed
 */
function smoothGradient($gradient) {
    if (empty($gradient) || strpos($gradient, 'gradient') === false) {
        return $gradient;
    }
    
    // Extract gradient type and content
    preg_match('/(linear|radial)-gradient\s*\(([^)]+)\)/', $gradient, $match);
    if (empty($match)) {
        return $gradient;
    }
    
    $gradType = $match[1];
    $content = $match[2];
    
    // Extract direction
    preg_match('/^\s*([^,]+),/', $content, $dirMatch);
    $direction = trim($dirMatch[1] ?? '135deg');
    
    // Extract all color stops
    preg_match_all('/(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))\s*(\d+%)?/', $content, $matches);
    $colors = $matches[1];
    $stops = $matches[2];
    
    if (count($colors) < 2) {
        return $gradient;
    }
    
    // Check for harsh transitions and add intermediate colors
    $newColors = [$colors[0]];
    $newStops = [trim($stops[0] ?? '0%')];
    
    for ($i = 0; $i < count($colors) - 1; $i++) {
        $color1 = $colors[$i];
        $color2 = $colors[$i + 1];
        $stop1 = isset($stops[$i]) ? (int)str_replace('%', '', $stops[$i]) : ($i * 100 / (count($colors) - 1));
        $stop2 = isset($stops[$i + 1]) ? (int)str_replace('%', '', $stops[$i + 1]) : (($i + 1) * 100 / (count($colors) - 1));
        
        // Only process hex colors (skip rgba)
        if (strpos($color1, '#') === 0 && strpos($color2, '#') === 0) {
            $lum1 = getLuminance($color1);
            $lum2 = getLuminance($color2);
            $diff = abs($lum1 - $lum2);
            
            // If luminance difference is too large, add intermediate colors
            if ($diff > 0.4) {
                $numSteps = ceil($diff / 0.2); // Add steps based on difference
                $numSteps = min($numSteps, 3); // Max 3 intermediate steps
                
                for ($j = 1; $j <= $numSteps; $j++) {
                    $factor = $j / ($numSteps + 1);
                    $intermediateColor = interpolateColor($color1, $color2, $factor);
                    $intermediateStop = round($stop1 + ($stop2 - $stop1) * $factor);
                    $newColors[] = $intermediateColor;
                    $newStops[] = $intermediateStop . '%';
                }
            }
        }
        
        // Add the next color
        $newColors[] = $color2;
        $newStops[] = trim($stops[$i + 1] ?? ($stop2 . '%'));
    }
    
    // Rebuild gradient
    $newContent = $direction . ', ';
    for ($i = 0; $i < count($newColors); $i++) {
        $newContent .= $newColors[$i];
        if (isset($newStops[$i]) && !empty($newStops[$i])) {
            $newContent .= ' ' . $newStops[$i];
        }
        if ($i < count($newColors) - 1) {
            $newContent .= ', ';
        }
    }
    
    return $gradType . '-gradient(' . $newContent . ')';
}

echo "==========================================\n";
echo "🎨 Fixing Harsh Gradient Transitions\n";
echo "==========================================\n\n";

try {
    // Get themes with gradient backgrounds
    $themes = fetchAll("SELECT id, name, page_background, widget_background FROM themes WHERE is_active = 1 ORDER BY name ASC");
    
    $fixed = [];
    
    foreach ($themes as $theme) {
        $themeId = $theme['id'];
        $themeName = $theme['name'];
        $pageBg = $theme['page_background'] ?? '';
        $widgetBg = $theme['widget_background'] ?? '';
        
        $updates = [];
        
        // Check and fix page background
        if (!empty($pageBg) && strpos($pageBg, 'gradient') !== false) {
            $smoothed = smoothGradient($pageBg);
            if ($smoothed !== $pageBg) {
                $updates['page_background'] = $smoothed;
            }
        }
        
        // Check and fix widget background
        if (!empty($widgetBg) && strpos($widgetBg, 'gradient') !== false) {
            $smoothed = smoothGradient($widgetBg);
            if ($smoothed !== $widgetBg) {
                $updates['widget_background'] = $smoothed;
            }
        }
        
        // Update if needed
        if (!empty($updates)) {
            $updateSql = [];
            $updateValues = [];
            
            foreach ($updates as $field => $value) {
                $updateSql[] = "{$field} = ?";
                $updateValues[] = $value;
            }
            
            $updateValues[] = $themeId;
            $sql = "UPDATE themes SET " . implode(', ', $updateSql) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
            
            $fixed[$themeName] = array_keys($updates);
            echo "✅ Fixed {$themeName}: " . implode(', ', array_keys($updates)) . "\n";
        }
    }
    
    echo "\n==========================================\n";
    if (empty($fixed)) {
        echo "✅ No gradients needed fixing!\n";
    } else {
        echo "✅ Fixed " . count($fixed) . " theme(s)\n";
    }
    echo "==========================================\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

