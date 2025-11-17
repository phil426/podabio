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
        'page_fonts' => $themeObj->getPageFonts($page, $theme),
        'widget_fonts' => $themeObj->getWidgetFonts($page, $theme),
        'page_background' => $themeObj->getPageBackground($page, $theme),
        'widget_background' => $themeObj->getWidgetBackground($page, $theme),
        'widget_border_color' => $themeObj->getWidgetBorderColor($page, $theme),
        'widget_styles' => $themeObj->getWidgetStyles($page, $theme),
        'spatial_effect' => $themeObj->getSpatialEffect($page, $theme)
    ];
}

/**
 * Get consolidated theme tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getThemeTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getThemeTokens($page, $theme);
}

/**
 * Convenience helper to fetch color tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getColorTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getColorTokens($page, $theme);
}

/**
 * Convenience helper to fetch typography tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getTypographyTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getTypographyTokens($page, $theme);
}

/**
 * Convenience helper to fetch spacing tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getSpacingTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getSpacingTokens($page, $theme);
}

/**
 * Convenience helper to fetch shape tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getShapeTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getShapeTokens($page, $theme);
}

/**
 * Convenience helper to fetch motion tokens
 * @param array $page
 * @param array|null $theme
 * @return array
 */
function getMotionTokens($page, $theme = null) {
    $themeObj = new Theme();
    return $themeObj->getMotionTokens($page, $theme);
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
 * Get widget background for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return string Widget background (color or gradient)
 */
function getWidgetBackground($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getWidgetBackground($page, $theme);
}

/**
 * Get widget border color for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return string Widget border color (color or gradient)
 */
function getWidgetBorderColor($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getWidgetBorderColor($page, $theme);
}

/**
 * Get widget fonts for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Widget fonts (primary, secondary)
 */
function getWidgetFonts($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getWidgetFonts($page, $theme);
}

/**
 * Get page fonts for a page
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Page fonts (primary, secondary)
 */
function getPageFonts($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getPageFonts($page, $theme);
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
            'none' => '0px',
            'thin' => 'var(--border-width-hairline)',
            'thick' => 'var(--border-width-bold)'
        ],
        'shadow' => [
            'none' => 'none',
            'subtle' => 'var(--shadow-level-1)',
            'pronounced' => 'var(--shadow-level-2)'
        ],
        'glow_blur' => [
            'subtle' => '8px',
            'pronounced' => '16px'
        ],
        'glow_opacity' => [
            'subtle' => '0.5',
            'pronounced' => '0.8'
        ],
        'spacing' => [
            'tight' => 'var(--space-sm)',
            'comfortable' => 'var(--space-md)',
            'spacious' => 'var(--space-lg)'
        ],
        'shape' => [
            'square' => 'var(--shape-corner-none)',
            'rounded' => 'var(--shape-corner-md)',
            'round' => 'var(--shape-corner-pill)'
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
    // No need to check for 'none' - glow intensity is always 'subtle' or 'pronounced'
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

