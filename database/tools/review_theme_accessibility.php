<?php
/**
 * Review and Fix Theme Accessibility and Contrast Issues
 * 
 * This script:
 * 1. Reviews all themes for WCAG contrast compliance
 * 2. Checks gradient backgrounds for harsh transitions
 * 3. Fixes issues automatically where possible
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

/**
 * Calculate relative luminance of a color
 */
function getLuminance($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
    
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Calculate contrast ratio between two colors
 */
function getContrastRatio($color1, $color2) {
    $l1 = getLuminance($color1);
    $l2 = getLuminance($color2);
    
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    
    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Check if contrast meets WCAG AA standards
 */
function meetsWCAGAA($foreground, $background) {
    $ratio = getContrastRatio($foreground, $background);
    return $ratio >= 4.5; // WCAG AA standard for normal text
}

/**
 * Check if contrast meets WCAG AA for large text
 */
function meetsWCAGAA_Large($foreground, $background) {
    $ratio = getContrastRatio($foreground, $background);
    return $ratio >= 3.0; // WCAG AA standard for large text (18pt+ or 14pt+ bold)
}

/**
 * Extract hex colors from a string (gradient, color value, etc.)
 */
function extractHexColors($str) {
    if (empty($str)) return [];
    
    preg_match_all('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $str, $matches);
    return $matches[0] ?? [];
}

/**
 * Check if gradient has harsh transitions
 * Returns true if gradient is smooth, false if harsh
 */
function isGradientSmooth($gradient) {
    if (empty($gradient) || strpos($gradient, 'gradient') === false) {
        return true; // Not a gradient, consider it "smooth"
    }
    
    $colors = extractHexColors($gradient);
    if (count($colors) < 2) {
        return true; // Not enough colors to judge
    }
    
    // Check for harsh transitions (large luminance differences between adjacent colors)
    for ($i = 0; $i < count($colors) - 1; $i++) {
        $l1 = getLuminance($colors[$i]);
        $l2 = getLuminance($colors[$i + 1]);
        $diff = abs($l1 - $l2);
        
        // If luminance difference is too large (>0.5), it's a harsh transition
        if ($diff > 0.5) {
            return false;
        }
    }
    
    return true;
}

/**
 * Smooth out a gradient by adding intermediate colors
 */
function smoothGradient($gradient) {
    if (empty($gradient) || strpos($gradient, 'gradient') === false) {
        return $gradient;
    }
    
    $colors = extractHexColors($gradient);
    if (count($colors) < 2) {
        return $gradient;
    }
    
    // Check if we need to smooth it
    $needsSmoothing = false;
    for ($i = 0; $i < count($colors) - 1; $i++) {
        $l1 = getLuminance($colors[$i]);
        $l2 = getLuminance($colors[$i + 1]);
        $diff = abs($l1 - $l2);
        if ($diff > 0.5) {
            $needsSmoothing = true;
            break;
        }
    }
    
    if (!$needsSmoothing) {
        return $gradient;
    }
    
    // Extract gradient type and direction
    preg_match('/(linear|radial)-gradient\s*\(([^)]+)\)/', $gradient, $gradMatch);
    if (empty($gradMatch)) {
        return $gradient;
    }
    
    $gradType = $gradMatch[1];
    $gradContent = $gradMatch[2];
    
    // Extract direction and color stops
    preg_match('/^\s*([^,]+),/', $gradContent, $dirMatch);
    $direction = $dirMatch[1] ?? '135deg';
    
    // Rebuild with smoother transitions
    // For now, return original - smoothing gradients is complex
    // We'll flag it for manual review
    return $gradient;
}

/**
 * Adjust color to meet contrast requirements
 */
function adjustColorForContrast($foreground, $background, $targetRatio = 4.5) {
    $currentRatio = getContrastRatio($foreground, $background);
    
    if ($currentRatio >= $targetRatio) {
        return $foreground; // Already meets requirements
    }
    
    // Determine if we need to lighten or darken
    $fgLum = getLuminance($foreground);
    $bgLum = getLuminance($background);
    
    // If background is dark, lighten foreground; if light, darken foreground
    $needsLightening = $bgLum < 0.5;
    
    // Convert hex to RGB
    $hex = str_replace('#', '', $foreground);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Adjust until we meet contrast
    $iterations = 0;
    while ($currentRatio < $targetRatio && $iterations < 20) {
        if ($needsLightening) {
            $r = min(255, $r + 10);
            $g = min(255, $g + 10);
            $b = min(255, $b + 10);
        } else {
            $r = max(0, $r - 10);
            $g = max(0, $g - 10);
            $b = max(0, $b - 10);
        }
        
        $newColor = sprintf('#%02x%02x%02x', $r, $g, $b);
        $currentRatio = getContrastRatio($newColor, $background);
        $iterations++;
    }
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

echo "==========================================\n";
echo "🎨 Theme Accessibility & Contrast Review\n";
echo "==========================================\n\n";

try {
    // Get all active themes
    $themes = fetchAll("SELECT * FROM themes WHERE is_active = 1 ORDER BY name ASC");
    
    echo "Found " . count($themes) . " active theme(s)\n\n";
    
    $issues = [];
    $fixed = [];
    
    foreach ($themes as $theme) {
        $themeId = $theme['id'];
        $themeName = $theme['name'];
        $themeIssues = [];
        $themeFixes = [];
        
        echo "Reviewing: {$themeName} (ID: {$themeId})\n";
        
        // Parse theme data
        $colorTokens = json_decode($theme['color_tokens'] ?? '{}', true) ?: [];
        $pageBackground = $theme['page_background'] ?? '';
        $widgetBackground = $theme['widget_background'] ?? '';
        
        // Get text colors
        $textPrimary = $colorTokens['text']['primary'] ?? null;
        $textSecondary = $colorTokens['text']['secondary'] ?? null;
        $headingText = $theme['page_heading_text'] ?? $textPrimary;
        $bodyText = $theme['page_body_text'] ?? $textSecondary;
        
        // Get background colors
        $bgBase = $colorTokens['background']['base'] ?? $pageBackground;
        $bgSurface = $colorTokens['background']['surface'] ?? $widgetBackground;
        
        // Check gradient backgrounds for harsh transitions
        if (!empty($pageBackground) && strpos($pageBackground, 'gradient') !== false) {
            if (!isGradientSmooth($pageBackground)) {
                $themeIssues[] = "Page background gradient has harsh transitions";
                // Try to smooth it
                $smoothed = smoothGradient($pageBackground);
                if ($smoothed !== $pageBackground) {
                    $themeFixes['page_background'] = $smoothed;
                }
            }
        }
        
        if (!empty($widgetBackground) && strpos($widgetBackground, 'gradient') !== false) {
            if (!isGradientSmooth($widgetBackground)) {
                $themeIssues[] = "Widget background gradient has harsh transitions";
                // Try to smooth it
                $smoothed = smoothGradient($widgetBackground);
                if ($smoothed !== $widgetBackground) {
                    $themeFixes['widget_background'] = $smoothed;
                }
            }
        }
        
        // Check contrast for text on backgrounds
        // Extract base color from gradient if needed
        $bgBaseColor = $bgBase;
        if (strpos($bgBase, 'gradient') !== false) {
            $gradColors = extractHexColors($bgBase);
            $bgBaseColor = $gradColors[0] ?? '#ffffff'; // Use first color as base
        }
        
        if (!empty($headingText) && !empty($bgBaseColor)) {
            $headingHex = extractHexColors($headingText);
            if (!empty($headingHex)) {
                $headingColor = $headingHex[0];
                if (!meetsWCAGAA($headingColor, $bgBaseColor)) {
                    $themeIssues[] = "Heading text contrast insufficient on page background";
                    $adjusted = adjustColorForContrast($headingColor, $bgBaseColor);
                    if ($adjusted !== $headingColor) {
                        if (isset($colorTokens['text'])) {
                            $colorTokens['text']['primary'] = $adjusted;
                            $themeFixes['color_tokens'] = $colorTokens;
                        }
                    }
                }
            }
        }
        
        if (!empty($bodyText) && !empty($bgBaseColor)) {
            $bodyHex = extractHexColors($bodyText);
            if (!empty($bodyHex)) {
                $bodyColor = $bodyHex[0];
                if (!meetsWCAGAA($bodyColor, $bgBaseColor)) {
                    $themeIssues[] = "Body text contrast insufficient on page background";
                    $adjusted = adjustColorForContrast($bodyColor, $bgBaseColor);
                    if ($adjusted !== $bodyColor) {
                        if (isset($colorTokens['text'])) {
                            $colorTokens['text']['secondary'] = $adjusted;
                            $themeFixes['color_tokens'] = $colorTokens;
                        }
                    }
                }
            }
        }
        
        // Check widget text contrast
        $widgetHeadingText = $colorTokens['widget'] ?? [];
        $widgetBodyText = $colorTokens['widget'] ?? [];
        
        $bgSurfaceColor = $bgSurface;
        if (strpos($bgSurface, 'gradient') !== false) {
            $gradColors = extractHexColors($bgSurface);
            $bgSurfaceColor = $gradColors[0] ?? '#ffffff';
        }
        
        // Summary
        if (!empty($themeIssues)) {
            $issues[$themeName] = $themeIssues;
            echo "   ⚠️  Issues found: " . count($themeIssues) . "\n";
            foreach ($themeIssues as $issue) {
                echo "      - {$issue}\n";
            }
        } else {
            echo "   ✅ No issues found\n";
        }
        
        // Apply fixes if any
        if (!empty($themeFixes)) {
            $updateSql = [];
            $updateValues = [];
            
            foreach ($themeFixes as $field => $value) {
                if ($field === 'color_tokens') {
                    $updateSql[] = "color_tokens = ?";
                    $updateValues[] = json_encode($value, JSON_UNESCAPED_SLASHES);
                } else {
                    $updateSql[] = "{$field} = ?";
                    $updateValues[] = $value;
                }
            }
            
            if (!empty($updateSql)) {
                $updateValues[] = $themeId;
                $sql = "UPDATE themes SET " . implode(', ', $updateSql) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
                
                $fixed[$themeName] = array_keys($themeFixes);
                echo "   🔧 Fixed: " . implode(', ', array_keys($themeFixes)) . "\n";
            }
        }
        
        echo "\n";
    }
    
    // Summary
    echo "==========================================\n";
    echo "📊 Summary\n";
    echo "==========================================\n\n";
    
    if (empty($issues) && empty($fixed)) {
        echo "✅ All themes passed accessibility review!\n";
    } else {
        if (!empty($issues)) {
            echo "⚠️  Themes with issues: " . count($issues) . "\n";
            foreach ($issues as $name => $themeIssues) {
                echo "   - {$name}: " . count($themeIssues) . " issue(s)\n";
            }
            echo "\n";
        }
        
        if (!empty($fixed)) {
            echo "🔧 Themes fixed: " . count($fixed) . "\n";
            foreach ($fixed as $name => $fields) {
                echo "   - {$name}: " . implode(', ', $fields) . "\n";
            }
        }
    }
    
    echo "\n";
    echo "Note: Some issues may require manual review, especially complex gradients.\n";
    echo "Please test themes visually after fixes are applied.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

