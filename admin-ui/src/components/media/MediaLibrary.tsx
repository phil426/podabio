import { useState, useRef, useCallback, ReactNode } from 'react';
import { Upload, Trash, MagnifyingGlass, Images, PencilSimple, X } from '@phosphor-icons/react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import clsx from 'clsx';
import { SegmentedControl } from '../common/SegmentedControl';
import { RenameMediaModal } from '../modals/RenameMediaModal';
import { ConfirmModal } from '../modals/ConfirmModal';
import {
    useMediaLibraryQuery,
    useUploadToMediaLibraryMutation,
    useDeleteMediaItemMutation,
    useStockPhotosQuery,
    useUpdateMediaItemMutation,
    type MediaItem,
    importStockPhoto
} from '../../api/media';
import { normalizeImageUrl } from '../../api/utils';
import styles from './media-library.module.css';

interface MediaLibraryProps {
    mode?: 'manage' | 'pick';
    onSelect?: (item: MediaItem) => void;
    className?: string;
    headerContent?: ReactNode; // Allow injecting header content (like close button)
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

function copyToClipboard(text: string) {
    if (!text) return;
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        // Could show a toast here if we had a toast system
    } catch (error) {
        console.error('Unable to copy to clipboard', error);
    }
}

export function MediaLibrary({ mode = 'manage', onSelect, className, headerContent }: MediaLibraryProps): JSX.Element {
    const [source, setSource] = useState<'library' | 'stock'>('library');
    const [searchTerm, setSearchTerm] = useState('');
    const [page, setPage] = useState(1);
    const [importingId, setImportingId] = useState<number | null>(null);
    const [itemToRename, setItemToRename] = useState<MediaItem | null>(null);
    const [itemToDelete, setItemToDelete] = useState<MediaItem | null>(null);

    const fileInputRef = useRef<HTMLInputElement | null>(null);

    // Queries
    const libraryQuery = useMediaLibraryQuery({
        page,
        per_page: 24,
        search: source === 'library' ? searchTerm.trim() || undefined : undefined
    });

    const stockQuery = useStockPhotosQuery(
        'all',
        searchTerm,
        page,
        source === 'stock'
    );

    const activeQuery = source === 'library' ? libraryQuery : stockQuery;
    const { data: mediaData, isLoading, isError, error } = activeQuery;

    const uploadMutation = useUploadToMediaLibraryMutation();
    const deleteMutation = useDeleteMediaItemMutation();
    const updateMutation = useUpdateMediaItemMutation();

    const handleFileSelect = useCallback(async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        try {
            await uploadMutation.mutateAsync(file);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        } catch (error) {
            console.error('Upload failed:', error);
            alert(error instanceof Error ? error.message : 'Failed to upload image');
        }
    }, [uploadMutation]);

    const handleDeleteParams = useCallback(async () => {
        if (!itemToDelete) return;
        try {
            await deleteMutation.mutateAsync(itemToDelete.id);
            setItemToDelete(null);
        } catch (error) {
            console.error('Delete failed:', error);
            alert(error instanceof Error ? error.message : 'Failed to delete image');
        }
    }, [deleteMutation, itemToDelete]);

    const handleRenameParams = useCallback(async (newFilename: string) => {
        if (!itemToRename) return;
        try {
            await updateMutation.mutateAsync({ id: itemToRename.id, data: { filename: newFilename } });
            setItemToRename(null);
        } catch (error) {
            console.error('Rename failed:', error);
            alert(error instanceof Error ? error.message : 'Failed to rename image');
        }
    }, [updateMutation, itemToRename]);

    const handleImport = useCallback(async (e: React.MouseEvent, item: MediaItem) => {
        e.stopPropagation();
        try {
            setImportingId(item.id);
            // For stock photos, 'url' or 'file_url' holds the source
            const importUrl = item.url || item.file_url;
            const importedItem = await importStockPhoto(
                importUrl,
                'all', // Provider aggregated
                item.author || 'Unknown'
            );

            // If in pick mode, select it immediately
            if (mode === 'pick' && onSelect && importedItem.media) {
                onSelect(importedItem.media);
            } else {
                // Otherwise switch to library to show it
                setSource('library');
                setSearchTerm('');
                alert('Image imported successfully!');
            }
        } catch (error) {
            console.error('Import failed:', error);
            alert('Failed to download image. Please try another.');
        } finally {
            setImportingId(null);
        }
    }, [mode, onSelect]);

    const handleSearchChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchTerm(e.target.value);
        setPage(1);
    }, []);

    // Handle both response formats
    const mediaItems = mediaData?.results || mediaData?.media || [];
    const totalPages = mediaData?.total_pages ?? 1;

    return (
        <div className={clsx(styles.container, mode === 'pick' ? 'glassPanel' : styles.integratedPanel, className)}>
            {mode === 'pick' && (
                <header className={styles.header}>
                    <div>
                        <h2 className={styles.title}>Media Library</h2>
                        <p className={styles.description}>Select an image or upload a new one.</p>
                    </div>
                    {headerContent}
                </header>
            )}

            <div className={styles.controls}>
                <div className={styles.segmentedWrapper}>
                    <SegmentedControl
                        options={[
                            { value: 'library', label: 'My Library', icon: <Images size={16} /> },
                            { value: 'stock', label: 'Stock Images', icon: <MagnifyingGlass size={16} /> },
                        ]}
                        value={source}
                        onChange={(val: string) => setSource(val as 'library' | 'stock')}
                    />
                </div>

                <div className={styles.searchContainer}>
                    <MagnifyingGlass size={16} weight="regular" className={styles.searchIcon} aria-hidden="true" />
                    <input
                        type="search"
                        value={searchTerm}
                        onChange={handleSearchChange}
                        placeholder={source === 'stock' ? "Search Unsplash & Pexels..." : "Search by filename..."}
                        className={styles.searchInput}
                    />
                </div>

                {source === 'library' && (
                    <>
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
                    </>
                )}
            </div>

            <ScrollArea.Root className={styles.scrollArea}>
                <ScrollArea.Viewport className={styles.viewport}>
                    <section className={styles.gridSection} aria-live="polite">
                        {isLoading ? (
                            <div className={styles.emptyState}>
                                <p>Loading...</p>
                            </div>
                        ) : isError ? (
                            <div className={styles.emptyState}>
                                <p>Error loading media: {error instanceof Error ? error.message : 'Unknown error'}</p>
                            </div>
                        ) : mediaItems.length === 0 ? (
                            <div className={styles.emptyState}>
                                <Images size={48} weight="regular" aria-hidden="true" />
                                <p>{searchTerm ? 'No images match your search.' : source === 'stock' ? 'Search for free stock photos.' : 'No images in your library yet.'}</p>
                                {source === 'library' && (
                                    <p className={styles.emptyStateSubtext}>
                                        Upload images to get started.
                                    </p>
                                )}
                            </div>
                        ) : (
                            <>
                                <ul className={styles.grid}>
                                    {mediaItems.map((item) => (
                                        <li
                                            key={item.id}
                                            className={styles.card}
                                            onClick={() => {
                                                if (mode === 'pick' && onSelect && source === 'library') {
                                                    onSelect(item);
                                                }
                                            }}
                                        >
                                            <div className={styles.cardImageContainer}>
                                                {/* Show importing overlay if applicable */}
                                                {importingId === item.id && (
                                                    <div className={styles.loadingOverlay}>
                                                        Importing...
                                                    </div>
                                                )}
                                                <img
                                                    src={normalizeImageUrl(item.url || item.file_url)}
                                                    alt={item.filename || 'Stock Photo'}
                                                    className={styles.cardImage}
                                                    loading="lazy"
                                                />

                                                {/* Always show overlay logic, but actions depend on mode/source */}
                                                <div className={styles.cardOverlay}>
                                                    <div className={styles.segmentedBar}>
                                                        {source === 'library' ? (
                                                            <>
                                                                {mode === 'manage' ? (
                                                                    // Manage Mode: Copy / Rename / Delete
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            className={styles.segmentedButton}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                copyToClipboard(item.file_url);
                                                                            }}
                                                                            title="Copy URL"
                                                                        >
                                                                            Copy
                                                                        </button>
                                                                        <div className={styles.segmentedDivider} />
                                                                        <button
                                                                            type="button"
                                                                            className={styles.segmentedButton}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                setItemToRename(item);
                                                                            }}
                                                                            title="Rename"
                                                                        >
                                                                            <PencilSimple size={14} />
                                                                        </button>
                                                                        <div className={styles.segmentedDivider} />
                                                                        <button
                                                                            type="button"
                                                                            className={styles.segmentedButton}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                setItemToDelete(item);
                                                                            }}
                                                                            title="Delete"
                                                                        >
                                                                            <Trash size={14} />
                                                                        </button>
                                                                    </>
                                                                ) : (
                                                                    // Pick Mode: Copy / Delete (Rename less usual but possible - let's keep it simple for pick)
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            className={styles.segmentedButton}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                copyToClipboard(item.file_url);
                                                                            }}
                                                                            title="Copy URL"
                                                                        >
                                                                            Copy
                                                                        </button>
                                                                        <div className={styles.segmentedDivider} />
                                                                        <button
                                                                            type="button"
                                                                            className={styles.segmentedButton}
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                setItemToDelete(item);
                                                                            }}
                                                                            title="Delete"
                                                                        >
                                                                            <Trash size={14} />
                                                                        </button>
                                                                    </>
                                                                )}
                                                            </>
                                                        ) : (
                                                            // Stock Source: Import
                                                            <button
                                                                type="button"
                                                                className={styles.segmentedButton}
                                                                onClick={(e) => handleImport(e, item)}
                                                                disabled={importingId === item.id}
                                                                title="Import"
                                                                style={{ color: '#00FF7F', width: '100%' }}
                                                            >
                                                                {importingId === item.id ? 'Importing...' : 'Import'}
                                                            </button>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className={styles.cardInfo}>
                                                <p className={styles.cardFilename} title={item.filename || item.author}>
                                                    {item.filename || `Photo by ${item.author}`}
                                                </p>
                                                <p className={styles.cardMeta}>
                                                    {source === 'library'
                                                        ? `${formatFileSize(item.file_size)} • ${item.uploaded_at ? formatDate(item.uploaded_at) : 'Unknown'}`
                                                        : (item.provider === 'unsplash' ? 'Unsplash' : 'Pexels')
                                                    }
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

            {/* Modals */}
            {itemToRename && (
                <RenameMediaModal
                    open={!!itemToRename}
                    filename={itemToRename.filename || ''}
                    onClose={() => setItemToRename(null)}
                    onSave={handleRenameParams}
                />
            )}

            {itemToDelete && (
                <ConfirmModal
                    open={!!itemToDelete}
                    title="Delete Image?"
                    description="Are you sure you want to delete this image? This action cannot be undone."
                    onClose={() => setItemToDelete(null)}
                    onConfirm={handleDeleteParams}
                    confirmLabel="Delete"
                />
            )}
        </div>
    );
}
