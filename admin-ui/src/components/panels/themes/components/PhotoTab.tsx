import { Images, Upload } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

export interface PhotoTabProps {
  uploadedImageUrl: string | null;
  onOpenMediaLibrary: () => void;
  onUploadImage: (file: File) => void;
}

export function PhotoTab({
  uploadedImageUrl,
  onOpenMediaLibrary,
  onUploadImage
}: PhotoTabProps): JSX.Element {
  return (
    <section className={styles.card}>
      <h3 className={styles.cardTitle}>Select Image</h3>
      <div className={styles.cardContent}>
        <div className={styles.photoOptions}>
          <button
            type="button"
            className={styles.photoOptionButton}
            onClick={onOpenMediaLibrary}
          >
            <Images aria-hidden="true" size={24} weight="regular" />
            Choose from Library
          </button>
          <label className={styles.photoOptionButton}>
            <Upload aria-hidden="true" size={24} weight="regular" />
            Upload Image
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp,image/gif"
              style={{ display: 'none' }}
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) {
                  onUploadImage(file);
                }
              }}
            />
          </label>
        </div>

        {uploadedImageUrl && (
          <div className={styles.selectedImage}>
            <div className={styles.coverImage}>
              <img src={uploadedImageUrl} alt="Selected image" />
            </div>
          </div>
        )}
      </div>
    </section>
  );
}


