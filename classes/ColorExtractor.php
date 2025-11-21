<?php
/**
 * Color Extractor Class
 * Extracts dominant colors from images for theme generation
 * PodaBio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class ColorExtractor {
    /**
     * Extract dominant colors from an image URL
     * @param string $imageUrl URL of the image
     * @param int $count Number of colors to extract (default: 5)
     * @return array Array of hex color strings sorted by dominance
     */
    public function extractColors($imageUrl, $count = 5) {
        if (empty($imageUrl)) {
            return $this->getDefaultColors($count);
        }

        try {
            // Download image
            $imageData = $this->fetchImage($imageUrl);
            if (!$imageData) {
                error_log("ColorExtractor: Failed to fetch image from: " . $imageUrl);
                return $this->getDefaultColors($count);
            }

            // Create image resource
            $image = @imagecreatefromstring($imageData);
            if (!$image) {
                error_log("ColorExtractor: Failed to create image from data");
                return $this->getDefaultColors($count);
            }

            // Resize for performance (max 200x200)
            $width = imagesx($image);
            $height = imagesy($image);
            $maxSize = 200;
            
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
                $width = $newWidth;
                $height = $newHeight;
            }

            // Extract colors using quantize method
            $colors = $this->quantizeColors($image, $width, $height, $count);
            
            imagedestroy($image);
            
            return $colors;
        } catch (Exception $e) {
            error_log("ColorExtractor: Exception - " . $e->getMessage());
            return $this->getDefaultColors($count);
        }
    }

    /**
     * Fetch image from URL
     * @param string $url Image URL
     * @return string|false Image data or false on failure
     */
    private function fetchImage($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'PodaBio/1.0',
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);

        $imageData = @file_get_contents($url, false, $context);
        return $imageData !== false ? $imageData : false;
    }

    /**
     * Quantize colors from image using simple color quantization
     * @param resource $image GD image resource
     * @param int $width Image width
     * @param int $height Image height
     * @param int $count Number of colors to extract
     * @return array Array of hex colors
     */
    private function quantizeColors($image, $width, $height, $count) {
        $colorMap = [];
        $sampleRate = max(1, (int)sqrt(($width * $height) / 10000)); // Sample every Nth pixel

        // Sample pixels
        for ($y = 0; $y < $height; $y += $sampleRate) {
            for ($x = 0; $x < $width; $x += $sampleRate) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Quantize to reduce color space (16 levels per channel)
                $qr = (int)($r / 16) * 16;
                $qg = (int)($g / 16) * 16;
                $qb = (int)($b / 16) * 16;
                
                $key = sprintf('%03d%03d%03d', $qr, $qg, $qb);
                
                if (!isset($colorMap[$key])) {
                    $colorMap[$key] = [
                        'r' => $r,
                        'g' => $g,
                        'b' => $b,
                        'count' => 0
                    ];
                }
                $colorMap[$key]['count']++;
            }
        }

        // Sort by frequency
        uasort($colorMap, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Convert to hex and take top N
        $colors = [];
        $taken = 0;
        foreach ($colorMap as $color) {
            if ($taken >= $count) break;
            
            $hex = sprintf('#%02x%02x%02x', $color['r'], $color['g'], $color['b']);
            
            // Skip very similar colors
            $isSimilar = false;
            foreach ($colors as $existing) {
                if ($this->colorDistance($hex, $existing) < 30) {
                    $isSimilar = true;
                    break;
                }
            }
            
            if (!$isSimilar) {
                $colors[] = $hex;
                $taken++;
            }
        }

        // Fill remaining slots with default colors if needed
        while (count($colors) < $count) {
            $colors[] = $this->getDefaultColors(1)[0];
        }

        return array_slice($colors, 0, $count);
    }

    /**
     * Calculate color distance (Euclidean distance in RGB space)
     * @param string $color1 Hex color
     * @param string $color2 Hex color
     * @return float Distance
     */
    private function colorDistance($color1, $color2) {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);
        
        $dr = $rgb1['r'] - $rgb2['r'];
        $dg = $rgb1['g'] - $rgb2['g'];
        $db = $rgb1['b'] - $rgb2['b'];
        
        return sqrt($dr * $dr + $dg * $dg + $db * $db);
    }

    /**
     * Convert hex color to RGB
     * @param string $hex Hex color (#RRGGBB)
     * @return array ['r' => int, 'g' => int, 'b' => int]
     */
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
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
     * Get default color palette
     * @param int $count Number of colors
     * @return array Default colors
     */
    private function getDefaultColors($count) {
        $defaults = [
            '#2563eb', // Blue
            '#1d4ed8', // Darker blue
            '#3b82f6', // Lighter blue
            '#60a5fa', // Light blue
            '#93c5fd'  // Very light blue
        ];
        return array_slice($defaults, 0, $count);
    }
}

