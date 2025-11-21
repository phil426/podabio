import * as Tabs from '@radix-ui/react-tabs';
import { Image, Sparkle, Square } from '@phosphor-icons/react';
import { PageBackgroundPicker } from '../../controls/PageBackgroundPicker';
import styles from '../theme-editor-panel.module.css';

interface PageBackgroundSectionProps {
  backgroundType: 'solid' | 'gradient' | 'image';
  onBackgroundTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  pageBackground: string;
  onBackgroundChange: (value: string) => void;
  pageBackgroundImage: string | null;
  onBackgroundImageUrlChange: (url: string) => void;
  onBackgroundImageUpload: (file: File) => Promise<void>;
  onBackgroundImageRemove: () => void;
}

export function PageBackgroundSection({
  backgroundType,
  onBackgroundTypeChange,
  pageBackground,
  onBackgroundChange,
  pageBackgroundImage,
  onBackgroundImageUrlChange,
  onBackgroundImageUpload,
  onBackgroundImageRemove
}: PageBackgroundSectionProps): JSX.Element {

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
          <Sparkle aria-hidden="true" size={16} weight="regular" />
          <span>Gradient</span>
        </Tabs.Trigger>
        <Tabs.Trigger value="image" className={styles.backgroundTabTrigger}>
          <Image aria-hidden="true" size={16} weight="regular" />
          <span>Image</span>
        </Tabs.Trigger>
      </Tabs.List>

      <Tabs.Content value="gradient" className={styles.backgroundTabContent}>
        <PageBackgroundPicker
          value={pageBackground}
          onChange={onBackgroundChange}
          mode="gradient"
        />
      </Tabs.Content>

      <Tabs.Content value="solid" className={styles.backgroundTabContent}>
        <PageBackgroundPicker
          value={pageBackground}
          onChange={onBackgroundChange}
          mode="solid"
        />
      </Tabs.Content>

      <Tabs.Content value="image" className={styles.backgroundTabContent}>
        <div className={styles.controlGroup}>
          <div className={styles.control}>
            <label>
              <span>Background Image URL</span>
              <input
                type="url"
                value={pageBackgroundImage || ''}
                onChange={(e) => onBackgroundImageUrlChange(e.target.value)}
                placeholder="https://example.com/image.jpg"
                className={styles.urlInput}
              />
            </label>
            <div style={{ marginTop: '0.5rem', display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <span style={{ fontSize: '0.75rem', color: 'var(--pod-semantic-text-secondary)' }}>or</span>
            </div>
            <label style={{ marginTop: '0.5rem', display: 'block' }}>
              <span>Upload Image</span>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) {
                    onBackgroundImageUpload(file);
                  }
                }}
                style={{ marginTop: '0.25rem', width: '100%' }}
              />
            </label>
            {pageBackgroundImage && (
              <div style={{ marginTop: '1rem' }}>
                <div style={{ position: 'relative', display: 'inline-block' }}>
                  <img
                    src={pageBackgroundImage}
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

