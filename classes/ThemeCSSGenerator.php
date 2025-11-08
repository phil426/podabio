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
    private $colors;
    private $fonts;
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
    private $layoutDensity;
    private $spacingValues;
    private $legacyColorOverridesApplied = false;
    
    public function __construct($page, $theme = null) {
        $this->page = $page;
        $this->theme = $theme;
        $this->themeObj = new Theme();
        
        // Load all theme data
        $this->colors = $this->themeObj->getThemeColors($page, $theme);
        $this->fonts = $this->themeObj->getThemeFonts($page, $theme); // Legacy support
        $this->pageFonts = $this->themeObj->getPageFonts($page, $theme);
        $this->widgetFonts = $this->themeObj->getWidgetFonts($page, $theme);
        $this->pageBackground = $this->themeObj->getPageBackground($page, $theme);
        $this->widgetBackground = $this->themeObj->getWidgetBackground($page, $theme);
        $this->widgetBorderColor = $this->themeObj->getWidgetBorderColor($page, $theme);
        $this->widgetStyles = $this->themeObj->getWidgetStyles($page, $theme);
        $this->spatialEffect = $this->themeObj->getSpatialEffect($page, $theme);
        $this->tokens = $this->themeObj->getThemeTokens($page, $theme);
        $this->colorTokens = $this->tokens['colors'] ?? [];
        $this->typographyTokens = $this->tokens['typography'] ?? [];
        $this->spacingTokens = $this->tokens['spacing'] ?? [];
        $this->shapeTokens = $this->tokens['shape'] ?? [];
        $this->motionTokens = $this->tokens['motion'] ?? [];
        $this->layoutDensity = $this->tokens['layout_density'] ?? 'comfortable';
        $this->spacingValues = $this->spacingTokens['values'] ?? [];

        if ($this->shouldApplyLegacyColorOverrides($page, $theme)) {
            $this->applyLegacyColorOverrides();
        }
    }
    
    /**
     * Calculate relative luminance of a color (for contrast calculation)
     * @param string $color Hex color (#RGB or #RRGGBB)
     * @return float Luminance value between 0 and 1
     */
    private function getLuminance($color) {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Handle 3-digit hex
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
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
        if (preg_match('/linear-gradient\([^,]+,\s*(#[0-9a-fA-F]{6})\s*\d+%,\s*(#[0-9a-fA-F]{6})\s*\d+%\)/', $background, $matches)) {
            // Return the average/middle color for gradient
            // For simplicity, we'll use the lighter color as dominant
            $color1 = $matches[1];
            $color2 = $matches[2];
            
            $lum1 = $this->getLuminance($color1);
            $lum2 = $this->getLuminance($color2);
            
            // Return the lighter color as it's more likely to be the "background"
            return $lum1 > $lum2 ? $color1 : $color2;
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
        $borderWidth = convertEnumToCSS($this->widgetStyles['border_width'] ?? 'none', 'border_width');
        $spacing = convertEnumToCSS($this->widgetStyles['spacing'] ?? 'comfortable', 'spacing');
        $borderRadius = convertEnumToCSS($this->widgetStyles['shape'] ?? 'rounded', 'shape');
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        
        $textPrimary = $this->colorTokens['text']['primary'] ?? $this->colors['primary'];
        $textSecondary = $this->colorTokens['text']['secondary'] ?? $this->colors['primary'];
        $textInverse = $this->colorTokens['text']['inverse'] ?? '#ffffff';
        $accentPrimary = $this->colorTokens['accent']['primary'] ?? $this->colors['accent'];
        $accentMuted = $this->colorTokens['accent']['muted'] ?? '#e0edff';
        $backgroundBase = $this->colorTokens['background']['base'] ?? $this->pageBackground;
        $backgroundSurface = $this->colorTokens['background']['surface'] ?? $this->widgetBackground;
        $backgroundSurfaceRaised = $this->colorTokens['background']['surface_raised'] ?? $backgroundSurface;
        $backgroundOverlay = $this->colorTokens['background']['overlay'] ?? 'rgba(15, 23, 42, 0.6)';
        $borderDefault = $this->colorTokens['border']['default'] ?? $this->widgetBorderColor;
        $borderFocusColor = $this->colorTokens['border']['focus'] ?? $accentPrimary;
        $shadowAmbient = $this->colorTokens['shadow']['ambient'] ?? 'rgba(15, 23, 42, 0.12)';
        $shadowFocus = $this->colorTokens['shadow']['focus'] ?? 'rgba(37, 99, 235, 0.35)';
        
        $stateColors = $this->colorTokens['state'] ?? [];
        $stateTextColors = $this->colorTokens['text_state'] ?? [];
        $gradientTokens = $this->colorTokens['gradient'] ?? [];
        $glowTokens = $this->colorTokens['glow'] ?? [];
        $effectiveWidgetBorderColor = $this->widgetBorderColor ?: $borderDefault;
        
        // Calculate optimal text colors for good contrast
        $pageBackgroundValue = $this->pageBackground ?: $backgroundBase;
        $widgetBackgroundValue = $this->widgetBackground ?: $backgroundSurface;
        
        $pageTitleColor = $this->getOptimalTextColor($pageBackgroundValue, $textPrimary);
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
        $css .= "    --gradient-page: " . h($gradientTokens['page'] ?? $pageBackgroundValue) . ";\n";
        $css .= "    --gradient-accent: " . h($gradientTokens['accent'] ?? $accentPrimary) . ";\n";
        $css .= "    --gradient-widget: " . h($gradientTokens['widget'] ?? $widgetBackgroundValue) . ";\n";
        $css .= "    --gradient-podcast: " . h($gradientTokens['podcast'] ?? ($gradientTokens['accent'] ?? $accentPrimary)) . ";\n";
        $css .= "    --aurora-glow-color: " . h($glowTokens['primary'] ?? $accentPrimary) . ";\n";
        $shellBaseColor = $this->getDominantBackgroundColor($backgroundBase);
        $shellBackground = $this->lightenColor($shellBaseColor, 0.85) ?? $shellBaseColor;
        if (!$shellBackground) {
            $shellBackground = '#f5f7fa';
        }
        $css .= "    --shell-background: " . h($shellBackground) . ";\n";
        
        // Tokenized spacing values
        foreach ($this->spacingValues as $token => $value) {
            $css .= "    --space-" . h($token) . ": " . h($value) . ";\n";
        }
        $css .= "    --layout-density: " . h($this->layoutDensity) . ";\n";
        
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
        
        $css .= "    --font-family-heading: '" . h($headingFont) . "', sans-serif;\n";
        $css .= "    --font-family-body: '" . h($bodyFont) . "', sans-serif;\n";
        $css .= "    --font-family-meta: '" . h($metaFont) . "', sans-serif;\n";
        
        foreach ($this->typographyTokens['scale'] ?? [] as $name => $value) {
            $css .= "    --type-scale-" . h($name) . ": " . h($value) . "rem;\n";
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
        
        // Legacy variables for backward compatibility
        $css .= "    --primary-color: " . h($this->colors['primary']) . ";\n";
        $css .= "    --secondary-color: " . h($this->colors['secondary']) . ";\n";
        $css .= "    --accent-color: " . h($this->colors['accent']) . ";\n";
        
        // Text colors with guaranteed contrast
        $css .= "    --page-title-color: " . h($pageTitleColor) . ";\n";
        $css .= "    --page-description-color: " . h($pageDescriptionColor) . ";\n";
        $css .= "    --social-icon-color: " . h($socialIconColor) . ";\n";
        
        // Legacy font variables for backward compatibility
        $css .= "    --heading-font: '" . h($this->pageFonts['page_primary_font']) . "';\n";
        $css .= "    --body-font: '" . h($this->pageFonts['page_secondary_font']) . "';\n";
        
        // New page font variables
        $css .= "    --page-primary-font: '" . h($this->pageFonts['page_primary_font']) . "';\n";
        $css .= "    --page-secondary-font: '" . h($this->pageFonts['page_secondary_font']) . "';\n";
        
        // Widget font variables (default to page fonts if not set)
        $css .= "    --widget-primary-font: '" . h($this->widgetFonts['widget_primary_font']) . "';\n";
        $css .= "    --widget-secondary-font: '" . h($this->widgetFonts['widget_secondary_font']) . "';\n";
        
        $css .= "    --page-background: " . h($pageBackgroundValue) . ";\n";
        $css .= "    --widget-background: " . h($widgetBackgroundValue) . ";\n";
        $css .= "    --widget-border-width: {$borderWidth};\n";
        $css .= "    --widget-border-color: " . h($effectiveWidgetBorderColor) . ";\n";
        $css .= "    --widget-spacing: {$spacing};\n";
        $css .= "    --widget-border-radius: {$borderRadius};\n";
        $css .= "    --text-color: var(--color-text-primary);\n";
        
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
            $css .= "    background: rgba(255, 255, 255, 0.5);\n";
            $css .= "    backdrop-filter: blur(10px);\n";
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
            $css .= "    background: var(--secondary-color);\n";
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
        
        $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'none';
        if ($glowIntensity === 'none') {
            return "";
        }
        
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
        $css .= "\n";
        
        // Check if background is a gradient
        $isGradient = strpos($this->pageBackground, 'gradient') !== false || strpos($this->pageBackground, 'linear-gradient') !== false || strpos($this->pageBackground, 'radial-gradient') !== false;
        
        // Base body styles
        $css .= "body {\n";
        $css .= "    font-family: var(--page-secondary-font), var(--body-font), sans-serif;\n";
        $css .= "    background: var(--page-background);\n";
        if (!$isGradient) {
            // For solid colors, use fixed attachment for full coverage
            $css .= "    background-attachment: fixed;\n";
        }
        $css .= "    min-height: 100vh;\n";
        $css .= "    color: var(--text-color);\n";
        $css .= "    margin: 0;\n";
        $css .= "    padding: 0;\n";
        $css .= "}\n\n";
        
        // Ensure html element also has background for full coverage
        $css .= "html {\n";
        $css .= "    background: var(--page-background);\n";
        $css .= "    min-height: 100%;\n";
        $css .= "}\n\n";
        
        // Typography - page fonts
        $css .= "h1, h2, h3, .page-title {\n";
        $css .= "    font-family: var(--page-primary-font), var(--heading-font), sans-serif;\n";
        $css .= "}\n\n";
        
        // Widget container
        $css .= ".widgets-container {\n";
        $css .= "    gap: var(--widget-spacing);\n";
        $css .= "}\n\n";
        
        // Widget items - base styling
        $css .= ".widget-item {\n";
        $css .= "    background: var(--widget-background);\n";
        $css .= "    border: var(--widget-border-width) solid var(--widget-border-color);\n";
        $css .= "    border-radius: var(--widget-border-radius);\n";
        $css .= "    position: relative;\n";
        
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        if ($borderEffect === 'shadow') {
            $css .= "    box-shadow: var(--widget-box-shadow);\n";
        } elseif ($borderEffect === 'glow') {
            $css .= "    box-shadow: none;\n";
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'none';
            if ($glowIntensity !== 'none') {
                $css .= "    animation: glow-pulse 3s ease-in-out infinite;\n";
            }
        }
        
        $css .= "}\n\n";
        
        // Add glow ::before pseudo-element if glow is enabled
        if ($borderEffect === 'glow') {
            $glowIntensity = $this->widgetStyles['border_glow_intensity'] ?? 'none';
            if ($glowIntensity !== 'none') {
                $css .= ".widget-item::before {\n";
                $css .= "    content: '';\n";
                $css .= "    position: absolute;\n";
                $css .= "    inset: -2px;\n";
                $css .= "    border-radius: inherit;\n";
                $css .= "    padding: 2px;\n";
                $css .= "    background: var(--widget-glow-color);\n";
                $css .= "    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);\n";
                $css .= "    -webkit-mask-composite: xor;\n";
                $css .= "    mask-composite: exclude;\n";
                $css .= "    filter: blur(var(--widget-glow-blur));\n";
                $css .= "    opacity: var(--widget-glow-opacity);\n";
                $css .= "    animation: glow-rotate 4s linear infinite;\n";
                $css .= "    z-index: -1;\n";
                $css .= "}\n\n";
            }
        }
        
        // Widget typography
        $css .= ".widget-item h1, .widget-item h2, .widget-item h3, .widget-title {\n";
        $css .= "    font-family: var(--widget-primary-font), var(--widget-secondary-font), var(--page-primary-font), sans-serif;\n";
        $css .= "    font-size: 1.125rem; /* 18px - increased one step */\n";
        $css .= "    font-weight: 400; /* Normal weight, not bold */\n";
        $css .= "}\n\n";
        
        $css .= ".widget-item p, .widget-item span, .widget-content {\n";
        $css .= "    font-family: var(--widget-secondary-font), var(--widget-primary-font), var(--page-secondary-font), sans-serif;\n";
        $css .= "    font-size: 1rem; /* 16px - increased one step */\n";
        $css .= "}\n\n";
        
        // Widget hover states
        $css .= ".widget-item:hover {\n";
        if ($borderEffect === 'shadow') {
            $css .= "    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);\n";
        }
        $css .= "    transform: translateY(-2px);\n";
        $css .= "}\n\n";
        
        // Add glow animation CSS if glow effect is enabled
        $css .= $this->generateGlowAnimationCSS();
        
        // Add spatial effect CSS
        $css .= $this->generateSpatialEffectCSS();
        
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
        
        // Social icons
        $css .= ".social-icon {\n";
        $css .= "    color: var(--social-icon-color);\n";
        $css .= "}\n\n";
        
        $css .= ".social-icon:hover {\n";
        $css .= "    color: var(--accent-color);\n";
        $css .= "    opacity: 0.8;\n";
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

    private function shouldApplyLegacyColorOverrides($page, $theme) {
        $pageHasTokens = !empty($page['color_tokens']);
        if ($pageHasTokens) {
            return false;
        }

        if (!empty($page['colors'])) {
            return true;
        }

        $themeHasTokens = !empty($theme['color_tokens'] ?? null);
        if ($themeHasTokens) {
            return false;
        }

        if (!empty($theme['colors'] ?? null)) {
            return true;
        }

        return false;
    }

    private function applyLegacyColorOverrides() {
        $primary = $this->normalizeHexColor($this->colors['primary'] ?? null);
        $secondary = $this->normalizeHexColor($this->colors['secondary'] ?? null);
        $accent = $this->normalizeHexColor($this->colors['accent'] ?? null);

        if ($primary) {
            $this->colorTokens['text']['primary'] = $primary;
            $this->colorTokens['text']['secondary'] = $this->colorTokens['text']['secondary'] ?? ($this->lightenColor($primary, 0.35) ?? $primary);
            $this->colorTokens['border']['default'] = $this->darkenColor($primary, 0.2) ?? $primary;
            if (empty($this->colorTokens['border']['focus'])) {
                $this->colorTokens['border']['focus'] = $this->lightenColor($primary, 0.25) ?? $primary;
            }
        }

        if ($secondary) {
            $base = $this->lightenColor($secondary, 0.12) ?? $secondary;
            $surfaceRaised = $this->lightenColor($secondary, 0.22) ?? $secondary;
            $this->colorTokens['background']['surface'] = $secondary;
            $this->colorTokens['background']['surface_raised'] = $surfaceRaised;
            $this->colorTokens['background']['base'] = $base;
        }

        if ($accent) {
            $this->colorTokens['accent']['primary'] = $accent;
            $this->colorTokens['accent']['muted'] = $this->colorTokens['accent']['muted'] ?? ($this->lightenColor($accent, 0.75) ?? $accent);
            if (empty($this->colorTokens['gradient']['accent'])) {
                $this->colorTokens['gradient']['accent'] = null;
            }
        }

        $this->legacyColorOverridesApplied = true;
    }

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
        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
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

