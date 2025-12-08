import { useEffect, useMemo, useState, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useQueryClient } from '@tanstack/react-query';
import * as Tabs from '@radix-ui/react-tabs';
import { X, Check, XCircle, Palette, TextAa, GridFour, Shapes, FloppyDisk, Copy, Image, Swatches, Square, Sparkle, TextB, TextItalic, TextUnderline, AlignLeft, TextAlignCenter, AlignRight, Share } from '@phosphor-icons/react';
import profileStyles from './profile-inspector.module.css';

import { usePageSnapshot, updatePageThemeId } from '../../api/page';
import { useThemeLibraryQuery, useUpdateThemeMutation, useCreateThemeMutation, type ThemeLibraryResult, getOrCreateUserTheme } from '../../api/themes';
import { useTokens } from '../../design-system/theme/TokenProvider';
import { useThemeInspector } from '../../state/themeInspector';
import { queryKeys, normalizeImageUrl } from '../../api/utils';
import { uploadBackgroundImage } from '../../api/uploads';
import { PageBackgroundPicker } from '../controls/PageBackgroundPicker';
import { ColorPaletteEditor } from './ColorPaletteEditor';
import { TokenAccordion, type TokenAccordionItem } from '../system/TokenAccordion';
import { PageBackgroundSection } from './theme-editor/PageBackgroundSection';
import { BlockBackgroundSection } from './theme-editor/BlockBackgroundSection';
import { TypographySection } from './theme-editor/TypographySection';
import { SpacingSection } from './theme-editor/SpacingSection';
import { ShapeSection } from './theme-editor/ShapeSection';
import { previewRenderer } from './themes/utils/previewRenderer';
import type { TabColorTheme } from '../layout/tab-colors';
import type { ThemeRecord, PageSnapshotResponse } from '../../api/types';
import type { TokenBundle } from '../../design-system/tokens';

import styles from './theme-editor-panel.module.css';

interface ThemeEditorPanelProps {
  activeColor: TabColorTheme;
  theme?: ThemeRecord | null;
  onSave?: () => void;
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
  
  // If it's already a hex color, return it
  if (typeof resolved === 'string' && /^#([0-9a-fA-F]{3}){1,2}$/.test(resolved)) {
    return resolved;
  }
  
  // If it's a gradient, return it as-is
  if (typeof resolved === 'string' && (resolved.includes('gradient') || resolved.includes('linear-gradient') || resolved.includes('radial-gradient'))) {
    return resolved;
  }
  
  // If it's an image URL (starts with http:// or https:// or /), return it as-is
  if (typeof resolved === 'string' && (resolved.startsWith('http://') || resolved.startsWith('https://') || resolved.startsWith('/') || resolved.startsWith('data:'))) {
    return resolved;
  }
  
  // If it's a token reference, try to resolve it
  if (typeof resolved === 'string' && resolved.startsWith('color.')) {
    // Try to resolve the reference
    const refParts = resolved.split('.');
    let refCurrent: any = tokens;
    for (const part of refParts) {
      if (refCurrent && typeof refCurrent === 'object' && part in refCurrent) {
        refCurrent = refCurrent[part];
      } else {
        break;
      }
    }
    if (typeof refCurrent === 'string') {
      // Check if it's a hex color, gradient, or image URL
      if (/^#([0-9a-fA-F]{3}){1,2}$/.test(refCurrent) || refCurrent.includes('gradient') || refCurrent.startsWith('http://') || refCurrent.startsWith('https://') || refCurrent.startsWith('/') || refCurrent.startsWith('data:')) {
        return refCurrent;
      }
    }
  }
  
  // Fallback to defaults based on path
  if (path.includes('accent.primary')) return '#2563eb';
    if (path.includes('accent.secondary')) return '#3b82f6';
  if (path.includes('text.primary')) return '#0f172a';
  if (path.includes('text.secondary')) return '#64748b';
  if (path.includes('surface.canvas')) return '#ffffff';
  if (path.includes('surface.base')) return '#ffffff';
  
  return '#2563eb';
}

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

// Helper functions for color manipulation
function hexToRgb(hex: string): [number, number, number] | null {
  const normalized = hex.replace('#', '');
  if (normalized.length === 3) {
    const r = parseInt(normalized[0] + normalized[0], 16);
    const g = parseInt(normalized[1] + normalized[1], 16);
    const b = parseInt(normalized[2] + normalized[2], 16);
    return [r, g, b];
  }
  if (normalized.length === 6) {
    const r = parseInt(normalized.substring(0, 2), 16);
    const g = parseInt(normalized.substring(2, 4), 16);
    const b = parseInt(normalized.substring(4, 6), 16);
    return [r, g, b];
  }
  return null;
}

function rgbToHex(r: number, g: number, b: number): string {
  return `#${[r, g, b].map(x => {
    const hex = Math.round(x).toString(16);
    return hex.length === 1 ? '0' + hex : hex;
  }).join('')}`;
}

function lightenColor(hex: string, amount: number): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;
  const [r, g, b] = rgb;
  const newR = Math.min(255, r + (255 - r) * amount);
  const newG = Math.min(255, g + (255 - g) * amount);
  const newB = Math.min(255, b + (255 - b) * amount);
  return rgbToHex(newR, newG, newB);
}

function darkenColor(hex: string, amount: number): string {
  const rgb = hexToRgb(hex);
  if (!rgb) return hex;
  const [r, g, b] = rgb;
  const newR = Math.max(0, r * (1 - amount));
  const newG = Math.max(0, g * (1 - amount));
  const newB = Math.max(0, b * (1 - amount));
  return rgbToHex(newR, newG, newB);
}

function isGradient(value: string): boolean {
  return typeof value === 'string' && (value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient'));
}

function isImageUrl(value: string): boolean {
  return typeof value === 'string' && (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/') || value.startsWith('data:'));
}


export function ThemeEditorPanel({ activeColor, theme, onSave }: ThemeEditorPanelProps): JSX.Element {
  // DEBUG: Log component initialization - v3 (widgetBorderWidth removed, buttonRadius2 fixed)
  console.log('[ThemeEditorPanel] ✅ Component initializing v3', { 
    themeId: theme?.id, 
    themeName: theme?.name,
    hasActiveColor: !!activeColor 
  });
  
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const queryClient = useQueryClient();
  const { tokens, setTokens } = useTokens();
  const { setThemeInspectorVisible } = useThemeInspector();
  const updateMutation = useUpdateThemeMutation();
  const createMutation = useCreateThemeMutation();
  
  const [themeName, setThemeName] = useState(theme?.name ?? '');
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [hasChanges, setHasChanges] = useState(false);
  
  // Color values from tokens
  const primaryColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.primary'),
    [tokens]
  );
  const secondaryColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.secondary'),
    [tokens]
  );
  const accentColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.primary'),
    [tokens]
  );
  const pageBackground = useMemo(() => 
    extractColorValue(tokens, 'semantic.surface.canvas'),
    [tokens]
  );
  const blockBackground = useMemo(() => 
    extractColorValue(tokens, 'semantic.surface.base'),
    [tokens]
  );
  const textPrimary = useMemo(() => 
    extractColorValue(tokens, 'semantic.text.primary'),
    [tokens]
  );
  const textSecondary = useMemo(() => 
    extractColorValue(tokens, 'semantic.text.secondary'),
    [tokens]
  );
  
  // Typography
  const headingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);
  
  const bodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);
  
  // Typography colors
  const headingColor = useMemo(() => {
    const typography = tokens.core?.typography as any;
    const color = typography?.color?.heading;
    if (typeof color === 'string') return color;
    // Fallback to semantic text primary if no override
    return extractColorValue(tokens, 'semantic.text.primary');
  }, [tokens]);
  
  const bodyColor = useMemo(() => {
    const typography = tokens.core?.typography as any;
    const color = typography?.color?.body;
    if (typeof color === 'string') return color;
    // Fallback to semantic text primary if no override
    return extractColorValue(tokens, 'semantic.text.primary');
  }, [tokens]);
  
  // Page typography font size presets
  const [headingFontSizePreset, setHeadingFontSizePreset] = useState<'small' | 'medium' | 'large' | 'xlarge'>('medium');
  const [bodyFontSizePreset, setBodyFontSizePreset] = useState<'small' | 'medium' | 'large' | 'xlarge'>('medium');
  
  // Widget typography font size presets (separate from page typography)
  const [widgetHeadingFontSizePreset, setWidgetHeadingFontSizePreset] = useState<'small' | 'medium' | 'large' | 'xlarge'>('medium');
  const [widgetBodyFontSizePreset, setWidgetBodyFontSizePreset] = useState<'small' | 'medium' | 'large' | 'xlarge'>('medium');
  
  // Typography formatting
  const [headingBold, setHeadingBold] = useState(false);
  const [headingItalic, setHeadingItalic] = useState(false);
  const [headingUnderline, setHeadingUnderline] = useState(false);
  const [headingAlignment, setHeadingAlignment] = useState<'left' | 'center' | 'right'>('center');
  
  const [bodyBold, setBodyBold] = useState(false);
  const [bodyItalic, setBodyItalic] = useState(false);
  const [bodyUnderline, setBodyUnderline] = useState(false);
  const [bodyAlignment, setBodyAlignment] = useState<'left' | 'center' | 'right'>('center');
  
  // Color mode for typography colors (solid or gradient)
  const [colorMode, setColorMode] = useState<'solid' | 'gradient'>('solid');
  
  // Widget typography colors (separate from page typography)
  const widgetHeadingColor = useMemo(() => {
    const typography = tokens.core?.typography as any;
    const color = typography?.color?.widget_heading;
    if (typeof color === 'string') return color;
    // Fallback to page heading color if not set
    return headingColor;
  }, [tokens, headingColor]);
  
  const widgetBodyColor = useMemo(() => {
    const typography = tokens.core?.typography as any;
    const color = typography?.color?.widget_body;
    if (typeof color === 'string') return color;
    // Fallback to page body color if not set
    return bodyColor;
  }, [tokens, bodyColor]);
  
  // Widget color mode (separate from page color mode)
  const [widgetColorMode, setWidgetColorMode] = useState<'solid' | 'gradient'>('solid');
  
  // Iconography
  const [iconSize, setIconSize] = useState<'small' | 'medium' | 'large'>('medium');
  const [iconColor, setIconColor] = useState<string>('#6b7280');
  const [iconSpacing, setIconSpacing] = useState<number>(0.75); // rem units
  
  // Spacing density
  const [spacingDensity, setSpacingDensity] = useState<'compact' | 'cozy' | 'comfortable'>('cozy');
  
  // Background
  const [backgroundType, setBackgroundType] = useState<'solid' | 'gradient' | 'image'>('solid');
  const [backgroundType2, setBackgroundType2] = useState<'solid' | 'gradient' | 'image'>('solid');
  
  // Background images
  const [pageBackgroundImage, setPageBackgroundImage] = useState<string | null>(null);
  const [blockBackgroundImage, setBlockBackgroundImage] = useState<string | null>(null);
  
  // Shape & Effects
  const [buttonRadius, setButtonRadius] = useState<'square' | 'rounded' | 'pill'>('rounded');
  const [borderEffect, setBorderEffect] = useState<'shadow' | 'glow'>('shadow');
  const [shadowIntensity, setShadowIntensity] = useState<'none' | 'subtle' | 'pronounced'>('subtle');
  const [glowIntensity, setGlowIntensity] = useState<'subtle' | 'pronounced'>('subtle');
  const [glowColor, setGlowColor] = useState<string>('#ff00ff');
  
  // Shape & Effects 2 (for Block Background)
  const [buttonRadius2, setButtonRadius2] = useState<'square' | 'rounded' | 'pill'>('rounded');
  const [borderEffect2, setBorderEffect2] = useState<'shadow' | 'glow'>('shadow');
  const [shadowIntensity2, setShadowIntensity2] = useState<'none' | 'subtle' | 'pronounced'>('subtle');
  const [glowIntensity2, setGlowIntensity2] = useState<'subtle' | 'pronounced'>('subtle');
  const [glowColor2, setGlowColor2] = useState<string>('#ff00ff');
  
  // Track last processed theme ID to avoid re-processing the same theme
  const lastProcessedThemeId = useRef<number | null>(null);
  
  useEffect(() => {
    // Guard against missing tokens
    if (!tokens) {
      return;
    }
    
    if (theme) {
      setThemeName(theme.name);
      
      // Start with snapshot tokens if available (includes token_overrides)
      let updatedTokens = snapshot?.tokens ? { ...snapshot.tokens } : { ...tokens };
      
      // Load theme token values and apply to tokens
      const colorTokens = safeParse(theme.color_tokens);
      const typographyTokens = safeParse(theme.typography_tokens);
      const spacingTokens = safeParse(theme.spacing_tokens);
      const shapeTokens = safeParse(theme.shape_tokens);
      const iconographyTokens = safeParse((theme as any).iconography_tokens);
      
      // Initialize iconography state
      if (iconographyTokens) {
        if (iconographyTokens.size) {
          const sizeValue = iconographyTokens.size;
          if (sizeValue === '36px' || sizeValue === 36) {
            setIconSize('small');
          } else if (sizeValue === '48px' || sizeValue === 48) {
            setIconSize('medium');
          } else if (sizeValue === '60px' || sizeValue === 60) {
            setIconSize('large');
          }
        }
        if (iconographyTokens.color && typeof iconographyTokens.color === 'string') {
          setIconColor(iconographyTokens.color);
        }
        if (iconographyTokens.spacing) {
          // Extract numeric value from spacing (e.g., "0.75rem" -> 0.75)
          const spacingValue = parseFloat(String(iconographyTokens.spacing));
          if (!isNaN(spacingValue)) {
            setIconSpacing(spacingValue);
          }
        }
      }
      
      // Apply color tokens - map backend structure to semantic tokens
      if (colorTokens) {
        const tokens = colorTokens as any;
        // Map background tokens
        if (tokens.background) {
          if (tokens.background.base && typeof tokens.background.base === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.surface.canvas', tokens.background.base);
          }
          // CRITICAL: Only set semantic.surface.base from colorTokens if theme.widget_background doesn't exist
          // theme.widget_background is the source of truth and will be set later
          if (tokens.background.surface && typeof tokens.background.surface === 'string' && !theme.widget_background) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.surface.base', tokens.background.surface);
          }
        }
        
        // Map text tokens
        if (tokens.text) {
          if (tokens.text.primary && typeof tokens.text.primary === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.text.primary', tokens.text.primary);
          }
          if (tokens.text.secondary && typeof tokens.text.secondary === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.text.secondary', tokens.text.secondary);
          }
        }
        
        // Map accent tokens
        if (tokens.accent) {
          if (tokens.accent.primary && typeof tokens.accent.primary === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.accent.primary', tokens.accent.primary);
          }
          if (tokens.accent.secondary && typeof tokens.accent.secondary === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.accent.secondary', tokens.accent.secondary);
          }
        }
      }
      
      // Also check database columns for backward compatibility
      if (theme.page_primary_font) {
        updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', theme.page_primary_font);
      }
      if (theme.page_secondary_font) {
        updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', theme.page_secondary_font);
      }
      
      // Apply typography tokens
      if (typographyTokens && typeof typographyTokens === 'object') {
        const fonts = (typographyTokens as any).font;
        if (fonts) {
          if (fonts.heading && typeof fonts.heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', fonts.heading);
          }
          if (fonts.body && typeof fonts.body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', fonts.body);
          }
        }
        
        // Apply typography colors if present
        const colors = (typographyTokens as any).color;
        if (colors) {
          if (colors.heading && typeof colors.heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.heading', colors.heading);
          }
          if (colors.body && typeof colors.body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.body', colors.body);
          }
          // Widget typography colors (separate from page typography)
          if (colors.widget_heading && typeof colors.widget_heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.widget_heading', colors.widget_heading);
          }
          if (colors.widget_body && typeof colors.widget_body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.widget_body', colors.widget_body);
          }
        }
        
        // Load font size preset from scale values
        // NOTE: Scale is shared between page and widgets, so we load it for both
        // Both page and widget presets are initialized from the same scale
        const scale = (typographyTokens as any).scale;
        if (scale) {
          // Detect heading preset from xl scale (page title uses xl)
          // Check xl first as it's the primary heading scale
          let detectedHeadingPreset: 'small' | 'medium' | 'large' | 'xlarge' = 'medium';
          if (scale.xl === 1.5 || (scale.xl >= 1.4 && scale.xl < 1.8)) {
            detectedHeadingPreset = 'small';
          } else if (scale.xl === 2.0 || (scale.xl >= 1.8 && scale.xl < 2.3)) {
            detectedHeadingPreset = 'medium';
          } else if (scale.xl === 2.488 || (scale.xl >= 2.3 && scale.xl < 2.7)) {
            detectedHeadingPreset = 'large';
          } else if (scale.xl === 3.0 || scale.xl >= 2.7) {
            detectedHeadingPreset = 'xlarge';
          } else {
            // Fallback to md scale if xl not available
            if (scale.md === 1.1) {
              detectedHeadingPreset = 'small';
            } else if (scale.md === 1.333) {
              detectedHeadingPreset = 'medium';
            } else if (scale.md === 1.5) {
              detectedHeadingPreset = 'large';
            } else if (scale.md === 1.777) {
              detectedHeadingPreset = 'xlarge';
            }
          }
          setHeadingFontSizePreset(detectedHeadingPreset);
          setWidgetHeadingFontSizePreset(detectedHeadingPreset); // Initialize widget preset from scale
          
          // Detect body preset from sm scale (body uses sm)
          let detectedBodyPreset: 'small' | 'medium' | 'large' | 'xlarge' = 'medium';
          if (scale.sm === 0.9 || (scale.sm >= 0.85 && scale.sm < 1.0)) {
            detectedBodyPreset = 'small';
          } else if (scale.sm === 1.111 || (scale.sm >= 1.0 && scale.sm < 1.2)) {
            detectedBodyPreset = 'medium';
          } else if (scale.sm === 1.25 || (scale.sm >= 1.2 && scale.sm < 1.4)) {
            detectedBodyPreset = 'large';
          } else if (scale.sm === 1.5 || scale.sm >= 1.4) {
            detectedBodyPreset = 'xlarge';
          }
          setBodyFontSizePreset(detectedBodyPreset);
          setWidgetBodyFontSizePreset(detectedBodyPreset); // Initialize widget preset from scale
        }
      }
      
      // Also check token_overrides from snapshot for typography
      if (snapshot?.token_overrides) {
        const overrides = snapshot.token_overrides as any;
        // Check for typography overrides in core.typography structure
        if (overrides.core?.typography) {
          const typoOverrides = overrides.core.typography;
          if (typoOverrides.font?.heading) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', typoOverrides.font.heading);
          }
          if (typoOverrides.font?.body) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', typoOverrides.font.body);
          }
          if (typoOverrides.color?.heading) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.heading', typoOverrides.color.heading);
          }
          if (typoOverrides.color?.body) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.body', typoOverrides.color.body);
          }
        }
        // Also check for backend structure (typography_tokens)
        if ((overrides as any).typography) {
          const typoOverrides = (overrides as any).typography;
          if (typoOverrides.font?.heading) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', typoOverrides.font.heading);
          }
          if (typoOverrides.font?.body) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', typoOverrides.font.body);
          }
          if (typoOverrides.color?.heading) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.heading', typoOverrides.color.heading);
          }
          if (typoOverrides.color?.body) {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.color.body', typoOverrides.color.body);
          }
        }
      }
      
      // Apply spacing tokens
      if (spacingTokens && typeof spacingTokens === 'object') {
        const density = (spacingTokens as any).density;
        if (density && ['compact', 'cozy', 'comfortable'].includes(density)) {
          setSpacingDensity(density as 'compact' | 'cozy' | 'comfortable');
        }
      }
      
      // Apply shape tokens - map from new structure (corner.*, border_width.*, shadow.level_*)
      if (shapeTokens && typeof shapeTokens === 'object') {
        // Map button_corner tokens to buttonRadius (page-level buttons)
        const buttonCorner = (shapeTokens as any).button_corner;
        if (buttonCorner) {
          // Find the active button corner value
          const buttonCornerValues = Object.values(buttonCorner) as string[];
          if (buttonCornerValues.length > 0) {
            const activeButtonCornerValue = buttonCornerValues[0];
            if (activeButtonCornerValue === '0px' || activeButtonCornerValue === '0') {
              setButtonRadius('square');
            } else if (activeButtonCornerValue === '9999px') {
              setButtonRadius('pill');
            } else {
              // Any other value (0.375rem, 0.75rem, 1.5rem, etc.) = rounded
              setButtonRadius('rounded');
            }
          }
        }
        
        // Fallback: Map corner tokens to buttonRadius if button_corner doesn't exist
        const corner = (shapeTokens as any).corner;
        if (!buttonCorner && corner) {
          // Find the active corner value
          const cornerValues = Object.values(corner) as string[];
          if (cornerValues.length > 0) {
            const activeCornerValue = cornerValues[0];
            if (activeCornerValue === '0px' || activeCornerValue === '0') {
              setButtonRadius('square');
            } else if (activeCornerValue === '9999px') {
              setButtonRadius('pill');
            } else {
              // Any other value (0.375rem, 0.75rem, 1.5rem, etc.) = rounded
              setButtonRadius('rounded');
            }
          }
        }
        
        // Fallback to old structure for backward compatibility
        const buttonRadiusOld = (shapeTokens as any).button_radius;
        if (buttonRadiusOld && !buttonCorner && !corner) {
          if (buttonRadiusOld === '0' || buttonRadiusOld === '0px') setButtonRadius('square');
          else if (buttonRadiusOld === '9999px') setButtonRadius('pill');
          else setButtonRadius('rounded');
        }
        
        // Border width and shadow level are no longer configurable for page-level
        // They are only configurable for block widgets via border_effect
        
        // CRITICAL: Also load block widget settings (buttonRadius2)
        // These use the SAME shape_tokens but are for block widgets
        // For block widgets, we need to find which corner is set (legacy: square, rounded, pill)
        if (corner) {
          // Find the active corner value for block widgets
          const cornerValues = Object.values(corner) as string[];
          if (cornerValues.length > 0) {
            const activeCornerValue = cornerValues[0];
            if (activeCornerValue === '0px' || activeCornerValue === '0') {
              setButtonRadius2('square');
            } else if (activeCornerValue === '9999px') {
              setButtonRadius2('pill');
            } else {
              // Any other value (0.375rem, 0.75rem, 1.5rem, etc.) = rounded
              setButtonRadius2('rounded');
            }
          }
        }
      }
      
      // Load widget_styles for border effects (shadow/glow)
      const widgetStyles = safeParse(theme?.widget_styles);
      if (widgetStyles) {
        console.log('[ThemeEditorPanel] Loading widget_styles:', widgetStyles);
        // Load border effect settings for block widgets
        if (widgetStyles.border_effect && ['shadow', 'glow'].includes(widgetStyles.border_effect as string)) {
          console.log('[ThemeEditorPanel] Setting borderEffect2 to:', widgetStyles.border_effect);
          setBorderEffect2(widgetStyles.border_effect as 'shadow' | 'glow');
        }
        if (widgetStyles.border_shadow_intensity && ['none', 'subtle', 'pronounced'].includes(widgetStyles.border_shadow_intensity as string)) {
          setShadowIntensity2(widgetStyles.border_shadow_intensity as 'none' | 'subtle' | 'pronounced');
        }
        if (widgetStyles.border_glow_intensity && ['subtle', 'pronounced'].includes(widgetStyles.border_glow_intensity as string)) {
          console.log('[ThemeEditorPanel] Setting glowIntensity2 to:', widgetStyles.border_glow_intensity);
          setGlowIntensity2(widgetStyles.border_glow_intensity as 'subtle' | 'pronounced');
        } else if (widgetStyles.border_glow_intensity === 'none') {
          // Migrate old 'none' values to 'subtle'
          setGlowIntensity2('subtle');
        }
        if (widgetStyles.glow_color && typeof widgetStyles.glow_color === 'string') {
          console.log('[ThemeEditorPanel] Setting glowColor2 to:', widgetStyles.glow_color);
          setGlowColor2(widgetStyles.glow_color);
        }
      }
      
      // Load motion tokens (store for future use, no UI controls yet)
      const motionTokens = safeParse(theme.motion_tokens);
      // Motion tokens are loaded but not displayed in UI yet
      
      setTokens(updatedTokens);
      
      // Load background images from page_background if it's a URL
      // Also check color_tokens.background.base and gradient.page
      let pageBgValue: string | null = null;
      if (theme.page_background && typeof theme.page_background === 'string') {
        pageBgValue = theme.page_background;
      } else if (colorTokens) {
        const tokens = colorTokens as any;
        if (tokens.background?.base && typeof tokens.background.base === 'string') {
          pageBgValue = tokens.background.base;
        } else if (tokens.gradient?.page && typeof tokens.gradient.page === 'string') {
          pageBgValue = tokens.gradient.page;
        }
      }
      
      if (pageBgValue) {
        // Check if it's an image URL (not a color or gradient)
        if ((pageBgValue.startsWith('http://') || pageBgValue.startsWith('https://') || pageBgValue.startsWith('/') || pageBgValue.startsWith('data:')) && !isGradient(pageBgValue)) {
          setPageBackgroundImage(pageBgValue);
        } else if (isGradient(pageBgValue)) {
          // If it's a gradient, store it but don't set as image
          setPageBackgroundImage(null);
        } else {
          setPageBackgroundImage(null);
        }
      } else {
        setPageBackgroundImage(null);
      }
      
      // Load block background image if available
      // Check widget_background column, color_tokens.background.surface, and gradient.widget
      // CRITICAL: If theme.widget_background exists, it takes priority and must update tokens
      let blockBgValue: string | null = null;
      if (theme.widget_background && typeof theme.widget_background === 'string') {
        blockBgValue = theme.widget_background;
        // CRITICAL: Update tokens so blockBackground (from semantic.surface.base) matches the saved value
        updatedTokens = applyTokenUpdate(updatedTokens, 'semantic.surface.base', blockBgValue);
      } else if (colorTokens) {
        const tokens = colorTokens as any;
        if (tokens.background?.surface && typeof tokens.background.surface === 'string') {
          blockBgValue = tokens.background.surface;
        } else if (tokens.gradient?.widget && typeof tokens.gradient.widget === 'string') {
          blockBgValue = tokens.gradient.widget;
        }
      } else {
        const resolvedBlockBg = resolveToken(updatedTokens, 'semantic.surface.base');
        if (typeof resolvedBlockBg === 'string') {
          blockBgValue = resolvedBlockBg;
        }
      }
      
      if (blockBgValue) {
        if ((blockBgValue.startsWith('http://') || blockBgValue.startsWith('https://') || blockBgValue.startsWith('/') || blockBgValue.startsWith('data:')) && !isGradient(blockBgValue)) {
          setBlockBackgroundImage(blockBgValue);
        } else if (isGradient(blockBgValue)) {
          setBlockBackgroundImage(null);
        } else {
          setBlockBackgroundImage(null);
        }
      } else {
        setBlockBackgroundImage(null);
      }
      
      // Set background type based on what we found
      if (pageBgValue && isImageUrl(pageBgValue)) {
        setBackgroundType('image');
      } else if (pageBgValue && isGradient(pageBgValue)) {
        setBackgroundType('gradient');
      } else {
        setBackgroundType('solid');
      }
      
      if (blockBgValue && isGradient(blockBgValue)) {
        setBackgroundType2('gradient');
      } else if (blockBgValue && isImageUrl(blockBgValue)) {
        setBackgroundType2('image');
      } else {
        setBackgroundType2('solid');
      }
      
      setHasChanges(false);
    } else {
      // Reset to defaults when no theme
      lastProcessedThemeId.current = null;
      setThemeName('');
      setPageBackgroundImage(null);
      setBlockBackgroundImage(null);
      setHasChanges(false);
    }
  }, [theme?.id, setTokens]); // Only depend on theme ID to avoid infinite loops
  
  useEffect(() => {
    if (saveStatus === 'success' || saveStatus === 'error') {
      const timer = setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [saveStatus]);
  
  const handleColorChange = (tokenPath: string, value: string) => {
    setTokens(applyTokenUpdate(tokens, tokenPath, value));
    setHasChanges(true);
  };
  
  const handleFontChange = (tokenPath: string, value: string) => {
    setTokens(applyTokenUpdate(tokens, tokenPath, value));
    setHasChanges(true);
  };
  
  const handleHeadingPresetChange = (preset: 'small' | 'medium' | 'large' | 'xlarge') => {
    setHeadingFontSizePreset(preset);
    setHasChanges(true);
  };
  
  const handleBodyPresetChange = (preset: 'small' | 'medium' | 'large' | 'xlarge') => {
    setBodyFontSizePreset(preset);
    setHasChanges(true);
  };
  
  const handleDensityChange = (density: 'compact' | 'cozy' | 'comfortable') => {
    setSpacingDensity(density);
    setHasChanges(true);
  };
  
  
  const handleShapeChange = (
    type: 'buttonRadius',
    value: 'square' | 'rounded' | 'pill'
  ) => {
    if (type === 'buttonRadius') setButtonRadius(value);
    setHasChanges(true);
  };
  
  const handleShapeChange2 = (
    type: 'buttonRadius',
    value: 'square' | 'rounded' | 'pill'
  ) => {
    if (type === 'buttonRadius') setButtonRadius2(value);
    setHasChanges(true);
  };
  
  const handleBackgroundImageUpload = async (file: File, type: 'page' | 'block') => {
    try {
      const result = await uploadBackgroundImage(file);
      if (result.url) {
        if (type === 'page') {
          setPageBackgroundImage(result.url);
          handleColorChange('semantic.surface.canvas', result.url);
        } else {
          setBlockBackgroundImage(result.url);
          handleColorChange('semantic.surface.base', result.url);
        }
        setHasChanges(true);
      }
    } catch (error) {
      console.error('Failed to upload background image', error);
      setStatusMessage(error instanceof Error ? error.message : 'Failed to upload image');
      setSaveStatus('error');
    }
  };
  
  const handleBackgroundImageUrlChange = (url: string, type: 'page' | 'block') => {
    if (type === 'page') {
      setPageBackgroundImage(url);
      handleColorChange('semantic.surface.canvas', url);
    } else {
      setBlockBackgroundImage(url);
      handleColorChange('semantic.surface.base', url);
    }
    setHasChanges(true);
  };
  
  const handleBackgroundImageRemove = (type: 'page' | 'block') => {
    if (type === 'page') {
      setPageBackgroundImage(null);
      handleColorChange('semantic.surface.canvas', '#ffffff');
    } else {
      setBlockBackgroundImage(null);
      handleColorChange('semantic.surface.base', '#ffffff');
    }
    setHasChanges(true);
  };
  
  const handleSave = async () => {
    try {
      setSaveStatus('saving');
      
      // Load existing tokens to preserve values not edited in UI
      const existingColorTokens = safeParse(theme?.color_tokens);
      const existingTypographyTokens = safeParse(theme?.typography_tokens);
      const existingSpacingTokens = safeParse(theme?.spacing_tokens);
      const existingShapeTokens = safeParse(theme?.shape_tokens);
      const existingMotionTokens = safeParse(theme?.motion_tokens);
      
      // Extract current values
      const accentPrimary = extractColorValue(tokens, 'semantic.accent.primary');
      const accentSecondary = extractColorValue(tokens, 'semantic.accent.secondary');
      const textPrimary = extractColorValue(tokens, 'semantic.text.primary');
      const textSecondary = extractColorValue(tokens, 'semantic.text.secondary');
      const backgroundBase = extractColorValue(tokens, 'semantic.surface.canvas');
      const backgroundSurface = extractColorValue(tokens, 'semantic.surface.base');
      
      // Determine widget_background value FIRST (before building colorTokens)
      // CRITICAL: Only use saved theme.widget_background if it's NOT the fallback white value
      // If saved value is #ffffff (fallback), ignore it and use current state
      // This prevents #ffffff from persisting when user sets a new background
      const savedWidgetBackground = theme?.widget_background;
      const isSavedValueFallback = savedWidgetBackground === '#ffffff' || savedWidgetBackground === '#fff' || savedWidgetBackground === 'white';
      const finalWidgetBackground = (!isSavedValueFallback && savedWidgetBackground) 
        ? savedWidgetBackground 
        : (blockBackgroundImage || blockBackground);
      // CRITICAL: Ensure colorTokens.background.surface matches widget_background
      const finalBackgroundSurface = finalWidgetBackground;
      const isBlockGradient = isGradient(finalWidgetBackground);
      const isBlockImage = isImageUrl(finalWidgetBackground);
      
      // Determine page_background value - always use backgroundBase (which comes from semantic.surface.canvas token)
      // This ensures gradients, images, and solid colors set via PageBackgroundPicker are all saved correctly
      // pageBackgroundImage is only used for uploaded images, but backgroundBase already contains the value
      const finalPageBackground = pageBackgroundImage || backgroundBase;
      const isPageGradient = isGradient(finalPageBackground);
      const isPageImage = isImageUrl(finalPageBackground);
      
      // Derive additional color values
      const existingColors = existingColorTokens as any;
      const accentMuted = existingColors?.accent?.muted || (accentPrimary && /^#/.test(accentPrimary) ? lightenColor(accentPrimary, 0.75) : '#e0edff');
      const textInverse = existingColors?.text?.inverse || '#ffffff';
      const borderDefault = existingColors?.border?.default || (textPrimary && /^#/.test(textPrimary) ? darkenColor(textPrimary, 0.2) : '#d1d5db');
      const borderFocus = existingColors?.border?.focus || (accentPrimary && /^#/.test(accentPrimary) ? lightenColor(accentPrimary, 0.25) : '#2563eb');
      const backgroundSurfaceRaised = existingColors?.background?.surface_raised || (backgroundSurface && /^#/.test(backgroundSurface) ? lightenColor(backgroundSurface, 0.22) : '#f9fafb');
      const backgroundOverlay = existingColors?.background?.overlay || 'rgba(15, 23, 42, 0.6)';
      
      // Build complete color tokens, preserving existing values
      const colorTokens: Record<string, any> = {
        ...(existingColorTokens || {}),
        accent: {
          ...(existingColors?.accent || {}),
          primary: accentPrimary,
          secondary: accentSecondary,
          muted: accentMuted
        },
        text: {
          ...(existingColors?.text || {}),
          primary: textPrimary,
          secondary: textSecondary,
          inverse: textInverse
        },
        background: {
          ...(existingColors?.background || {}),
          base: backgroundBase,
          surface: finalBackgroundSurface, // CRITICAL: Use finalWidgetBackground to keep in sync with widget_background
          surface_raised: backgroundSurfaceRaised,
          overlay: backgroundOverlay
        },
        border: {
          ...(existingColors?.border || {}),
          default: borderDefault,
          focus: borderFocus
        },
        state: {
          ...(existingColors?.state || {}),
          success: existingColors?.state?.success || '#12b76a',
          warning: existingColors?.state?.warning || '#f59e0b',
          danger: existingColors?.state?.danger || '#ef4444'
        },
        text_state: {
          ...(existingColors?.text_state || {}),
          success: existingColors?.text_state?.success || '#0f5132',
          warning: existingColors?.text_state?.warning || '#7c2d12',
          danger: existingColors?.text_state?.danger || '#7f1d1d'
        },
        shadow: {
          ...(existingColors?.shadow || {}),
          ambient: existingColors?.shadow?.ambient || 'rgba(15, 23, 42, 0.12)',
          focus: existingColors?.shadow?.focus || 'rgba(37, 99, 235, 0.35)'
        },
        gradient: {
          ...(existingColors?.gradient || {}),
          page: isPageGradient ? finalPageBackground : (existingColors?.gradient?.page || null),
          accent: existingColors?.gradient?.accent || null,
          widget: isBlockGradient ? finalWidgetBackground : (existingColors?.gradient?.widget || null),
          podcast: existingColors?.gradient?.podcast || null
        },
        glow: {
          ...(existingColors?.glow || {}),
          primary: existingColors?.glow?.primary || null
        }
      };
      
      // Build iconography tokens
      const existingIconographyTokens = safeParse(theme?.iconography_tokens);
      const iconographyTokens: Record<string, any> = {
        ...(existingIconographyTokens || {}),
        size: iconSize === 'small' ? '36px' : iconSize === 'medium' ? '48px' : '60px',
        color: iconColor,
        spacing: `${iconSpacing}rem`
      };
      
      // DEBUG: Log iconography tokens being saved
      console.log('SAVING ICONOGRAPHY DEBUG:', {
        iconSize,
        iconColor,
        iconSpacing,
        iconographyTokens
      });
      
      // Build typography tokens, preserving existing values
      const existingTypography = existingTypographyTokens as any;
      const typographyTokens: Record<string, any> = {
        ...(existingTypographyTokens || {}),
        font: {
          ...(existingTypography?.font || {}),
          heading: headingFont,
          body: bodyFont,
          metatext: existingTypography?.font?.metatext || bodyFont
        },
        color: {
          ...(existingTypography?.color || {}),
          heading: headingColor !== extractColorValue(tokens, 'semantic.text.primary') ? headingColor : undefined,
          body: bodyColor !== extractColorValue(tokens, 'semantic.text.primary') ? bodyColor : undefined,
          // Widget typography colors (separate from page typography)
          widget_heading: widgetHeadingColor !== headingColor ? widgetHeadingColor : undefined,
          widget_body: widgetBodyColor !== bodyColor ? widgetBodyColor : undefined
        },
        scale: {
          ...(existingTypography?.scale || {}),
          // Map heading preset to xl (page title), lg, and md scale values
          xl: headingFontSizePreset === 'small' ? 1.5 :
              headingFontSizePreset === 'medium' ? 2.0 :
              headingFontSizePreset === 'large' ? 2.488 : 3.0,
          lg: headingFontSizePreset === 'small' ? 1.2 :
              headingFontSizePreset === 'medium' ? 1.5 :
              headingFontSizePreset === 'large' ? 1.777 : 2.2,
          md: headingFontSizePreset === 'small' ? 1.1 :
              headingFontSizePreset === 'medium' ? 1.333 :
              headingFontSizePreset === 'large' ? 1.5 : 1.777,
          // Map body preset to sm (body text) scale value
          sm: bodyFontSizePreset === 'small' ? 0.9 :
              bodyFontSizePreset === 'medium' ? 1.111 :
              bodyFontSizePreset === 'large' ? 1.25 : 1.5,
          xs: existingTypography?.scale?.xs || 0.889
        },
        line_height: {
          ...(existingTypography?.line_height || {}),
          tight: existingTypography?.line_height?.tight || 1.2,
          normal: existingTypography?.line_height?.normal || 1.5,
          relaxed: existingTypography?.line_height?.relaxed || 1.7
        },
        weight: {
          ...(existingTypography?.weight || {}),
          normal: existingTypography?.weight?.normal || 400,
          medium: existingTypography?.weight?.medium || 500,
          bold: existingTypography?.weight?.bold || 600
        }
      };
      
      // Build spacing tokens, preserving existing values
      const spacingTokens: Record<string, any> = {
        ...(existingSpacingTokens || {}),
        density: spacingDensity, // Page spacing density (also used for widgets)
        base_scale: existingSpacingTokens?.base_scale || {
          '2xs': 0.25,
          'xs': 0.5,
          'sm': 0.75,
          'md': 1.0,
          'lg': 1.5,
          'xl': 2.0,
          '2xl': 3.0
        },
        density_multipliers: existingSpacingTokens?.density_multipliers || {
          compact: {
            '2xs': 0.6,
            'xs': 0.65,
            'sm': 0.7,
            'md': 0.75,
            'lg': 0.8,
            'xl': 0.85,
            '2xl': 0.9
          },
          cozy: {
            '2xs': 0.85,
            'xs': 0.9,
            'sm': 0.95,
            'md': 1.0,
            'lg': 1.0,
            'xl': 1.05,
            '2xl': 1.1
          },
          comfortable: {
            '2xs': 1.0,
            'xs': 1.1,
            'sm': 1.2,
            'md': 1.5,
            'lg': 1.75,
            'xl': 2.0,
            '2xl': 2.25
          }
        },
        modifiers: existingSpacingTokens?.modifiers || []
      };
      
      // Map buttonRadius to shape_tokens.corner.*
      // Map buttonRadius2 to shape_tokens.corner.* (legacy: square, rounded, pill)
      const cornerMap: Record<string, string> = {
        'square': 'none',
        'rounded': 'md',
        'pill': 'pill'
      };
      
      // Map shadowLevel to shape_tokens.shadow.level_*
      const shadowMap: Record<string, string | null> = {
        'none': null, // Omit if none
        'subtle': 'level_1',
        'pronounced': 'level_2'
      };
      
      // Build shape tokens with correct structure
      // CRITICAL: Use buttonRadius2 for BLOCK WIDGETS
      // These are the block widget settings from the "Block / widget style" tab
      // CRITICAL: Clear all other corner values to prevent cross-contamination
      // Only set the one value that matches buttonRadius2
      const cornerKey = cornerMap[buttonRadius2];
      const cornerValue = buttonRadius2 === 'square' ? '0px' :
                         buttonRadius2 === 'rounded' ? '0.75rem' :
                         '9999px'; // pill

      // Build a clean corner object with only the active value
      const cleanCorner: Record<string, string> = {};
      cleanCorner[cornerKey] = cornerValue;

      // Also save buttonRadius (page-level) for buttons
      // Use a separate key to distinguish from widget corner
      const buttonCornerKey = cornerMap[buttonRadius];
      const buttonCornerValue = buttonRadius === 'square' ? '0px' :
                               buttonRadius === 'rounded' ? '0.75rem' :
                               '9999px'; // pill
      const cleanButtonCorner: Record<string, string> = {};
      cleanButtonCorner[buttonCornerKey] = buttonCornerValue;

      const shapeTokens: Record<string, any> = {
        ...(existingShapeTokens || {}),
        corner: cleanCorner, // For widgets
        button_corner: cleanButtonCorner // For page-level buttons
      };
      
      // Add shadow if border effect is shadow and intensity is not none
      const existingShape = existingShapeTokens as any;
      if (borderEffect2 === 'shadow' && shadowMap[shadowIntensity2]) {
        shapeTokens.shadow = {
          ...(existingShape?.shadow || {}),
          [shadowMap[shadowIntensity2]!]: shadowIntensity2 === 'subtle' 
            ? '0 1px 2px rgba(15, 23, 42, 0.06)' 
            : '0 16px 48px rgba(15, 23, 42, 0.5)',
          focus: existingShape?.shadow?.focus || '0 0 0 4px rgba(37, 99, 235, 0.35)'
        };
      } else {
        shapeTokens.shadow = {
          ...(existingShape?.shadow || {}),
          focus: existingShape?.shadow?.focus || '0 0 0 4px rgba(37, 99, 235, 0.35)'
        };
      }
      
      // Build motion tokens with defaults
      const existingMotion = existingMotionTokens as any;
      const motionTokens: Record<string, any> = {
        ...(existingMotionTokens || {}),
        duration: {
          ...(existingMotion?.duration || {}),
          fast: existingMotion?.duration?.fast || '150ms',
          standard: existingMotion?.duration?.standard || '250ms'
        },
        easing: {
          ...(existingMotion?.easing || {}),
          standard: existingMotion?.easing?.standard || 'cubic-bezier(0.4, 0, 0.2, 1)',
          decelerate: existingMotion?.easing?.decelerate || 'cubic-bezier(0.0, 0, 0.2, 1)'
        },
        focus: {
          ...(existingMotion?.focus || {}),
          ring_width: existingMotion?.focus?.ring_width || '3px',
          ring_offset: existingMotion?.focus?.ring_offset || '2px'
        }
      };
      
      // finalWidgetBackground and finalBackgroundSurface already defined above
      // DEBUG: Log what we're saving for widget background
      console.log('SAVING WIDGET BACKGROUND DEBUG:', {
        blockBackgroundImage,
        blockBackground,
        finalWidgetBackground,
        backgroundSurface,
        finalBackgroundSurface,
        isGradient: isGradient(finalWidgetBackground),
        isImage: isImageUrl(finalWidgetBackground)
      });
      
      // Map buttonRadius for widget_styles.shape (legacy)
      const widgetStylesShape = buttonRadius2 === 'square' ? 'square' :
                               buttonRadius2 === 'rounded' ? 'rounded' :
                               'pill';
      
      // Build widget_styles for backward compatibility
      const existingWidgetStyles = safeParse(theme?.widget_styles);
      const widgetStyles: Record<string, any> = {
        ...(existingWidgetStyles || {}),
        border_width: 'none', // No longer configurable
        shape: widgetStylesShape,
        border_effect: borderEffect2,
        border_shadow_intensity: borderEffect2 === 'shadow' ? shadowIntensity2 : 'none',
        border_glow_intensity: borderEffect2 === 'glow' ? glowIntensity2 : 'subtle',
        glow_color: borderEffect2 === 'glow' ? glowColor2 : '#ff00ff',
        spacing: spacingDensity === 'compact' ? 'tight' :
                 spacingDensity === 'cozy' ? 'comfortable' : 'spacious'
      };
      
      // Build legacy colors JSON
      const legacyColors = {
        primary: textPrimary,
        secondary: backgroundSurface,
        accent: accentPrimary
      };
      
      // Build legacy fonts JSON
      const legacyFonts = {
        heading: headingFont,
        body: bodyFont
      };
      
      // DEBUG: Log what we're saving
      console.log('SAVING THEME DEBUG:', {
        shapeTokens: shapeTokens,
        buttonRadius2: buttonRadius2,
        borderEffect2: borderEffect2,
        shadowIntensity2: shadowIntensity2,
        glowIntensity2: glowIntensity2,
        glowColor2: glowColor2,
        finalWidgetBackground: finalWidgetBackground,
        blockBackground: blockBackground,
        blockBackgroundImage: blockBackgroundImage
      });
      
      const themeData = {
        name: themeName || theme?.name || 'Untitled Theme',
        color_tokens: colorTokens,
        typography_tokens: typographyTokens,
        spacing_tokens: spacingTokens,
        shape_tokens: shapeTokens,
        motion_tokens: motionTokens,
        iconography_tokens: iconographyTokens,
        page_background: finalPageBackground,
        widget_background: finalWidgetBackground,
        widget_border_color: borderDefault,
        page_primary_font: headingFont,
        page_secondary_font: bodyFont,
        widget_primary_font: headingFont, // Default to page fonts for now
        widget_secondary_font: bodyFont, // Default to page fonts for now
        spatial_effect: theme?.spatial_effect || 'none',
        widget_styles: widgetStyles,
        colors: legacyColors,
        fonts: legacyFonts
      };
      
      // DEBUG: Log scale values being saved
      const scaleToSave = typographyTokens?.scale;
      console.log('SAVING THEME DEBUG:', {
        headingPreset: headingFontSizePreset,
        bodyPreset: bodyFontSizePreset,
        scale_xl: scaleToSave?.xl,
        scale_sm: scaleToSave?.sm,
        fullScale: scaleToSave
      });
      
      // CRITICAL DEBUG: Log widget shape being saved
      console.log('WIDGET SHAPE SAVE DEBUG:', {
        buttonRadius2: buttonRadius2,
        cornerMap: cornerMap,
        cornerKey: cornerKey,
        cornerValue: cornerValue,
        cleanCorner: cleanCorner,
        shapeTokens: shapeTokens,
        fullShapeTokens: JSON.stringify(shapeTokens, null, 2)
      });
      
      // Always use the single user theme (get or create if needed)
      const userThemeId = await getOrCreateUserTheme();
      
      // Update the user theme with all current settings
      await updateMutation.mutateAsync({
        themeId: userThemeId,
        data: {
          ...themeData,
          name: 'My Theme' // Keep user theme name consistent
        }
      });
      
      setSaveStatus('success');
      setHasChanges(false);
      setStatusMessage('Theme updated successfully');
      
      // Invalidate and refetch theme library to update theme cards
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      
      // Ensure page.theme_id points to user theme and clear page-level overrides
      try {
        await updatePageThemeId(userThemeId, {
          page_background: null, // Clear to use theme value
          widget_background: null, // Clear to use theme value
          widget_border_color: null,
          page_primary_font: null,
          page_secondary_font: null,
          widget_primary_font: null,
          widget_secondary_font: null,
          widget_styles: null,
          spatial_effect: null
        });
        // Explicitly refetch page snapshot to ensure preview updates
        await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
      } catch (error) {
        console.error('Failed to update page theme_id:', error);
        // Don't fail the whole save if this fails
      }
      
      onSave?.();
    } catch (error) {
      console.error('Failed to save theme', error);
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save theme');
    }
  };
  
  const handleSaveAsNew = async () => {
    try {
      setSaveStatus('saving');
      
      // Reuse the same comprehensive logic as handleSave
      // Load existing tokens to preserve values not edited in UI
      const existingColorTokens = safeParse(theme?.color_tokens);
      const existingTypographyTokens = safeParse(theme?.typography_tokens);
      const existingSpacingTokens = safeParse(theme?.spacing_tokens);
      const existingShapeTokens = safeParse(theme?.shape_tokens);
      const existingMotionTokens = safeParse(theme?.motion_tokens);
      
      // Extract current values
      const accentPrimary = extractColorValue(tokens, 'semantic.accent.primary');
      const accentSecondary = extractColorValue(tokens, 'semantic.accent.secondary');
      const textPrimary = extractColorValue(tokens, 'semantic.text.primary');
      const textSecondary = extractColorValue(tokens, 'semantic.text.secondary');
      const backgroundBase = extractColorValue(tokens, 'semantic.surface.canvas');
      const backgroundSurface = extractColorValue(tokens, 'semantic.surface.base');
      
      // Determine page_background value - use image if set, otherwise use the token value
      const finalPageBackground = pageBackgroundImage || backgroundBase;
      const isPageGradient = isGradient(finalPageBackground);
      
      // Determine block background value
      // CRITICAL: Only use saved theme.widget_background if it's NOT the fallback white value
      // If saved value is #ffffff (fallback), ignore it and use current state
      const savedWidgetBackground = theme?.widget_background;
      const isSavedValueFallback = savedWidgetBackground === '#ffffff' || savedWidgetBackground === '#fff' || savedWidgetBackground === 'white';
      const finalWidgetBackground = (!isSavedValueFallback && savedWidgetBackground) 
        ? savedWidgetBackground 
        : (blockBackgroundImage || blockBackground);
      const isBlockGradient = isGradient(finalWidgetBackground);
      
      // CRITICAL: Ensure colorTokens.background.surface matches widget_background
      const finalBackgroundSurface = finalWidgetBackground;
      
      // Derive additional color values
      const existingColors = existingColorTokens as any;
      const accentMuted = existingColors?.accent?.muted || (accentPrimary && /^#/.test(accentPrimary) ? lightenColor(accentPrimary, 0.75) : '#e0edff');
      const textInverse = existingColors?.text?.inverse || '#ffffff';
      const borderDefault = existingColors?.border?.default || (textPrimary && /^#/.test(textPrimary) ? darkenColor(textPrimary, 0.2) : '#d1d5db');
      const borderFocus = existingColors?.border?.focus || (accentPrimary && /^#/.test(accentPrimary) ? lightenColor(accentPrimary, 0.25) : '#2563eb');
      const backgroundSurfaceRaised = existingColors?.background?.surface_raised || (backgroundSurface && /^#/.test(backgroundSurface) ? lightenColor(backgroundSurface, 0.22) : '#f9fafb');
      const backgroundOverlay = existingColors?.background?.overlay || 'rgba(15, 23, 42, 0.6)';
      
      // Build complete color tokens (same structure as handleSave)
      const colorTokens: Record<string, any> = {
        ...(existingColorTokens || {}),
        accent: {
          ...(existingColors?.accent || {}),
          primary: accentPrimary,
          secondary: accentSecondary,
          muted: accentMuted
        },
        text: {
          ...(existingColors?.text || {}),
          primary: textPrimary,
          secondary: textSecondary,
          inverse: textInverse
        },
        background: {
          ...(existingColors?.background || {}),
          base: backgroundBase,
          surface: finalBackgroundSurface, // CRITICAL: Use finalWidgetBackground to keep in sync with widget_background
          surface_raised: backgroundSurfaceRaised,
          overlay: backgroundOverlay
        },
        border: {
          ...(existingColors?.border || {}),
          default: borderDefault,
          focus: borderFocus
        },
        state: {
          ...(existingColors?.state || {}),
          success: existingColors?.state?.success || '#12b76a',
          warning: existingColors?.state?.warning || '#f59e0b',
          danger: existingColors?.state?.danger || '#ef4444'
        },
        text_state: {
          ...(existingColors?.text_state || {}),
          success: existingColors?.text_state?.success || '#0f5132',
          warning: existingColors?.text_state?.warning || '#7c2d12',
          danger: existingColors?.text_state?.danger || '#7f1d1d'
        },
        shadow: {
          ...(existingColors?.shadow || {}),
          ambient: existingColors?.shadow?.ambient || 'rgba(15, 23, 42, 0.12)',
          focus: existingColors?.shadow?.focus || 'rgba(37, 99, 235, 0.35)'
        },
        gradient: {
          ...(existingColors?.gradient || {}),
          page: isPageGradient ? finalPageBackground : (existingColors?.gradient?.page || null),
          accent: existingColors?.gradient?.accent || null,
          widget: isBlockGradient ? finalWidgetBackground : (existingColors?.gradient?.widget || null),
          podcast: existingColors?.gradient?.podcast || null
        },
        glow: {
          ...(existingColors?.glow || {}),
          primary: existingColors?.glow?.primary || null
        }
      };
      
      // Build typography tokens (same as handleSave)
      const existingTypography = existingTypographyTokens as any;
      const typographyTokens: Record<string, any> = {
        ...(existingTypographyTokens || {}),
        font: {
          ...(existingTypography?.font || {}),
          heading: headingFont,
          body: bodyFont,
          metatext: existingTypography?.font?.metatext || bodyFont
        },
        color: {
          ...(existingTypography?.color || {}),
          heading: headingColor !== extractColorValue(tokens, 'semantic.text.primary') ? headingColor : undefined,
          body: bodyColor !== extractColorValue(tokens, 'semantic.text.primary') ? bodyColor : undefined,
          // Widget typography colors (separate from page typography)
          widget_heading: widgetHeadingColor !== headingColor ? widgetHeadingColor : undefined,
          widget_body: widgetBodyColor !== bodyColor ? widgetBodyColor : undefined
        },
        scale: {
          ...(existingTypography?.scale || {}),
          // Map heading preset to xl (page title), lg, and md scale values
          xl: headingFontSizePreset === 'small' ? 1.5 :
              headingFontSizePreset === 'medium' ? 2.0 :
              headingFontSizePreset === 'large' ? 2.488 : 3.0,
          lg: headingFontSizePreset === 'small' ? 1.2 :
              headingFontSizePreset === 'medium' ? 1.5 :
              headingFontSizePreset === 'large' ? 1.777 : 2.2,
          md: headingFontSizePreset === 'small' ? 1.1 :
              headingFontSizePreset === 'medium' ? 1.333 :
              headingFontSizePreset === 'large' ? 1.5 : 1.777,
          // Map body preset to sm (body text) scale value
          sm: bodyFontSizePreset === 'small' ? 0.9 :
              bodyFontSizePreset === 'medium' ? 1.111 :
              bodyFontSizePreset === 'large' ? 1.25 : 1.5,
          xs: existingTypography?.scale?.xs || 0.889
        },
        line_height: {
          ...(existingTypography?.line_height || {}),
          tight: existingTypography?.line_height?.tight || 1.2,
          normal: existingTypography?.line_height?.normal || 1.5,
          relaxed: existingTypography?.line_height?.relaxed || 1.7
        },
        weight: {
          ...(existingTypography?.weight || {}),
          normal: existingTypography?.weight?.normal || 400,
          medium: existingTypography?.weight?.medium || 500,
          bold: existingTypography?.weight?.bold || 600
        }
      };
      
      // Build spacing tokens (same as handleSave)
      const existingSpacing = existingSpacingTokens as any;
      const spacingTokens: Record<string, any> = {
        ...(existingSpacingTokens || {}),
        density: spacingDensity, // Page spacing density (also used for widgets)
        base_scale: existingSpacing?.base_scale || {
          '2xs': 0.25,
          'xs': 0.5,
          'sm': 0.75,
          'md': 1.0,
          'lg': 1.5,
          'xl': 2.0,
          '2xl': 3.0
        },
        density_multipliers: existingSpacing?.density_multipliers || {
          compact: {
            '2xs': 0.75,
            'xs': 0.85,
            'sm': 0.9,
            'md': 1.0,
            'lg': 1.0,
            'xl': 1.0,
            '2xl': 1.0
          },
          comfortable: {
            '2xs': 1.0,
            'xs': 1.0,
            'sm': 1.1,
            'md': 1.25,
            'lg': 1.3,
            'xl': 1.35,
            '2xl': 1.4
          }
        },
        modifiers: existingSpacing?.modifiers || []
      };
      
      // Map buttonRadius to shape_tokens.corner.*
      const cornerMap: Record<string, string> = {
        'none': 'none',
        'small': 'sm',
        'medium': 'md',
        'large': 'lg',
        'pill': 'pill'
      };
      
      // Map shadowIntensity2 to shape_tokens.shadow.level_* (only if border effect is shadow)
      const shadowMap: Record<string, string | null> = {
        'none': null,
        'subtle': 'level_1',
        'pronounced': 'level_2'
      };
      
      // Build shape tokens (same as handleSave)
      // CRITICAL: Clear all other corner values to prevent cross-contamination
      // Only set the one value that matches buttonRadius2 (block widget shape)
      const cornerKey2 = cornerMap[buttonRadius2];
      const cornerValue2 = buttonRadius2 === 'square' ? '0px' :
                          buttonRadius2 === 'rounded' ? '0.75rem' :
                          '9999px'; // pill
      
      // Build a clean corner object with only the active value
      const cleanCorner2: Record<string, string> = {};
      cleanCorner2[cornerKey2] = cornerValue2;
      
      const shapeTokens: Record<string, any> = {
        ...(existingShapeTokens || {}),
        corner: cleanCorner2
      };
      
      // Add shadow if border effect is shadow and intensity is not none
      const existingShape = existingShapeTokens as any;
      if (borderEffect2 === 'shadow' && shadowMap[shadowIntensity2]) {
        shapeTokens.shadow = {
          ...(existingShape?.shadow || {}),
          [shadowMap[shadowIntensity2]!]: shadowIntensity2 === 'subtle' 
            ? '0 1px 2px rgba(15, 23, 42, 0.06)' 
            : '0 16px 48px rgba(15, 23, 42, 0.5)',
          focus: existingShape?.shadow?.focus || '0 0 0 4px rgba(37, 99, 235, 0.35)'
        };
      } else {
        shapeTokens.shadow = {
          ...(existingShape?.shadow || {}),
          focus: existingShape?.shadow?.focus || '0 0 0 4px rgba(37, 99, 235, 0.35)'
        };
      }
      
      // Build motion tokens (same as handleSave)
      const existingMotion = existingMotionTokens as any;
      const motionTokens: Record<string, any> = {
        ...(existingMotionTokens || {}),
        duration: {
          ...(existingMotion?.duration || {}),
          fast: existingMotion?.duration?.fast || '150ms',
          standard: existingMotion?.duration?.standard || '250ms'
        },
        easing: {
          ...(existingMotion?.easing || {}),
          standard: existingMotion?.easing?.standard || 'cubic-bezier(0.4, 0, 0.2, 1)',
          decelerate: existingMotion?.easing?.decelerate || 'cubic-bezier(0.0, 0, 0.2, 1)'
        },
        focus: {
          ...(existingMotion?.focus || {}),
          ring_width: existingMotion?.focus?.ring_width || '3px',
          ring_offset: existingMotion?.focus?.ring_offset || '2px'
        }
      };
      
      // Build iconography tokens (same as handleSave)
      const existingIconographyTokens = safeParse(theme?.iconography_tokens);
      const iconographyTokens: Record<string, any> = {
        ...(existingIconographyTokens || {}),
        size: iconSize === 'small' ? '36px' : iconSize === 'medium' ? '48px' : '60px',
        color: iconColor,
        spacing: `${iconSpacing}rem`
      };
      
      // finalWidgetBackground already defined above (line 1277)
      // Build widget_styles for backward compatibility
      const existingWidgetStyles = safeParse(theme?.widget_styles);
      const widgetStyles: Record<string, any> = {
        ...(existingWidgetStyles || {}),
        border_width: 'none', // No longer configurable
        shape: buttonRadius2 === 'square' ? 'square' :
               buttonRadius2 === 'rounded' ? 'rounded' :
               'pill', // buttonRadius2 === 'pill'
        border_effect: borderEffect2,
        border_shadow_intensity: borderEffect2 === 'shadow' ? shadowIntensity2 : 'none',
        border_glow_intensity: borderEffect2 === 'glow' ? glowIntensity2 : 'subtle',
        glow_color: borderEffect2 === 'glow' ? glowColor2 : '#ff00ff',
        spacing: spacingDensity === 'compact' ? 'tight' :
                 spacingDensity === 'cozy' ? 'comfortable' : 'spacious'
      };
      
      // DEBUG: Log glow settings being saved
      console.log('[ThemeEditorPanel] 💾 Saving glow settings:', {
        borderEffect2,
        glowIntensity2,
        glowColor2,
        widgetStyles: {
          border_effect: widgetStyles.border_effect,
          border_glow_intensity: widgetStyles.border_glow_intensity,
          glow_color: widgetStyles.glow_color
        }
      });
      
      // Build legacy colors JSON
      const legacyColors = {
        primary: textPrimary,
        secondary: backgroundSurface,
        accent: accentPrimary
      };
      
      // Build legacy fonts JSON
      const legacyFonts = {
        heading: headingFont,
        body: bodyFont
      };
      
      const themeData = {
        name: themeName ? `${themeName} Copy` : `${theme?.name || 'Untitled Theme'} Copy`,
        color_tokens: colorTokens,
        typography_tokens: typographyTokens,
        spacing_tokens: spacingTokens,
        shape_tokens: shapeTokens,
        motion_tokens: motionTokens,
        iconography_tokens: iconographyTokens,
        page_background: finalPageBackground,
        widget_background: finalWidgetBackground,
        widget_border_color: borderDefault,
        page_primary_font: headingFont,
        page_secondary_font: bodyFont,
        widget_primary_font: headingFont,
        widget_secondary_font: bodyFont,
        spatial_effect: theme?.spatial_effect || 'none',
        widget_styles: widgetStyles,
        colors: legacyColors,
        fonts: legacyFonts
      };
      
      // Always use the single user theme instead of creating new
      const userThemeId = await getOrCreateUserTheme();
      await updateMutation.mutateAsync({
        themeId: userThemeId,
        data: {
          ...themeData,
          name: 'My Theme' // Keep user theme name consistent
        }
      });
      setStatusMessage('Theme saved as new');
      setSaveStatus('success');
      setHasChanges(false);
      
      await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      
      // Ensure page.theme_id points to user theme and clear page-level overrides
      try {
        await updatePageThemeId(userThemeId, {
          page_background: null, // Clear to use theme value
          widget_background: null, // Clear to use theme value
          widget_border_color: null,
          page_primary_font: null,
          page_secondary_font: null,
          widget_primary_font: null,
          widget_secondary_font: null,
          widget_styles: null,
          spatial_effect: null
        });
        // Explicitly refetch page snapshot to ensure preview updates
        await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
      } catch (error) {
        console.error('Failed to update page theme_id:', error);
        // Don't fail the whole save if this fails
      }
      
      onSave?.();
    } catch (error) {
      console.error('Failed to save theme', error);
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save theme');
    }
  };
  
  const handleReset = () => {
    if (theme) {
      // Reload theme data
      const colorTokens = safeParse(theme.color_tokens);
      const typographyTokens = safeParse(theme.typography_tokens);
      const spacingTokens = safeParse(theme.spacing_tokens);
      const shapeTokens = safeParse(theme.shape_tokens);
      
      let updatedTokens = { ...tokens };
      
      if (colorTokens) {
        Object.entries(colorTokens).forEach(([key, value]) => {
          if (typeof value === 'object' && value !== null) {
            Object.entries(value).forEach(([subKey, subValue]) => {
              if (typeof subValue === 'string') {
                const path = `semantic.${key}.${subKey}`;
                updatedTokens = applyTokenUpdate(updatedTokens, path, subValue);
              }
            });
          }
        });
      }
      
      if (typographyTokens && typeof typographyTokens === 'object') {
        const fonts = (typographyTokens as any).font;
        if (fonts) {
          if (fonts.heading && typeof fonts.heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', fonts.heading);
          }
          if (fonts.body && typeof fonts.body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', fonts.body);
          }
        }
      }
      
      if (spacingTokens && typeof spacingTokens === 'object') {
        const density = (spacingTokens as any).density;
        if (density && ['compact', 'cozy', 'comfortable'].includes(density)) {
          setSpacingDensity(density as 'compact' | 'cozy' | 'comfortable');
        }
      }
      
      if (shapeTokens && typeof shapeTokens === 'object') {
        const buttonRadiusOld = (shapeTokens as any).button_radius;
        if (buttonRadiusOld) {
          if (buttonRadiusOld === '0' || buttonRadiusOld === '0px') setButtonRadius('square');
          else if (buttonRadiusOld === '9999px') setButtonRadius('pill');
          else setButtonRadius('rounded');
        }
        
        // Border width and shadow level are no longer configurable
        // They are only configurable for block widgets via border_effect
      }
      
      setTokens(updatedTokens);
      setHasChanges(false);
      setStatusMessage('Changes reset');
      setTimeout(() => setStatusMessage(null), 2000);
    }
  };
  
  const fontOptions = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Source Sans Pro'];
  
  // Page Style accordion items - using extracted components
  const pageStyleItems: TokenAccordionItem[] = useMemo(() => [
    {
      id: 'page-background-type',
      trigger: (
        <div className={styles.accordionTrigger}>
          <Image className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Page Background Type</span>
        </div>
      ),
      description: 'Choose background type',
      content: (
        <PageBackgroundSection
          backgroundType={backgroundType}
          onBackgroundTypeChange={(type) => {
            setBackgroundType(type as 'solid' | 'gradient' | 'image');
            setHasChanges(true);
          }}
          pageBackground={pageBackground}
          onBackgroundChange={(value) => {
            handleColorChange('semantic.surface.canvas', value);
            setHasChanges(true);
          }}
          pageBackgroundImage={pageBackgroundImage}
          onBackgroundImageUrlChange={(url) => handleBackgroundImageUrlChange(url, 'page')}
          onBackgroundImageUpload={(file) => handleBackgroundImageUpload(file, 'page')}
          onBackgroundImageRemove={() => handleBackgroundImageRemove('page')}
        />
      )
    },
    {
      id: 'typography-page',
      trigger: (
        <div className={styles.accordionTrigger}>
          <TextAa className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Typography</span>
        </div>
      ),
      description: 'Choose fonts and colors',
      content: (
        <TypographySection
          headingFont={headingFont}
          bodyFont={bodyFont}
          headingFontSizePreset={headingFontSizePreset}
          bodyFontSizePreset={bodyFontSizePreset}
          headingBold={headingBold}
          headingItalic={headingItalic}
          headingUnderline={headingUnderline}
          headingAlignment={headingAlignment}
          bodyBold={bodyBold}
          bodyItalic={bodyItalic}
          bodyUnderline={bodyUnderline}
          bodyAlignment={bodyAlignment}
          colorMode={colorMode}
          headingColor={headingColor}
          bodyColor={bodyColor}
          fontOptions={fontOptions}
          onFontChange={(path, value) => {
            handleFontChange(path, value);
            setHasChanges(true);
          }}
          onHeadingPresetChange={handleHeadingPresetChange}
          onBodyPresetChange={handleBodyPresetChange}
          onHeadingBoldChange={(bold) => {
            setHeadingBold(bold);
            setHasChanges(true);
          }}
          onHeadingItalicChange={(italic) => {
            setHeadingItalic(italic);
            setHasChanges(true);
          }}
          onHeadingUnderlineChange={(underline) => {
            setHeadingUnderline(underline);
            setHasChanges(true);
          }}
          onHeadingAlignmentChange={(alignment) => {
            setHeadingAlignment(alignment);
            setHasChanges(true);
          }}
          onBodyBoldChange={(bold) => {
            setBodyBold(bold);
            setHasChanges(true);
          }}
          onBodyItalicChange={(italic) => {
            setBodyItalic(italic);
            setHasChanges(true);
          }}
          onBodyUnderlineChange={(underline) => {
            setBodyUnderline(underline);
            setHasChanges(true);
          }}
          onBodyAlignmentChange={(alignment) => {
            setBodyAlignment(alignment);
            setHasChanges(true);
          }}
          onColorModeChange={(mode) => {
            setColorMode(mode);
            setHasChanges(true);
          }}
          onHeadingColorChange={(value) => {
            handleColorChange('core.typography.color.heading', value);
            setHasChanges(true);
          }}
          onBodyColorChange={(value) => {
            handleColorChange('core.typography.color.body', value);
            setHasChanges(true);
          }}
        />
      )
    },
    {
      id: 'iconography-page',
      trigger: (
        <div className={styles.accordionTrigger}>
          <Share className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Iconography</span>
        </div>
      ),
      description: 'Customize social icon size and color',
      content: (
        <div className={styles.typographySection}>
          {/* Icon Size */}
          <div className={styles.control}>
            <span className={styles.controlLabel}>Icon Size</span>
            <div className={styles.presetButtons}>
              {(['small', 'medium', 'large'] as const).map((size) => (
                <button
                  key={size}
                  type="button"
                  className={`${styles.presetButton} ${iconSize === size ? styles.presetButtonActive : ''}`}
                  onClick={() => {
                    setIconSize(size);
                    setHasChanges(true);
                  }}
                >
                  {size.charAt(0).toUpperCase() + size.slice(1)}
                </button>
              ))}
            </div>
          </div>

          {/* Icon Spacing */}
          <div className={styles.control} style={{ marginTop: '1rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
              <span className={styles.controlLabel}>Icon Spacing</span>
              <span style={{ fontSize: '0.85rem', color: 'var(--pod-semantic-text-secondary, #6b7280)', fontWeight: 500 }}>
                {iconSpacing}rem
              </span>
            </div>
            <input
              type="range"
              min="0"
              max="2"
              step="0.1"
              value={iconSpacing}
              onChange={(e) => {
                setIconSpacing(parseFloat(e.target.value));
                setHasChanges(true);
              }}
              style={{
                width: '100%',
                height: '6px',
                borderRadius: '3px',
                background: 'rgba(15, 23, 42, 0.1)',
                outline: 'none',
                cursor: 'pointer',
                WebkitAppearance: 'none',
                appearance: 'none'
              }}
            />
            <style>{`
              input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--active-tab-color, #2563eb);
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
              }
              input[type="range"]::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--active-tab-color, #2563eb);
                cursor: pointer;
                border: none;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
              }
              input[type="range"]::-webkit-slider-thumb:hover {
                background: var(--active-tab-color, #2563eb);
                box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
              }
              input[type="range"]::-moz-range-thumb:hover {
                background: var(--active-tab-color, #2563eb);
                box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
              }
            `}</style>
          </div>

          {/* Icon Color */}
          <div className={styles.control} style={{ marginTop: '1rem' }}>
            <span className={styles.controlLabel}>Icon Color</span>
            <Tabs.Root defaultValue="dark" className={styles.nestedTabs}>
              <Tabs.List className={styles.nestedTabList}>
                <Tabs.Trigger value="dark" className={styles.nestedTabTrigger}>
                  Dark
                </Tabs.Trigger>
                <Tabs.Trigger value="light" className={styles.nestedTabTrigger}>
                  Light
                </Tabs.Trigger>
              </Tabs.List>
              <Tabs.Content value="dark" className={styles.nestedTabContent}>
                <PageBackgroundPicker
                  value={iconColor}
                  onChange={(value) => {
                    setIconColor(value);
                    setHasChanges(true);
                  }}
                  mode="solid"
                  presetsOnly={true}
                  darkOnly={true}
                />
              </Tabs.Content>
              <Tabs.Content value="light" className={styles.nestedTabContent}>
                <PageBackgroundPicker
                  value={iconColor}
                  onChange={(value) => {
                    setIconColor(value);
                    setHasChanges(true);
                  }}
                  mode="solid"
                  presetsOnly={true}
                  lightOnly={true}
                />
              </Tabs.Content>
            </Tabs.Root>
          </div>
        </div>
      )
    },
    {
      id: 'spacing-page',
      trigger: (
        <div className={styles.accordionTrigger}>
          <GridFour className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Spacing</span>
        </div>
      ),
      description: 'Adjust density',
      content: (
        <SpacingSection
          density={spacingDensity}
          onDensityChange={(density) => {
            handleDensityChange(density);
            setHasChanges(true);
          }}
        />
      )
    },
  ], [
    backgroundType,
    pageBackground,
    pageBackgroundImage,
    headingFont,
    bodyFont,
    headingFontSizePreset,
    bodyFontSizePreset,
    headingBold,
    headingItalic,
    headingUnderline,
    headingAlignment,
    bodyBold,
    bodyItalic,
    bodyUnderline,
    bodyAlignment,
    colorMode,
    headingColor,
    bodyColor,
    iconSize,
    iconSpacing,
    iconColor,
    spacingDensity,
    buttonRadius,
    borderEffect,
    shadowIntensity,
    glowIntensity,
    glowColor,
    fontOptions
  ]);

  // Block/Widget Style accordion items - using extracted components
  const blockWidgetStyleItems: TokenAccordionItem[] = useMemo(() => [
    {
      id: 'block-background-type',
      trigger: (
        <div className={styles.accordionTrigger}>
          <Image className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Block Widget Background Type</span>
        </div>
      ),
      description: 'Choose background type',
      content: (
        <BlockBackgroundSection
          backgroundType={backgroundType2}
          onBackgroundTypeChange={(type) => {
            setBackgroundType2(type);
            setHasChanges(true);
          }}
          blockBackground={blockBackground}
          onBackgroundChange={(value) => {
            handleColorChange('semantic.surface.base', value);
            setHasChanges(true);
          }}
          blockBackgroundImage={blockBackgroundImage}
          onBackgroundImageUrlChange={(url) => handleBackgroundImageUrlChange(url, 'block')}
          onBackgroundImageUpload={(file) => handleBackgroundImageUpload(file, 'block')}
          onBackgroundImageRemove={() => handleBackgroundImageRemove('block')}
        />
      )
    },
    {
      id: 'typography-block',
      trigger: (
        <div className={styles.accordionTrigger}>
          <TextAa className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Typography</span>
        </div>
      ),
      description: 'Choose fonts and colors for block content',
      content: (
        <TypographySection
          headingFont={headingFont}
          bodyFont={bodyFont}
          headingFontSizePreset={widgetHeadingFontSizePreset}
          bodyFontSizePreset={widgetBodyFontSizePreset}
          headingBold={headingBold}
          headingItalic={headingItalic}
          headingUnderline={headingUnderline}
          headingAlignment={headingAlignment}
          bodyBold={bodyBold}
          bodyItalic={bodyItalic}
          bodyUnderline={bodyUnderline}
          bodyAlignment={bodyAlignment}
          colorMode={widgetColorMode}
          headingColor={widgetHeadingColor}
          bodyColor={widgetBodyColor}
          fontOptions={fontOptions}
          onFontChange={(path, value) => {
            handleFontChange(path, value);
            setHasChanges(true);
          }}
          onHeadingPresetChange={(preset) => {
            setWidgetHeadingFontSizePreset(preset);
            setHasChanges(true);
          }}
          onBodyPresetChange={(preset) => {
            setWidgetBodyFontSizePreset(preset);
            setHasChanges(true);
          }}
          onHeadingBoldChange={(bold) => {
            setHeadingBold(bold);
            setHasChanges(true);
          }}
          onHeadingItalicChange={(italic) => {
            setHeadingItalic(italic);
            setHasChanges(true);
          }}
          onHeadingUnderlineChange={(underline) => {
            setHeadingUnderline(underline);
            setHasChanges(true);
          }}
          onHeadingAlignmentChange={(alignment) => {
            setHeadingAlignment(alignment);
            setHasChanges(true);
          }}
          onBodyBoldChange={(bold) => {
            setBodyBold(bold);
            setHasChanges(true);
          }}
          onBodyItalicChange={(italic) => {
            setBodyItalic(italic);
            setHasChanges(true);
          }}
          onBodyUnderlineChange={(underline) => {
            setBodyUnderline(underline);
            setHasChanges(true);
          }}
          onBodyAlignmentChange={(alignment) => {
            setBodyAlignment(alignment);
            setHasChanges(true);
          }}
          onHeadingColorChange={(value) => {
            handleColorChange('core.typography.color.widget_heading', value);
            setHasChanges(true);
          }}
          onBodyColorChange={(value) => {
            handleColorChange('core.typography.color.widget_body', value);
            setHasChanges(true);
          }}
          onColorModeChange={(mode) => {
            setWidgetColorMode(mode);
            setHasChanges(true);
          }}
          headingId="block-heading-font"
          bodyId="block-body-font"
        />
      )
    },
    {
      id: 'shape-block',
      trigger: (
        <div className={styles.accordionTrigger}>
          <Shapes className={styles.accordionIcon} aria-hidden="true" size={16} weight="regular" />
          <span>Shape & Effects</span>
        </div>
      ),
      description: 'Border radius, borders, and shadows',
      content: (
        <ShapeSection
          buttonRadius={buttonRadius2}
          borderEffect={borderEffect2}
          shadowIntensity={shadowIntensity2}
          glowIntensity={glowIntensity2}
          glowColor={glowColor2}
          onButtonRadiusChange={(radius) => {
            handleShapeChange2('buttonRadius', radius);
            setHasChanges(true);
          }}
          onBorderEffectChange={(effect) => {
            setBorderEffect2(effect);
            setHasChanges(true);
          }}
          onShadowIntensityChange={(intensity) => {
            setShadowIntensity2(intensity);
            setHasChanges(true);
          }}
          onGlowIntensityChange={(intensity) => {
            setGlowIntensity2(intensity);
            setHasChanges(true);
          }}
          onGlowColorChange={(color) => {
            setGlowColor2(color);
            setHasChanges(true);
          }}
        />
      )
    }
  ], [
    backgroundType2,
    blockBackground,
    blockBackgroundImage,
    headingFont,
    bodyFont,
    headingFontSizePreset,
    bodyFontSizePreset,
    colorMode,
    headingColor,
    bodyColor,
    buttonRadius2,
    borderEffect2,
    shadowIntensity2,
    glowIntensity2,
    glowColor2,
    fontOptions
  ]);

  const themeTabDefinitions = [
    {
      value: 'page-style',
      label: 'Page style'
    },
    {
      value: 'block-widget-style',
      label: 'Block / widget style'
    },
    {
      value: 'shadow-preview',
      label: 'Shadow preview'
    }
  ];

  // Shadow preview (read-only) generated from current theme
  // Note: ThemeEditorPanel uses tokens, not uiState, so preview shows saved theme state
  // For real-time updates, we'd need to convert tokens to uiState format
  const shadowPreviewVars = useMemo(() => {
    const pageRecord = snapshot?.page ? (snapshot.page as unknown as Record<string, unknown>) : undefined;
    // Use empty uiState - preview will show theme's saved state
    // TODO: Convert tokens to uiState for real-time updates
    const emptyUIState: Record<string, unknown> = {};
    return previewRenderer.generateCSSVariables(theme ?? null, emptyUIState, pageRecord);
  }, [snapshot?.page, theme]);

  // Get page data for shadow preview
  const page = snapshot?.page;
  const widgets = snapshot?.widgets || [];
  const socialIcons = snapshot?.social_icons || [];
  const shadowPreviewAvatar = (page?.profile_image as string | null) ?? null;
  const podcastName = (page?.podcast_name as string | undefined) || (page?.username as string | undefined);
  const podcastDescription = (page?.podcast_description as string | undefined);

  // Get platform icon helper (simplified version)
  const getPlatformIcon = (platformName: string): JSX.Element => {
    const platform = platformName.toLowerCase();
    const platformIcons: Record<string, string> = {
      'apple_podcasts': 'fas fa-podcast',
      'spotify': 'fab fa-spotify',
      'youtube_music': 'fab fa-youtube',
      'iheart_radio': 'fas fa-heart',
      'amazon_music': 'fab fa-amazon',
      'facebook': 'fab fa-facebook',
      'twitter': 'fab fa-twitter',
      'instagram': 'fab fa-instagram',
      'linkedin': 'fab fa-linkedin',
      'youtube': 'fab fa-youtube',
      'tiktok': 'fab fa-tiktok',
      'snapchat': 'fab fa-snapchat',
      'pinterest': 'fab fa-pinterest',
      'reddit': 'fab fa-reddit',
      'discord': 'fab fa-discord',
      'twitch': 'fab fa-twitch',
      'github': 'fab fa-github',
      'behance': 'fab fa-behance',
      'dribbble': 'fab fa-dribbble',
      'medium': 'fab fa-medium',
      'substack': 'fas fa-newspaper'
    };
    const iconClass = platformIcons[platform] || 'fas fa-link';
    return <i className={iconClass} />;
  };

  const ShadowPreviewCard = () => {
    const hostRef = useRef<HTMLDivElement | null>(null);
    const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);

    useEffect(() => {
      if (!hostRef.current) return;
      const root = hostRef.current.shadowRoot ?? hostRef.current.attachShadow({ mode: 'open' });
      setShadowRoot(root);
    }, []);

    const varLines = useMemo(
      () => Object.entries(shadowPreviewVars).map(([k, v]) => `  ${k}: ${v};`).join('\n'),
      [shadowPreviewVars]
    );

    const initials = useMemo(() => {
      const text = podcastName || 'P';
      const parts = text.trim().split(' ');
      const letters = parts.slice(0, 2).map((chunk) => chunk[0]).join('');
      return letters.toUpperCase();
    }, [podcastName]);

    // Load Font Awesome into Shadow DOM
    useEffect(() => {
      if (!shadowRoot) return;
      
      const link = shadowRoot.ownerDocument.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
      link.integrity = 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==';
      link.crossOrigin = 'anonymous';
      shadowRoot.appendChild(link);
    }, [shadowRoot]);

    const shadowStyles = useMemo(
      () => `
      :host {
        all: initial;
        display: block;
        font-family: var(--font-family-body, 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif);
        color: var(--page-description-color, #475569);
        background: var(--page-background, #ffffff);
      }
      :root {
${varLines}
      }
      .mobile-page-container {
        width: 100%;
        min-height: 100vh;
        background: var(--page-background, #ffffff);
        padding: var(--page-spacing, 16px);
      }
      .page-container {
        max-width: 100%;
        margin: 0 auto;
      }
      .profile-header {
        text-align: center;
        margin-bottom: 24px;
      }
      .profile-image-container {
        margin: 0 auto 16px;
        display: inline-block;
      }
      .profile-image {
        width: var(--profile-image-size, 120px);
        height: var(--profile-image-size, 120px);
        border-radius: var(--profile-image-radius, 16%);
        border-width: var(--profile-image-border-width, 0px);
        border-color: var(--profile-image-border-color, transparent);
        border-style: ${shadowPreviewVars['--profile-image-border-width'] && parseFloat(String(shadowPreviewVars['--profile-image-border-width']).replace('px', '')) > 0 ? 'solid' : 'none'};
        box-shadow: var(--profile-image-box-shadow, none);
        object-fit: cover;
        display: block;
      }
      .page-title {
        font-family: var(--page-title-font, var(--font-family-heading, inherit));
        font-size: var(--page-title-size, 24px);
        font-weight: var(--page-title-weight, 700);
        color: var(--page-title-color, #0f172a);
        margin: 0 0 12px 0;
        text-align: center;
      }
      .page-description {
        font-family: var(--page-description-font, var(--font-family-body, inherit));
        font-size: var(--page-bio-size, 14px);
        font-weight: var(--page-bio-weight, 400);
        color: var(--page-description-color, #475569);
        margin: 0 0 24px 0;
        text-align: center;
        line-height: 1.5;
      }
      .social-icons {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: var(--social-icon-spacing, var(--icon-spacing, 16px));
        margin: 0 0 24px 0;
      }
      .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--social-icon-size, var(--icon-size, 32px));
        height: var(--social-icon-size, var(--icon-size, 32px));
        color: var(--social-icon-color, var(--icon-color, #2563eb));
        text-decoration: none;
        font-size: calc(var(--social-icon-size, var(--icon-size, 32px)) * 0.625);
      }
      .widgets-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .widget-item {
        background: var(--widget-background, #ffffff);
        border: var(--widget-border-width, 1px) solid var(--widget-border-color, #e2e8f0);
        border-radius: var(--widget-border-radius, 12px);
        padding: 16px;
        box-shadow: var(--widget-shadow-depth, none);
      }
      .widget-title {
        font-family: var(--widget-heading-font, var(--font-family-heading, inherit));
        font-size: var(--widget-heading-size, 16px);
        font-weight: var(--widget-heading-weight, 600);
        color: var(--widget-heading-color, #0f172a);
        margin: 0 0 8px 0;
      }
      .widget-description {
        font-family: var(--widget-body-font, var(--font-family-body, inherit));
        font-size: var(--widget-body-size, 14px);
        font-weight: var(--widget-body-weight, 400);
        color: var(--widget-body-color, #475569);
        margin: 0;
        line-height: 1.5;
      }
    `,
      [varLines, shadowPreviewVars]
    );

    return (
      <div style={{ width: '100%', minHeight: 400 }}>
        <div ref={hostRef} style={{ width: '100%', border: '1px dashed #cbd5e1', borderRadius: 12, padding: 12, background: '#f8fafc', minHeight: 400 }} />
        {shadowRoot &&
          createPortal(
            <div className="mobile-page-container">
              <style>{shadowStyles}</style>
              <div className="page-container">
                {/* Profile Image */}
                {shadowPreviewAvatar && (
                  <div className="profile-header">
                    <div className="profile-image-container">
                      <img 
                        src={normalizeImageUrl(shadowPreviewAvatar)} 
                        alt="Profile" 
                        className="profile-image"
                        onError={(e) => {
                          (e.target as HTMLImageElement).style.display = 'none';
                        }}
                      />
                    </div>
                  </div>
                )}

                {/* Page Title */}
                {podcastName && (
                  <h1 className="page-title">{podcastName}</h1>
                )}

                {/* Page Description */}
                {podcastDescription && (
                  <p className="page-description">{podcastDescription}</p>
                )}

                {/* Social Icons */}
                {socialIcons.length > 0 && (
                  <div className="social-icons">
                    {socialIcons.map((icon: any) => (
                      <a
                        key={icon.id}
                        href={icon.url}
                        className="social-icon"
                        target="_blank"
                        rel="noopener noreferrer"
                        title={icon.platform_name}
                        onClick={(e) => e.preventDefault()}
                      >
                        {getPlatformIcon(icon.platform_name)}
                      </a>
                    ))}
                  </div>
                )}

                {/* Widgets */}
                {widgets.length > 0 && (
                  <div className="widgets-container">
                    {widgets.slice(0, 3).map((widget: any) => (
                      <div key={widget.id} className="widget-item">
                        {widget.title && (
                          <div className="widget-title">{widget.title}</div>
                        )}
                        {widget.config_data && (() => {
                          try {
                            const config = typeof widget.config_data === 'string' 
                              ? JSON.parse(widget.config_data) 
                              : widget.config_data;
                            if (config.description) {
                              return <div className="widget-description">{config.description}</div>;
                            }
                          } catch {
                            // Ignore parse errors
                          }
                          return null;
                        })()}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>,
            shadowRoot as unknown as Element
          )}
      </div>
    );
  };
  
  return (
    <section
      className={styles.wrapper}
      aria-label="Theme editor"
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div className={styles.headerContent}>
          <div className={styles.headerActions}>
            {theme?.id ? (
              <>
                <div className={styles.primaryAction}>
                  <button
                    type="button"
                    className={styles.saveButton}
                    onClick={handleSave}
                    disabled={saveStatus === 'saving' || !hasChanges}
                  >
                    <FloppyDisk aria-hidden="true" size={16} weight="regular" />
                    {saveStatus === 'saving' ? 'Saving…' : 'Update theme'}
                  </button>
                  <span className={styles.actionHint}>Apply changes to this theme</span>
                </div>
                <div className={styles.secondaryAction}>
                  <button
                    type="button"
                    className={styles.saveAsNewButton}
                    onClick={handleSaveAsNew}
                    disabled={saveStatus === 'saving'}
                  >
                    <Copy aria-hidden="true" size={16} weight="regular" />
                    Save as new
                  </button>
                  <span className={styles.actionHint}>Create a duplicate theme</span>
                </div>
              </>
            ) : (
              <button
                type="button"
                className={styles.saveButton}
                onClick={handleSave}
                disabled={saveStatus === 'saving' || !hasChanges}
              >
                <FloppyDisk aria-hidden="true" size={16} weight="regular" />
                {saveStatus === 'saving' ? 'Saving…' : 'Save theme'}
              </button>
            )}
          </div>
          <div className={styles.headerText}>
            <h3>{theme?.id ? 'Edit Theme' : 'New Theme'}</h3>
            <p>Customize colors, typography, spacing, and effects.</p>
          </div>
        </div>
      </header>
      
      <Tabs.Root className={styles.themeTabs} defaultValue="page-style">
        <Tabs.List className={styles.themeTabList} aria-label="Theme style sections">
          {themeTabDefinitions.map((tab) => (
            <Tabs.Trigger
              key={tab.value}
              value={tab.value}
              className={styles.themeTabTrigger}
            >
              <span>{tab.label}</span>
            </Tabs.Trigger>
          ))}
        </Tabs.List>

        <Tabs.Content value="page-style" className={styles.themeTabContent}>
          <TokenAccordion items={pageStyleItems} type="multiple" defaultValue={['page-background-type', 'typography-page', 'iconography-page', 'spacing-page']} />
        </Tabs.Content>

        <Tabs.Content value="block-widget-style" className={styles.themeTabContent}>
          <TokenAccordion items={blockWidgetStyleItems} type="multiple" defaultValue={['block-background-type', 'typography-block']} />
        </Tabs.Content>

        <Tabs.Content value="shadow-preview" className={styles.themeTabContent}>
          <div style={{ display: 'grid', gap: 12 }}>
            <div style={{ display: 'grid', gap: 4 }}>
              <h4 style={{ margin: 0, fontSize: 14, fontWeight: 600, color: '#0f172a' }}>Shadow DOM preview</h4>
              <p style={{ margin: 0, fontSize: 13, color: '#475569' }}>
                Read-only live preview rendered in a ShadowRoot (no iframe). Uses current theme CSS variables.
              </p>
            </div>
            <ShadowPreviewCard />
          </div>
        </Tabs.Content>
      </Tabs.Root>
      
      <div className={styles.actions}>
        <div className={styles.actionButtons}>
          {hasChanges && (
            <button
              type="button"
              className={styles.resetButton}
              onClick={handleReset}
              disabled={saveStatus === 'saving'}
            >
              Reset
            </button>
          )}
        </div>
        {statusMessage && (
          <div className={`${styles.statusMessage} ${styles[`statusMessage_${saveStatus}`]}`}>
            {saveStatus === 'success' && <Check aria-hidden="true" size={16} weight="regular" />}
            {saveStatus === 'error' && <XCircle aria-hidden="true" size={16} weight="regular" />}
            <span>{statusMessage}</span>
          </div>
        )}
      </div>
    </section>
  );
}

