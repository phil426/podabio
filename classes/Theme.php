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
    private static $themeColumns = null;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Safely decode JSON columns
     * @param array $source Source row
     * @param string $field Field name
     * @return array|null
     */
    private function decodeJsonField(array $source, string $field) {
        if (!isset($source[$field]) || $source[$field] === null) {
            return null;
        }

        if (is_array($source[$field])) {
            return $source[$field];
        }

        $decoded = json_decode($source[$field], true);
        return is_array($decoded) ? $decoded : null;
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
     * Parse JSON column to associative array with fallback
     * @param mixed $value
     * @param array $default
     * @return array
     */
    private function parseJsonColumn($value, $default = []) {
        if (empty($value)) {
            return $default;
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }
    
    /**
     * Merge theme tokens with defaults
     * @param array $defaults
     * @param array ...$overrides
     * @return array
     */
    private function mergeTokens(array $defaults, array ...$overrides) {
        $result = $defaults;
        
        foreach ($overrides as $override) {
            if (!empty($override) && is_array($override)) {
                $result = array_replace_recursive($result, $override);
            }
        }
        
        return $result;
    }
    
    /**
     * Default accessible color tokens
     * @return array
     */
    private function getDefaultColorTokens() {
        return [
            'background' => [
                'base' => '#f5f7fa',
                'surface' => '#ffffff',
                'surface_raised' => '#f9fafb',
                'overlay' => 'rgba(15, 23, 42, 0.6)'
            ],
            'text' => [
                'primary' => '#111827',
                'secondary' => '#4b5563',
                'inverse' => '#ffffff'
            ],
            'border' => [
                'default' => '#d1d5db',
                'focus' => '#2563eb'
            ],
            'accent' => [
                'primary' => '#0066ff',
                'muted' => '#e0edff'
            ],
            'state' => [
                'success' => '#12b76a',
                'warning' => '#f59e0b',
                'danger' => '#ef4444'
            ],
            'text_state' => [
                'success' => '#0f5132',
                'warning' => '#7c2d12',
                'danger' => '#7f1d1d'
            ],
            'shadow' => [
                'ambient' => 'rgba(15, 23, 42, 0.12)',
                'focus' => 'rgba(37, 99, 235, 0.35)'
            ],
            'gradient' => [
                'page' => null,
                'accent' => null,
                'widget' => null,
                'podcast' => null
            ],
            'glow' => [
                'primary' => null
            ]
        ];
    }
    
    /**
     * Default typography tokens (modular scale)
     * @return array
     */
    private function getDefaultTypographyTokens() {
        $defaultFont = defined('THEME_DEFAULT_FONT') ? THEME_DEFAULT_FONT : 'Inter';
        
        return [
            'font' => [
                'heading' => $defaultFont,
                'body' => $defaultFont,
                'metatext' => $defaultFont
            ],
            'scale' => [
                'xl' => 2.488,
                'lg' => 1.777,
                'md' => 1.333,
                'sm' => 1.111,
                'xs' => 0.889
            ],
            'line_height' => [
                'tight' => 1.2,
                'normal' => 1.5,
                'relaxed' => 1.7
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 500,
                'bold' => 600
            ]
        ];
    }
    
    /**
     * Default spacing token configuration
     * @return array
     */
    private function getDefaultSpacingTokens() {
        return [
            'density' => 'comfortable',
            'base_scale' => [
                '2xs' => 0.25,
                'xs' => 0.5,
                'sm' => 0.75,
                'md' => 1.0,
                'lg' => 1.5,
                'xl' => 2.0,
                '2xl' => 3.0
            ],
            'density_multipliers' => [
                'compact' => [
                    '2xs' => 0.75,
                    'xs' => 0.85,
                    'sm' => 0.9,
                    'md' => 1.0,
                    'lg' => 1.0,
                    'xl' => 1.0,
                    '2xl' => 1.0
                ],
                'comfortable' => [
                    '2xs' => 1.0,
                    'xs' => 1.0,
                    'sm' => 1.1,
                    'md' => 1.25,
                    'lg' => 1.3,
                    'xl' => 1.35,
                    '2xl' => 1.4
                ]
            ],
            'modifiers' => []
        ];
    }
    
    /**
     * Default shape/radius tokens
     * @return array
     */
    private function getDefaultShapeTokens() {
        return [
            'corner' => [
                'none' => '0px',
                'sm' => '0.375rem',
                'md' => '0.75rem',
                'lg' => '1.5rem',
                'pill' => '9999px'
            ],
            'border_width' => [
                'hairline' => '1px',
                'regular' => '2px',
                'bold' => '4px'
            ],
            'shadow' => [
                'level_1' => '0 2px 6px rgba(15, 23, 42, 0.12)',
                'level_2' => '0 6px 16px rgba(15, 23, 42, 0.16)',
                'focus' => '0 0 0 4px rgba(37, 99, 235, 0.35)'
            ]
        ];
    }
    
    /**
     * Default motion tokens
     * @return array
     */
    private function getDefaultMotionTokens() {
        return [
            'duration' => [
                'fast' => '150ms',
                'standard' => '250ms'
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                'decelerate' => 'cubic-bezier(0.0, 0, 0.2, 1)'
            ],
            'focus' => [
                'ring_width' => '3px',
                'ring_offset' => '2px'
            ]
        ];
    }
    
    /**
     * Resolve effective layout density
     * @param array $page
     * @param array|null $theme
     * @param string|null $fallback
     * @return string
     */
    private function resolveLayoutDensity($page, $theme = null, $fallback = null) {
        $density = $fallback ?? 'comfortable';
        
        if (!empty($page['layout_density'])) {
            $density = $page['layout_density'];
        } elseif (!empty($theme['layout_density'])) {
            $density = $theme['layout_density'];
        }
        
        $allowed = ['compact', 'comfortable'];
        return in_array($density, $allowed, true) ? $density : 'comfortable';
    }
    
    /**
     * Format spacing token to rem value
     * @param float $value
     * @return string
     */
    private function formatSpacingValue($value) {
        $rounded = round($value, 4);
        // Remove trailing zeros for cleaner CSS
        $formatted = rtrim(rtrim((string)$rounded, '0'), '.');
        if ($formatted === '') {
            $formatted = '0';
        }
        return $formatted . 'rem';
    }
    
    /**
     * Get merged color tokens with fallbacks
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getColorTokens($page, $theme = null) {
        $defaults = $this->getDefaultColorTokens();
        $pageTokens = $this->parseJsonColumn($page['color_tokens'] ?? null, []);
        $themeTokens = $theme ? $this->parseJsonColumn($theme['color_tokens'] ?? null, []) : [];
        
        return $this->mergeTokens($defaults, $themeTokens, $pageTokens);
    }
    
    /**
     * Get merged typography tokens with fallbacks
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getTypographyTokens($page, $theme = null) {
        $defaults = $this->getDefaultTypographyTokens();
        $pageTokens = $this->parseJsonColumn($page['typography_tokens'] ?? null, []);
        $themeTokens = $theme ? $this->parseJsonColumn($theme['typography_tokens'] ?? null, []) : [];
        
        return $this->mergeTokens($defaults, $themeTokens, $pageTokens);
    }
    
    /**
     * Get merged spacing tokens with computed density values
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getSpacingTokens($page, $theme = null) {
        $defaults = $this->getDefaultSpacingTokens();
        $pageTokens = $this->parseJsonColumn($page['spacing_tokens'] ?? null, []);
        $themeTokens = $theme ? $this->parseJsonColumn($theme['spacing_tokens'] ?? null, []) : [];
        
        $merged = $this->mergeTokens($defaults, $themeTokens, $pageTokens);
        
        $density = $this->resolveLayoutDensity($page, $theme, $merged['density'] ?? 'comfortable');
        $baseScale = $merged['base_scale'] ?? $defaults['base_scale'];
        $densityMultipliers = $merged['density_multipliers'][$density] ?? ($defaults['density_multipliers'][$density] ?? []);
        
        $values = [];
        foreach ($baseScale as $key => $base) {
            $multiplier = isset($densityMultipliers[$key]) ? (float)$densityMultipliers[$key] : 1.0;
            $values[$key] = $this->formatSpacingValue($base * $multiplier);
        }
        
        $merged['density'] = $density;
        $merged['values'] = $values;
        $merged['modifiers'] = $merged['modifiers'] ?? [];
        
        return $merged;
    }
    
    /**
     * Get merged shape tokens
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getShapeTokens($page, $theme = null) {
        $defaults = $this->getDefaultShapeTokens();
        $pageTokens = $this->parseJsonColumn($page['shape_tokens'] ?? null, []);
        $themeTokens = $theme ? $this->parseJsonColumn($theme['shape_tokens'] ?? null, []) : [];
        
        return $this->mergeTokens($defaults, $themeTokens, $pageTokens);
    }
    
    /**
     * Get merged motion tokens
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getMotionTokens($page, $theme = null) {
        $defaults = $this->getDefaultMotionTokens();
        $pageTokens = $this->parseJsonColumn($page['motion_tokens'] ?? null, []);
        $themeTokens = $theme ? $this->parseJsonColumn($theme['motion_tokens'] ?? null, []) : [];
        
        return $this->mergeTokens($defaults, $themeTokens, $pageTokens);
    }
    
    /**
     * Get consolidated theme token sets
     * @param array $page
     * @param array|null $theme
     * @return array
     */
    public function getThemeTokens($page, $theme = null) {
        $colorTokens = $this->getColorTokens($page, $theme);
        $typographyTokens = $this->getTypographyTokens($page, $theme);
        $spacingTokens = $this->getSpacingTokens($page, $theme);
        $shapeTokens = $this->getShapeTokens($page, $theme);
        $motionTokens = $this->getMotionTokens($page, $theme);
        
        return [
            'colors' => $colorTokens,
            'typography' => $typographyTokens,
            'spacing' => $spacingTokens,
            'shape' => $shapeTokens,
            'motion' => $motionTokens,
            'layout_density' => $spacingTokens['density'] ?? $this->resolveLayoutDensity($page, $theme)
        ];
    }
    
    /**
     * Lazy-load list of columns on themes table
     * @return array
     */
    private function getThemeColumns() {
        if (self::$themeColumns !== null) {
            return self::$themeColumns;
        }
        
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM themes");
            $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            self::$themeColumns = is_array($columns) ? $columns : [];
        } catch (PDOException $e) {
            error_log("Failed to inspect themes columns: " . $e->getMessage());
            self::$themeColumns = [];
        }
        
        return self::$themeColumns;
    }
    
    /**
     * Check if themes table contains a column
     * @param string $column
     * @return bool
     */
    private function hasThemeColumn($column) {
        $columns = $this->getThemeColumns();
        return in_array($column, $columns, true);
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
        
        // Validate optional token JSON columns if present
        $tokenColumns = ['color_tokens', 'typography_tokens', 'spacing_tokens', 'shape_tokens', 'motion_tokens'];
        foreach ($tokenColumns as $tokenColumn) {
            if (isset($themeData[$tokenColumn])) {
                $parsed = $this->parseJsonColumn($themeData[$tokenColumn], null);
                if ($parsed !== null && !is_array($parsed)) {
                    return false;
                }
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
            $validEffects = ['none', 'glass', 'depth', 'floating', 'tilt'];
            if (in_array($page['spatial_effect'], $validEffects, true)) {
                return $page['spatial_effect'];
            }
        }
        
        // If no page effect and theme is provided, use theme effect
        if ($theme && !empty($theme['spatial_effect'])) {
            $validEffects = ['none', 'glass', 'depth', 'floating', 'tilt'];
            if (in_array($theme['spatial_effect'], $validEffects, true)) {
                return $theme['spatial_effect'];
            }
        }
        
        // If no theme provided but page has theme_id, fetch it
        if (empty($theme) && !empty($page['theme_id'])) {
            $theme = $this->getCachedTheme($page['theme_id']);
            if ($theme && !empty($theme['spatial_effect'])) {
                $validEffects = ['none', 'glass', 'depth', 'floating', 'tilt'];
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
        $tokens = $this->getThemeTokens($page, $theme);
        
        return [
            'colors' => $this->getThemeColors($page, $theme),
            'fonts' => $this->getThemeFonts($page, $theme),
            'page_fonts' => $this->getPageFonts($page, $theme),
            'widget_fonts' => $this->getWidgetFonts($page, $theme),
            'page_background' => $this->getPageBackground($page, $theme),
            'widget_background' => $this->getWidgetBackground($page, $theme),
            'widget_border_color' => $this->getWidgetBorderColor($page, $theme),
            'widget_styles' => $this->getWidgetStyles($page, $theme),
            'spatial_effect' => $this->getSpatialEffect($page, $theme),
            'tokens' => $tokens,
            'layout_density' => $tokens['layout_density'] ?? 'comfortable'
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
     * Clone an existing theme into the current user's library
     * @param int $themeId Theme to clone
     * @param int $userId Destination user ID
     * @param string|null $name Optional new name
     * @return array ['success' => bool, 'theme_id' => int|null, 'error' => string|null]
     */
    public function cloneTheme($themeId, $userId, $name = null) {
        $theme = fetchOne("SELECT * FROM themes WHERE id = ?", [$themeId]);

        if (!$theme) {
            return ['success' => false, 'theme_id' => null, 'error' => 'Theme not found'];
        }

        $cloneName = $name !== null && $name !== '' ? $name : ($theme['name'] ?? 'Custom theme') . ' Copy';

        $themeData = [
            'colors' => $this->decodeJsonField($theme, 'colors'),
            'fonts' => $this->decodeJsonField($theme, 'fonts'),
            'page_background' => $theme['page_background'] ?? null,
            'widget_styles' => $this->decodeJsonField($theme, 'widget_styles'),
            'spatial_effect' => $theme['spatial_effect'] ?? 'none',
            'widget_background' => $theme['widget_background'] ?? null,
            'widget_border_color' => $theme['widget_border_color'] ?? null,
            'widget_primary_font' => $theme['widget_primary_font'] ?? null,
            'widget_secondary_font' => $theme['widget_secondary_font'] ?? null,
            'page_primary_font' => $theme['page_primary_font'] ?? null,
            'page_secondary_font' => $theme['page_secondary_font'] ?? null
        ];

        if ($this->hasThemeColumn('color_tokens') && isset($theme['color_tokens'])) {
            $themeData['color_tokens'] = $this->decodeJsonField($theme, 'color_tokens');
        }

        if ($this->hasThemeColumn('typography_tokens') && isset($theme['typography_tokens'])) {
            $themeData['typography_tokens'] = $this->decodeJsonField($theme, 'typography_tokens');
        }

        if ($this->hasThemeColumn('spacing_tokens') && isset($theme['spacing_tokens'])) {
            $themeData['spacing_tokens'] = $this->decodeJsonField($theme, 'spacing_tokens');
        }

        if ($this->hasThemeColumn('shape_tokens') && isset($theme['shape_tokens'])) {
            $themeData['shape_tokens'] = $this->decodeJsonField($theme, 'shape_tokens');
        }

        if ($this->hasThemeColumn('motion_tokens') && isset($theme['motion_tokens'])) {
            $themeData['motion_tokens'] = $this->decodeJsonField($theme, 'motion_tokens');
        }

        if ($this->hasThemeColumn('layout_density') && isset($theme['layout_density'])) {
            $themeData['layout_density'] = $theme['layout_density'];
        }

        if ($this->hasThemeColumn('categories') && isset($theme['categories'])) {
            $themeData['categories'] = $this->decodeJsonField($theme, 'categories');
        }
        
        if ($this->hasThemeColumn('tags') && isset($theme['tags'])) {
            $themeData['tags'] = $this->decodeJsonField($theme, 'tags');
        }

        return $this->createTheme($userId, $cloneName, $themeData);
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
            
            $columns = [
                'user_id',
                'name',
                'colors',
                'fonts',
                'page_background',
                'widget_styles',
                'spatial_effect',
                'widget_background',
                'widget_border_color',
                'widget_primary_font',
                'widget_secondary_font',
                'page_primary_font',
                'page_secondary_font'
            ];
            
            $params = [
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
            ];
            
            if ($this->hasThemeColumn('color_tokens')) {
                $columns[] = 'color_tokens';
                $params[] = isset($themeData['color_tokens']) ? (is_array($themeData['color_tokens']) ? json_encode($themeData['color_tokens']) : $themeData['color_tokens']) : null;
            }
            if ($this->hasThemeColumn('typography_tokens')) {
                $columns[] = 'typography_tokens';
                $params[] = isset($themeData['typography_tokens']) ? (is_array($themeData['typography_tokens']) ? json_encode($themeData['typography_tokens']) : $themeData['typography_tokens']) : null;
            }
            if ($this->hasThemeColumn('spacing_tokens')) {
                $columns[] = 'spacing_tokens';
                $params[] = isset($themeData['spacing_tokens']) ? (is_array($themeData['spacing_tokens']) ? json_encode($themeData['spacing_tokens']) : $themeData['spacing_tokens']) : null;
            }
            if ($this->hasThemeColumn('shape_tokens')) {
                $columns[] = 'shape_tokens';
                $params[] = isset($themeData['shape_tokens']) ? (is_array($themeData['shape_tokens']) ? json_encode($themeData['shape_tokens']) : $themeData['shape_tokens']) : null;
            }
            if ($this->hasThemeColumn('motion_tokens')) {
                $columns[] = 'motion_tokens';
                $params[] = isset($themeData['motion_tokens']) ? (is_array($themeData['motion_tokens']) ? json_encode($themeData['motion_tokens']) : $themeData['motion_tokens']) : null;
            }
            if ($this->hasThemeColumn('layout_density')) {
                $columns[] = 'layout_density';
                $params[] = $themeData['layout_density'] ?? null;
            }
            
            if ($this->hasThemeColumn('categories')) {
                $columns[] = 'categories';
                $params[] = isset($themeData['categories']) ? (is_array($themeData['categories']) ? json_encode($themeData['categories']) : $themeData['categories']) : null;
            }
            
            if ($this->hasThemeColumn('tags')) {
                $columns[] = 'tags';
                $params[] = isset($themeData['tags']) ? (is_array($themeData['tags']) ? json_encode($themeData['tags']) : $themeData['tags']) : null;
            }
            
            $columns[] = 'is_active';
            $params[] = 1;
            
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO themes (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
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
            
            if ($this->hasThemeColumn('color_tokens') && isset($themeData['color_tokens'])) {
                $updates[] = "color_tokens = ?";
                $params[] = is_array($themeData['color_tokens']) ? json_encode($themeData['color_tokens']) : $themeData['color_tokens'];
            }
            
            if ($this->hasThemeColumn('typography_tokens') && isset($themeData['typography_tokens'])) {
                $updates[] = "typography_tokens = ?";
                $params[] = is_array($themeData['typography_tokens']) ? json_encode($themeData['typography_tokens']) : $themeData['typography_tokens'];
            }
            
            if ($this->hasThemeColumn('spacing_tokens') && isset($themeData['spacing_tokens'])) {
                $updates[] = "spacing_tokens = ?";
                $params[] = is_array($themeData['spacing_tokens']) ? json_encode($themeData['spacing_tokens']) : $themeData['spacing_tokens'];
            }
            
            if ($this->hasThemeColumn('shape_tokens') && isset($themeData['shape_tokens'])) {
                $updates[] = "shape_tokens = ?";
                $params[] = is_array($themeData['shape_tokens']) ? json_encode($themeData['shape_tokens']) : $themeData['shape_tokens'];
            }
            
            if ($this->hasThemeColumn('motion_tokens') && isset($themeData['motion_tokens'])) {
                $updates[] = "motion_tokens = ?";
                $params[] = is_array($themeData['motion_tokens']) ? json_encode($themeData['motion_tokens']) : $themeData['motion_tokens'];
            }
            
            if ($this->hasThemeColumn('layout_density') && isset($themeData['layout_density'])) {
                $updates[] = "layout_density = ?";
                $params[] = $themeData['layout_density'];
            }
            
            if ($this->hasThemeColumn('categories') && isset($themeData['categories'])) {
                $updates[] = "categories = ?";
                $params[] = is_array($themeData['categories']) ? json_encode($themeData['categories']) : $themeData['categories'];
            }
            
            if ($this->hasThemeColumn('tags') && isset($themeData['tags'])) {
                $updates[] = "tags = ?";
                $params[] = is_array($themeData['tags']) ? json_encode($themeData['tags']) : $themeData['tags'];
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

