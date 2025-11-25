/**
 * Theme Card Meta Section
 * Displays metadata badges (owner, density)
 */

import { getThemeMetadata } from './utils/themeCardUtils';
import type { ThemeRecord } from '../../../api/types';
import styles from '../ThemePreviewCard.module.css';

interface ThemeCardMetaProps {
  theme: ThemeRecord;
}

export function ThemeCardMeta({ theme }: ThemeCardMetaProps): JSX.Element {
  const { ownerBadge, densityLabel } = getThemeMetadata(theme);

  return (
    <div className={styles.metaRow}>
      <span className={styles.metaBadge}>{ownerBadge}</span>
      <span className={styles.metaBadge}>{densityLabel}</span>
    </div>
  );
}

