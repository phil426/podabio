import { useMemo, useState, useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';

import { useWidgetSelection } from '../../state/widgetSelection';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { WidgetInspector } from '../panels/WidgetInspector';
import { ProfileInspector } from '../panels/ProfileInspector';
import { PodcastPlayerInspector } from '../panels/PodcastPlayerInspector';
import { FeaturedBlockInspector } from '../panels/FeaturedBlockInspector';
import { SocialIconInspector } from '../panels/SocialIconInspector';
import { IntegrationInspector } from '../panels/IntegrationInspector';
import { useThemeInspector } from '../../state/themeInspector';
import { ThemeEditorPanel } from '../panels/ThemeEditorPanel';
import { useThemeLibraryQuery, type ThemeLibraryResult } from '../../api/themes';
import { usePageSnapshot } from '../../api/page';
import { queryKeys } from '../../api/utils';
import type { ThemeRecord } from '../../api/types';
import type { TokenBundle, SemanticTokenGroup } from '../../design-system/tokens';

import { type TabColorTheme, type TabValue } from './tab-colors';

import styles from './properties-panel.module.css';

interface PropertiesPanelProps {
  activeColor: TabColorTheme;
  activeTab?: TabValue;
}

export function PropertiesPanel({ activeColor, activeTab = 'structure' }: PropertiesPanelProps): JSX.Element {
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const showThemeInspector = useThemeInspector((state) => state.isThemeInspectorVisible);
  const queryClient = useQueryClient();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const { data: snapshot } = usePageSnapshot();

  const activeTheme = useMemo(
    () => deriveActiveTheme(themeLibrary, snapshot?.page?.theme_id ?? null),
    [themeLibrary, snapshot?.page?.theme_id]
  );

  // Find selected widget to check if it's featured
  const selectedWidget = useMemo(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const isFeaturedWidget = selectedWidget?.is_featured === 1;

  // Determine which inspector to show based on activeTab and selection state
  // This ensures inspectors from other tabs don't persist when switching tabs
  let inspector: JSX.Element | null = null;

  // Gate inspectors by activeTab to prevent stale inspectors from other tabs
  if (activeTab === 'structure' || activeTab === 'design') {
    // Style tab: Show widget/page inspectors or default to Profile
    if (selectedWidgetId?.startsWith('page:')) {
      if (selectedWidgetId === 'page:profile') {
        inspector = <ProfileInspector focus="profile" activeColor={activeColor} />;
      } else if (selectedWidgetId === 'page:footer') {
        inspector = <ProfileInspector focus="footer" activeColor={activeColor} />;
      } else if (selectedWidgetId === 'page:podcast-player') {
        inspector = <PodcastPlayerInspector activeColor={activeColor} />;
      } else {
        // Legacy support for old IDs
        if (selectedWidgetId === 'page:short-bio') {
          inspector = <ProfileInspector focus="bio" activeColor={activeColor} />;
        } else {
          inspector = <ProfileInspector focus="image" activeColor={activeColor} />;
        }
      }
    } else if (selectedWidgetId) {
      // Show FeaturedBlockInspector if widget is featured, otherwise show WidgetInspector
      if (isFeaturedWidget) {
        inspector = (
          <>
            <FeaturedBlockInspector activeColor={activeColor} />
            <WidgetInspector activeColor={activeColor} />
          </>
        );
      } else {
        inspector = <WidgetInspector activeColor={activeColor} />;
      }
    } else if (activeTab === 'structure') {
      // Default to Profile inspector when on structure tab and nothing is selected
      inspector = <ProfileInspector focus="profile" activeColor={activeColor} />;
    }
    // Note: ThemeEditorPanel is handled separately via showThemeInspector state
  } else if (activeTab === 'integrations') {
    // Integrations tab: Show IntegrationInspector only if integration is selected
    if (selectedIntegrationId !== null) {
      inspector = <IntegrationInspector activeColor={activeColor} />;
    }
    // No default inspector for integrations tab
  } else if (activeTab === 'settings') {
    // Settings tab: Show SocialIconInspector only if social icon is selected
    if (selectedSocialIconId !== null) {
      inspector = <SocialIconInspector activeColor={activeColor} />;
    }
    // No default inspector for settings tab
  } else if (activeTab === 'analytics') {
    // Analytics tab: No inspector (right panel is collapsed)
    inspector = null;
  }

  return (
    <div 
      className={styles.container} 
      aria-label="Properties panel"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <div className={styles.scrollArea}>
        {showThemeInspector && (
          <>
            {console.log('[PropertiesPanel] Rendering ThemeEditorPanel', { 
              showThemeInspector, 
              activeThemeId: activeTheme?.id,
              activeThemeName: activeTheme?.name 
            })}
            <ThemeEditorPanel 
              activeColor={activeColor} 
              theme={activeTheme}
              onSave={async () => {
                // Explicitly refetch to ensure preview updates immediately
                await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
              }}
            />
          </>
        )}

        {inspector}
      </div>
    </div>
  );
}

function deriveActiveTheme(
  library: ThemeLibraryResult | undefined,
  themeId: number | null
): ThemeRecord | null {
  const systemThemes = library?.system ?? [];
  const userThemes = library?.user ?? [];

  if (themeId == null) {
    return systemThemes[0] ?? userThemes[0] ?? null;
  }

  const combined = [...userThemes, ...systemThemes];
  return combined.find((theme) => theme.id === themeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
}

function buildThemeTokenOverrides(theme: ThemeRecord | null): Partial<TokenBundle> | null {
  if (!theme) {
    return null;
  }

  const overrides: Partial<TokenBundle> = {};
  const semanticOverrides: Partial<SemanticTokenGroup> = {};

  const colorTokens = parseThemePayload(theme.color_tokens);
  const legacyColors = parseThemePayload(theme.colors);

  if (colorTokens) {
    const background = isRecord(colorTokens.background) ? (colorTokens.background as Record<string, unknown>) : null;
    const accent = isRecord(colorTokens.accent) ? (colorTokens.accent as Record<string, unknown>) : null;
    const text = isRecord(colorTokens.text) ? (colorTokens.text as Record<string, unknown>) : null;
    const border = isRecord(colorTokens.border) ? (colorTokens.border as Record<string, unknown>) : null;
    const state = isRecord(colorTokens.state) ? (colorTokens.state as Record<string, unknown>) : null;
    const shadow = isRecord(colorTokens.shadow) ? (colorTokens.shadow as Record<string, unknown>) : null;

    const overlaySurface = pickColor(background?.overlay);
    if (overlaySurface) {
      semanticOverrides.surface = { ...(semanticOverrides.surface ?? {}), overlay: overlaySurface };
    }

    const accentOverrides: Record<string, string> = {};
    const accentPrimary = pickColor(accent?.primary) ?? pickColor(accent?.highlight) ?? pickColor(accent?.alt);
    const accentSecondary = pickColor(accent?.alt) ?? pickColor(accent?.muted);
    const accentOutline = pickColor(accent?.muted) ?? pickColor(accent?.primary);

    if (accentPrimary) accentOverrides.primary = accentPrimary;
    if (accentSecondary) accentOverrides.secondary = accentSecondary;
    if (accentOutline) accentOverrides.outline = accentOutline;
    if (Object.keys(accentOverrides).length > 0) {
      semanticOverrides.accent = accentOverrides;
    }

    const textOverrides: Record<string, string> = {};
    const textPrimary = pickColor(text?.primary);
    const textSecondary = pickColor(text?.secondary);
    const textInverse = pickColor(text?.inverse);
    const textMuted = pickColor(text?.muted);

    if (textPrimary) textOverrides.primary = textPrimary;
    if (textSecondary) textOverrides.secondary = textSecondary;
    if (textMuted) textOverrides.muted = textMuted;
    if (textInverse) textOverrides.inverse = textInverse;
    if (Object.keys(textOverrides).length > 0) {
      semanticOverrides.text = { ...(semanticOverrides.text ?? {}), ...textOverrides };
    }

    const stateOverrides: Record<string, string> = {};
    if (state) {
      const success = pickColor(state.success);
      const warning = pickColor(state.warning);
      const danger = pickColor(state.danger);
      if (success) stateOverrides.success = success;
      if (warning) stateOverrides.warning = warning;
      if (danger) stateOverrides.critical = danger;
    }
    if (Object.keys(stateOverrides).length > 0) {
      semanticOverrides.state = { ...(semanticOverrides.state ?? {}), ...stateOverrides };
    }

    if (border) {
      const dividerOverrides: Record<string, string> = {};
      const borderDefault = pickColor(border.default);
      const borderFocus = pickColor(border.focus);
      if (borderDefault) dividerOverrides.subtle = borderDefault;
      if (borderDefault) dividerOverrides.strong = borderDefault;
      if (Object.keys(dividerOverrides).length > 0) {
        semanticOverrides.divider = dividerOverrides;
      }

      if (borderFocus) {
        semanticOverrides.focus = { ring: borderFocus, halo: borderFocus };
      }
    } else if (shadow) {
      const focusColor = pickColor(shadow.focus);
      if (focusColor) {
        semanticOverrides.focus = { ring: focusColor, halo: focusColor };
      }
    }
  }

  if (!Object.keys(semanticOverrides).length && legacyColors) {
    const accentOverrides: Record<string, string> = {};

    const primary = pickColor(legacyColors.primary);
    const accent = pickColor(legacyColors.accent) ?? primary;

    if (accent) accentOverrides.primary = accent;
    if (primary && accent) accentOverrides.secondary = primary;

    if (Object.keys(accentOverrides).length > 0) {
      semanticOverrides.accent = accentOverrides;
    }
  }

  if (Object.keys(semanticOverrides).length > 0) {
    overrides.semantic = semanticOverrides as SemanticTokenGroup;
  }

  return Object.keys(overrides).length > 0 ? overrides : null;
}

function mergeTokenBundle(base: TokenBundle, overrides: Partial<TokenBundle>): TokenBundle {
  if (!overrides || Object.keys(overrides).length === 0) {
    return base;
  }

  const merged = deepClone(base);
  deepMerge(merged as unknown as Record<string, unknown>, overrides as unknown as Record<string, unknown>);
  return merged;
}

function deriveThemeDensity(theme: ThemeRecord | null): 'compact' | 'cozy' | 'comfortable' | null {
  if (!theme) {
    return null;
  }

  const spacingTokens = parseThemePayload(theme.spacing_tokens);
  const spacingDensity =
    spacingTokens && typeof spacingTokens['density'] === 'string'
      ? (spacingTokens['density'] as string)
      : undefined;
  const candidate = spacingDensity ?? theme.layout_density ?? null;
  return candidate === 'compact' || candidate === 'cozy' || candidate === 'comfortable' ? candidate : null;
}

function parseThemePayload(input: unknown): Record<string, unknown> | null {
  if (!input) {
    return null;
  }

  if (typeof input === 'string') {
    try {
      const parsed = JSON.parse(input);
      return isRecord(parsed) ? (parsed as Record<string, unknown>) : null;
    } catch {
      return null;
    }
  }

  return isRecord(input) ? (input as Record<string, unknown>) : null;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function pickColor(value: unknown): string | undefined {
  return typeof value === 'string' && value.trim() !== '' ? value : undefined;
}

function deepMerge(target: Record<string, unknown>, source: Record<string, unknown>): void {
  Object.entries(source).forEach(([key, value]) => {
    if (value === undefined || value === null) {
      return;
    }

    if (isRecord(value)) {
      if (!isRecord(target[key])) {
        target[key] = {};
      }
      deepMerge(target[key] as Record<string, unknown>, value as Record<string, unknown>);
    } else {
      target[key] = value;
    }
  });
}

function resolveToken(bundle: TokenBundle, path: string): unknown {
  const visited = new Set<string>();

  let currentPath =
    path.startsWith('core.') || path.startsWith('semantic.') || path.startsWith('component.')
      ? path
      : path.startsWith('color.') || path.startsWith('space.') || path.startsWith('type.')
      ? `core.${path}`
      : path;

  while (currentPath && !visited.has(currentPath)) {
    visited.add(currentPath);
    const value = currentPath.split('.').reduce<unknown>((acc, segment) => {
      if (acc && typeof acc === 'object' && segment in acc) {
        return (acc as Record<string, unknown>)[segment];
      }
      return undefined;
    }, bundle as unknown);

    if (typeof value === 'string' && value.includes('.')) {
      currentPath =
        value.startsWith('core.') || value.startsWith('semantic.') || value.startsWith('component.')
          ? value
          : value.startsWith('color.') || value.startsWith('space.') || value.startsWith('type.')
          ? `core.${value}`
          : value;
      continue;
    }

    return value;
  }

  return undefined;
}

function applyTokenUpdate<T>(source: T, path: string, value: string): T {
  const clone = deepClone(source);
  setPath(clone as unknown as Record<string, unknown>, path.split('.'), value);
  return clone;
}

function deepClone<T>(input: T): T {
  return JSON.parse(JSON.stringify(input ?? {}));
}

function setPath(target: Record<string, unknown>, segments: string[], value: unknown) {
  if (segments.length === 0) {
    return;
  }

  const [head, ...rest] = segments;
  if (rest.length === 0) {
    target[head] = value;
    return;
  }

  if (!target[head] || typeof target[head] !== 'object') {
    target[head] = {};
  } else {
    target[head] = { ...(target[head] as Record<string, unknown>) };
  }

  setPath(target[head] as Record<string, unknown>, rest, value);
}

function hasNestedKeys(object: Record<string, unknown>): boolean {
  return Object.values(object).some((value) => {
    if (value && typeof value === 'object') {
      return hasNestedKeys(value as Record<string, unknown>);
    }
    return true;
  });
}

