import * as Dialog from '@radix-ui/react-dialog';
import { MediaLibrary } from '../media/MediaLibrary';
import { MediaItem } from '../../api/media';
import styles from './media-library-modal.module.css';

export interface MediaLibraryModalProps {
  open: boolean;
  onClose: () => void;
  onSelect?: (mediaItem: MediaItem) => void;
}

export function MediaLibraryModal({
  open,
  onClose,
  onSelect
}: MediaLibraryModalProps): JSX.Element {

  const handleSelect = (item: MediaItem) => {
    if (onSelect) {
      onSelect(item);
    }
    onClose();
  };

  return (
    <Dialog.Root open={open} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={`${styles.modal} glassPanel`} aria-label="Media Library" style={{ padding: 0, background: 'none', border: 'none', boxShadow: 'none' }}>
          <MediaLibrary
            mode="pick"
            onSelect={handleSelect}
            className={styles.modalContentOverride}
            headerContent={
              <Dialog.Close asChild>
                <button type="button" className={styles.closeButton} aria-label="Close">
                  Close
                </button>
              </Dialog.Close>
            }
          />
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

