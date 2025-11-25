/**
 * Theme Card Hero Section
 * Displays the preview image/gradient with font preview
 */

import { Check } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../../api/types';
import { getHeroBackground, extractTypography } from './utils/themeCardUtils';
import styles from '../ThemePreviewCard.module.css';

interface ThemeCardHeroProps {
  theme: ThemeRecord;
  selected: boolean;
  onSelect: () => void;
  disabled?: boolean;
  swatches: string[];
}

export function ThemeCardHero({
  theme,
  selected,
  onSelect,
  disabled,
  swatches
}: ThemeCardHeroProps): JSX.Element {
  const heroBackground = getHeroBackground(theme, swatches);
  const { headingFont, bodyFont } = extractTypography(theme);

  return (
    <button
      type="button"
      className={styles.hero}
      onClick={onSelect}
      style={heroBackground}
      disabled={disabled}
      aria-label={`Apply ${theme.name}`}
    >
      <div className={styles.heroContent}>
        <span className={styles.heroTitle} style={{ fontFamily: headingFont }}>
          {headingFont === 'inherit' ? 'Heading font' : headingFont}
        </span>
        <span className={styles.heroSubtitle} style={{ fontFamily: bodyFont }}>
          {bodyFont === 'inherit' ? 'Secondary font' : bodyFont}
        </span>
      </div>
      {selected && (
        <span className={styles.heroBadge}>
          <Check aria-hidden="true" size={16} weight="regular" />
          Active
        </span>
      )}
    </button>
  );
}



