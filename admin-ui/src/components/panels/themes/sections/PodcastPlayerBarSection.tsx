/**
 * Podcast Player Bar Section
 * Settings for podcast player bar appearance
 */

import { useState } from 'react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { PodaColorPicker } from '../../../controls/PodaColorPicker';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './widget-button-section.module.css';

interface PodcastPlayerBarSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function PodcastPlayerBarSection({
  uiState,
  onFieldChange,
  activeColor
}: PodcastPlayerBarSectionProps): JSX.Element {
  const playerBackground = (uiState['podcast-player-background'] as string) ?? 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)';
  const playerBorderColor = (uiState['podcast-player-border-color'] as string) ?? 'rgba(255, 255, 255, 0.2)';
  const playerBorderWidth = (uiState['podcast-player-border-width'] as number) ?? 1;
  const playerShadowEnabled = (uiState['podcast-player-shadow-enabled'] as boolean) ?? true;
  const playerShadowDepth = (uiState['podcast-player-shadow-depth'] as number) ?? 16;
  const playerTextColor = (uiState['podcast-player-text-color'] as string) ?? '#ffffff';

  return (
    <div className={styles.section}>
      {/* Background */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Background</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Background</label>
          <BackgroundColorSwatch
            value={playerBackground}
            onChange={(value) => onFieldChange('podcast-player-background', value)}
            label="Podcast player background"
          />
        </div>
      </div>

      {/* Border */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Border</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Color</label>
          <PodaColorPicker
            value={playerBorderColor}
            onChange={(value) => onFieldChange('podcast-player-border-color', value)}
            solidOnly
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Width</label>
          <SliderInput
            value={playerBorderWidth}
            min={0}
            max={8}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('podcast-player-border-width', value)}
          />
        </div>
      </div>

      {/* Shadow */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Shadow</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Enable Shadow</label>
          <div className={styles.toggleGroup}>
            <button
              type="button"
              className={`${styles.toggleButton} ${playerShadowEnabled ? styles.active : ''}`}
              onClick={() => onFieldChange('podcast-player-shadow-enabled', !playerShadowEnabled)}
            >
              {playerShadowEnabled ? 'Enabled' : 'Disabled'}
            </button>
          </div>
        </div>

        {playerShadowEnabled && (
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Shadow Depth</label>
            <SliderInput
              value={playerShadowDepth}
              min={0}
              max={50}
              step={1}
              unit="px"
              onChange={(value) => onFieldChange('podcast-player-shadow-depth', value)}
            />
          </div>
        )}
      </div>

      {/* Text Color */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Text</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Text Color</label>
          <PodaColorPicker
            value={playerTextColor}
            onChange={(value) => onFieldChange('podcast-player-text-color', value)}
            solidOnly
          />
        </div>
      </div>
    </div>
  );
}

