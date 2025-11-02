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
    }
    
    /**
     * Generate CSS variables block
     * @return string CSS :root block with all variables
     */
    public function generateCSSVariables() {
        $borderWidth = convertEnumToCSS($this->widgetStyles['border_width'] ?? 'medium', 'border_width');
        $spacing = convertEnumToCSS($this->widgetStyles['spacing'] ?? 'comfortable', 'spacing');
        $borderRadius = convertEnumToCSS($this->widgetStyles['shape'] ?? 'rounded', 'shape');
        $borderEffect = $this->widgetStyles['border_effect'] ?? 'shadow';
        
        $css = ":root {\n";
        $css .= "    --primary-color: " . h($this->colors['primary']) . ";\n";
        $css .= "    --secondary-color: " . h($this->colors['secondary']) . ";\n";
        $css .= "    --accent-color: " . h($this->colors['accent']) . ";\n";
        
        // Legacy font variables for backward compatibility
        $css .= "    --heading-font: '" . h($this->pageFonts['page_primary_font']) . "';\n";
        $css .= "    --body-font: '" . h($this->pageFonts['page_secondary_font']) . "';\n";
        
        // New page font variables
        $css .= "    --page-primary-font: '" . h($this->pageFonts['page_primary_font']) . "';\n";
        $css .= "    --page-secondary-font: '" . h($this->pageFonts['page_secondary_font']) . "';\n";
        
        // Widget font variables (default to page fonts if not set)
        $css .= "    --widget-primary-font: '" . h($this->widgetFonts['widget_primary_font']) . "';\n";
        $css .= "    --widget-secondary-font: '" . h($this->widgetFonts['widget_secondary_font']) . "';\n";
        
        $css .= "    --page-background: " . h($this->pageBackground) . ";\n";
        $css .= "    --widget-background: " . h($this->widgetBackground) . ";\n";
        $css .= "    --widget-border-width: {$borderWidth};\n";
        $css .= "    --widget-border-color: " . h($this->widgetBorderColor) . ";\n";
        $css .= "    --widget-spacing: {$spacing};\n";
        $css .= "    --widget-border-radius: {$borderRadius};\n";
        $css .= "    --text-color: var(--primary-color);\n";
        
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
        $css .= "}\n\n";
        
        $css .= ".widget-item p, .widget-item span, .widget-content {\n";
        $css .= "    font-family: var(--widget-secondary-font), var(--widget-primary-font), var(--page-secondary-font), sans-serif;\n";
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
        
        $css .= ".page-description {\n";
        $css .= "    color: var(--primary-color);\n";
        $css .= "}\n\n";
        
        // Social icons
        $css .= ".social-icon {\n";
        $css .= "    color: var(--primary-color);\n";
        $css .= "}\n\n";
        
        $css .= ".social-icon:hover {\n";
        $css .= "    color: var(--accent-color);\n";
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
}

