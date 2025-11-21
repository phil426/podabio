<?php
/**
 * Podcast Theme Generator Class
 * Generates themes from podcast cover art colors
 * PodaBio
 */

require_once __DIR__ . '/ColorExtractor.php';
require_once __DIR__ . '/ThemeCSSGenerator.php';

class PodcastThemeGenerator {
    private $colorExtractor;
    
    public function __construct() {
        $this->colorExtractor = new ColorExtractor();
    }

    /**
     * Generate theme from color palette
     * @param array $colors Array of 2-5 hex colors
     * @param string|null $podcastName Podcast name
     * @param string|null $podcastDescription Podcast description
     * @return array Theme data structure
     */
    public function generateTheme($colors, $podcastName = null, $podcastDescription = null) {
        // Ensure we have at least 2 colors, pad to 5 if needed
        if (count($colors) < 2) {
            $colors = array_merge($colors, $this->getDefaultColors(2 - count($colors)));
        }
        if (count($colors) < 5) {
            $colors = array_merge($colors, $this->getDefaultColors(5 - count($colors)));
        }

        // Map colors to theme properties
        $pageBackground = $colors[0]; // Most dominant
        $pageTitleColor = isset($colors[1]) ? $colors[1] : $this->adjustBrightness($colors[0], -30);
        $pageBodyColor = isset($colors[2]) ? $colors[2] : $this->adjustBrightness($colors[0], -50);
        $widgetBackground = isset($colors[3]) ? $colors[3] : $this->adjustBrightness($colors[0], 10);
        $accentColor = isset($colors[4]) ? $colors[4] : $colors[0];

        // Ensure contrast ratios using our own methods
        $pageTitleColor = $this->ensureContrast($pageTitleColor, $pageBackground, 3.0);
        $pageBodyColor = $this->ensureContrast($pageBodyColor, $pageBackground, 4.5);
        $widgetTextColor = $this->ensureContrast($pageBodyColor, $widgetBackground, 4.5);

        // Create gradient background from first two colors
        $gradientStart = $pageBackground;
        $gradientEnd = isset($colors[1]) ? $colors[1] : $this->adjustBrightness($pageBackground, -20);
        $pageBackgroundGradient = "linear-gradient(135deg, {$gradientStart} 0%, {$gradientEnd} 100%)";

        // Popular font pairs
        // Page fonts: Playfair Display + Source Sans Pro (elegant)
        // Widget fonts: Montserrat + Open Sans (modern)
        $pageHeadingFont = 'Playfair Display';
        $pageBodyFont = 'Source Sans Pro';
        $widgetHeadingFont = 'Montserrat';
        $widgetBodyFont = 'Open Sans';

        // Build theme structure with enhanced styling
        $themeData = [
            'name' => $podcastName ? $this->truncateString($podcastName, 60) : 'Podcast Theme',
            'color_tokens' => [
                'gradient' => [
                    'primary' => [
                        'type' => 'gradient',
                        'value' => $pageBackgroundGradient
                    ],
                    'secondary' => [
                        'type' => 'solid',
                        'value' => $this->adjustBrightness($accentColor, -20)
                    ]
                ],
                'semantic' => [
                    'text' => [
                        'primary' => $pageTitleColor,
                        'secondary' => $pageBodyColor
                    ],
                    'background' => [
                        'primary' => $pageBackgroundGradient,
                        'secondary' => $widgetBackground
                    ],
                    'accent' => [
                        'primary' => $accentColor
                    ]
                ],
                'core' => [
                    'typography' => [
                        'color' => [
                            'heading' => $pageTitleColor,
                            'body' => $pageBodyColor,
                            'widget_heading' => $widgetTextColor,
                            'widget_body' => $widgetTextColor
                        ]
                    ]
                ]
            ],
            'typography_tokens' => [
                'font' => [
                    'heading' => $pageHeadingFont,
                    'body' => $pageBodyFont,
                    'widget_heading' => $widgetHeadingFont,
                    'widget_body' => $widgetBodyFont
                ],
                'color' => [
                    'heading' => $pageTitleColor,
                    'body' => $pageBodyColor,
                    'widget_heading' => $widgetTextColor,
                    'widget_body' => $widgetTextColor
                ]
            ],
            'page_background' => $pageBackgroundGradient,
            'widget_background' => $widgetBackground,
            'widget_border_color' => $this->adjustBrightness($widgetBackground, -15),
            'page_primary_font' => $pageHeadingFont,
            'page_secondary_font' => $pageBodyFont,
            'widget_primary_font' => $widgetHeadingFont,
            'widget_secondary_font' => $widgetBodyFont,
            // Page title effects: 3px outline and drop shadow
            'page_name_effect' => 'shadow',
            'page_name_shadow_color' => $this->adjustBrightness($pageTitleColor, -40),
            'page_name_shadow_intensity' => 0.8,
            'page_name_shadow_depth' => 3,
            'page_name_shadow_blur' => 6,
            'page_name_border_color' => $this->adjustBrightness($pageTitleColor, -20),
            'page_name_border_width' => 3,
            // Profile image styling: 15% radius, moderate drop shadow
            'profile_image_radius' => 15,
            'profile_image_effect' => 'shadow',
            'profile_image_shadow_color' => '#000000',
            'profile_image_shadow_intensity' => 0.4,
            'profile_image_shadow_depth' => 4,
            'profile_image_shadow_blur' => 12,
            // Widget styling: glow and border
            'widget_styles' => [
                'border_width' => 2,
                'border_radius' => 12,
                'glow_enabled' => true,
                'glow_color' => $accentColor,
                'glow_width' => 8,
                'glow_intensity' => 0.6,
                'glow_blur' => 'medium'
            ]
        ];

        // Add podcast data if provided
        if ($podcastName) {
            $themeData['podcast_name'] = $this->truncateString($podcastName, 30);
        }
        if ($podcastDescription) {
            $themeData['podcast_description'] = $this->truncateString($podcastDescription, 113);
        }

        return $themeData;
    }

    /**
     * Shuffle colors while maintaining contrast ratios
     * @param array $colors Array of 2-5 hex colors
     * @return array Shuffled colors with validated contrast
     */
    public function shuffleColors($colors) {
        // Ensure we have at least 2 colors, pad to 5 if needed
        if (count($colors) < 2) {
            $colors = array_merge($colors, $this->getDefaultColors(2 - count($colors)));
        }
        if (count($colors) < 5) {
            $colors = array_merge($colors, $this->getDefaultColors(5 - count($colors)));
        }

        // Randomly shuffle color assignments
        $shuffled = $colors;
        shuffle($shuffled);

        // Ensure contrast after shuffle
        $pageBackground = $shuffled[0];
        $shuffled[1] = $this->ensureContrast($shuffled[1], $pageBackground, 3.0);
        $shuffled[2] = $this->ensureContrast($shuffled[2], $pageBackground, 4.5);
        $shuffled[3] = $this->ensureContrast($shuffled[3], $pageBackground, 2.5); // Widget bg can be lower contrast
        $shuffled[4] = $this->ensureContrast($shuffled[4], $pageBackground, 2.0); // Accent can be lower contrast

        return $shuffled;
    }

    /**
     * Ensure color meets minimum contrast ratio
     * @param string $foreground Foreground color
     * @param string $background Background color
     * @param float $minRatio Minimum contrast ratio
     * @return string Adjusted color
     */
    private function ensureContrast($foreground, $background, $minRatio) {
        $currentRatio = $this->calculateContrastRatio($foreground, $background);
        
        if ($currentRatio >= $minRatio) {
            return $foreground;
        }

        // Calculate optimal color based on background luminance
        $bgLuminance = $this->getLuminance($background);
        
        // Try white or black first
        $whiteContrast = $this->calculateContrastRatio('#ffffff', $background);
        $blackContrast = $this->calculateContrastRatio('#000000', $background);
        
        if ($bgLuminance < 0.5 && $whiteContrast >= $minRatio) {
            // Dark background - use white
            return '#ffffff';
        } elseif ($bgLuminance >= 0.5 && $blackContrast >= $minRatio) {
            // Light background - use black
            return '#000000';
        }

        // Manual adjustment: lighten or darken based on background
        if ($bgLuminance > 0.5) {
            // Light background - darken foreground
            $adjusted = $this->adjustBrightness($foreground, -30);
            $adjustedRatio = $this->calculateContrastRatio($adjusted, $background);
            if ($adjustedRatio >= $minRatio) {
                return $adjusted;
            }
            // If still not enough, use dark gray
            return '#1a1a1a';
        } else {
            // Dark background - lighten foreground
            $adjusted = $this->adjustBrightness($foreground, 30);
            $adjustedRatio = $this->calculateContrastRatio($adjusted, $background);
            if ($adjustedRatio >= $minRatio) {
                return $adjusted;
            }
            // If still not enough, use light gray
            return '#f0f0f0';
        }
    }

    /**
     * Calculate contrast ratio between two colors
     * @param string $color1 First color
     * @param string $color2 Second color
     * @return float Contrast ratio
     */
    private function calculateContrastRatio($color1, $color2) {
        $l1 = $this->getLuminance($color1);
        $l2 = $this->getLuminance($color2);
        
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        
        if ($darker == 0) return 21; // Maximum contrast
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get luminance of a color
     * @param string $color Hex color
     * @return float Luminance (0-1)
     */
    private function getLuminance($color) {
        if (!preg_match('/^#?[0-9a-fA-F]{3,6}$/', $color)) {
            return 0.5;
        }
        
        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        $r = hexdec(substr($color, 0, 2)) / 255;
        $g = hexdec(substr($color, 2, 2)) / 255;
        $b = hexdec(substr($color, 4, 2)) / 255;
        
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Adjust brightness of a color
     * @param string $color Hex color
     * @param int $amount Amount to adjust (-100 to 100)
     * @return string Adjusted hex color
     */
    private function adjustBrightness($color, $amount) {
        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        $r = max(0, min(255, $r + $amount));
        $g = max(0, min(255, $g + $amount));
        $b = max(0, min(255, $b + $amount));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Truncate string to max length
     * @param string $str String to truncate
     * @param int $maxLength Maximum length
     * @return string Truncated string
     */
    public function truncateString($str, $maxLength) {
        if (strlen($str) <= $maxLength) {
            return $str;
        }
        return substr($str, 0, $maxLength - 3) . '...';
    }

    /**
     * Map podcast data to page fields
     * @param string|null $podcastName Podcast name
     * @param string|null $podcastDescription Podcast description
     * @return array Page data fields
     */
    public function mapPodcastData($podcastName = null, $podcastDescription = null) {
        $pageData = [];
        
        if ($podcastName) {
            $pageData['podcast_name'] = $this->truncateString($podcastName, 30);
        }
        
        if ($podcastDescription) {
            $pageData['podcast_description'] = $this->truncateString($podcastDescription, 113);
        }
        
        return $pageData;
    }

    /**
     * Get default colors
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

