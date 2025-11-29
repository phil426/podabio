import * as Dialog from '@radix-ui/react-dialog';
import { X } from '@phosphor-icons/react';
import { WidgetInspector } from './WidgetInspector';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './widget-inspector-modal.module.css';

interface WidgetInspectorModalProps {
  activeColor: TabColorTheme;
  widgetId: string | null;
  onClose: () => void;
}

export function WidgetInspectorModal({ activeColor, widgetId, onClose }: WidgetInspectorModalProps): JSX.Element {
  const isOpen = widgetId !== null;

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
      <Dialog.Portal>
        <Dialog.Overlay 
          className={styles.overlay}
          onClick={(e) => {
            // Only close if clicking directly on overlay
            if (e.target === e.currentTarget) {
              onClose();
            }
          }}
        />
        <Dialog.Content 
          className={styles.modal} 
          aria-label="Edit Widget"
          onPointerDownOutside={(e) => {
            const target = e.target as HTMLElement;
            // Check if the click is inside a Popover Portal, Media Library Drawer, or any interactive element
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]') ||
                               target.closest('[aria-label="Media library"]') ||
                               target.closest('._drawer_');
            
            // If inside a Popover, Media Library Drawer, or slider, prevent dialog from closing
            if (isInPopover) {
              e.preventDefault();
              return;
            }
          }}
          onInteractOutside={(e) => {
            const target = e.target as HTMLElement;
            // Check if the interaction is inside a Popover Portal, Media Library Drawer, or any interactive element
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]') ||
                               target.closest('[aria-label="Media library"]') ||
                               target.closest('._drawer_');
            
            // If inside a Popover, Media Library Drawer, or slider, prevent dialog from closing
            if (isInPopover) {
              e.preventDefault();
              return;
            }
          }}
        >
          <header className={styles.header}>
            <div className={styles.headerContent}>
              <Dialog.Title className={styles.title}>Edit Widget</Dialog.Title>
              <Dialog.Description className={styles.description}>
                Configure widget settings and appearance
              </Dialog.Description>
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close modal"
                onClick={onClose}
              >
                <X aria-hidden="true" size={20} weight="regular" />
              </button>
            </Dialog.Close>
          </header>

          <div className={styles.body}>
            <WidgetInspector activeColor={activeColor} widgetId={widgetId} />
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

