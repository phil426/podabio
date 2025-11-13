import { useEffect, useMemo, useState } from 'react';
import clsx from 'clsx';
import { LuSearch, LuX } from 'react-icons/lu';

import { useThemeLibraryQuery, useCloneThemeMutation, useRenameThemeMutation, useDeleteThemeMutation } from '../../api/themes';
import { usePageSnapshot, usePageSettingsMutation } from '../../api/page';
import type { ThemeRecord } from '../../api/types';
import { useThemeInspector } from '../../state/themeInspector';
import { StyleGuidePreview } from './StyleGuidePreview';
import { ThemeSwatch } from './ThemeSwatch';
import styles from './theme-library-panel.module.css';

interface StatusMessage {
  tone: 'success' | 'error';
  message: string;
}

export function ThemeLibraryPanel(): JSX.Element {
  const { data, isLoading, isError, error } = useThemeLibraryQuery();
  const { mutateAsync: applyTheme, isPending: isApplying } = usePageSettingsMutation();
  const cloneMutation = useCloneThemeMutation();
  const renameMutation = useRenameThemeMutation();
  const { data: snapshot } = usePageSnapshot();
  const [status, setStatus] = useState<StatusMessage | null>(null);

  const currentThemeId = snapshot?.page?.theme_id ?? null;

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  const systemThemes: ThemeRecord[] = data?.system ?? [];
  const userThemes: ThemeRecord[] = data?.user ?? [];
  const activeTheme: ThemeRecord | null = (() => {
    if (currentThemeId == null) {
      return systemThemes[0] ?? userThemes[0] ?? null;
    }
    const combined = [...userThemes, ...systemThemes];
    return combined.find((theme) => theme.id === currentThemeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
  })();

  const [systemOpen, setSystemOpen] = useState(true);
  const [userOpen, setUserOpen] = useState(true);
  const [openThemeId, setOpenThemeId] = useState<number | null>(null);
  const [isDragOver, setIsDragOver] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const deleteMutation = useDeleteThemeMutation();
  const { setThemeInspectorVisible } = useThemeInspector();

  const handleApplyTheme = async (theme: ThemeRecord) => {
    try {
      await applyTheme({ theme_id: String(theme.id) });
      setStatus({ tone: 'success', message: `Theme “${theme.name}” applied.` });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to apply theme.' });
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

  const handleDeleteTheme = async (theme: ThemeRecord) => {
    if (!window.confirm(`Are you sure you want to delete "${theme.name}"? This cannot be undone.`)) {
      return;
    }
    try {
      await deleteMutation.mutateAsync(theme.id);
      setStatus({ tone: 'success', message: 'Theme deleted.' });
    } catch (err) {
      setStatus({ tone: 'error', message: err instanceof Error ? err.message : 'Unable to delete theme.' });
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

  const handleDrop = async (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    
    const themeIdStr = e.dataTransfer.getData('text/plain');
    if (!themeIdStr) return;
    
    const themeId = Number.parseInt(themeIdStr, 10);
    if (Number.isNaN(themeId)) return;
    
    const allThemes = [...systemThemes, ...userThemes];
    const droppedTheme = allThemes.find((t) => t.id === themeId);
    
    if (droppedTheme) {
      await handleApplyTheme(droppedTheme);
      setThemeInspectorVisible(true);
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    setIsDragOver(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
  };

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
          <LuSearch className={styles.searchIcon} aria-hidden="true" />
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
              <LuX aria-hidden="true" />
          </button>
          )}
        </div>
      </div>

      {/* Active Theme Section */}
      <div className={styles.activeThemeSection}>
        <h4 className={styles.activeThemeLabel}>Active theme</h4>
        <div
          className={clsx(styles.activeThemeLanding, isDragOver && styles.dragOver)}
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
        >
        {activeTheme ? (
          <StyleGuidePreview
            theme={activeTheme}
            selected
            onSelect={() => {
              setThemeInspectorVisible(true);
            }}
            disabled={isApplying}
            isOpen={openThemeId === activeTheme.id}
            onToggle={() => setOpenThemeId(openThemeId === activeTheme.id ? null : activeTheme.id)}
            isFirst
            isLast
            isDraggable={false}
          />
        ) : (
          <div className={styles.landingPlaceholder}>
            <span>Drag a theme here to set as active</span>
          </div>
        )}
        </div>
      </div>

      {/* Your Themes Section */}
      <div className={styles.section}>
        <div className={styles.sectionHeaderRow}>
          <div className={styles.userHeaderLeft}>
            <h4>Your saved themes</h4>
            <span className={styles.count}>({filteredUserThemes.length})</span>
            <button
              type="button"
              className={styles.toggleButton}
              onClick={() => setUserOpen((open) => !open)}
              aria-expanded={userOpen}
              aria-controls="user-theme-grid"
            >
              {userOpen ? 'Hide' : 'Show'}
            </button>
          </div>
          <button
            type="button"
            className={styles.createButton}
            onClick={() => {
              if (!activeTheme) return;
              handleCloneTheme(activeTheme);
            }}
            disabled={cloneMutation.isPending || !activeTheme}
          >
            + Duplicate active
          </button>
        </div>
        {userThemes.length === 0 ? (
          <p className={styles.placeholder}>Clone a system theme to start your personal theme library.</p>
        ) : (
          userOpen && (
            <div className={clsx(styles.themeGrid, viewMode === 'list' && styles.listView)} id="user-theme-grid">
              {filteredUserThemes.length === 0 ? (
                <p className={styles.noResults}>No themes match your search.</p>
              ) : (
                filteredUserThemes.map((theme) => (
                  <ThemeSwatch
                    key={theme.id}
                    theme={theme}
                    selected={currentThemeId === theme.id}
                    isActive={currentThemeId === theme.id}
                    onApply={() => handleApplyTheme(theme)}
                    onEdit={() => handleEditTheme(theme)}
                    onDuplicate={() => handleCloneTheme(theme)}
                    onDelete={() => handleDeleteTheme(theme)}
                    isUserTheme
                  />
                ))
              )}
            </div>
          )
        )}
      </div>

      {/* System Themes Section */}
      <div className={styles.section}>
        <div className={styles.sectionHeaderRow}>
          <div>
            <h4>System themes</h4>
            <span className={styles.count}>({filteredSystemThemes.length})</span>
          </div>
          <button
            type="button"
            className={styles.toggleButton}
            onClick={() => setSystemOpen((open) => !open)}
            aria-expanded={systemOpen}
            aria-controls="system-theme-grid"
          >
            {systemOpen ? 'Hide' : 'Show'}
          </button>
        </div>
        {systemOpen && (
          <div className={clsx(styles.themeGrid, viewMode === 'list' && styles.listView)} id="system-theme-grid">
            {filteredSystemThemes.length === 0 ? (
              <p className={styles.noResults}>No themes match your search.</p>
            ) : (
              filteredSystemThemes.map((theme) => (
                <ThemeSwatch
                  key={theme.id}
                  theme={theme}
                  selected={currentThemeId === theme.id}
                  isActive={currentThemeId === theme.id}
                  onApply={() => handleApplyTheme(theme)}
                  onEdit={() => handleEditTheme(theme)}
                  onDuplicate={() => handleCloneTheme(theme)}
                  isUserTheme={false}
                />
              ))
            )}
            </div>
        )}
      </div>
    </section>
  );
}

