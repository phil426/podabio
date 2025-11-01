<?php
/**
 * Theme Helper Functions
 * Centralized theme-related helper functions
 * Podn.Bio
 */

require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/WidgetStyleManager.php';

/**
 * Get complete theme configuration
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Complete theme config with colors, fonts, page_background, widget_styles, spatial_effect
 */
function getThemeConfig($page, $theme = null) {
    $themeObj = new Theme();
    
    return [
        'colors' => $themeObj->getThemeColors($page, $theme),
        'fonts' => $themeObj->getThemeFonts($page, $theme),
        'page_background' => $themeObj->getPageBackground($page, $theme),
        'widget_styles' => $themeObj->getWidgetStyles($page, $theme),
        'spatial_effect' => $themeObj->getSpatialEffect($page, $theme)
    ];
}

/**
 * Get widget styles for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Widget styles with defaults applied
 */
function getWidgetStyles($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getWidgetStyles($page, $theme);
}

/**
 * Get page background for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return string Page background (color or gradient)
 */
function getPageBackground($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getPageBackground($page, $theme);
}

/**
 * Get spatial effect for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return string Spatial effect name
 */
function getSpatialEffect($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getSpatialEffect($page, $theme);
}

/**
 * Convert enum value to CSS value
 * @param string $enum Enum value (e.g., 'thin', 'subtle', 'rounded')
 * @param string $type Conversion type (border_width, shadow, glow_blur, glow_opacity, spacing, shape)
 * @return string CSS value
 */
function convertEnumToCSS($enum, $type) {
    $mappings = [
        'border_width' => [
            'thin' => '1px',
            'medium' => '2px',
            'thick' => '3px'
        ],
        'shadow' => [
            'none' => 'none',
            'subtle' => '0 2px 4px rgba(0, 0, 0, 0.05)',
            'pronounced' => '0 4px 12px rgba(0, 0, 0, 0.15)'
        ],
        'glow_blur' => [
            'none' => '0px',
            'subtle' => '8px',
            'pronounced' => '16px'
        ],
        'glow_opacity' => [
            'none' => '0',
            'subtle' => '0.5',
            'pronounced' => '0.8'
        ],
        'spacing' => [
            'tight' => '0.5rem',
            'comfortable' => '1rem',
            'spacious' => '1.5rem'
        ],
        'shape' => [
            'square' => '0px',
            'rounded' => '8px',
            'round' => '50px'
        ]
    ];
    
    if (!isset($mappings[$type])) {
        return '';
    }
    
    return $mappings[$type][$enum] ?? '';
}

/**
 * Parse gradient or color value
 * @param string $value Color or gradient string
 * @return array Parsed components or null if invalid
 */
function parseGradientOrColor($value) {
    if (empty($value) || !is_string($value)) {
        return null;
    }
    
    // Check if it's a gradient
    if (preg_match('/^(linear-gradient|radial-gradient|conic-gradient)\((.+)\)$/', $value, $matches)) {
        return [
            'type' => 'gradient',
            'function' => $matches[1],
            'value' => $value,
            'params' => $matches[2]
        ];
    }
    
    // Check if it's a CSS variable
    if (preg_match('/^var\(--([a-zA-Z0-9_-]+)\)$/', $value, $matches)) {
        return [
            'type' => 'variable',
            'value' => $value,
            'var_name' => $matches[1]
        ];
    }
    
    // Check if it's a hex color
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
        return [
            'type' => 'color',
            'value' => $value
        ];
    }
    
    return null;
}

/**
 * Check if value is a gradient
 * @param string $value Value to check
 * @return bool True if gradient
 */
function isGradient($value) {
    $parsed = parseGradientOrColor($value);
    return $parsed !== null && $parsed['type'] === 'gradient';
}

/**
 * Generate glow animation CSS
 * @param string $color Glow color
 * @param string $intensity Glow intensity (none, subtle, pronounced)
 * @return string CSS for glow animation
 */
function generateGlowAnimation($color, $intensity) {
    if ($intensity === 'none') {
        return '';
    }
    
    $blur = convertEnumToCSS($intensity, 'glow_blur');
    $opacity = convertEnumToCSS($intensity, 'glow_opacity');
    
    // This will be used in ThemeCSSGenerator
    return [
        'color' => $color,
        'blur' => $blur,
        'opacity' => $opacity
    ];
}

/**
 * Validate widget styles
 * @param array $styles Widget styles to validate
 * @return bool True if valid
 */
function validateWidgetStyles($styles) {
    return WidgetStyleManager::validate($styles);
}

/**
 * Validate color value
 * @param string $color Color to validate
 * @return bool True if valid
 */
function validateColorValue($color) {
    if (empty($color) || !is_string($color)) {
        return false;
    }
    
    // Check hex color
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
        return true;
    }
    
    // Check CSS variable
    if (preg_match('/^var\(--[a-zA-Z0-9_-]+\)$/', $color)) {
        return true;
    }
    
    // Check gradient
    if (isGradient($color)) {
        return true;
    }
    
    return false;
}

