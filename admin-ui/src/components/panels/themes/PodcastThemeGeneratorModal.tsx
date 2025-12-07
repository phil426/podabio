/**
 * Podcast Theme Generator Modal
 * Wrapper component providing modal/drawer container for PodcastThemeGenerator
 */

import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
import { PodcastThemeGenerator } from './PodcastThemeGenerator';
import { ThemeWizardErrorBoundary } from './components/ThemeWizardErrorBoundary';
import styles from './podcast-theme-generator-modal.module.css';

interface PodcastThemeGeneratorModalProps {
  coverImageUrl: string | null; // Initial RSS feed cover image
  isOpen: boolean;
  onClose: () => void;
  onThemeGenerated?: (themeId: number) => void;
}

export function PodcastThemeGeneratorModal({
  coverImageUrl,
  isOpen,
  onClose,
  onThemeGenerated
}: PodcastThemeGeneratorModalProps): JSX.Element {
  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <VisuallyHidden.Root asChild>
            <Dialog.Title>Theme Wizard</Dialog.Title>
          </VisuallyHidden.Root>
          <VisuallyHidden.Root asChild>
            <Dialog.Description>
              Extract colors from images to create custom themes
            </Dialog.Description>
          </VisuallyHidden.Root>
          <ThemeWizardErrorBoundary onReset={onClose}>
            <PodcastThemeGenerator
              coverImageUrl={coverImageUrl}
              onClose={onClose}
              onThemeGenerated={onThemeGenerated}
            />
          </ThemeWizardErrorBoundary>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

