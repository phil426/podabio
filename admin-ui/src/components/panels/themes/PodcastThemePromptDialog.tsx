/**
 * Podcast Theme Prompt Dialog
 * Standalone prompt component asking user if they want to generate a theme
 */

import * as Dialog from '@radix-ui/react-dialog';
import { Sparkle, X } from '@phosphor-icons/react';
import styles from './podcast-theme-prompt-dialog.module.css';

interface PodcastThemePromptDialogProps {
  coverImageUrl: string | null;
  podcastName: string | null;
  isOpen: boolean;
  onAccept: () => void;
  onDecline: () => void;
}

export function PodcastThemePromptDialog({
  coverImageUrl,
  podcastName,
  isOpen,
  onAccept,
  onDecline
}: PodcastThemePromptDialogProps): JSX.Element {
  // Decode HTML entities
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  const decodedPodcastName = podcastName ? decodeHtmlEntities(podcastName) : null;

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onDecline()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <button
            type="button"
            className={styles.closeButton}
            onClick={onDecline}
            aria-label="Close"
          >
            <X aria-hidden="true" size={20} weight="regular" />
          </button>

          <div className={styles.body}>
            {coverImageUrl && (
              <div className={styles.coverImage}>
                <img src={coverImageUrl} alt={decodedPodcastName || 'Podcast cover'} />
              </div>
            )}

            <div className={styles.icon}>
              <Sparkle aria-hidden="true" size={20} weight="regular" />
            </div>

            <h2 className={styles.title}>Generate Theme from Podcast?</h2>
            
            <p className={styles.description}>
              We can extract colors from your podcast cover art and create a custom theme
              {decodedPodcastName && ` for "${decodedPodcastName}"`}. This will automatically set your page colors,
              fonts, and styling to match your podcast branding.
            </p>

            <div className={styles.actions}>
              <button
                type="button"
                className={styles.declineButton}
                onClick={onDecline}
              >
                Maybe Later
              </button>
              <button
                type="button"
                className={styles.acceptButton}
                onClick={onAccept}
              >
                Generate Theme
              </button>
            </div>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

