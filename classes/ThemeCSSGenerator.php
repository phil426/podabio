<?php
/**
 * Theme CSS Generator
 * Centralized CSS generation for theme variables and effects
 * Podn.Bio
 */

require_once __DIR__ . '/Theme.php';
require_once __DIR__ . '/../includes/theme-helpers.php';

class ThemeCSSGenerator {
    private $page;
    private $theme;
    private $themeObj;
    // REMOVED: $colors and $fonts - legacy code removed
    private $pageFonts;
    private $widgetFonts;
    private $pageBackground;
    private $widgetBackground;
    private $widgetBorderColor;
    private $widgetStyles;
    private $spatialEffect;
    private $tokens;
    private $colorTokens;
    private $typographyTokens;
    private $spacingTokens;
    private $shapeTokens;
    private $motionTokens;
    private $iconographyTokens;
    private $layoutDensity;
    private $spacingValues;
    // REMOVED: legacyColorOverridesApplied - legacy code removed
    private $resolvedPageBackgroundValue = null;
    private $resolvedWidgetBackgroundValue = null;
    private $resolvedWidgetBorderColor = null;
    private $resolvedBorderWidth = null;
    private $resolvedBorderRadius = null;
    
    public function __construct($page, $theme = null) {
        $this->page = $page;
        $this->theme = $theme;
        $this->themeObj = new Theme();
        
        // Load all theme data
        // REMOVED: Legacy colors and fonts - not needed
        $this->pageFonts = $this->themeObj->getPageFonts($page, $theme);
        $this->widgetFonts = $this->themeObj->getWidgetFonts($page, $theme);
        $this->pageBackground = $this->themeObj->getPageBackground($page, $theme);
        // DEBUG: Log what getPageBackground returned with full details
        error_log("THEME CSS GENERATOR CONSTRUCTOR DEBUG:");
        error_log("  - page_id: " . ($page['id'] ?? 'unknown'));
        error_log("  - theme_id: " . ($theme['id'] ?? 'null'));
        error_log("  - page['page_background']: " . ($page['page_background'] ?? 'NULL') . " (type: " . gettype($page['page_background'] ?? null) . ")");
        error_log("  - theme['page_background']: " . ($theme['page_background'] ?? 'NULL') . " (type: " . gettype($theme['page_background'] ?? null) . ")");
        error_log("  - getPageBackground() returned: " . ($this->pageBackground ?? 'NULL') . " (type: " . gettype($this->pageBackground ?? null) . ")");
        // DEBUG: Log before calling getWidgetBackground
        error_log("THEME CSS GENERATOR CONSTRUCTOR: About to call getWidgetBackground");
        error_log("  - page['theme_id']: " . ($page['theme_id'] ?? 'NULL'));
        error_log("  - theme['id']: " . ($theme['id'] ?? 'NULL'));
        error_log("  - theme['widget_background'] (raw): " . ($theme['widget_background'] ?? 'NULL') . " (type: " . gettype($theme['widget_background'] ?? null) . ")");
        
        $this->widgetBackground = $this->themeObj->getWidgetBackground($page, $theme);
        
        // DEBUG: Log after calling getWidgetBackground
        error_log("THEME CSS GENERATOR CONSTRUCTOR: After getWidgetBackground call");
        error_log("  - this->widgetBackground: " . ($this->widgetBackground ?? 'NULL') . " (type: " . gettype($this->widgetBackground ?? null) . ", is_empty: " . (empty($this->widgetBackground) ? 'yes' : 'no') . ")");
        
        $this->widgetBorderColor = $this->themeObj->getWidgetBorderColor($page, $theme);
        $this->widgetStyles = $this->themeObj->getWidgetStyles($page, $theme);
        // DEBUG: Log widget_styles to verify glow settings are loaded
        error_log("GLOW DEBUG: widget_styles loaded = " . json_encode($this->widgetStyles));
        $this->spatialEffect = $this->themeObj->getSpatialEffect($page, $theme);
        $this->tokens = $this->themeObj->getThemeTokens($page, $theme);
        $this->colorTokens = $this->tokens['colors'] ?? [];
        $this->typographyTokens = $this->tokens['typography'] ?? [];
        $this->spacingTokens = $this->tokens['spacing'] ?? [];
        $this->shapeTokens = $this->tokens['shape'] ?? [];
        $this->motionTokens = $this->tokens['motion'] ?? [];
        
        // CRITICAL DEBUG: Log shape tokens with full details
        error_log("THEME CSS GENERATOR CONSTRUCTOR: shapeTokens = " . json_encode($this->shapeTokens));
        if (isset($this->shapeTokens['corner'])) {
            error_log("THEME CSS GENERATOR CONSTRUCTOR: shapeTokens.corner = " . json_encode($this->shapeTokens['corner']));
            error_log("THEME CSS GENERATOR CONSTRUCTOR: shapeTokens.corner type = " . gettype($this->shapeTokens['corner']));
            if (is_array($this->shapeTokens['corner'])) {
                error_log("THEME CSS GENERATOR CONSTRUCTOR: shapeTokens.corner keys = " . implode(', ', array_keys($this->shapeTokens['corner'])));
                error_log("THEME CSS GENERATOR CONSTRUCTOR: shapeTokens.corner values = " . implode(', ', array_values($this->shapeTokens['corner'])));
            }
        } else {
            error_log("THEME CSS GENERATOR CONSTRUCTOR: WARNING - shapeTokens.corner is NOT SET!");
        }
        $this->iconographyTokens = $this->tokens['iconography'] ?? [];
        $this->layoutDensity = $this->tokens['layout_density'] ?? 'comfortable';
        $this->spacingValues = $this->spacingTokens['values'] ?? [];

        // REMOVED: Legacy color overrides - no longer needed
    }
    
    /**
     * Calculate relative luminance of a color (for contrast calculation)
     * @param string $color Hex color (#RGB or #RRGGBB)
     * @return float Luminance value between 0 and 1
     */
    private function getLuminance($color) {
        // Validate that this is actually a hex color
        // If it's not a hex color (e.g., a gradient), return a default luminance
        if (!is_string($color) || !preg_match('/^#?[0-9a-fA-F]{3,6}$/', $color)) {
            // Return a neutral luminance (0.5) for non-hex colors
            return 0.5;
        }
        
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Handle 3-digit hex
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        // Ensure we have exactly 6 hex digits
        if (strlen($color) !== 6 || !ctype_xdigit($color)) {
            return 0.5; // Default neutral luminance
        }
        
        // Convert to RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // Normalize to 0-1
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;
        
        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
    
    /**
     * Calculate contrast ratio between two colors
     * @param string $color1 First color
     * @param string $color2 Second color
     * @return float Contrast ratio (1 to 21)
     */
    private function getContrastRatio($color1, $color2) {
        $lum1 = $this->getLuminance($color1);
        $lum2 = $this->getLuminance($color2);
        
        $lighter = max($lum1, $lum2);
        $darker = min($lum1, $lum2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }
    
    /**
     * Get the dominant color from a gradient background
     * @param string $background Background value (color or gradient)
     * @return string Hex color of dominant/middle gradient color
     */
    private function getDominantBackgroundColor($background) {
        // If it's a solid color, return it
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $background)) {
            return $background;
        }
        
        // If it's a gradient, extract colors
        // Match format: linear-gradient(356deg, #dc5555 0%, #991B1B 100%)
        // Also match: linear-gradient(135deg, #F3E8FF 0%, #E9D5FF 100%)
        if (preg_match('/linear-gradient\([^,]+,\s*(#[0-9a-fA-F]{3,6})\s*\d+%,\s*(#[0-9a-fA-F]{3,6})\s*\d+%\)/', $background, $matches)) {
            // Return the average/middle color for gradient
            // For simplicity, we'll use the lighter color as dominant
            $color1 = $matches[1];
            $color2 = $matches[2];
            
            // Normalize hex colors to 6 digits
            $color1 = $this->normalizeHexColor($color1) ?? $color1;
            $color2 = $this->normalizeHexColor($color2) ?? $color2;
            
            // Only calculate luminance if both are valid hex colors
            if ($color1 && $color2 && preg_match('/^#[0-9a-fA-F]{6}$/', $color1) && preg_match('/^#[0-9a-fA-F]{6}$/', $color2)) {
                $lum1 = $this->getLuminance($color1);
                $lum2 = $this->getLuminance($color2);
                
                // Return the lighter color as it's more likely to be the "background"
                return $lum1 > $lum2 ? $color1 : $color2;
            }
            
            // If normalization failed, return the first color
            return $color1 ?: $color2 ?: '#ffffff';
        }
        
        // Fallback to white if we can't parse
        return '#ffffff';
    }
    
    /**
     * Get optimal text color for good contrast against background
     * @param string $backgroundColor Background color
     * @param string $defaultColor Default/primary color to use if contrast is good
     * @return string Hex color that ensures good contrast
     */
    private function getOptimalTextColor($backgroundColor, $defaultColor) {
        $bgColor = $this->getDominantBackgroundColor($backgroundColor);
        $bgLum = $this->getLuminance($bgColor);
        
        // Check contrast with default color
        $contrast = $this->getContrastRatio($defaultColor, $bgColor);
        
        // WCAG AA requires 4.5:1 for normal text, 3:1 for large text
        // We'll use 4:1 as a reasonable threshold
        if ($contrast >= 4.0) {
            return $defaultColor;
        }
        
        // If contrast is poor, choose white or black based on background
        // If background is dark (luminance < 0.5), use white text
        // If background is light (luminance >= 0.5), use black/dark text
        if ($bgLum < 0.5) {
            // Dark background - use white or light color
            // Try white first
            $whiteContrast = $this->getContrastRatio('#ffffff', $bgColor);
            if ($whiteContrast >= 4.0) {
                return '#ffffff';
            }
            // If white doesn't work, use a very light gray
            return '#f0f0f0';
        } else {
            // Light background - use black or dark color
            $blackContrast = $this->getContrastRatio('#000000', $bgColor);
            if ($blackContrast >= 4.0) {
                return '#000000';
            }
            // If black doesn't work, use a very dark gray
            return '#1a1a1a';
        }
    }
    
    /**
     * Generate CSS variables block
     * @return string CSS :root block with all variables
     */
    public function generateCSSVariables() {
        // CRITICAL: Read from shape_tokens and spacing_tokens, NOT legacy widget_styles
        // Shape tokens structure: { corner: { md: '0.9rem' }, border_width: { regular: '2px' }, shadow: { level_1: '...' } }
        
        // Get border width from shape_tokens.border_width - NO LEGACY FALLBACKS
        // Check ALL border_width values and use the first non-zero one found
        $borderWidthValue = null;
        if (!empty($this->shapeTokens['border_width']) && is_array($this->shapeTokens['border_width'])) {
            // Priority: regular (thick) > hairline (thin) > bold
            if (!empty($this->shapeTokens['border_width']['regular'])) {
                $borderWidthValue = $this->shapeTokens['border_width']['regular'];
            } elseif (!empty($this->shapeTokens['border_width']['hairline'])) {
                $borderWidthValue = $this->shapeTokens['border_width']['hairline'];
            } elseif (!empty($this->shapeTokens['border_width']['bold'])) {
                $borderWidthValue = $this->shapeTokens['border_width']['bold'];
            } else {
                // If none of the standard keys, get the first non-zero value
                foreach ($this->shapeTokens['border_width'] as $key => $value) {
                    if (!empty($value) && $value !== '0' && $value !== '0px') {
                        $borderWidthValue = $value;
                        break;
                    }
                }
            }
        }
        // NO LEGACY FALLBACKS - use only shape_tokens
        $this->resolvedBorderWidth = $borderWidthValue ?: '0px';
        $borderWidth = $this->resolvedBorderWidth; // For CSS variable output
        
        // Get spacing from spacing_tokens.density
        $spacing = '1.5rem'; // Default
        if (!empty($this->spacingValues['lg'])) {
            $spacing = $this->spacingValues['lg'];
        } elseif (!empty($this->layoutDensity)) {
            // Calculate based on density multipliers
            $baseSpacing = $this->spacingValues['md'] ?? '1rem';
            $density = $this->layoutDensity;
            $multipliers = [
                'compact' => 0.8,
                'cozy' => 1.0,
                'comfortable' => 1.75
            ];
            $multiplier = $multipliers[$density] ?? 1.0;
            $spacing = (floatval($baseSpacing) * $multiplier) . 'rem';
        }
        
        // Get border radius from shape_tokens.corner - NO LEGACY FALLBACKS
        // CRITICAL: We need to find which corner value is the "active" one
        // The theme saves only ONE corner value (the active one), but mergeTokens keeps all defaults
        // So we need to check which corner value was explicitly set by the theme
        $borderRadiusValue = '0.75rem'; // Default rounded (md)
        if (!empty($this->shapeTokens['corner']) && is_array($this->shapeTokens['corner'])) {
            // DEBUG: Log all corner values
            error_log("THEME CSS DEBUG: shape_tokens.corner = " . json_encode($this->shapeTokens['corner']));
            error_log("THEME CSS DEBUG: shape_tokens.corner keys = " . implode(', ', array_keys($this->shapeTokens['corner'])));
            error_log("THEME CSS DEBUG: shape_tokens.corner values = " . implode(', ', array_values($this->shapeTokens['corner'])));
            
            // CRITICAL: getShapeTokens replaces corner with only theme's values if theme has corner
            // If theme has no corner, defaults are used (which have all values)
            // So we need to handle both cases:
            // 1. Theme has corner: only one value exists, use it
            // 2. Theme has no corner: all default values exist, use 'md' (rounded) as default
            $cornerKeys = array_keys($this->shapeTokens['corner']);
            $cornerValues = array_values($this->shapeTokens['corner']);
            
            if (count($cornerValues) === 1) {
                // Theme has corner - only one value exists, use it
                $borderRadiusValue = $cornerValues[0];
                $activeKey = $cornerKeys[0];
                error_log("THEME CSS DEBUG: Using theme corner value: {$activeKey} = {$borderRadiusValue}");
            } elseif (count($cornerValues) > 1) {
                // Theme has no corner - defaults are used (all values exist)
                // Use 'md' (rounded) as the default
                if (isset($this->shapeTokens['corner']['md'])) {
                    $borderRadiusValue = $this->shapeTokens['corner']['md'];
                    error_log("THEME CSS DEBUG: Using default corner value: md = {$borderRadiusValue}");
                } else {
                    // Fallback: use first value
                    $borderRadiusValue = $cornerValues[0];
                    $activeKey = $cornerKeys[0];
                    error_log("THEME CSS DEBUG: Using first corner value: {$activeKey} = {$borderRadiusValue}");
                }
            } else {
                // No corner values found, use default
                error_log("THEME CSS WARNING: No corner values found in shapeTokens, using default rounded");
            }
        } else {
            error_log("THEME CSS WARNING: shape_tokens.corner is empty or not an array!");
        }
        // NO LEGACY FALLBACKS - use only shape_tokens
        error_log("THEME CSS DEBUG: resolved border radius = " . $borderRadiusValue);
        $this->resolvedBorderRadius = $borderRadiusValue;
        $borderRadius = $this->resolvedBorderRadius; // For CSS variable output
        
        // Get button corner radius from shape_tokens.button_corner (for page-level buttons)
        $buttonCornerRadius = '0.75rem'; // Default rounded
        if (!empty($this->shapeTokens['button_corner']) && is_array($this->shapeTokens['button_corner'])) {
            // Priority: md (medium/rounded) > pill > none (square)
            if (!empty($this->shapeTokens['button_corner']['md'])) {
                $buttonCornerRadius = $this->shapeTokens['button_corner']['md'];
            } elseif (!empty($this->shapeTokens['button_corner']['pill'])) {
                $buttonCornerRadius = $this->shapeTokens['button_corner']['pill'];
            } elseif (!empty($this->shapeTokens['button_corner']['none'])) {
                $buttonCornerRadius = $this->shapeTokens['button_corner']['none'];
            } else {
                // Get the first value found
                $buttonCornerValues = array_values($this->shapeTokens['button_corner']);
                if (!empty($buttonCornerValues[0])) {
                    $buttonCornerRadius = $buttonCornerValues[0];
                }
            }
        }
        
        // Get border effect from widget_styles
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        
        // NO LEGACY FALLBACKS - use only colorTokens
        $textPrimary = $this->colorTokens['text']['primary'] ?? '#0f172a';
        $textSecondary = $this->colorTokens['text']['secondary'] ?? '#64748b';
        $textInverse = $this->colorTokens['text']['inverse'] ?? '#ffffff';
        $accentPrimary = $this->colorTokens['accent']['primary'] ?? '#2563eb';
        $accentMuted = $this->colorTokens['accent']['muted'] ?? '#e0edff';
        
        // CRITICAL: Use ONLY theme.page_background - no fallbacks, no legacy, just the theme value
        $pageBackgroundValue = $this->pageBackground;
        
        // NO FALLBACKS - if theme doesn't have page_background, that's an error
        if (empty($pageBackgroundValue) || $pageBackgroundValue === null || $pageBackgroundValue === '') {
            error_log("THEME CSS ERROR: theme.page_background is empty/null! Theme should always have a background.");
            // Still output something to prevent broken CSS, but log the error
            $pageBackgroundValue = '#ffffff';
        }
        
        $this->resolvedPageBackgroundValue = $pageBackgroundValue; // Store for use in generateCompleteStyleBlock
        $backgroundBase = $pageBackgroundValue; // Use the resolved page background as base
        
        // DEBUG: Log page background resolution with full details
        error_log("THEME CSS generateCSSVariables DEBUG:");
        error_log("  - this->pageBackground: " . ($this->pageBackground ?? 'NULL') . " (type: " . gettype($this->pageBackground ?? null) . ", length: " . (is_string($this->pageBackground) ? strlen($this->pageBackground) : 'N/A') . ")");
        error_log("  - colorTokens[background][base]: " . ($this->colorTokens['background']['base'] ?? 'NULL'));
        error_log("  - resolved pageBackgroundValue: " . $pageBackgroundValue . " (type: " . gettype($pageBackgroundValue) . ", length: " . (is_string($pageBackgroundValue) ? strlen($pageBackgroundValue) : 'N/A') . ")");
        // NO LEGACY FALLBACKS - use only colorTokens
        $backgroundSurface = $this->colorTokens['background']['surface'] ?? '#f8fafc';
        $backgroundSurfaceRaised = $this->colorTokens['background']['surface_raised'] ?? $backgroundSurface;
        $backgroundOverlay = $this->colorTokens['background']['overlay'] ?? 'rgba(15, 23, 42, 0.6)';
        $borderDefault = $this->colorTokens['border']['default'] ?? '#e2e8f0';
        $borderFocusColor = $this->colorTokens['border']['focus'] ?? $accentPrimary;
        $shadowAmbient = $this->colorTokens['shadow']['ambient'] ?? 'rgba(15, 23, 42, 0.12)';
        $shadowFocus = $this->colorTokens['shadow']['focus'] ?? 'rgba(37, 99, 235, 0.35)';
        
        $stateColors = $this->colorTokens['state'] ?? [];
        $stateTextColors = $this->colorTokens['text_state'] ?? [];
        $gradientTokens = $this->colorTokens['gradient'] ?? [];
        $glowTokens = $this->colorTokens['glow'] ?? [];
        
        // CRITICAL: Use ONLY theme.widget_border_color - no fallbacks
        $this->resolvedWidgetBorderColor = $this->widgetBorderColor;
        if (empty($this->resolvedWidgetBorderColor) || $this->resolvedWidgetBorderColor === null || $this->resolvedWidgetBorderColor === '') {
            error_log("THEME CSS ERROR: widget_border_color is empty/null! Using minimal fallback only to prevent broken CSS.");
            $this->resolvedWidgetBorderColor = '#e2e8f0'; // Only to prevent broken CSS
        }
        
        // CRITICAL: Use ONLY theme.widget_background - no fallbacks
        // Priority: theme.widget_background column > colorTokens.background.surface
        error_log("THEME CSS generateCSSVariables: Starting widget background resolution");
        error_log("  - this->widgetBackground (from constructor): " . ($this->widgetBackground ?? 'NULL') . " (type: " . gettype($this->widgetBackground ?? null) . ", is_empty: " . (empty($this->widgetBackground) ? 'yes' : 'no') . ", === null: " . ($this->widgetBackground === null ? 'yes' : 'no') . ", === '': " . ($this->widgetBackground === '' ? 'yes' : 'no') . ")");
        error_log("  - colorTokens[background][surface]: " . ($this->colorTokens['background']['surface'] ?? 'NULL') . " (type: " . gettype($this->colorTokens['background']['surface'] ?? null) . ")");
        
        $this->resolvedWidgetBackgroundValue = $this->widgetBackground;
        
        // If widget_background is empty, try colorTokens.background.surface (this is synced with widget_background in ThemeEditorPanel)
        if (empty($this->resolvedWidgetBackgroundValue) || $this->resolvedWidgetBackgroundValue === null || $this->resolvedWidgetBackgroundValue === '') {
            error_log("THEME CSS DEBUG: widget_background is empty/null, checking colorTokens.background.surface");
            $surfaceColor = $this->colorTokens['background']['surface'] ?? null;
            if (!empty($surfaceColor) && $surfaceColor !== null && $surfaceColor !== '') {
                error_log("THEME CSS DEBUG: Using colorTokens.background.surface = " . $surfaceColor);
                $this->resolvedWidgetBackgroundValue = $surfaceColor;
            } else {
                error_log("THEME CSS ERROR: Both widget_background and colorTokens.background.surface are empty/null!");
                error_log("  - Falling back to #ffffff to prevent broken CSS");
                error_log("  - THIS IS THE SOURCE OF #ffffff - widget_background was not saved or is empty in database!");
                $this->resolvedWidgetBackgroundValue = '#ffffff'; // Only to prevent broken CSS
            }
        } else {
            error_log("THEME CSS DEBUG: Using widget_background from theme = " . $this->resolvedWidgetBackgroundValue);
        }
        
        error_log("THEME CSS DEBUG: Final resolvedWidgetBackgroundValue = " . $this->resolvedWidgetBackgroundValue . " (type: " . gettype($this->resolvedWidgetBackgroundValue) . ", length: " . (is_string($this->resolvedWidgetBackgroundValue) ? strlen($this->resolvedWidgetBackgroundValue) : 'N/A') . ")");
        
        // Check for page-title-color override in token_overrides
        // Check multiple possible paths: semantic.text.title, colors.text.title, or direct page-title-color
        $pageTitleColorOverride = null;
        if (!empty($this->tokens['semantic']['text']['title'] ?? null)) {
            $pageTitleColorOverride = $this->tokens['semantic']['text']['title'];
        } elseif (!empty($this->tokens['colors']['text']['title'] ?? null)) {
            $pageTitleColorOverride = $this->tokens['colors']['text']['title'];
        } elseif (!empty($this->tokens['page-title-color'] ?? null)) {
            // Direct override for CSS variable name
            $pageTitleColorOverride = $this->tokens['page-title-color'];
        }
        
        // Use override if present, otherwise calculate optimal color
        $pageTitleColor = $pageTitleColorOverride ?: $this->getOptimalTextColor($pageBackgroundValue, $textPrimary);
        $pageDescriptionColor = $this->getOptimalTextColor($pageBackgroundValue, $textSecondary);
        $socialIconColor = $this->getOptimalTextColor($pageBackgroundValue, $accentPrimary);
        $onBackground = $this->getOptimalTextColor($pageBackgroundValue, $textPrimary);
        $onSurface = $this->getOptimalTextColor($backgroundSurface, $textPrimary);
        $onSurfaceRaised = $this->getOptimalTextColor($backgroundSurfaceRaised, $textPrimary);
        $onAccent = $this->getOptimalTextColor($accentPrimary, $textInverse);
        
        $css = ":root {\n";
        
        // Tokenized color variables
        $css .= "    --color-background-base: " . h($backgroundBase) . ";\n";
        $css .= "    --color-background-surface: " . h($backgroundSurface) . ";\n";
        $css .= "    --color-background-surface-raised: " . h($backgroundSurfaceRaised) . ";\n";
        $css .= "    --color-background-overlay: " . h($backgroundOverlay) . ";\n";
        $css .= "    --color-text-primary: " . h($textPrimary) . ";\n";
        $css .= "    --color-text-secondary: " . h($textSecondary) . ";\n";
        $css .= "    --color-text-inverse: " . h($textInverse) . ";\n";
        $css .= "    --color-border-default: " . h($borderDefault) . ";\n";
        $css .= "    --color-border-focus: " . h($borderFocusColor) . ";\n";
        $css .= "    --color-accent-primary: " . h($accentPrimary) . ";\n";
        $css .= "    --color-accent-muted: " . h($accentMuted) . ";\n";
        $css .= "    --color-state-success: " . h($stateColors['success'] ?? '#12b76a') . ";\n";
        $css .= "    --color-state-warning: " . h($stateColors['warning'] ?? '#f59e0b') . ";\n";
        $css .= "    --color-state-danger: " . h($stateColors['danger'] ?? '#ef4444') . ";\n";
        $css .= "    --color-text-state-success: " . h($stateTextColors['success'] ?? '#0f5132') . ";\n";
        $css .= "    --color-text-state-warning: " . h($stateTextColors['warning'] ?? '#7c2d12') . ";\n";
        $css .= "    --color-text-state-danger: " . h($stateTextColors['danger'] ?? '#7f1d1d') . ";\n";
        $css .= "    --color-text-on-background: " . h($onBackground) . ";\n";
        $css .= "    --color-text-on-surface: " . h($onSurface) . ";\n";
        $css .= "    --color-text-on-surface-raised: " . h($onSurfaceRaised) . ";\n";
        $css .= "    --color-text-on-accent: " . h($onAccent) . ";\n";
        $css .= "    --color-shadow-ambient: " . h($shadowAmbient) . ";\n";
        $css .= "    --color-shadow-focus: " . h($shadowFocus) . ";\n";
        // Use gradient.page token if available, otherwise check if page_background is a gradient
        $gradientPageValue = $gradientTokens['page'] ?? null;
        if (!$gradientPageValue && (strpos($pageBackgroundValue, 'gradient') !== false || strpos($pageBackgroundValue, 'linear-gradient') !== false || strpos($pageBackgroundValue, 'radial-gradient') !== false)) {
            $gradientPageValue = $pageBackgroundValue;
        }
        $css .= "    --gradient-page: " . h($gradientPageValue ?? $pageBackgroundValue) . ";\n";
        $css .= "    --gradient-accent: " . h($gradientTokens['accent'] ?? $accentPrimary) . ";\n";
        $css .= "    --gradient-widget: " . h($gradientTokens['widget'] ?? $this->resolvedWidgetBackgroundValue ?? '#ffffff') . ";\n";
        $css .= "    --gradient-podcast: " . h($gradientTokens['podcast'] ?? ($gradientTokens['accent'] ?? $accentPrimary)) . ";\n";
        $css .= "    --aurora-glow-color: " . h($glowTokens['primary'] ?? $accentPrimary) . ";\n";
        $shellBaseColor = $this->getDominantBackgroundColor($backgroundBase);
        $shellBackground = $this->lightenColor($shellBaseColor, 0.85) ?? $shellBaseColor;
        if (!$shellBackground) {
            $shellBackground = '#f5f7fa';
        }
        $css .= "    --shell-background: " . h($shellBackground) . ";\n";
        
        // Tokenized spacing values (page spacing)
        foreach ($this->spacingValues as $token => $value) {
            $css .= "    --space-" . h($token) . ": " . h($value) . ";\n";
        }
        $css .= "    --layout-density: " . h($this->layoutDensity) . ";\n";
        
        // Widget spacing values (use same density as page spacing)
        // Widgets use the same spacing density as the page
        $baseScale = $this->spacingTokens['base_scale'] ?? [
            '2xs' => 0.25, 'xs' => 0.5, 'sm' => 0.75, 'md' => 1.0,
            'lg' => 1.5, 'xl' => 2.0, '2xl' => 3.0
        ];
        $density = $this->spacingTokens['density'] ?? 'cozy';
        $densityMultipliers = $this->spacingTokens['density_multipliers'][$density] ?? [
            '2xs' => 0.85, 'xs' => 0.9, 'sm' => 0.95, 'md' => 1.0,
            'lg' => 1.0, 'xl' => 1.05, '2xl' => 1.1
        ];
        
        // Calculate widget spacing values (same as page spacing)
        foreach ($baseScale as $token => $base) {
            $multiplier = isset($densityMultipliers[$token]) ? (float)$densityMultipliers[$token] : 1.0;
            $widgetValue = $base * $multiplier;
            $css .= "    --widget-space-" . h($token) . ": " . h($widgetValue) . "rem;\n";
        }
        
        // Shape tokens
        foreach ($this->shapeTokens['corner'] ?? [] as $name => $value) {
            $css .= "    --shape-corner-" . h(str_replace('_', '-', $name)) . ": " . h($value) . ";\n";
        }
        foreach ($this->shapeTokens['border_width'] ?? [] as $name => $value) {
            $css .= "    --border-width-" . h(str_replace('_', '-', $name)) . ": " . h($value) . ";\n";
        }
        foreach ($this->shapeTokens['shadow'] ?? [] as $name => $value) {
            $css .= "    --shadow-" . h(str_replace('_', '-', $name)) . ": " . h($value) . ";\n";
        }
        
        // Typography tokens
        $headingFont = $this->typographyTokens['font']['heading'] ?? $this->pageFonts['page_primary_font'];
        $bodyFont = $this->typographyTokens['font']['body'] ?? $this->pageFonts['page_secondary_font'];
        $metaFont = $this->typographyTokens['font']['metatext'] ?? $bodyFont;
        
        // Typography colors - read from typography_tokens (saved by Edit Theme Panel)
        // Priority: typography_tokens.color.heading/body (from Edit Theme Panel) > token_overrides > defaults
        $headingColor = null;
        $bodyColor = null;
        
        // Check typography_tokens first (this is what Edit Theme Panel saves to)
        if (!empty($this->typographyTokens['color']['heading'] ?? null)) {
            $headingColor = $this->typographyTokens['color']['heading'];
        }
        if (!empty($this->typographyTokens['color']['body'] ?? null)) {
            $bodyColor = $this->typographyTokens['color']['body'];
        }
        
        // Fallback to token_overrides if typography_tokens don't have colors
        if (!$headingColor && !empty($this->tokens['typography']['color']['heading'] ?? null)) {
            $headingColor = $this->tokens['typography']['color']['heading'];
        } elseif (!$headingColor && !empty($this->tokens['core']['typography']['color']['heading'] ?? null)) {
            $headingColor = $this->tokens['core']['typography']['color']['heading'];
        }
        if (!$bodyColor && !empty($this->tokens['typography']['color']['body'] ?? null)) {
            $bodyColor = $this->tokens['typography']['color']['body'];
        } elseif (!$bodyColor && !empty($this->tokens['core']['typography']['color']['body'] ?? null)) {
            $bodyColor = $this->tokens['core']['typography']['color']['body'];
        }
        
        // Widget typography colors (separate from page typography)
        $widgetHeadingColor = null;
        $widgetBodyColor = null;
        
        // Check widget typography colors first (this is what Edit Theme Panel saves for widgets)
        if (!empty($this->typographyTokens['color']['widget_heading'] ?? null)) {
            $widgetHeadingColor = $this->typographyTokens['color']['widget_heading'];
        }
        if (!empty($this->typographyTokens['color']['widget_body'] ?? null)) {
            $widgetBodyColor = $this->typographyTokens['color']['widget_body'];
        }
        
        // Fallback to page typography colors if widget colors not set
        if (!$widgetHeadingColor) {
            $widgetHeadingColor = $headingColor;
        }
        if (!$widgetBodyColor) {
            $widgetBodyColor = $bodyColor;
        }
        
        $css .= "    --font-family-heading: '" . h($headingFont) . "', sans-serif;\n";
        $css .= "    --font-family-body: '" . h($bodyFont) . "', sans-serif;\n";
        $css .= "    --font-family-meta: '" . h($metaFont) . "', sans-serif;\n";
        
        // Typography color variables (for page typography)
        // Check if colors are gradients and handle accordingly
        $isHeadingGradient = $headingColor && (strpos($headingColor, 'gradient') !== false || strpos($headingColor, 'linear-gradient') !== false || strpos($headingColor, 'radial-gradient') !== false);
        $isBodyGradient = $bodyColor && (strpos($bodyColor, 'gradient') !== false || strpos($bodyColor, 'linear-gradient') !== false || strpos($bodyColor, 'radial-gradient') !== false);
        
        if ($headingColor) {
            $css .= "    --heading-font-color: " . h($headingColor) . ";\n";
            if ($isHeadingGradient) {
                $css .= "    --heading-font-gradient: " . h($headingColor) . ";\n";
            }
        }
        if ($bodyColor) {
            $css .= "    --body-font-color: " . h($bodyColor) . ";\n";
            if ($isBodyGradient) {
                $css .= "    --body-font-gradient: " . h($bodyColor) . ";\n";
            }
        }
        
        // Widget typography color variables (separate from page typography)
        $isWidgetHeadingGradient = $widgetHeadingColor && (strpos($widgetHeadingColor, 'gradient') !== false || strpos($widgetHeadingColor, 'linear-gradient') !== false || strpos($widgetHeadingColor, 'radial-gradient') !== false);
        $isWidgetBodyGradient = $widgetBodyColor && (strpos($widgetBodyColor, 'gradient') !== false || strpos($widgetBodyColor, 'linear-gradient') !== false || strpos($widgetBodyColor, 'radial-gradient') !== false);
        
        if ($widgetHeadingColor) {
            $css .= "    --widget-heading-font-color: " . h($widgetHeadingColor) . ";\n";
            if ($isWidgetHeadingGradient) {
                $css .= "    --widget-heading-font-gradient: " . h($widgetHeadingColor) . ";\n";
            }
        } else {
            // Fallback to page heading color if widget color not set
            $css .= "    --widget-heading-font-color: var(--heading-font-color, var(--color-text-primary, #0f172a));\n";
        }
        if ($widgetBodyColor) {
            $css .= "    --widget-body-font-color: " . h($widgetBodyColor) . ";\n";
            if ($isWidgetBodyGradient) {
                $css .= "    --widget-body-font-gradient: " . h($widgetBodyColor) . ";\n";
            }
        } else {
            // Fallback to page body color if widget color not set
            $css .= "    --widget-body-font-color: var(--body-font-color, var(--color-text-secondary, #64748b));\n";
        }
        
        // Generate type scale CSS variables
        $scaleTokens = $this->typographyTokens['scale'] ?? [];
        if (!empty($scaleTokens)) {
            error_log("CSS GENERATOR DEBUG: Generating scale variables, xl=" . ($scaleTokens['xl'] ?? 'null') . ", sm=" . ($scaleTokens['sm'] ?? 'null'));
            foreach ($scaleTokens as $name => $value) {
                $css .= "    --type-scale-" . h($name) . ": " . h($value) . "rem;\n";
            }
        } else {
            error_log("CSS GENERATOR DEBUG: No scale tokens found in typographyTokens");
        }
        foreach ($this->typographyTokens['line_height'] ?? [] as $name => $value) {
            $css .= "    --type-line-height-" . h($name) . ": " . h($value) . ";\n";
        }
        foreach ($this->typographyTokens['weight'] ?? [] as $name => $value) {
            $css .= "    --type-weight-" . h($name) . ": " . h($value) . ";\n";
        }
        
        // Motion tokens
        foreach ($this->motionTokens['duration'] ?? [] as $name => $value) {
            $css .= "    --motion-duration-" . h($name) . ": " . h($value) . ";\n";
        }
        foreach ($this->motionTokens['easing'] ?? [] as $name => $value) {
            $css .= "    --motion-easing-" . h($name) . ": " . h($value) . ";\n";
        }
        $css .= "    --focus-ring-width: " . h($this->motionTokens['focus']['ring_width'] ?? '3px') . ";\n";
        $css .= "    --focus-ring-offset: " . h($this->motionTokens['focus']['ring_offset'] ?? '2px') . ";\n";
        $css .= "    --focus-ring-color: " . h($borderFocusColor) . ";\n";
        
        // REMOVED: Legacy color variables - no longer needed
        
        // Text colors with guaranteed contrast
        $css .= "    --page-title-color: " . h($pageTitleColor) . ";\n";
        $css .= "    --page-description-color: " . h($pageDescriptionColor) . ";\n";
        $css .= "    --social-icon-color: " . h($socialIconColor) . ";\n";
        
        // Iconography tokens - Always generate variables (use fallbacks if not set)
        $iconSize = $this->iconographyTokens['size'] ?? '48px';
        $iconColor = $this->iconographyTokens['color'] ?? '';
        $iconSpacing = $this->iconographyTokens['spacing'] ?? '0.75rem';
        
        // DEBUG: Log iconography tokens
        error_log("ICONOGRAPHY DEBUG: size=" . ($iconSize ?? 'null') . ", color=" . ($iconColor ?? 'null') . ", spacing=" . ($iconSpacing ?? 'null'));
        error_log("ICONOGRAPHY DEBUG: iconographyTokens=" . json_encode($this->iconographyTokens));
        error_log("ICONOGRAPHY DEBUG: iconColor type=" . gettype($iconColor) . ", empty=" . (empty($iconColor) ? 'yes' : 'no') . ", isset=" . (isset($this->iconographyTokens['color']) ? 'yes' : 'no'));
        
        $css .= "    --icon-size: " . h($iconSize) . ";\n";
        $css .= "    --icon-spacing: " . h($iconSpacing) . ";\n";
        // Generate --icon-color if it's set and not empty
        if (isset($this->iconographyTokens['color']) && $this->iconographyTokens['color'] !== null && $this->iconographyTokens['color'] !== '') {
            $css .= "    --icon-color: " . h($iconColor) . ";\n";
            error_log("ICONOGRAPHY DEBUG: Generated --icon-color CSS variable: " . h($iconColor));
        } else {
            error_log("ICONOGRAPHY DEBUG: No icon color set, skipping --icon-color variable");
        }
        
        // Legacy font variables for backward compatibility
        // REMOVED: Legacy font variables - no longer needed
        
        // Page font variables
        $css .= "    --page-primary-font: '" . h($this->pageFonts['page_primary_font']) . "';\n";
        $css .= "    --page-secondary-font: '" . h($this->pageFonts['page_secondary_font']) . "';\n";
        
        // Widget font variables (default to page fonts if not set)
        $css .= "    --widget-primary-font: '" . h($this->widgetFonts['widget_primary_font']) . "';\n";
        $css .= "    --widget-secondary-font: '" . h($this->widgetFonts['widget_secondary_font']) . "';\n";
        
        // NO FALLBACKS - use only the theme background value
        error_log("THEME CSS DEBUG: Setting --page-background CSS variable to: " . $pageBackgroundValue);
        $css .= "    --page-background: " . h($pageBackgroundValue) . ";\n";
        // REMOVED: --widget-background CSS variable - using direct value in .widget-item instead
        // $css .= "    --widget-background: " . h($this->resolvedWidgetBackgroundValue) . ";\n";
        $css .= "    --widget-border-width: " . h($this->resolvedBorderWidth) . ";\n";
        $css .= "    --widget-border-color: " . h($this->resolvedWidgetBorderColor) . ";\n";
        $css .= "    --widget-spacing: {$spacing};\n";
        $css .= "    --widget-border-radius: " . h($this->resolvedBorderRadius) . ";\n";
        $css .= "    --text-color: var(--color-text-primary);\n";
        
        // Page-level spacing based on density
        // Page padding (left and right sides of page) - use lg spacing token for more density responsiveness
        // spacingValues already has density multipliers applied from Theme.php::getSpacingTokens()
        $pagePadding = $this->spacingValues['lg'] ?? '1.5rem';
        $css .= "    --page-padding: " . h($pagePadding) . ";\n";
        
        // Widget gap (spacing between blocks) - also uses lg spacing token
        $widgetGap = $this->spacingValues['lg'] ?? '1.5rem';
        $css .= "    --widget-gap: " . h($widgetGap) . ";\n";
        
        // Button corner radius (for page-level buttons)
        $css .= "    --button-corner-radius: " . h($buttonCornerRadius) . ";\n";
        
        // Add shadow or glow variables based on border effect
        if ($borderEffect === 'shadow') {
            $shadowIntensity = convertEnumToCSS($this->widgetStyles['border_shadow_intensity'] ?? 'subtle', 'shadow');
            $css .= "    --widget-box-shadow: {$shadowIntensity};\n";
        } elseif ($borderEffect === 'glow') {
            $glowColor = $this->widgetStyles['glow_color'] ?? '#ff00ff';
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'subtle';
            $glowBlur = convertEnumToCSS($glowIntensity, 'glow_blur');
            $glowOpacity = convertEnumToCSS($glowIntensity, 'glow_opacity');
            $css .= "    --widget-glow-color: " . h($glowColor) . ";\n";
            $css .= "    --widget-glow-blur: {$glowBlur};\n";
            $css .= "    --widget-glow-opacity: {$glowOpacity};\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Generate spatial effect CSS classes
     * @return string CSS for spatial effects
     */
    public function generateSpatialEffectCSS() {
        $css = "";
        
        if ($this->spatialEffect === 'glass') {
            $css .= "body.spatial-glass {\n";
            $css .= "    background: var(--page-background);\n";
            $css .= "    backdrop-filter: blur(20px) saturate(180%);\n";
            $css .= "    -webkit-backdrop-filter: blur(20px) saturate(180%);\n";
            $css .= "}\n\n";
            $css .= "body.spatial-glass .widget-item {\n";
            // CRITICAL: Don't override background - use the theme widget background with opacity
            // Apply backdrop-filter for glass effect but keep the theme background
            $css .= "    backdrop-filter: blur(10px);\n";
            $css .= "    -webkit-backdrop-filter: blur(10px);\n";
            $css .= "}\n\n";
        } elseif ($this->spatialEffect === 'depth') {
            $css .= "body.spatial-depth {\n";
            $css .= "    perspective: 1000px;\n";
            $css .= "}\n\n";
            $css .= "body.spatial-depth .widget-item {\n";
            $css .= "    transform-style: preserve-3d;\n";
            $css .= "    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);\n";
            $css .= "    transition: transform 0.3s ease;\n";
            $css .= "}\n\n";
            $css .= "body.spatial-depth .widget-item:hover {\n";
            $css .= "    transform: translateZ(10px);\n";
            $css .= "}\n\n";
        } elseif ($this->spatialEffect === 'floating') {
            $css .= "body.spatial-floating {\n";
            $css .= "    padding: 2rem;\n";
            $css .= "}\n\n";
            $css .= "body.spatial-floating .page-container {\n";
            // REMOVED: Legacy --secondary-color reference
            $css .= "    background: var(--color-background-surface);\n";
            $css .= "    border-radius: 24px;\n";
            $css .= "    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);\n";
            $css .= "    padding: 2rem;\n";
            $css .= "    max-width: 1200px;\n";
            $css .= "    margin: 0 auto;\n";
            $css .= "}\n\n";
        } elseif ($this->spatialEffect === 'tilt') {
            $css .= "body.spatial-tilt .widget-item {\n";
            $css .= "    will-change: transform;\n";
            $css .= "    transition: transform 0.1s ease-out;\n";
            $css .= "    transform-style: preserve-3d;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }
    
    /**
     * Generate glow animation CSS
     * @return string CSS keyframes and rules for glow effect
     */
    public function generateGlowAnimationCSS() {
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        
        if ($borderEffect !== 'glow') {
            return "";
        }
        
        $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'subtle';
        // No need to check for 'none' - glow intensity is always 'subtle' or 'pronounced'
        
        $css = "@keyframes glow-pulse {\n";
        $css .= "    0%, 100% { opacity: 0.8; }\n";
        $css .= "    50% { opacity: 1; }\n";
        $css .= "}\n\n";
        
        $css .= "@keyframes glow-rotate {\n";
        $css .= "    0% { filter: blur(var(--widget-glow-blur)) hue-rotate(0deg); }\n";
        $css .= "    100% { filter: blur(var(--widget-glow-blur)) hue-rotate(360deg); }\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Generate complete style block
     * @return string Complete <style> block ready for page.php
     */
    public function generateCompleteStyleBlock() {
        $css = "<style>\n";
        $css .= $this->generateCSSVariables();
        
        // Re-read page typography colors for page-level CSS rules
        $headingColor = null;
        $bodyColor = null;
        
        // Check typography_tokens first (this is what Edit Theme Panel saves to)
        if (!empty($this->typographyTokens['color']['heading'] ?? null)) {
            $headingColor = $this->typographyTokens['color']['heading'];
        }
        if (!empty($this->typographyTokens['color']['body'] ?? null)) {
            $bodyColor = $this->typographyTokens['color']['body'];
        }
        
        // Fallback to token_overrides if typography_tokens don't have colors
        if (!$headingColor && !empty($this->tokens['typography']['color']['heading'] ?? null)) {
            $headingColor = $this->tokens['typography']['color']['heading'];
        } elseif (!$headingColor && !empty($this->tokens['core']['typography']['color']['heading'] ?? null)) {
            $headingColor = $this->tokens['core']['typography']['color']['heading'];
        }
        if (!$bodyColor && !empty($this->tokens['typography']['color']['body'] ?? null)) {
            $bodyColor = $this->tokens['typography']['color']['body'];
        } elseif (!$bodyColor && !empty($this->tokens['core']['typography']['color']['body'] ?? null)) {
            $bodyColor = $this->tokens['core']['typography']['color']['body'];
        }
        
        // Check if page colors are gradients
        $isHeadingGradient = $headingColor && (strpos($headingColor, 'gradient') !== false || strpos($headingColor, 'linear-gradient') !== false || strpos($headingColor, 'radial-gradient') !== false);
        $isBodyGradient = $bodyColor && (strpos($bodyColor, 'gradient') !== false || strpos($bodyColor, 'linear-gradient') !== false || strpos($bodyColor, 'radial-gradient') !== false);
        
        // Widget typography colors are now set as CSS variables in generateCSSVariables()
        // We just need to check if they're gradients for the widget CSS rules
        // Read widget colors to check for gradients
        $widgetHeadingColor = null;
        $widgetBodyColor = null;
        
        // Check widget typography colors first (this is what Edit Theme Panel saves for widgets)
        if (!empty($this->typographyTokens['color']['widget_heading'] ?? null)) {
            $widgetHeadingColor = $this->typographyTokens['color']['widget_heading'];
        }
        if (!empty($this->typographyTokens['color']['widget_body'] ?? null)) {
            $widgetBodyColor = $this->typographyTokens['color']['widget_body'];
        }
        
        // Fallback to page typography colors if widget colors not set
        if (!$widgetHeadingColor) {
            if (!empty($this->typographyTokens['color']['heading'] ?? null)) {
                $widgetHeadingColor = $this->typographyTokens['color']['heading'];
            }
        }
        if (!$widgetBodyColor) {
            if (!empty($this->typographyTokens['color']['body'] ?? null)) {
                $widgetBodyColor = $this->typographyTokens['color']['body'];
            }
        }
        
        // Check if widget colors are gradients
        $isWidgetHeadingGradient = $widgetHeadingColor && (strpos($widgetHeadingColor, 'gradient') !== false || strpos($widgetHeadingColor, 'linear-gradient') !== false || strpos($widgetHeadingColor, 'radial-gradient') !== false);
        $isWidgetBodyGradient = $widgetBodyColor && (strpos($widgetBodyColor, 'gradient') !== false || strpos($widgetBodyColor, 'linear-gradient') !== false || strpos($widgetBodyColor, 'radial-gradient') !== false);
        
        // Get the resolved page background value (calculated in generateCSSVariables)
        // NO FALLBACKS - use only resolved value
        $pageBackgroundValue = $this->resolvedPageBackgroundValue ?? $this->pageBackground;
        
        if (empty($pageBackgroundValue) || $pageBackgroundValue === null || $pageBackgroundValue === '') {
            error_log("THEME CSS ERROR: No page background value available!");
            $pageBackgroundValue = '#ffffff'; // Only to prevent broken CSS
        }
        
        // Check if background is a gradient - use the resolved pageBackgroundValue, not this->pageBackground
        // This ensures we check the actual value being used, not the raw database value
        $isGradient = strpos($pageBackgroundValue, 'gradient') !== false || strpos($pageBackgroundValue, 'linear-gradient') !== false || strpos($pageBackgroundValue, 'radial-gradient') !== false;
        
        // DEBUG: Log what we're outputting with full details
        error_log("THEME CSS generateCompleteStyleBlock DEBUG:");
        error_log("  - resolvedPageBackgroundValue: " . ($this->resolvedPageBackgroundValue ?? 'NULL'));
        error_log("  - pageBackgroundValue (used in CSS): " . $pageBackgroundValue . " (type: " . gettype($pageBackgroundValue) . ", length: " . (is_string($pageBackgroundValue) ? strlen($pageBackgroundValue) : 'N/A') . ")");
        error_log("  - isGradient: " . ($isGradient ? 'yes' : 'no'));
        error_log("  - CSS will output: background: " . h($pageBackgroundValue) . " !important;");
        
        // Base body styles
        // CRITICAL: Use the resolved pageBackgroundValue directly (not CSS variable) to ensure it's applied
        // Use !important to override any other styles that might interfere
        $css .= "body {\n";
        $css .= "    font-family: var(--page-secondary-font), var(--body-font), sans-serif;\n";
        // Use direct value with !important - no CSS variable, no fallback, just the actual value
        $css .= "    background: " . h($pageBackgroundValue) . " !important;\n";
        if (!$isGradient) {
            // For solid colors, use fixed attachment for full coverage
            $css .= "    background-attachment: fixed !important;\n";
        }
        $css .= "    min-height: 100vh;\n";
        $css .= "    color: var(--text-color);\n";
        $css .= "    margin: 0;\n";
        $css .= "    padding: 0;\n";
        $css .= "}\n\n";
        
        // Ensure html element also has background for full coverage
        $css .= "html {\n";
        $css .= "    background: " . h($pageBackgroundValue) . " !important;\n";
        $css .= "    min-height: 100%;\n";
        $css .= "}\n\n";
        
        // Typography - page fonts
        $css .= "h1, h2, h3, .page-title {\n";
        $css .= "    font-family: var(--page-primary-font), var(--heading-font), sans-serif;\n";
        if ($headingColor) {
            if ($isHeadingGradient) {
                // For gradients, use background-clip technique
                $css .= "    background: var(--heading-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
            $css .= "    color: var(--heading-font-color);\n";
            }
        }
        $css .= "}\n\n";
        
        // Body text color
        if ($bodyColor) {
            $css .= "body, p, .page-description, .widget-description {\n";
            if ($isBodyGradient) {
                // For gradients, use background-clip technique
                $css .= "    background: var(--body-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
            $css .= "    color: var(--body-font-color);\n";
            }
            $css .= "}\n\n";
        }
        
        // Widget container
        $css .= ".widgets-container {\n";
        $css .= "    gap: var(--widget-spacing);\n";
        $css .= "}\n\n";
        
        // ============================================
        // WIDGET STYLING - Complete rebuild
        // All widget styling comes from here only
        // ============================================
        
        // Widget items - base styling
        // CRITICAL: Use direct values with !important (like page background) to ensure they're applied
        // Ensure resolvedWidgetBackgroundValue is set (should be set by generateCSSVariables, but double-check)
        if (empty($this->resolvedWidgetBackgroundValue)) {
            // Fallback: try to resolve it now if it wasn't set
            $this->resolvedWidgetBackgroundValue = $this->widgetBackground;
            if (empty($this->resolvedWidgetBackgroundValue) || $this->resolvedWidgetBackgroundValue === null || $this->resolvedWidgetBackgroundValue === '') {
                error_log("THEME CSS ERROR: widget_background is empty/null in generateCompleteStyleBlock! Using fallback.");
                $this->resolvedWidgetBackgroundValue = '#ffffff'; // Only to prevent broken CSS
            }
        }
        error_log("THEME CSS DEBUG: Generating .widget-item CSS with background = " . ($this->resolvedWidgetBackgroundValue ?? 'NULL') . " (type: " . gettype($this->resolvedWidgetBackgroundValue ?? null) . ")");
        $css .= ".widget-item {\n";
        // Layout
        $css .= "    display: flex;\n";
        $css .= "    align-items: center;\n";
        $css .= "    gap: var(--widget-space-sm, 0.75rem);\n";
        $css .= "    width: 100%;\n";
        // CRITICAL: Use widget-specific spacing for interior padding
        $css .= "    padding: var(--widget-space-sm, 0.75rem) var(--widget-space-md, 1rem);\n";
        $css .= "    box-sizing: border-box;\n";
        $css .= "    position: relative;\n";
        $css .= "    z-index: auto;\n";
        // Visual styling (from theme) - CRITICAL: Use direct value with !important
        $css .= "    background: " . h($this->resolvedWidgetBackgroundValue) . " !important;\n";
        $css .= "    border: " . h($this->resolvedBorderWidth) . " solid " . h($this->resolvedWidgetBorderColor) . " !important;\n";
        // CRITICAL DEBUG: Log what border-radius is being applied
        error_log("THEME CSS GENERATOR: Applying border-radius to .widget-item: " . $this->resolvedBorderRadius);
        $css .= "    border-radius: " . h($this->resolvedBorderRadius) . " !important;\n";
        
        // Get border effect from widget_styles
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        
        // Apply shadow or glow based on border effect
        // CRITICAL: For glow, don't set box-shadow here - it will be set later after background
        // For shadow, set it here in the base rule
        if ($borderEffect === 'shadow') {
            // Get shadow from shape_tokens.shadow - NO LEGACY FALLBACKS
            $shadowValue = null;
            if (!empty($this->shapeTokens['shadow']['level_1'])) {
                $shadowValue = $this->shapeTokens['shadow']['level_1'];
            } elseif (!empty($this->shapeTokens['shadow']['level_2'])) {
                $shadowValue = $this->shapeTokens['shadow']['level_2'];
            }
            // Apply shadow - if none, use 'none', otherwise use the value
            if ($shadowValue) {
                $css .= "    box-shadow: " . h($shadowValue) . " !important;\n";
            } else {
                $css .= "    box-shadow: none !important;\n";
            }
        } elseif ($borderEffect === 'glow') {
            // For glow effect, don't set box-shadow here - it will be set later
            // This prevents the "box-shadow: none" from overriding the glow
            // The glow box-shadow will be applied after background re-application
            // DO NOT set box-shadow: none here - let the glow rule set it later
            $css .= "    position: relative;\n";
        } else {
            $css .= "    box-shadow: none !important;\n";
        }
        // Typography
        $css .= "    font-family: var(--widget-secondary-font, var(--page-secondary-font), sans-serif);\n";
        // Interactive
        $css .= "    text-decoration: none;\n";
        $css .= "    color: var(--heading-font-color, var(--color-text-primary, #0f172a));\n";
        $css .= "    transition: transform var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4, 0, 0.2, 1)), box-shadow var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4, 0, 0.2, 1));\n";
        $css .= "}\n\n";
        
        // Widgets without thumbnails/icons - center text
        $css .= ".widget-link-simple {\n";
        $css .= "    justify-content: center;\n";
        $css .= "    text-align: center;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-link-simple .widget-content {\n";
        $css .= "    padding: 0 !important;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-link-simple .widget-title {\n";
        $css .= "    margin: 0 !important;\n";
        $css .= "    font-size: var(--type-scale-md, 1.333rem);\n";
        $css .= "    font-weight: var(--type-weight-medium, 500);\n";
        $css .= "}\n\n";
        
        // Thumbnail wrapper for consistent sizing
        $css .= ".widget-thumbnail-wrapper {\n";
        $css .= "    flex-shrink: 0;\n";
        $css .= "    width: clamp(3rem, 16vw, 3.75rem);\n";
        $css .= "    height: clamp(3rem, 16vw, 3.75rem);\n";
        $css .= "    display: flex;\n";
        $css .= "    align-items: center;\n";
        $css .= "    justify-content: center;\n";
        $css .= "    border-radius: var(--shape-corner-md, 0.75rem);\n";
        $css .= "    overflow: hidden;\n";
        $css .= "}\n\n";
        
        // Icon wrapper for consistent sizing
        $css .= ".widget-icon-wrapper {\n";
        $css .= "    flex-shrink: 0;\n";
        $css .= "    width: clamp(3rem, 16vw, 3.75rem);\n";
        $css .= "    height: clamp(3rem, 16vw, 3.75rem);\n";
        $css .= "    display: flex;\n";
        $css .= "    align-items: center;\n";
        $css .= "    justify-content: center;\n";
        $css .= "}\n\n";
        
        // Thumbnail image
        $css .= ".widget-thumbnail {\n";
        $css .= "    width: 100%;\n";
        $css .= "    height: 100%;\n";
        $css .= "    border-radius: var(--widget-border-radius, var(--shape-corner-md, 0.75rem));\n";
        $css .= "    object-fit: cover;\n";
        $css .= "    flex-shrink: 0;\n";
        $css .= "}\n\n";
        
        // Thumbnail fallback (display controlled by JavaScript)
        $css .= ".widget-thumbnail-fallback {\n";
        $css .= "    display: flex;\n";
        $css .= "    align-items: center;\n";
        $css .= "    justify-content: center;\n";
        $css .= "    width: 100%;\n";
        $css .= "    height: 100%;\n";
        $css .= "    background: rgba(0, 0, 0, 0.05);\n";
        $css .= "    border-radius: var(--widget-border-radius, var(--shape-corner-md, 0.75rem));\n";
        $css .= "    color: rgba(0, 0, 0, 0.3);\n";
        $css .= "    font-size: 1.5rem;\n";
        $css .= "}\n\n";
        
        // Icon
        $css .= ".widget-icon {\n";
        $css .= "    font-size: 1.5rem;\n";
        $css .= "    color: inherit;\n";
        $css .= "    display: flex;\n";
        $css .= "    align-items: center;\n";
        $css .= "    justify-content: center;\n";
        $css .= "}\n\n";
        
        // Widget content container
        $css .= ".widget-content {\n";
        $css .= "    flex: 1;\n";
        $css .= "    min-width: 0; /* Allow flex item to shrink below content size */\n";
        $css .= "    font-family: var(--widget-secondary-font, var(--page-secondary-font), sans-serif);\n";
        $css .= "    font-size: var(--type-scale-sm, 1rem);\n";
        $css .= "    line-height: var(--type-line-height-normal, 1.5);\n";
        $css .= "}\n\n";
        
        // Widget title
        $css .= ".widget-title {\n";
        $css .= "    font-weight: var(--type-weight-medium, 500);\n";
        $css .= "    margin: 0 0 var(--widget-space-2xs, 0.25rem) 0;\n";
        $css .= "    font-family: var(--widget-primary-font, var(--page-primary-font), sans-serif);\n";
        // Typography color - use widget-specific colors (can be gradient)
        if ($widgetHeadingColor) {
            if ($isWidgetHeadingGradient) {
                $css .= "    background: var(--widget-heading-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
                $css .= "    color: var(--widget-heading-font-color, var(--color-text-primary, #0f172a));\n";
            }
        } else {
            $css .= "    color: var(--widget-heading-font-color, var(--color-text-primary, #0f172a));\n";
        }
        $css .= "    font-size: var(--type-scale-md, 1.333rem);\n";
        $css .= "}\n\n";
        
        // Widget description
        $css .= ".widget-description {\n";
        $css .= "    font-size: var(--type-scale-sm, 1rem);\n";
        // Typography color - use widget-specific colors (can be gradient)
        if ($widgetBodyColor) {
            if ($isWidgetBodyGradient) {
                $css .= "    background: var(--widget-body-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
                $css .= "    color: var(--widget-body-font-color, var(--color-text-secondary, #64748b));\n";
            }
        } else {
            $css .= "    color: var(--widget-body-font-color, var(--color-text-secondary, #64748b));\n";
        }
        $css .= "    opacity: 0.9;\n";
        $css .= "    margin: var(--widget-space-2xs, 0.25rem) 0 0 0;\n";
        $css .= "    font-family: var(--widget-secondary-font, var(--page-secondary-font), sans-serif);\n";
        $css .= "    min-width: 0; /* Allow text to be constrained in flex container */\n";
        $css .= "}\n\n";
        
        // Marquee animation for Custom Link widget descriptions
        $css .= ".widget-item .widget-description.marquee {\n";
        $css .= "    overflow: hidden;\n";
        $css .= "    white-space: nowrap;\n";
        $css .= "    position: relative;\n";
        $css .= "    width: 100%;\n";
        $css .= "    max-width: 100%;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-item .widget-description .marquee-content {\n";
        $css .= "    display: inline-flex;\n";
        $css .= "    white-space: nowrap;\n";
        $css .= "    animation: widget-marquee-scroll linear infinite;\n";
        $css .= "    animation-duration: var(--marquee-duration, 12s);\n";
        $css .= "    will-change: transform; /* Optimize animation performance */\n";
        $css .= "}\n\n";
        
        $css .= ".widget-item .widget-description .marquee-content .marquee-text {\n";
        $css .= "    display: inline-block;\n";
        $css .= "    white-space: nowrap;\n";
        $css .= "    padding-right: 2em; /* Space between duplicates for better visual separation */\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Video
        $css .= ".widget-video {\n";
        $css .= "    padding: 0;\n";
        $css .= "    border: none;\n";
        $css .= "    background: transparent;\n";
        $css .= "    width: 100%;\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Text/HTML
        $css .= ".widget-text {\n";
        $css .= "    text-align: left;\n";
        $css .= "    width: 100%;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-text-content {\n";
        $css .= "    padding: 1rem;\n";
        $css .= "    color: var(--body-font-color, var(--color-text-primary, #0f172a));\n";
        $css .= "    line-height: 1.6;\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Image
        $css .= ".widget-image {\n";
        $css .= "    padding: 0;\n";
        $css .= "    border: none;\n";
        $css .= "    background: transparent;\n";
        $css .= "    display: block;\n";
        $css .= "    width: 100%;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-image-content {\n";
        $css .= "    width: 100%;\n";
        $css .= "    height: auto;\n";
        $css .= "    border-radius: var(--widget-border-radius, var(--shape-corner-md, 0.75rem));\n";
        $css .= "    display: block;\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Heading
        $css .= ".widget-heading {\n";
        $css .= "    width: 100%;\n";
        $css .= "    padding: var(--widget-space-sm, 0.75rem) var(--widget-space-md, 1rem);\n";
        $css .= "    text-align: center;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-heading-text {\n";
        $css .= "    margin: 0;\n";
        $css .= "    font-family: var(--widget-primary-font, var(--page-primary-font), sans-serif);\n";
        // Use widget-specific colors
        if ($widgetHeadingColor) {
            if ($isWidgetHeadingGradient) {
                $css .= "    background: var(--widget-heading-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
                $css .= "    color: var(--widget-heading-font-color, var(--color-text-primary, #0f172a));\n";
            }
        } else {
            $css .= "    color: var(--widget-heading-font-color, var(--color-text-primary, #0f172a));\n";
        }
        $css .= "}\n\n";
        
        $css .= ".widget-heading-h1 .widget-heading-text {\n";
        $css .= "    font-size: clamp(2rem, 4vw, 2.75rem);\n";
        $css .= "    font-weight: var(--type-weight-bold, 700);\n";
        $css .= "}\n\n";
        
        $css .= ".widget-heading-h2 .widget-heading-text {\n";
        $css .= "    font-size: clamp(1.6rem, 3.25vw, 2.2rem);\n";
        $css .= "    font-weight: var(--type-weight-semibold, 600);\n";
        $css .= "}\n\n";
        
        $css .= ".widget-heading-h3 .widget-heading-text {\n";
        $css .= "    font-size: clamp(1.3rem, 2.75vw, 1.8rem);\n";
        $css .= "    font-weight: var(--type-weight-medium, 500);\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Text Note
        $css .= ".widget-text-note {\n";
        $css .= "    width: 100%;\n";
        $css .= "    padding: var(--widget-space-xs, 0.5rem) var(--widget-space-sm, 0.75rem);\n";
        $css .= "    font-size: 0.9rem;\n";
        $css .= "    font-style: italic;\n";
        // Use widget-specific colors
        if ($widgetBodyColor) {
            if ($isWidgetBodyGradient) {
                $css .= "    background: var(--widget-body-font-gradient);\n";
                $css .= "    -webkit-background-clip: text;\n";
                $css .= "    background-clip: text;\n";
                $css .= "    color: transparent;\n";
            } else {
                $css .= "    color: var(--widget-body-font-color, rgba(15, 23, 42, 0.75));\n";
            }
        } else {
            $css .= "    color: var(--widget-body-font-color, rgba(15, 23, 42, 0.75));\n";
        }
        $css .= "    text-align: center;\n";
        $css .= "}\n\n";
        
        $css .= ".widget-text-note p {\n";
        $css .= "    margin: 0;\n";
        $css .= "}\n\n";
        
        // Widget type-specific: Divider
        $css .= ".widget-divider {\n";
        $css .= "    width: 100%;\n";
        $css .= "    padding: var(--widget-space-xs, 0.5rem) var(--widget-space-md, 1rem);\n";
        $css .= "}\n\n";
        
        $css .= ".widget-divider-line {\n";
        $css .= "    border: none;\n";
        $css .= "    height: 3px;\n";
        $css .= "    width: 100%;\n";
        $css .= "    border-radius: 999px;\n";
        $css .= "    background: rgba(148, 163, 184, 0.45);\n";
        $css .= "}\n\n";
        
        $css .= ".widget-divider-line-shadow {\n";
        $css .= "    background: rgba(71, 85, 105, 0.6);\n";
        $css .= "    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);\n";
        $css .= "}\n\n";
        
        $css .= ".widget-divider-line-gradient {\n";
        $css .= "    background: linear-gradient(90deg, rgba(37, 99, 235, 0.85), rgba(124, 58, 237, 0.85));\n";
        $css .= "}\n\n";
        
        // Widget hover states
        // Get border effect from widget_styles or default to shadow
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        $css .= ".widget-item:hover {\n";
        if ($borderEffect === 'shadow') {
            // Enhanced shadow on hover - get shadow value again for hover
            $hoverShadowValue = null;
            if (!empty($this->shapeTokens['shadow']['level_1'])) {
                $hoverShadowValue = $this->shapeTokens['shadow']['level_1'];
            } elseif (!empty($this->shapeTokens['shadow']['level_2'])) {
                $hoverShadowValue = $this->shapeTokens['shadow']['level_2'];
            }
            if ($hoverShadowValue) {
                // Increase shadow intensity on hover
                $css .= "    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;\n";
            } else {
                $css .= "    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;\n";
            }
        } elseif ($borderEffect === 'glow') {
            // Enhanced glow on hover
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'subtle';
            $glowColor = $this->widgetStyles['glow_color'] ?? '#ff00ff';
            $glowOpacity = convertEnumToCSS($glowIntensity, 'glow_opacity');
            $glowColorRgba = $this->hexToRgba($glowColor, $glowOpacity);
            // Increase glow intensity on hover (larger blur and spread)
            $hoverBlur = $glowIntensity === 'subtle' ? '12px' : '24px';
            $hoverSpread = $glowIntensity === 'subtle' ? '6px' : '12px';
            $css .= "    box-shadow: 0 0 " . h($hoverBlur) . " " . h($hoverSpread) . " " . h($glowColorRgba) . " !important;\n";
        }
        $css .= "    transform: translateY(calc(var(--widget-space-2xs, 0.25rem) * -1));\n";
        $css .= "}\n\n";
        
        // Add spatial effect CSS (before widget-item so widget-item can override if needed)
        $css .= $this->generateSpatialEffectCSS();
        
        // Add glow animation CSS and styles if glow effect is enabled
        $css .= $this->generateGlowAnimationCSS();
        
        // CRITICAL: Re-apply widget-item background AFTER spatial effects to ensure it's not overridden
        // This ensures the theme widget background always takes precedence
        $css .= ".widget-item {\n";
        $css .= "    background: " . h($this->resolvedWidgetBackgroundValue) . " !important;\n";
        $css .= "}\n\n";
        
        // Apply glow effect styles to widget items AFTER background (apply to all widgets if glow is enabled)
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        error_log("GLOW DEBUG: Checking border effect - borderEffect=" . $borderEffect . ", widgetStyles=" . json_encode($this->widgetStyles));
        if ($borderEffect === 'glow') {
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'subtle';
            $glowColor = $this->widgetStyles['glow_color'] ?? '#ff00ff';
            error_log("GLOW DEBUG: Glow enabled - intensity=" . $glowIntensity . ", color=" . $glowColor);
            
            $glowBlur = convertEnumToCSS($glowIntensity, 'glow_blur');
            $glowOpacity = convertEnumToCSS($glowIntensity, 'glow_opacity');
            
            // Convert hex color to RGBA for better glow visibility
            // Parse hex color and convert to rgba with opacity
            $glowColorRgba = $this->hexToRgba($glowColor, $glowOpacity);
            
            // Calculate spread radius based on intensity (makes glow more visible)
            $glowSpread = $glowIntensity === 'subtle' ? '4px' : '8px';
            
            // Apply glow effect using box-shadow with spread radius for better visibility
            // Format: box-shadow: offset-x offset-y blur-radius spread-radius color
            // CRITICAL: Use higher specificity to ensure glow overrides any other box-shadow rules
            $css .= "body .widget-item,\n";
            $css .= ".widget-item {\n";
            $css .= "    box-shadow: 0 0 " . h($glowBlur) . " " . h($glowSpread) . " " . h($glowColorRgba) . " !important;\n";
            $css .= "    animation: glow-pulse 3s ease-in-out infinite !important;\n";
            $css .= "}\n\n";
            
            error_log("GLOW DEBUG: ✅ Applied glow CSS - blur=" . $glowBlur . ", spread=" . $glowSpread . ", color=" . $glowColorRgba);
        } else {
            error_log("GLOW DEBUG: ❌ Glow NOT applied - borderEffect=" . $borderEffect);
        }
        
        // Profile elements
        $css .= ".profile-image {\n";
        $css .= "    border: 3px solid var(--primary-color);\n";
        $css .= "}\n\n";
        
        $css .= ".page-title {\n";
        $css .= "    color: var(--page-title-color);\n";
        $css .= "}\n\n";
        
        $css .= ".page-description {\n";
        $css .= "    color: var(--page-description-color);\n";
        $css .= "}\n\n";
        
        // Social icons - Always apply iconography settings with higher specificity
        // Use body selector to ensure these styles override base styles in Page.php
        $css .= "body .social-icon {\n";
        // Use --icon-color if set, otherwise fall back to --social-icon-color or --color-accent-primary
        if (isset($this->iconographyTokens['color']) && $this->iconographyTokens['color'] !== null && $this->iconographyTokens['color'] !== '') {
            $css .= "    color: var(--icon-color) !important;\n";
        } else {
            $css .= "    color: var(--icon-color, var(--social-icon-color, var(--color-accent-primary)));\n";
        }
        // Always apply size from iconography tokens
        if (isset($this->iconographyTokens['size']) && $this->iconographyTokens['size'] !== null && $this->iconographyTokens['size'] !== '') {
            $css .= "    width: var(--icon-size) !important;\n";
            $css .= "    height: var(--icon-size) !important;\n";
            $css .= "    font-size: calc(var(--icon-size) * 0.625) !important;\n"; // Icon size is typically 62.5% of container
        }
        $css .= "}\n\n";
        
        $css .= "body .social-icon:hover {\n";
        // Use icon color on hover if set, otherwise use accent color
        if (isset($this->iconographyTokens['color']) && $this->iconographyTokens['color'] !== null && $this->iconographyTokens['color'] !== '') {
            $css .= "    color: var(--icon-color) !important;\n";
        } else {
            $css .= "    color: var(--icon-color, var(--accent-color));\n";
        }
        $css .= "    opacity: 0.8;\n";
        $css .= "}\n\n";
        
        // Apply button shape to page-level buttons
        // CRITICAL: These rules must come AFTER podcast-player.css loads
        // Use matching specificity to override podcast-player.css rules
        
        // Generic buttons
        $css .= "button:not(.podcast-top-drawer button):not(.podcast-top-drawer .tab-button):not(.podcast-top-drawer .control-button-large):not(.podcast-top-drawer .secondary-control-btn):not(.podcast-top-drawer .podcast-drawer-footer-button):not(.podcast-top-drawer .retry-button), .btn {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        // Podcast banner toggle (outside drawer) - needs to override podcast-player.css
        $css .= ".podcast-banner-toggle {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        // Podcast drawer buttons - MUST match podcast-player.css selector specificity exactly
        // podcast-player.css uses: .podcast-top-drawer .control-button-large { border-radius: 50%; }
        // We need to override with same specificity + !important
        $css .= ".podcast-top-drawer .tab-button {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        $css .= ".podcast-top-drawer .control-button-large {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        $css .= ".podcast-top-drawer .secondary-control-btn {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        $css .= ".podcast-top-drawer .podcast-drawer-footer-button {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        $css .= ".podcast-top-drawer .retry-button {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        // Other buttons
        $css .= ".drawer-close {\n";
        $css .= "    border-radius: var(--button-corner-radius, 0.75rem) !important;\n";
        $css .= "}\n\n";
        
        $css .= "</style>\n";
        
        return $css;
    }
    
    /**
     * Get spatial effect body class
     * @return string Body class name
     */
    public function getSpatialEffectClass() {
        return 'spatial-' . $this->spatialEffect;
    }
    
    /**
     * Get widget data attributes for border effect
     * @return string Data attributes string
     */
    public function getWidgetEffectAttributes() {
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        $attrs = 'data-border-effect="' . h($borderEffect) . '"';
        
        if ($borderEffect === 'glow') {
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'none';
            $attrs .= ' data-glow-intensity="' . h($glowIntensity) . '"';
        }
        
        return $attrs;
    }

    // REMOVED: All legacy color override methods - no longer needed

    private function normalizeHexColor($color) {
        if (!is_string($color)) {
            return null;
        }

        $color = trim($color);
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return null;
        }

        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return strtoupper($color);
    }

    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        // Handle 3-digit hex
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Convert hex color to RGBA string
     * @param string $hex Hex color (#RGB or #RRGGBB)
     * @param string $opacity Opacity value (0-1 or CSS value like "0.5")
     * @return string RGBA color string
     */
    private function hexToRgba($hex, $opacity) {
        $rgb = $this->hexToRgb($hex);
        if (!$rgb) {
            // Fallback if hex is invalid
            return 'rgba(255, 0, 255, ' . floatval($opacity) . ')';
        }
        
        // Convert opacity to float (handle both "0.5" string and 0.5 float)
        $opacityFloat = is_numeric($opacity) ? floatval($opacity) : 0.5;
        $opacityFloat = max(0, min(1, $opacityFloat)); // Clamp between 0 and 1
        
        return 'rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', ' . $opacityFloat . ')';
    }

    private function rgbToHex($rgb) {
        if (!is_array($rgb) || count($rgb) !== 3) {
            return null;
        }

        return sprintf('#%02X%02X%02X',
            max(0, min(255, (int)round($rgb[0]))),
            max(0, min(255, (int)round($rgb[1]))),
            max(0, min(255, (int)round($rgb[2]))));
    }

    private function mixHexColors($hexA, $hexB, $ratio) {
        $ratio = max(0, min(1, $ratio));
        $rgbA = $this->hexToRgb($hexA);
        $rgbB = $this->hexToRgb($hexB);

        if (!$rgbA || !$rgbB) {
            return null;
        }

        $mixed = [
            ($rgbA[0] * (1 - $ratio)) + ($rgbB[0] * $ratio),
            ($rgbA[1] * (1 - $ratio)) + ($rgbB[1] * $ratio),
            ($rgbA[2] * (1 - $ratio)) + ($rgbB[2] * $ratio)
        ];

        return $this->rgbToHex($mixed);
    }

    private function lightenColor($hex, $amount) {
        $normalized = $this->normalizeHexColor($hex);
        if (!$normalized) {
            return null;
        }

        return $this->mixHexColors($normalized, '#FFFFFF', max(0, min(1, $amount)));
    }

    private function darkenColor($hex, $amount) {
        $normalized = $this->normalizeHexColor($hex);
        if (!$normalized) {
            return null;
        }

        return $this->mixHexColors($normalized, '#000000', max(0, min(1, $amount)));
    }
}

