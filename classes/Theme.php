<?php
/**
 * Theme Class
 * Centralized theme management and operations
 * Podn.Bio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

class Theme {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get theme by ID with validation
     * @param int $themeId
     * @return array|null
     */
    public function getTheme($themeId) {
        if (empty($themeId)) {
            return null;
        }
        
        $theme = fetchOne("SELECT * FROM themes WHERE id = ? AND is_active = 1", [$themeId]);
        
        if ($theme && $this->validateTheme($theme)) {
            return $theme;
        }
        
        return null;
    }
    
    /**
     * Get all themes
     * @param bool $activeOnly Only return active themes
     * @return array
     */
    public function getAllThemes($activeOnly = true) {
        $sql = "SELECT * FROM themes";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $themes = fetchAll($sql, $params);
        
        // Validate each theme
        return array_filter($themes, [$this, 'validateTheme']);
    }
    
    /**
     * Extract colors from page with theme fallback
     * Returns array with primary, secondary, and accent colors
     * @param array $page Page data array
     * @param array|null $theme Theme data array (optional, will be fetched if page has theme_id)
     * @return array
     */
    public function getThemeColors($page, $theme = null) {
        $colors = [];
        
        // First, try page-specific colors
        if (!empty($page['colors'])) {
            $pageColors = parseThemeJson($page['colors'], []);
            if (!empty($pageColors)) {
                $colors = $pageColors;
            }
        }
        
        // If no page colors and theme is provided, use theme colors
        if (empty($colors) && $theme) {
            if (!empty($theme['colors'])) {
                $themeColors = parseThemeJson($theme['colors'], []);
                if (!empty($themeColors)) {
                    $colors = $themeColors;
                }
            }
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($colors) && empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getTheme($page['theme_id']);
            if ($theme && !empty($theme['colors'])) {
                $themeColors = parseThemeJson($theme['colors'], []);
                if (!empty($themeColors)) {
                    $colors = $themeColors;
                }
            }
        }
        
        // Apply defaults for any missing colors
        $defaults = $this->getDefaultColors();
        return [
            'primary' => $colors['primary'] ?? $defaults['primary'],
            'secondary' => $colors['secondary'] ?? $defaults['secondary'],
            'accent' => $colors['accent'] ?? $defaults['accent']
        ];
    }
    
    /**
     * Extract fonts from page with theme fallback
     * Returns array with heading and body fonts
     * @param array $page Page data array
     * @param array|null $theme Theme data array (optional, will be fetched if page has theme_id)
     * @return array
     */
    public function getThemeFonts($page, $theme = null) {
        $fonts = [];
        
        // First, try page-specific fonts
        if (!empty($page['fonts'])) {
            $pageFonts = parseThemeJson($page['fonts'], []);
            if (!empty($pageFonts)) {
                $fonts = $pageFonts;
            }
        }
        
        // If no page fonts and theme is provided, use theme fonts
        if (empty($fonts) && $theme) {
            if (!empty($theme['fonts'])) {
                $themeFonts = parseThemeJson($theme['fonts'], []);
                if (!empty($themeFonts)) {
                    $fonts = $themeFonts;
                }
            }
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($fonts) && empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getTheme($page['theme_id']);
            if ($theme && !empty($theme['fonts'])) {
                $themeFonts = parseThemeJson($theme['fonts'], []);
                if (!empty($themeFonts)) {
                    $fonts = $themeFonts;
                }
            }
        }
        
        // Apply defaults for any missing fonts
        $defaults = $this->getDefaultFonts();
        return [
            'heading' => $fonts['heading'] ?? $defaults['heading'],
            'body' => $fonts['body'] ?? $defaults['body']
        ];
    }
    
    /**
     * Get default color set
     * @return array
     */
    public function getDefaultColors() {
        return [
            'primary' => defined('THEME_DEFAULT_PRIMARY_COLOR') ? THEME_DEFAULT_PRIMARY_COLOR : '#000000',
            'secondary' => defined('THEME_DEFAULT_SECONDARY_COLOR') ? THEME_DEFAULT_SECONDARY_COLOR : '#ffffff',
            'accent' => defined('THEME_DEFAULT_ACCENT_COLOR') ? THEME_DEFAULT_ACCENT_COLOR : '#0066ff'
        ];
    }
    
    /**
     * Get default font set
     * @return array
     */
    public function getDefaultFonts() {
        $defaultFont = defined('THEME_DEFAULT_FONT') ? THEME_DEFAULT_FONT : 'Inter';
        return [
            'heading' => $defaultFont,
            'body' => $defaultFont
        ];
    }
    
    /**
     * Validate theme data structure
     * @param array $themeData
     * @return bool
     */
    public function validateTheme($themeData) {
        if (empty($themeData) || !is_array($themeData)) {
            return false;
        }
        
        // Check required fields
        if (empty($themeData['id']) || empty($themeData['name'])) {
            return false;
        }
        
        // Validate colors JSON
        if (isset($themeData['colors'])) {
            if (is_string($themeData['colors'])) {
                $colors = parseThemeJson($themeData['colors'], []);
            } else {
                $colors = $themeData['colors'];
            }
            
            if (!is_array($colors)) {
                return false;
            }
            
            // Validate color format (should be hex codes)
            if (isset($colors['primary']) && !$this->isValidColor($colors['primary'])) {
                return false;
            }
            if (isset($colors['secondary']) && !$this->isValidColor($colors['secondary'])) {
                return false;
            }
            if (isset($colors['accent']) && !$this->isValidColor($colors['accent'])) {
                return false;
            }
        }
        
        // Validate fonts JSON
        if (isset($themeData['fonts'])) {
            if (is_string($themeData['fonts'])) {
                $fonts = parseThemeJson($themeData['fonts'], []);
            } else {
                $fonts = $themeData['fonts'];
            }
            
            if (!is_array($fonts)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if a color value is valid (hex format)
     * @param string $color
     * @return bool
     */
    private function isValidColor($color) {
        if (empty($color) || !is_string($color)) {
            return false;
        }
        
        // Check hex color format (#RRGGBB or #RGB)
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
    
    /**
     * Build Google Fonts URL from font array
     * @param array $fonts Array with 'heading' and/or 'body' keys
     * @return string Google Fonts URL
     */
    public function buildGoogleFontsUrl($fonts) {
        if (empty($fonts) || !is_array($fonts)) {
            $fonts = $this->getDefaultFonts();
        }
        
        $headingFont = $fonts['heading'] ?? $this->getDefaultFonts()['heading'];
        $bodyFont = $fonts['body'] ?? $this->getDefaultFonts()['body'];
        
        // Build Google Fonts URL
        $headingFontUrl = str_replace(' ', '+', $headingFont);
        $bodyFontUrl = str_replace(' ', '+', $bodyFont);
        
        return "https://fonts.googleapis.com/css2?family={$headingFontUrl}:wght@400;600;700&family={$bodyFontUrl}:wght@400;500&display=swap";
    }
}

