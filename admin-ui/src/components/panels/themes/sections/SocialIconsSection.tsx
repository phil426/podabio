/**
 * Social Icons Section
 * Settings for social icon appearance
 */

import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './social-icons-section.module.css';

interface SocialIconsSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function SocialIconsSection({
  uiState,
  onFieldChange,
  activeColor
}: SocialIconsSectionProps): JSX.Element {
  // Extract solid color from value (in case a gradient was previously set)
  const rawColor = (uiState['social-icon-color'] as string) ?? '#2563eb';
  const socialIconColor = rawColor.includes('gradient') ? '#2563eb' : (rawColor.startsWith('#') ? rawColor : '#2563eb');
  const socialIconSize = (uiState['social-icon-size'] as number) ?? 32;
  const socialIconSpacing = (uiState['social-icon-spacing'] as number) ?? 1;

  return (
    <div className={styles.section}>
      <div className={styles.fieldGroup}>
        <label className={styles.label}>Color</label>
        <BackgroundColorSwatch
          value={socialIconColor}
          onChange={(value) => onFieldChange('social-icon-color', value)}
          label="Social icon color"
          solidOnly={true}
        />
      </div>

      <div className={styles.fieldGroup}>
        <label className={styles.label}>Size</label>
        <SliderInput
          value={socialIconSize}
          min={20}
          max={64}
          step={1}
          unit="px"
          onChange={(value) => onFieldChange('social-icon-size', value)}
        />
      </div>

      <div className={styles.fieldGroup}>
        <label className={styles.label}>Spacing</label>
        <SliderInput
          value={socialIconSpacing}
          min={0}
          max={3}
          step={0.1}
          unit="rem"
          onChange={(value) => onFieldChange('social-icon-spacing', value)}
        />
      </div>
    </div>
  );
}

