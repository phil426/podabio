/**
 * Theme Library View
 * Browse and select themes
 */

import { useMemo } from 'react';
import { Sparkle } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../../api/types';
import type { ThemeLibraryResult } from '../../../api/themes';
import type { TabColorTheme } from '../../layout/tab-colors';
// import { usePodcastThemePrompt } from '../../../hooks/usePodcastThemePrompt';
// import { PodcastThemeGeneratorModal } from './PodcastThemeGeneratorModal';
import { ThemePreviewCard } from '../ThemePreviewCard';
import styles from './theme-library-view.module.css';

interface ThemeLibraryViewProps {
  themeLibrary: ThemeLibraryResult | undefined;
  activeTheme: ThemeRecord | null;
  onSelectTheme: (theme: ThemeRecord) => void;
  onApplyTheme?: (theme: ThemeRecord) => void;
  activeColor: TabColorTheme;
}

export function ThemeLibraryView({
  themeLibrary,
  activeTheme,
  onSelectTheme,
  onApplyTheme,
  activeColor
}: ThemeLibraryViewProps): JSX.Element {
  // Separate active theme from system themes (user themes are retired)
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

  // const {
  //   openGenerator,
  //   closeGenerator,
  //   isGeneratorOpen,
  //   generatorProps,
  // } = usePodcastThemePrompt();

  const hasPodcastData = false; // logic removed

  return (
    <>
      <div className={styles.container}>
        <div className={styles.mainContent}>
          <header className={styles.header}>
            <div>
              <h2>Themes</h2>
              <p>Select a theme to apply and customize</p>
            </div>
            <div className={styles.headerActions}>
              {/* Theme Wizard button removed */}
            </div>
          </header>

          {/* Active Theme Section */}
          {activeSystemTheme && (
            <section className={styles.section}>
              <h3 className={styles.sectionTitle}>Active Theme</h3>
              <div className={styles.themeGrid}>
                <ThemePreviewCard
                  key={activeTheme?.id}
                  theme={activeSystemTheme || activeTheme!}
                  selected={true}
                  onSelect={() => {
                    const theme = activeSystemTheme || activeTheme!;
                    // Don't call onApplyTheme for the active theme - it's already applied
                    // Just open the editor
                    onSelectTheme(theme);
                  }}
                />
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

          {otherSystemThemes.length === 0 && !activeTheme && (
            <div className={styles.empty}>
              <p>No themes available.</p>
            </div>
          )}
        </div>
      </div>

      {/* Podcast Theme Generator Modal removed */}
    </>
  );
}

