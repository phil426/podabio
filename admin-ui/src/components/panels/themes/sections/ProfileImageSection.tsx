/**
 * Profile Image Section
 * Settings for profile image only
 */

import { useState, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Upload, X, Images } from '@phosphor-icons/react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SpecialTextSelect } from '../../ultimate-theme-modifier/SpecialTextSelect';
import { usePageSnapshot, removeProfileImage, updatePageSettings } from '../../../../api/page';
import { uploadProfileImage } from '../../../../api/uploads';
import { queryKeys, normalizeImageUrl } from '../../../../api/utils';
import { MediaLibraryDrawer } from '../../../overlays/MediaLibraryDrawer';
import type { MediaItem } from '../../../../api/media';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface ProfileImageSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function ProfileImageSection({
  uiState,
  onFieldChange,
  activeColor
}: ProfileImageSectionProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  
  const profileImage = page?.profile_image ?? null;

  const handleSelectFromLibrary = async (mediaItem: MediaItem) => {
    try {
      setIsUploading(true);
      await updatePageSettings({ profile_image: mediaItem.file_url });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setMediaLibraryOpen(false);
    } catch (error) {
      console.error('Failed to update profile image:', error);
      alert(error instanceof Error ? error.message : 'Unable to update profile image.');
    } finally {
      setIsUploading(false);
    }
  };

  // Map effect values to display names
  const effectValueToDisplay: Record<string, string> = {
    'none': 'None',
    'glow': 'Glow',
    'shadow': 'Drop Shadow'
  };
  const effectDisplayToValue: Record<string, string> = {
    'None': 'none',
    'Glow': 'glow',
    'Drop Shadow': 'shadow'
  };

  return (
    <div className={styles.section}>
      {/* Profile Image */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Profile Image</h4>
        
        {/* Profile Image Upload */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Image</label>
          <div className={styles.imageUploadContainer}>
            <div 
              className={styles.imagePreview}
              data-has-image={profileImage ? 'true' : 'false'}
            >
              {profileImage ? (
                <img 
                  src={normalizeImageUrl(profileImage)} 
                  alt="Profile" 
                  className={styles.imagePreviewImg}
                />
              ) : (
                <div className={styles.imagePlaceholder}>
                  <span>No image</span>
                </div>
              )}
              <div className={styles.imageOverlay}>
                <div className={styles.segmentedBar}>
                  <button
                    type="button"
                    className={styles.segmentedButton}
                    onClick={() => fileInputRef.current?.click()}
                    disabled={isUploading}
                    title={isUploading ? 'Uploading…' : profileImage ? 'Replace image' : 'Upload image'}
                  >
                    <Upload size={16} weight="regular" aria-hidden="true" />
                  </button>
                  <div className={styles.segmentedDivider} />
                  <button
                    type="button"
                    className={styles.segmentedButton}
                    onClick={() => setMediaLibraryOpen(true)}
                    disabled={isUploading}
                    title="Choose from library"
                  >
                    <Images size={16} weight="regular" aria-hidden="true" />
                  </button>
                  {profileImage && (
                    <>
                      <div className={styles.segmentedDivider} />
                      <button
                        type="button"
                        className={`${styles.segmentedButton} ${styles.segmentedButtonDanger}`}
                        onClick={async () => {
                          try {
                            setIsUploading(true);
                            await removeProfileImage();
                            await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
                          } catch (error) {
                            console.error('Failed to remove image:', error);
                          } finally {
                            setIsUploading(false);
                          }
                        }}
                        disabled={isUploading}
                        title="Remove image"
                      >
                        <X size={16} weight="regular" aria-hidden="true" />
                      </button>
                    </>
                  )}
                </div>
              </div>
            </div>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              style={{ display: 'none' }}
              onChange={async (e) => {
                const file = e.target.files?.[0];
                if (!file) return;
                
                // Client-side validation: Check file size (5MB limit)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                  alert(`File size exceeds the maximum allowed size of 5MB. Please choose a smaller image.`);
                  if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                  }
                  return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                  alert('Invalid file type. Please use JPEG, PNG, GIF, or WebP format.');
                  if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                  }
                  return;
                }
                
                try {
                  setIsUploading(true);
                  await uploadProfileImage(file);
                  await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
                } catch (error) {
                  const errorMessage = error instanceof Error ? error.message : 'Upload failed. Please try again.';
                  alert(errorMessage);
                  console.error('Upload failed:', error);
                } finally {
                  setIsUploading(false);
                  if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                  }
                }
              }}
            />
          </div>
        </div>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Size</label>
          <SliderInput
            value={(uiState['profile-image-size'] as number) ?? 120}
            min={80}
            max={180}
            step={4}
            unit="px"
            onChange={(value) => onFieldChange('profile-image-size', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Radius</label>
          <SliderInput
            value={(uiState['profile-image-radius'] as number) ?? 16}
            min={0}
            max={50}
            step={1}
            unit="%"
            onChange={(value) => onFieldChange('profile-image-radius', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Special Effect</label>
          <SpecialTextSelect
            value={effectValueToDisplay[(uiState['profile-image-effect'] as string) ?? 'none'] || 'None'}
            options={['None', 'Glow', 'Drop Shadow']}
            onChange={(value) => {
              onFieldChange('profile-image-effect', effectDisplayToValue[value] || value);
            }}
          />
        </div>

        {/* Shadow Controls - Only show when shadow is selected */}
        {(uiState['profile-image-effect'] as string) === 'shadow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Color</label>
              <BackgroundColorSwatch
                value={(uiState['profile-image-shadow-color'] as string) ?? '#000000'}
                onChange={(value) => onFieldChange('profile-image-shadow-color', value)}
                label="Shadow color"
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Intensity</label>
              <SliderInput
                value={(uiState['profile-image-shadow-intensity'] as number) ?? 0.5}
                min={0}
                max={1}
                step={0.1}
                onChange={(value) => onFieldChange('profile-image-shadow-intensity', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Depth</label>
              <SliderInput
                value={(uiState['profile-image-shadow-depth'] as number) ?? 4}
                min={0}
                max={20}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('profile-image-shadow-depth', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Blur</label>
              <SliderInput
                value={(uiState['profile-image-shadow-blur'] as number) ?? 8}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('profile-image-shadow-blur', value)}
              />
            </div>
          </>
        )}

        {/* Glow Controls - Only show when glow is selected */}
        {(uiState['profile-image-effect'] as string) === 'glow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Color</label>
              <BackgroundColorSwatch
                value={(uiState['profile-image-glow-color'] as string) ?? '#2563eb'}
                onChange={(value) => onFieldChange('profile-image-glow-color', value)}
                label="Glow color"
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Width</label>
              <SliderInput
                value={(uiState['profile-image-glow-width'] as number) ?? 10}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('profile-image-glow-width', value)}
              />
            </div>
          </>
        )}

        {/* Border Controls */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Color</label>
          <BackgroundColorSwatch
            value={(uiState['profile-image-border-color'] as string) ?? '#000000'}
            onChange={(value) => onFieldChange('profile-image-border-color', value)}
            label="Border color"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Width</label>
          <SliderInput
            value={(uiState['profile-image-border-width'] as number) ?? 0}
            min={0}
            max={10}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('profile-image-border-width', value)}
          />
        </div>
      </div>
      <MediaLibraryDrawer
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleSelectFromLibrary}
      />
    </div>
  );
}

