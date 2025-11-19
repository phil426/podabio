import { useMemo, useCallback, useState, useEffect, useRef } from 'react';
import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import * as Tooltip from '@radix-ui/react-tooltip';
import { usePageSnapshot, updatePageThemeId } from '../../api/page';
import { useThemeLibraryQuery, useUpdateThemeMutation, useCreateThemeMutation } from '../../api/themes';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import { useTokens } from '../../design-system/theme/TokenProvider';
import type { TokenBundle } from '../../design-system/tokens';
import { ColorsSection } from './ultimate-theme-modifier/ColorsSection';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './colors-panel.module.css';

interface ColorsPanelProps {
  activeColor: TabColorTheme;
}

// Helper function to safely parse JSON
function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object') return input;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

// Helper function to update tokens by path
function applyTokenUpdate<T extends Record<string, any>>(obj: T, path: string, value: unknown): T {
  const parts = path.split('.');
  const result = { ...obj };
  let current: any = result;
  
  for (let i = 0; i < parts.length - 1; i++) {
    const key = parts[i];
    if (!(key in current) || typeof current[key] !== 'object' || current[key] === null) {
      current[key] = {};
    } else {
      current[key] = { ...current[key] };
    }
    current = current[key];
  }
  
  current[parts[parts.length - 1]] = value;
  return result;
}

// Extract color value from tokens
function extractColorValue(bundle: TokenBundle, path: string): string {
  const parts = path.split('.');
  let current: any = bundle;
  
  for (const part of parts) {
    if (current && typeof current === 'object' && part in current) {
      current = current[part];
    } else {
      return '#2563eb';
    }
  }
  
  if (typeof current === 'string') {
    return current;
  }
  
  return '#2563eb';
}

// Extract color from CSS box-shadow value (e.g., "0 1px 2px rgba(15, 23, 42, 0.06)" -> "rgba(15, 23, 42, 0.06)")
function extractColorFromShadow(shadowValue: string): string {
  if (!shadowValue || typeof shadowValue !== 'string') {
    return 'rgba(15, 23, 42, 0.06)';
  }
  
  // Match rgba/rgb values in the shadow
  const rgbaMatch = shadowValue.match(/rgba?\([^)]+\)/);
  if (rgbaMatch) {
    return rgbaMatch[0];
  }
  
  // Match hex colors in the shadow
  const hexMatch = shadowValue.match(/#[0-9a-fA-F]{3,6}/);
  if (hexMatch) {
    return hexMatch[0];
  }
  
  // Fallback
  return 'rgba(15, 23, 42, 0.06)';
}

// Helper functions to compute optimal text colors (matching PHP ThemeCSSGenerator logic)
function getLuminance(color: string): number {
  // Validate hex color
  if (!color || typeof color !== 'string' || !/^#?[0-9a-fA-F]{3,6}$/i.test(color)) {
    return 0.5; // Neutral luminance for non-hex colors
  }
  
  // Remove # if present
  color = color.replace('#', '');
  
  // Handle 3-digit hex
  if (color.length === 3) {
    color = color[0] + color[0] + color[1] + color[1] + color[2] + color[2];
  }
  
  // Ensure exactly 6 hex digits
  if (color.length !== 6 || !/^[0-9a-fA-F]{6}$/.test(color)) {
    return 0.5;
  }
  
  // Convert to RGB
  const r = parseInt(color.substr(0, 2), 16) / 255;
  const g = parseInt(color.substr(2, 2), 16) / 255;
  const b = parseInt(color.substr(4, 2), 16) / 255;
  
  // Apply gamma correction
  const gammaCorrect = (val: number) => val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
  const rG = gammaCorrect(r);
  const gG = gammaCorrect(g);
  const bG = gammaCorrect(b);
  
  // Calculate luminance
  return 0.2126 * rG + 0.7152 * gG + 0.0722 * bG;
}

function getContrastRatio(color1: string, color2: string): number {
  const lum1 = getLuminance(color1);
  const lum2 = getLuminance(color2);
  const lighter = Math.max(lum1, lum2);
  const darker = Math.min(lum1, lum2);
  return (lighter + 0.05) / (darker + 0.05);
}

function getDominantBackgroundColor(background: string): string {
  if (!background || typeof background !== 'string') {
    return '#ffffff';
  }
  
  // Check for gradient
  const gradientMatch = background.match(/linear-gradient\([^,]+,\s*(#[0-9a-fA-F]{3,6})\s*\d+%,\s*(#[0-9a-fA-F]{3,6})\s*\d+%\)/i);
  if (gradientMatch) {
    const color1 = gradientMatch[1].length === 4 
      ? '#' + gradientMatch[1][1] + gradientMatch[1][1] + gradientMatch[1][2] + gradientMatch[1][2] + gradientMatch[1][3] + gradientMatch[1][3]
      : gradientMatch[1];
    const color2 = gradientMatch[2].length === 4
      ? '#' + gradientMatch[2][1] + gradientMatch[2][1] + gradientMatch[2][2] + gradientMatch[2][2] + gradientMatch[2][3] + gradientMatch[2][3]
      : gradientMatch[2];
    
    // Normalize to 6-digit hex
    const normalize = (hex: string) => hex.length === 4
      ? '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]
      : hex;
    const c1 = normalize(color1);
    const c2 = normalize(color2);
    
    if (/^#[0-9a-fA-F]{6}$/i.test(c1) && /^#[0-9a-fA-F]{6}$/i.test(c2)) {
      const lum1 = getLuminance(c1);
      const lum2 = getLuminance(c2);
      // Return the lighter color as it's more likely to be the "background"
      return lum1 > lum2 ? c1 : c2;
    }
    return c1 || c2 || '#ffffff';
  }
  
  // Check for solid hex color
  if (/^#?[0-9a-fA-F]{3,6}$/i.test(background)) {
    const normalized = background.replace('#', '');
    if (normalized.length === 3) {
      return '#' + normalized[0] + normalized[0] + normalized[1] + normalized[1] + normalized[2] + normalized[2];
    }
    return background.startsWith('#') ? background : '#' + background;
  }
  
  return '#ffffff';
}

function getOptimalTextColor(backgroundColor: string, defaultColor: string): string {
  const bgColor = getDominantBackgroundColor(backgroundColor);
  const bgLum = getLuminance(bgColor);
  
  // Check contrast with default color
  const contrast = getContrastRatio(defaultColor, bgColor);
  
  // WCAG AA threshold: 4:1
  if (contrast >= 4.0) {
    return defaultColor;
  }
  
  // If contrast is poor, choose white or black based on background
  if (bgLum < 0.5) {
    // Dark background - use white or light color
    const whiteContrast = getContrastRatio('#ffffff', bgColor);
    if (whiteContrast >= 4.0) {
      return '#ffffff';
    }
    return '#f0f0f0';
  } else {
    // Light background - use black or dark color
    const blackContrast = getContrastRatio('#000000', bgColor);
    if (blackContrast >= 4.0) {
      return '#000000';
    }
    return '#1a1a1a';
  }
}

export function ColorsPanel({ activeColor }: ColorsPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const { tokens, setTokens } = useTokens();
  const updateMutation = useUpdateThemeMutation();
  const createMutation = useCreateThemeMutation();
  const queryClient = useQueryClient();
  const page = snapshot?.page;
  const [tokenValues, setTokenValues] = useState<Map<string, unknown>>(new Map());
  const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const activeTheme = useMemo(() => {
    const systemThemes = themeLibrary?.system ?? [];
    const userThemes = themeLibrary?.user ?? [];
    const themeId = page?.theme_id ?? null;
    
    if (themeId == null) {
      return systemThemes[0] ?? userThemes[0] ?? null;
    }
    
    const combined = [...userThemes, ...systemThemes];
    return combined.find((theme) => theme.id === themeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
  }, [themeLibrary, page?.theme_id]);

  // Load tokenValues from theme
  useEffect(() => {
    if (!activeTheme) {
      setTokenValues(new Map());
      return;
    }

    const initialValues = new Map<string, unknown>();
    const colorTokens = safeParse(activeTheme.color_tokens);
    const typographyTokens = safeParse(activeTheme.typography_tokens);
    const spacingTokens = safeParse(activeTheme.spacing_tokens);
    const shapeTokens = safeParse(activeTheme.shape_tokens);
    const motionTokens = safeParse(activeTheme.motion_tokens);

    // Load color tokens
    if (colorTokens?.gradient) {
      if (colorTokens.gradient.page) initialValues.set('color_tokens.gradient.page', colorTokens.gradient.page);
      if (colorTokens.gradient.accent) initialValues.set('color_tokens.gradient.accent', colorTokens.gradient.accent);
      if (colorTokens.gradient.widget) initialValues.set('color_tokens.gradient.widget', colorTokens.gradient.widget);
      if (colorTokens.gradient.podcast) initialValues.set('color_tokens.gradient.podcast', colorTokens.gradient.podcast);
    }

    // Load typography tokens
    if (typographyTokens) {
      if (typographyTokens.scale) {
        if (typographyTokens.scale.xl) initialValues.set('core.typography.scale.xl', typographyTokens.scale.xl);
        if (typographyTokens.scale.lg) initialValues.set('core.typography.scale.lg', typographyTokens.scale.lg);
        if (typographyTokens.scale.md) initialValues.set('core.typography.scale.md', typographyTokens.scale.md);
        if (typographyTokens.scale.sm) initialValues.set('core.typography.scale.sm', typographyTokens.scale.sm);
        if (typographyTokens.scale.xs) initialValues.set('core.typography.scale.xs', typographyTokens.scale.xs);
      }
      if (typographyTokens.line_height) {
        if (typographyTokens.line_height.tight) initialValues.set('core.typography.line_height.tight', typographyTokens.line_height.tight);
        if (typographyTokens.line_height.normal) initialValues.set('core.typography.line_height.normal', typographyTokens.line_height.normal);
        if (typographyTokens.line_height.relaxed) initialValues.set('core.typography.line_height.relaxed', typographyTokens.line_height.relaxed);
      }
      if (typographyTokens.weight) {
        if (typographyTokens.weight.normal) initialValues.set('core.typography.weight.normal', typographyTokens.weight.normal);
        if (typographyTokens.weight.medium) initialValues.set('core.typography.weight.medium', typographyTokens.weight.medium);
        if (typographyTokens.weight.bold) initialValues.set('core.typography.weight.bold', typographyTokens.weight.bold);
      }
    }

    // Load spacing tokens
    if (spacingTokens) {
      if (spacingTokens.density) initialValues.set('spacing_tokens.density', spacingTokens.density);
      if (spacingTokens.page_multiplier !== undefined) {
        const multiplier = typeof spacingTokens.page_multiplier === 'number'
          ? spacingTokens.page_multiplier
          : parseFloat(String(spacingTokens.page_multiplier));
        if (!isNaN(multiplier)) {
          initialValues.set('page_spacing_multiplier', multiplier);
        }
      }
      if (spacingTokens.base_scale) {
        if (spacingTokens.base_scale['2xs']) initialValues.set('spacing_tokens.base_scale.2xs', spacingTokens.base_scale['2xs']);
        if (spacingTokens.base_scale.xs) initialValues.set('spacing_tokens.base_scale.xs', spacingTokens.base_scale.xs);
        if (spacingTokens.base_scale.sm) initialValues.set('spacing_tokens.base_scale.sm', spacingTokens.base_scale.sm);
        if (spacingTokens.base_scale.md) initialValues.set('spacing_tokens.base_scale.md', spacingTokens.base_scale.md);
        if (spacingTokens.base_scale.lg) initialValues.set('spacing_tokens.base_scale.lg', spacingTokens.base_scale.lg);
        if (spacingTokens.base_scale.xl) initialValues.set('spacing_tokens.base_scale.xl', spacingTokens.base_scale.xl);
        if (spacingTokens.base_scale['2xl']) initialValues.set('spacing_tokens.base_scale.2xl', spacingTokens.base_scale['2xl']);
      }
    }

    // Load shape tokens
    if (shapeTokens) {
      if (shapeTokens.corner) {
        if (shapeTokens.corner.none) initialValues.set('shape_tokens.corner.none', shapeTokens.corner.none);
        if (shapeTokens.corner.sm) initialValues.set('shape_tokens.corner.sm', shapeTokens.corner.sm);
        if (shapeTokens.corner.md) initialValues.set('shape_tokens.corner.md', shapeTokens.corner.md);
        if (shapeTokens.corner.lg) initialValues.set('shape_tokens.corner.lg', shapeTokens.corner.lg);
        if (shapeTokens.corner.pill) initialValues.set('shape_tokens.corner.pill', shapeTokens.corner.pill);
      }
      if (shapeTokens.border_width) {
        if (shapeTokens.border_width.hairline) initialValues.set('shape_tokens.border_width.hairline', shapeTokens.border_width.hairline);
        if (shapeTokens.border_width.regular) initialValues.set('shape_tokens.border_width.regular', shapeTokens.border_width.regular);
        if (shapeTokens.border_width.bold) initialValues.set('shape_tokens.border_width.bold', shapeTokens.border_width.bold);
      }
    }

    // Load motion tokens
    if (motionTokens) {
      if (motionTokens.duration) {
        if (motionTokens.duration.fast) initialValues.set('motion_tokens.duration.fast', motionTokens.duration.fast);
        if (motionTokens.duration.standard) initialValues.set('motion_tokens.duration.standard', motionTokens.duration.standard);
      }
      if (motionTokens.easing) {
        if (motionTokens.easing.standard) initialValues.set('motion_tokens.easing.standard', motionTokens.easing.standard);
        if (motionTokens.easing.decelerate) initialValues.set('motion_tokens.easing.decelerate', motionTokens.easing.decelerate);
      }
      if (motionTokens.focus) {
        if (motionTokens.focus.ring_width) initialValues.set('motion_tokens.focus.ring_width', motionTokens.focus.ring_width);
        if (motionTokens.focus.ring_offset) initialValues.set('motion_tokens.focus.ring_offset', motionTokens.focus.ring_offset);
      }
    }

    // Load widget_styles values
    const widgetStyles = safeParse(activeTheme.widget_styles);
    if (widgetStyles) {
      if (widgetStyles.border_width) {
        const borderWidth = typeof widgetStyles.border_width === 'string' 
          ? parseFloat(widgetStyles.border_width.replace('px', ''))
          : typeof widgetStyles.border_width === 'number'
          ? widgetStyles.border_width
          : undefined;
        if (!isNaN(borderWidth as number)) {
          initialValues.set('widget_border_width', borderWidth);
        }
      }
      if (widgetStyles.border_shadow_intensity_numeric !== undefined) {
        const intensity = typeof widgetStyles.border_shadow_intensity_numeric === 'number'
          ? widgetStyles.border_shadow_intensity_numeric
          : parseFloat(String(widgetStyles.border_shadow_intensity_numeric));
        if (!isNaN(intensity)) {
          initialValues.set('widget_shadow_intensity', intensity);
        }
      }
    }

    // Load iconography tokens
    const iconographyTokens = safeParse(activeTheme.iconography_tokens);
    if (iconographyTokens) {
      // Use !== undefined check to match save logic (allows empty strings, null, etc.)
      if (iconographyTokens.color !== undefined) initialValues.set('iconography_tokens.color', iconographyTokens.color);
      if (iconographyTokens.size !== undefined) initialValues.set('iconography_tokens.size', iconographyTokens.size);
      if (iconographyTokens.spacing !== undefined) initialValues.set('iconography_tokens.spacing', iconographyTokens.spacing);
    }

    setTokenValues(initialValues);
  }, [activeTheme]);

  // Save theme changes with debouncing
  const saveTheme = useCallback(async () => {
    if (!activeTheme || !tokens || isSaving) return;

    setIsSaving(true);
    try {
      const isSystemTheme = activeTheme.user_id === null || activeTheme.user_id === undefined;
      const existingColorTokens = safeParse(activeTheme.color_tokens);
      const existingTypographyTokens = safeParse(activeTheme.typography_tokens);
      const existingSpacingTokens = safeParse(activeTheme.spacing_tokens);
      const existingShapeTokens = safeParse(activeTheme.shape_tokens);
      const existingMotionTokens = safeParse(activeTheme.motion_tokens);

      // Extract current color values from tokens
      const accentPrimary = extractColorValue(tokens, 'semantic.accent.primary');
      const accentSecondary = extractColorValue(tokens, 'semantic.accent.secondary');
      const textPrimary = extractColorValue(tokens, 'semantic.text.primary');
      const textSecondary = extractColorValue(tokens, 'semantic.text.secondary');
      const textInverse = extractColorValue(tokens, 'semantic.text.inverse');
      const backgroundBase = extractColorValue(tokens, 'semantic.surface.canvas');
      const backgroundSurface = extractColorValue(tokens, 'semantic.surface.base');

      // Build color tokens
      const colorTokens: Record<string, any> = {
        ...(existingColorTokens || {}),
        accent: {
          ...(existingColorTokens?.accent || {}),
          primary: accentPrimary,
          secondary: accentSecondary,
          muted: existingColorTokens?.accent?.muted || 'rgba(37, 99, 235, 0.18)'
        },
        text: {
          ...(existingColorTokens?.text || {}),
          primary: textPrimary,
          secondary: textSecondary,
          inverse: textInverse
        },
        background: {
          ...(existingColorTokens?.background || {}),
          base: backgroundBase,
          surface: backgroundSurface,
          surface_raised: existingColorTokens?.background?.surface_raised || 'rgba(255, 255, 255, 0.95)',
          overlay: existingColorTokens?.background?.overlay || 'rgba(15, 23, 42, 0.6)'
        },
        border: {
          ...(existingColorTokens?.border || {}),
          default: existingColorTokens?.border?.default || 'rgba(0, 0, 0, 0.1)',
          focus: existingColorTokens?.border?.focus || accentPrimary
        },
        state: {
          ...(existingColorTokens?.state || {}),
          success: existingColorTokens?.state?.success || '#12b76a',
          warning: existingColorTokens?.state?.warning || '#f59e0b',
          danger: existingColorTokens?.state?.danger || '#ef4444'
        },
        text_state: {
          ...(existingColorTokens?.text_state || {}),
          success: existingColorTokens?.text_state?.success || '#0f5132',
          warning: existingColorTokens?.text_state?.warning || '#7c2d12',
          danger: existingColorTokens?.text_state?.danger || '#7f1d1d'
        },
        shadow: {
          ...(existingColorTokens?.shadow || {}),
          ambient: existingColorTokens?.shadow?.ambient || 'rgba(15, 23, 42, 0.12)',
          focus: existingColorTokens?.shadow?.focus || 'rgba(37, 99, 235, 0.35)'
        },
        gradient: {
          page: tokenValues.get('color_tokens.gradient.page') as string ?? existingColorTokens?.gradient?.page ?? null,
          accent: tokenValues.get('color_tokens.gradient.accent') as string ?? existingColorTokens?.gradient?.accent ?? null,
          widget: tokenValues.get('color_tokens.gradient.widget') as string ?? existingColorTokens?.gradient?.widget ?? null,
          podcast: tokenValues.get('color_tokens.gradient.podcast') as string ?? existingColorTokens?.gradient?.podcast ?? null
        },
        glow: {
          ...(existingColorTokens?.glow || {}),
          primary: existingColorTokens?.glow?.primary || null
        }
      };

      // Extract typography values
      const headingFont = typeof tokens.core?.typography?.font?.heading === 'string' 
        ? tokens.core.typography.font.heading 
        : 'Inter';
      const bodyFont = typeof tokens.core?.typography?.font?.body === 'string'
        ? tokens.core.typography.font.body
        : 'Inter';

      // Build typography tokens
      const typographyTokens: Record<string, any> = {
        ...(existingTypographyTokens || {}),
        font: {
          ...(existingTypographyTokens?.font || {}),
          heading: headingFont,
          body: bodyFont,
          metatext: existingTypographyTokens?.font?.metatext || bodyFont
        },
        scale: {
          xl: tokenValues.get('core.typography.scale.xl') as number ?? existingTypographyTokens?.scale?.xl ?? 2.55,
          lg: tokenValues.get('core.typography.scale.lg') as number ?? existingTypographyTokens?.scale?.lg ?? 1.9,
          md: tokenValues.get('core.typography.scale.md') as number ?? existingTypographyTokens?.scale?.md ?? 1.32,
          sm: tokenValues.get('core.typography.scale.sm') as number ?? existingTypographyTokens?.scale?.sm ?? 1.08,
          xs: tokenValues.get('core.typography.scale.xs') as number ?? existingTypographyTokens?.scale?.xs ?? 0.9
        },
        line_height: {
          tight: tokenValues.get('core.typography.line_height.tight') as number ?? existingTypographyTokens?.line_height?.tight ?? 1.2,
          normal: tokenValues.get('core.typography.line_height.normal') as number ?? existingTypographyTokens?.line_height?.normal ?? 1.55,
          relaxed: tokenValues.get('core.typography.line_height.relaxed') as number ?? existingTypographyTokens?.line_height?.relaxed ?? 1.8
        },
        weight: {
          normal: tokenValues.get('core.typography.weight.normal') as number ?? existingTypographyTokens?.weight?.normal ?? 400,
          medium: tokenValues.get('core.typography.weight.medium') as number ?? existingTypographyTokens?.weight?.medium ?? 500,
          bold: tokenValues.get('core.typography.weight.bold') as number ?? existingTypographyTokens?.weight?.bold ?? 700
        }
      };

      // Build spacing tokens
      const spacingDensity = tokenValues.get('spacing_tokens.density') as string || existingSpacingTokens?.density || 'comfortable';
      const pageSpacingMultiplier = tokenValues.get('page_spacing_multiplier') as number | undefined;
      const spacingTokens: Record<string, any> = {
        ...(existingSpacingTokens || {}),
        density: spacingDensity,
        ...(pageSpacingMultiplier !== undefined && { page_multiplier: pageSpacingMultiplier }),
        base_scale: {
          '2xs': tokenValues.get('spacing_tokens.base_scale.2xs') as number ?? existingSpacingTokens?.base_scale?.['2xs'] ?? 0.25,
          'xs': tokenValues.get('spacing_tokens.base_scale.xs') as number ?? existingSpacingTokens?.base_scale?.xs ?? 0.5,
          'sm': tokenValues.get('spacing_tokens.base_scale.sm') as number ?? existingSpacingTokens?.base_scale?.sm ?? 0.85,
          'md': tokenValues.get('spacing_tokens.base_scale.md') as number ?? existingSpacingTokens?.base_scale?.md ?? 1.1,
          'lg': tokenValues.get('spacing_tokens.base_scale.lg') as number ?? existingSpacingTokens?.base_scale?.lg ?? 1.6,
          'xl': tokenValues.get('spacing_tokens.base_scale.xl') as number ?? existingSpacingTokens?.base_scale?.xl ?? 2.2,
          '2xl': tokenValues.get('spacing_tokens.base_scale.2xl') as number ?? existingSpacingTokens?.base_scale?.['2xl'] ?? 3.2
        },
        density_multipliers: existingSpacingTokens?.density_multipliers || {},
        modifiers: existingSpacingTokens?.modifiers || []
      };

      // Build shape tokens
      const shapeTokens: Record<string, any> = {
        ...(existingShapeTokens || {}),
        corner: {
          none: tokenValues.get('shape_tokens.corner.none') as string ?? existingShapeTokens?.corner?.none ?? '0px',
          sm: tokenValues.get('shape_tokens.corner.sm') as string ?? existingShapeTokens?.corner?.sm ?? '0.4rem',
          md: tokenValues.get('shape_tokens.corner.md') as string ?? existingShapeTokens?.corner?.md ?? '0.9rem',
          lg: tokenValues.get('shape_tokens.corner.lg') as string ?? existingShapeTokens?.corner?.lg ?? '1.6rem',
          pill: tokenValues.get('shape_tokens.corner.pill') as string ?? existingShapeTokens?.corner?.pill ?? '999px'
        },
        border_width: {
          hairline: tokenValues.get('shape_tokens.border_width.hairline') as string ?? existingShapeTokens?.border_width?.hairline ?? '1px',
          regular: tokenValues.get('shape_tokens.border_width.regular') as string ?? existingShapeTokens?.border_width?.regular ?? '2px',
          bold: tokenValues.get('shape_tokens.border_width.bold') as string ?? existingShapeTokens?.border_width?.bold ?? '4px'
        },
        shadow: existingShapeTokens?.shadow || {
          level_1: '0 16px 38px rgba(6, 10, 45, 0.32)',
          level_2: '0 24px 60px rgba(6, 10, 45, 0.38)',
          focus: '0 0 0 3px rgba(122,255,216,0.35)'
        }
      };

      // Build motion tokens
      const motionTokens: Record<string, any> = {
        ...(existingMotionTokens || {}),
        duration: {
          fast: tokenValues.get('motion_tokens.duration.fast') as string ?? existingMotionTokens?.duration?.fast ?? '160ms',
          standard: tokenValues.get('motion_tokens.duration.standard') as string ?? existingMotionTokens?.duration?.standard ?? '260ms'
        },
        easing: {
          standard: tokenValues.get('motion_tokens.easing.standard') as string ?? existingMotionTokens?.easing?.standard ?? 'cubic-bezier(0.4, 0, 0.2, 1)',
          decelerate: tokenValues.get('motion_tokens.easing.decelerate') as string ?? existingMotionTokens?.easing?.decelerate ?? 'cubic-bezier(0.0, 0, 0.2, 1)'
        },
        focus: {
          ring_width: tokenValues.get('motion_tokens.focus.ring_width') as string ?? existingMotionTokens?.focus?.ring_width ?? '3px',
          ring_offset: tokenValues.get('motion_tokens.focus.ring_offset') as string ?? existingMotionTokens?.focus?.ring_offset ?? '2px'
        }
      };

      // Determine page_background
      const isGradient = backgroundBase.includes('gradient');
      const isImage = backgroundBase.startsWith('http://') || backgroundBase.startsWith('https://') || backgroundBase.startsWith('/') || backgroundBase.startsWith('data:');
      const pageBackground = isGradient || isImage ? backgroundBase : (existingColorTokens?.gradient?.page || backgroundBase);

      // Build widget_styles
      const existingWidgetStyles = safeParse(activeTheme.widget_styles);
      const widgetBorderWidth = tokenValues.get('widget_border_width') as number | undefined;
      const widgetShadowIntensity = tokenValues.get('widget_shadow_intensity') as number | undefined;
      
      const widgetStyles: Record<string, unknown> = {
        ...(existingWidgetStyles || {}),
        ...(widgetBorderWidth !== undefined && { border_width: `${widgetBorderWidth}px` }),
        ...(widgetShadowIntensity !== undefined && { border_shadow_intensity_numeric: widgetShadowIntensity })
      };

      // Build iconography tokens
      const existingIconographyTokens = safeParse(activeTheme.iconography_tokens);
      const iconographyTokens: Record<string, unknown> = {
        ...(existingIconographyTokens || {}),
        ...(tokenValues.get('iconography_tokens.color') !== undefined && { color: tokenValues.get('iconography_tokens.color') }),
        ...(tokenValues.get('iconography_tokens.size') !== undefined && { size: tokenValues.get('iconography_tokens.size') }),
        ...(tokenValues.get('iconography_tokens.spacing') !== undefined && { spacing: tokenValues.get('iconography_tokens.spacing') })
      };

      const themeData = {
        name: activeTheme.name,
        color_tokens: colorTokens,
        typography_tokens: typographyTokens,
        spacing_tokens: spacingTokens,
        shape_tokens: shapeTokens,
        motion_tokens: motionTokens,
        iconography_tokens: iconographyTokens,
        page_background: pageBackground,
        widget_styles: widgetStyles
      };

      // If it's a system theme, create a new user theme copy
      if (isSystemTheme) {
        // Create new theme with "Custom - [original name]" format
        const customThemeName = `Custom - ${activeTheme.name}`;
        const response = await createMutation.mutateAsync({
          ...themeData,
          name: customThemeName
        });
        
        // If creation was successful and we got a theme ID, update the page to use it
        if (response.success && response.data && typeof response.data === 'object' && 'theme_id' in response.data) {
          const newThemeId = (response.data as { theme_id: number }).theme_id;
          if (typeof newThemeId === 'number') {
            await updatePageThemeId(newThemeId);
          }
        }
      } else {
        // Update existing user theme
        await updateMutation.mutateAsync({
          themeId: activeTheme.id,
          data: themeData
        });
      }

      // Invalidate queries to refresh data
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      console.error('Failed to save theme:', error);
    } finally {
      setIsSaving(false);
    }
  }, [activeTheme, tokens, tokenValues, updateMutation, createMutation, queryClient, isSaving]);

  const handleTokenChange = useCallback((path: string, value: unknown, oldValue: unknown) => {
    if (!tokens) return;
    
    // Update tokenValues for all paths
    setTokenValues(prev => {
      const next = new Map(prev);
      next.set(path, value);
      return next;
    });
    
    // If it's a TokenBundle path, update tokens
    if (path.startsWith('semantic.') || path.startsWith('core.')) {
      const updatedTokens = applyTokenUpdate(tokens, path, value);
      setTokens(updatedTokens);
    }

    // Debounce save - wait 1 second after last change
    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }
    saveTimeoutRef.current = setTimeout(() => {
      saveTheme();
    }, 1000);
  }, [tokens, setTokens, saveTheme]);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, []);

  return (
    <Tooltip.Provider delayDuration={200}>
      <motion.div
        className={styles.panel}
        initial={{ opacity: 0, x: 10 }}
        animate={{ opacity: 1, x: 0 }}
        transition={{ duration: 0.25 }}
        style={{
          '--active-tab-color': activeColor.text,
          '--active-tab-bg': activeColor.primary,
        } as React.CSSProperties}
      >
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.content}>
              <header className={styles.header}>
                <h2>Colors</h2>
                <p>Customize the color palette for your page</p>
              </header>

              {tokens && (
                <ColorsSection
                  tokens={tokens}
                  onTokenChange={handleTokenChange}
                  searchQuery=""
                  tokenValues={tokenValues}
                  pageBackground={useMemo(() => {
                    let result: string | null = null;
                    
                    // Priority 1: Check active theme's page_background column directly (this is what actually renders)
                    if (activeTheme?.page_background && typeof activeTheme.page_background === 'string' && activeTheme.page_background.trim() !== '') {
                      result = activeTheme.page_background;
                    }
                    // Priority 2: Check page_background from page snapshot (might be a page-level override)
                    else if (page?.page_background && typeof page.page_background === 'string' && page.page_background.trim() !== '') {
                      result = page.page_background;
                    }
                    // Priority 3: Try to extract from theme's color_tokens
                    else if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.gradient?.page && typeof colorTokens.gradient.page === 'string' && colorTokens.gradient.page.trim() !== '') {
                          result = colorTokens.gradient.page as string;
                        }
                        else if (colorTokens?.semantic?.surface?.canvas && typeof colorTokens.semantic.surface.canvas === 'string' && colorTokens.semantic.surface.canvas.trim() !== '') {
                          result = colorTokens.semantic.surface.canvas as string;
                        }
                        else if (colorTokens?.background?.base && typeof colorTokens.background.base === 'string' && colorTokens.background.base.trim() !== '') {
                          result = colorTokens.background.base as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme color_tokens:', e);
                      }
                    }
                    // Priority 4: Check tokens from snapshot (should reflect theme)
                    if (!result || result === '#2563eb') {
                      const tokenBg = extractColorValue(tokens, 'semantic.surface.canvas');
                      if (tokenBg && tokenBg !== '#2563eb' && tokenBg.trim() !== '') {
                        result = tokenBg;
                      }
                    }
                    
                    return result || null;
                  }, [page?.page_background, activeTheme?.page_background, activeTheme?.color_tokens, tokens])}
                  pageHeadingText={useMemo(() => {
                    // Priority 1: Check active theme's typography_tokens.color.heading (this maps to --heading-font-color)
                    let headingFontColor: string | null = null;
                    if (activeTheme?.typography_tokens) {
                      try {
                        const typographyTokens = typeof activeTheme.typography_tokens === 'string' 
                          ? JSON.parse(activeTheme.typography_tokens) 
                          : activeTheme.typography_tokens;
                        if (typographyTokens?.color?.heading && typeof typographyTokens.color.heading === 'string' && typographyTokens.color.heading.trim() !== '') {
                          headingFontColor = typographyTokens.color.heading as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme typography_tokens:', e);
                      }
                    }
                    
                    // If we have --heading-font-color, use it
                    if (headingFontColor) {
                      return headingFontColor;
                    }
                    
                    // Priority 2: Compute --page-title-color from page background and textPrimary
                    // Get page background and textPrimary for computation
                    let pageBg = null;
                    if (activeTheme?.page_background && typeof activeTheme.page_background === 'string' && activeTheme.page_background.trim() !== '') {
                      pageBg = activeTheme.page_background;
                    } else if (page?.page_background && typeof page.page_background === 'string' && page.page_background.trim() !== '') {
                      pageBg = page.page_background;
                    } else if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.gradient?.page) pageBg = colorTokens.gradient.page as string;
                        else if (colorTokens?.semantic?.surface?.canvas) pageBg = colorTokens.semantic.surface.canvas as string;
                        else if (colorTokens?.background?.base) pageBg = colorTokens.background.base as string;
                      } catch (e) {}
                    }
                    
                    let textPrimary = null;
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.text?.primary) textPrimary = colorTokens.text.primary as string;
                      } catch (e) {}
                    }
                    if (!textPrimary) {
                      textPrimary = extractColorValue(tokens, 'semantic.text.primary');
                    }
                    
                    // Compute optimal color if we have background
                    if (pageBg && textPrimary) {
                      return getOptimalTextColor(pageBg, textPrimary);
                    }
                    
                    // Fallback to textPrimary
                    return textPrimary || '#111827';
                  }, [activeTheme?.typography_tokens, activeTheme?.color_tokens, activeTheme?.page_background, page?.page_background, tokens])}
                  pageBodyText={useMemo(() => {
                    // Priority 1: Check active theme's typography_tokens.color.body (this maps to --body-font-color)
                    let bodyFontColor: string | null = null;
                    if (activeTheme?.typography_tokens) {
                      try {
                        const typographyTokens = typeof activeTheme.typography_tokens === 'string' 
                          ? JSON.parse(activeTheme.typography_tokens) 
                          : activeTheme.typography_tokens;
                        if (typographyTokens?.color?.body && typeof typographyTokens.color.body === 'string' && typographyTokens.color.body.trim() !== '') {
                          bodyFontColor = typographyTokens.color.body as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme typography_tokens:', e);
                      }
                    }
                    
                    // If we have --body-font-color, use it
                    if (bodyFontColor) {
                      return bodyFontColor;
                    }
                    
                    // Priority 2: Compute --page-description-color from page background and textSecondary
                    // Get page background and textSecondary for computation
                    let pageBg = null;
                    if (activeTheme?.page_background && typeof activeTheme.page_background === 'string' && activeTheme.page_background.trim() !== '') {
                      pageBg = activeTheme.page_background;
                    } else if (page?.page_background && typeof page.page_background === 'string' && page.page_background.trim() !== '') {
                      pageBg = page.page_background;
                    } else if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.gradient?.page) pageBg = colorTokens.gradient.page as string;
                        else if (colorTokens?.semantic?.surface?.canvas) pageBg = colorTokens.semantic.surface.canvas as string;
                        else if (colorTokens?.background?.base) pageBg = colorTokens.background.base as string;
                      } catch (e) {}
                    }
                    
                    let textSecondary = null;
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.text?.secondary) textSecondary = colorTokens.text.secondary as string;
                      } catch (e) {}
                    }
                    if (!textSecondary) {
                      textSecondary = extractColorValue(tokens, 'semantic.text.secondary');
                    }
                    
                    // Compute optimal color if we have background
                    if (pageBg && textSecondary) {
                      return getOptimalTextColor(pageBg, textSecondary);
                    }
                    
                    // Fallback to textSecondary
                    return textSecondary || '#4b5563';
                  }, [activeTheme?.typography_tokens, activeTheme?.color_tokens, activeTheme?.page_background, page?.page_background, tokens])}
                  widgetBackground={useMemo(() => {
                    // Priority 1: Check active theme's widget_background column (this is the ONLY source of truth per PHP)
                    // Note: Page-level widget_background override exists in snapshot but PHP only uses theme values for rendering
                    const themeWidgetBg = activeTheme?.widget_background;
                    if (themeWidgetBg && typeof themeWidgetBg === 'string' && themeWidgetBg.trim() !== '') {
                      return themeWidgetBg;
                    }
                    // Priority 2: Check theme's color_tokens.background.surface (fallback used by CSS generator if widget_background is empty)
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        // Check background.surface (this is what CSS generator checks if widget_background is empty/null)
                        const surfaceBg = colorTokens?.background?.surface;
                        if (surfaceBg && typeof surfaceBg === 'string' && surfaceBg.trim() !== '') {
                          return surfaceBg as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme color_tokens:', e);
                      }
                    }
                    // Priority 3: Check page-level override (for display purposes, even though PHP doesn't use it for rendering)
                    if (page?.widget_background && typeof page.widget_background === 'string' && page.widget_background.trim() !== '') {
                      return page.widget_background;
                    }
                    // Priority 4: Check tokens from snapshot (semantic.surface.base is the frontend path)
                    const tokenBg = extractColorValue(tokens, 'semantic.surface.base');
                    if (tokenBg && tokenBg !== '#2563eb') {
                      return tokenBg;
                    }
                    // Fallback: #ffffff (same as PHP CSS generator fallback)
                    return '#ffffff';
                  }, [activeTheme?.widget_background, activeTheme?.color_tokens, page?.widget_background, tokens])}
                  widgetBorder={useMemo(() => {
                    // Priority 1: Check active theme's widget_border_color column (this is the ONLY source of truth per PHP)
                    // Note: Page-level widget_border_color override exists in snapshot but PHP only uses theme values for rendering
                    const themeBorderColor = activeTheme?.widget_border_color;
                    if (themeBorderColor && typeof themeBorderColor === 'string' && themeBorderColor.trim() !== '') {
                      return themeBorderColor;
                    }
                    // Priority 2: Check page-level override (for display purposes, even though PHP doesn't use it for rendering)
                    if (page?.widget_border_color && typeof page.widget_border_color === 'string' && page.widget_border_color.trim() !== '') {
                      return page.widget_border_color;
                    }
                    // Fallback: #e2e8f0 (same as PHP CSS generator fallback, line 406)
                    return '#e2e8f0';
                  }, [activeTheme?.widget_border_color, page?.widget_border_color])}
                  widgetHeadingText={useMemo(() => {
                    // Priority 1: Check active theme's typography_tokens.color.widget_heading (correct path for widget heading)
                    if (activeTheme?.typography_tokens) {
                      try {
                        const typographyTokens = typeof activeTheme.typography_tokens === 'string' 
                          ? JSON.parse(activeTheme.typography_tokens) 
                          : activeTheme.typography_tokens;
                        if (typographyTokens?.color?.widget_heading && typeof typographyTokens.color.widget_heading === 'string' && typographyTokens.color.widget_heading.trim() !== '') {
                          return typographyTokens.color.widget_heading as string;
                        }
                        // Fallback to page heading if widget heading not set
                        if (typographyTokens?.color?.heading && typeof typographyTokens.color.heading === 'string' && typographyTokens.color.heading.trim() !== '') {
                          return typographyTokens.color.heading as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme typography_tokens:', e);
                      }
                    }
                    // Priority 2: Fallback to color_tokens.text.primary
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.text?.primary && typeof colorTokens.text.primary === 'string' && colorTokens.text.primary.trim() !== '') {
                          return colorTokens.text.primary as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme color_tokens:', e);
                      }
                    }
                    // Priority 3: Check tokens from snapshot
                    return extractColorValue(tokens, 'semantic.text.primary');
                  }, [activeTheme?.typography_tokens, activeTheme?.color_tokens, tokens])}
                  widgetBodyText={useMemo(() => {
                    // Priority 1: Check active theme's typography_tokens.color.widget_body (correct path for widget body)
                    if (activeTheme?.typography_tokens) {
                      try {
                        const typographyTokens = typeof activeTheme.typography_tokens === 'string' 
                          ? JSON.parse(activeTheme.typography_tokens) 
                          : activeTheme.typography_tokens;
                        if (typographyTokens?.color?.widget_body && typeof typographyTokens.color.widget_body === 'string' && typographyTokens.color.widget_body.trim() !== '') {
                          return typographyTokens.color.widget_body as string;
                        }
                        // Fallback to page body if widget body not set
                        if (typographyTokens?.color?.body && typeof typographyTokens.color.body === 'string' && typographyTokens.color.body.trim() !== '') {
                          return typographyTokens.color.body as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme typography_tokens:', e);
                      }
                    }
                    // Priority 2: Fallback to color_tokens.text.secondary
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.text?.secondary && typeof colorTokens.text.secondary === 'string' && colorTokens.text.secondary.trim() !== '') {
                          return colorTokens.text.secondary as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme color_tokens:', e);
                      }
                    }
                    // Priority 3: Check tokens from snapshot
                    return extractColorValue(tokens, 'semantic.text.secondary');
                  }, [activeTheme?.typography_tokens, activeTheme?.color_tokens, tokens])}
                  widgetShadow={useMemo(() => {
                    // Check widget_styles to determine if it's glow or shadow
                    const widgetStyles = safeParse(activeTheme?.widget_styles);
                    const borderEffect = widgetStyles?.border_effect ?? 'shadow';
                    
                    // If glow effect, use glow_color from widget_styles
                    if (borderEffect === 'glow') {
                      if (widgetStyles?.glow_color && typeof widgetStyles.glow_color === 'string' && widgetStyles.glow_color.trim() !== '') {
                        return widgetStyles.glow_color as string;
                      }
                      // Fallback for glow
                      return '#ff00ff';
                    }
                    
                    // Otherwise it's shadow - use shape_tokens.shadow based on intensity
                    const shadowIntensity = widgetStyles?.border_shadow_intensity ?? 'subtle';
                    let shadowLevel = 'level_1';
                    if (shadowIntensity === 'pronounced') {
                      shadowLevel = 'level_2';
                    } else if (shadowIntensity === 'none') {
                      // No shadow - return a default fallback
                      return 'rgba(15, 23, 42, 0.06)';
                    }
                    
                    // Priority 1: Check theme's shape_tokens.shadow[level]
                    let shadowCssValue: string | null = null;
                    if (activeTheme?.shape_tokens) {
                      try {
                        const shapeTokens = typeof activeTheme.shape_tokens === 'string' 
                          ? JSON.parse(activeTheme.shape_tokens) 
                          : activeTheme.shape_tokens;
                        if (shapeTokens?.shadow?.[shadowLevel] && typeof shapeTokens.shadow[shadowLevel] === 'string' && shapeTokens.shadow[shadowLevel].trim() !== '') {
                          shadowCssValue = shapeTokens.shadow[shadowLevel] as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme shape_tokens:', e);
                      }
                    }
                    
                    // Priority 2: Check tokens from snapshot (shape.shadow[level])
                    if (!shadowCssValue) {
                      const shadowValue = extractColorValue(tokens, `shape.shadow.${shadowLevel}`);
                      if (shadowValue && shadowValue !== '#2563eb') {
                        shadowCssValue = shadowValue;
                      }
                    }
                    
                    // Extract color from CSS box-shadow value
                    if (shadowCssValue) {
                      return extractColorFromShadow(shadowCssValue);
                    }
                    
                    // Fallback: default shadow colors
                    return shadowLevel === 'level_2' 
                      ? 'rgba(15, 23, 42, 0.5)' 
                      : 'rgba(15, 23, 42, 0.06)';
                  }, [activeTheme?.widget_styles, activeTheme?.shape_tokens, tokens])}
                  socialIconColor={useMemo(() => {
                    // Priority 1: Check active theme's iconography_tokens.color (this maps to --icon-color)
                    let iconColor: string | null = null;
                    if (activeTheme?.iconography_tokens) {
                      try {
                        const iconographyTokens = typeof activeTheme.iconography_tokens === 'string' 
                          ? JSON.parse(activeTheme.iconography_tokens) 
                          : activeTheme.iconography_tokens;
                        if (iconographyTokens?.color && typeof iconographyTokens.color === 'string' && iconographyTokens.color.trim() !== '') {
                          iconColor = iconographyTokens.color as string;
                        }
                      } catch (e) {
                        console.warn('Failed to parse theme iconography_tokens:', e);
                      }
                    }
                    
                    // If we have --icon-color, use it
                    if (iconColor) {
                      return iconColor;
                    }
                    
                    // Priority 2: Compute --social-icon-color from page background and accentPrimary
                    // Get page background and accentPrimary for computation
                    let pageBg = null;
                    if (activeTheme?.page_background && typeof activeTheme.page_background === 'string' && activeTheme.page_background.trim() !== '') {
                      pageBg = activeTheme.page_background;
                    } else if (page?.page_background && typeof page.page_background === 'string' && page.page_background.trim() !== '') {
                      pageBg = page.page_background;
                    } else if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.gradient?.page) pageBg = colorTokens.gradient.page as string;
                        else if (colorTokens?.semantic?.surface?.canvas) pageBg = colorTokens.semantic.surface.canvas as string;
                        else if (colorTokens?.background?.base) pageBg = colorTokens.background.base as string;
                      } catch (e) {}
                    }
                    
                    let accentPrimary = null;
                    if (activeTheme?.color_tokens) {
                      try {
                        const colorTokens = typeof activeTheme.color_tokens === 'string' 
                          ? JSON.parse(activeTheme.color_tokens) 
                          : activeTheme.color_tokens;
                        if (colorTokens?.accent?.primary) accentPrimary = colorTokens.accent.primary as string;
                      } catch (e) {}
                    }
                    if (!accentPrimary) {
                      accentPrimary = extractColorValue(tokens, 'semantic.accent.primary');
                    }
                    
                    // Compute optimal color if we have background
                    if (pageBg && accentPrimary) {
                      return getOptimalTextColor(pageBg, accentPrimary);
                    }
                    
                    // Fallback to accentPrimary
                    return accentPrimary || '#2563eb';
                  }, [activeTheme?.iconography_tokens, activeTheme?.color_tokens, activeTheme?.page_background, page?.page_background, tokens])}
                />
              )}
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      </motion.div>
    </Tooltip.Provider>
  );
}

