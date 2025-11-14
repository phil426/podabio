import { useMemo, useState } from 'react';
import clsx from 'clsx';
import { LuUser, LuPencil, LuCopy, LuTrash2, LuCheck } from 'react-icons/lu';

import type { ThemeRecord } from '../../api/types';
import { StyleGuidePreview } from './StyleGuidePreview';

import styles from './theme-swatch.module.css';

interface ThemeSwatchProps {
  theme: ThemeRecord;
  selected?: boolean;
  isActive?: boolean;
  onApply?: () => void;
  onEdit?: () => void;
  onDuplicate?: () => void;
  onDelete?: () => void;
  showActions?: boolean;
  isUserTheme?: boolean;
}

function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object' && !Array.isArray(input)) return input as Record<string, unknown>;
  if (typeof input !== 'string') return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

function extractThemeColors(theme: ThemeRecord): string[] {
  const colorTokens = safeParse(theme.color_tokens);
  const colors = safeParse(theme.colors);
  
  const palette: string[] = [];
  
  // Try to extract from color_tokens first
  if (colorTokens) {
    const accent = colorTokens.accent as Record<string, unknown> | undefined;
    const text = colorTokens.text as Record<string, unknown> | undefined;
    const background = colorTokens.background as Record<string, unknown> | undefined;
    
    if (accent?.primary && typeof accent.primary === 'string') palette.push(accent.primary);
    if (accent?.secondary && typeof accent.secondary === 'string') palette.push(accent.secondary);
    if (text?.primary && typeof text.primary === 'string') palette.push(text.primary);
    if (background?.base && typeof background.base === 'string') palette.push(background.base);
  }
  
  // Fallback to legacy colors
  if (palette.length === 0 && colors) {
    if (colors.primary_color && typeof colors.primary_color === 'string') palette.push(colors.primary_color);
    if (colors.secondary_color && typeof colors.secondary_color === 'string') palette.push(colors.secondary_color);
    if (colors.accent_color && typeof colors.accent_color === 'string') palette.push(colors.accent_color);
    if (colors.text_color && typeof colors.text_color === 'string') palette.push(colors.text_color);
  }
  
  // Add page background
  if (theme.page_background && typeof theme.page_background === 'string') {
    palette.push(theme.page_background);
  }
  
  // Ensure we have at least some colors
  if (palette.length === 0) {
    palette.push('#2563eb', '#3b82f6', '#0f172a', '#ffffff');
  }
  
  return palette.slice(0, 5); // Max 5 colors
}

function extractTypography(theme: ThemeRecord) {
  const typographyTokens = safeParse(theme.typography_tokens);
  const fonts = typographyTokens && typeof typographyTokens === 'object' && 'font' in typographyTokens 
    ? (typographyTokens.font as Record<string, unknown>)
    : null;
  const headingFont = (fonts?.heading as string | undefined) ?? theme.page_primary_font ?? 'Inter';
  const bodyFont = (fonts?.body as string | undefined) ?? theme.widget_primary_font ?? 'Inter';
  return { headingFont, bodyFont };
}

export function ThemeSwatch({
  theme,
  selected = false,
  isActive = false,
  onApply,
  onEdit,
  onDuplicate,
  onDelete,
  showActions = true,
  isUserTheme = false
}: ThemeSwatchProps): JSX.Element {
  const [isExpanded, setIsExpanded] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  
  const colors = useMemo(() => extractThemeColors(theme), [theme]);
  const typography = useMemo(() => extractTypography(theme), [theme]);
  const pageBackground = theme.page_background ?? '#ffffff';
  
  const handleClick = () => {
    if (onApply) {
      onApply();
    }
  };
  
  const handleExpand = () => {
    setIsExpanded(true);
  };
  
  if (isExpanded) {
    return (
      <div className={styles.expandedWrapper}>
        <button
          type="button"
          className={styles.collapseButton}
          onClick={() => setIsExpanded(false)}
          aria-label="Collapse preview"
        >
          ×
        </button>
        <StyleGuidePreview
          theme={theme}
          selected={selected}
          onSelect={handleClick}
          disabled={false}
          isOpen={true}
          onToggle={() => setIsExpanded(false)}
          isDraggable={false}
        />
      </div>
    );
  }
  
  return (
    <div
      className={clsx(
        styles.swatch,
        selected && styles.selected,
        isActive && styles.active
      )}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div
        className={styles.preview}
        style={{ background: pageBackground }}
        onClick={handleClick}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleClick();
          }
        }}
        aria-label={`Preview ${theme.name} theme`}
      >
        {/* Color Palette */}
        <div className={styles.colorPalette}>
          {colors.map((color, index) => (
            <div
              key={index}
              className={styles.colorSwatch}
              style={{ backgroundColor: color }}
              aria-label={`Color ${index + 1}: ${color}`}
            />
          ))}
        </div>
        
        {/* Typography Sample */}
        <div className={styles.typographySample}>
          <div
            className={styles.headingSample}
            style={{ fontFamily: `'${typography.headingFont}', sans-serif` }}
          >
            Aa
          </div>
          <div
            className={styles.bodySample}
            style={{ fontFamily: `'${typography.bodyFont}', sans-serif` }}
          >
            Aa
          </div>
        </div>
        
        {/* Button Example */}
        <div className={styles.buttonExample}>
          <div
            className={styles.buttonPreview}
            style={{
              backgroundColor: colors[0] ?? '#2563eb',
              borderRadius: '6px'
            }}
          />
        </div>
      </div>
      
      {/* Theme Name */}
      <div className={styles.footer}>
        <span className={styles.name}>{theme.name}</span>
        {isActive && (
          <span className={styles.activeBadge} aria-label="Active theme">
            <LuCheck aria-hidden="true" />
          </span>
        )}
      </div>
      
      {/* Actions */}
      {showActions && (isHovered || selected) && (
        <div className={styles.actions}>
          {onApply && (
            <button
              type="button"
              className={styles.actionButton}
              onClick={(e) => {
                e.stopPropagation();
                onApply();
              }}
              aria-label={`Apply ${theme.name} theme`}
            >
              <LuCheck aria-hidden="true" />
            </button>
          )}
          {onEdit && (
            <button
              type="button"
              className={styles.actionButton}
              onClick={(e) => {
                e.stopPropagation();
                onEdit();
              }}
              aria-label={`Edit ${theme.name} theme`}
            >
              <LuPencil aria-hidden="true" />
            </button>
          )}
          {onDuplicate && (
            <button
              type="button"
              className={styles.actionButton}
              onClick={(e) => {
                e.stopPropagation();
                onDuplicate();
              }}
              aria-label={`Duplicate ${theme.name} theme`}
            >
              <LuCopy aria-hidden="true" />
            </button>
          )}
          {onDelete && isUserTheme && (
            <button
              type="button"
              className={styles.actionButton}
              onClick={(e) => {
                e.stopPropagation();
                onDelete();
              }}
              aria-label={`Delete ${theme.name} theme`}
            >
              <LuTrash2 aria-hidden="true" />
            </button>
          )}
          <button
            type="button"
            className={styles.actionButton}
            onClick={(e) => {
              e.stopPropagation();
              handleExpand();
            }}
            aria-label={`Expand ${theme.name} preview`}
          >
            ↗
          </button>
        </div>
      )}
    </div>
  );
}

