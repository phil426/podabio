import { useEffect, useMemo, useState } from 'react';
import clsx from 'clsx';
import { MagnifyingGlass, X } from '@phosphor-icons/react';

import { useThemeLibraryQuery, useCloneThemeMutation, useRenameThemeMutation, useDeleteThemeMutation } from '../../api/themes';
import { usePageSnapshot, updatePageThemeId } from '../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import type { ThemeRecord } from '../../api/types';
import type { TokenBundle } from '../../design-system/tokens';
import { useThemeInspector } from '../../state/themeInspector';
import { StyleGuidePreview } from './StyleGuidePreview';
import { ThemeSwatch, extractThemeColors } from './ThemeSwatch';
import { ConfirmDeleteDialog } from './themes/ConfirmDeleteDialog';
import styles from './theme-library-panel.module.css';

interface StatusMessage {
  tone: 'success' | 'error';
  message: string;
}

export function ThemeLibraryPanel(): JSX.Element {
  const { data, isLoading, isError, error } = useThemeLibraryQuery();
  const cloneMutation = useCloneThemeMutation();
  const renameMutation = useRenameThemeMutation();
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const [status, setStatus] = useState<StatusMessage | null>(null);
  const [isApplying, setIsApplying] = useState(false);

  const currentThemeId = snapshot?.page?.theme_id ?? null;

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  const systemThemes: ThemeRecord[] = data?.system ?? [];
  const userThemes: ThemeRecord[] = data?.user ?? [];

  const [searchQuery, setSearchQuery] = useState('');
  const deleteMutation = useDeleteThemeMutation();
  const { setThemeInspectorVisible } = useThemeInspector();
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [themeToDelete, setThemeToDelete] = useState<ThemeRecord | null>(null);

  const handleApplyTheme = async (theme: ThemeRecord) => {
    try {
      setIsApplying(true);
      
      // Extract page background from theme
      let pageBackground: string | null | undefined = theme.page_background;
      
      // If page_background is not set, try to extract from color_tokens
      if (!pageBackground && theme.color_tokens) {
        try {
          const colorTokens = typeof theme.color_tokens === 'string' 
            ? JSON.parse(theme.color_tokens) 
            : theme.color_tokens;
          
          // Try semantic.surface.canvas path
          if (colorTokens?.semantic?.surface?.canvas) {
            pageBackground = colorTokens.semantic.surface.canvas as string;
          }
          // Try semantic.surface.background path
          else if (colorTokens?.semantic?.surface?.background) {
            pageBackground = colorTokens.semantic.surface.background as string;
          }
          // Try gradient.page path
          else if (colorTokens?.gradient?.page) {
            pageBackground = colorTokens.gradient.page as string;
          }
        } catch (e) {
          // If parsing fails, use null to let theme value be used
          console.warn('Failed to parse color_tokens:', e);
        }
      }
      
      // Parse widget_styles if it's a string
      let widgetStyles: Record<string, unknown> | string | null = null;
      if (theme.widget_styles) {
        if (typeof theme.widget_styles === 'string') {
          try {
            widgetStyles = JSON.parse(theme.widget_styles);
          } catch (e) {
            console.warn('Failed to parse widget_styles:', e);
            widgetStyles = theme.widget_styles;
          }
        } else {
          widgetStyles = theme.widget_styles;
        }
      }
      
      // Extract widget background (prioritize direct column over color_tokens)
      const widgetBackground = theme.widget_background ?? null;
      
      // Use updatePageThemeId with all theme fields
      // Pass null to clear page-level overrides (so theme values are used)
      await updatePageThemeId(theme.id, {
        page_background: pageBackground ?? null,
        widget_background: widgetBackground,
        widget_border_color: theme.widget_border_color ?? null,
        page_primary_font: theme.page_primary_font ?? null,
        page_secondary_font: theme.page_secondary_font ?? null,
        widget_primary_font: theme.widget_primary_font ?? null,
        widget_secondary_font: theme.widget_secondary_font ?? null,
        widget_styles: widgetStyles,
        spatial_effect: theme.spatial_effect ?? null
      });
      
      // Invalidate and refetch queries to update the UI
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatus({ tone: 'success', message: `Theme "${theme.name}" applied.` });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to apply theme.' });
    } finally {
      setIsApplying(false);
    }
  };

  const handleCloneTheme = async (theme: ThemeRecord) => {
    const suggestedName = `${theme.name} Copy`;
    const name = window.prompt('Name for your new theme copy:', suggestedName);
    if (name === null) return;
    try {
      await cloneMutation.mutateAsync({ themeId: theme.id, name: name.trim() === '' ? suggestedName : name.trim() });
      setStatus({ tone: 'success', message: 'Theme cloned to your library.' });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to clone theme.' });
    }
  };

  const handleRenameTheme = async (theme: ThemeRecord) => {
    const name = window.prompt('Rename theme:', theme.name);
    if (name === null) return;
    const trimmed = name.trim();
    if (!trimmed) {
      setStatus({ tone: 'error', message: 'Theme name cannot be empty.' });
      return;
    }
    try {
      await renameMutation.mutateAsync({ themeId: theme.id, name: trimmed });
      setStatus({ tone: 'success', message: 'Theme renamed.' });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to rename theme.' });
    }
  };

  const handleDeleteTheme = (theme: ThemeRecord) => {
    setThemeToDelete(theme);
    setDeleteDialogOpen(true);
  };

  const handleConfirmDelete = async () => {
    if (!themeToDelete) return;

    try {
      await deleteMutation.mutateAsync(themeToDelete.id);
      setStatus({ tone: 'success', message: 'Theme deleted.' });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to delete theme.' });
    } finally {
      setThemeToDelete(null);
      setDeleteDialogOpen(false);
    }
  };

  const handleEditTheme = (theme: ThemeRecord) => {
    handleApplyTheme(theme);
    setThemeInspectorVisible(true);
  };

  // Filter themes based on search query
  const filteredSystemThemes = useMemo(() => {
    if (!searchQuery.trim()) return systemThemes;
    const query = searchQuery.toLowerCase();
    return systemThemes.filter((theme) => theme.name.toLowerCase().includes(query));
  }, [systemThemes, searchQuery]);

  const filteredUserThemes = useMemo(() => {
    if (!searchQuery.trim()) return userThemes;
    const query = searchQuery.toLowerCase();
    return userThemes.filter((theme) => theme.name.toLowerCase().includes(query));
  }, [userThemes, searchQuery]);


  if (isLoading) {
    return (
      <section className={styles.panel} aria-label="Theme library">
        <p>Loading theme library…</p>
      </section>
    );
  }

  if (isError) {
    return (
      <section className={styles.panel} aria-label="Theme library">
        <p className={styles.error}>{error instanceof Error ? error.message : 'Unable to load themes.'}</p>
      </section>
    );
  }

  // Combine all themes for a simple list, with active theme first
  const allThemes = useMemo(() => {
    const combined = [...filteredUserThemes, ...filteredSystemThemes];
    if (currentThemeId) {
      const activeIndex = combined.findIndex(t => t.id === currentThemeId);
      if (activeIndex > 0) {
        const [active] = combined.splice(activeIndex, 1);
        combined.unshift(active);
      }
    }
    return combined;
  }, [filteredUserThemes, filteredSystemThemes, currentThemeId]);

  // Extract current colors/values for the active theme
  // PRIORITY: Theme data (from Edit Theme Panel) > Snapshot tokens (current page state)
  const currentValues = useMemo(() => {
    // Find the active theme from theme library
    const activeTheme = currentThemeId ? [...userThemes, ...systemThemes].find(t => t.id === currentThemeId) : null;
    
    // If we have theme data, prioritize it over snapshot tokens
    if (activeTheme) {
      const themeColors = extractThemeColors(activeTheme);
      if (themeColors && themeColors.length > 0) {
        return themeColors;
      }
    }
    
    // Fallback to snapshot tokens if theme data not available
    if (!snapshot?.tokens) return undefined;
    const tokens = snapshot.tokens as TokenBundle;
    
    // Helper to extract color(s) from a value - returns gradient object or solid color string
    const extractColorValue = (value: string | null | undefined): string | { gradient: string } | null => {
      if (!value) return null;
      
      // Check if it's a solid hex color
      if (/^#([0-9a-fA-F]{3}){1,2}$/.test(value)) {
        return value;
      }
      
      // Check if it's a gradient - return the gradient string as an object
      if (value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient')) {
        return { gradient: value };
      }
      
      return null;
    };
    
    const extractFromToken = (path: string): string | { gradient: string } | null => {
      const parts = path.split('.');
      let current: any = tokens;
      for (const part of parts) {
        if (current && typeof current === 'object' && part in current) {
          current = current[part];
        } else {
          return null;
        }
      }
      if (typeof current === 'string') {
        return extractColorValue(current);
      }
      return null;
    };

    const extractFromPageValue = (value: string | null | undefined): string | { gradient: string } | null => {
      return extractColorValue(value);
    };

    const values: Array<string | { gradient: string }> = [];
    
    // Helper to get a single color value from token path with fallback
    const getSingleValue = (paths: string[], fallback: string): string | { gradient: string } => {
      for (const path of paths) {
        const result = extractFromToken(path);
        if (result) {
          return result;
        }
      }
      return fallback;
    };
    
    // Helper to check if value already exists (comparing strings and gradient objects)
    const valueExists = (value: string | { gradient: string }, existing: Array<string | { gradient: string }>): boolean => {
      if (typeof value === 'string') {
        return existing.some(v => v === value || (typeof v === 'object' && 'gradient' in v && v.gradient === value));
      }
      if (typeof value === 'object' && 'gradient' in value) {
        return existing.some(v => 
          (typeof v === 'object' && 'gradient' in v && v.gradient === value.gradient) ||
          (typeof v === 'string' && v === value.gradient)
        );
      }
      return false;
    };
    
    // 1. Page Name color (heading font color)
    const headingColor = getSingleValue(
      ['core.typography.color.heading', 'typography.color.heading'],
      getSingleValue(['semantic.text.primary'], '#0f172a')
    );
    if (headingColor && !valueExists(headingColor, values)) {
      values.push(headingColor);
    }
    
    // 2. Bio Text color (body font color)
    const bodyColor = getSingleValue(
      ['core.typography.color.body', 'typography.color.body'],
      getSingleValue(['semantic.text.primary'], '#0f172a')
    );
    if (bodyColor && !valueExists(bodyColor, values) && values.length < 4) {
      values.push(bodyColor);
    }
    
    // 3. Icon Color
    const iconColor = getSingleValue(
      ['iconography.color', 'core.iconography.color'],
      getSingleValue(['semantic.accent.primary'], '#6b7280')
    );
    if (iconColor && !valueExists(iconColor, values) && values.length < 4) {
      values.push(iconColor);
    }
    
    // 4. Background color (page background) - can be gradient
    const pageBgTokenResult = extractFromToken('semantic.surface.canvas');
    const pageBgPageResult = extractFromPageValue(snapshot?.page?.page_background);
    const pageBg = pageBgTokenResult || pageBgPageResult || '#ffffff';
    
    if (pageBg && !valueExists(pageBg, values) && values.length < 4) {
      values.push(pageBg);
    }
    
    // 5. Widget title text color (fallback to semantic text if not found)
    if (values.length < 4) {
      const widgetTitleColor = getSingleValue(['semantic.text.primary'], '#0f172a');
      if (widgetTitleColor && !valueExists(widgetTitleColor, values)) {
        values.push(widgetTitleColor);
      }
    }
    
    // 6. Widget background color - can be gradient
    if (values.length < 4) {
      const widgetBgTokenResult = extractFromToken('semantic.surface.base');
      const widgetBgPageResult = extractFromPageValue(snapshot?.page?.widget_background);
      const widgetBg = widgetBgTokenResult || widgetBgPageResult || '#ffffff';
      
      if (widgetBg && !valueExists(widgetBg, values)) {
        values.push(widgetBg);
      }
    }
    
    // Ensure we have at least some values
    if (values.length === 0) {
      return ['#0f172a', '#0f172a', '#6b7280', '#ffffff'];
    }
    
    return values.slice(0, 4); // Max 4 values for overlapping circles
  }, [currentThemeId, userThemes, systemThemes, snapshot?.tokens, snapshot?.page?.page_background, snapshot?.page?.widget_background]);

  return (
    <section className={styles.panel} aria-label="Theme library">
      <header className={styles.header}>
        <div>
          <h3>Themes</h3>
        </div>
        {status && <span className={clsx(styles.statusChip, styles[`statusChip_${status.tone}`])}>{status.message}</span>}
      </header>

      {/* Search Bar */}
      <div className={styles.searchSection}>
        <div className={styles.searchInputWrapper}>
          <MagnifyingGlass className={styles.searchIcon} aria-hidden="true" size={16} weight="regular" />
          <input
            type="text"
            className={styles.searchInput}
            placeholder="Search themes..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            aria-label="Search themes"
          />
          {searchQuery && (
          <button
            type="button"
              className={styles.clearSearchButton}
              onClick={() => setSearchQuery('')}
              aria-label="Clear search"
            >
              <X aria-hidden="true" size={16} weight="regular" />
          </button>
          )}
        </div>
      </div>

      {/* Simple Theme List */}
      <div className={styles.themeList}>
        {allThemes.length === 0 ? (
          <p className={styles.noResults}>No themes match your search.</p>
        ) : (
          allThemes.map((theme) => {
            const isCurrent = currentThemeId === theme.id;
            return (
              <button
                key={theme.id}
                type="button"
                className={clsx(styles.themeListItem, isCurrent && styles.themeListItemActive)}
                onClick={() => handleApplyTheme(theme)}
                disabled={isApplying}
              >
              <ThemeSwatch
                theme={theme}
                selected={isCurrent}
                isActive={isCurrent}
                onApply={() => handleApplyTheme(theme)}
                onEdit={() => handleEditTheme(theme)}
                onDuplicate={() => handleCloneTheme(theme)}
                onDelete={() => handleDeleteTheme(theme)}
                isUserTheme={userThemes.some(t => t.id === theme.id)}
                showActions={false}
                currentValues={isCurrent ? currentValues : undefined}
                displayName={isCurrent && snapshot?.page?.username ? `/${snapshot.page.username}` : undefined}
              />
              </button>
            );
          })
        )}
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDeleteDialog
        isOpen={deleteDialogOpen}
        onClose={() => {
          setDeleteDialogOpen(false);
          setThemeToDelete(null);
        }}
        onConfirm={handleConfirmDelete}
        title="Delete Theme"
        message={themeToDelete ? `Are you sure you want to delete "${themeToDelete.name}"? This cannot be undone.` : ''}
        confirmLabel="Delete"
        cancelLabel="Cancel"
      />
    </section>
  );
}

