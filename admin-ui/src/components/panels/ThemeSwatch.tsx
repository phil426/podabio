import { useMemo } from 'react';
import clsx from 'clsx';

import type { ThemeRecord } from '../../api/types';

import styles from './theme-swatch.module.css';

interface ThemeSwatchProps {
  theme: ThemeRecord;
  selected?: boolean;
  isActive?: boolean;
  onApply?: () => void;
  onEdit?: () => void;
  onDuplicate?: () => void;
  onDelete?: () => void;
  showActions?: boolean;
  isUserTheme?: boolean;
  currentColors?: string[]; // Override colors for active theme
  displayName?: string; // Override theme name for display
  currentValues?: Array<string | { gradient: string }>; // Override values (colors or gradients) for active theme
}

function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object' && !Array.isArray(input)) return input as Record<string, unknown>;
  if (typeof input !== 'string') return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

export function extractThemeColors(theme: ThemeRecord): Array<string | { gradient: string }> {
  const colorTokens = safeParse(theme.color_tokens);
  const colors = safeParse(theme.colors);
  
  const palette: Array<string | { gradient: string }> = [];
  
  // Helper to check if value is a gradient
  const isGradient = (value: string): boolean => {
    return value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient');
  };
  
  // Helper to normalize color value (gradient object or solid color string)
  const normalizeColor = (value: string): string | { gradient: string } => {
    if (isGradient(value)) {
      return { gradient: value };
    }
    return value;
  };
  
  // PRIORITY 1: page_background column (primary source from Edit Theme Panel)
  if (theme.page_background && typeof theme.page_background === 'string') {
    palette.push(normalizeColor(theme.page_background));
  }
  
  // PRIORITY 2: widget_background column (for widget background preview)
  if (theme.widget_background && typeof theme.widget_background === 'string') {
    const widgetBg = normalizeColor(theme.widget_background);
    // Only add if different from page background
    const pageBgStr = typeof palette[0] === 'string' ? palette[0] : (typeof palette[0] === 'object' ? palette[0].gradient : null);
    const widgetBgStr = typeof widgetBg === 'string' ? widgetBg : widgetBg.gradient;
    if (pageBgStr !== widgetBgStr) {
      palette.push(widgetBg);
    }
  }
  
  // PRIORITY 3: Extract from color_tokens (for accent and text colors)
  if (colorTokens) {
    const accent = colorTokens.accent as Record<string, unknown> | undefined;
    const text = colorTokens.text as Record<string, unknown> | undefined;
    const background = colorTokens.background as Record<string, unknown> | undefined;
    
    // Only add accent/text colors if we don't already have them
    if (accent?.primary && typeof accent.primary === 'string') {
      const accentColor = normalizeColor(accent.primary);
      if (palette.length < 4) palette.push(accentColor);
    }
    if (accent?.secondary && typeof accent.secondary === 'string' && palette.length < 4) {
      const accentColor = normalizeColor(accent.secondary);
      palette.push(accentColor);
    }
    if (text?.primary && typeof text.primary === 'string' && palette.length < 4) {
      const textColor = normalizeColor(text.primary);
      palette.push(textColor);
    }
    
    // Only use background.base as fallback if page_background wasn't set
    if (palette.length === 0 && background?.base && typeof background.base === 'string') {
      palette.push(normalizeColor(background.base));
    }
  }
  
  // PRIORITY 4: Fallback to legacy colors
  if (palette.length === 0 && colors) {
    if (colors.primary_color && typeof colors.primary_color === 'string') palette.push(normalizeColor(colors.primary_color));
    if (colors.secondary_color && typeof colors.secondary_color === 'string') palette.push(normalizeColor(colors.secondary_color));
    if (colors.accent_color && typeof colors.accent_color === 'string') palette.push(normalizeColor(colors.accent_color));
    if (colors.text_color && typeof colors.text_color === 'string') palette.push(normalizeColor(colors.text_color));
  }
  
  // Ensure we have at least some colors
  if (palette.length === 0) {
    palette.push('#2563eb', '#3b82f6', '#0f172a', '#ffffff');
  }
  
  return palette.slice(0, 5); // Max 5 colors
}

function extractTypography(theme: ThemeRecord) {
  const typographyTokens = safeParse(theme.typography_tokens);
  const fonts = typographyTokens && typeof typographyTokens === 'object' && 'font' in typographyTokens 
    ? (typographyTokens.font as Record<string, unknown>)
    : null;
  const headingFont = (fonts?.heading as string | undefined) ?? theme.page_primary_font ?? 'Inter';
  const bodyFont = (fonts?.body as string | undefined) ?? theme.widget_primary_font ?? 'Inter';
  return { headingFont, bodyFont };
}

export function ThemeSwatch({
  theme,
  selected = false,
  isActive = false,
  onApply,
  onEdit,
  onDuplicate,
  onDelete,
  showActions = true,
  isUserTheme = false,
  currentColors,
  displayName,
  currentValues
}: ThemeSwatchProps): JSX.Element {
  const themeColors = useMemo(() => extractThemeColors(theme), [theme]);
  const { headingFont, bodyFont } = useMemo(() => extractTypography(theme), [theme]);
  
  // Use currentValues if provided (for active theme with gradients), otherwise fall back to currentColors or theme colors
  // themeColors now properly handles gradients, so we can use them directly
  const displayValues = useMemo(() => {
    if (isActive && currentValues && currentValues.length > 0) {
      return currentValues;
    }
    if (isActive && currentColors && currentColors.length > 0) {
      // Convert string array to gradient objects if needed
      return currentColors.map(c => {
        if (typeof c === 'string' && (c.includes('gradient') || c.includes('linear-gradient') || c.includes('radial-gradient'))) {
          return { gradient: c };
    }
        return c;
      });
    }
    // themeColors now returns Array<string | { gradient: string }>, so use directly
    return themeColors;
  }, [isActive, currentValues, currentColors, themeColors]);
  
  // Use displayName if provided, otherwise use theme name
  const name = displayName || theme.name;
  
  // Helper to check if a value is a gradient
  const isGradient = (value: string | { gradient: string }): value is { gradient: string } => {
    return typeof value === 'object' && 'gradient' in value;
  };
  
  // Helper to get the style for a swatch (gradient or solid color)
  const getSwatchStyle = (value: string | { gradient: string }, index: number) => {
    if (isGradient(value)) {
      return {
        background: value.gradient,
        zIndex: displayValues.length - index
      };
    } else {
      return {
        backgroundColor: value,
        zIndex: displayValues.length - index
      };
    }
  };
  
  return (
    <div
      className={clsx(
        styles.swatch,
        selected && styles.selected,
        isActive && styles.active
      )}
    >
      {/* Theme Name */}
      <div className={styles.footer}>
        <span className={styles.name}>{name}</span>
      </div>
      <div className={styles.swatchMainRow}>
        {/* Color Swatches - Overlapping Circles */}
        <div className={styles.colorPalette}>
          {displayValues.slice(0, 4).map((value, index) => {
            const style = getSwatchStyle(value, index);
            const label = isGradient(value) 
              ? `Gradient ${index + 1}` 
              : `Color ${index + 1}: ${value}`;
            
            return (
              <div
                key={index}
                className={styles.colorSwatch}
                style={style}
                aria-label={label}
              />
            );
          })}
        </div>

        {/* Typography Preview */}
        <div className={styles.typographySample} aria-label="Typography preview">
          <span
            className={styles.headingSample}
            style={{ fontFamily: headingFont }}
          >
            Aa
          </span>
          <span
            className={styles.bodySample}
            style={{ fontFamily: bodyFont }}
          >
            Body
          </span>
        </div>
      </div>
    </div>
  );
}

