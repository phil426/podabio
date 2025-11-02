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
    private static $cache = [];
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get cached theme to reduce database queries
     * @param int $themeId
     * @return array|null
     */
    private function getCachedTheme($themeId) {
        if (!isset(self::$cache[$themeId])) {
            self::$cache[$themeId] = $this->getTheme($themeId);
        }
        return self::$cache[$themeId];
    }
    
    /**
     * Clear theme cache (useful after updates)
     * @param int|null $themeId If provided, clear specific theme. Otherwise clear all.
     */
    public static function clearCache($themeId = null) {
        if ($themeId !== null) {
            unset(self::$cache[$themeId]);
        } else {
            self::$cache = [];
        }
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
     * Returns array with page_primary_font and page_secondary_font
     * @param array $page Page data array
     * @param array|null $theme Theme data array (optional, will be fetched if page has theme_id)
     * @return array
     */
    public function getThemeFonts($page, $theme = null) {
        // First, try page-specific page fonts (new columns)
        if (!empty($page['page_primary_font']) || !empty($page['page_secondary_font'])) {
            $defaults = $this->getDefaultFonts();
            $pagePrimary = $page['page_primary_font'] ?? $defaults['page_primary_font'];
            $pageSecondary = $page['page_secondary_font'] ?? $defaults['page_secondary_font'];
            return [
                'page_primary_font' => $pagePrimary,
                'page_secondary_font' => $pageSecondary,
                'heading' => $pagePrimary,
                'body' => $pageSecondary
            ];
        }
        
        // Try legacy fonts JSON for backward compatibility
        $fonts = [];
        if (!empty($page['fonts'])) {
            $pageFonts = parseThemeJson($page['fonts'], []);
            if (!empty($pageFonts)) {
                $fonts = $pageFonts;
            }
        }
        
        // If no page fonts and theme is provided, use theme fonts
        if (empty($fonts) && $theme) {
            // Try new theme columns first
            if (!empty($theme['page_primary_font']) || !empty($theme['page_secondary_font'])) {
                $defaults = $this->getDefaultFonts();
                $pagePrimary = $theme['page_primary_font'] ?? $defaults['page_primary_font'];
                $pageSecondary = $theme['page_secondary_font'] ?? $defaults['page_secondary_font'];
                return [
                    'page_primary_font' => $pagePrimary,
                    'page_secondary_font' => $pageSecondary,
                    'heading' => $pagePrimary,
                    'body' => $pageSecondary
                ];
            }
            
            // Fallback to legacy fonts JSON
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
            if ($theme) {
                // Try new theme columns first
                if (!empty($theme['page_primary_font']) || !empty($theme['page_secondary_font'])) {
                    $defaults = $this->getDefaultFonts();
                    return [
                        'page_primary_font' => $theme['page_primary_font'] ?? $defaults['page_primary_font'],
                        'page_secondary_font' => $theme['page_secondary_font'] ?? $defaults['page_secondary_font']
                    ];
                }
                
                // Fallback to legacy fonts JSON
                if (!empty($theme['fonts'])) {
                    $themeFonts = parseThemeJson($theme['fonts'], []);
                    if (!empty($themeFonts)) {
                        $fonts = $themeFonts;
                    }
                }
            }
        }
        
        // Apply defaults for any missing fonts
        $defaults = $this->getDefaultFonts();
        // Map legacy 'heading'/'body' to new structure
        $pagePrimary = $fonts['heading'] ?? $fonts['page_primary_font'] ?? $defaults['page_primary_font'];
        $pageSecondary = $fonts['body'] ?? $fonts['page_secondary_font'] ?? $defaults['page_secondary_font'];
        
        return [
            // New structure
            'page_primary_font' => $pagePrimary,
            'page_secondary_font' => $pageSecondary,
            // Legacy support
            'heading' => $pagePrimary,
            'body' => $pageSecondary
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
            'heading' => $defaultFont, // Legacy support
            'body' => $defaultFont, // Legacy support
            'page_primary_font' => $defaultFont,
            'page_secondary_font' => $defaultFont,
            'widget_primary_font' => $defaultFont,
            'widget_secondary_font' => $defaultFont
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
     * Supports both legacy ('heading'/'body') and new ('page_primary_font'/'page_secondary_font'/'widget_primary_font'/'widget_secondary_font') keys
     * @param array $fonts Font array
     * @return string Google Fonts URL
     */
    public function buildGoogleFontsUrl($fonts) {
        if (empty($fonts) || !is_array($fonts)) {
            $fonts = $this->getDefaultFonts();
        }
        
        // Collect unique fonts (page and widget)
        $defaults = $this->getDefaultFonts();
        $pagePrimary = $fonts['page_primary_font'] ?? $fonts['heading'] ?? $defaults['page_primary_font'];
        $pageSecondary = $fonts['page_secondary_font'] ?? $fonts['body'] ?? $defaults['page_secondary_font'];
        $widgetPrimary = $fonts['widget_primary_font'] ?? $defaults['widget_primary_font'];
        $widgetSecondary = $fonts['widget_secondary_font'] ?? $defaults['widget_secondary_font'];
        
        // Get unique fonts
        $uniqueFonts = array_unique([$pagePrimary, $pageSecondary, $widgetPrimary, $widgetSecondary]);
        
        // Build Google Fonts URL
        $fontParams = [];
        foreach ($uniqueFonts as $font) {
            $fontUrl = str_replace(' ', '+', $font);
            $fontParams[] = "family={$fontUrl}:wght@400;600;700";
        }
        
        return "https://fonts.googleapis.com/css2?" . implode('&', $fontParams) . "&display=swap";
    }
    
    /**
     * Get widget styles for a page with theme fallback
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return array Widget styles with defaults applied
     */
    public function getWidgetStyles($page, $theme = null) {
        require_once __DIR__ . '/WidgetStyleManager.php';
        $styles = [];
        
        // First, try page-specific widget styles
        if (!empty($page['widget_styles'])) {
            $pageStyles = parseThemeJson($page['widget_styles'], []);
            if (!empty($pageStyles)) {
                $styles = $pageStyles;
            }
        }
        
        // Add widget background and border color from new columns
        if (!empty($page['widget_background'])) {
            $styles['widget_background'] = $page['widget_background'];
        }
        if (!empty($page['widget_border_color'])) {
            $styles['widget_border_color'] = $page['widget_border_color'];
        }
        
        // If no page styles and theme is provided, use theme styles
        if (empty($styles['widget_background']) && $theme && !empty($theme['widget_background'])) {
            $styles['widget_background'] = $theme['widget_background'];
        }
        if (empty($styles['widget_border_color']) && $theme && !empty($theme['widget_border_color'])) {
            $styles['widget_border_color'] = $theme['widget_border_color'];
        }
        
        if ($theme && !empty($theme['widget_styles'])) {
            $themeStyles = parseThemeJson($theme['widget_styles'], []);
            if (!empty($themeStyles)) {
                $styles = array_merge($themeStyles, $styles);
            }
        }
        
        // If no theme provided but page has theme_id, fetch it
        if ((empty($styles['widget_background']) || empty($styles['widget_border_color'])) && empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme) {
                if (empty($styles['widget_background']) && !empty($theme['widget_background'])) {
                    $styles['widget_background'] = $theme['widget_background'];
                }
                if (empty($styles['widget_border_color']) && !empty($theme['widget_border_color'])) {
                    $styles['widget_border_color'] = $theme['widget_border_color'];
                }
                if (!empty($theme['widget_styles'])) {
                    $themeStyles = parseThemeJson($theme['widget_styles'], []);
                    if (!empty($themeStyles)) {
                        $styles = array_merge($themeStyles, $styles);
                    }
                }
            }
        }
        
        // Merge with defaults
        return WidgetStyleManager::mergeWithDefaults($styles);
    }
    
    /**
     * Get widget background for a page with theme fallback
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return string Widget background (color or gradient)
     */
    public function getWidgetBackground($page, $theme = null) {
        // First, try page-specific widget background
        if (!empty($page['widget_background'])) {
            return $page['widget_background'];
        }
        
        // If no page background and theme is provided, use theme background
        if ($theme && !empty($theme['widget_background'])) {
            return $theme['widget_background'];
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && !empty($theme['widget_background'])) {
                return $theme['widget_background'];
            }
        }
        
        // Fallback to secondary color
        $colors = $this->getThemeColors($page, $theme);
        return $colors['secondary'];
    }
    
    /**
     * Get widget border color for a page with theme fallback
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return string Widget border color (color or gradient)
     */
    public function getWidgetBorderColor($page, $theme = null) {
        // First, try page-specific widget border color
        if (!empty($page['widget_border_color'])) {
            return $page['widget_border_color'];
        }
        
        // If no page border color and theme is provided, use theme border color
        if ($theme && !empty($theme['widget_border_color'])) {
            return $theme['widget_border_color'];
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && !empty($theme['widget_border_color'])) {
                return $theme['widget_border_color'];
            }
        }
        
        // Fallback to primary color
        $colors = $this->getThemeColors($page, $theme);
        return $colors['primary'];
    }
    
    /**
     * Get widget fonts for a page with theme fallback
     * Returns array with widget_primary_font and widget_secondary_font
     * Widget fonts default to page fonts if not set
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return array
     */
    public function getWidgetFonts($page, $theme = null) {
        // First, try page-specific widget fonts (new columns)
        if (!empty($page['widget_primary_font']) || !empty($page['widget_secondary_font'])) {
            $pageFonts = $this->getPageFonts($page, $theme);
            return [
                'widget_primary_font' => $page['widget_primary_font'] ?? $pageFonts['page_primary_font'],
                'widget_secondary_font' => $page['widget_secondary_font'] ?? $pageFonts['page_secondary_font']
            ];
        }
        
        // If no page widget fonts and theme is provided, use theme widget fonts
        if ($theme && (!empty($theme['widget_primary_font']) || !empty($theme['widget_secondary_font']))) {
            $pageFonts = $this->getPageFonts($page, $theme);
            return [
                'widget_primary_font' => $theme['widget_primary_font'] ?? $pageFonts['page_primary_font'],
                'widget_secondary_font' => $theme['widget_secondary_font'] ?? $pageFonts['page_secondary_font']
            ];
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && (!empty($theme['widget_primary_font']) || !empty($theme['widget_secondary_font']))) {
                $pageFonts = $this->getPageFonts($page, $theme);
                return [
                    'widget_primary_font' => $theme['widget_primary_font'] ?? $pageFonts['page_primary_font'],
                    'widget_secondary_font' => $theme['widget_secondary_font'] ?? $pageFonts['page_secondary_font']
                ];
            }
        }
        
        // Default to page fonts
        $pageFonts = $this->getPageFonts($page, $theme);
        $defaults = $this->getDefaultFonts();
        return [
            'widget_primary_font' => $pageFonts['page_primary_font'] ?? $defaults['widget_primary_font'],
            'widget_secondary_font' => $pageFonts['page_secondary_font'] ?? $defaults['widget_secondary_font']
        ];
    }
    
    /**
     * Get page fonts for a page with theme fallback
     * Returns array with page_primary_font and page_secondary_font
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return array
     */
    public function getPageFonts($page, $theme = null) {
        // This is essentially the same as getThemeFonts, but more explicitly named
        return $this->getThemeFonts($page, $theme);
    }
    
    /**
     * Get page background for a page with theme fallback
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return string Page background (color or gradient)
     */
    public function getPageBackground($page, $theme = null) {
        // First, try page-specific background
        if (!empty($page['page_background'])) {
            return $page['page_background'];
        }
        
        // If no page background and theme is provided, use theme background
        if ($theme && !empty($theme['page_background'])) {
            return $theme['page_background'];
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && !empty($theme['page_background'])) {
                return $theme['page_background'];
            }
        }
        
        // Fallback to secondary color
        $colors = $this->getThemeColors($page, $theme);
        return $colors['secondary'];
    }
    
    /**
     * Get spatial effect for a page with theme fallback
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return string Spatial effect name
     */
    public function getSpatialEffect($page, $theme = null) {
        // First, try page-specific spatial effect
        if (!empty($page['spatial_effect'])) {
            $validEffects = ['none', 'glass', 'depth', 'floating'];
            if (in_array($page['spatial_effect'], $validEffects, true)) {
                return $page['spatial_effect'];
            }
        }
        
        // If no page effect and theme is provided, use theme effect
        if ($theme && !empty($theme['spatial_effect'])) {
            $validEffects = ['none', 'glass', 'depth', 'floating'];
            if (in_array($theme['spatial_effect'], $validEffects, true)) {
                return $theme['spatial_effect'];
            }
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && !empty($theme['spatial_effect'])) {
                $validEffects = ['none', 'glass', 'depth', 'floating'];
                if (in_array($theme['spatial_effect'], $validEffects, true)) {
                    return $theme['spatial_effect'];
                }
            }
        }
        
        // Default to 'none'
        return 'none';
    }
    
    /**
     * Get complete theme configuration
     * @param array $page Page data array
     * @param array|null $theme Optional theme data array
     * @return array Complete theme config
     */
    public function getThemeConfig($page, $theme = null) {
        return [
            'colors' => $this->getThemeColors($page, $theme),
            'fonts' => $this->getThemeFonts($page, $theme),
            'page_fonts' => $this->getPageFonts($page, $theme),
            'widget_fonts' => $this->getWidgetFonts($page, $theme),
            'page_background' => $this->getPageBackground($page, $theme),
            'widget_background' => $this->getWidgetBackground($page, $theme),
            'widget_border_color' => $this->getWidgetBorderColor($page, $theme),
            'widget_styles' => $this->getWidgetStyles($page, $theme),
            'spatial_effect' => $this->getSpatialEffect($page, $theme)
        ];
    }
    
    /**
     * Get all themes for a specific user (user-created themes)
     * @param int $userId User ID
     * @return array Array of user themes
     */
    public function getUserThemes($userId) {
        $sql = "SELECT * FROM themes WHERE user_id = ? AND is_active = 1 ORDER BY name ASC";
        $themes = fetchAll($sql, [$userId]);
        
        // Validate each theme
        return array_filter($themes, [$this, 'validateTheme']);
    }
    
    /**
     * Get all system themes (themes with user_id = NULL)
     * @param bool $activeOnly Only return active themes
     * @return array Array of system themes
     */
    public function getSystemThemes($activeOnly = true) {
        $sql = "SELECT * FROM themes WHERE user_id IS NULL";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $themes = fetchAll($sql, $params);
        
        // Validate each theme
        return array_filter($themes, [$this, 'validateTheme']);
    }
    
    /**
     * Create a new user theme
     * @param int $userId User ID
     * @param string $name Theme name
     * @param array $themeData Theme data (colors, fonts, page_background, widget_styles, spatial_effect)
     * @return array ['success' => bool, 'theme_id' => int|null, 'error' => string|null]
     */
    public function createTheme($userId, $name, $themeData) {
        require_once __DIR__ . '/WidgetStyleManager.php';
        
        // Validate theme data
        if (empty($name) || strlen($name) > 100) {
            return ['success' => false, 'theme_id' => null, 'error' => 'Theme name must be 1-100 characters'];
        }
        
        // Check theme limit (max 3 custom themes)
        $existingThemes = fetchOne("SELECT COUNT(*) as count FROM themes WHERE user_id = ?", [$userId]);
        if ($existingThemes && $existingThemes['count'] >= 3) {
            return ['success' => false, 'theme_id' => null, 'error' => 'You can create a maximum of 3 custom themes. Please delete one first.'];
        }
        
        // Sanitize widget styles if provided
        if (isset($themeData['widget_styles'])) {
            $themeData['widget_styles'] = WidgetStyleManager::sanitize($themeData['widget_styles']);
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO themes (user_id, name, colors, fonts, page_background, widget_styles, spatial_effect, 
                    widget_background, widget_border_color, widget_primary_font, widget_secondary_font, 
                    page_primary_font, page_secondary_font, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $colors = isset($themeData['colors']) ? (is_array($themeData['colors']) ? json_encode($themeData['colors']) : $themeData['colors']) : null;
            $fonts = isset($themeData['fonts']) ? (is_array($themeData['fonts']) ? json_encode($themeData['fonts']) : $themeData['fonts']) : null;
            $pageBackground = $themeData['page_background'] ?? null;
            $widgetStyles = isset($themeData['widget_styles']) ? (is_array($themeData['widget_styles']) ? json_encode($themeData['widget_styles']) : $themeData['widget_styles']) : null;
            $spatialEffect = $themeData['spatial_effect'] ?? 'none';
            $widgetBackground = $themeData['widget_background'] ?? null;
            $widgetBorderColor = $themeData['widget_border_color'] ?? null;
            $widgetPrimaryFont = $themeData['widget_primary_font'] ?? null;
            $widgetSecondaryFont = $themeData['widget_secondary_font'] ?? null;
            $pagePrimaryFont = $themeData['page_primary_font'] ?? null;
            $pageSecondaryFont = $themeData['page_secondary_font'] ?? null;
            
            $stmt->execute([
                $userId,
                $name,
                $colors,
                $fonts,
                $pageBackground,
                $widgetStyles,
                $spatialEffect,
                $widgetBackground,
                $widgetBorderColor,
                $widgetPrimaryFont,
                $widgetSecondaryFont,
                $pagePrimaryFont,
                $pageSecondaryFont
            ]);
            
            $themeId = $this->pdo->lastInsertId();
            
            return ['success' => true, 'theme_id' => $themeId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Theme creation failed: " . $e->getMessage());
            return ['success' => false, 'theme_id' => null, 'error' => 'Failed to create theme'];
        }
    }
    
    /**
     * Delete a user's theme
     * @param int $themeId Theme ID
     * @param int $userId User ID (for authorization)
     * @return bool True if deleted successfully
     */
    public function deleteUserTheme($themeId, $userId) {
        try {
            // Verify theme belongs to user
            $theme = fetchOne("SELECT id FROM themes WHERE id = ? AND user_id = ?", [$themeId, $userId]);
            
            if (!$theme) {
                return false;
            }
            
            // Delete theme
            executeQuery("DELETE FROM themes WHERE id = ? AND user_id = ?", [$themeId, $userId]);
            
            // Clear cache
            self::clearCache($themeId);
            
            return true;
        } catch (PDOException $e) {
            error_log("Theme deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a user theme
     * @param int $themeId Theme ID
     * @param int $userId User ID (for authorization)
     * @param string|null $name New theme name (optional)
     * @param array $themeData Theme data updates (optional)
     * @return bool True if updated successfully
     */
    public function updateUserTheme($themeId, $userId, $name = null, $themeData = []) {
        require_once __DIR__ . '/WidgetStyleManager.php';
        
        try {
            // Verify theme belongs to user
            $theme = fetchOne("SELECT id FROM themes WHERE id = ? AND user_id = ?", [$themeId, $userId]);
            
            if (!$theme) {
                return false;
            }
            
            $updates = [];
            $params = [];
            
            if ($name !== null) {
                $updates[] = "name = ?";
                $params[] = $name;
            }
            
            if (isset($themeData['colors'])) {
                $updates[] = "colors = ?";
                $params[] = is_array($themeData['colors']) ? json_encode($themeData['colors']) : $themeData['colors'];
            }
            
            if (isset($themeData['fonts'])) {
                $updates[] = "fonts = ?";
                $params[] = is_array($themeData['fonts']) ? json_encode($themeData['fonts']) : $themeData['fonts'];
            }
            
            if (isset($themeData['page_background'])) {
                $updates[] = "page_background = ?";
                $params[] = $themeData['page_background'];
            }
            
            if (isset($themeData['widget_styles'])) {
                $sanitized = WidgetStyleManager::sanitize($themeData['widget_styles']);
                $updates[] = "widget_styles = ?";
                $params[] = json_encode($sanitized);
            }
            
            if (isset($themeData['spatial_effect'])) {
                $updates[] = "spatial_effect = ?";
                $params[] = $themeData['spatial_effect'];
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $params[] = $themeId;
            $params[] = $userId;
            
            $sql = "UPDATE themes SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
            executeQuery($sql, $params);
            
            // Clear cache
            self::clearCache($themeId);
            
            return true;
        } catch (PDOException $e) {
            error_log("Theme update failed: " . $e->getMessage());
            return false;
        }
    }
}

