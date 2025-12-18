/**
 * Page Title Section
 * Settings for page title only
 */

import { PodaColorPicker } from '../../../controls/PodaColorPicker';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SpecialTextSelect } from '../../ultimate-theme-modifier/SpecialTextSelect';
import { TypographyControl, TypographyValue } from '../controls/TypographyControl';
import { usePageSnapshot } from '../../../../api/page';
import type { TabColorTheme } from '../../../layout/tab-colors';
import { getThemeColors } from '../utils/colorUtils';
import styles from './page-customization-section.module.css';

interface PageTitleSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
}

export function PageTitleSection({
  uiState,
  onFieldChange,
  activeColor,
  palette
}: PageTitleSectionProps): JSX.Element {
  // Map effect values to display names
  const { data: snapshot } = usePageSnapshot();
  const pageTitleText = snapshot?.page?.podcast_name || snapshot?.page?.username || 'Page Title';
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
  const shadowColor = '#000000';
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
  const pageTitleAlignment = (uiState['page-title-alignment'] as 'left' | 'center' | 'right') ?? snapshot?.page?.name_alignment ?? 'center';

  const typographyValue = {
    font: pageTitleFont,
    size: pageTitleSize,
    spacing: pageTitleSpacing,
    color: pageTitleColor,
    weight: {
      bold: !!pageTitleWeight.bold,
      italic: !!pageTitleWeight.italic
    },
    alignment: pageTitleAlignment,
    borderColor: borderColor,
    borderWidth: borderWidth
  };

  const handleTypographyChange = (updates: Partial<TypographyValue>) => {
    if (updates.font !== undefined) onFieldChange('page-title-font', updates.font);
    if (updates.size !== undefined) onFieldChange('page-title-size', updates.size);
    if (updates.spacing !== undefined) onFieldChange('page-title-spacing', updates.spacing);
    if (updates.color !== undefined) onFieldChange('page-title-color', updates.color);
    if (updates.weight !== undefined) onFieldChange('page-title-weight', updates.weight);
    if (updates.alignment !== undefined) onFieldChange('page-title-alignment', updates.alignment);
    if (updates.borderColor !== undefined) onFieldChange('page-title-border-color', updates.borderColor);
    if (updates.borderWidth !== undefined) onFieldChange('page-title-border-width', updates.borderWidth);
  };

  return (
    <div className={styles.section}>
      {/* Page Title */}
      <div className={styles.subsection}>
        {/* Live Preview */}
        <div className={styles.previewBox}>
          <h1 className={styles.previewText} style={{
            fontFamily: pageTitleFont.split(',')[0],
            fontSize: `${pageTitleSize}px`,
            textAlign: pageTitleAlignment,
            color: (pageTitleEffectValue === 'none' && pageTitleColor.includes('gradient')) ? 'transparent' : pageTitleColor,
            fontWeight: pageTitleWeight.bold ? 'bold' : 'normal',
            fontStyle: pageTitleWeight.italic ? 'italic' : 'normal',
            lineHeight: pageTitleSpacing,
            textShadow: pageTitleEffectValue === 'shadow'
              ? `${shadowDepth}px ${shadowDepth}px ${shadowBlur}px ${shadowColor}`
              : pageTitleEffectValue === 'glow'
                ? `0 0 ${glowWidth}px ${glowColor}`
                : 'none',
            backgroundImage: (pageTitleEffectValue === 'none' && pageTitleColor.includes('gradient')) ? pageTitleColor : 'none',
            backgroundClip: (pageTitleEffectValue === 'none' && pageTitleColor.includes('gradient')) ? 'text' : 'border-box',
            WebkitBackgroundClip: (pageTitleEffectValue === 'none' && pageTitleColor.includes('gradient')) ? 'text' : 'border-box',
            WebkitTextFillColor: (pageTitleEffectValue === 'none' && pageTitleColor.includes('gradient')) ? 'transparent' : 'initial',
            WebkitTextStroke: borderWidth > 0 ? `${borderWidth}px ${borderColor}` : '0px',
            paintOrder: 'stroke fill',
          }}>
            {pageTitleText}
          </h1>
        </div>

        {/* Reusable Typography Control */}
        <TypographyControl
          value={typographyValue}
          onChange={handleTypographyChange}
          palette={palette}
        />

        <div style={{ height: '1px', background: 'rgba(255,255,255,0.05)', margin: '8px 0' }}></div>

        {/* Special Effects */}
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

        {/* Shadow Controls */}
        {pageTitleEffectValue === 'shadow' && (
          <>
            <div className={styles.controlRow}>
              <div className={styles.fieldGroup}>
                <label className={styles.label}>Intensity</label>
                <SliderInput
                  value={shadowIntensity}
                  min={0}
                  max={1}
                  step={0.1}
                  onChange={(value) => onFieldChange('page-title-shadow-intensity', value)}
                />
              </div>
            </div>

            <div className={styles.controlRow}>
              <div className={styles.fieldGroup}>
                <label className={styles.label}>Depth</label>
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
                <label className={styles.label}>Blur</label>
                <SliderInput
                  value={shadowBlur}
                  min={0}
                  max={50}
                  step={1}
                  unit="px"
                  onChange={(value) => onFieldChange('page-title-shadow-blur', value)}
                />
              </div>
            </div>
          </>
        )}

        {/* Glow Controls */}
        {pageTitleEffectValue === 'glow' && (
          <div className={styles.controlRow}>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Color</label>
              <PodaColorPicker
                value={glowColor}
                onChange={(value) => onFieldChange('page-title-glow-color', value)}
                solidOnly
                palette={palette}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Width</label>
              <SliderInput
                value={glowWidth}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-glow-width', value)}
              />
            </div>
          </div>
        )}
      </div>
    </div >
  );
}

