import { useMemo } from 'react';
import clsx from 'clsx';
import { Check, Trash } from '@phosphor-icons/react';
import type { ThemeRecord } from '../../api/types';
import { getCardBackground, isDarkColor, getThemeDescription, getButtonColor, getButtonRadius, extractTypography } from './themes/utils/themeCardUtils';
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
  const background = useMemo(() => getCardBackground(theme), [theme]);
  const isDarkBackground = useMemo(() => background ? isDarkColor(background) : false, [background]);
  const contrastClass = isDarkBackground ? styles.cardDark : '';
  const description = useMemo(() => getThemeDescription(theme), [theme]);
  const buttonColor = useMemo(() => getButtonColor(theme), [theme]);
  const buttonRadius = useMemo(() => getButtonRadius(theme), [theme]);
  const { headingFont, bodyFont } = useMemo(() => extractTypography(theme), [theme]);
  
  const buttonRadiusStyle = useMemo(() => {
    switch (buttonRadius) {
      case 'square':
        return '0px';
      case 'pill':
        return '9999px';
      case 'rounded':
      default:
        return '6px';
    }
  }, [buttonRadius]);
  
  // Convert font names to CSS font-family values
  const headingFontFamily = useMemo(() => {
    if (!headingFont || headingFont === 'inherit') {
      return 'Georgia, "Times New Roman", serif'; // Default serif for titles
    }
    return headingFont;
  }, [headingFont]);
  
  const bodyFontFamily = useMemo(() => {
    if (!bodyFont || bodyFont === 'inherit') {
      return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'; // Default sans-serif
    }
    return bodyFont;
  }, [bodyFont]);

  const isUserTheme = Boolean(theme.user_id);
  const showDeleteButton = isUserTheme && tertiaryActions?.onDelete;

  return (
    <article
      className={clsx(styles.card, selected && styles.cardSelected, contrastClass)}
      aria-pressed={selected ? 'true' : 'false'}
      style={background ? { background } : undefined}
      onClick={disabled ? undefined : onSelect}
    >
      {selected && (
        <button
          type="button"
          className={styles.checkmarkButton}
          aria-label={`${theme.name} is selected`}
          title={`${theme.name} is selected`}
        >
          <Check aria-hidden="true" size={9} weight="regular" />
        </button>
      )}
      
      {showDeleteButton && (
        <button
          type="button"
          className={styles.deleteButton}
          onClick={(e) => {
            e.stopPropagation();
            tertiaryActions?.onDelete?.();
          }}
          aria-label={`Delete ${theme.name}`}
          title={`Delete ${theme.name}`}
        >
          <Trash aria-hidden="true" size={9} weight="regular" />
        </button>
      )}
      
      <div className={styles.cardContent}>
        <div className={styles.cardHeader}>
          <div className={styles.cardTitleSection}>
            <h3 
              className={styles.cardTitle}
              style={{ fontFamily: headingFontFamily }}
            >
              {theme.name ?? 'Custom Theme'}
            </h3>
            <p 
              className={styles.cardSubtitle}
              style={{ fontFamily: bodyFontFamily }}
            >
              {description}
            </p>
            <div 
              className={styles.buttonPreview}
              style={{ 
                backgroundColor: buttonColor,
                borderRadius: buttonRadiusStyle
              }}
              title={`Button style: ${buttonRadius}`}
            />
          </div>
        </div>
      </div>
    </article>
  );
}

