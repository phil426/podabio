/**
 * Widget Text Section
 * Settings for widget heading and body text
 */

import { TypographyControl, TypographyValue } from '../controls/TypographyControl';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './widget-text-section.module.css';

interface WidgetTextSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
}

export function WidgetTextSection({
  uiState,
  onFieldChange,
  activeColor,
  palette
}: WidgetTextSectionProps): JSX.Element {
  // Heading values
  const widgetHeadingColor = (uiState['widget-heading-color'] as string) ?? '#0f172a';
  const widgetHeadingFont = (uiState['widget-heading-font'] as string) ?? 'Inter';
  const widgetHeadingSize = (uiState['widget-heading-size'] as number) ?? 20;
  const widgetHeadingSpacing = (uiState['widget-heading-spacing'] as number) ?? 1.3;
  const widgetHeadingWeight = (uiState['widget-heading-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };
  const widgetHeadingAlignment = (uiState['widget-heading-alignment'] as 'left' | 'center' | 'right') ?? 'left';

  // Body values
  const widgetBodyColor = (uiState['widget-body-color'] as string) ?? '#4b5563';
  const widgetBodyFont = (uiState['widget-body-font'] as string) ?? 'Inter';
  const widgetBodySize = (uiState['widget-body-size'] as number) ?? 16;
  const widgetBodySpacing = (uiState['widget-body-spacing'] as number) ?? 1.5;
  const widgetBodyWeight = (uiState['widget-body-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };
  const widgetBodyAlignment = (uiState['widget-body-alignment'] as 'left' | 'center' | 'right') ?? 'left';

  const headingTypography: TypographyValue = {
    font: widgetHeadingFont,
    size: widgetHeadingSize,
    spacing: widgetHeadingSpacing,
    color: widgetHeadingColor,
    weight: {
      bold: !!widgetHeadingWeight.bold,
      italic: !!widgetHeadingWeight.italic
    },
    alignment: widgetHeadingAlignment,
    borderColor: 'transparent',
    borderWidth: 0
  };

  const bodyTypography: TypographyValue = {
    font: widgetBodyFont,
    size: widgetBodySize,
    spacing: widgetBodySpacing,
    color: widgetBodyColor,
    weight: {
      bold: !!widgetBodyWeight.bold,
      italic: !!widgetBodyWeight.italic
    },
    alignment: widgetBodyAlignment,
    borderColor: 'transparent',
    borderWidth: 0
  };

  const handleHeadingChange = (updates: Partial<TypographyValue>) => {
    if (updates.font !== undefined) onFieldChange('widget-heading-font', updates.font);
    if (updates.size !== undefined) onFieldChange('widget-heading-size', updates.size);
    if (updates.spacing !== undefined) onFieldChange('widget-heading-spacing', updates.spacing);
    if (updates.color !== undefined) onFieldChange('widget-heading-color', updates.color);
    if (updates.weight !== undefined) onFieldChange('widget-heading-weight', updates.weight);
    if (updates.alignment !== undefined) onFieldChange('widget-heading-alignment', updates.alignment);
  };

  const handleBodyChange = (updates: Partial<TypographyValue>) => {
    if (updates.font !== undefined) onFieldChange('widget-body-font', updates.font);
    if (updates.size !== undefined) onFieldChange('widget-body-size', updates.size);
    if (updates.spacing !== undefined) onFieldChange('widget-body-spacing', updates.spacing);
    if (updates.color !== undefined) onFieldChange('widget-body-color', updates.color);
    if (updates.weight !== undefined) onFieldChange('widget-body-weight', updates.weight);
    if (updates.alignment !== undefined) onFieldChange('widget-body-alignment', updates.alignment);
  };

  return (
    <div className={styles.section}>
      <div className={styles.twoColumn}>
        {/* Heading Column */}
        <div className={styles.column}>
          <h4 className={styles.columnTitle}>Heading Text</h4>
          <TypographyControl
            value={headingTypography}
            onChange={handleHeadingChange}
            palette={palette}
          />
        </div>

        {/* Body Column */}
        <div className={styles.column}>
          <h4 className={styles.columnTitle}>Body Text</h4>
          <TypographyControl
            value={bodyTypography}
            onChange={handleBodyChange}
            palette={palette}
          />
        </div>
      </div>
    </div>
  );
}

