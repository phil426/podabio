import * as Dialog from '@radix-ui/react-dialog';
import styles from './glass-modal.module.css'; // Reusing existing glass styles

interface ConfirmModalProps {
    open: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
}

export function ConfirmModal({
    open,
    onClose,
    onConfirm,
    title,
    description,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel'
}: ConfirmModalProps): JSX.Element {
    return (
        <Dialog.Root open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} />
                <Dialog.Content className={styles.content}>
                    <div style={{ padding: '1.5rem' }}>
                        <Dialog.Title style={{ margin: '0 0 0.5rem', fontSize: '1.25rem', fontWeight: 600, color: '#fff' }}>
                            {title}
                        </Dialog.Title>
                        <Dialog.Description style={{ margin: '0 0 1.5rem', fontSize: '0.95rem', color: 'rgba(255,255,255,0.7)', lineHeight: 1.5 }}>
                            {description}
                        </Dialog.Description>

                        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem' }}>
                            <button
                                onClick={onClose}
                                style={{
                                    padding: '0.5rem 1rem',
                                    borderRadius: '8px',
                                    background: 'rgba(255,255,255,0.05)',
                                    border: '1px solid rgba(255,255,255,0.1)',
                                    color: '#fff',
                                    cursor: 'pointer',
                                    fontWeight: 500
                                }}
                            >
                                {cancelLabel}
                            </button>
                            <button
                                onClick={() => { onConfirm(); onClose(); }}
                                style={{
                                    padding: '0.5rem 1rem',
                                    borderRadius: '8px',
                                    background: 'rgba(239, 68, 68, 0.2)',
                                    border: '1px solid rgba(239, 68, 68, 0.4)',
                                    color: '#fca5a5',
                                    cursor: 'pointer',
                                    fontWeight: 600
                                }}
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
