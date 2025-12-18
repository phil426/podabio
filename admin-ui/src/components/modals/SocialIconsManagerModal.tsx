
import * as Dialog from '@radix-ui/react-dialog';
import { X, List } from '@phosphor-icons/react';
import { SocialIconsManager } from '../panels/SocialIconsManager';
import styles from '../panels/themes/content-editor-modal.module.css'; // Reuse existing modal styles

interface SocialIconsManagerModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export function SocialIconsManagerModal({ isOpen, onClose }: SocialIconsManagerModalProps): JSX.Element {
    return (
        <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
            <Dialog.Portal>
                <Dialog.Overlay
                    className={styles.overlay}
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            onClose();
                        }
                    }}
                />
                <Dialog.Content
                    className={`${styles.modal} glassPanel`}
                    aria-label="Manage Social Icons"
                    onPointerDownOutside={(e) => {
                        // Prevent closing if interacting with popovers inside
                        const target = e.target as HTMLElement;
                        const isInPopover = target.closest('[data-radix-popover-content]') ||
                            target.closest('[data-radix-portal]');
                        if (isInPopover) {
                            e.preventDefault();
                        }
                    }}
                >
                    <header className={styles.header}>
                        <div className={styles.headerContent}>
                            <Dialog.Title className={styles.title}>Manage Social Icons</Dialog.Title>
                            <Dialog.Description className={styles.description}>
                                Add, reorder, and remove social media links
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
                        <SocialIconsManager />
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
