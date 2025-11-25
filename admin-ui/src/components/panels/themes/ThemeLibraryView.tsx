/**
 * Theme Library View
 * Browse and select themes
 */

import { useMemo } from 'react';
import { Plus, Pencil, Sparkle } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../../api/types';
import type { ThemeLibraryResult } from '../../../api/themes';
import type { TabColorTheme } from '../../layout/tab-colors';
import { usePodcastThemePrompt } from '../../../hooks/usePodcastThemePrompt';
import { PodcastThemeGeneratorModal } from './PodcastThemeGeneratorModal';
import { ThemePreviewCard } from '../ThemePreviewCard';
import { ThemeInfoPanel } from './ThemeInfoPanel';
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

export function ThemeLibraryView({
  themeLibrary,
  activeTheme,
  onSelectTheme,
  onApplyTheme,
  onCreateNew,
  onDeleteTheme,
  activeColor
}: ThemeLibraryViewProps): JSX.Element {
  // Separate active theme from user themes
  const { activeUserTheme, otherUserThemes } = useMemo(() => {
    const themes = [...(themeLibrary?.user ?? [])];
    if (activeTheme && activeTheme.user_id) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex >= 0) {
        const [active] = themes.splice(activeIndex, 1);
        return { activeUserTheme: active, otherUserThemes: themes };
      }
    }
    return { activeUserTheme: null, otherUserThemes: themes };
  }, [themeLibrary?.user, activeTheme]);

  // Separate active theme from system themes
  const { activeSystemTheme, otherSystemThemes } = useMemo(() => {
    const themes = [...(themeLibrary?.system ?? [])];
    if (activeTheme && !activeTheme.user_id) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex >= 0) {
        const [active] = themes.splice(activeIndex, 1);
        return { activeSystemTheme: active, otherSystemThemes: themes };
      }
    }
    return { activeSystemTheme: null, otherSystemThemes: themes };
  }, [themeLibrary?.system, activeTheme]);

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
        <div className={styles.mainContent}>
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

      {/* Active Theme Section */}
      {(activeUserTheme || activeSystemTheme) && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Active Theme</h3>
          <div className={styles.themeGrid}>
            <ThemePreviewCard
              key={activeTheme?.id}
              theme={activeUserTheme || activeSystemTheme || activeTheme!}
              selected={true}
              onSelect={() => {
                const theme = activeUserTheme || activeSystemTheme || activeTheme!;
                if (onApplyTheme) {
                  onApplyTheme(theme);
                }
                onSelectTheme(theme);
              }}
              tertiaryActions={activeUserTheme ? {
                onDelete: () => onDeleteTheme(activeUserTheme)
              } : undefined}
            />
          </div>
        </section>
      )}

      {otherUserThemes.length > 0 && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Your Themes</h3>
          <div className={styles.themeGrid}>
            {otherUserThemes.map(theme => (
              <ThemePreviewCard
                key={theme.id}
                theme={theme}
                selected={false}
                onSelect={() => {
                  if (onApplyTheme) {
                    onApplyTheme(theme);
                  }
                  onSelectTheme(theme);
                }}
                tertiaryActions={{
                  onDelete: () => onDeleteTheme(theme)
                }}
              />
            ))}
          </div>
        </section>
      )}

      {otherSystemThemes.length > 0 && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Theme Library</h3>
          <div className={styles.themeGrid}>
            {otherSystemThemes.map(theme => (
              <ThemePreviewCard
                key={theme.id}
                theme={theme}
                selected={false}
                onSelect={() => {
                  if (onApplyTheme) {
                    onApplyTheme(theme);
                  }
                  onSelectTheme(theme);
                }}
              />
            ))}
          </div>
        </section>
      )}

      {otherSystemThemes.length === 0 && otherUserThemes.length === 0 && !activeTheme && (
        <div className={styles.empty}>
          <p>No themes available. Create your first theme to get started.</p>
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
        </div>
      )}
        </div>

        {/* Info Panel */}
        <div className={styles.infoPanel}>
          <ThemeInfoPanel activeColor={activeColor} />
        </div>
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

