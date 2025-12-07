import { Images, Palette, MagnifyingGlass } from '@phosphor-icons/react';
import styles from '../podcast-theme-generator.module.css';

export type EmptyStateType = 'no-image' | 'no-colors' | 'no-search-results';

export interface EmptyStateProps {
  type: EmptyStateType;
  message?: string;
}

export function EmptyState({ type, message }: EmptyStateProps): JSX.Element {
  const getContent = () => {
    switch (type) {
      case 'no-image':
        return {
          icon: <Images size={48} weight="regular" />,
          title: 'No image selected',
          description: message || 'Select an image from RSS feed or upload a photo to extract colors',
          iconColor: 'var(--admin-text-tertiary, #94a3b8)'
        };
      case 'no-colors':
        return {
          icon: <Palette size={48} weight="regular" />,
          title: 'No colors extracted',
          description: message || 'Extract colors from the selected image to generate a theme',
          iconColor: 'var(--admin-text-tertiary, #94a3b8)'
        };
      case 'no-search-results':
        return {
          icon: <MagnifyingGlass size={48} weight="regular" />,
          title: 'No podcasts found',
          description: message || 'Try a different search query',
          iconColor: 'var(--admin-text-tertiary, #94a3b8)'
        };
    }
  };

  const content = getContent();

  return (
    <div className={styles.emptyState}>
      <div className={styles.emptyStateIcon} style={{ color: content.iconColor }}>
        {content.icon}
      </div>
      <h4 className={styles.emptyStateTitle}>{content.title}</h4>
      <p className={styles.emptyStateDescription}>{content.description}</p>
    </div>
  );
}


