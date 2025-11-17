import clsx from 'clsx';
import { LuPencil, LuCheck } from 'react-icons/lu';

import type { ThemeRecord } from '../../api/types';

import styles from './ThemePreviewCard.module.css';

type ThemePreviewCardProps = {
  theme: ThemeRecord;
  selected?: boolean;
  onSelect: () => void;
  primaryActionLabel: string;
  secondaryActionLabel?: string;
  onSecondaryAction?: () => void;
  tertiaryActions?: {
    onRename?: () => void;
    onDelete?: () => void;
  };
  disabled?: boolean;
};

function parseColorSwatches(theme: ThemeRecord): string[] {
  const raw = typeof theme.colors === 'string' ? safeParse(theme.colors) : theme.colors;
  if (raw && typeof raw === 'object') {
    return Object.values(raw)
      .filter((value): value is string => typeof value === 'string' && value.trim().startsWith('#'))
      .slice(0, 5);
  }
    return ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#dbeafe'];
}

function safeParse(input: string | null | undefined): Record<string, unknown> | null {
  if (!input) return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

export function ThemePreviewCard({
  theme,
  selected,
  onSelect,
  primaryActionLabel,
  tertiaryActions,
  disabled
}: ThemePreviewCardProps): JSX.Element {
  const swatches = parseColorSwatches(theme);
  const primarySwatch = swatches[0] ?? '#2563eb';
  const secondarySwatch = swatches[1] ?? '#1d4ed8';
  const heroBackground = theme.preview_image
    ? { backgroundImage: `linear-gradient(135deg, rgba(15, 23, 42, 0.2), rgba(15, 23, 42, 0.55)), url(${theme.preview_image})` }
    : { backgroundImage: `linear-gradient(135deg, ${primarySwatch}, ${secondarySwatch})` };
  const background = theme.page_background && typeof theme.page_background === 'string'
    ? theme.page_background
    : theme.widget_background && typeof theme.widget_background === 'string'
      ? theme.widget_background
      : undefined;
  const isDarkBackground = background ? isDarkColor(background) : false;
  const contrastClass = isDarkBackground ? styles.cardDark : '';

  const headingFont = theme.page_primary_font ?? theme.widget_primary_font ?? 'inherit';
  const bodyFont = theme.page_secondary_font ?? theme.widget_secondary_font ?? 'inherit';
  const ownerBadge = theme.user_id ? 'Community' : 'System';
  const densityLabel = theme.layout_density ? theme.layout_density : 'cozy';
  const isCustom = Boolean(theme.user_id) && theme.name?.toLowerCase().includes('custom');
  const isActiveCard = selected && primaryActionLabel === undefined;
  const themeLabel = isActiveCard
    ? 'Current Theme'
    : isCustom
      ? 'Custom Theme'
      : theme.name ?? 'Custom Theme';

  return (
    <article
      className={clsx(styles.card, selected && styles.cardSelected, contrastClass)}
      aria-pressed={selected ? 'true' : 'false'}
      style={background ? { background } : undefined}
    >
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
            <LuCheck aria-hidden="true" />
            Active
          </span>
        )}
      </button>

      <header className={styles.header}>
        <div className={styles.metaRow}>
          <span className={styles.metaBadge}>{ownerBadge}</span>
          <span className={styles.metaBadge}>{densityLabel}</span>
        </div>
      </header>

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

      <footer className={styles.footer}>
        <div className={styles.footerLeft}>
          <h5 className={styles.title}>{themeLabel}</h5>
          {primaryActionLabel && (
            <button
              type="button"
              className={styles.applyButton}
              onClick={onSelect}
              disabled={disabled}
            >
              {primaryActionLabel}
            </button>
          )}
        </div>
        {tertiaryActions && tertiaryActions.onRename && (
          <div className={styles.iconActions} aria-label="Theme actions">
            <button
              type="button"
              className={styles.iconButton}
              onClick={tertiaryActions.onRename}
              disabled={disabled}
              aria-label="Rename theme"
              title="Rename theme"
            >
              <LuPencil aria-hidden="true" />
            </button>
          </div>
        )}
      </footer>
    </article>
  );
}

function isDarkColor(color: string): boolean {
  const hexMatch = color.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hexMatch) {
    const normalized = color.replace('#', '');
    const bigint = parseInt(normalized.length === 3 ? normalized.repeat(2) : normalized, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    const luminance = 0.2126 * (r / 255) + 0.7152 * (g / 255) + 0.0722 * (b / 255);
    return luminance < 0.5;
  }
  return false;
}

