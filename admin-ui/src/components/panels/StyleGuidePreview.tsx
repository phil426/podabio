import { useMemo } from 'react';
import clsx from 'clsx';
import { User } from '@phosphor-icons/react';

import type { ThemeRecord } from '../../api/types';

import styles from './StyleGuidePreview.module.css';

type StyleGuidePreviewProps = {
  theme: ThemeRecord;
  selected?: boolean;
  onSelect: () => void;
  disabled?: boolean;
  isOpen: boolean;
  onToggle: () => void;
  isFirst?: boolean;
  isLast?: boolean;
  isDraggable?: boolean;
};

function safeParse(input: string | null | undefined): Record<string, unknown> | null {
  if (!input) return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

function extractThemeProperties(theme: ThemeRecord) {
  const colors = typeof theme.colors === 'string' ? safeParse(theme.colors) : theme.colors;
  const shapeTokens = typeof theme.shape_tokens === 'string' ? safeParse(theme.shape_tokens) : theme.shape_tokens;
  
  const primaryColor = (colors?.primary_color as string) ?? '#2563eb';
  const secondaryColor = (colors?.secondary_color as string) ?? '#3b82f6';
  const accentColor = (colors?.accent_color as string) ?? '#14b8a6';
  const textColor = (colors?.text_color as string) ?? '#0f172a';
  
  const pageBackground = theme.page_background ?? '#ffffff';
  const widgetBackground = theme.widget_background ?? '#f8fafc';
  const widgetBorder = theme.widget_border_color ?? '#e2e8f0';
  
  const pageFont = theme.page_primary_font ?? theme.widget_primary_font ?? 'Inter';
  const widgetFont = theme.widget_primary_font ?? theme.page_primary_font ?? 'Inter';
  
  const buttonRadius = (shapeTokens?.button_radius as string) ?? '8px';
  
  return {
    primaryColor,
    secondaryColor,
    accentColor,
    textColor,
    pageBackground,
    widgetBackground,
    widgetBorder,
    pageFont,
    widgetFont,
    buttonRadius
  };
}

export function StyleGuidePreview({
  theme,
  selected,
  onSelect,
  disabled,
  isOpen,
  onToggle,
  isFirst = false,
  isLast = false,
  isDraggable = true
}: StyleGuidePreviewProps): JSX.Element {
  const themeProps = useMemo(() => extractThemeProperties(theme), [theme]);
  
  const toggleButtonClass = clsx(
    styles.toggleButton,
    !isOpen && styles.collapsed,
    isFirst && styles.firstButton,
    isLast && styles.lastButton
  );
  
  const previewClass = clsx(
    styles.preview,
    !isOpen && styles.collapsed,
    isLast && styles.lastPreview
  );

  const handleDragStart = (e: React.DragEvent) => {
    if (!isDraggable) return;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(theme.id));
  };

  const layerItemClass = clsx(
    styles.themeLayerItem,
    isDraggable && styles.draggable
  );

  return (
    <div
      className={layerItemClass}
      draggable={isDraggable}
      onDragStart={handleDragStart}
    >
      <button
        type="button"
        className={toggleButtonClass}
        onClick={(e) => {
          if (!isDraggable) {
            // Active theme - clicking opens properties panel
            e.stopPropagation();
            onSelect();
          } else {
            // Regular theme - clicking toggles preview
            onToggle();
          }
        }}
        disabled={disabled}
        aria-expanded={isOpen}
        aria-label={!isDraggable ? `Select ${theme.name} to open properties` : `${isOpen ? 'Collapse' : 'Expand'} ${theme.name} preview`}
      >
        <span
          className={styles.colorChip}
          style={{ background: themeProps.pageBackground }}
          aria-hidden="true"
        />
        <span className={styles.themeName}>{theme.name}</span>
        <span className={styles.toggleIcon} aria-hidden="true">
          ▼
        </span>
      </button>
      
      <div
        className={previewClass}
        style={{
          background: themeProps.pageBackground,
          fontFamily: `'${themeProps.pageFont}', sans-serif`
        }}
      >
        <div className={styles.profilePictureFrame}>
          <div className={styles.profilePictureTitle}>Profile Picture</div>
          <div className={styles.profilePictureContainer}>
            <div
              className={styles.profilePicture}
              style={{
                background: themeProps.accentColor,
                borderColor: themeProps.widgetBorder
              }}
            >
              <LuUser className={styles.profilePictureIcon} aria-hidden="true" />
            </div>
          </div>
        </div>
        
        <div className={styles.colorPalette}>
          <div className={styles.colorPalettePanel}>
            <div className={styles.colorPaletteTitle}>Color Palette</div>
            <div className={styles.colorSwatches} role="list" aria-label={`${theme.name} color palette`}>
              <div className={styles.colorSwatchWrapper}>
                <div
                  className={styles.colorSwatch}
                  style={{ background: themeProps.primaryColor }}
                  role="listitem"
                  aria-label={`Primary color ${themeProps.primaryColor}`}
                />
                <div className={styles.colorSwatchLabel}>Primary</div>
              </div>
              <div className={styles.colorSwatchWrapper}>
                <div
                  className={styles.colorSwatch}
                  style={{ background: themeProps.secondaryColor }}
                  role="listitem"
                  aria-label={`Secondary color ${themeProps.secondaryColor}`}
                />
                <div className={styles.colorSwatchLabel}>Secondary</div>
              </div>
              <div className={styles.colorSwatchWrapper}>
                <div
                  className={styles.colorSwatch}
                  style={{ background: themeProps.accentColor }}
                  role="listitem"
                  aria-label={`Accent color ${themeProps.accentColor}`}
                />
                <div className={styles.colorSwatchLabel}>Accent</div>
              </div>
            </div>
          </div>
        </div>
        
        <div className={styles.typographySection}>
          <div className={styles.typographyTitle}>Typography</div>
          <div
            className={styles.typographySample}
            style={{
              fontFamily: `'${themeProps.pageFont}', sans-serif`,
              color: themeProps.textColor,
              background: themeProps.widgetBackground
            }}
          >
            <h1>Heading 1</h1>
            <h2>Heading 2</h2>
            <p>Body text sample</p>
          </div>
        </div>
        
        <div className={styles.buttonSection}>
          <div className={styles.buttonTitle}>Buttons</div>
          <div className={styles.buttonExamples}>
            <button
              type="button"
              className={styles.buttonExample}
              style={{
                background: themeProps.accentColor,
                color: themeProps.textColor,
                borderRadius: themeProps.buttonRadius
              }}
              onClick={onSelect}
              disabled={disabled}
            >
              Primary
            </button>
            <button
              type="button"
              className={styles.buttonExample}
              style={{
                background: themeProps.widgetBackground,
                color: themeProps.textColor,
                border: `2px solid ${themeProps.widgetBorder}`,
                borderRadius: themeProps.buttonRadius
              }}
              onClick={onSelect}
              disabled={disabled}
            >
              Secondary
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

