/**
 * Page Background Section
 * Settings for page background and vertical spacing only
 */

import { useState, useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Upload, X, Images, Square, Gradient, Image } from '@phosphor-icons/react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SegmentedControl } from '../../../common/SegmentedControl';
import { FocalPointPicker } from '../controls/FocalPointPicker';
import { MediaLibraryModal } from '../../../overlays/MediaLibraryModal';
import { queryKeys, normalizeImageUrl } from '../../../../api/utils';
import type { MediaItem } from '../../../../api/media';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';


interface PageBackgroundSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
  onPreviewVisibilityChange?: (visible: boolean) => void;
}

export function PageBackgroundSection({
  uiState,
  onFieldChange,
  activeColor,
  palette,
  onPreviewVisibilityChange
}: PageBackgroundSectionProps): JSX.Element {
  const queryClient = useQueryClient();
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);

  // Background Values
  const pageBackground = (uiState['page-background'] as string) ?? '#ffffff';
  const pageSpacing = (uiState['page-spacing'] as number) ?? 16;
  const pageBackgroundAnimate = (uiState['page-background-animate'] as boolean) ?? false;

  // Image Values
  const bgImageUrl = (uiState['page-background-image-url'] as string) ?? null;
  const bgImageOverlay = (uiState['page-background-image-overlay'] as string) ?? 'rgba(0,0,0,0.4)';
  const bgImageScale = (uiState['page-background-image-scale'] as number) ?? 1;
  const bgImageBlur = (uiState['page-background-image-blur'] as string) ?? '0px';
  const bgImageFocalX = (uiState['page-background-image-focal-x'] as string) ?? '50%';
  const bgImageFocalY = (uiState['page-background-image-focal-y'] as string) ?? '50%';

  // Determine current mode
  const [mode, setMode] = useState<'solid' | 'gradient' | 'image'>('solid');

  useEffect(() => {
    let newMode: 'solid' | 'gradient' | 'image';
    if (bgImageUrl && bgImageUrl !== 'none') {
      newMode = 'image';
    } else if (pageBackground && pageBackground.includes('gradient')) {
      newMode = 'gradient';
    } else {
      newMode = 'solid';
    }
    setMode(newMode);
    // Notify parent about preview visibility (hide if image mode)
    if (onPreviewVisibilityChange) {
      onPreviewVisibilityChange(newMode !== 'image');
    }
  }, [bgImageUrl, pageBackground, onPreviewVisibilityChange]);

  const handleModeChange = (newMode: 'solid' | 'gradient' | 'image') => {
    setMode(newMode);

    // Notify parent about preview visibility
    if (onPreviewVisibilityChange) {
      onPreviewVisibilityChange(newMode !== 'image');
    }

    if (newMode === 'solid') {
      onFieldChange('page-background', '#ffffff');
      onFieldChange('page-background-animate', false);
      onFieldChange('page-background-image-url', null); // Clear image
    } else if (newMode === 'gradient') {
      onFieldChange('page-background', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
      onFieldChange('page-background-image-url', null); // Clear image
    } else if (newMode === 'image') {
      // Don't overwrite background color/gradient, just set image
      // If no image is set, maybe set a placeholder or keep null until upload
    }
  };


  const handleSelectFromLibrary = (mediaItem: MediaItem) => {
    onFieldChange('page-background-image-url', mediaItem.file_url);
    setMediaLibraryOpen(false);
  };

  return (
    <div className={styles.section}>
      {/* Background Type Selector */}
      <div className={styles.fieldGroup}>
        <SegmentedControl
          options={[
            { value: 'solid', label: 'Solid', icon: <Square size={16} /> },
            { value: 'gradient', label: 'Gradient', icon: <Gradient size={16} /> },
            { value: 'image', label: 'Image', icon: <Image size={16} /> },
          ]}
          value={mode}
          onChange={(val) => handleModeChange(val as 'solid' | 'gradient' | 'image')}
        />
      </div>

      {mode === 'image' && (
        <div className={styles.subsection}>
          <h4 className={styles.subsectionTitle}>Background Image</h4>

          <div className={styles.fieldGroup}>
            <div className={styles.imageUploadContainer}>
              <div
                className={styles.imagePreview}
                data-has-image={!!bgImageUrl}
                style={{
                  aspectRatio: '9/20',
                  height: 'auto',
                  backgroundSize: 'cover',
                  backgroundImage: bgImageUrl ? `url(${normalizeImageUrl(bgImageUrl)})` : 'none',
                  backgroundPosition: bgImageUrl ? `${bgImageFocalX} ${bgImageFocalY}` : 'center'
                }}
              >
                {!bgImageUrl && (
                  <div className={styles.imagePlaceholder}>
                    <span>No image selected</span>
                  </div>
                )}
                <div className={styles.imageOverlay}>
                  <div className={styles.segmentedBar}>
                    <button
                      type="button"
                      className={styles.segmentedButton}
                      onClick={() => setMediaLibraryOpen(true)}
                      title="Choose from library"
                    >
                      <Images size={16} />
                    </button>
                    {bgImageUrl && (
                      <>
                        <div className={styles.segmentedDivider} />
                        <button
                          type="button"
                          className={`${styles.segmentedButton} ${styles.segmentedButtonDanger}`}
                          onClick={() => onFieldChange('page-background-image-url', null)}
                          disabled={false}
                          title="Remove image"
                        >
                          <X size={16} />
                        </button>
                      </>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className={styles.controlsContainer}>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Focal Point</label>
              {bgImageUrl ? (
                <FocalPointPicker
                  imageUrl={normalizeImageUrl(bgImageUrl)}
                  valueX={bgImageFocalX}
                  valueY={bgImageFocalY}
                  onChange={(x: string, y: string) => {
                    onFieldChange('page-background-image-focal-x', x);
                    onFieldChange('page-background-image-focal-y', y);
                  }}
                />
              ) : (
                <div className={styles.description}>Select an image to set focal point</div>
              )}
            </div>

            <div className={styles.fieldGroup}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                <BackgroundColorSwatch
                  value={(() => {
                    // Extract RGB part for the swatch
                    if (bgImageOverlay.startsWith('rgba')) {
                      const parts = bgImageOverlay.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                      if (parts) return `rgb(${parts[1]}, ${parts[2]}, ${parts[3]})`;
                    }
                    return bgImageOverlay;
                  })()}
                  onChange={(color) => {
                    // When color changes, preserve current opacity
                    const currentOpacity = parseFloat(bgImageOverlay.split(',')[3]) || 0;

                    // Convert incoming color (likely hex or rgb) to rgba
                    if (color.startsWith('#')) {
                      // Hex to RGB
                      const r = parseInt(color.slice(1, 3), 16);
                      const g = parseInt(color.slice(3, 5), 16);
                      const b = parseInt(color.slice(5, 7), 16);
                      onFieldChange('page-background-image-overlay', `rgba(${r},${g},${b},${currentOpacity})`);
                    } else if (color.startsWith('rgb')) {
                      const parts = color.match(/\d+/g);
                      if (parts && parts.length >= 3) {
                        onFieldChange('page-background-image-overlay', `rgba(${parts[0]},${parts[1]},${parts[2]},${currentOpacity})`);
                      }
                    }
                  }}
                  label="Overlay Color"
                  palette={palette}
                >
                  <SliderInput
                    value={parseFloat(bgImageOverlay.split(',')[3]) || 0}
                    min={0}
                    max={1}
                    step={0.05}
                    label="Opacity"
                    onChange={(val) => {
                      // Update opacity while keeping current RGB
                      let r = 0, g = 0, b = 0;
                      if (bgImageOverlay.startsWith('rgba') || bgImageOverlay.startsWith('rgb')) {
                        const parts = bgImageOverlay.match(/\d+/g);
                        if (parts && parts.length >= 3) {
                          r = parseInt(parts[0]);
                          g = parseInt(parts[1]);
                          b = parseInt(parts[2]);
                        }
                      }
                      onFieldChange('page-background-image-overlay', `rgba(${r},${g},${b},${val})`)
                    }}
                  />
                </BackgroundColorSwatch>
              </div>
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Blur</label>
              <SliderInput
                value={parseInt(bgImageBlur) || 0}
                min={0}
                max={20}
                step={1}
                unit="px"
                onChange={(val) => onFieldChange('page-background-image-blur', `${val}px`)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Scale</label>
              <SliderInput
                value={bgImageScale}
                min={1}
                max={2}
                step={0.1}
                onChange={(val: number) => onFieldChange('page-background-image-scale', val)}
              />
            </div>
          </div>

        </div>
      )}

      {(mode === 'solid' || mode === 'gradient') && (
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Page Background</label>
          <BackgroundColorSwatch
            value={pageBackground}
            backgroundType={mode === 'gradient' ? 'gradient' : 'solid'}
            onChange={(value) => onFieldChange('page-background', value)}
            // We lock the type in the swatch to match our top-level mode
            solidOnly={mode === 'solid'}
            label="Page background"
            palette={palette}
          />
        </div>
      )}

      {/* Gradient Animation Toggle */}
      {mode === 'gradient' && (
        <div className={styles.fieldGroup}>
          <label className={styles.toggleRow}>
            <span className={styles.label}>Animate Gradient</span>
            <label className={styles.toggleSwitch}>
              <input
                type="checkbox"
                checked={pageBackgroundAnimate}
                onChange={(e) => onFieldChange('page-background-animate', e.target.checked)}
              />
              <span className={styles.toggleSlider} />
            </label>
          </label>
        </div>
      )}

      {/* Spacing */}
      <div className={styles.controlsContainer}>
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Spacing</label>
          <p className={styles.description}>
            Controls spacing between all page elements.
          </p>
          <SliderInput
            value={pageSpacing}
            min={8}
            max={48}
            step={2}
            unit="px"
            onChange={(value) => onFieldChange('page-spacing', value)}
          />
        </div>
      </div>

      <MediaLibraryModal
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleSelectFromLibrary}
      />
    </div>
  );
}

