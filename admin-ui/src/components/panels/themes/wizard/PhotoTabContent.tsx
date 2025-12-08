import { Upload, FolderOpen, CircleNotch } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

interface PhotoTabContentProps {
    uploadedImageUrl: string | null;
    isUploading: boolean;
    onFileUpload: (file: File) => void;
    onOpenMediaLibrary: () => void;
}

export function PhotoTabContent({
    uploadedImageUrl,
    isUploading: isUploadingExternal, // Rename to avoid conflict if needed, though prop name is distinct
    onFileUpload,
    onOpenMediaLibrary
}: PhotoTabContentProps) {

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            onFileUpload(file);
        }
    };

    return (
        <div className={styles.tabContent}>
            <div className={styles.uploadSection}>
                {!uploadedImageUrl ? (
                    <div className={styles.uploadPlaceholder}>
                        <div className={styles.uploadIconWrapper}>
                            {isUploadingExternal ? (
                                <CircleNotch size={48} className="fa-spin" />
                            ) : (
                                <Upload size={48} />
                            )}
                        </div>
                        <h3>Upload Cover Art</h3>
                        <p>Upload an image or select from library</p>
                        <div className={styles.uploadActions}>
                            <label className={styles.uploadButton}>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleFileChange}
                                    hidden
                                    disabled={isUploadingExternal}
                                />
                                <Upload size={20} />
                                <span>Upload File</span>
                            </label>
                            <span className={styles.separator}>or</span>
                            <button
                                className={styles.secondaryButton}
                                onClick={onOpenMediaLibrary}
                                disabled={isUploadingExternal}
                            >
                                <FolderOpen size={20} />
                                <span>Media Library</span>
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
                            <label className={styles.textButton}>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleFileChange}
                                    hidden
                                />
                                Change Image
                            </label>
                            <button
                                className={styles.textButton}
                                onClick={onOpenMediaLibrary}
                            >
                                Select from Library
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
