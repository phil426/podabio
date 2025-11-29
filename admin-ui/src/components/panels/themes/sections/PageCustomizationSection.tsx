/**
 * Page Customization Section
 * Settings for page background, title, and bio
 */

import { useState, useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Upload, X, Images } from '@phosphor-icons/react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { PodaColorPicker } from '../../../controls/PodaColorPicker';
import { FontSelect } from '../../ultimate-theme-modifier/FontSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SpecialTextSelect } from '../../ultimate-theme-modifier/SpecialTextSelect';
import { fieldRegistry } from '../utils/fieldRegistry';
import { usePageSnapshot, removeProfileImage, updatePageSettings } from '../../../../api/page';
import { uploadProfileImage } from '../../../../api/uploads';
import { queryKeys, normalizeImageUrl } from '../../../../api/utils';
import { MediaLibraryDrawer } from '../../../overlays/MediaLibraryDrawer';
import type { MediaItem } from '../../../../api/media';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';

interface PageCustomizationSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function PageCustomizationSection({
  uiState,
  onFieldChange,
  activeColor
}: PageCustomizationSectionProps): JSX.Element {
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
  
  // Get fields for this section
  const fields = fieldRegistry.getFieldsForSection('page-customization');

  // Extract values with defaults
  const pageBackground = (uiState['page-background'] as string) ?? '#ffffff';
  const pageVerticalSpacing = (uiState['page-vertical-spacing'] as number) ?? 24;
  const [pageBackgroundType, setPageBackgroundType] = useState<'solid' | 'gradient'>('solid');
  
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
  const pageTitleEffectValue = (uiState['page-title-effect'] as string) ?? 'none';
  const pageTitleEffect = effectValueToDisplay[pageTitleEffectValue] || 'None';
  
  // Shadow properties
  const shadowColor = (uiState['page-title-shadow-color'] as string) ?? '#000000';
  const shadowIntensity = (uiState['page-title-shadow-intensity'] as number) ?? 0.5;
  const shadowDepth = (uiState['page-title-shadow-depth'] as number) ?? 4;
  const shadowBlur = (uiState['page-title-shadow-blur'] as number) ?? 8;
  
  // Glow properties
  const glowColor = (uiState['page-title-glow-color'] as string) ?? '#2563eb';
  const glowWidth = (uiState['page-title-glow-width'] as number) ?? 10;
  
  // Border/Stroke properties
  const borderColor = (uiState['page-title-border-color'] as string) ?? '#000000';
  const borderWidth = (uiState['page-title-border-width'] as number) ?? 0;
  
  const pageTitleColor = (uiState['page-title-color'] as string) ?? '#0f172a';
  const pageTitleFont = (uiState['page-title-font'] as string) ?? 'Inter';
  const pageTitleSize = (uiState['page-title-size'] as number) ?? 24;
  const pageTitleSpacing = (uiState['page-title-spacing'] as number) ?? 1.2;
  const pageTitleWeight = (uiState['page-title-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };
  
  const pageBioColor = (uiState['page-bio-color'] as string) ?? '#4b5563';
  const pageBioFont = (uiState['page-bio-font'] as string) ?? 'Inter';
  const pageBioSize = (uiState['page-bio-size'] as number) ?? 16;
  const pageBioSpacing = (uiState['page-bio-spacing'] as number) ?? 100;
  const pageBioWeight = (uiState['page-bio-weight'] as { bold?: boolean; italic?: boolean }) ?? { bold: false, italic: false };

  // Determine background type from value
  useEffect(() => {
    if (!pageBackground || typeof pageBackground !== 'string') {
      setPageBackgroundType('solid');
      return;
    }
    if (pageBackground.includes('gradient') || pageBackground.includes('linear-gradient') || pageBackground.includes('radial-gradient')) {
      setPageBackgroundType('gradient');
    } else {
      setPageBackgroundType('solid');
    }
  }, [pageBackground]);


  return (
    <div className={styles.section}>
      {/* Page Background */}
      <div className={styles.fieldGroup}>
        <label className={styles.label}>Page Background</label>
        <BackgroundColorSwatch
          value={pageBackground}
          backgroundType={pageBackgroundType}
          onChange={(value) => onFieldChange('page-background', value)}
          onTypeChange={(type) => {
            setPageBackgroundType(type);
            if (type === 'solid') {
              onFieldChange('page-background', '#ffffff');
            } else if (type === 'gradient') {
              onFieldChange('page-background', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
            }
          }}
          label="Page background"
        />
      </div>

      {/* Vertical Spacing */}
      <div className={styles.fieldGroup}>
        <label className={styles.label}>Vertical Spacing</label>
        <SliderInput
          value={pageVerticalSpacing}
          min={0}
          max={100}
          step={4}
          unit="px"
          onChange={(value) => onFieldChange('page-vertical-spacing', value)}
        />
      </div>

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
            <MediaLibraryDrawer
              open={mediaLibraryOpen}
              onClose={() => setMediaLibraryOpen(false)}
              onSelect={handleSelectFromLibrary}
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
            step={0.5}
            unit="px"
            onChange={(value) => onFieldChange('profile-image-border-width', value)}
          />
        </div>
      </div>

      {/* Page Title */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Page Title</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Special Effect</label>
          <SpecialTextSelect
            value={pageTitleEffect}
            options={['None', 'Glow', 'Drop Shadow']}
            onChange={(value) => {
              onFieldChange('page-title-effect', effectDisplayToValue[value] || value);
            }}
          />
        </div>

        {/* Shadow Controls - Only show when shadow is selected */}
        {pageTitleEffectValue === 'shadow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Color</label>
              <PodaColorPicker
                value={shadowColor}
                onChange={(value) => onFieldChange('page-title-shadow-color', value)}
                solidOnly
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Intensity</label>
              <SliderInput
                value={shadowIntensity}
                min={0}
                max={1}
                step={0.1}
                onChange={(value) => onFieldChange('page-title-shadow-intensity', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Depth</label>
              <SliderInput
                value={shadowDepth}
                min={0}
                max={20}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-shadow-depth', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Blur</label>
              <SliderInput
                value={shadowBlur}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-shadow-blur', value)}
              />
            </div>
          </>
        )}

        {/* Glow Controls - Only show when glow is selected */}
        {pageTitleEffectValue === 'glow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Color</label>
              <PodaColorPicker
                value={glowColor}
                onChange={(value) => onFieldChange('page-title-glow-color', value)}
                solidOnly
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Width</label>
              <SliderInput
                value={glowWidth}
                min={0}
                max={50}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('page-title-glow-width', value)}
              />
            </div>
          </>
        )}

        {/* Page Title Color - Always visible */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Color</label>
          <BackgroundColorSwatch
            value={pageTitleColor}
            onChange={(value) => onFieldChange('page-title-color', value)}
            label="Page title color"
            solidOnly={true}
          />
        </div>

        {/* Font Border/Stroke Controls - Always visible */}
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font Border Color</label>
          <BackgroundColorSwatch
            value={borderColor}
            onChange={(value) => onFieldChange('page-title-border-color', value)}
            label="Font border color"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font Border Width</label>
          <SliderInput
            value={borderWidth}
            min={0}
            max={10}
            step={0.5}
            unit="px"
            onChange={(value) => onFieldChange('page-title-border-width', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font</label>
          <FontSelect
            value={pageTitleFont}
            onChange={(value) => onFieldChange('page-title-font', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Size</label>
          <SliderInput
            value={pageTitleSize}
            min={14}
            max={48}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('page-title-size', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Spacing</label>
          <SliderInput
            value={pageTitleSpacing}
            min={1}
            max={2}
            step={0.1}
            onChange={(value) => onFieldChange('page-title-spacing', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Style</label>
          <div className={styles.toggleGroup}>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageTitleWeight.bold ? styles.active : ''}`}
              onClick={() => onFieldChange('page-title-weight', { ...pageTitleWeight, bold: !pageTitleWeight.bold })}
            >
              Bold
            </button>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageTitleWeight.italic ? styles.active : ''}`}
              onClick={() => onFieldChange('page-title-weight', { ...pageTitleWeight, italic: !pageTitleWeight.italic })}
            >
              Italic
            </button>
          </div>
        </div>
      </div>

      {/* Page Bio */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Page Bio</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Color</label>
          <BackgroundColorSwatch
            value={pageBioColor}
            onChange={(value) => onFieldChange('page-bio-color', value)}
            label="Page bio color"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Font</label>
          <FontSelect
            value={pageBioFont}
            onChange={(value) => onFieldChange('page-bio-font', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Size</label>
          <SliderInput
            value={pageBioSize}
            min={10}
            max={24}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('page-bio-size', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Spacing</label>
          <SliderInput
            value={pageBioSpacing}
            min={50}
            max={200}
            step={5}
            unit="%"
            onChange={(value) => onFieldChange('page-bio-spacing', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Style</label>
          <div className={styles.toggleGroup}>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageBioWeight.bold ? styles.active : ''}`}
              onClick={() => onFieldChange('page-bio-weight', { ...pageBioWeight, bold: !pageBioWeight.bold })}
            >
              Bold
            </button>
            <button
              type="button"
              className={`${styles.toggleButton} ${pageBioWeight.italic ? styles.active : ''}`}
              onClick={() => onFieldChange('page-bio-weight', { ...pageBioWeight, italic: !pageBioWeight.italic })}
            >
              Italic
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

