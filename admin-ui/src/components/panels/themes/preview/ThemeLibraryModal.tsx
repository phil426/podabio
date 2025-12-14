/**
 * Theme Library Modal
 * Modal wrapper for ThemeLibraryView to allow theme selection from page background hotspot
 */

import * as Dialog from '@radix-ui/react-dialog';
import { X } from '@phosphor-icons/react';
import { usePageSnapshot, updatePageThemeId } from '../../../../api/page';
import { useThemeLibraryQuery } from '../../../../api/themes';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../../api/utils';
import type { ThemeRecord } from '../../../../api/types';
import type { TabColorTheme } from '../../../layout/tab-colors';
import { ThemeLibraryView } from '../ThemeLibraryView';
import styles from './theme-library-modal.module.css';

interface ThemeLibraryModalProps {
  isOpen: boolean;
  onClose: () => void;
  activeColor: TabColorTheme;
}

export function ThemeLibraryModal({ isOpen, onClose, activeColor }: ThemeLibraryModalProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const queryClient = useQueryClient();

  // Derive active theme from theme library (user themes retired - only system themes)
  const activeTheme: ThemeRecord | null = themeLibrary
    ? (() => {
      const themeId = snapshot?.page?.theme_id ?? null;
      if (themeId == null) {
        return themeLibrary.system?.[0] ?? null;
      }
      return themeLibrary.system?.find(theme => theme.id === themeId) ?? themeLibrary.system?.[0] ?? null;
    })()
    : null;

  const handleSelectTheme = (theme: ThemeRecord) => {
    // Just close the modal - selection is handled by onApplyTheme
    onClose();
  };

  const handleApplyTheme = async (theme: ThemeRecord) => {
    try {
      await updatePageThemeId(theme.id);
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      onClose();
    } catch (error) {
      console.error('Failed to apply theme:', error);
    }
  };


  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={`${styles.content} glassPanel`}>
          <div className={styles.header}>
            <Dialog.Title className={styles.title}>Select Theme</Dialog.Title>
            <Dialog.Description className={styles.description}>
              Choose a theme to apply to your page
            </Dialog.Description>
            <Dialog.Close asChild>
              <button className={styles.closeButton} aria-label="Close">
                <X size={20} weight="regular" />
              </button>
            </Dialog.Close>
          </div>
          <div className={styles.body}>
            <ThemeLibraryView
              themeLibrary={themeLibrary}
              activeTheme={activeTheme}
              onSelectTheme={handleSelectTheme}
              onApplyTheme={handleApplyTheme}
              activeColor={activeColor}
            />
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

