/**
 * Page Description Section
 * Settings for page description/bio only
 */

import { StandardColorPicker } from '../../../controls/StandardColorPicker';
import { FontSelect } from '../../ultimate-theme-modifier/FontSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface PageDescriptionSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function PageDescriptionSection({
  uiState,
  onFieldChange,
  activeColor
}: PageDescriptionSectionProps): JSX.Element {
  const pageBioColor = (uiState['page-bio-color'] as string) ?? '#4b5563';
  const pageBioFont = (uiState['page-bio-font'] as string) ?? 'Inter';
  const pageBioSize = (uiState['page-bio-size'] as number) ?? 16;
  const pageBioSpacing = (uiState['page-bio-spacing'] as number) ?? 100;
  const pageBioWeight = (uiState['page-bio-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };

  return (
    <div className={styles.section}>
      {/* Page Bio */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Page Description</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Color</label>
          <StandardColorPicker
            label="Page bio color"
            value={pageBioColor}
            onChange={(value) => onFieldChange('page-bio-color', value)}
            hideWrapper
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font</label>
          <FontSelect
            value={pageBioFont}
            onChange={(value) => onFieldChange('page-bio-font', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Size</label>
          <SliderInput
            value={pageBioSize}
            min={10}
            max={24}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('page-bio-size', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Spacing</label>
          <SliderInput
            value={pageBioSpacing}
            min={50}
            max={200}
            step={5}
            unit="%"
            onChange={(value) => onFieldChange('page-bio-spacing', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Style</label>
          <div className={styles.toggleGroup}>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageBioWeight.bold ? styles.active : ''}`}
              onClick={() => onFieldChange('page-bio-weight', { ...pageBioWeight, bold: !pageBioWeight.bold })}
            >
              Bold
            </button>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageBioWeight.italic ? styles.active : ''}`}
              onClick={() => onFieldChange('page-bio-weight', { ...pageBioWeight, italic: !pageBioWeight.italic })}
            >
              Italic
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

