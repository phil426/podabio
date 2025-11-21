/**
 * Preview Renderer System
 * Extensible preview system for theme editor
 * Generates CSS variables and renders preview components
 */

import { extractTokenValues } from './themeMapper';
import { fieldRegistry } from './fieldRegistry';
import type { ThemeRecord } from '../../../api/types';

export interface PreviewElement {
  id: string;
  component: string;
  cssVars: string[];
  props?: Record<string, unknown>;
}

export interface CSSVariableMap {
  [varName: string]: string;
}

class PreviewRenderer {
  private elements: Map<string, PreviewElement> = new Map();

  /**
   * Register a preview element
   */
  register(element: PreviewElement): void {
    this.elements.set(element.id, element);
  }

  /**
   * Get a preview element by ID
   */
  get(elementId: string): PreviewElement | undefined {
    return this.elements.get(elementId);
  }

  /**
   * Get all registered preview elements
   */
  getAllElements(): PreviewElement[] {
    return Array.from(this.elements.values());
  }

  /**
   * Generate CSS variables from theme data
   * Maps all token values to CSS custom properties
   * uiState contains field IDs (e.g., 'page-title-color'), not token paths
   */
  generateCSSVariables(theme: ThemeRecord | null, uiState?: Record<string, unknown>): CSSVariableMap {
    const cssVars: CSSVariableMap = {};
    const tokenValues = extractTokenValues(theme);

    // Convert UI state (field IDs) to token path values and handle direct columns
    const uiStateTokenValues: Record<string, unknown> = {};
    const directColumns: Record<string, unknown> = {};
    
    if (uiState) {
      for (const [fieldId, value] of Object.entries(uiState)) {
        const field = fieldRegistry.get(fieldId);
        if (field && field.tokenPath) {
          // Check if it's a direct column (not a token path)
          if (field.tokenPath === 'page_background' || 
              field.tokenPath === 'widget_background' || 
              field.tokenPath === 'widget_border_color') {
            directColumns[field.tokenPath] = value;
          } else {
            // Map field ID to token path
            uiStateTokenValues[field.tokenPath] = value;
          }
        }
      }
    }

    // Merge token values from theme with UI state (unsaved changes take priority)
    const allValues = { ...tokenValues, ...uiStateTokenValues };

    // Handle direct columns (from theme or UI state)
    const pageBackground = directColumns['page_background'] ?? 
                           (theme?.page_background) ?? 
                           allValues['typography_tokens.background.page'];
    if (pageBackground) {
      cssVars['--page-background'] = String(pageBackground);
    }

    const widgetBackground = directColumns['widget_background'] ?? 
                             (theme?.widget_background) ?? 
                             allValues['widget_styles.background'];
    if (widgetBackground) {
      cssVars['--widget-background'] = String(widgetBackground);
    }

    const widgetBorderColor = directColumns['widget_border_color'] ?? 
                              (theme?.widget_border_color) ?? 
                              allValues['widget_styles.border_color'];
    if (widgetBorderColor) {
      cssVars['--widget-border-color'] = String(widgetBorderColor);
    }

    // Map typography tokens
    if (allValues['typography_tokens.color.heading']) {
      cssVars['--page-title-color'] = String(allValues['typography_tokens.color.heading']);
    }
    if (allValues['typography_tokens.color.body']) {
      cssVars['--page-description-color'] = String(allValues['typography_tokens.color.body']);
    }
    if (allValues['typography_tokens.color.widget_heading']) {
      cssVars['--widget-heading-color'] = String(allValues['typography_tokens.color.widget_heading']);
    }
    if (allValues['typography_tokens.color.widget_body']) {
      cssVars['--widget-body-color'] = String(allValues['typography_tokens.color.widget_body']);
    }
    if (allValues['typography_tokens.font.heading']) {
      const fontName = String(allValues['typography_tokens.font.heading']);
      cssVars['--page-title-font'] = `'${fontName}', sans-serif`;
    }
    if (allValues['typography_tokens.font.body']) {
      const fontName = String(allValues['typography_tokens.font.body']);
      cssVars['--page-description-font'] = `'${fontName}', sans-serif`;
    }
    if (allValues['typography_tokens.scale.heading']) {
      cssVars['--page-title-size'] = `${allValues['typography_tokens.scale.heading']}px`;
    }
    if (allValues['typography_tokens.scale.body']) {
      cssVars['--page-description-size'] = `${allValues['typography_tokens.scale.body']}px`;
    }
    // Page bio color is already mapped via typography_tokens.color.body above
    if (allValues['typography_tokens.line_height.heading']) {
      cssVars['--page-title-spacing'] = String(allValues['typography_tokens.line_height.heading']);
    }
    if (allValues['typography_tokens.line_height.body']) {
      cssVars['--page-bio-spacing'] = String(allValues['typography_tokens.line_height.body']);
    }
    
    // Map font weights and styles
    if (allValues['typography_tokens.weight.heading']) {
      const weight = allValues['typography_tokens.weight.heading'];
      if (typeof weight === 'object' && weight !== null) {
        const weightObj = weight as { bold?: boolean; italic?: boolean };
        cssVars['--page-title-weight'] = weightObj.bold ? 'bold' : 'normal';
        cssVars['--page-title-style'] = weightObj.italic ? 'italic' : 'normal';
      }
    }
    if (allValues['typography_tokens.weight.body']) {
      const weight = allValues['typography_tokens.weight.body'];
      if (typeof weight === 'object' && weight !== null) {
        const weightObj = weight as { bold?: boolean; italic?: boolean };
        cssVars['--page-bio-weight'] = weightObj.bold ? 'bold' : 'normal';
        cssVars['--page-bio-style'] = weightObj.italic ? 'italic' : 'normal';
      }
    }
    if (allValues['typography_tokens.weight.widget_heading']) {
      const weight = allValues['typography_tokens.weight.widget_heading'];
      if (typeof weight === 'object' && weight !== null) {
        const weightObj = weight as { bold?: boolean; italic?: boolean };
        cssVars['--widget-heading-weight'] = weightObj.bold ? 'bold' : 'normal';
        cssVars['--widget-heading-style'] = weightObj.italic ? 'italic' : 'normal';
      }
    }
    if (allValues['typography_tokens.weight.widget_body']) {
      const weight = allValues['typography_tokens.weight.widget_body'];
      if (typeof weight === 'object' && weight !== null) {
        const weightObj = weight as { bold?: boolean; italic?: boolean };
        cssVars['--widget-body-weight'] = weightObj.bold ? 'bold' : 'normal';
        cssVars['--widget-body-style'] = weightObj.italic ? 'italic' : 'normal';
      }
    }
    
    // Map widget text tokens
    if (allValues['typography_tokens.font.widget_heading']) {
      const fontName = String(allValues['typography_tokens.font.widget_heading']);
      cssVars['--widget-heading-font'] = `'${fontName}', sans-serif`;
    }
    if (allValues['typography_tokens.font.widget_body']) {
      const fontName = String(allValues['typography_tokens.font.widget_body']);
      cssVars['--widget-body-font'] = `'${fontName}', sans-serif`;
    }
    if (allValues['typography_tokens.scale.widget_heading']) {
      cssVars['--widget-heading-size'] = `${allValues['typography_tokens.scale.widget_heading']}px`;
    }
    if (allValues['typography_tokens.scale.widget_body']) {
      cssVars['--widget-body-size'] = `${allValues['typography_tokens.scale.widget_body']}px`;
    }
    if (allValues['typography_tokens.line_height.widget_heading']) {
      cssVars['--widget-heading-spacing'] = String(allValues['typography_tokens.line_height.widget_heading']);
    }
    if (allValues['typography_tokens.line_height.widget_body']) {
      cssVars['--widget-body-spacing'] = String(allValues['typography_tokens.line_height.widget_body']);
    }

    // Map widget styles (only if not already set from direct columns)
    if (!cssVars['--widget-background'] && allValues['widget_styles.background']) {
      cssVars['--widget-background'] = String(allValues['widget_styles.background']);
    }
    if (!cssVars['--widget-border-color'] && allValues['widget_styles.border_color']) {
      cssVars['--widget-border-color'] = String(allValues['widget_styles.border_color']);
    }
    if (allValues['widget_styles.border_width']) {
      cssVars['--widget-border-width'] = String(allValues['widget_styles.border_width']);
    }
    if (allValues['widget_styles.width']) {
      const width = allValues['widget_styles.width'];
      cssVars['--widget-width'] = `${width}%`;
    } else {
      // Default fallback
      cssVars['--widget-width'] = '100%';
    }

    // Profile image styling (from page data via uiState)
    if (uiState) {
      // Profile image size
      const profileImageSize = uiState['profile-image-size'];
      if (profileImageSize !== undefined) {
        cssVars['--profile-image-size'] = typeof profileImageSize === 'number' ? `${profileImageSize}px` : String(profileImageSize);
      }
      
      // Profile image radius (0-50% for border-radius)
      const profileImageRadius = uiState['profile-image-radius'];
      if (profileImageRadius !== undefined) {
        const radiusValue = typeof profileImageRadius === 'number' ? profileImageRadius : Number(profileImageRadius);
        cssVars['--profile-image-radius'] = `${radiusValue}%`;
      }
      
      // Profile image border
      const profileImageBorderWidth = uiState['profile-image-border-width'];
      if (profileImageBorderWidth !== undefined) {
        cssVars['--profile-image-border-width'] = typeof profileImageBorderWidth === 'number' ? `${profileImageBorderWidth}px` : String(profileImageBorderWidth);
      }
      const profileImageBorderColor = uiState['profile-image-border-color'];
      if (profileImageBorderColor !== undefined) {
        cssVars['--profile-image-border-color'] = String(profileImageBorderColor);
      }
      
      // Profile image effects (shadow/glow)
      const profileImageEffect = uiState['profile-image-effect'] ?? 'none';
      let profileImageShadows: string[] = [];
      
      if (profileImageEffect === 'shadow') {
        const shadowColor = uiState['profile-image-shadow-color'] ?? '#000000';
        const shadowIntensity = uiState['profile-image-shadow-intensity'] ?? 0.5;
        const shadowDepth = uiState['profile-image-shadow-depth'] ?? 4;
        const shadowBlur = uiState['profile-image-shadow-blur'] ?? 8;
        
        const rgbaColor = hexToRgba(String(shadowColor), Number(shadowIntensity));
        profileImageShadows.push(`${shadowDepth}px ${shadowDepth}px ${shadowBlur}px ${rgbaColor}`);
      } else if (profileImageEffect === 'glow') {
        const glowColor = uiState['profile-image-glow-color'] ?? '#2563eb';
        const glowWidth = uiState['profile-image-glow-width'] ?? 10;
        
        const glowColorRgba = hexToRgba(String(glowColor), 0.8);
        profileImageShadows.push(`0 0 ${glowWidth}px ${glowColorRgba}`, `0 0 ${glowWidth * 1.5}px ${glowColorRgba}`, `0 0 ${glowWidth * 2}px ${glowColorRgba}`);
      }
      
      // Profile image border is handled via CSS border properties (border-width, border-color, border-style)
      // NOT via box-shadow - this matches page.php implementation
      // Border properties are set separately above
      
      if (profileImageShadows.length > 0) {
        cssVars['--profile-image-box-shadow'] = profileImageShadows.join(', ');
      } else {
        cssVars['--profile-image-box-shadow'] = 'none';
      }
    }

    // Map widget glow effect (if border_effect is 'glow')
    const borderEffect = allValues['widget_styles.border_effect'] ?? 'none';
    if (borderEffect === 'glow') {
      // Get glow values - check both UI state format and CSS generator format
      // UI saves: glow_intensity (number 0-1), glow_color, glow_width
      // CSS generator uses: border_glow_intensity (enum 'subtle'/'pronounced'), glow_color
      const glowIntensity = allValues['widget_styles.border_glow_intensity'] ?? 
                           allValues['widget_styles.glow_intensity'] ?? 
                           'subtle';
      const glowColor = allValues['widget_styles.glow_color'] ?? '#ff00ff';
      const glowWidth = allValues['widget_styles.glow_width'] ?? null;
      
      // Convert intensity to blur and opacity
      // If glowIntensity is a number (0-1), convert to enum-like behavior
      // If it's already an enum ('subtle'/'pronounced'), use it directly
      let glowBlur: string;
      let glowOpacity: number;
      let glowSpread: string;
      
      // Use glow_width directly as blur radius if provided, otherwise calculate from intensity
      if (glowWidth && (typeof glowWidth === 'number' || (typeof glowWidth === 'string' && !isNaN(Number(glowWidth)))) ) {
        const widthNum = typeof glowWidth === 'number' ? glowWidth : Number(glowWidth);
        glowBlur = `${widthNum}px`;
        // Spread is half of blur for good glow effect
        glowSpread = `${widthNum / 2}px`;
        // Map intensity (0-1) to opacity (0.3 to 0.8)
        if (typeof glowIntensity === 'number') {
          glowOpacity = 0.3 + (glowIntensity * 0.5); // 0.3 to 0.8
        } else {
          glowOpacity = glowIntensity === 'pronounced' ? 0.8 : 0.5;
        }
      } else {
        // No glow_width - calculate from intensity
        if (typeof glowIntensity === 'number') {
          // Numeric intensity (0-1) - map to blur and opacity
          if (glowIntensity <= 0.5) {
            glowBlur = '8px';
            glowOpacity = 0.3 + (glowIntensity * 0.4); // 0.3 to 0.5
            glowSpread = '4px';
          } else {
            glowBlur = '16px';
            glowOpacity = 0.5 + ((glowIntensity - 0.5) * 0.6); // 0.5 to 0.8
            glowSpread = '8px';
          }
        } else {
          // Enum format ('subtle' or 'pronounced')
          if (glowIntensity === 'pronounced') {
            glowBlur = '16px';
            glowOpacity = 0.8;
            glowSpread = '8px';
          } else {
            glowBlur = '8px';
            glowOpacity = 0.5;
            glowSpread = '4px';
          }
        }
      }
      
      // Convert hex color to rgba
      const glowColorRgba = hexToRgba(String(glowColor), glowOpacity);
      
      // Generate box-shadow for glow: 0 0 blur spread color
      cssVars['--widget-glow-box-shadow'] = `0 0 ${glowBlur} ${glowSpread} ${glowColorRgba}`;
    } else {
      // No glow - set to none
      cssVars['--widget-glow-box-shadow'] = 'none';
    }

    // Map iconography tokens (use same variable names as CSS generator)
    if (allValues['iconography_tokens.color']) {
      cssVars['--icon-color'] = String(allValues['iconography_tokens.color']);
      cssVars['--social-icon-color'] = String(allValues['iconography_tokens.color']); // Also set for compatibility
    }
    if (allValues['iconography_tokens.size']) {
      const size = allValues['iconography_tokens.size'];
      // If size is a number, add 'px', otherwise use as-is (might already have unit)
      const sizeValue = typeof size === 'number' ? `${size}px` : String(size);
      cssVars['--icon-size'] = sizeValue;
      cssVars['--social-icon-size'] = sizeValue; // Also set for compatibility
    }
    if (allValues['iconography_tokens.spacing']) {
      const spacing = allValues['iconography_tokens.spacing'];
      // If spacing is a number, add 'rem', otherwise use as-is (might already have unit)
      const spacingValue = typeof spacing === 'number' ? `${spacing}rem` : String(spacing);
      cssVars['--icon-spacing'] = spacingValue;
      cssVars['--social-icon-spacing'] = spacingValue; // Also set for compatibility
    }

    // Map spacing tokens
    if (allValues['spacing_tokens.page_spacing']) {
      cssVars['--page-spacing'] = `${allValues['spacing_tokens.page_spacing']}%`;
    }
    // Calculate page vertical spacing (used by profile image spacing)
    // Check uiState first (unsaved changes), then allValues (from theme), then default
    const pageVerticalSpacingFromUI = uiState?.['page-vertical-spacing'];
    const pageVerticalSpacingValue = pageVerticalSpacingFromUI !== undefined 
      ? pageVerticalSpacingFromUI 
      : (allValues['spacing_tokens.vertical_spacing'] ?? 24);
    const verticalSpacingNum = typeof pageVerticalSpacingValue === 'number' 
      ? pageVerticalSpacingValue 
      : (typeof pageVerticalSpacingValue === 'string' 
        ? Number(String(pageVerticalSpacingValue).replace(/px|rem|%|em/gi, '').trim()) || 24
        : 24);
    cssVars['--page-vertical-spacing'] = `${verticalSpacingNum}px`;
    
    // Profile image spacing - fixed: page vertical spacing + 20px top, page vertical spacing bottom
    // Always calculate this (not just when uiState exists) so spacing works even without uiState
    cssVars['--profile-image-spacing-top'] = `${verticalSpacingNum + 20}px`;
    cssVars['--profile-image-spacing-bottom'] = `${verticalSpacingNum}px`;

    // Map widget border radius (rounding)
    if (allValues['shape_tokens.corner.radius']) {
      const radius = allValues['shape_tokens.corner.radius'];
      cssVars['--widget-border-radius'] = `${radius}px`;
    } else {
      // Default fallback
      cssVars['--widget-border-radius'] = '12px';
    }

    // Map page title border properties (outside border using text-shadow)
    // Border should be layered above drop shadow, so we build it first
    const borderColor = allValues['typography_tokens.effect.border.color'] ?? '#000000';
    const borderWidth = allValues['typography_tokens.effect.border.width'] ?? 0;
    const borderShadows: string[] = [];
    
    if (Number(borderWidth) > 0) {
      // Create outside border using multiple text-shadows positioned around the text
      // This creates a border effect that doesn't cut into the text
      const width = Number(borderWidth);
      
      // Generate shadows in a circle around the text for smooth border
      for (let angle = 0; angle < 360; angle += 15) {
        const rad = (angle * Math.PI) / 180;
        const x = Math.cos(rad) * width;
        const y = Math.sin(rad) * width;
        borderShadows.push(`${x}px ${y}px 0 ${borderColor}`);
      }
    }

    // Map page title effect properties
    // allValues already includes uiStateTokenValues, so effect properties should be there
    const effectType = allValues['typography_tokens.effect.heading'] ?? 'none';
    const effectShadows: string[] = [];
    
    if (effectType === 'shadow') {
      // Shadow effect
      const shadowColor = allValues['typography_tokens.effect.shadow.color'] ?? '#000000';
      const shadowIntensity = allValues['typography_tokens.effect.shadow.intensity'] ?? 0.5;
      const shadowDepth = allValues['typography_tokens.effect.shadow.depth'] ?? 4;
      const shadowBlur = allValues['typography_tokens.effect.shadow.blur'] ?? 8;
      
      // Convert color to rgba with intensity
      const rgbaColor = hexToRgba(String(shadowColor), Number(shadowIntensity));
      
      // Generate text-shadow: offset-x offset-y blur-radius color
      effectShadows.push(`${shadowDepth}px ${shadowDepth}px ${shadowBlur}px ${rgbaColor}`);
    } else if (effectType === 'glow') {
      // Glow effect
      const glowColor = allValues['typography_tokens.effect.glow.color'] ?? '#2563eb';
      const glowWidth = allValues['typography_tokens.effect.glow.width'] ?? 10;
      
      // Generate text-shadow for glow (multiple shadows for better glow effect)
      const glowColorRgba = hexToRgba(String(glowColor), 0.8);
      effectShadows.push(`0 0 ${glowWidth}px ${glowColorRgba}`, `0 0 ${glowWidth * 1.5}px ${glowColorRgba}`, `0 0 ${glowWidth * 2}px ${glowColorRgba}`);
    }

    // Combine shadows: border first (renders on top), then effect (renders behind)
    // In CSS text-shadow, first shadows render on top, so border should be first
    const allShadows = [...borderShadows, ...effectShadows];
    if (allShadows.length > 0) {
      cssVars['--page-title-text-shadow'] = allShadows.join(', ');
    } else {
      cssVars['--page-title-text-shadow'] = 'none';
    }

    return cssVars;
  }

  /**
   * Apply CSS variables to a DOM element
   */
  applyCSSVariables(element: HTMLElement, cssVars: CSSVariableMap): void {
    Object.entries(cssVars).forEach(([name, value]) => {
      element.style.setProperty(name, value);
    });
  }

  /**
   * Get CSS variable string for inline styles
   */
  getCSSVariableString(cssVars: CSSVariableMap): string {
    return Object.entries(cssVars)
      .map(([name, value]) => `${name}: ${value};`)
      .join(' ');
  }
}

// Create singleton instance
export const previewRenderer = new PreviewRenderer();

/**
 * Convert hex color to rgba string
 */
function hexToRgba(hex: string, opacity: number): string {
  // Remove # if present
  const cleanHex = hex.replace('#', '');
  
  // Handle 3-digit hex
  const fullHex = cleanHex.length === 3
    ? cleanHex.split('').map(char => char + char).join('')
    : cleanHex;
  
  // Parse RGB values
  const r = parseInt(fullHex.substring(0, 2), 16);
  const g = parseInt(fullHex.substring(2, 4), 16);
  const b = parseInt(fullHex.substring(4, 6), 16);
  
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

// Register default preview elements
previewRenderer.register({
  id: 'page',
  component: 'PagePreview',
  cssVars: [
    '--page-background',
    '--page-title-color',
    '--page-title-font',
    '--page-title-size',
    '--page-description-color',
    '--page-description-font',
    '--page-description-size',
    '--page-spacing'
  ]
});

previewRenderer.register({
  id: 'widget',
  component: 'WidgetPreview',
  cssVars: [
    '--widget-background',
    '--widget-border-color',
    '--widget-border-width',
    '--widget-heading-color',
    '--widget-heading-font',
    '--widget-heading-size',
    '--widget-body-color',
    '--widget-body-font',
    '--widget-body-size'
  ]
});

previewRenderer.register({
  id: 'social-icons',
  component: 'IconPreview',
  cssVars: [
    '--social-icon-color',
    '--social-icon-size',
    '--social-icon-spacing'
  ]
});

