<?php
/**
 * Color Extractor Class
 * Extracts dominant colors from images using color quantization
 * Podn.Bio
 */

require_once __DIR__ . '/../config/constants.php';

class ColorExtractor {
    
    /**
     * Extract dominant colors from an image
     * Returns primary, secondary, and accent colors
     * @param string $imagePath Full path to image file
     * @param int $colorCount Number of colors to extract (default 5, will pick top 3)
     * @return array ['success' => bool, 'colors' => array|null, 'error' => string|null]
     */
    public function extractColors($imagePath, $colorCount = 5) {
        if (!extension_loaded('gd')) {
            return ['success' => false, 'colors' => null, 'error' => 'GD library not available'];
        }
        
        // Check if file exists
        if (!file_exists($imagePath)) {
            return ['success' => false, 'colors' => null, 'error' => 'Image file not found'];
        }
        
        // Get image info
        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            return ['success' => false, 'colors' => null, 'error' => 'Invalid image file'];
        }
        
        list($width, $height, $imageType) = $imageInfo;
        
        // Load image based on type
        $image = null;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($imagePath);
                }
                break;
            default:
                return ['success' => false, 'colors' => null, 'error' => 'Unsupported image format'];
        }
        
        if (!$image) {
            return ['success' => false, 'colors' => null, 'error' => 'Failed to load image'];
        }
        
        // Resize image for faster processing (max 200x200)
        $maxDimension = 200;
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }
        
        // Extract color palette using color quantization
        $colors = $this->extractColorPalette($image, $width, $height, $colorCount);
        
        // Clean up
        imagedestroy($image);
        
        if (empty($colors)) {
            return ['success' => false, 'colors' => null, 'error' => 'Failed to extract colors'];
        }
        
        // Map colors to primary, secondary, accent
        $mappedColors = $this->mapColorsToTheme($colors);
        
        return [
            'success' => true,
            'colors' => $mappedColors,
            'error' => null
        ];
    }
    
    /**
     * Extract color palette from image using sampling and quantization
     * @param resource $image GD image resource
     * @param int $width Image width
     * @param int $height Image height
     * @param int $maxColors Maximum colors to extract
     * @return array Array of hex colors with frequency
     */
    private function extractColorPalette($image, $width, $height, $maxColors) {
        $colorCounts = [];
        $sampleRate = 5; // Sample every 5th pixel for performance
        
        // Sample pixels
        for ($y = 0; $y < $height; $y += $sampleRate) {
            for ($x = 0; $x < $width; $x += $sampleRate) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Skip very dark or very light colors (likely background/noise)
                $brightness = ($r + $g + $b) / 3;
                if ($brightness < 20 || $brightness > 235) {
                    continue;
                }
                
                // Quantize colors to reduce similar shades
                $r = round($r / 16) * 16;
                $g = round($g / 16) * 16;
                $b = round($b / 16) * 16;
                
                $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
                
                if (!isset($colorCounts[$hex])) {
                    $colorCounts[$hex] = 0;
                }
                $colorCounts[$hex]++;
            }
        }
        
        // Sort by frequency
        arsort($colorCounts);
        
        // Get top colors
        $topColors = array_slice(array_keys($colorCounts), 0, $maxColors, true);
        
        return $topColors;
    }
    
    /**
     * Map extracted colors to theme color roles (primary, secondary, accent)
     * @param array $colors Array of hex color strings
     * @return array ['primary' => hex, 'secondary' => hex, 'accent' => hex]
     */
    private function mapColorsToTheme($colors) {
        if (empty($colors)) {
            return [
                'primary' => '#000000',
                'secondary' => '#ffffff',
                'accent' => '#0066ff'
            ];
        }
        
        // Convert hex to RGB for calculations
        $rgbColors = array_map(function($hex) {
            return $this->hexToRgb($hex);
        }, $colors);
        
        // Calculate brightness and saturation for each color
        $colorData = [];
        foreach ($rgbColors as $index => $rgb) {
            $hex = $colors[$index];
            $brightness = $this->calculateBrightness($rgb);
            $saturation = $this->calculateSaturation($rgb);
            
            $colorData[] = [
                'hex' => $hex,
                'rgb' => $rgb,
                'brightness' => $brightness,
                'saturation' => $saturation
            ];
        }
        
        // Sort by saturation (most saturated = best accent)
        usort($colorData, function($a, $b) {
            return $b['saturation'] - $a['saturation'];
        });
        
        // Find primary (darkest or most saturated dark color)
        $primary = null;
        foreach ($colorData as $color) {
            if ($color['brightness'] < 100 || $color['saturation'] > 0.5) {
                $primary = $color['hex'];
                break;
            }
        }
        if (!$primary) {
            $primary = $colorData[0]['hex'];
        }
        
        // Find secondary (lightest color)
        usort($colorData, function($a, $b) {
            return $b['brightness'] - $a['brightness'];
        });
        $secondary = $colorData[0]['hex'];
        
        // Find accent (most saturated color that's not primary or secondary)
        usort($colorData, function($a, $b) {
            return $b['saturation'] - $a['saturation'];
        });
        $accent = null;
        foreach ($colorData as $color) {
            if ($color['hex'] !== $primary && $color['hex'] !== $secondary && $color['saturation'] > 0.3) {
                $accent = $color['hex'];
                break;
            }
        }
        if (!$accent) {
            // Use second most saturated if no good accent found
            $accent = isset($colorData[1]) ? $colorData[1]['hex'] : '#0066ff';
        }
        
        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent
        ];
    }
    
    /**
     * Convert hex color to RGB array
     * @param string $hex Hex color (e.g., #ff0000)
     * @return array ['r' => int, 'g' => int, 'b' => int]
     */
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        
        // Handle 3-digit hex
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Calculate color brightness (0-255)
     * @param array $rgb RGB array
     * @return float Brightness value
     */
    private function calculateBrightness($rgb) {
        return ($rgb['r'] * 0.299 + $rgb['g'] * 0.587 + $rgb['b'] * 0.114);
    }
    
    /**
     * Calculate color saturation (0-1)
     * @param array $rgb RGB array
     * @return float Saturation value
     */
    private function calculateSaturation($rgb) {
        $max = max($rgb['r'], $rgb['g'], $rgb['b']);
        $min = min($rgb['r'], $rgb['g'], $rgb['b']);
        
        if ($max == 0) {
            return 0;
        }
        
        return ($max - $min) / $max;
    }
    
    /**
     * Extract colors from image URL (downloads image first)
     * @param string $imageUrl URL to image
     * @param int $colorCount Number of colors to extract
     * @return array ['success' => bool, 'colors' => array|null, 'error' => string|null]
     */
    public function extractColorsFromUrl($imageUrl, $colorCount = 5) {
        // Download image to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'color_extract_');
        
        $imageData = @file_get_contents($imageUrl);
        if ($imageData === false) {
            return ['success' => false, 'colors' => null, 'error' => 'Failed to download image'];
        }
        
        if (file_put_contents($tempFile, $imageData) === false) {
            return ['success' => false, 'colors' => null, 'error' => 'Failed to save temporary image'];
        }
        
        // Extract colors
        $result = $this->extractColors($tempFile, $colorCount);
        
        // Clean up temporary file
        @unlink($tempFile);
        
        return $result;
    }
}

