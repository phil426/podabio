<?php
/**
 * Widget Style Manager
 * Centralized widget style defaults, validation, and sanitization
 * PodaBio
 */

class WidgetStyleManager {
    
    /**
     * Default widget styles
     * @var array
     */
    private static $defaults = [
        'border_width' => 'none',
        'border_effect' => 'shadow',
        'border_shadow_intensity' => 'subtle',
        'border_glow_intensity' => 'subtle',
        'glow_color' => '#ff00ff',
        // REMOVED: Legacy color references - these are now in theme columns
        'spacing' => 'comfortable',
        'shape' => 'rounded'
    ];
    
    /**
     * Valid enum values for each style property
     * @var array
     */
    private static $validEnums = [
        'border_width' => ['none', 'thin', 'thick'],
        'border_effect' => ['shadow', 'glow'],
        'border_shadow_intensity' => ['none', 'subtle', 'pronounced'],
        'border_glow_intensity' => ['subtle', 'pronounced'],
        'spacing' => ['tight', 'comfortable', 'spacious'],
        'shape' => ['square', 'rounded', 'round']
    ];
    
    /**
     * Get default widget styles
     * @return array
     */
    public static function getDefaults() {
        return self::$defaults;
    }
    
    /**
     * Validate widget styles array
     * @param array $styles Widget styles to validate
     * @return bool True if valid
     */
    public static function validate($styles) {
        if (!is_array($styles)) {
            return false;
        }
        
        // Check each enum field
        foreach (self::$validEnums as $field => $validValues) {
            if (isset($styles[$field]) && !in_array($styles[$field], $validValues, true)) {
                return false;
            }
        }
        
        // Validate glow_color if glow effect is used
        if (isset($styles['border_effect']) && $styles['border_effect'] === 'glow') {
            if (isset($styles['glow_color']) && !self::isValidColor($styles['glow_color'])) {
                return false;
            }
        }
        
        // Validate border_color and background_color (can be color or gradient)
        if (isset($styles['border_color']) && !self::isValidColorOrGradient($styles['border_color'])) {
            return false;
        }
        
        if (isset($styles['background_color']) && !self::isValidColorOrGradient($styles['background_color'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Merge styles with defaults
     * @param array $styles User-provided styles
     * @return array Merged styles with defaults
     */
    public static function mergeWithDefaults($styles) {
        if (!is_array($styles)) {
            $styles = [];
        }
        
        $merged = array_merge(self::$defaults, $styles);
        
        // Ensure enum values are valid
        foreach (self::$validEnums as $field => $validValues) {
            if (isset($merged[$field]) && !in_array($merged[$field], $validValues, true)) {
                $merged[$field] = self::$defaults[$field];
            }
        }
        
        return $merged;
    }
    
    /**
     * Sanitize widget styles
     * @param array $styles Widget styles to sanitize
     * @return array Sanitized styles
     */
    public static function sanitize($styles) {
        if (!is_array($styles)) {
            return self::$defaults;
        }
        
        $sanitized = [];
        
        // Sanitize enum fields
        foreach (self::$validEnums as $field => $validValues) {
            if (isset($styles[$field])) {
                if (in_array($styles[$field], $validValues, true)) {
                    $sanitized[$field] = $styles[$field];
                } else {
                    $sanitized[$field] = self::$defaults[$field];
                }
            }
        }
        
        // Sanitize color fields
        if (isset($styles['glow_color'])) {
            $sanitized['glow_color'] = self::sanitizeColor($styles['glow_color'], self::$defaults['glow_color']);
        }
        
        if (isset($styles['border_color'])) {
            $sanitized['border_color'] = self::sanitizeColorOrGradient($styles['border_color'], self::$defaults['border_color']);
        }
        
        if (isset($styles['background_color'])) {
            $sanitized['background_color'] = self::sanitizeColorOrGradient($styles['background_color'], self::$defaults['background_color']);
        }
        
        // Merge with defaults for any missing fields
        return self::mergeWithDefaults($sanitized);
    }
    
    /**
     * Check if value is valid enum
     * @param string $field Field name
     * @param string $value Value to check
     * @return bool
     */
    public static function isValidEnum($field, $value) {
        if (!isset(self::$validEnums[$field])) {
            return false;
        }
        return in_array($value, self::$validEnums[$field], true);
    }
    
    /**
     * Get valid enum values for a field
     * @param string $field Field name
     * @return array Valid values or empty array
     */
    public static function getValidEnums($field) {
        return self::$validEnums[$field] ?? [];
    }
    
    /**
     * Check if a color value is valid (hex format)
     * @param string $color Color value
     * @return bool
     */
    private static function isValidColor($color) {
        if (empty($color) || !is_string($color)) {
            return false;
        }
        
        // Check hex color format (#RRGGBB or #RGB)
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return true;
        }
        
        // Check CSS variable format
        if (preg_match('/^var\(--[a-zA-Z0-9_-]+\)$/', $color)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if value is valid color or gradient
     * @param string $value Value to check
     * @return bool
     */
    private static function isValidColorOrGradient($value) {
        if (empty($value) || !is_string($value)) {
            return false;
        }
        
        // Check if it's a gradient
        if (preg_match('/^(linear-gradient|radial-gradient|conic-gradient)\(/', $value)) {
            return true;
        }
        
        // Check if it's a valid color
        return self::isValidColor($value);
    }
    
    /**
     * Sanitize color value
     * @param string $color Color to sanitize
     * @param string $default Default color if invalid
     * @return string Sanitized color
     */
    private static function sanitizeColor($color, $default = '#000000') {
        if (self::isValidColor($color)) {
            return $color;
        }
        return $default;
    }
    
    /**
     * Sanitize color or gradient value
     * @param string $value Value to sanitize
     * @param string $default Default value if invalid
     * @return string Sanitized value
     */
    private static function sanitizeColorOrGradient($value, $default = '#000000') {
        if (self::isValidColorOrGradient($value)) {
            return $value;
        }
        return $default;
    }
}

