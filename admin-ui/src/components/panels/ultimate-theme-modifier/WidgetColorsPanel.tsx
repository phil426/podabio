import { useMemo, useState, useEffect } from 'react';
import { BackgroundColorSwatch } from '../../controls/BackgroundColorSwatch';
import { FontSelect } from './FontSelect';
import { SliderInput } from './SliderInput';
import { WidgetBorderEffectSelect } from './WidgetBorderEffectSelect';
import { usePageSnapshot } from '../../../api/page';
import { useThemeLibraryQuery } from '../../../api/themes';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './page-settings-panel.module.css';

function deriveActiveTheme(themeLibrary: any, themeId: number | null) {
  if (!themeLibrary) return null;
  const systemThemes = themeLibrary?.system ?? [];
  const userThemes = themeLibrary?.user ?? [];
  
  if (themeId == null) {
    return systemThemes[0] ?? userThemes[0] ?? null;
  }
  
  const combined = [...userThemes, ...systemThemes];
  return combined.find((theme: any) => theme.id === themeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
}

function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object') return input;
  if (typeof input !== 'string') return null;
  try {
    const parsed = JSON.parse(input);
    return typeof parsed === 'object' && parsed !== null ? parsed : null;
  } catch {
    return null;
  }
}

interface WidgetColorsPanelProps {
  tokens: TokenBundle;
  tokenValues: Map<string, unknown>;
  onTokenChange: (path: string, value: unknown, oldValue: unknown) => void;
  widgetBackground?: string | null;
  widgetBorder?: string | null;
  widgetShadow?: string | null;
  widgetHeadingText?: string | null;
  widgetBodyText?: string | null;
  socialIconColor?: string | null;
}

function resolveToken(bundle: TokenBundle, path: string): unknown {
  const parts = path.split('.');
  let current: any = bundle;
  
  for (const part of parts) {
    if (current && typeof current === 'object' && part in current) {
      current = current[part];
    } else {
      return undefined;
    }
  }
  
  return current;
}

function extractColorValue(tokens: TokenBundle, path: string): string {
  const resolved = resolveToken(tokens, path);
  
  if (typeof resolved === 'string') {
    if (/^#([0-9a-fA-F]{3}){1,2}$/.test(resolved)) {
      return resolved;
    }
    if (resolved.includes('gradient')) {
      return resolved;
    }
    if (resolved.startsWith('http://') || resolved.startsWith('https://') || resolved.startsWith('/') || resolved.startsWith('data:')) {
      return resolved;
    }
    if (resolved.startsWith('rgba(')) {
      return resolved;
    }
  }
  
  return '#2563eb';
}

function extractNumericValue(tokens: TokenBundle, path: string, defaultValue: number = 0): number {
  const resolved = resolveToken(tokens, path);
  if (typeof resolved === 'number') {
    return resolved;
  }
  if (typeof resolved === 'string') {
    const num = parseFloat(resolved);
    if (!isNaN(num)) {
      return num;
    }
  }
  return defaultValue;
}

// Helper to determine background type from value
function getBackgroundType(value: string): 'solid' | 'gradient' | 'image' {
  if (!value || typeof value !== 'string') return 'solid';
  if (value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient')) {
    return 'gradient';
  }
  if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/') || value.startsWith('data:')) {
    return 'image';
  }
  return 'solid';
}

export function WidgetColorsPanel({ tokens, tokenValues, onTokenChange, widgetBackground: widgetBackgroundProp, widgetBorder: widgetBorderProp, widgetShadow: widgetShadowProp, widgetHeadingText: widgetHeadingTextProp, widgetBodyText: widgetBodyTextProp, socialIconColor: socialIconColorProp }: WidgetColorsPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  
  // Derive active theme from theme library
  const activeTheme = useMemo(() => {
    return deriveActiveTheme(themeLibrary, snapshot?.page?.theme_id ?? null);
  }, [themeLibrary, snapshot?.page?.theme_id]);
  
  // Load border_effect from widget_styles
  const [borderEffect, setBorderEffect] = useState<'none' | 'shadow' | 'glow'>('shadow');
  
  useEffect(() => {
    const widgetStyles = activeTheme?.widget_styles ? safeParse(activeTheme.widget_styles) : null;
    const effect = widgetStyles?.border_effect;
    if (effect === 'shadow' || effect === 'glow') {
      setBorderEffect(effect);
    } else {
      setBorderEffect('none');
    }
  }, [activeTheme?.widget_styles]);
  // Extract values - prioritize tokenValues (unsaved changes), then props from active theme, then tokens
  const widgetHeadingText = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved) - use widget-specific token
    const colorFromValues = tokenValues.get('core.typography.color.widget_heading') as string | undefined;
    if (colorFromValues && typeof colorFromValues === 'string' && colorFromValues.trim() !== '') {
      return colorFromValues;
    }
    // Priority 2: Check props from active theme
    if (widgetHeadingTextProp && typeof widgetHeadingTextProp === 'string' && widgetHeadingTextProp.trim() !== '') {
      return widgetHeadingTextProp;
    }
    // Priority 3: Extract from tokens - use widget-specific token
    const widgetColor = extractColorValue(tokens, 'core.typography.color.widget_heading');
    if (widgetColor && widgetColor !== '#2563eb') { // #2563eb is the default "not found" value
      return widgetColor;
    }
    // Priority 4: Fallback to page heading color if widget color not set
    return extractColorValue(tokens, 'semantic.text.primary');
  }, [widgetHeadingTextProp, tokens, tokenValues]);
  
  const widgetBodyText = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved) - use widget-specific token
    const colorFromValues = tokenValues.get('core.typography.color.widget_body') as string | undefined;
    if (colorFromValues && typeof colorFromValues === 'string' && colorFromValues.trim() !== '') {
      return colorFromValues;
    }
    // Priority 2: Check props from active theme
    if (widgetBodyTextProp && typeof widgetBodyTextProp === 'string' && widgetBodyTextProp.trim() !== '') {
      return widgetBodyTextProp;
    }
    // Priority 3: Extract from tokens - use widget-specific token
    const widgetColor = extractColorValue(tokens, 'core.typography.color.widget_body');
    if (widgetColor && widgetColor !== '#2563eb') { // #2563eb is the default "not found" value
      return widgetColor;
    }
    // Priority 4: Fallback to page body color if widget color not set
    return extractColorValue(tokens, 'semantic.text.secondary');
  }, [widgetBodyTextProp, tokens, tokenValues]);
  
  const widgetBackground = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const bgFromValues = tokenValues.get('semantic.surface.base') as string | undefined;
    if (bgFromValues && typeof bgFromValues === 'string' && bgFromValues.trim() !== '') {
      return bgFromValues;
    }
    // Priority 2: Check prop from ColorsPanel (which loads from active theme)
    if (widgetBackgroundProp && typeof widgetBackgroundProp === 'string' && widgetBackgroundProp.trim() !== '') {
      return widgetBackgroundProp;
    }
    // Priority 3: Check active theme's widget_background directly
    const themeWidgetBg = activeTheme?.widget_background;
    if (themeWidgetBg && typeof themeWidgetBg === 'string' && themeWidgetBg.trim() !== '') {
      return themeWidgetBg;
    }
    // Priority 4: Check theme's color_tokens.background.surface
    if (activeTheme?.color_tokens) {
      try {
        const colorTokens = safeParse(activeTheme.color_tokens);
        const surfaceBg = colorTokens?.background?.surface;
        if (surfaceBg && typeof surfaceBg === 'string' && surfaceBg.trim() !== '') {
          return surfaceBg;
        }
      } catch (e) {
        console.warn('Failed to parse theme color_tokens:', e);
      }
    }
    // Priority 5: Fallback to tokens
    const tokenValue = extractColorValue(tokens, 'semantic.surface.base');
    // Return default white if token value is the default blue (means not found)
    return tokenValue !== '#2563eb' ? tokenValue : '#FFFFFF';
  }, [widgetBackgroundProp, tokens, activeTheme, tokenValues]);
  
  const widgetBorder = useMemo(() => {
    // Priority 1: Check prop from ColorsPanel (which loads from active theme)
    if (widgetBorderProp && typeof widgetBorderProp === 'string' && widgetBorderProp.trim() !== '') {
      return widgetBorderProp;
    }
    // Priority 2: Check active theme's widget_border_color directly
    const themeBorderColor = activeTheme?.widget_border_color;
    if (themeBorderColor && typeof themeBorderColor === 'string' && themeBorderColor.trim() !== '') {
      return themeBorderColor;
    }
    // Priority 3: Fallback to tokens
    const tokenValue = extractColorValue(tokens, 'semantic.divider.subtle');
    // Return default rgba if token value is the default blue (means not found)
    return tokenValue !== '#2563eb' ? tokenValue : 'rgba(0, 0, 0, 0.1)';
  }, [widgetBorderProp, tokens, activeTheme]);
  
  const widgetShadow = useMemo(() => {
    if (widgetShadowProp && typeof widgetShadowProp === 'string' && widgetShadowProp.trim() !== '') {
      return widgetShadowProp;
    }
    return extractColorValue(tokens, 'semantic.shadow.ambient');
  }, [widgetShadowProp, tokens]);

  // Widget fonts - widgets use the same font tokens as page
  const widgetHeadingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  const widgetBodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  // Widget sizes - widgets use the same size tokens as page
  const widgetHeadingSize = useMemo(() => extractNumericValue(tokens, 'core.typography.size.heading', 24), [tokens]);
  const widgetBodySize = useMemo(() => extractNumericValue(tokens, 'core.typography.size.body', 16), [tokens]);

  // Widget border width - extract from shape_tokens.border_width.regular or tokenValues
  const widgetBorderWidth = useMemo(() => {
    // First check tokenValues
    const width = tokenValues.get('widget_border_width') as string | number | undefined;
    if (typeof width === 'number') {
      return width;
    }
    if (typeof width === 'string') {
      const num = parseFloat(width);
      if (!isNaN(num)) {
        return num;
      }
    }
    // Fall back to shape_tokens.border_width.regular
    const borderWidth = extractNumericValue(tokens, 'shape.border_width.regular', 2);
    return borderWidth;
  }, [tokens, tokenValues]);

  // Widget shadow/glow intensity - extract from tokenValues or default to 50 (0-100 scale)
  const widgetShadowIntensity = useMemo(() => {
    const intensity = tokenValues.get('widget_shadow_intensity') as string | number | undefined;
    if (typeof intensity === 'number') {
      return intensity;
    }
    if (typeof intensity === 'string') {
      const num = parseFloat(intensity);
      if (!isNaN(num)) {
        return num;
      }
    }
    // Default to 50 (middle intensity)
    return 50;
  }, [tokenValues]);

  // Widget width - extract from tokenValues or default to 100 (percentage)
  const widgetWidth = useMemo(() => {
    const width = tokenValues.get('widget_width') as string | number | undefined;
    if (typeof width === 'number') {
      return width;
    }
    if (typeof width === 'string') {
      // Remove % if present and parse
      const num = parseFloat(width.replace('%', ''));
      if (!isNaN(num)) {
        return num;
      }
    }
    // Default to 100% (full width)
    return 100;
  }, [tokenValues]);

  // Social icons values from iconography tokens - prioritize prop from active theme
  const socialIconColor = useMemo(() => {
    if (socialIconColorProp && typeof socialIconColorProp === 'string' && socialIconColorProp.trim() !== '') {
      return socialIconColorProp;
    }
    const color = tokenValues.get('iconography_tokens.color') as string | undefined;
    if (color && typeof color === 'string') {
      // Check if it's a valid color format
      if (/^#([0-9a-fA-F]{3}){1,2}$/.test(color) || color.startsWith('rgba(') || color.includes('gradient')) {
        return color;
      }
    }
    return '#2563eb'; // Default fallback
  }, [socialIconColorProp, tokenValues]);

  const socialIconSize = useMemo(() => {
    const size = tokenValues.get('iconography_tokens.size') as string | number | undefined;
    if (typeof size === 'number') {
      return size;
    }
    if (typeof size === 'string') {
      // Extract numeric value from string like "48px" -> 48
      const num = parseFloat(size);
      if (!isNaN(num)) {
        return num;
      }
    }
    return 48; // Default 48px
  }, [tokenValues]);

  const socialIconSpacing = useMemo(() => {
    const spacing = tokenValues.get('iconography_tokens.spacing') as string | number | undefined;
    if (typeof spacing === 'number') {
      return spacing;
    }
    if (typeof spacing === 'string') {
      // Extract numeric value from string like "0.75rem" -> 0.75
      const num = parseFloat(spacing);
      if (!isNaN(num)) {
        return num;
      }
    }
    return 0.75; // Default 0.75rem
  }, [tokenValues]);

  const backgroundType = useMemo(() => {
    const bg = widgetBackground;
    if (!bg || typeof bg !== 'string') {
      return 'solid';
    }
    if (bg.includes('gradient') || bg.includes('linear-gradient') || bg.includes('radial-gradient')) {
      return 'gradient';
    }
    if (bg.startsWith('http://') || bg.startsWith('https://') || bg.startsWith('/') || bg.startsWith('data:')) {
      return 'image';
    }
    return 'solid';
  }, [widgetBackground]);

  const backgroundImage = useMemo(() => {
    if (backgroundType === 'image' && widgetBackground) {
      return widgetBackground;
    }
    return null;
  }, [backgroundType, widgetBackground]);

  const handleColorChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleFontChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleSizeChange = (path: string, value: number) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleIconographyChange = (key: 'color' | 'size' | 'spacing', value: string | number) => {
    // Iconography tokens are stored in tokenValues, not in TokenBundle
    // We need to update them via a special path
    const path = `iconography_tokens.${key}`;
    const oldValue = tokenValues.get(path);
    onTokenChange(path, value, oldValue);
  };

  const handleWidgetBorderWidthChange = (value: number) => {
    const path = 'widget_border_width';
    const oldValue = tokenValues.get(path);
    onTokenChange(path, value, oldValue);
  };

  const handleWidgetShadowIntensityChange = (value: number) => {
    const path = 'widget_shadow_intensity';
    const oldValue = tokenValues.get(path);
    onTokenChange(path, value, oldValue);
  };

  const handleWidgetWidthChange = (value: number) => {
    const path = 'widget_width';
    const oldValue = tokenValues.get(path);
    onTokenChange(path, value, oldValue);
  };

  const handleBorderEffectChange = (value: 'none' | 'shadow' | 'glow') => {
    setBorderEffect(value);
    // Store border_effect in widget_styles via a special token path
    const path = 'widget_styles.border_effect';
    const oldValue = tokenValues.get(path);
    // Only save 'shadow' or 'glow', not 'none' (which means no effect)
    const effectValue = value === 'none' ? null : value;
    onTokenChange(path, effectValue, oldValue);
  };
  
  // Get border type and image for widget border
  const borderBackgroundType = useMemo(() => {
    if (!widgetBorder || typeof widgetBorder !== 'string') {
      return 'solid' as const;
    }
    return getBackgroundType(widgetBorder);
  }, [widgetBorder]);
  const borderBackgroundImage = useMemo(() => {
    if (borderBackgroundType === 'image' && widgetBorder) {
      return widgetBorder;
    }
    return null;
  }, [borderBackgroundType, widgetBorder]);

  return (
    <div className={styles.panel}>
      <div className={styles.content}>
        <div className={styles.controls}>
          {/* Widget Background */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Widget background color</label>
            <BackgroundColorSwatch
              value={widgetBackground}
              backgroundType={backgroundType}
              backgroundImage={backgroundImage}
              onChange={(value) => handleColorChange('semantic.surface.base', value)}
              onTypeChange={(type) => {
                if (type === 'solid') {
                  handleColorChange('semantic.surface.base', '#FFFFFF');
                } else if (type === 'gradient') {
                  if (!widgetBackground.includes('gradient')) {
                    handleColorChange('semantic.surface.base', 'linear-gradient(135deg, rgba(122,255,216,0.18) 0%, rgba(91,156,255,0.14) 50%, rgba(168,117,255,0.24) 100%)');
                  }
                }
              }}
              onImageChange={(url) => {
                if (url) {
                  handleColorChange('semantic.surface.base', url);
                }
              }}
              label="Widget background color"
            />
          </div>

          {/* Widget Border */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Widget border</label>
            <BackgroundColorSwatch
              value={widgetBorder}
              backgroundType={borderBackgroundType}
              backgroundImage={borderBackgroundImage}
              onChange={(value) => handleColorChange('semantic.divider.subtle', value)}
              onTypeChange={(type) => {
                if (type === 'solid') {
                  handleColorChange('semantic.divider.subtle', 'rgba(0, 0, 0, 0.1)');
                } else if (type === 'gradient') {
                  if (!widgetBorder.includes('gradient')) {
                    handleColorChange('semantic.divider.subtle', 'linear-gradient(135deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.05) 100%)');
                  }
                }
              }}
              onImageChange={(url) => {
                if (url) {
                  handleColorChange('semantic.divider.subtle', url);
                }
              }}
              label="Widget border"
            />
          </div>

          {/* Widget Width */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Widget width</label>
            <SliderInput
              value={widgetWidth}
              min={50}
              max={100}
              step={5}
              onChange={handleWidgetWidthChange}
              unit="%"
            />
          </div>

          {/* Widget Border Width */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Widget border width</label>
            <SliderInput
              value={widgetBorderWidth}
              min={0}
              max={8}
              step={1}
              onChange={handleWidgetBorderWidthChange}
              unit="px"
            />
          </div>

          {/* Widget Shadow/Glow Effect */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Widget shadow/glow</label>
            <WidgetBorderEffectSelect
              value={borderEffect}
              onChange={handleBorderEffectChange}
            />
          </div>

          {/* Widget Heading section */}
          <div className={styles.section}>
            <h4 className={styles.sectionTitle}>Widget heading</h4>
            
            {/* Widget Heading color */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Widget heading color</label>
              <BackgroundColorSwatch
                value={widgetHeadingText}
                backgroundType={getBackgroundType(widgetHeadingText)}
                onChange={(value) => handleColorChange('core.typography.color.widget_heading', value)}
                onTypeChange={(type) => {
                  if (type === 'solid') {
                    handleColorChange('core.typography.color.widget_heading', '#111827');
                  } else if (type === 'gradient') {
                    if (!widgetHeadingText.includes('gradient')) {
                      handleColorChange('core.typography.color.widget_heading', 'linear-gradient(135deg, #111827 0%, #4b5563 100%)');
                    }
                  }
                }}
                onImageChange={(url) => {
                  if (url) {
                    handleColorChange('core.typography.color.widget_heading', url);
                  }
                }}
                label="Widget heading color"
              />
            </div>

            {/* Widget Heading font */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Widget heading font</label>
              <FontSelect
                value={widgetHeadingFont}
                onChange={(value) => handleFontChange('core.typography.font.heading', value)}
              />
            </div>

            {/* Widget Heading size */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Text size</label>
              <SliderInput
                value={widgetHeadingSize}
                min={14}
                max={40}
                step={1}
                onChange={(value) => handleSizeChange('core.typography.size.heading', value)}
                unit="px"
              />
            </div>
          </div>

          {/* Widget Body section */}
          <div className={styles.section}>
            <h4 className={styles.sectionTitle}>Widget body</h4>
            
            {/* Widget Body color */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Widget body color</label>
              <BackgroundColorSwatch
                value={widgetBodyText}
                backgroundType={getBackgroundType(widgetBodyText)}
                onChange={(value) => handleColorChange('core.typography.color.widget_body', value)}
                onTypeChange={(type) => {
                  if (type === 'solid') {
                    handleColorChange('core.typography.color.widget_body', '#4b5563');
                  } else if (type === 'gradient') {
                    if (!widgetBodyText.includes('gradient')) {
                      handleColorChange('core.typography.color.widget_body', 'linear-gradient(135deg, #4b5563 0%, #6b7280 100%)');
                    }
                  }
                }}
                onImageChange={(url) => {
                  if (url) {
                    handleColorChange('core.typography.color.widget_body', url);
                  }
                }}
                label="Widget body color"
              />
            </div>

            {/* Widget Body font */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Widget body font</label>
              <FontSelect
                value={widgetBodyFont}
                onChange={(value) => handleFontChange('core.typography.font.body', value)}
              />
            </div>

            {/* Widget Body size */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Text size</label>
              <SliderInput
                value={widgetBodySize}
                min={10}
                max={20}
                step={1}
                onChange={(value) => handleSizeChange('core.typography.size.body', value)}
                unit="px"
              />
            </div>
          </div>

          {/* Social Icons section */}
          <div className={styles.section}>
            <h4 className={styles.sectionTitle}>Social icons</h4>
            
            {/* Social Icons color */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Social icons color</label>
              <BackgroundColorSwatch
                value={socialIconColor}
                backgroundType={getBackgroundType(socialIconColor)}
                onChange={(value) => handleIconographyChange('color', value)}
                onTypeChange={(type) => {
                  if (type === 'solid') {
                    handleIconographyChange('color', '#2563eb');
                  } else if (type === 'gradient') {
                    if (!socialIconColor.includes('gradient')) {
                      handleIconographyChange('color', 'linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)');
                    }
                  }
                }}
                onImageChange={(url) => {
                  if (url) {
                    handleIconographyChange('color', url);
                  }
                }}
                label="Social icons color"
              />
            </div>

            {/* Social Icons size */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Social icons size</label>
              <SliderInput
                value={socialIconSize}
                min={20}
                max={64}
                step={1}
                onChange={(value) => handleIconographyChange('size', `${value}px`)}
                unit="px"
              />
            </div>

            {/* Social Icons spacing */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Social icons spacing</label>
              <SliderInput
                value={socialIconSpacing}
                min={0}
                max={3}
                step={0.125}
                onChange={(value) => handleIconographyChange('spacing', `${value}rem`)}
                unit="rem"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

