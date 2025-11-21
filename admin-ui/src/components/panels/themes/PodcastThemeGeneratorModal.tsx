/**
 * Podcast Theme Generator Modal
 * Wrapper component providing modal/drawer container for PodcastThemeGenerator
 */

import * as Dialog from '@radix-ui/react-dialog';
import { PodcastThemeGenerator } from './PodcastThemeGenerator';
import styles from './podcast-theme-generator-modal.module.css';

interface PodcastThemeGeneratorModalProps {
  coverImageUrl: string | null;
  podcastName: string | null;
  podcastDescription: string | null;
  isOpen: boolean;
  onClose: () => void;
  onThemeGenerated?: (themeId: number) => void;
}

export function PodcastThemeGeneratorModal({
  coverImageUrl,
  podcastName,
  podcastDescription,
  isOpen,
  onClose,
  onThemeGenerated
}: PodcastThemeGeneratorModalProps): JSX.Element {
  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <PodcastThemeGenerator
            coverImageUrl={coverImageUrl}
            podcastName={podcastName}
            podcastDescription={podcastDescription}
            onClose={onClose}
            onThemeGenerated={onThemeGenerated}
          />
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

