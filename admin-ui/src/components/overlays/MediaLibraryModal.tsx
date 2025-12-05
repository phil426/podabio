import { useState, useRef, useCallback, useEffect } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { X, Upload, Trash, MagnifyingGlass, Images } from '@phosphor-icons/react';
import * as ScrollArea from '@radix-ui/react-scroll-area';

import {
  useMediaLibraryQuery,
  useUploadToMediaLibraryMutation,
  useDeleteMediaItemMutation,
  type MediaItem
} from '../../api/media';
import { normalizeImageUrl } from '../../api/utils';
import { ImageCropModal } from './ImageCropModal';

import styles from './media-library-modal.module.css';

export interface MediaLibraryModalProps {
  open: boolean;
  onClose: () => void;
  onSelect?: (mediaItem: MediaItem) => void;
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

export function MediaLibraryModal({ open, onClose, onSelect }: MediaLibraryModalProps): JSX.Element {
  const [searchTerm, setSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [cropModalOpen, setCropModalOpen] = useState(false);
  const [imageToCrop, setImageToCrop] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  // Reset state when modal opens
  useEffect(() => {
    if (open) {
      setSearchTerm('');
      setPage(1);
    }
  }, [open]);

  const {
    data: mediaData,
    isLoading,
    isError,
    error
  } = useMediaLibraryQuery({
    page,
    per_page: 24,
    search: searchTerm.trim() || undefined
  });

  const uploadMutation = useUploadToMediaLibraryMutation();
  const deleteMutation = useDeleteMediaItemMutation();

  const handleFileSelect = useCallback(async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      alert('Invalid file type. Please use JPEG, PNG, GIF, or WebP format.');
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      return;
    }

    // Check file size (5MB limit)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
      alert('File size exceeds the maximum allowed size of 5MB.');
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      return;
    }

    // Create preview URL and show crop modal
    const reader = new FileReader();
    reader.onload = (e) => {
      const imageSrc = e.target?.result as string;
      setImageToCrop(imageSrc);
      setCropModalOpen(true);
    };
    reader.readAsDataURL(file);

    // Reset file input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, []);

  const handleCropComplete = useCallback(async (croppedImageBlob: Blob) => {
    try {
      const croppedFile = new File([croppedImageBlob], 'image.jpg', { type: 'image/jpeg' });
      await uploadMutation.mutateAsync(croppedFile);
      setCropModalOpen(false);
      setImageToCrop(null);
    } catch (error) {
      console.error('Upload failed:', error);
      alert(error instanceof Error ? error.message : 'Failed to upload image');
    }
  }, [uploadMutation]);

  const handleDelete = useCallback(async (e: React.MouseEvent, mediaId: number) => {
    e.stopPropagation();
    
    if (!confirm('Are you sure you want to delete this image?')) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(mediaId);
    } catch (error) {
      console.error('Delete failed:', error);
      alert(error instanceof Error ? error.message : 'Failed to delete image');
    }
  }, [deleteMutation]);

  const handleSearchChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
    setPage(1); // Reset to first page when searching
  }, []);

  const handleSelectItem = useCallback((item: MediaItem) => {
    if (onSelect) {
      onSelect(item);
      onClose();
    }
  }, [onSelect, onClose]);

  const mediaItems = mediaData?.media ?? [];
  const totalPages = mediaData?.total_pages ?? 1;

  return (
    <Dialog.Root open={open} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.modal} aria-label="Media Library">
          <header className={styles.header}>
            <div className={styles.headerContent}>
              <Dialog.Title className={styles.title}>Media Library</Dialog.Title>
              <Dialog.Description className={styles.description}>
                Browse and manage your uploaded images.
              </Dialog.Description>
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close media library"
              >
                <X size={20} weight="regular" aria-hidden="true" />
              </button>
            </Dialog.Close>
          </header>

          <div className={styles.controls}>
            <div className={styles.searchContainer}>
              <MagnifyingGlass size={16} weight="regular" className={styles.searchIcon} aria-hidden="true" />
              <input
                type="search"
                value={searchTerm}
                onChange={handleSearchChange}
                placeholder="Search by filename..."
                className={styles.searchInput}
              />
            </div>

            <button
              type="button"
              className={styles.uploadButton}
              onClick={() => fileInputRef.current?.click()}
              disabled={uploadMutation.isPending}
            >
              <Upload size={16} weight="regular" aria-hidden="true" />
              {uploadMutation.isPending ? 'Uploading...' : 'Upload Image'}
            </button>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              onChange={handleFileSelect}
              className={styles.hiddenInput}
            />
          </div>

          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <section className={styles.gridSection} aria-live="polite">
                {isLoading ? (
                  <div className={styles.emptyState}>
                    <p>Loading media library...</p>
                  </div>
                ) : isError ? (
                  <div className={styles.emptyState}>
                    <p>Error loading media library: {error instanceof Error ? error.message : 'Unknown error'}</p>
                  </div>
                ) : mediaItems.length === 0 ? (
                  <div className={styles.emptyState}>
                    <Images size={48} weight="regular" aria-hidden="true" />
                    <p>{searchTerm ? 'No images match your search.' : 'No images in your library yet.'}</p>
                    <p className={styles.emptyStateSubtext}>
                      Upload images to get started.
                    </p>
                  </div>
                ) : (
                  <>
                    <ul className={styles.grid}>
                      {mediaItems.map((item) => (
                        <li
                          key={item.id}
                          className={styles.card}
                          onClick={() => handleSelectItem(item)}
                        >
                          <div className={styles.cardImageContainer}>
                            <img
                              src={normalizeImageUrl(item.file_url)}
                              alt={item.filename}
                              className={styles.cardImage}
                              loading="lazy"
                            />
                            <div className={styles.cardOverlay}>
                              <div className={styles.segmentedBar}>
                                {onSelect && (
                                  <>
                                    <button
                                      type="button"
                                      className={styles.segmentedButton}
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        handleSelectItem(item);
                                      }}
                                    >
                                      Select
                                    </button>
                                    <div className={styles.segmentedDivider} />
                                  </>
                                )}
                                <button
                                  type="button"
                                  className={styles.segmentedButton}
                                  onClick={(e) => handleDelete(e, item.id)}
                                  disabled={deleteMutation.isPending}
                                  title="Delete image"
                                >
                                  <Trash size={14} weight="regular" aria-hidden="true" />
                                </button>
                              </div>
                            </div>
                          </div>
                          <div className={styles.cardInfo}>
                            <p className={styles.cardFilename} title={item.filename}>
                              {item.filename}
                            </p>
                            <p className={styles.cardMeta}>
                              {formatFileSize(item.file_size)} • {formatDate(item.uploaded_at)}
                            </p>
                          </div>
                        </li>
                      ))}
                    </ul>

                    {totalPages > 1 && (
                      <div className={styles.pagination}>
                        <button
                          type="button"
                          className={styles.paginationButton}
                          onClick={() => setPage(p => Math.max(1, p - 1))}
                          disabled={page === 1}
                        >
                          Previous
                        </button>
                        <span className={styles.paginationInfo}>
                          Page {page} of {totalPages}
                        </span>
                        <button
                          type="button"
                          className={styles.paginationButton}
                          onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                          disabled={page >= totalPages}
                        >
                          Next
                        </button>
                      </div>
                    )}
                  </>
                )}
              </section>
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
        </Dialog.Content>
      </Dialog.Portal>
      {imageToCrop && (
        <ImageCropModal
          open={cropModalOpen}
          onClose={() => {
            setCropModalOpen(false);
            setImageToCrop(null);
          }}
          imageSrc={imageToCrop}
          onCropComplete={handleCropComplete}
          aspectRatio={undefined} // Free crop for media library
          cropShape="rect"
          minZoom={1}
          maxZoom={3}
          initialZoom={1}
        />
      )}
    </Dialog.Root>
  );
}

