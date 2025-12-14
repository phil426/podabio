/**
 * Page Background Section
 * Settings for page background and vertical spacing only
 */

import { useState, useEffect } from 'react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface PageBackgroundSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
}

export function PageBackgroundSection({
  uiState,
  onFieldChange,
  activeColor,
  palette
}: PageBackgroundSectionProps): JSX.Element {
  const pageBackground = (uiState['page-background'] as string) ?? '#ffffff';
  const pageSpacing = (uiState['page-spacing'] as number) ?? 16;
  const pageBackgroundAnimate = (uiState['page-background-animate'] as boolean) ?? false;
  const [pageBackgroundType, setPageBackgroundType] = useState<'solid' | 'gradient'>('solid');

  // Determine background type
  useEffect(() => {
    if (!pageBackground || typeof pageBackground !== 'string') {
      setPageBackgroundType('solid');
      return;
    }
    if (pageBackground.includes('gradient')) {
      setPageBackgroundType('gradient');
    } else {
      setPageBackgroundType('solid');
    }
  }, [pageBackground]);

  return (
    <div className={styles.section}>
      {/* Page Background */}
      <div className={styles.fieldGroup}>
        <label className={styles.label}>Page Background</label>
        <BackgroundColorSwatch
          value={pageBackground}
          backgroundType={pageBackgroundType}
          onChange={(value) => onFieldChange('page-background', value)}
          onTypeChange={(type) => {
            setPageBackgroundType(type);
            if (type === 'solid') {
              onFieldChange('page-background', '#ffffff');
              // Disable animation when switching to solid
              onFieldChange('page-background-animate', false);
            } else if (type === 'gradient') {
              onFieldChange('page-background', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
            }
          }}
          label="Page background"
          palette={palette}
        />
      </div>

      {/* Gradient Animation Toggle - Only visible when gradient is selected */}
      {pageBackgroundType === 'gradient' && (
        <div className={styles.fieldGroup}>
          <label className={styles.toggleRow}>
            <span className={styles.label}>Animate Gradient</span>
            <label className={styles.toggleSwitch}>
              <input
                type="checkbox"
                checked={pageBackgroundAnimate}
                onChange={(e) => onFieldChange('page-background-animate', e.target.checked)}
              />
              <span className={styles.toggleSlider} />
            </label>
          </label>
        </div>
      )}

      {/* Spacing */}
      <div className={styles.fieldGroup}>
        <label className={styles.label}>Spacing</label>
        <p className={styles.description}>
          Controls spacing between all page elements (cover image, title, bio, social icons, widgets, and footer)
        </p>
        <SliderInput
          value={pageSpacing}
          min={8}
          max={48}
          step={2}
          unit="px"
          onChange={(value) => onFieldChange('page-spacing', value)}
        />
      </div>
    </div>
  );
}

