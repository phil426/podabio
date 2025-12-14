import { Images } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface PhotoTabContentProps {
    uploadedImageUrl: string | null;
    isUploading: boolean;
    onOpenMediaLibrary: () => void;
}

export function PhotoTabContent({
    uploadedImageUrl,
    onOpenMediaLibrary
}: PhotoTabContentProps) {
    return (
        <div className={styles.tabContent}>
            <div className={styles.uploadSection}>
                {!uploadedImageUrl ? (
                    <div className={styles.uploadPlaceholder}>
                        <div className={styles.uploadIconWrapper}>
                            <Images size={48} weight="light" />
                        </div>
                        <h3>Select Cover Art</h3>
                        <p>Choose an image from your media library</p>
                        <div className={styles.uploadActions}>
                            <button
                                className={styles.secondaryButton}
                                onClick={onOpenMediaLibrary}
                                style={{ width: '100%', justifyContent: 'center' }}
                            >
                                <Images size={20} />
                                <span>Open Media Library</span>
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className={styles.previewContainer}>
                        <img
                            src={uploadedImageUrl}
                            alt="Uploaded cover"
                            className={styles.uploadedPreview}
                        />
                        <div className={styles.uploadedActions}>
                            <button
                                className={styles.textButton}
                                onClick={onOpenMediaLibrary}
                            >
                                Change Image
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
