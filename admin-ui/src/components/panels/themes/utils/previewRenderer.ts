/**
 * Preview Renderer System
 * Extensible preview system for theme editor
 * Generates CSS variables and renders preview components
 */

import { extractTokenValues } from './themeMapper';
import { fieldRegistry } from './fieldRegistry';
import type { ThemeRecord } from '../../../../api/types';
import { normalizeImageUrl } from '../../../../api/utils';

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
  generateCSSVariables(theme: ThemeRecord | null, uiState?: Record<string, unknown>, page?: Record<string, unknown>): CSSVariableMap {
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

    // Handle page background animation
    const pageBackgroundAnimate = uiState?.['page-background-animate'] ??
      (page?.page_background_animate ? true : false);
    const isGradient = pageBackground && typeof pageBackground === 'string' &&
      (pageBackground.includes('gradient') || pageBackground.includes('linear-gradient') || pageBackground.includes('radial-gradient'));
    if (pageBackgroundAnimate && isGradient) {
      cssVars['--page-background-animate'] = 'true';
    }

    // Handle Page Background Image - CRITICAL for preview updates
    // Check uiState first, then page data
    const rawBgImageUrl = (uiState?.['page_background_image_url'] as string) ?? (page?.['page_background_image_url'] as string);
    const bgImageUrl = normalizeImageUrl(rawBgImageUrl);

    if (bgImageUrl) {
      cssVars['--page-background-image-url'] = `url('${bgImageUrl}')`;
      cssVars['--page-background-image-active'] = '1';
      // Force screen background to transparent so image shows through
      // page-screen-background is for page-preview.php, page-background is for page.php
      cssVars['--page-screen-background'] = 'transparent';
      cssVars['--page-background'] = 'transparent';

      // Handle Image Overlay
      const bgOverlay = (uiState?.['page_background_image_overlay'] as string) ?? (page?.['page_background_image_overlay'] as string);
      if (bgOverlay) {
        cssVars['--page-background-image-overlay'] = bgOverlay;
      }

      // Handle Focal Point
      // Values are stored as "50%" strings in DB/State
      const rawFocalX = (uiState?.['page_background_image_focal_x'] as string | number) ?? (page?.['page_background_image_focal_x'] as string | number) ?? '50%';
      const rawFocalY = (uiState?.['page_background_image_focal_y'] as string | number) ?? (page?.['page_background_image_focal_y'] as string | number) ?? '50%';

      const focalX = parseFloat(String(rawFocalX).replace('%', ''));
      const focalY = parseFloat(String(rawFocalY).replace('%', ''));

      cssVars['--page-background-image-focal-x'] = `${isNaN(focalX) ? 50 : focalX}%`;
      cssVars['--page-background-image-focal-y'] = `${isNaN(focalY) ? 50 : focalY}%`;

      // Handle Scale
      const scale = (uiState?.['page_background_image_scale'] as number) ?? (page?.['page_background_image_scale'] as number) ?? 1;
      // Scale should be a unitless factor (e.g. 1, 1.5), NOT a percentage
      cssVars['--page-background-image-scale'] = `${scale}`;

      // Handle Blur
      const rawBlur = (uiState?.['page_background_image_blur'] as string | number) ?? (page?.['page_background_image_blur'] as string | number) ?? 0;
      const blur = parseFloat(String(rawBlur).replace('px', ''));
      const finalBlur = isNaN(blur) ? 0 : blur;
      cssVars['--page-background-image-focal-blur'] = `${finalBlur}px`;
    } else {
      // Ensure image is inactive if not present
      cssVars['--page-background-image-active'] = '0';
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
    // Page title color - check uiState first, then allValues
    const pageTitleColor = uiState?.['page-title-color'] ?? allValues['typography_tokens.color.heading'];
    if (pageTitleColor !== undefined) {
      cssVars['--page-title-color'] = String(pageTitleColor);
    }
    if (allValues['typography_tokens.color.body']) {
      cssVars['--page-description-color'] = String(allValues['typography_tokens.color.body']);
    }
    if (allValues['typography_tokens.color.widget_heading']) {
      const headingColor = String(allValues['typography_tokens.color.widget_heading']);
      cssVars['--widget-heading-color'] = headingColor;
      cssVars['--widget-heading-font-color'] = headingColor; // Match ThemeCSSGenerator
    }
    if (allValues['typography_tokens.color.widget_body']) {
      const bodyColor = String(allValues['typography_tokens.color.widget_body']);
      cssVars['--widget-body-color'] = bodyColor;
      cssVars['--widget-body-font-color'] = bodyColor; // Match ThemeCSSGenerator
    }
    // Page title font - check uiState first, then allValues
    const pageTitleFont = uiState?.['page-title-font'] ?? allValues['typography_tokens.font.heading'];
    if (pageTitleFont !== undefined) {
      const fontName = String(pageTitleFont);
      cssVars['--page-title-font'] = `'${fontName}', sans-serif`;
    }
    if (allValues['typography_tokens.font.body']) {
      const fontName = String(allValues['typography_tokens.font.body']);
      cssVars['--page-description-font'] = `'${fontName}', sans-serif`;
    }
    // Page title size - check uiState first, then allValues
    const pageTitleSize = uiState?.['page-title-size'] ?? allValues['typography_tokens.scale.heading'];
    if (pageTitleSize !== undefined) {
      const sizeValue = typeof pageTitleSize === 'number' ? pageTitleSize : parseFloat(String(pageTitleSize));
      if (!isNaN(sizeValue)) {
        cssVars['--page-title-size'] = `${sizeValue}px`;
      }
    }
    // Page description/bio size - check uiState first, then allValues
    const pageBioSize = uiState?.['page-bio-size'] ?? allValues['typography_tokens.scale.body'];
    if (pageBioSize !== undefined) {
      const sizeValue = typeof pageBioSize === 'number' ? pageBioSize : parseFloat(String(pageBioSize));
      if (!isNaN(sizeValue)) {
        cssVars['--page-description-size'] = `${sizeValue}px`;
      }
    }
    // Page bio color is already mapped via typography_tokens.color.body above
    // Page title spacing - check uiState first, then allValues
    const pageTitleSpacing = uiState?.['page-title-spacing'] ?? allValues['typography_tokens.line_height.heading'];
    if (pageTitleSpacing !== undefined) {
      cssVars['--page-title-spacing'] = String(pageTitleSpacing);
    }

    // Map font weights and styles
    // Page title weight - check uiState first, then allValues
    const pageTitleWeight = uiState?.['page-title-weight'] ?? allValues['typography_tokens.weight.heading'];
    if (pageTitleWeight !== undefined) {
      if (typeof pageTitleWeight === 'object' && pageTitleWeight !== null) {
        const weightObj = pageTitleWeight as { bold?: boolean; italic?: boolean };
        cssVars['--page-title-weight'] = weightObj.bold ? 'bold' : 'normal';
        cssVars['--page-title-style'] = weightObj.italic ? 'italic' : 'normal';
      } else if (typeof pageTitleWeight === 'string') {
        // Handle string format like "bold" or "italic" or "bold italic"
        const isBold = pageTitleWeight.includes('bold') || pageTitleWeight === 'bold';
        const isItalic = pageTitleWeight.includes('italic') || pageTitleWeight === 'italic';
        cssVars['--page-title-weight'] = isBold ? 'bold' : 'normal';
        cssVars['--page-title-style'] = isItalic ? 'italic' : 'normal';
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

    // Map widget text tokens - use variable names that match ThemeCSSGenerator
    if (allValues['typography_tokens.font.widget_heading']) {
      const fontName = String(allValues['typography_tokens.font.widget_heading']);
      cssVars['--widget-heading-font'] = `'${fontName}', sans-serif`;
      cssVars['--widget-primary-font'] = `'${fontName}', sans-serif`; // Match ThemeCSSGenerator
    }
    if (allValues['typography_tokens.font.widget_body']) {
      const fontName = String(allValues['typography_tokens.font.widget_body']);
      cssVars['--widget-body-font'] = `'${fontName}', sans-serif`;
      cssVars['--widget-secondary-font'] = `'${fontName}', sans-serif`; // Match ThemeCSSGenerator
    }
    if (allValues['typography_tokens.scale.widget_heading']) {
      const size = Number(allValues['typography_tokens.scale.widget_heading']);
      cssVars['--widget-heading-size'] = `${size}px`;
      // Convert px to rem (assuming 16px base)
      cssVars['--type-scale-md'] = `${size / 16}rem`; // Match ThemeCSSGenerator scale
    }
    if (allValues['typography_tokens.scale.widget_body']) {
      const size = Number(allValues['typography_tokens.scale.widget_body']);
      cssVars['--widget-body-size'] = `${size}px`;
      // Convert px to rem (assuming 16px base)
      cssVars['--type-scale-sm'] = `${size / 16}rem`; // Match ThemeCSSGenerator scale
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
    if (allValues['widget_styles.border_width'] !== undefined) {
      const borderWidth = allValues['widget_styles.border_width'];
      // Ensure it has 'px' unit if it's a number
      if (typeof borderWidth === 'number') {
        cssVars['--widget-border-width'] = `${borderWidth}px`;
      } else if (typeof borderWidth === 'string') {
        // If it's already a string with units, use as-is
        // If it's "0" or "none", convert to "0px"
        if (borderWidth === '0' || borderWidth === 'none') {
          cssVars['--widget-border-width'] = '0px';
        } else {
          cssVars['--widget-border-width'] = borderWidth;
        }
      } else {
        cssVars['--widget-border-width'] = String(borderWidth);
      }
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
        const glowWidth = typeof uiState['profile-image-glow-width'] === 'number'
          ? uiState['profile-image-glow-width']
          : (typeof uiState['profile-image-glow-width'] === 'string' ? Number(uiState['profile-image-glow-width']) : 10);

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

    // Map widget shadow effect (if border_effect is 'shadow')
    // Check both allValues (from theme + converted uiState) and uiState directly as fallback
    const borderEffect = allValues['widget_styles.border_effect'] ??
      (uiState?.['widget-border-effect'] as string) ??
      'none';

    // Only set shadow CSS variable if borderEffect is 'shadow'
    // Don't set it to 'none' for other effects - let CSS fallback work
    if (borderEffect === 'shadow') {
      // Get shadow values from widget_styles, with fallback to uiState directly
      const shadowDepth = allValues['widget_styles.shadow_depth'] ??
        (uiState?.['widget-shadow-depth'] as number) ??
        1;
      const shadowColor = allValues['widget_styles.shadow_color'] ??
        (uiState?.['widget-shadow-color'] as string) ??
        'rgba(15, 23, 42, 0.12)';
      const shadowIntensity = allValues['widget_styles.shadow_intensity'] ??
        (uiState?.['widget-shadow-intensity'] as number) ??
        1;

      // Only apply shadow if depth > 0
      if (typeof shadowDepth === 'number' && shadowDepth > 0) {
        // Calculate shadow blur based on depth (0-10)
        // Match profile image shadow style: depth controls both offset and blur
        // Profile image uses: depth depth blur color (e.g., 4px 4px 8px rgba(...))
        // For widgets, scale blur relative to depth: depth 1 = 2px blur, depth 10 = 20px blur
        const shadowBlur = shadowDepth * 2; // 0-20px (similar to profile image's 8px default for depth 4)

        // Convert shadow color to rgba if needed (same as profile image)
        let shadowColorRgba = String(shadowColor);
        if (typeof shadowColor === 'string' && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/i.test(shadowColor)) {
          // Hex color - convert to rgba with intensity as opacity
          shadowColorRgba = hexToRgba(shadowColor, Number(shadowIntensity) || 0.5);
        } else if (typeof shadowColor === 'string' && shadowColor.startsWith('rgba')) {
          // Already rgba - adjust opacity by intensity
          const rgbaMatch = shadowColor.match(/rgba\(([^)]+)\)/);
          if (rgbaMatch) {
            const parts = rgbaMatch[1].split(',').map(p => p.trim());
            if (parts.length === 4) {
              const opacity = parseFloat(parts[3]) * (Number(shadowIntensity) || 0.5);
              shadowColorRgba = `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, ${opacity})`;
            }
          }
        } else if (typeof shadowColor === 'string' && shadowColor.startsWith('rgb')) {
          // RGB color - convert to rgba with intensity as opacity
          const rgbMatch = shadowColor.match(/rgb\(([^)]+)\)/);
          if (rgbMatch) {
            const parts = rgbMatch[1].split(',').map(p => p.trim());
            if (parts.length === 3) {
              shadowColorRgba = `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, ${shadowIntensity})`;
            }
          }
        }

        // Generate drop shadow like profile image: offset-x offset-y blur-radius color
        // Both x and y offsets use depth for diagonal drop shadow effect
        cssVars['--widget-shadow-box-shadow'] = `${shadowDepth}px ${shadowDepth}px ${shadowBlur}px ${shadowColorRgba}`;
      } else {
        // Shadow depth is 0 - set to none
        cssVars['--widget-shadow-box-shadow'] = 'none';
      }
    }
    // Don't set shadow CSS variable for glow or none - let CSS fallback handle it

    // Map widget glow effect (if border_effect is 'glow')
    // Also check if glow values exist in uiState even if borderEffect isn't set correctly
    const hasGlowValues = borderEffect === 'glow' ||
      (uiState?.['widget-glow-color'] !== undefined ||
        uiState?.['widget-glow-width'] !== undefined ||
        uiState?.['widget-glow-intensity'] !== undefined);

    if (borderEffect === 'glow' || (hasGlowValues && borderEffect !== 'shadow')) {
      // Get glow values - check both UI state format and CSS generator format
      // UI saves: glow_intensity (number 0-1), glow_color, glow_width
      // CSS generator uses: border_glow_intensity (enum 'subtle'/'pronounced'), glow_color
      // Also check uiState directly as fallback
      const glowIntensity = allValues['widget_styles.border_glow_intensity'] ??
        allValues['widget_styles.glow_intensity'] ??
        (uiState?.['widget-glow-intensity'] as number) ??
        'subtle';
      const glowColor = allValues['widget_styles.glow_color'] ??
        (uiState?.['widget-glow-color'] as string) ??
        '#2563eb';
      const glowWidth = allValues['widget_styles.glow_width'] ??
        (uiState?.['widget-glow-width'] as number) ??
        null;

      // Convert intensity to blur and opacity
      // If glowIntensity is a number (0-1), convert to enum-like behavior
      // If it's already an enum ('subtle'/'pronounced'), use it directly
      let glowBlur: string;
      let glowOpacity: number;
      let glowSpread: string;

      // Use glow_width directly as blur radius if provided, otherwise calculate from intensity
      if (glowWidth && (typeof glowWidth === 'number' || (typeof glowWidth === 'string' && !isNaN(Number(glowWidth))))) {
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
    }
    // Don't set glow CSS variable for shadow or none - let CSS fallback handle it

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

    // Calculate unified page spacing (used for all element spacing)
    // Check uiState first (unsaved changes), then allValues (from theme), then default
    const pageSpacingFromUI = uiState?.['page-spacing'];
    const pageSpacingValue = pageSpacingFromUI !== undefined
      ? pageSpacingFromUI
      : (allValues['spacing_tokens.page_spacing'] ?? 16);
    const spacingNum = typeof pageSpacingValue === 'number'
      ? pageSpacingValue
      : (typeof pageSpacingValue === 'string'
        ? Number(String(pageSpacingValue).replace(/px|rem|%|em/gi, '').trim()) || 16
        : 16);

    // Use unified spacing for all elements
    cssVars['--page-spacing'] = `${spacingNum}px`;
    cssVars['--page-element-spacing'] = `${spacingNum}px`;

    // Profile image spacing - use unified spacing with small offset for top (accounting for podcast bar)
    // Always calculate this (not just when uiState exists) so spacing works even without uiState
    cssVars['--profile-image-spacing-top'] = `${spacingNum + 12}px`;
    cssVars['--profile-image-spacing-bottom'] = `${spacingNum}px`;

    // Map widget border radius (rounding)
    // Check uiState first (unsaved changes), then allValues
    const widgetRounding = uiState?.['widget-rounding'] ?? allValues['shape_tokens.corner.radius'];
    if (widgetRounding !== undefined) {
      const radiusValue = typeof widgetRounding === 'number' ? widgetRounding : parseFloat(String(widgetRounding));
      if (!isNaN(radiusValue)) {
        cssVars['--widget-border-radius'] = `${radiusValue}px`;
      }
    } else {
      // Default fallback
      cssVars['--widget-border-radius'] = '12px';
    }

    // Map podcast player bar styles (from uiState)
    if (uiState) {
      // Background
      const playerBackground = uiState['podcast-player-background'];
      if (playerBackground !== undefined) {
        cssVars['--podcast-player-background'] = String(playerBackground);
      }

      // Border
      const playerBorderColor = uiState['podcast-player-border-color'];
      if (playerBorderColor !== undefined) {
        cssVars['--podcast-player-border-color'] = String(playerBorderColor);
      }
      const playerBorderWidth = uiState['podcast-player-border-width'];
      if (playerBorderWidth !== undefined) {
        cssVars['--podcast-player-border-width'] = typeof playerBorderWidth === 'number' ? `${playerBorderWidth}px` : String(playerBorderWidth);
      }

      // Shadow
      const playerShadowEnabled = uiState['podcast-player-shadow-enabled'] ?? true;
      if (playerShadowEnabled) {
        const playerShadowDepth = uiState['podcast-player-shadow-depth'] ?? 16;
        const shadowDepthNum = typeof playerShadowDepth === 'number' ? playerShadowDepth : Number(playerShadowDepth);
        cssVars['--podcast-player-box-shadow'] = `0 ${shadowDepthNum / 4}px ${shadowDepthNum}px rgba(15, 23, 42, 0.16)`;
      } else {
        cssVars['--podcast-player-box-shadow'] = 'none';
      }

      // Text color
      const playerTextColor = uiState['podcast-player-text-color'];
      if (playerTextColor !== undefined) {
        cssVars['--podcast-player-text-color'] = String(playerTextColor);
      }
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
    // Read from uiState directly (page-level field, not theme token)
    // Also check if page object is passed (for preview)
    // CRITICAL: If uiState has 'page-title-effect' set to 'none', use that (even if page data has an effect)
    // This allows users to turn off effects in real-time preview
    const effectType = uiState?.['page-title-effect'] !== undefined
      ? (uiState['page-title-effect'] as string) ?? 'none'
      : ((typeof page !== 'undefined' && page && 'page_name_effect' in page ? (page as Record<string, unknown>).page_name_effect : null) ?? 'none');
    const effectShadows: string[] = [];

    // Get background color for effects that need it (retro, pretty, flat, long)
    // Use the page background from CSS vars if available, otherwise default
    const pageBgValue = cssVars['--page-background'] ?? allValues['page_background'] ?? '#f1f1f1';
    const bgColor = typeof pageBgValue === 'string' && pageBgValue.includes('gradient')
      ? '#f1f1f1' // Default for gradients
      : (typeof pageBgValue === 'string' ? pageBgValue : '#f1f1f1');

    // Only generate effect shadows if effect is not 'none'
    if (effectType === 'shadow') {
      // Drop Shadow effect
      const shadowColor = allValues['typography_tokens.effect.shadow.color'] ?? '#000000';
      const shadowIntensity = allValues['typography_tokens.effect.shadow.intensity'] ?? 0.5;
      const shadowDepth = allValues['typography_tokens.effect.shadow.depth'] ?? 4;
      const shadowBlur = allValues['typography_tokens.effect.shadow.blur'] ?? 8;

      // Convert color to rgba with intensity
      const rgbaColor = hexToRgba(String(shadowColor), Number(shadowIntensity));

      // Generate text-shadow: offset-x offset-y blur-radius color
      effectShadows.push(`${shadowDepth}px ${shadowDepth}px ${shadowBlur}px ${rgbaColor}`);
    } else if (effectType === 'glow') {
      // Neon Glow effect - multiple glowing shadows
      const glowColor = allValues['typography_tokens.effect.glow.color'] ?? '#3EB0B4';
      const glowWidth = typeof allValues['typography_tokens.effect.glow.width'] === 'number'
        ? allValues['typography_tokens.effect.glow.width'] as number
        : (typeof allValues['typography_tokens.effect.glow.width'] === 'string'
          ? Number(allValues['typography_tokens.effect.glow.width'])
          : 10);

      // Generate text-shadow for glow (multiple shadows for better glow effect)
      const glowColorRgba = hexToRgba(String(glowColor), 0.8);
      effectShadows.push(
        `0 0 ${glowWidth}px ${glowColorRgba}`,
        `0 0 ${glowWidth * 1.5}px ${glowColorRgba}`,
        `0 0 ${glowWidth * 2}px ${glowColorRgba}`,
        `0 0 ${glowWidth * 3}px ${glowColorRgba}`,
        `0 0 ${glowWidth * 4}px ${glowColorRgba}`
      );
    } else if (effectType === 'retro') {
      // Retro shadow - two shadows, first matches background, second is offset grey
      effectShadows.push(`2px 2px 0px ${bgColor}`, `3px 3px 0px #707070`);
    } else if (effectType === 'anaglyphic') {
      // Anaglyphic - colored text with offset colored shadow
      // Override text color to purple with transparency
      cssVars['--page-title-color'] = 'rgba(97, 70, 127, 0.7)';
      const shadowRgba = 'rgba(62, 176, 180, 0.7)';
      effectShadows.push(`5px 5px 0 ${shadowRgba}`);
    } else if (effectType === 'elegant') {
      // Elegant - many layers gradually offsetting to the left and getting lighter
      const shadows: string[] = [];
      for (let i = 1; i <= 28; i++) {
        const lightness = Math.min(100, 30 + (i * 2.5)); // Gradually lighter
        const grayValue = Math.round(255 - (lightness * 2.55));
        const hex = `#${grayValue.toString(16).padStart(2, '0')}${grayValue.toString(16).padStart(2, '0')}${grayValue.toString(16).padStart(2, '0')}`;
        shadows.push(`-${i}px ${i * 2}px 1px ${hex}`);
      }
      effectShadows.push(...shadows);
    } else if (effectType === 'deep') {
      // Deep - layers of gradually darker shades offsetting downward
      // Text color should be background color (white)
      cssVars['--page-title-color'] = bgColor;
      const shadows: string[] = [];
      // Top highlight
      shadows.push('0 -1px 0 #fff');
      // Multiple dark layers going down
      for (let i = 1; i <= 15; i++) {
        const darkness = Math.max(18, 46 - (i * 2));
        const hex = `#${darkness.toString(16).padStart(2, '0')}${darkness.toString(16).padStart(2, '0')}${darkness.toString(16).padStart(2, '0')}`;
        shadows.push(`0 ${i}px 0 ${hex}`);
      }
      // Final blur shadow
      shadows.push('0 22px 30px rgba(0, 0, 0, 0.9)');
      effectShadows.push(...shadows);
    } else if (effectType === 'game') {
      // Game - alternating colored shadows
      // Text color should be white
      cssVars['--page-title-color'] = '#ffffff';
      effectShadows.push(
        '5px 5px 0 #ffd217',
        '9px 9px 0 #5ac7ff',
        '14px 14px 0 #ffd217',
        '18px 18px 0 #5ac7ff'
      );
    } else if (effectType === 'comic') {
      // Comic - many black shadows wrapping white letters
      // Text color should be white
      cssVars['--page-title-color'] = '#ffffff';
      effectShadows.push(
        '0px -6px 0 #212121',
        '0px -6px 0 #212121',
        '0px 6px 0 #212121',
        '0px 6px 0 #212121',
        '-6px 0px 0 #212121',
        '6px 0px 0 #212121',
        '-6px 0px 0 #212121',
        '6px 0px 0 #212121',
        '-6px -6px 0 #212121',
        '6px -6px 0 #212121',
        '-6px 6px 0 #212121',
        '6px 6px 0 #212121',
        '-6px 18px 0 #212121',
        '0px 18px 0 #212121',
        '6px 18px 0 #212121',
        '0 19px 1px rgba(0,0,0,.1)',
        '0 0 6px rgba(0,0,0,.1)',
        '0 6px 3px rgba(0,0,0,.3)',
        '0 12px 6px rgba(0,0,0,.2)',
        '0 18px 18px rgba(0,0,0,.25)',
        '0 24px 24px rgba(0,0,0,.2)',
        '0 36px 36px rgba(0,0,0,.15)'
      );
    } else if (effectType === 'fancy') {
      // Fancy - elegant blurred grey shadow
      // Text color should be white
      cssVars['--page-title-color'] = '#ffffff';
      effectShadows.push('-15px 5px 20px #ced0d3');
    } else if (effectType === 'pretty') {
      // Pretty - alternating background color and blue shadows
      const blueColor = '#1c4b82';
      effectShadows.push(
        `-1px -1px 0px ${bgColor}`,
        `3px 3px 0px ${bgColor}`,
        `6px 6px 0px ${blueColor}`
      );
    } else if (effectType === 'flat') {
      // Flat - elegant blurred shadow with white highlight
      // Text color should be background color
      cssVars['--page-title-color'] = bgColor;
      effectShadows.push('0 13.36px 8.896px #c4b59d', '0 -2px 1px #fff');
    } else if (effectType === 'long') {
      // Long shadow - generated gradient shadow
      const shadowColor = '#33313b';
      const steps = 50;
      const shadows: string[] = [];
      for (let i = 1; i <= steps; i++) {
        const opacity = 0.5 * (1 - (i / steps)); // Fade out
        const rgba = hexToRgba(shadowColor, opacity);
        shadows.push(`${i}px ${i}px 0 ${rgba}`);
      }
      effectShadows.push(...shadows);
    } else if (effectType === 'party') {
      // Party Time - multiple colorful shadows based on page title color
      // Text color should be white
      cssVars['--page-title-color'] = '#ffffff';

      // Get page title color to generate variations
      const pageTitleColor = allValues['typography_tokens.color.heading'] ?? '#ffffff';
      const baseColor = typeof pageTitleColor === 'string' ? pageTitleColor : '#ffffff';

      // Generate color variations from base color
      const partyColors = generatePartyColors(baseColor);

      // Get font size to calculate shadow offsets (use percentage of font size)
      const fontSize = allValues['typography_tokens.scale.heading'] ?? 24;
      const fontSizeNum = typeof fontSize === 'number' ? fontSize : Number(fontSize) || 24;

      // Create shadows with increasing offsets (similar to vw units but using px)
      // Offsets: 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5 times a base unit
      const baseOffset = Math.max(2, fontSizeNum * 0.05); // 5% of font size, min 2px
      const offsets = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5];

      offsets.forEach((multiplier, index) => {
        const offset = baseOffset * multiplier;
        const color = partyColors[index] || partyColors[partyColors.length - 1];
        effectShadows.push(`${offset}px ${offset}px 0px ${color}`);
      });
    }

    // Combine shadows: border first (renders on top), then effect (renders behind)
    // In CSS text-shadow, first shadows render on top, so border should be first
    // CRITICAL: If effect is 'none', only use border shadows (if any)
    // If effect is 'none' and no border shadows, set text-shadow to 'none' to clear any existing effects
    const allShadows = effectType === 'none' ? borderShadows : [...borderShadows, ...effectShadows];
    if (allShadows.length > 0) {
      cssVars['--page-title-text-shadow'] = allShadows.join(', ');
    } else {
      // Explicitly set to 'none' to clear any existing text-shadow from previous effects
      cssVars['--page-title-text-shadow'] = 'none';
    }

    // Set the effect class name for use in components
    // CRITICAL: If effect is 'none', explicitly set to empty string to remove any existing effect class
    if (effectType !== 'none' && effectType !== '' && effectType !== null) {
      cssVars['--page-title-effect-class'] = `page-title-effect-${effectType}`;
    } else {
      // Explicitly set to empty string to ensure effect class is removed
      cssVars['--page-title-effect-class'] = '';
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

/**
 * Generate party colors from a base color
 * Creates a vibrant gradient from the base color
 */
function generatePartyColors(baseColor: string): string[] {
  // Parse base color
  const cleanHex = baseColor.replace('#', '');
  const fullHex = cleanHex.length === 3
    ? cleanHex.split('').map(char => char + char).join('')
    : cleanHex;

  const r = parseInt(fullHex.substring(0, 2), 16);
  const g = parseInt(fullHex.substring(2, 4), 16);
  const b = parseInt(fullHex.substring(4, 6), 16);

  // Convert RGB to HSL for easier color manipulation
  const hsl = rgbToHsl(r, g, b);

  // Generate 9 colors: lighter/more saturated -> darker/less saturated
  const colors: string[] = [];
  for (let i = 0; i < 9; i++) {
    // Adjust hue slightly for variation (rotate around color wheel)
    const hueShift = (i * 30) % 360; // 30 degree steps
    const newHue = (hsl.h + hueShift) % 360;

    // Start bright and saturated, gradually darken
    const saturation = Math.max(70, 100 - (i * 3)); // 100% -> 70%
    const lightness = Math.max(30, 80 - (i * 5)); // 80% -> 30%

    const rgb = hslToRgb(newHue, saturation, lightness);
    colors.push(`#${rgb.r.toString(16).padStart(2, '0')}${rgb.g.toString(16).padStart(2, '0')}${rgb.b.toString(16).padStart(2, '0')}`);
  }

  return colors;
}

/**
 * Convert RGB to HSL
 */
function rgbToHsl(r: number, g: number, b: number): { h: number; s: number; l: number } {
  r /= 255;
  g /= 255;
  b /= 255;

  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  let h = 0;
  let s = 0;
  const l = (max + min) / 2;

  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

    switch (max) {
      case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
      case g: h = ((b - r) / d + 2) / 6; break;
      case b: h = ((r - g) / d + 4) / 6; break;
    }
  }

  return { h: h * 360, s: s * 100, l: l * 100 };
}

/**
 * Convert HSL to RGB
 */
function hslToRgb(h: number, s: number, l: number): { r: number; g: number; b: number } {
  h /= 360;
  s /= 100;
  l /= 100;

  let r: number, g: number, b: number;

  if (s === 0) {
    r = g = b = l;
  } else {
    const hue2rgb = (p: number, q: number, t: number) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return {
    r: Math.round(r * 255),
    g: Math.round(g * 255),
    b: Math.round(b * 255)
  };
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

