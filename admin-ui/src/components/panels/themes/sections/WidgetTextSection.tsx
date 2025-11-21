/**
 * Widget Text Section
 * Settings for widget heading and body text
 */

import { ColorTokenPicker } from '../../../controls/ColorTokenPicker';
import { FontSelect } from '../../ultimate-theme-modifier/FontSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './widget-text-section.module.css';

interface WidgetTextSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function WidgetTextSection({
  uiState,
  onFieldChange,
  activeColor
}: WidgetTextSectionProps): JSX.Element {
  // Heading values
  const widgetHeadingColor = (uiState['widget-heading-color'] as string) ?? '#0f172a';
  const widgetHeadingFont = (uiState['widget-heading-font'] as string) ?? 'Inter';
  const widgetHeadingSize = (uiState['widget-heading-size'] as number) ?? 20;
  const widgetHeadingSpacing = (uiState['widget-heading-spacing'] as number) ?? 1.3;
  const widgetHeadingWeight = (uiState['widget-heading-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };

  // Body values
  const widgetBodyColor = (uiState['widget-body-color'] as string) ?? '#4b5563';
  const widgetBodyFont = (uiState['widget-body-font'] as string) ?? 'Inter';
  const widgetBodySize = (uiState['widget-body-size'] as number) ?? 16;
  const widgetBodySpacing = (uiState['widget-body-spacing'] as number) ?? 1.5;
  const widgetBodyWeight = (uiState['widget-body-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };

  return (
    <div className={styles.section}>
      <div className={styles.twoColumn}>
        {/* Heading Column */}
        <div className={styles.column}>
          <h4 className={styles.columnTitle}>Heading Text</h4>
          
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Color</label>
            <ColorTokenPicker
              label="Widget heading color"
              token="typography_tokens.color.widget_heading"
              value={widgetHeadingColor}
              onChange={(value) => onFieldChange('widget-heading-color', value)}
              hideToken
              hideWrapper
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Font</label>
            <FontSelect
              value={widgetHeadingFont}
              onChange={(value) => onFieldChange('widget-heading-font', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Size</label>
            <SliderInput
              value={widgetHeadingSize}
              min={14}
              max={48}
              step={1}
              unit="px"
              onChange={(value) => onFieldChange('widget-heading-size', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Spacing</label>
            <SliderInput
              value={widgetHeadingSpacing}
              min={1}
              max={2}
              step={0.1}
              onChange={(value) => onFieldChange('widget-heading-spacing', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Style</label>
            <div className={styles.toggleGroup}>
              <button
                type="button"
                className={`${styles.toggleButton} ${widgetHeadingWeight.bold ? styles.active : ''}`}
                onClick={() => onFieldChange('widget-heading-weight', { ...widgetHeadingWeight, bold: !widgetHeadingWeight.bold })}
              >
                Bold
              </button>
              <button
                type="button"
                className={`${styles.toggleButton} ${widgetHeadingWeight.italic ? styles.active : ''}`}
                onClick={() => onFieldChange('widget-heading-weight', { ...widgetHeadingWeight, italic: !widgetHeadingWeight.italic })}
              >
                Italic
              </button>
            </div>
          </div>
        </div>

        {/* Body Column */}
        <div className={styles.column}>
          <h4 className={styles.columnTitle}>Body Text</h4>
          
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Color</label>
            <ColorTokenPicker
              label="Widget body color"
              token="typography_tokens.color.widget_body"
              value={widgetBodyColor}
              onChange={(value) => onFieldChange('widget-body-color', value)}
              hideToken
              hideWrapper
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Font</label>
            <FontSelect
              value={widgetBodyFont}
              onChange={(value) => onFieldChange('widget-body-font', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Size</label>
            <SliderInput
              value={widgetBodySize}
              min={14}
              max={48}
              step={1}
              unit="px"
              onChange={(value) => onFieldChange('widget-body-size', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Spacing</label>
            <SliderInput
              value={widgetBodySpacing}
              min={1}
              max={2}
              step={0.1}
              onChange={(value) => onFieldChange('widget-body-spacing', value)}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.label}>Style</label>
            <div className={styles.toggleGroup}>
              <button
                type="button"
                className={`${styles.toggleButton} ${widgetBodyWeight.bold ? styles.active : ''}`}
                onClick={() => onFieldChange('widget-body-weight', { ...widgetBodyWeight, bold: !widgetBodyWeight.bold })}
              >
                Bold
              </button>
              <button
                type="button"
                className={`${styles.toggleButton} ${widgetBodyWeight.italic ? styles.active : ''}`}
                onClick={() => onFieldChange('widget-body-weight', { ...widgetBodyWeight, italic: !widgetBodyWeight.italic })}
              >
                Italic
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

