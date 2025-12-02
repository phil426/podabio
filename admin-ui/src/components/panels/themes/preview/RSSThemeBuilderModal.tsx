/**
 * RSS Theme Builder Modal
 * Modal wrapper for PodcastPlayerInspector to allow RSS feed configuration from page background hotspot
 */

import * as Dialog from '@radix-ui/react-dialog';
import { X } from '@phosphor-icons/react';
import type { TabColorTheme } from '../../../layout/tab-colors';
import { PodcastPlayerInspector } from '../../PodcastPlayerInspector';
import styles from './rss-theme-builder-modal.module.css';

interface RSSThemeBuilderModalProps {
  isOpen: boolean;
  onClose: () => void;
  activeColor: TabColorTheme;
}

export function RSSThemeBuilderModal({ isOpen, onClose, activeColor }: RSSThemeBuilderModalProps): JSX.Element {
  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <div className={styles.header}>
            <Dialog.Title className={styles.title}>Build RSS Theme</Dialog.Title>
            <Dialog.Description className={styles.description}>
              Configure your podcast RSS feed and generate a theme from your podcast cover
            </Dialog.Description>
            <Dialog.Close asChild>
              <button className={styles.closeButton} aria-label="Close">
                <X size={20} weight="regular" />
              </button>
            </Dialog.Close>
          </div>
          <div className={styles.body}>
            <PodcastPlayerInspector activeColor={activeColor} />
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

