/**
 * Theme Card Color Swatches
 * Displays the color palette
 */

import type { ThemeRecord } from '../../../api/types';
import styles from '../ThemePreviewCard.module.css';

interface ThemeCardSwatchesProps {
  theme: ThemeRecord;
  swatches: string[];
}

export function ThemeCardSwatches({ theme, swatches }: ThemeCardSwatchesProps): JSX.Element {
  return (
    <div className={styles.swatchRow} role="list" aria-label={`${theme.name} palette`}>
      {swatches.map((color, index) => (
        <span
          key={`${theme.id}-swatch-${index}`}
          className={styles.swatch}
          style={{ background: color }}
          aria-label={`Swatch ${index + 1} ${color}`}
          role="listitem"
        />
      ))}
    </div>
  );
}






