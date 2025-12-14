import { useState } from 'react';
import * as Tabs from '@radix-ui/react-tabs';
import { Image, Swatches, Square, Images } from '@phosphor-icons/react';
import { PageBackgroundPicker } from '../../controls/PageBackgroundPicker';
import { MediaLibraryModal } from '../../overlays/MediaLibraryModal';
import type { MediaItem } from '../../../api/media';
import styles from '../theme-editor-panel.module.css';

interface BlockBackgroundSectionProps {
  backgroundType: 'solid' | 'gradient' | 'image';
  onBackgroundTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  blockBackground: string;
  onBackgroundChange: (value: string) => void;
  blockBackgroundImage: string | null;
  onBackgroundImageUrlChange: (url: string) => void;
  onBackgroundImageUpload?: (file: File) => Promise<void>;
  onBackgroundImageRemove: () => void;
}

export function BlockBackgroundSection({
  backgroundType,
  onBackgroundTypeChange,
  blockBackground,
  onBackgroundChange,
  blockBackgroundImage,
  onBackgroundImageUrlChange,
  onBackgroundImageUpload,
  onBackgroundImageRemove
}: BlockBackgroundSectionProps): JSX.Element {
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  return (
    <Tabs.Root
      className={styles.backgroundTabs}
      value={backgroundType}
      onValueChange={(value) => onBackgroundTypeChange(value as 'solid' | 'gradient' | 'image')}
    >
      <Tabs.List className={styles.backgroundTabList} aria-label="Background type">
        <Tabs.Trigger value="solid" className={styles.backgroundTabTrigger}>
          <Square aria-hidden="true" size={16} weight="regular" />
          <span>Solid</span>
        </Tabs.Trigger>
        <Tabs.Trigger value="gradient" className={styles.backgroundTabTrigger}>
          <Swatches aria-hidden="true" size={16} weight="regular" />
          <span>Gradient</span>
        </Tabs.Trigger>
        <Tabs.Trigger value="image" className={styles.backgroundTabTrigger}>
          <Image aria-hidden="true" size={16} weight="regular" />
          <span>Image</span>
        </Tabs.Trigger>
      </Tabs.List>

      <Tabs.Content value="solid" className={styles.backgroundTabContent}>
        <PageBackgroundPicker
          value={blockBackground}
          onChange={onBackgroundChange}
          mode="solid"
        />
      </Tabs.Content>

      <Tabs.Content value="gradient" className={styles.backgroundTabContent}>
        <PageBackgroundPicker
          value={blockBackground}
          onChange={onBackgroundChange}
          mode="gradient"
        />
      </Tabs.Content>

      <Tabs.Content value="image" className={styles.backgroundTabContent}>
        <div className={styles.controlGroup}>
          <div className={styles.control}>
            <label>
              <span>Background Image URL</span>
              <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                <input
                  type="url"
                  value={blockBackgroundImage || ''}
                  onChange={(e) => onBackgroundImageUrlChange(e.target.value)}
                  placeholder="https://example.com/image.jpg"
                  className={styles.urlInput}
                  style={{ flex: 1 }}
                />
                <button
                  type="button"
                  onClick={() => setMediaLibraryOpen(true)}
                  style={{
                    padding: '0.5rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(255, 255, 255, 0.1)',
                    background: 'rgba(255, 255, 255, 0.05)',
                    color: 'var(--pod-semantic-text-primary)',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                  title="Choose from Library"
                >
                  <Images size={16} weight="regular" />
                </button>
              </div>
            </label>
            <MediaLibraryModal
              open={mediaLibraryOpen}
              onClose={() => setMediaLibraryOpen(false)}
              onSelect={(item: MediaItem) => {
                onBackgroundImageUrlChange(item.file_url);
                setMediaLibraryOpen(false);
              }}
            />
            {blockBackgroundImage && (
              <div style={{ marginTop: '1rem' }}>
                <div style={{ position: 'relative', display: 'inline-block' }}>
                  <img
                    src={blockBackgroundImage}
                    alt="Background preview"
                    style={{
                      maxWidth: '100%',
                      maxHeight: '200px',
                      borderRadius: '8px',
                      border: '1px solid var(--pod-semantic-divider-subtle)'
                    }}
                  />
                  <button
                    type="button"
                    onClick={onBackgroundImageRemove}
                    style={{
                      position: 'absolute',
                      top: '0.25rem',
                      right: '0.25rem',
                      padding: '0.25rem 0.5rem',
                      background: 'rgba(0, 0, 0, 0.7)',
                      color: 'white',
                      border: 'none',
                      borderRadius: '4px',
                      cursor: 'pointer',
                      fontSize: '0.75rem'
                    }}
                  >
                    Remove
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </Tabs.Content>
    </Tabs.Root>
  );
}

