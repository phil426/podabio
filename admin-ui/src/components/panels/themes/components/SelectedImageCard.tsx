import { useState } from 'react';
import { CircleNotch, ArrowClockwise } from '@phosphor-icons/react';
import { EmptyState } from './EmptyState';
import styles from '../podcast-theme-generator.module.css';

export interface SelectedImageCardProps {
  coverImageUrl: string | null;
  isUploading: boolean;
  onImageLoadError: (imageUrl: string) => void;
}

export function SelectedImageCard({
  coverImageUrl,
  isUploading,
  onImageLoadError,
  onRetryLoad
}: SelectedImageCardProps): JSX.Element | null {
  if (isUploading) {
    return (
      <section className={styles.card}>
        <h3 className={styles.cardTitle}>Selected Image</h3>
        <div className={styles.cardContent}>
          <div className={styles.loading}>
            <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
            <p>Uploading artwork to media library...</p>
          </div>
        </div>
      </section>
    );
  }

  if (!coverImageUrl) {
    return (
      <section className={styles.card}>
        <h3 className={styles.cardTitle}>Selected Image</h3>
        <div className={styles.cardContent}>
          <EmptyState type="no-image" />
        </div>
      </section>
    );
  }

  const [imageError, setImageError] = useState(false);

  return (
    <section className={styles.card}>
      <h3 className={styles.cardTitle}>Selected Image</h3>
      <div className={styles.cardContent}>
        {imageError && onRetryLoad ? (
          <div className={styles.imageError}>
            <p>Failed to load image</p>
            <button
              type="button"
              className={styles.retryButton}
              onClick={() => {
                setImageError(false);
                onRetryLoad();
              }}
              aria-label="Retry loading image"
            >
              <ArrowClockwise aria-hidden="true" size={16} weight="regular" />
              Retry
            </button>
          </div>
        ) : (
          <div className={styles.coverImage}>
            <img
              src={coverImageUrl}
              alt="Selected image"
              onError={() => {
                setImageError(true);
                onImageLoadError(coverImageUrl);
              }}
            />
          </div>
        )}
      </div>
    </section>
  );
}

