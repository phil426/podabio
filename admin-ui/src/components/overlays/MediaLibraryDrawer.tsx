// MediaLibraryDrawer is now a wrapper around MediaLibraryModal for backward compatibility
// The "drawer" is now a centered modal, but keeping the old name for compatibility

import { MediaLibraryModal, type MediaLibraryModalProps } from './MediaLibraryModal';
import type { MediaItem } from '../../api/media';

interface MediaLibraryDrawerProps {
  open: boolean;
  onClose: () => void;
  onSelect: (mediaItem: MediaItem) => void;
}

export function MediaLibraryDrawer({ open, onClose, onSelect }: MediaLibraryDrawerProps): JSX.Element {
  return (
    <MediaLibraryModal
      open={open}
      onClose={onClose}
      onSelect={onSelect}
    />
  );
}
