/**
 * Podcast Theme Prompt Dialog
 * Standalone prompt component asking user if they want to generate a theme
 */

import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from '@radix-ui/react-visually-hidden';
import { Sparkle, X } from '@phosphor-icons/react';
import styles from './podcast-theme-prompt-dialog.module.css';

interface PodcastThemePromptDialogProps {
  isOpen: boolean;
  onAccept: () => void;
  onDecline: () => void;
}

export function PodcastThemePromptDialog({
  isOpen,
  onAccept,
  onDecline
}: PodcastThemePromptDialogProps): JSX.Element {
  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onDecline()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <VisuallyHidden.Root asChild>
            <Dialog.Title>Theme Wizard?</Dialog.Title>
          </VisuallyHidden.Root>
          <VisuallyHidden.Root asChild>
            <Dialog.Description>
              We can extract colors from your cover art and create a custom theme. This will automatically set your page colors, fonts, and styling to match your branding.
            </Dialog.Description>
          </VisuallyHidden.Root>
          
          <button
            type="button"
            className={styles.closeButton}
            onClick={onDecline}
            aria-label="Close"
          >
            <X aria-hidden="true" size={20} weight="regular" />
          </button>

          <div className={styles.body}>
            <div className={styles.icon}>
              <Sparkle aria-hidden="true" size={20} weight="regular" />
            </div>

            <h2 className={styles.title}>Theme Wizard?</h2>
            
            <p className={styles.description}>
              We can extract colors from your cover art and create a custom theme. This will automatically set your page colors,
              fonts, and styling to match your branding.
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
                Open Theme Wizard
              </button>
            </div>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

