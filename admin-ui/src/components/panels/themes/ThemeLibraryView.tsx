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
  // Sort themes so active theme appears first
  const sortedUserThemes = useMemo(() => {
    const themes = [...(themeLibrary?.user ?? [])];
    if (activeTheme) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex > 0) {
        const [active] = themes.splice(activeIndex, 1);
        themes.unshift(active);
      }
    }
    return themes;
  }, [themeLibrary?.user, activeTheme]);

  // Sort themes so active theme appears first
  const sortedSystemThemes = useMemo(() => {
    const themes = [...(themeLibrary?.system ?? [])];
    if (activeTheme) {
      const activeIndex = themes.findIndex(t => t.id === activeTheme.id);
      if (activeIndex > 0) {
        const [active] = themes.splice(activeIndex, 1);
        themes.unshift(active);
      }
    }
    return themes;
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

      {sortedUserThemes.length > 0 && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Your Themes</h3>
          <div className={styles.themeGrid}>
            {sortedUserThemes.map(theme => (
              <ThemePreviewCard
                key={theme.id}
                theme={theme}
                selected={activeTheme?.id === theme.id}
                onSelect={() => onSelectTheme(theme)}
                primaryActionLabel={activeTheme?.id === theme.id ? undefined : onApplyTheme ? "Apply" : "Edit"}
                onPrimaryAction={activeTheme?.id === theme.id ? undefined : onApplyTheme ? () => onApplyTheme(theme) : () => onSelectTheme(theme)}
                secondaryActionLabel={activeTheme?.id === theme.id ? undefined : "Edit"}
                onSecondaryAction={activeTheme?.id === theme.id ? undefined : () => onSelectTheme(theme)}
                disabled={false}
                tertiaryActions={{
                  onDelete: () => onDeleteTheme(theme)
                }}
              />
            ))}
          </div>
        </section>
      )}

      {sortedSystemThemes.length > 0 && (
        <section className={styles.section}>
          <h3 className={styles.sectionTitle}>Theme Library</h3>
          <div className={styles.themeGrid}>
            {sortedSystemThemes.map(theme => (
              <ThemePreviewCard
                key={theme.id}
                theme={theme}
                selected={activeTheme?.id === theme.id}
                onSelect={() => onSelectTheme(theme)}
                primaryActionLabel={activeTheme?.id === theme.id ? undefined : onApplyTheme ? "Apply" : "Edit"}
                onPrimaryAction={activeTheme?.id === theme.id ? undefined : onApplyTheme ? () => onApplyTheme(theme) : () => onSelectTheme(theme)}
                secondaryActionLabel={activeTheme?.id === theme.id ? undefined : "Edit"}
                onSecondaryAction={activeTheme?.id === theme.id ? undefined : () => onSelectTheme(theme)}
                disabled={false}
              />
            ))}
          </div>
        </section>
      )}

      {sortedSystemThemes.length === 0 && sortedUserThemes.length === 0 && (
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

