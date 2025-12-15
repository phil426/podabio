import { useRef } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { IntegrationInspector } from '../panels/IntegrationInspector';
import { tabColors } from '../layout/tab-colors';
import styles from './glass-modal.module.css';

export function IntegrationModal(): JSX.Element | null {
    const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
    const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);

    // We only show if there is a selection
    const isOpen = Boolean(selectedIntegrationId);

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            selectIntegration(null);
        }
    };

    if (!isOpen) return null;

    return (
        <Dialog.Root open={isOpen} onOpenChange={handleOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className={styles.overlay} />
                <Dialog.Content className={styles.content}>
                    <VisuallyHidden.Root asChild>
                        <Dialog.Title>Integration Settings</Dialog.Title>
                    </VisuallyHidden.Root>
                    <VisuallyHidden.Root asChild>
                        <Dialog.Description>Configure third-party integrations</Dialog.Description>
                    </VisuallyHidden.Root>
                    <IntegrationInspector activeColor={tabColors.integration} />
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
