import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { SocialIconInspector } from '../panels/SocialIconInspector';
import { tabColors } from '../layout/tab-colors';
import styles from './glass-modal.module.css';

export function SocialIconModal(): JSX.Element | null {
    const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
    const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);

    const isOpen = Boolean(selectedSocialIconId);

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            selectSocialIcon(null);
        }
    };

    if (!isOpen) return null;

    return (
        <Dialog.Root open={isOpen} onOpenChange={handleOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} />
                <Dialog.Content className={styles.content}>
                    <VisuallyHidden.Root asChild>
                        <Dialog.Title>Social Icons</Dialog.Title>
                    </VisuallyHidden.Root>
                    <VisuallyHidden.Root asChild>
                        <Dialog.Description>Configure social media links</Dialog.Description>
                    </VisuallyHidden.Root>
                    <SocialIconInspector activeColor={tabColors.integration} />
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
