import type { ThemeRecord } from '../../../api/types';

/**
 * Safe JSON parser helper
 */
function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object') return input;
  if (typeof input !== 'string') return null;

  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

/**
 * Extract typography information from theme
 */
export function extractTypography(theme: ThemeRecord): { headingFont: string; bodyFont: string } {
  const typographyTokens = safeParse(theme.typography_tokens);
  const fonts = typographyTokens && typeof typographyTokens === 'object' && 'font' in typographyTokens 
    ? (typographyTokens.font as Record<string, unknown>)
    : null;
  const headingFont = (fonts?.heading as string | undefined) ?? theme.page_primary_font ?? 'Inter';
  const bodyFont = (fonts?.body as string | undefined) ?? theme.widget_primary_font ?? 'Inter';
  return { headingFont, bodyFont };
}

/**
 * Extract color swatches from theme
 */
export function extractColorSwatches(theme: ThemeRecord): string[] {
  const colorTokens = safeParse(theme.color_tokens);
  const colors = safeParse(theme.colors);
  
  const swatches: string[] = [];
  
  // Helper to check if value is a gradient
  const isGradient = (value: string): boolean => {
    return value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient');
  };
  
  // Helper to extract solid color from gradient or return color as-is
  const extractSolidColor = (value: string): string | null => {
    if (!value) return null;
    if (isGradient(value)) {
      // Try to extract first color from gradient
      const match = value.match(/#([0-9a-fA-F]{3}){1,2}/);
      return match ? match[0] : null;
    }
    // Check if it's a valid hex color
    if (/^#([0-9a-fA-F]{3}){1,2}$/.test(value)) {
      return value;
    }
    return null;
  };
  
  // PRIORITY 1: page_background
  if (theme.page_background && typeof theme.page_background === 'string') {
    const color = extractSolidColor(theme.page_background);
    if (color) swatches.push(color);
  }
  
  // PRIORITY 2: widget_background
  if (theme.widget_background && typeof theme.widget_background === 'string') {
    const color = extractSolidColor(theme.widget_background);
    if (color && !swatches.includes(color)) swatches.push(color);
  }
  
  // PRIORITY 3: Extract from color_tokens
  if (colorTokens) {
    const accent = colorTokens.accent as Record<string, unknown> | undefined;
    const text = colorTokens.text as Record<string, unknown> | undefined;
    
    if (accent?.primary && typeof accent.primary === 'string') {
      const color = extractSolidColor(accent.primary);
      if (color && !swatches.includes(color) && swatches.length < 5) swatches.push(color);
    }
    if (accent?.secondary && typeof accent.secondary === 'string' && swatches.length < 5) {
      const color = extractSolidColor(accent.secondary);
      if (color && !swatches.includes(color)) swatches.push(color);
    }
    if (text?.primary && typeof text.primary === 'string' && swatches.length < 5) {
      const color = extractSolidColor(text.primary);
      if (color && !swatches.includes(color)) swatches.push(color);
    }
  }
  
  // PRIORITY 4: Fallback to legacy colors
  if (swatches.length === 0 && colors) {
    if (colors.primary_color && typeof colors.primary_color === 'string') {
      const color = extractSolidColor(colors.primary_color);
      if (color) swatches.push(color);
    }
    if (colors.secondary_color && typeof colors.secondary_color === 'string' && swatches.length < 5) {
      const color = extractSolidColor(colors.secondary_color);
      if (color && !swatches.includes(color)) swatches.push(color);
    }
    if (colors.accent_color && typeof colors.accent_color === 'string' && swatches.length < 5) {
      const color = extractSolidColor(colors.accent_color);
      if (color && !swatches.includes(color)) swatches.push(color);
    }
  }
  
  // Ensure we have at least some colors
  if (swatches.length === 0) {
    swatches.push('#2563eb', '#3b82f6', '#0f172a', '#ffffff');
  }
  
  return swatches.slice(0, 5); // Max 5 swatches
}

/**
 * Get hero background style from theme
 */
export function getHeroBackground(theme: ThemeRecord, swatches: string[]): React.CSSProperties {
  // Check if page_background is a gradient
  if (theme.page_background && typeof theme.page_background === 'string') {
    const bg = theme.page_background;
    if (bg.includes('gradient') || bg.includes('linear-gradient') || bg.includes('radial-gradient')) {
      return { background: bg };
    }
  }
  
  // Use first swatch as solid background
  if (swatches.length > 0) {
    return { background: swatches[0] };
  }
  
  // Fallback
  return { background: '#2563eb' };
}

/**
 * Get card background color/style
 */
export function getCardBackground(theme: ThemeRecord): string | null {
  // Use page_background if available
  if (theme.page_background && typeof theme.page_background === 'string') {
    return theme.page_background;
  }
  
  // Fallback to widget_background
  if (theme.widget_background && typeof theme.widget_background === 'string') {
    return theme.widget_background;
  }
  
  // Fallback to white
  return '#ffffff';
}

/**
 * Check if a color is dark (for contrast purposes)
 */
export function isDarkColor(color: string | null): boolean {
  if (!color || typeof color !== 'string') return false;
  
  // Extract hex from gradient or use as-is
  const match = color.match(/#([0-9a-fA-F]{3}){1,2}/);
  const hexColor = match ? match[0] : color;
  
  if (!hexColor || !/^#([0-9a-fA-F]{3}){1,2}$/.test(hexColor)) {
    return false;
  }
  
  // Convert hex to RGB
  const hex = hexColor.replace('#', '');
  const r = hex.length === 3 ? parseInt(hex[0] + hex[0], 16) : parseInt(hex.substring(0, 2), 16);
  const g = hex.length === 3 ? parseInt(hex[1] + hex[1], 16) : parseInt(hex.substring(2, 4), 16);
  const b = hex.length === 3 ? parseInt(hex[2] + hex[2], 16) : parseInt(hex.substring(4, 6), 16);
  
  // Calculate luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  
  // Consider dark if luminance is less than 0.5
  return luminance < 0.5;
}

/**
 * Get theme metadata (owner badge and density label)
 */
export function getThemeMetadata(theme: ThemeRecord): { ownerBadge: string; densityLabel: string } {
  const ownerBadge = theme.user_id ? 'Custom' : 'System';
  const densityLabel = theme.layout_density || 'Standard';
  
  return { ownerBadge, densityLabel };
}
