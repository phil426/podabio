import { useMemo } from 'react';
import clsx from 'clsx';
import { Pencil } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../api/types';
import { ThemeCardHero } from './themes/ThemeCardHero';
import { ThemeCardMeta } from './themes/ThemeCardMeta';
import { ThemeCardSwatches } from './themes/ThemeCardSwatches';
import { ThemeCardFooter } from './themes/ThemeCardFooter';
import { extractColorSwatches, getCardBackground, isDarkColor } from './themes/utils/themeCardUtils';
import styles from './ThemePreviewCard.module.css';

type ThemePreviewCardProps = {
  theme: ThemeRecord;
  selected?: boolean;
  onSelect: () => void;
  primaryActionLabel?: string;
  onPrimaryAction?: () => void;
  secondaryActionLabel?: string;
  onSecondaryAction?: () => void;
  tertiaryActions?: {
    onRename?: () => void;
    onDelete?: () => void;
  };
  disabled?: boolean;
};

export function ThemePreviewCard({
  theme,
  selected,
  onSelect,
  primaryActionLabel,
  onPrimaryAction,
  secondaryActionLabel,
  onSecondaryAction,
  tertiaryActions,
  disabled
}: ThemePreviewCardProps): JSX.Element {
  // Extract theme data
  const swatches = useMemo(() => extractColorSwatches(theme), [theme]);
  const background = useMemo(() => getCardBackground(theme), [theme]);
  const isDarkBackground = useMemo(() => background ? isDarkColor(background) : false, [background]);
  const contrastClass = isDarkBackground ? styles.cardDark : '';
  const isActiveCard = selected && primaryActionLabel === undefined;

  return (
    <article
      className={clsx(styles.card, selected && styles.cardSelected, contrastClass)}
      aria-pressed={selected ? 'true' : 'false'}
      style={background ? { background } : undefined}
      onClick={onSelect}
    >
      <ThemeCardHero
        theme={theme}
        selected={selected}
        onSelect={onSelect}
        disabled={disabled}
        swatches={swatches}
      />

      <div className={styles.cardBody}>
        <ThemeCardMeta theme={theme} />
        <ThemeCardSwatches theme={theme} swatches={swatches} />
      </div>

      {selected && (
        <div className={styles.editBar}>
          <button
            type="button"
            className={styles.editButton}
            onClick={(e) => {
              e.stopPropagation();
              onSecondaryAction?.() || onSelect();
            }}
            disabled={disabled}
          >
            <Pencil aria-hidden="true" size={14} weight="regular" />
            Edit Theme
          </button>
        </div>
      )}

      <ThemeCardFooter
        theme={theme}
        selected={selected}
        primaryActionLabel={primaryActionLabel}
        onSelect={onSelect}
        tertiaryActions={tertiaryActions}
        disabled={disabled}
        isActiveCard={isActiveCard}
      />
    </article>
  );
}

