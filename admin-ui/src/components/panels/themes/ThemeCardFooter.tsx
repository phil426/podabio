/**
 * Theme Card Footer
 * Displays title, actions, and edit controls
 */

import { useState, useRef, useEffect } from 'react';
import clsx from 'clsx';
import { Pencil, Trash } from '@phosphor-icons/react';
import { useRenameThemeMutation } from '../../../api/themes';
import type { ThemeRecord } from '../../../api/types';
import styles from '../ThemePreviewCard.module.css';

interface ThemeCardFooterProps {
  theme: ThemeRecord;
  selected: boolean;
  primaryActionLabel?: string;
  onSelect: () => void;
  tertiaryActions?: {
    onRename?: () => void;
    onDelete?: () => void;
  };
  disabled?: boolean;
  isActiveCard: boolean;
}

export function ThemeCardFooter({
  theme,
  selected,
  primaryActionLabel,
  onSelect,
  tertiaryActions,
  disabled,
  isActiveCard
}: ThemeCardFooterProps): JSX.Element {
  const [isEditing, setIsEditing] = useState(false);
  const [editedName, setEditedName] = useState(theme.name ?? '');
  const inputRef = useRef<HTMLInputElement>(null);
  const renameMutation = useRenameThemeMutation();
  const isUserTheme = Boolean(theme.user_id);

  useEffect(() => {
    if (isEditing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [isEditing]);

  useEffect(() => {
    setEditedName(theme.name ?? '');
  }, [theme.name]);

  const handleStartEdit = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (isUserTheme && !disabled) {
      setIsEditing(true);
    }
  };

  const handleSaveEdit = async () => {
    if (editedName.trim() && editedName.trim() !== theme.name && isUserTheme) {
      try {
        await renameMutation.mutateAsync({
          themeId: theme.id,
          name: editedName.trim()
        });
      } catch (error) {
        console.error('Failed to rename theme:', error);
        setEditedName(theme.name ?? '');
      }
    } else {
      setEditedName(theme.name ?? '');
    }
    setIsEditing(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleSaveEdit();
    } else if (e.key === 'Escape') {
      setEditedName(theme.name ?? '');
      setIsEditing(false);
    }
  };

  const themeLabel = isActiveCard
    ? 'Current Theme'
    : theme.name ?? 'Custom Theme';

  return (
    <footer className={styles.footer}>
      <div className={styles.footerTop}>
        {isEditing ? (
          <input
            ref={inputRef}
            type="text"
            className={clsx(styles.title, styles.titleInput)}
            value={editedName}
            onChange={(e) => setEditedName(e.target.value)}
            onBlur={handleSaveEdit}
            onKeyDown={handleKeyDown}
            onClick={(e) => e.stopPropagation()}
            disabled={renameMutation.isPending}
          />
        ) : (
          <h5 
            className={clsx(styles.title, isUserTheme && styles.titleEditable)}
            onClick={handleStartEdit}
            title={isUserTheme ? 'Click to edit name' : undefined}
          >
            {themeLabel}
          </h5>
        )}
        {tertiaryActions && (tertiaryActions.onRename || tertiaryActions.onDelete) && (
          <div className={styles.iconActions} aria-label="Theme actions">
            {tertiaryActions.onRename && (
              <button
                type="button"
                className={styles.iconButton}
                onClick={(e) => {
                  e.stopPropagation();
                  tertiaryActions.onRename?.();
                }}
                disabled={disabled}
                aria-label="Rename theme"
                title="Rename theme"
              >
                <Pencil aria-hidden="true" size={14} weight="regular" />
              </button>
            )}
            {tertiaryActions.onDelete && (
              <button
                type="button"
                className={styles.iconButton}
                onClick={(e) => {
                  e.stopPropagation();
                  tertiaryActions.onDelete?.();
                }}
                disabled={disabled}
                aria-label="Delete theme"
                title="Delete theme"
              >
                <Trash aria-hidden="true" size={14} weight="regular" />
              </button>
            )}
          </div>
        )}
      </div>
      {primaryActionLabel && (
        <div className={styles.actionRow}>
          <button
            type="button"
            className={styles.applyButton}
            onClick={(e) => {
              e.stopPropagation();
              onSelect();
            }}
            disabled={disabled}
          >
            {primaryActionLabel}
          </button>
        </div>
      )}
    </footer>
  );
}

