import { useState, useEffect } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import styles from './glass-modal.module.css';

interface RenameMediaModalProps {
    open: boolean;
    filename: string;
    onClose: () => void;
    onSave: (newName: string) => void;
}

export function RenameMediaModal({ open, filename, onClose, onSave }: RenameMediaModalProps): JSX.Element {
    const [value, setValue] = useState(filename);

    useEffect(() => {
        setValue(filename);
    }, [filename]);

    const handleSave = () => {
        if (value.trim()) {
            onSave(value.trim());
            onClose();
        }
    };

    return (
        <Dialog.Root open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} />
                <Dialog.Content className={styles.content}>
                    <div style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column' }}>
                        <Dialog.Title style={{ margin: '0 0 1rem', fontSize: '1.25rem', fontWeight: 600, color: '#fff' }}>
                            Rename Image
                        </Dialog.Title>

                        <input
                            type="text"
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            placeholder="Enter filename"
                            autoFocus
                            style={{
                                width: '100%',
                                padding: '0.75rem',
                                borderRadius: '8px',
                                border: '1px solid rgba(255,255,255,0.1)',
                                background: 'rgba(0,0,0,0.3)',
                                color: '#fff',
                                fontSize: '1rem',
                                marginBottom: '1.5rem',
                                outline: 'none'
                            }}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') handleSave();
                            }}
                        />

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
                                Cancel
                            </button>
                            <button
                                onClick={handleSave}
                                style={{
                                    padding: '0.5rem 1rem',
                                    borderRadius: '8px',
                                    background: 'rgba(0, 255, 127, 0.1)',
                                    border: '1px solid rgba(0, 255, 127, 0.3)',
                                    color: '#00FF7F',
                                    cursor: 'pointer',
                                    fontWeight: 600
                                }}
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
