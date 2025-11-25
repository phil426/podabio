/**
 * Theme Info Panel
 * Information panel for the theme chooser page
 */

import { Info, Palette, Sparkle, Pencil } from '@phosphor-icons/react';
import type { TabColorTheme } from '../../layout/tab-colors';
import styles from './theme-info-panel.module.css';

interface ThemeInfoPanelProps {
  activeColor: TabColorTheme;
}

export function ThemeInfoPanel({ activeColor }: ThemeInfoPanelProps): JSX.Element {
  return (
    <div 
      className={styles.panel}
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
      } as React.CSSProperties}
    >
      <div className={styles.content}>
        <header className={styles.header}>
          <div className={styles.headerIcon}>
            <Info size={20} weight="regular" />
          </div>
          <h2 className={styles.title}>About Themes</h2>
        </header>

        <div className={styles.sections}>
          <div className={styles.section}>
            <div className={styles.sectionIcon}>
              <Palette size={16} weight="regular" />
            </div>
            <h3 className={styles.sectionTitle}>Selecting Themes</h3>
            <p className={styles.sectionContent}>
              Click any theme card to apply it to your page and open the theme editor. The active theme is highlighted at the top.
            </p>
          </div>

          <div className={styles.section}>
            <div className={styles.sectionIcon}>
              <Pencil size={16} weight="regular" />
            </div>
            <h3 className={styles.sectionTitle}>Customizing Themes</h3>
            <p className={styles.sectionContent}>
              Click a theme to open the editor where you can customize colors, fonts, backgrounds, and more. Changes are saved automatically.
            </p>
          </div>

          <div className={styles.section}>
            <div className={styles.sectionIcon}>
              <Sparkle size={16} weight="regular" />
            </div>
            <h3 className={styles.sectionTitle}>Creating Themes</h3>
            <p className={styles.sectionContent}>
              Create new themes from scratch or generate one automatically from your podcast artwork. Your custom themes are saved in "Your Themes".
            </p>
          </div>
        </div>

        <div className={styles.helpSection}>
          <div className={styles.helpHeader}>
            <Info size={16} weight="regular" />
            <h3 className={styles.helpTitle}>Quick Tips</h3>
          </div>
          <ul className={styles.helpList}>
            <li>System themes are pre-designed and cannot be deleted</li>
            <li>Your custom themes can be edited or deleted at any time</li>
            <li>Use the preview to see how your theme looks before applying</li>
          </ul>
        </div>
      </div>
    </div>
  );
}

