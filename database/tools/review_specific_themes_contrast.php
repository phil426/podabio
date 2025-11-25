<?php
/**
 * Review Specific Themes for Contrast
 * Checks social icon and description text contrast for Maui Sundown and Moonlight
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

$pdo = getDB();

// WCAG contrast functions
function getLuminance($hex) {
    $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) return 0.5;
    
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    
    $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
    
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function getContrastRatio($color1, $color2) {
    $lum1 = getLuminance($color1);
    $lum2 = getLuminance($color2);
    $lighter = max($lum1, $lum2);
    $darker = min($lum1, $lum2);
    return ($lighter + 0.05) / ($darker + 0.05);
}

function meetsWCAGAA($color1, $color2) {
    return getContrastRatio($color1, $color2) >= 4.5;
}

function extractHexFromGradient($gradient) {
    if (preg_match_all('/#([0-9a-fA-F]{6})/i', $gradient, $matches)) {
        return array_map(function($m) { return '#' . $m; }, $matches[1]);
    }
    return [];
}

function getDominantColor($background) {
    if (strpos($background, 'gradient') !== false) {
        $colors = extractHexFromGradient($background);
        if (!empty($colors)) {
            return $colors[0];
        }
    }
    if (preg_match('/#([0-9a-fA-F]{6})/i', $background, $match)) {
        return '#' . $match[1];
    }
    return '#ffffff';
}

function isLightColor($hex) {
    $lum = getLuminance($hex);
    return $lum > 0.5;
}

try {
    echo "🎨 Contrast Review: Maui Sundown & Moonlight\n";
    echo "═══════════════════════════════════════════════════════\n\n";
    
    $themesToReview = ['Maui Sundown', 'Moonlight'];
    $updated = 0;
    
    foreach ($themesToReview as $themeName) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Reviewing: {$themeName}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $stmt = $pdo->prepare("SELECT * FROM themes WHERE name = ? AND is_active = 1");
        $stmt->execute([$themeName]);
        $theme = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$theme) {
            echo "❌ Theme not found.\n\n";
            continue;
        }
        
        $themeId = $theme['id'];
        $needsUpdate = false;
        $updates = [];
        $params = [];
        $issues = [];
        
        // Parse tokens
        $typographyTokens = [];
        $iconographyTokens = [];
        
        if (!empty($theme['typography_tokens'])) {
            $typographyTokens = is_string($theme['typography_tokens'])
                ? json_decode($theme['typography_tokens'], true) ?: []
                : $theme['typography_tokens'];
        }
        
        if (!empty($theme['iconography_tokens'])) {
            $iconographyTokens = is_string($theme['iconography_tokens'])
                ? json_decode($theme['iconography_tokens'], true) ?: []
                : $theme['iconography_tokens'];
        }
        
        // Get page background
        $pageBackground = $theme['page_background'] ?? '#ffffff';
        $dominantBgColor = getDominantColor($pageBackground);
        $isLightBg = isLightColor($dominantBgColor);
        
        echo "Page Background: {$pageBackground}\n";
        echo "Dominant Color: {$dominantBgColor} (" . ($isLightBg ? 'Light' : 'Dark') . ")\n\n";
        
        // 1. Check Description Text (Body Text) Contrast
        echo "1. Description Text (Body) Contrast:\n";
        $bodyColor = $typographyTokens['color']['body'] ?? '#4b5563';
        $bodyContrast = getContrastRatio($dominantBgColor, $bodyColor);
        $meetsAA = meetsWCAGAA($dominantBgColor, $bodyColor);
        
        echo "   Current body color: {$bodyColor}\n";
        echo "   Contrast ratio: " . number_format($bodyContrast, 2) . ":1\n";
        echo "   WCAG AA compliant: " . ($meetsAA ? '✅ Yes' : '❌ No (needs 4.5:1)') . "\n";
        
        if (!$meetsAA) {
            // Fix contrast
            if ($isLightBg) {
                $newBodyColor = '#1e293b'; // Dark slate
            } else {
                $newBodyColor = '#e2e8f0'; // Light slate
            }
            $typographyTokens['color']['body'] = $newBodyColor;
            $newContrast = getContrastRatio($dominantBgColor, $newBodyColor);
            $issues[] = "Body text contrast insufficient ({$bodyContrast}:1) → updated to {$newBodyColor} ({$newContrast}:1)";
            $needsUpdate = true;
            echo "   ⚠️  Updating to: {$newBodyColor} (contrast: " . number_format($newContrast, 2) . ":1)\n";
        }
        echo "\n";
        
        // 2. Check Social Icon Contrast
        echo "2. Social Icon Contrast:\n";
        $iconColor = $iconographyTokens['color'] ?? '#2563eb';
        $iconContrast = getContrastRatio($dominantBgColor, $iconColor);
        $iconMeetsAA = meetsWCAGAA($dominantBgColor, $iconColor);
        
        echo "   Current icon color: {$iconColor}\n";
        echo "   Contrast ratio: " . number_format($iconContrast, 2) . ":1\n";
        echo "   WCAG AA compliant: " . ($iconMeetsAA ? '✅ Yes' : '❌ No (needs 4.5:1)') . "\n";
        
        if (!$iconMeetsAA) {
            // Fix contrast
            if ($isLightBg) {
                $newIconColor = '#1e293b'; // Dark slate
            } else {
                $newIconColor = '#f8fafc'; // Light slate
            }
            
            if (empty($iconographyTokens)) {
                $iconographyTokens = [
                    'color' => $newIconColor,
                    'size' => '48px',
                    'spacing' => '0.75rem'
                ];
            } else {
                $iconographyTokens['color'] = $newIconColor;
            }
            
            $newIconContrast = getContrastRatio($dominantBgColor, $newIconColor);
            $issues[] = "Social icon contrast insufficient ({$iconContrast}:1) → updated to {$newIconColor} ({$newIconContrast}:1)";
            $needsUpdate = true;
            echo "   ⚠️  Updating to: {$newIconColor} (contrast: " . number_format($newIconContrast, 2) . ":1)\n";
        }
        echo "\n";
        
        // Update database if needed
        if ($needsUpdate) {
            if (!empty($typographyTokens)) {
                $updates[] = "typography_tokens = ?";
                $params[] = json_encode($typographyTokens, JSON_UNESCAPED_SLASHES);
            }
            
            if (!empty($iconographyTokens)) {
                $updates[] = "iconography_tokens = ?";
                $params[] = json_encode($iconographyTokens, JSON_UNESCAPED_SLASHES);
            }
            
            if (!empty($updates)) {
                $params[] = $themeId;
                $sql = "UPDATE themes SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $updated++;
                
                echo "✅ Theme updated!\n";
                if (!empty($issues)) {
                    echo "   Issues fixed:\n";
                    foreach ($issues as $issue) {
                        echo "   • $issue\n";
                    }
                }
            }
        } else {
            echo "✨ All contrast ratios meet WCAG AA standards. No changes needed.\n";
        }
        
        echo "\n";
    }
    
    echo "═══════════════════════════════════════════════════════\n";
    echo "✅ Review Complete!\n";
    echo "   Themes reviewed: " . count($themesToReview) . "\n";
    echo "   Themes updated: $updated\n";
    echo "═══════════════════════════════════════════════════════\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

