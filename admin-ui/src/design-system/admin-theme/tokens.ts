/**
 * Admin UI Theme Tokens
 * 
 * CRITICAL: These tokens use --admin-* prefix to avoid conflicts with user page variables (--pod-*)
 * These are for the admin UI only, not for user-facing pages.
 */

export type AdminThemeMode = 'light' | 'dark';

export interface AdminThemeTokens {
  // Brand colors (from poda.bio brand guidelines)
  brand: {
    bgDeepBlack: string; // #121212 - never pure black
    accentSignalGreen: string; // #00FF7F - with outer glow effect
    textWhite: string; // #FFFFFF - primary text
    textTechGrey: string; // #8E8E93 - secondary text/metadata
  };
  
  // Typography
  typography: {
    fontHeading: string; // Nunito Sans Expanded Extrabold
    fontBody: string; // Space Mono Regular
  };
  
  // Backgrounds
  background: {
    page: string;
    panel: string;
    rail: string;
    canvas: string;
    overlay: string;
    button: string;
    buttonHover: string;
  };
  
  // Text colors
  text: {
    primary: string;
    secondary: string;
    muted: string;
    inverse: string;
    accent: string;
  };
  
  // Borders
  border: {
    default: string;
    subtle: string;
    strong: string;
    accent: string;
  };
  
  // Surfaces
  surface: {
    panel: string;
    canvas: string;
    overlay: string;
    elevated: string;
  };
  
  // Shadows
  shadow: {
    sm: string;
    md: string;
    lg: string;
    glow: string; // For signal green glow effect
  };
  
  // Accent colors
  accent: {
    primary: string;
    primaryHover: string;
    secondary: string;
  };
}

export const adminThemeTokens: Record<AdminThemeMode, AdminThemeTokens> = {
  dark: {
    brand: {
      bgDeepBlack: '#121212',
      accentSignalGreen: '#00FF7F',
      textWhite: '#FFFFFF',
      textTechGrey: '#8E8E93',
    },
    typography: {
      fontHeading: "'Nunito Sans Expanded', 'Nunito Sans', -apple-system, BlinkMacSystemFont, sans-serif",
      fontBody: "'Space Mono', 'Courier New', monospace",
    },
    background: {
      page: '#121212',
      panel: '#1a1a1a',
      rail: '#121212',
      canvas: '#0f0f0f',
      overlay: 'rgba(0, 0, 0, 0.8)',
      button: '#2a2a2a',
      buttonHover: '#333333',
    },
    text: {
      primary: '#FFFFFF',
      secondary: '#8E8E93',
      muted: 'rgba(255, 255, 255, 0.5)',
      inverse: '#121212',
      accent: '#00FF7F',
    },
    border: {
      default: 'rgba(255, 255, 255, 0.1)',
      subtle: 'rgba(255, 255, 255, 0.05)',
      strong: 'rgba(255, 255, 255, 0.2)',
      accent: '#00FF7F',
    },
    surface: {
      panel: '#1a1a1a',
      canvas: '#0f0f0f',
      overlay: 'rgba(0, 0, 0, 0.8)',
      elevated: '#242424',
    },
    shadow: {
      sm: '0 1px 2px rgba(0, 0, 0, 0.3)',
      md: '0 4px 8px rgba(0, 0, 0, 0.4)',
      lg: '0 8px 16px rgba(0, 0, 0, 0.5)',
      glow: '0 0 8px rgba(0, 255, 127, 0.5), 0 0 16px rgba(0, 255, 127, 0.3)',
    },
    accent: {
      primary: '#00FF7F',
      primaryHover: '#00E670',
      secondary: '#8E8E93',
    },
  },
  light: {
    brand: {
      bgDeepBlack: '#121212',
      accentSignalGreen: '#00FF7F',
      textWhite: '#FFFFFF',
      textTechGrey: '#8E8E93',
    },
    typography: {
      fontHeading: "'Nunito Sans Expanded', 'Nunito Sans', -apple-system, BlinkMacSystemFont, sans-serif",
      fontBody: "'Space Mono', 'Courier New', monospace",
    },
    background: {
      page: '#f5f7ff',
      panel: '#ffffff',
      rail: '#1e293b',
      canvas: '#f8fafc',
      overlay: 'rgba(9, 18, 39, 0.6)',
      button: '#ffffff',
      buttonHover: '#f8fafc',
    },
    text: {
      primary: '#0f172a',
      secondary: '#475569',
      muted: 'rgba(15, 23, 42, 0.5)',
      inverse: '#ffffff',
      accent: '#2563eb',
    },
    border: {
      default: 'rgba(15, 23, 42, 0.1)',
      subtle: 'rgba(15, 23, 42, 0.05)',
      strong: 'rgba(15, 23, 42, 0.2)',
      accent: '#2563eb',
    },
    surface: {
      panel: '#ffffff',
      canvas: '#f8fafc',
      overlay: 'rgba(9, 18, 39, 0.6)',
      elevated: '#ffffff',
    },
    shadow: {
      sm: '0 1px 2px rgba(0, 0, 0, 0.1)',
      md: '0 4px 8px rgba(0, 0, 0, 0.15)',
      lg: '0 8px 16px rgba(0, 0, 0, 0.2)',
      glow: '0 0 8px rgba(37, 99, 235, 0.3), 0 0 16px rgba(37, 99, 235, 0.2)',
    },
    accent: {
      primary: '#2563eb',
      primaryHover: '#1d4ed8',
      secondary: '#475569',
    },
  },
};

/**
 * Convert theme tokens to CSS variables
 * All variables use --admin-* prefix to avoid conflicts with --pod-* (user page variables)
 */
export function adminThemeTokensToCSSVars(tokens: AdminThemeTokens): Record<string, string> {
  return {
    // Brand colors
    '--admin-brand-bg-deep-black': tokens.brand.bgDeepBlack,
    '--admin-brand-accent-signal-green': tokens.brand.accentSignalGreen,
    '--admin-brand-text-white': tokens.brand.textWhite,
    '--admin-brand-text-tech-grey': tokens.brand.textTechGrey,
    
    // Typography
    '--admin-font-heading': tokens.typography.fontHeading,
    '--admin-font-body': tokens.typography.fontBody,
    
    // Backgrounds
    '--admin-bg-page': tokens.background.page,
    '--admin-bg-panel': tokens.background.panel,
    '--admin-bg-rail': tokens.background.rail,
    '--admin-bg-canvas': tokens.background.canvas,
    '--admin-bg-overlay': tokens.background.overlay,
    '--admin-bg-button': tokens.background.button,
    '--admin-bg-button-hover': tokens.background.buttonHover,
    
    // Text colors
    '--admin-text-primary': tokens.text.primary,
    '--admin-text-secondary': tokens.text.secondary,
    '--admin-text-muted': tokens.text.muted,
    '--admin-text-inverse': tokens.text.inverse,
    '--admin-text-accent': tokens.text.accent,
    
    // Borders
    '--admin-border-default': tokens.border.default,
    '--admin-border-subtle': tokens.border.subtle,
    '--admin-border-strong': tokens.border.strong,
    '--admin-border-accent': tokens.border.accent,
    
    // Surfaces
    '--admin-surface-panel': tokens.surface.panel,
    '--admin-surface-canvas': tokens.surface.canvas,
    '--admin-surface-overlay': tokens.surface.overlay,
    '--admin-surface-elevated': tokens.surface.elevated,
    
    // Shadows
    '--admin-shadow-sm': tokens.shadow.sm,
    '--admin-shadow-md': tokens.shadow.md,
    '--admin-shadow-lg': tokens.shadow.lg,
    '--admin-shadow-glow': tokens.shadow.glow,
    
    // Accents
    '--admin-accent-primary': tokens.accent.primary,
    '--admin-accent-primary-hover': tokens.accent.primaryHover,
    '--admin-accent-secondary': tokens.accent.secondary,
  };
}

