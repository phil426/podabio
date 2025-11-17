import { useState, useEffect, useMemo, useRef, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as Tabs from '@radix-ui/react-tabs';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import * as Tooltip from '@radix-ui/react-tooltip';
import {
  Palette,
  TextT,
  GridFour,
  Shapes,
  Lightning,
  MagnifyingGlass,
  FloppyDisk,
  ArrowCounterClockwise,
  Download,
  Upload,
  Check,
  X,
  CaretDown,
  CaretRight
} from '@phosphor-icons/react';

import { useTokens } from '../../design-system/theme/TokenProvider';
import { useUpdateThemeMutation, useCreateThemeMutation } from '../../api/themes';
import { useThemeLibraryQuery } from '../../api/themes';
import { ColorsSection } from './ultimate-theme-modifier/ColorsSection';
import { TypographySection } from './ultimate-theme-modifier/TypographySection';
import { SpacingSection } from './ultimate-theme-modifier/SpacingSection';
import { ShapeSection } from './ultimate-theme-modifier/ShapeSection';
import { MotionSection } from './ultimate-theme-modifier/MotionSection';
import type { TabColorTheme } from '../layout/tab-colors';
import type { ThemeRecord } from '../../api/types';
import type { TokenBundle } from '../../design-system/tokens';

import styles from './ultimate-theme-modifier.module.css';

interface UltimateThemeModifierProps {
  activeColor: TabColorTheme;
  theme?: ThemeRecord | null;
  onSave?: () => void;
}

type TabValue = 'colors' | 'typography' | 'spacing' | 'shape' | 'motion';

interface TokenChange {
  path: string;
  oldValue: unknown;
  newValue: unknown;
  timestamp: number;
}

export function UltimateThemeModifier({ activeColor, theme, onSave }: UltimateThemeModifierProps): JSX.Element {
  const { tokens, setTokens } = useTokens();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const updateMutation = useUpdateThemeMutation();
  const createMutation = useCreateThemeMutation();

  const [activeTab, setActiveTab] = useState<TabValue>('colors');
  const [searchQuery, setSearchQuery] = useState('');
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [modifiedTokens, setModifiedTokens] = useState<Set<string>>(new Set());
  const [undoStack, setUndoStack] = useState<TokenChange[]>([]);
  const [redoStack, setRedoStack] = useState<TokenChange[]>([]);
  const [auroraDefaults, setAuroraDefaults] = useState<TokenBundle | null>(null);
  // Track all token values (including those not in TokenBundle like spacing_tokens, shape_tokens, etc.)
  const [tokenValues, setTokenValues] = useState<Map<string, unknown>>(new Map());

  // Load theme token values into tokenValues map
  useEffect(() => {
    if (theme) {
      const colorTokens = safeParse(theme.color_tokens);
      const typographyTokens = safeParse(theme.typography_tokens);
      const spacingTokens = safeParse(theme.spacing_tokens);
      const shapeTokens = safeParse(theme.shape_tokens);
      const motionTokens = safeParse(theme.motion_tokens);

      const initialValues = new Map<string, unknown>();

      // Load color tokens
      if (colorTokens) {
        if (colorTokens.gradient) {
          if (colorTokens.gradient.page) initialValues.set('color_tokens.gradient.page', colorTokens.gradient.page);
          if (colorTokens.gradient.accent) initialValues.set('color_tokens.gradient.accent', colorTokens.gradient.accent);
          if (colorTokens.gradient.widget) initialValues.set('color_tokens.gradient.widget', colorTokens.gradient.widget);
          if (colorTokens.gradient.podcast) initialValues.set('color_tokens.gradient.podcast', colorTokens.gradient.podcast);
        }
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

      setTokenValues(initialValues);
    } else {
      setTokenValues(new Map());
    }
  }, [theme]);

  // Track token changes
  const trackChange = useCallback((path: string, oldValue: unknown, newValue: unknown) => {
    setModifiedTokens(prev => new Set(prev).add(path));
    setUndoStack(prev => [...prev, { path, oldValue, newValue, timestamp: Date.now() }]);
    setRedoStack([]); // Clear redo stack on new change
  }, []);

  // Handle token changes from sections
  const handleTokenChange = useCallback((path: string, value: unknown, oldValue: unknown) => {
    // Store the value in our tokenValues map
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
    
    // Track the change
    trackChange(path, oldValue, value);
  }, [tokens, setTokens, trackChange]);

  // Helper function to update tokens
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

  // Helper to safely parse JSON
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

  // Extract color value from tokens
  function extractColorValue(bundle: TokenBundle, path: string): string {
    const parts = path.split('.');
    let current: any = bundle;
    
    for (const part of parts) {
      if (current && typeof current === 'object' && part in current) {
        current = current[part];
      } else {
        return '#2563eb'; // Default fallback
      }
    }
    
    if (typeof current === 'string') {
      return current;
    }
    
    return '#2563eb';
  }

  // Handle save
  const handleSave = useCallback(async () => {
    if (!theme || !tokens) {
      setStatusMessage('No theme selected');
      setSaveStatus('error');
      return;
    }

    setSaveStatus('saving');
    setStatusMessage('Saving...');

    try {
      // Load existing tokens to preserve values not edited in UI
      const existingColorTokens = safeParse(theme.color_tokens);
      const existingTypographyTokens = safeParse(theme.typography_tokens);
      const existingSpacingTokens = safeParse(theme.spacing_tokens);
      const existingShapeTokens = safeParse(theme.shape_tokens);
      const existingMotionTokens = safeParse(theme.motion_tokens);
      
      // Extract current color values from tokens
      const accentPrimary = extractColorValue(tokens, 'semantic.accent.primary');
      const accentSecondary = extractColorValue(tokens, 'semantic.accent.secondary');
      const textPrimary = extractColorValue(tokens, 'semantic.text.primary');
      const textSecondary = extractColorValue(tokens, 'semantic.text.secondary');
      const textInverse = extractColorValue(tokens, 'semantic.text.inverse');
      const backgroundBase = extractColorValue(tokens, 'semantic.surface.canvas');
      const backgroundSurface = extractColorValue(tokens, 'semantic.surface.base');
      
      // Build color tokens, preserving existing values
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

      // Build typography tokens, preserving existing values
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

      // Build spacing tokens, preserving existing values
      const spacingDensity = tokenValues.get('spacing_tokens.density') as string || existingSpacingTokens?.density || 'comfortable';
      const spacingTokens: Record<string, any> = {
        ...(existingSpacingTokens || {}),
        density: spacingDensity,
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

      // Build shape tokens, preserving existing values
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

      // Build motion tokens, preserving existing values
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

      // Determine page_background - use gradient if it's a gradient, otherwise use base
      const isGradient = backgroundBase.includes('gradient');
      const isImage = backgroundBase.startsWith('http://') || backgroundBase.startsWith('https://') || backgroundBase.startsWith('/') || backgroundBase.startsWith('data:');
      const pageBackground = isGradient || isImage ? backgroundBase : (existingColorTokens?.gradient?.page || backgroundBase);

      // Save to database
      await updateMutation.mutateAsync({
        themeId: theme.id,
        data: {
          name: theme.name,
          color_tokens: colorTokens,
          typography_tokens: typographyTokens,
          spacing_tokens: spacingTokens,
          shape_tokens: shapeTokens,
          motion_tokens: motionTokens,
          page_background: pageBackground
        }
      });

      setSaveStatus('success');
      setStatusMessage('Theme saved successfully');
      setModifiedTokens(new Set());
      onSave?.();

      setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 2000);
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save theme');
    }
  }, [theme, tokens, updateMutation, onSave]);

  // Undo handler
  const handleUndo = useCallback(() => {
    if (undoStack.length === 0) return;

    const lastChange = undoStack[undoStack.length - 1];
    const { path, oldValue } = lastChange;

    // Update tokenValues
    setTokenValues(prev => {
      const next = new Map(prev);
      if (oldValue === undefined) {
        next.delete(path);
      } else {
        next.set(path, oldValue);
      }
      return next;
    });

    // Update tokens if it's a TokenBundle path
    if (path.startsWith('semantic.') || path.startsWith('core.')) {
      const updatedTokens = applyTokenUpdate(tokens, path, oldValue);
      setTokens(updatedTokens);
    }

    // Move from undo to redo stack
    setUndoStack(prev => prev.slice(0, -1));
    setRedoStack(prev => [...prev, lastChange]);

    // Update modified tokens
    setModifiedTokens(prev => {
      const next = new Set(prev);
      if (oldValue === undefined) {
        next.delete(path);
      } else {
        next.add(path);
      }
      return next;
    });
  }, [undoStack, tokens, setTokens]);

  // Redo handler
  const handleRedo = useCallback(() => {
    if (redoStack.length === 0) return;

    const lastChange = redoStack[redoStack.length - 1];
    const { path, newValue } = lastChange;

    // Update tokenValues
    setTokenValues(prev => {
      const next = new Map(prev);
      next.set(path, newValue);
      return next;
    });

    // Update tokens if it's a TokenBundle path
    if (path.startsWith('semantic.') || path.startsWith('core.')) {
      const updatedTokens = applyTokenUpdate(tokens, path, newValue);
      setTokens(updatedTokens);
    }

    // Move from redo to undo stack
    setRedoStack(prev => prev.slice(0, -1));
    setUndoStack(prev => [...prev, lastChange]);

    // Update modified tokens
    setModifiedTokens(prev => new Set(prev).add(path));
  }, [redoStack, tokens, setTokens]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 's') {
        e.preventDefault();
        handleSave();
      } else if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
        e.preventDefault();
        handleUndo();
      } else if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'z') {
        e.preventDefault();
        handleRedo();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleSave, handleUndo, handleRedo]);

  return (
    <Tooltip.Provider delayDuration={200}>
    <div className={styles.container}>
      {/* Header */}
      <div className={styles.header}>
        <div className={styles.headerTop}>
          <h2 className={styles.title}>Theme Modifier</h2>
          <div className={styles.headerActions}>
            <Tooltip.Root>
              <Tooltip.Trigger asChild>
            <button
              className={styles.saveButton}
              onClick={handleSave}
              disabled={saveStatus === 'saving' || modifiedTokens.size === 0}
              aria-label="Save theme"
                  title="Save theme" 
            >
              {saveStatus === 'saving' ? (
                <>Saving...</>
              ) : saveStatus === 'success' ? (
                <><Check weight="bold" /> Saved</>
              ) : (
                <><FloppyDisk weight="regular" /> Save</>
              )}
            </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
                  className={styles.tooltip}
                >
                  Save your current color, type, spacing, shape, and motion changes to this theme.
                  <Tooltip.Arrow className={styles.tooltipArrow} />
                </Tooltip.Content>
              </Tooltip.Portal>
            </Tooltip.Root>
          </div>
        </div>
        <div className={styles.searchBar}>
          <MagnifyingGlass className={styles.searchIcon} weight="regular" />
          <input
            type="text"
            placeholder="Search tokens..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className={styles.searchInput}
          />
          {searchQuery && (
            <Tooltip.Root>
              <Tooltip.Trigger asChild>
            <button
              className={styles.clearSearch}
              onClick={() => setSearchQuery('')}
              aria-label="Clear search"
                  title="Clear search"
            >
              <X weight="regular" />
            </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
                  className={styles.tooltip}
                >
                  Clear your search and show all tokens again.
                  <Tooltip.Arrow className={styles.tooltipArrow} />
                </Tooltip.Content>
              </Tooltip.Portal>
            </Tooltip.Root>
          )}
        </div>
      </div>

      {/* Tab Navigation */}
      <Tabs.Root value={activeTab} onValueChange={(value) => setActiveTab(value as TabValue)} className={styles.tabs}>
        <Tabs.List className={styles.tabList}>
          <Tabs.Trigger value="colors" className={styles.tabTrigger}>
            <Palette weight="bold" />
            <span>Colors</span>
            {modifiedTokens.size > 0 && <span className={styles.badge}>{modifiedTokens.size}</span>}
          </Tabs.Trigger>
          <Tabs.Trigger value="typography" className={styles.tabTrigger}>
            <TextT weight="bold" />
            <span>Typography</span>
          </Tabs.Trigger>
          <Tabs.Trigger value="spacing" className={styles.tabTrigger}>
            <GridFour weight="bold" />
            <span>Spacing</span>
          </Tabs.Trigger>
          <Tabs.Trigger value="shape" className={styles.tabTrigger}>
            <Shapes weight="bold" />
            <span>Shape</span>
          </Tabs.Trigger>
          <Tabs.Trigger value="motion" className={styles.tabTrigger}>
            <Lightning weight="bold" />
            <span>Motion</span>
          </Tabs.Trigger>
        </Tabs.List>

        {/* Tab Content */}
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.content}>
              <Tabs.Content value="colors" className={styles.tabContent}>
                <AnimatePresence mode="wait">
                  {tokens && (
                    <motion.div
                      key="colors"
                      initial={{ opacity: 0, x: 10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      transition={{ duration: 0.2 }}
                    >
                      <ColorsSection
                        tokens={tokens}
                        onTokenChange={handleTokenChange}
                        searchQuery={searchQuery}
                        tokenValues={tokenValues}
                      />
                    </motion.div>
                  )}
                </AnimatePresence>
              </Tabs.Content>

              <Tabs.Content value="typography" className={styles.tabContent}>
                <AnimatePresence mode="wait">
                  {tokens && (
                    <motion.div
                      key="typography"
                      initial={{ opacity: 0, x: 10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      transition={{ duration: 0.2 }}
                    >
                      <TypographySection
                        tokens={tokens}
                        onTokenChange={handleTokenChange}
                        searchQuery={searchQuery}
                        tokenValues={tokenValues}
                      />
                    </motion.div>
                  )}
                </AnimatePresence>
              </Tabs.Content>

              <Tabs.Content value="spacing" className={styles.tabContent}>
                <AnimatePresence mode="wait">
                  {tokens && (
                    <motion.div
                      key="spacing"
                      initial={{ opacity: 0, x: 10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      transition={{ duration: 0.2 }}
                    >
                      <SpacingSection
                        tokens={tokens}
                        onTokenChange={handleTokenChange}
                        searchQuery={searchQuery}
                        tokenValues={tokenValues}
                      />
                    </motion.div>
                  )}
                </AnimatePresence>
              </Tabs.Content>

              <Tabs.Content value="shape" className={styles.tabContent}>
                <AnimatePresence mode="wait">
                  {tokens && (
                    <motion.div
                      key="shape"
                      initial={{ opacity: 0, x: 10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      transition={{ duration: 0.2 }}
                    >
                      <ShapeSection
                        tokens={tokens}
                        onTokenChange={handleTokenChange}
                        searchQuery={searchQuery}
                        tokenValues={tokenValues}
                      />
                    </motion.div>
                  )}
                </AnimatePresence>
              </Tabs.Content>

              <Tabs.Content value="motion" className={styles.tabContent}>
                <AnimatePresence mode="wait">
                  {tokens && (
                    <motion.div
                      key="motion"
                      initial={{ opacity: 0, x: 10 }}
                      animate={{ opacity: 1, x: 0 }}
                      exit={{ opacity: 0, x: -10 }}
                      transition={{ duration: 0.2 }}
                    >
                      <MotionSection
                        tokens={tokens}
                        onTokenChange={handleTokenChange}
                        searchQuery={searchQuery}
                        tokenValues={tokenValues}
                      />
                    </motion.div>
                  )}
                </AnimatePresence>
              </Tabs.Content>
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      </Tabs.Root>

      {/* Footer */}
      <div className={styles.footer}>
        <div className={styles.footerLeft}>
          {modifiedTokens.size > 0 && (
            <span className={styles.modifiedCount}>
              {modifiedTokens.size} {modifiedTokens.size === 1 ? 'token' : 'tokens'} modified
            </span>
          )}
        </div>
        <div className={styles.footerRight}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
          <button
            className={styles.footerButton}
            onClick={handleUndo}
            disabled={undoStack.length === 0}
            title="Undo (Cmd/Ctrl+Z)"
            aria-label="Undo"
          >
            <ArrowCounterClockwise weight="regular" /> Undo
          </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="top" align="center" className={styles.tooltip}>
                Step back one change. You can also press Cmd/Ctrl+Z.
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>

          <Tooltip.Root>
            <Tooltip.Trigger asChild>
          <button
            className={styles.footerButton}
            onClick={handleRedo}
            disabled={redoStack.length === 0}
            title="Redo (Cmd/Ctrl+Shift+Z)"
            aria-label="Redo"
          >
            <ArrowCounterClockwise weight="regular" style={{ transform: 'scaleX(-1)' }} /> Redo
          </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="top" align="center" className={styles.tooltip}>
                Re-apply the last undone change. You can also press Cmd/Ctrl+Shift+Z.
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>

          <Tooltip.Root>
            <Tooltip.Trigger asChild>
          <button
            className={styles.footerButton}
            onClick={() => {
              // Reset all - TODO: implement
            }}
            disabled={modifiedTokens.size === 0}
            title="Reset all changes (Coming soon)"
            aria-label="Reset all changes"
          >
            Reset All
          </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="top" align="center" className={styles.tooltip}>
                Reset every modified token in this theme back to its defaults. (Coming soon)
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>

          <Tooltip.Root>
            <Tooltip.Trigger asChild>
          <button
            className={styles.footerButton}
            onClick={() => {
              // Export - TODO: implement
            }}
            disabled
            title="Export tokens (Coming soon)"
            aria-label="Export tokens"
          >
            <Download weight="regular" /> Export
          </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="top" align="center" className={styles.tooltip}>
                Export this theme’s tokens for use in other projects. (Coming soon)
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>

          <Tooltip.Root>
            <Tooltip.Trigger asChild>
          <button
            className={styles.footerButton}
            onClick={() => {
              // Import - TODO: implement
            }}
            disabled
            title="Import tokens (Coming soon)"
            aria-label="Import tokens"
          >
            <Upload weight="regular" /> Import
          </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="top" align="center" className={styles.tooltip}>
                Import a token JSON file to update this theme. (Coming soon)
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </div>
      </div>

      {/* Status Message */}
      <AnimatePresence>
        {statusMessage && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
            className={`${styles.statusMessage} ${styles[`status${saveStatus}`]}`}
          >
            {statusMessage}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
    </Tooltip.Provider>
  );
}

