import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
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
                    <VisuallyHidden.Root asChild>
                        <Dialog.Title>Podcast Settings</Dialog.Title>
                    </VisuallyHidden.Root>
                    <VisuallyHidden.Root asChild>
                        <Dialog.Description>Configure podcast RSS and player settings</Dialog.Description>
                    </VisuallyHidden.Root>
                    <PodcastInspector activeColor={tabColors.podcast} />
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
