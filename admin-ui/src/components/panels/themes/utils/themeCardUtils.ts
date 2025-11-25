/**
 * Theme Card Utilities
 * Shared utilities for theme card components
 */

import type { ThemeRecord } from '../../../../api/types';

export function safeParse(input: string | null | undefined): Record<string, unknown> | null {
  if (!input) return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

/**
 * Extract color swatches from theme
 * Returns array of hex color strings
 */
export function extractColorSwatches(theme: ThemeRecord): string[] {
  // Try color_tokens first (new format)
  const colorTokens = safeParse(theme.color_tokens);
  if (colorTokens) {
    const accent = colorTokens.accent as Record<string, unknown> | undefined;
    const background = colorTokens.background as Record<string, unknown> | undefined;
    const text = colorTokens.text as Record<string, unknown> | undefined;
    
    const colors: string[] = [];
    
    // Add background colors
    if (background?.base && typeof background.base === 'string') {
      colors.push(background.base);
    }
    if (background?.surface && typeof background.surface === 'string') {
      colors.push(background.surface);
    }
    
    // Add accent colors
    if (accent?.primary && typeof accent.primary === 'string') {
      colors.push(accent.primary);
    }
    if (accent?.secondary && typeof accent.secondary === 'string') {
      colors.push(accent.secondary);
    }
    
    // Add text colors
    if (text?.primary && typeof text.primary === 'string') {
      colors.push(text.primary);
    }
    
    if (colors.length > 0) {
      return colors.slice(0, 5);
    }
  }
  
  // Fallback to legacy colors format
  const raw = typeof theme.colors === 'string' ? safeParse(theme.colors) : theme.colors;
  if (raw && typeof raw === 'object') {
    return Object.values(raw)
      .filter((value): value is string => typeof value === 'string' && value.trim().startsWith('#'))
      .slice(0, 5);
  }
  
  // Default fallback
  return ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#dbeafe'];
}

/**
 * Check if a color is dark (for contrast)
 */
export function isDarkColor(color: string): boolean {
  const hexMatch = color.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hexMatch) {
    const normalized = color.replace('#', '');
    const bigint = parseInt(normalized.length === 3 ? normalized.repeat(2) : normalized, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    const luminance = 0.2126 * (r / 255) + 0.7152 * (g / 255) + 0.0722 * (b / 255);
    return luminance < 0.5;
  }
  return false;
}

/**
 * Get hero background style for theme card
 */
export function getHeroBackground(theme: ThemeRecord, swatches: string[]): React.CSSProperties {
  const primarySwatch = swatches[0] ?? '#2563eb';
  const secondarySwatch = swatches[1] ?? '#1d4ed8';
  
  if (theme.preview_image) {
    return {
      backgroundImage: `linear-gradient(135deg, rgba(15, 23, 42, 0.2), rgba(15, 23, 42, 0.55)), url(${theme.preview_image})`
    };
  }
  
  return {
    backgroundImage: `linear-gradient(135deg, ${primarySwatch}, ${secondarySwatch})`
  };
}

/**
 * Get card background color
 */
export function getCardBackground(theme: ThemeRecord): string | undefined {
  if (theme.page_background && typeof theme.page_background === 'string') {
    return theme.page_background;
  }
  if (theme.widget_background && typeof theme.widget_background === 'string') {
    return theme.widget_background;
  }
  return undefined;
}

/**
 * Extract typography from theme
 */
export function extractTypography(theme: ThemeRecord): { headingFont: string; bodyFont: string } {
  return {
    headingFont: theme.page_primary_font ?? theme.widget_primary_font ?? 'inherit',
    bodyFont: theme.page_secondary_font ?? theme.widget_secondary_font ?? 'inherit'
  };
}

/**
 * Get theme metadata labels
 */
export function getThemeMetadata(theme: ThemeRecord): { ownerBadge: string; densityLabel: string } {
  return {
    ownerBadge: theme.user_id ? 'Community' : 'System',
    densityLabel: theme.layout_density ?? 'cozy'
  };
}

/**
 * Get theme description/subtitle based on theme name or characteristics
 * Descriptions written with a subtle, dry sense of humor
 */
export function getThemeDescription(theme: ThemeRecord): string {
  const name = (theme.name ?? '').toLowerCase();
  
  // Map known theme names to descriptions (with dry humor)
  const themeDescriptions: Record<string, string> = {
    'amber glow': 'Warm, golden, and probably needs a sunset to match.',
    'aqaba': 'Red Sea vibes, minus the actual sea. Or the red.',
    'aurora borealis': 'Northern lights, now in your browser. No travel required.',
    'charcoal': 'Dark mode for people who take themselves seriously.',
    'charcoal sketch': 'Artistic darkness with a golden touch. Like a sketch, but digital.',
    'cherry blossom': 'Pink, pretty, and probably Japanese. Sakura season, all year.',
    'classic minimal': 'The theme that proves less is more, and more is... well, less.',
    'cloud white': 'So clean, it might actually clean your screen.',
    'coral reef': 'Tropical vibes without the sunburn. Or the actual reef.',
    'cream soda': 'Sweet, bubbly, and surprisingly professional.',
    'crimson tide': 'Deep red, dramatic, and probably needs a soundtrack.',
    'deep ocean': 'Dive deep into elegance. Warning: may cause feelings of tranquility.',
    'ember': 'Warm, inviting, and slightly dangerous. Like a good campfire.',
    'emerald city': 'Green, glorious, and probably has a wizard. Somewhere.',
    'forest canopy': 'Nature\'s green, now in pixels. Trees not included.',
    'forest night': 'Nature-inspired, but without the bugs. Or the actual nature.',
    'golden hour': 'That perfect lighting photographers love. Now in theme form.',
    'lavender dreams': 'Soft, purple, and probably involves sleep. Or should.',
    'lavender fields': 'Soft, serene, and probably French. Oui, oui.',
    'legal pad': 'For when your content needs to look important. And yellow.',
    'maui sundown': 'Tropical paradise, now in theme form. Mai tais sold separately.',
    'midnight blue': 'Dark mode done right. Because staring at white screens at 2am is a crime.',
    'midnight express': 'Fast, dark, and probably going somewhere important. Or not.',
    'midnight oil': 'Deep blue, scholarly, and probably involves late-night studying.',
    'mint fresh': 'Fresh, calming, and minty. Like toothpaste, but for your website.',
    'moonlight': 'Elegant, mysterious, and slightly nocturnal. Perfect for night owls.',
    'morning mist': 'Light, airy, and probably caffeinated. Or should be.',
    'my diary': 'Personal, intimate, and slightly dramatic. Just like a real diary.',
    'new linen': 'Crisp, clean, and wrinkle-free. Unlike actual linen.',
    'notebook': 'For when you want your content to look handwritten. But readable.',
    'ocean depths': 'Deep blue-green, mysterious, and probably has fish. Somewhere.',
    'obsidian': 'Dark, sleek, and sharper than it looks. Handle with care.',
    'oxford library': 'Academic, sophisticated, and smells like old books. Metaphorically.',
    'paper white': 'Minimalist perfection. Like paper, but better. And digital.',
    'peach blush': 'Soft, warm, and slightly embarrassed. In a good way.',
    'peach fuzz': 'Soft, fuzzy, and probably needs a shave. Themed, not literal.',
    'plum perfect': 'Rich purple, elegant, and probably expensive. Themed.',
    'rose quartz': 'Delicate, beautiful, and surprisingly hard. Like actual quartz.',
    'sage & stone': 'Earthy, grounded, and probably zen. Or trying to be.',
    'sage garden': 'Calming, earthy, and probably good for your chakras.',
    'sand dollar': 'Beachy, breezy, and worth exactly one dollar. Themed.',
    'sky light': 'Bright, airy, and probably requires sunglasses.',
    'slate gray': 'Muted, sophisticated, and probably British. Or trying to be.',
    'slate night': 'Dark, sophisticated, and slightly geological.',
    'steel blue': 'Industrial, cool, and probably made of steel. Metaphorically.',
    'sunset boulevard': 'Hollywood glamour, now in gradients. Stars not included.',
    'the daily': 'Newspaper chic, minus the actual news. Or the paper.',
    'turquoise bay': 'Caribbean vibes, minus the actual Caribbean. Or the bay.',
    'twilight': 'That perfect time between day and night. Also a book series.',
    'vanilla bean': 'Classic, simple, and surprisingly complex. Like actual vanilla.',
    'velvet': 'Luxurious, smooth, and probably expensive. Themed.',
    'violet storm': 'Purple power, dramatic, and probably needs thunder. Themed.',
    'white sand': 'Clean, pristine, and won\'t get in your shoes.',
    'wine dark': 'Deep, rich, and probably pairs well with cheese.',
  };
  
  // Check exact match first
  if (themeDescriptions[name]) {
    return themeDescriptions[name];
  }
  
  // Check partial matches
  for (const [key, description] of Object.entries(themeDescriptions)) {
    if (name.includes(key) || key.includes(name)) {
      return description;
    }
  }
  
  // Generate description based on color characteristics
  const swatches = extractColorSwatches(theme);
  const primaryColor = swatches[0];
  
  if (primaryColor) {
    const isDark = isDarkColor(primaryColor);
    if (isDark) {
      return 'Bold, striking, and contrast-compliant. We checked.';
    } else {
      return 'Bright, breezy, and actually readable. You\'re welcome.';
    }
  }
  
  // Default fallback
  return 'A theme. It has colors. And fonts. Revolutionary.';
}

/**
 * Get primary swatch color for checkmark icon
 */
export function getPrimarySwatch(theme: ThemeRecord): string {
  const swatches = extractColorSwatches(theme);
  return swatches[0] ?? '#2563eb';
}

/**
 * Get button color from theme (accent primary or fallback)
 */
export function getButtonColor(theme: ThemeRecord): string {
  const colorTokens = safeParse(theme.color_tokens);
  if (colorTokens) {
    const accent = colorTokens.accent as Record<string, unknown> | undefined;
    if (accent?.primary && typeof accent.primary === 'string') {
      return accent.primary;
    }
  }
  
  // Fallback to legacy colors
  const raw = typeof theme.colors === 'string' ? safeParse(theme.colors) : theme.colors;
  if (raw && typeof raw === 'object') {
    const primaryColor = raw.primary_color as string | undefined;
    if (primaryColor && typeof primaryColor === 'string') {
      return primaryColor;
    }
  }
  
  // Default fallback
  return '#2563eb';
}

/**
 * Get button border radius from theme shape tokens
 * Returns: 'square' | 'rounded' | 'pill' | number (px value)
 */
export function getButtonRadius(theme: ThemeRecord): 'square' | 'rounded' | 'pill' {
  const shapeTokens = safeParse(theme.shape_tokens);
  if (shapeTokens && typeof shapeTokens === 'object') {
    // Check button_corner first
    const buttonCorner = (shapeTokens as any).button_corner;
    if (buttonCorner) {
      const buttonCornerValues = Object.values(buttonCorner) as string[];
      if (buttonCornerValues.length > 0) {
        const activeButtonCornerValue = buttonCornerValues[0];
        if (activeButtonCornerValue === '0px' || activeButtonCornerValue === '0') {
          return 'square';
        } else if (activeButtonCornerValue === '9999px' || activeButtonCornerValue === '999px') {
          return 'pill';
        } else {
          return 'rounded';
        }
      }
    }
    
    // Fallback to corner tokens
    const corner = (shapeTokens as any).corner;
    if (corner) {
      const cornerValues = Object.values(corner) as string[];
      if (cornerValues.length > 0) {
        const activeCornerValue = cornerValues[0];
        if (activeCornerValue === '0px' || activeCornerValue === '0') {
          return 'square';
        } else if (activeCornerValue === '9999px' || activeCornerValue === '999px') {
          return 'pill';
        } else {
          return 'rounded';
        }
      }
    }
    
    // Fallback to old button_radius
    const buttonRadiusOld = (shapeTokens as any).button_radius;
    if (buttonRadiusOld) {
      if (buttonRadiusOld === '0px' || buttonRadiusOld === '0') {
        return 'square';
      } else if (buttonRadiusOld === '9999px' || buttonRadiusOld === '999px') {
        return 'pill';
      } else {
        return 'rounded';
      }
    }
  }
  
  // Default fallback
  return 'rounded';
}


