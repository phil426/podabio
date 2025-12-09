import * as Dialog from '@radix-ui/react-dialog';
import { PodcastInspector } from '../panels/PodcastInspector';
import { tabColors } from '../layout/tab-colors';
import styles from './glass-modal.module.css';

interface PodcastSettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    activeSection: 'rss' | 'player' | 'search' | 'podlinks' | null;
}

export function PodcastSettingsModal({ isOpen, onClose, activeSection }: PodcastSettingsModalProps): JSX.Element | null {
    if (!isOpen || !activeSection) return null;

    return (
        <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} />
                <Dialog.Content className={styles.content}>
                    <PodcastInspector activeColor={tabColors.podcast} />
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
