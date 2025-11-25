/**
 * Theme Library View
 * Browse and select themes
 */

import { useMemo, useState } from 'react';
import { Plus, Sparkle, MagnifyingGlass, GridFour, List, X } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../../api/types';
import type { ThemeLibraryResult } from '../../../api/themes';
import type { TabColorTheme } from '../../layout/tab-colors';
import { usePodcastThemePrompt } from '../../../hooks/usePodcastThemePrompt';
import { PodcastThemeGeneratorModal } from './PodcastThemeGeneratorModal';
import { ThemePreviewCard } from '../ThemePreviewCard';
import styles from './theme-library-view.module.css';

interface ThemeLibraryViewProps {
  themeLibrary: ThemeLibraryResult | undefined;
  activeTheme: ThemeRecord | null;
  onSelectTheme: (theme: ThemeRecord) => void;
  onApplyTheme?: (theme: ThemeRecord) => void;
  onCreateNew: () => void;
  onDeleteTheme: (theme: ThemeRecord) => void;
  activeColor: TabColorTheme;
}

type ViewMode = 'grid' | 'list';
type FilterTab = 'all' | 'my' | 'system';

export function ThemeLibraryView({
  themeLibrary,
  activeTheme,
  onSelectTheme,
  onApplyTheme,
  onCreateNew,
  onDeleteTheme,
  activeColor
}: ThemeLibraryViewProps): JSX.Element {
  const [searchQuery, setSearchQuery] = useState('');
  const [viewMode, setViewMode] = useState<ViewMode>('grid');
  const [filterTab, setFilterTab] = useState<FilterTab>('all');

  // Separate active theme from others
  const { activeUserTheme, otherUserThemes } = useMemo(() => {
    const themes = [...(themeLibrary?.user ?? [])];
    if (activeTheme && activeTheme.user_id !== null) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex >= 0) {
        const [active] = themes.splice(activeIndex, 1);
        return { activeUserTheme: active, otherUserThemes: themes };
      }
    }
    return { activeUserTheme: null, otherUserThemes: themes };
  }, [themeLibrary?.user, activeTheme]);

  // Separate active theme from others
  const { activeSystemTheme, otherSystemThemes } = useMemo(() => {
    const themes = [...(themeLibrary?.system ?? [])];
    if (activeTheme && activeTheme.user_id === null) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex >= 0) {
        const [active] = themes.splice(activeIndex, 1);
        return { activeSystemTheme: active, otherSystemThemes: themes };
      }
    }
    return { activeSystemTheme: null, otherSystemThemes: themes };
  }, [themeLibrary?.system, activeTheme]);

  // Filter themes based on search and tab
  const filteredThemes = useMemo(() => {
    let themes: ThemeRecord[] = [];
    
    if (filterTab === 'all') {
      themes = [...otherUserThemes, ...otherSystemThemes];
    } else if (filterTab === 'my') {
      themes = [...otherUserThemes];
    } else if (filterTab === 'system') {
      themes = [...otherSystemThemes];
    }

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      themes = themes.filter(theme => 
        theme.name?.toLowerCase().includes(query) ||
        theme.categories?.some((cat: string) => cat.toLowerCase().includes(query)) ||
        theme.tags?.some((tag: string) => tag.toLowerCase().includes(query))
      );
    }

    return themes;
  }, [otherUserThemes, otherSystemThemes, filterTab, searchQuery]);

  const {
    openGenerator,
    closeGenerator,
    isGeneratorOpen,
    generatorProps,
  } = usePodcastThemePrompt();

  const hasPodcastData = Boolean(generatorProps.coverImageUrl);

  return (
    <>
      <div className={styles.container}>
        <header className={styles.header}>
          <div>
            <h2>Themes</h2>
            <p>Manage and customize your page themes</p>
          </div>
          <div className={styles.headerActions}>
            {hasPodcastData && (
              <button
                type="button"
                className={styles.podcastButton}
                onClick={openGenerator}
                style={{
                  '--button-bg': activeColor.primary,
                  '--button-color': activeColor.text,
                  '--button-border': activeColor.border
                } as React.CSSProperties}
              >
                <Sparkle aria-hidden="true" size={16} weight="regular" />
                Generate from Podcast
              </button>
            )}
            <button
              type="button"
              className={styles.createButton}
              onClick={onCreateNew}
              style={{
                '--button-bg': activeColor.primary,
                '--button-color': activeColor.text,
                '--button-border': activeColor.border
              } as React.CSSProperties}
            >
              <Plus aria-hidden="true" size={16} weight="regular" />
              New Theme
            </button>
          </div>
        </header>

        {/* Search and Filter Bar */}
        <div className={styles.searchFilterBar}>
          <div className={styles.searchWrapper}>
            <MagnifyingGlass className={styles.searchIcon} aria-hidden="true" size={16} weight="regular" />
            <input
              type="text"
              className={styles.searchInput}
              placeholder="Search themes..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            {searchQuery && (
              <button
                type="button"
                className={styles.clearSearch}
                onClick={() => setSearchQuery('')}
                aria-label="Clear search"
              >
                <X aria-hidden="true" size={14} weight="regular" />
              </button>
            )}
          </div>
          <div className={styles.viewModeToggle}>
            <button
              type="button"
              className={`${styles.viewModeButton} ${viewMode === 'grid' ? styles.viewModeButtonActive : ''}`}
              onClick={() => setViewMode('grid')}
              aria-label="Grid view"
              title="Grid view"
            >
              <GridFour aria-hidden="true" size={16} weight="regular" />
            </button>
            <button
              type="button"
              className={`${styles.viewModeButton} ${viewMode === 'list' ? styles.viewModeButtonActive : ''}`}
              onClick={() => setViewMode('list')}
              aria-label="List view"
              title="List view"
            >
              <List aria-hidden="true" size={16} weight="regular" />
            </button>
          </div>
        </div>

        {/* Filter Tabs */}
        <div className={styles.filterTabs}>
          <button
            type="button"
            className={`${styles.filterTab} ${filterTab === 'all' ? styles.filterTabActive : ''}`}
            onClick={() => setFilterTab('all')}
          >
            All
          </button>
          {otherUserThemes.length > 0 && (
            <button
              type="button"
              className={`${styles.filterTab} ${filterTab === 'my' ? styles.filterTabActive : ''}`}
              onClick={() => setFilterTab('my')}
            >
              My Themes ({otherUserThemes.length})
            </button>
          )}
          {otherSystemThemes.length > 0 && (
            <button
              type="button"
              className={`${styles.filterTab} ${filterTab === 'system' ? styles.filterTabActive : ''}`}
              onClick={() => setFilterTab('system')}
            >
              System ({otherSystemThemes.length})
            </button>
          )}
        </div>

      {/* Active Theme - Full Width Row */}
      {(activeUserTheme || activeSystemTheme) && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Active Theme</h3>
          <div className={styles.activeThemeRow}>
            <ThemePreviewCard
              key={activeUserTheme?.id || activeSystemTheme?.id}
              theme={activeUserTheme || activeSystemTheme!}
              selected={true}
              onSelect={() => onSelectTheme(activeUserTheme || activeSystemTheme!)}
              primaryActionLabel={undefined}
              secondaryActionLabel="Edit"
              onSecondaryAction={() => onSelectTheme(activeUserTheme || activeSystemTheme!)}
              disabled={false}
              tertiaryActions={activeUserTheme ? {
                onDelete: () => onDeleteTheme(activeUserTheme)
              } : undefined}
            />
          </div>
        </section>
      )}

      {/* Filtered Themes */}
      {filteredThemes.length > 0 && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>
            {filterTab === 'all' ? 'All Themes' : filterTab === 'my' ? 'Your Themes' : 'Theme Library'}
            {searchQuery && ` (${filteredThemes.length} found)`}
          </h3>
          <div className={viewMode === 'grid' ? styles.themeGrid : styles.themeList} data-view-mode={viewMode}>
            {filteredThemes.map(theme => {
              const isUserTheme = theme.user_id !== null;
              return (
                <ThemePreviewCard
                  key={theme.id}
                  theme={theme}
                  selected={false}
                  onSelect={() => onSelectTheme(theme)}
                  primaryActionLabel={onApplyTheme ? "Apply" : "Edit"}
                  onPrimaryAction={onApplyTheme ? () => onApplyTheme(theme) : () => onSelectTheme(theme)}
                  secondaryActionLabel="Edit"
                  onSecondaryAction={() => onSelectTheme(theme)}
                  disabled={false}
                  tertiaryActions={isUserTheme ? {
                    onDelete: () => onDeleteTheme(theme)
                  } : undefined}
                />
              );
            })}
          </div>
        </section>
      )}

      {filteredThemes.length === 0 && !activeUserTheme && !activeSystemTheme && (
        <div className={styles.empty}>
          <p>
            {searchQuery 
              ? `No themes found matching "${searchQuery}". Try a different search term.`
              : 'No themes available. Create your first theme to get started.'}
          </p>
          {!searchQuery && (
            <button
              type="button"
              className={styles.createButton}
              onClick={onCreateNew}
              style={{
                '--button-bg': activeColor.primary,
                '--button-color': activeColor.text,
                '--button-border': activeColor.border
              } as React.CSSProperties}
            >
              <Plus aria-hidden="true" size={16} weight="regular" />
              Create Theme
            </button>
          )}
        </div>
      )}
      </div>

      {/* Podcast Theme Generator Modal */}
      <PodcastThemeGeneratorModal
        coverImageUrl={generatorProps.coverImageUrl}
        podcastName={generatorProps.podcastName}
        podcastDescription={generatorProps.podcastDescription}
        isOpen={isGeneratorOpen}
        onClose={closeGenerator}
      />
    </>
  );
}

