import { MediaLibrary } from '../media/MediaLibrary';
import styles from './media-library-tab.module.css';

export function MediaLibraryTab(): JSX.Element {
  return (
    <div className={styles.container}>
      {/* Pass custom class to override specific styles if needed, 
            but generally we want the standard look. 
            The container class here might just be for layout spacing.
        */}
      <MediaLibrary mode="manage" className={styles.libraryOverride} />
    </div>
  );
}


