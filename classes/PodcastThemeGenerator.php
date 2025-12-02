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

        // CRITICAL: Use all 5 extracted colors from the podcast cover
        // These are the only colors that will be used throughout the theme
        $color1 = $colors[0]; // Most dominant - used for page background gradient start
        $color2 = isset($colors[1]) ? $colors[1] : $this->adjustBrightness($colors[0], -20); // Gradient end
        $color3 = isset($colors[2]) ? $colors[2] : $this->adjustBrightness($colors[0], -30); // Page title
        $color4 = isset($colors[3]) ? $colors[3] : $this->adjustBrightness($colors[0], -50); // Page body / widget background
        $color5 = isset($colors[4]) ? $colors[4] : $colors[0]; // Accent / widget border / effects

        // Map colors to theme properties using all 5 colors
        // CRITICAL: Use all 5 colors visibly throughout the theme
        $pageBackground = $color1; // Most dominant for gradient start
        $pageTitleColor = $color3; // Use color 3 for page title (will adjust for contrast if needed)
        $pageBodyColor = $color4; // Use color 4 for page body text (will adjust for contrast if needed)
        $widgetBackground = $color3; // Use color 3 for widget background (different from page background)
        $accentColor = $color5; // Use color 5 for accents
        $widgetBorderColor = $color2; // Use color 2 for widget borders (different from accent)
        $widgetTextColor = $color4; // Use color 4 for widget text

        // CRITICAL: Adjust colors for contrast WITHOUT replacing with black/white
        // Use gentle brightness adjustments to maintain the original color palette
        $pageTitleColor = $this->adjustColorForContrast($pageTitleColor, $pageBackground, 3.0);
        $pageBodyColor = $this->adjustColorForContrast($pageBodyColor, $pageBackground, 4.5);
        $widgetTextColor = $this->adjustColorForContrast($widgetTextColor, $widgetBackground, 4.5);

        // Create gradient background using only the first two colors
        $pageBackgroundGradient = "linear-gradient(135deg, {$color1} 0%, {$color2} 100%)";

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
                        'primary' => $pageTitleColor, // color3
                        'secondary' => $pageBodyColor // color4
                    ],
                    'background' => [
                        'primary' => $pageBackgroundGradient, // All 5 colors in gradient
                        'secondary' => $widgetBackground, // color3
                        'surface' => $color2, // Use color2 for surface
                        'surface_raised' => $this->adjustBrightness($color2, 10) // Slightly lighter color2
                    ],
                    'accent' => [
                        'primary' => $accentColor, // color5
                        'secondary' => $color2, // Use color2 as secondary accent
                        'muted' => $this->adjustBrightness($accentColor, -20) // Lighter color5
                    ],
                    'border' => [
                        'default' => $widgetBorderColor, // color2
                        'focus' => $accentColor, // color5
                        'subtle' => $color4 // Use color4 for subtle borders
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
            'page_background' => $pageBackgroundGradient, // All 5 colors in gradient
            'widget_background' => $widgetBackground, // color3
            'widget_border_color' => $widgetBorderColor, // color2
            'page_primary_font' => $pageHeadingFont,
            'page_secondary_font' => $pageBodyFont,
            'widget_primary_font' => $widgetHeadingFont,
            'widget_secondary_font' => $widgetBodyFont,
            // Page title effects: Use color2 for shadow and color5 for border (use 2 different colors)
            'page_name_effect' => 'shadow',
            'page_name_shadow_color' => $color2, // Use color2 from cover
            'page_name_shadow_intensity' => 0.8,
            'page_name_shadow_depth' => 3,
            'page_name_shadow_blur' => 6,
            'page_name_border_color' => $color5, // Use color5 from cover (different from shadow)
            'page_name_border_width' => 3,
            // Profile image styling: Use color4 for shadow (another color)
            'profile_image_radius' => 15,
            'profile_image_effect' => 'shadow',
            'profile_image_shadow_color' => $color4, // Use color4 from cover
            'profile_image_shadow_intensity' => 0.4,
            'profile_image_shadow_depth' => 4,
            'profile_image_shadow_blur' => 12,
            // Widget styling: Use color5 for glow and color2 for border (use 2 different colors)
            'widget_styles' => [
                'border_width' => 2,
                'border_radius' => 12,
                'border_effect' => 'glow', // Use glow effect
                'glow_color' => $color5, // Use color5 from cover
                'glow_width' => 8,
                'glow_intensity' => 0.6,
                'border_glow_intensity' => 'subtle'
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
     * CRITICAL: Only shuffles the provided 5 colors - no new extraction or default colors
     * @param array $colors Array of exactly 5 hex colors (from extracted palette)
     * @return array Shuffled colors with validated contrast
     */
    public function shuffleColors($colors) {
        // CRITICAL: Require exactly 5 colors - these are the only colors to shuffle
        if (count($colors) !== 5) {
            throw new Exception('Exactly 5 colors are required for shuffling');
        }

        // Randomly shuffle the 5 color assignments
        $shuffled = $colors;
        shuffle($shuffled);

        // CRITICAL: Only adjust brightness of the shuffled colors - NEVER replace with black/white
        // We want to keep the original 5 colors, just adjust them slightly for contrast
        $pageBackground = $shuffled[0];
        
        // Adjust colors for contrast by lightening/darkening, but keep them within the 5-color palette
        // Use a gentler contrast adjustment that doesn't replace colors
        $shuffled[1] = $this->adjustColorForContrast($shuffled[1], $pageBackground, 3.0);
        $shuffled[2] = $this->adjustColorForContrast($shuffled[2], $pageBackground, 4.5);
        $shuffled[3] = $this->adjustColorForContrast($shuffled[3], $pageBackground, 2.5); // Widget bg can be lower contrast
        $shuffled[4] = $this->adjustColorForContrast($shuffled[4], $pageBackground, 2.0); // Accent can be lower contrast

        return $shuffled;
    }
    
    /**
     * Adjust color for contrast by lightening/darkening only - never replace with black/white
     * @param string $foreground Foreground color
     * @param string $background Background color
     * @param float $minRatio Minimum contrast ratio
     * @return string Adjusted color (always from the original palette, just adjusted)
     */
    private function adjustColorForContrast($foreground, $background, $minRatio) {
        $currentRatio = $this->calculateContrastRatio($foreground, $background);
        
        if ($currentRatio >= $minRatio) {
            return $foreground; // Already has enough contrast
        }

        // Calculate background luminance
        $bgLuminance = $this->getLuminance($background);
        
        // Only adjust brightness - never replace with black/white
        // Try adjusting the original color first
        if ($bgLuminance > 0.5) {
            // Light background - darken foreground (try multiple levels)
            for ($adjustment = -10; $adjustment >= -50; $adjustment -= 10) {
                $adjusted = $this->adjustBrightness($foreground, $adjustment);
                $adjustedRatio = $this->calculateContrastRatio($adjusted, $background);
                if ($adjustedRatio >= $minRatio) {
                    return $adjusted;
                }
            }
        } else {
            // Dark background - lighten foreground (try multiple levels)
            for ($adjustment = 10; $adjustment <= 50; $adjustment += 10) {
                $adjusted = $this->adjustBrightness($foreground, $adjustment);
                $adjustedRatio = $this->calculateContrastRatio($adjusted, $background);
                if ($adjustedRatio >= $minRatio) {
                    return $adjusted;
                }
            }
        }
        
        // If we can't achieve contrast by adjusting, return the original color
        // Better to have low contrast than replace with black/white
        return $foreground;
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

