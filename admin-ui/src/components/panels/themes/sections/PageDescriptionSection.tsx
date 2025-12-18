/**
 * Page Description Section
 * Settings for page description/bio only
 */

import { TypographyControl, TypographyValue } from '../controls/TypographyControl';
import { usePageSnapshot } from '../../../../api/page';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface PageDescriptionSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
}

export function PageDescriptionSection({
  uiState,
  onFieldChange,
  activeColor,
  palette
}: PageDescriptionSectionProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const pageBioColor = (uiState['page-bio-color'] as string) ?? '#4b5563';
  const pageBioFont = (uiState['page-bio-font'] as string) ?? 'Inter';
  const pageBioSize = (uiState['page-bio-size'] as number) ?? 16;
  const pageBioWeight = (uiState['page-bio-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };
  const pageBioSpacing = (uiState['page-bio-spacing'] as number) ?? 1.5;
  const pageBioAlignment = (uiState['page-bio-alignment'] as 'left' | 'center' | 'right') ?? snapshot?.page?.bio_alignment ?? 'center';

  const typographyValue = {
    font: pageBioFont,
    size: pageBioSize,
    spacing: pageBioSpacing,
    color: pageBioColor,
    weight: {
      bold: !!pageBioWeight.bold,
      italic: !!pageBioWeight.italic
    },
    alignment: pageBioAlignment,
    borderColor: 'transparent', // Not currently supported for bio, using default
    borderWidth: 0
  };

  const handleTypographyChange = (updates: Partial<TypographyValue>) => {
    if (updates.font !== undefined) onFieldChange('page-bio-font', updates.font);
    if (updates.size !== undefined) onFieldChange('page-bio-size', updates.size);
    if (updates.spacing !== undefined) onFieldChange('page-bio-spacing', updates.spacing);
    if (updates.color !== undefined) onFieldChange('page-bio-color', updates.color);
    if (updates.weight !== undefined) onFieldChange('page-bio-weight', updates.weight);
    if (updates.alignment !== undefined) onFieldChange('page-bio-alignment', updates.alignment);
    // Border props ignored for now as they weren't in original, but control handles them if we want to add later
  };

  return (
    <div className={styles.section}>
      {/* Page Bio */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Page Description</h4>

        <TypographyControl
          value={typographyValue}
          onChange={handleTypographyChange}
          palette={palette}
        />
      </div>
    </div>
  );
}

