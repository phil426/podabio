/**
 * Confirm Delete Dialog
 * A reusable confirmation dialog for destructive actions
 */

import * as Dialog from '@radix-ui/react-dialog';
import { Warning, X } from '@phosphor-icons/react';
import styles from './confirm-delete-dialog.module.css';

interface ConfirmDeleteDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
}

export function ConfirmDeleteDialog({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmLabel = 'Delete',
  cancelLabel = 'Cancel'
}: ConfirmDeleteDialogProps): JSX.Element {
  const handleConfirm = () => {
    onConfirm();
    onClose();
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <button
            type="button"
            className={styles.closeButton}
            onClick={onClose}
            aria-label="Close"
          >
            <X aria-hidden="true" size={20} weight="regular" />
          </button>

          <div className={styles.body}>
            <div className={styles.icon}>
              <Warning aria-hidden="true" size={24} weight="regular" />
            </div>

            <h2 className={styles.title}>{title}</h2>
            
            <p className={styles.message}>{message}</p>

            <div className={styles.actions}>
              <button
                type="button"
                className={styles.cancelButton}
                onClick={onClose}
              >
                {cancelLabel}
              </button>
              <button
                type="button"
                className={styles.confirmButton}
                onClick={handleConfirm}
              >
                {confirmLabel}
              </button>
            </div>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

