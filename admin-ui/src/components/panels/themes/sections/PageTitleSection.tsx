/**
 * Page Title Section
 * Settings for page title only
 */

import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { PodaColorPicker } from '../../../controls/PodaColorPicker';
import { FontSelect } from '../../ultimate-theme-modifier/FontSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SpecialTextSelect } from '../../ultimate-theme-modifier/SpecialTextSelect';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface PageTitleSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function PageTitleSection({
  uiState,
  onFieldChange,
  activeColor
}: PageTitleSectionProps): JSX.Element {
  // Map effect values to display names
  const effectValueToDisplay: Record<string, string> = {
    'none': 'None',
    'glow': 'Neon Glow',
    'shadow': 'Drop Shadow',
    'retro': 'Retro Shadow',
    'anaglyphic': 'Anaglyphic',
    'deep': 'Deep',
    'game': 'Game',
    'fancy': 'Fancy',
    'pretty': 'Pretty',
    'flat': 'Flat',
    'long': 'Long Shadow',
    'party': 'Party Time'
  };
  const effectDisplayToValue: Record<string, string> = {
    'None': 'none',
    'Neon Glow': 'glow',
    'Drop Shadow': 'shadow',
    'Retro Shadow': 'retro',
    'Anaglyphic': 'anaglyphic',
    'Deep': 'deep',
    'Game': 'game',
    'Fancy': 'fancy',
    'Pretty': 'pretty',
    'Flat': 'flat',
    'Long Shadow': 'long',
    'Party Time': 'party'
  };
  const pageTitleEffectValue = (uiState['page-title-effect'] as string) ?? 'none';
  const pageTitleEffect = effectValueToDisplay[pageTitleEffectValue] || 'None';
  
  // Ensure the value matches one of the options for Radix Select
  const allOptions: string[] = ['None', 'Neon Glow', 'Drop Shadow', 'Retro Shadow', 'Anaglyphic', 'Deep', 'Game', 'Fancy', 'Pretty', 'Flat', 'Long Shadow', 'Party Time'];
  const validValue = allOptions.includes(pageTitleEffect) ? pageTitleEffect : 'None';
  
  // Debug: Log to verify options are correct
  if (typeof window !== 'undefined' && (window as any).__DEBUG__) {
    console.log('PageTitleSection - Effect options:', allOptions);
    console.log('PageTitleSection - Current value:', pageTitleEffectValue, '-> Display:', pageTitleEffect, '-> Valid:', validValue);
  }
  
  // Shadow properties
  const shadowColor = (uiState['page-title-shadow-color'] as string) ?? '#000000';
  const shadowIntensity = (uiState['page-title-shadow-intensity'] as number) ?? 0.5;
  const shadowDepth = (uiState['page-title-shadow-depth'] as number) ?? 4;
  const shadowBlur = (uiState['page-title-shadow-blur'] as number) ?? 8;
  
  // Glow properties
  const glowColor = (uiState['page-title-glow-color'] as string) ?? '#2563eb';
  const glowWidth = (uiState['page-title-glow-width'] as number) ?? 10;
  
  // Border/Stroke properties
  const borderColor = (uiState['page-title-border-color'] as string) ?? '#000000';
  const borderWidth = (uiState['page-title-border-width'] as number) ?? 0;
  
  const pageTitleColor = (uiState['page-title-color'] as string) ?? '#0f172a';
  const pageTitleFont = (uiState['page-title-font'] as string) ?? 'Inter';
  const pageTitleSize = (uiState['page-title-size'] as number) ?? 24;
  const pageTitleSpacing = (uiState['page-title-spacing'] as number) ?? 1.2;
  const pageTitleWeight = (uiState['page-title-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };

  return (
    <div className={styles.section}>
      {/* Page Title */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Page Title</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Special Effect</label>
          <SpecialTextSelect
            value={validValue}
            options={allOptions}
            onChange={(value) => {
              onFieldChange('page-title-effect', effectDisplayToValue[value] || value);
            }}
          />
        </div>

        {/* Shadow Controls - Only show when shadow is selected */}
        {pageTitleEffectValue === 'shadow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Color</label>
              <PodaColorPicker
                value={shadowColor}
                onChange={(value) => onFieldChange('page-title-shadow-color', value)}
                solidOnly
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Intensity</label>
              <SliderInput
                value={shadowIntensity}
                min={0}
                max={1}
                step={0.1}
                onChange={(value) => onFieldChange('page-title-shadow-intensity', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Depth</label>
              <SliderInput
                value={shadowDepth}
                min={0}
                max={20}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-shadow-depth', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Blur</label>
              <SliderInput
                value={shadowBlur}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-shadow-blur', value)}
              />
            </div>
          </>
        )}

        {/* Glow Controls - Only show when glow is selected */}
        {pageTitleEffectValue === 'glow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Color</label>
              <PodaColorPicker
                value={glowColor}
                onChange={(value) => onFieldChange('page-title-glow-color', value)}
                solidOnly
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Width</label>
              <SliderInput
                value={glowWidth}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-glow-width', value)}
              />
            </div>
          </>
        )}

        {/* Page Title Color - Always visible */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Color</label>
          <BackgroundColorSwatch
            value={pageTitleColor}
            onChange={(value) => onFieldChange('page-title-color', value)}
            label="Page title color"
            solidOnly={true}
          />
        </div>

        {/* Font Border/Stroke Controls - Always visible */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font Border Color</label>
          <BackgroundColorSwatch
            value={borderColor}
            onChange={(value) => onFieldChange('page-title-border-color', value)}
            label="Font border color"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font Border Width</label>
          <SliderInput
            value={borderWidth}
            min={0}
            max={10}
            step={0.5}
            unit="px"
            onChange={(value) => onFieldChange('page-title-border-width', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font</label>
          <FontSelect
            value={pageTitleFont}
            onChange={(value) => onFieldChange('page-title-font', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Size</label>
          <SliderInput
            value={pageTitleSize}
            min={14}
            max={48}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('page-title-size', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Spacing</label>
          <SliderInput
            value={pageTitleSpacing}
            min={1}
            max={2}
            step={0.1}
            onChange={(value) => onFieldChange('page-title-spacing', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Style</label>
          <div className={styles.toggleGroup}>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageTitleWeight.bold ? styles.active : ''}`}
              onClick={() => onFieldChange('page-title-weight', { ...pageTitleWeight, bold: !pageTitleWeight.bold })}
            >
              Bold
            </button>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageTitleWeight.italic ? styles.active : ''}`}
              onClick={() => onFieldChange('page-title-weight', { ...pageTitleWeight, italic: !pageTitleWeight.italic })}
            >
              Italic
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

