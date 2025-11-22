/**
 * Podcast Theme Generator Component
 * Core component for generating themes from podcast cover art
 * Standalone and reusable
 */

import { useState, useEffect, useCallback } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Shuffle, CircleNotch, Check, X } from '@phosphor-icons/react';
import { extractColorsFromImage, generateThemeFromPodcast, shuffleThemeColors } from '../../../api/podcastTheme';
import { useCreateThemeMutation } from '../../../api/themes';
import { usePageAppearanceMutation, updatePageThemeId } from '../../../api/page';
import { queryKeys } from '../../../api/utils';
import { ThemePreview } from './preview/ThemePreview';
import styles from './podcast-theme-generator.module.css';

interface PodcastThemeGeneratorProps {
  coverImageUrl: string | null;
  podcastName: string | null;
  podcastDescription: string | null;
  onClose: () => void;
  onThemeGenerated?: (themeId: number) => void;
}

export function PodcastThemeGenerator({
  coverImageUrl,
  podcastName,
  podcastDescription,
  onClose,
  onThemeGenerated
}: PodcastThemeGeneratorProps): JSX.Element {
  const queryClient = useQueryClient();
  const createThemeMutation = useCreateThemeMutation();
  const updatePageMutation = usePageAppearanceMutation();

  const [colors, setColors] = useState<string[]>([]);
  const [isExtracting, setIsExtracting] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isShuffling, setIsShuffling] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [previewCSSVars, setPreviewCSSVars] = useState<Record<string, string>>({});

  // Extract colors on mount if cover image is available
  useEffect(() => {
    if (coverImageUrl && colors.length === 0) {
      handleExtractColors();
    }
  }, [coverImageUrl]);

  // Update preview when colors change
  useEffect(() => {
    if (colors.length >= 2) {
      updatePreview();
    }
  }, [colors]);

  const handleExtractColors = useCallback(async () => {
    if (!coverImageUrl) {
      setError('No cover image URL provided');
      return;
    }

    setIsExtracting(true);
    setError(null);

    try {
      const extractedColors = await extractColorsFromImage(coverImageUrl);
      setColors(extractedColors);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to extract colors');
      console.error('Color extraction error:', err);
    } finally {
      setIsExtracting(false);
    }
  }, [coverImageUrl]);

  const handleShuffle = useCallback(async () => {
    if (colors.length < 5) {
      setError('Please extract colors first');
      return;
    }

    setIsShuffling(true);
    setError(null);

    try {
      const shuffled = await shuffleThemeColors(colors);
      setColors(shuffled);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to shuffle colors');
      console.error('Color shuffle error:', err);
    } finally {
      setIsShuffling(false);
    }
  }, [colors]);

  const updatePreview = useCallback(async () => {
    if (colors.length < 2) return;

    try {
      const themeData = await generateThemeFromPodcast({
        coverImageUrl: coverImageUrl || '',
        podcastName,
        podcastDescription,
        colors
      });

      // Convert theme data to CSS variables for preview
      const cssVars: Record<string, string> = {
        '--page-background': themeData.page_background,
        '--widget-background': themeData.widget_background,
        '--widget-border-color': themeData.widget_border_color,
        '--page-title-color': (themeData.typography_tokens?.color?.heading as string) || '#000000',
        '--page-description-color': (themeData.typography_tokens?.color?.body as string) || '#666666',
        '--widget-heading-color': (themeData.typography_tokens?.color?.widget_heading as string) || '#000000',
        '--widget-body-color': (themeData.typography_tokens?.color?.widget_body as string) || '#666666',
        '--icon-color': (themeData.color_tokens?.semantic?.accent?.primary as string) || '#2563eb',
        '--profile-image-radius': themeData.profile_image_radius ? `${themeData.profile_image_radius}%` : '15%',
      };

      setPreviewCSSVars(cssVars);
    } catch (err) {
      console.error('Preview update error:', err);
    }
  }, [colors, coverImageUrl, podcastName, podcastDescription]);

  const handleGenerateTheme = useCallback(async () => {
    if (colors.length < 2) {
      setError('At least 2 colors are required');
      return;
    }

    setIsGenerating(true);
    setError(null);

    try {
      // Generate theme data
      const themeData = await generateThemeFromPodcast({
        coverImageUrl: coverImageUrl || '',
        podcastName,
        podcastDescription,
        colors
      });

      // Create theme with all styling features
      const response = await createThemeMutation.mutateAsync({
        name: themeData.name,
        color_tokens: themeData.color_tokens,
        typography_tokens: themeData.typography_tokens,
        page_background: themeData.page_background,
        widget_background: themeData.widget_background,
        widget_border_color: themeData.widget_border_color,
        page_primary_font: themeData.page_primary_font,
        page_secondary_font: themeData.page_secondary_font,
        widget_primary_font: themeData.widget_primary_font,
        widget_secondary_font: themeData.widget_secondary_font,
        widget_styles: themeData.widget_styles,
      });

      if (!response.theme_id) {
        throw new Error('Theme creation failed - no theme ID returned');
      }

      const themeId = typeof response.theme_id === 'string' 
        ? parseInt(response.theme_id, 10) 
        : response.theme_id;

      // Apply theme to page
      await updatePageThemeId(themeId);

      // Update page with podcast name and description (truncated)
      const pageUpdates: Record<string, string | number> = {};
      
      // Set cover image as profile image
      if (coverImageUrl) {
        pageUpdates.profile_image = coverImageUrl;
      }
      
      if (themeData.podcast_name) {
        pageUpdates.podcast_name = themeData.podcast_name;
      }
      if (themeData.podcast_description) {
        pageUpdates.podcast_description = themeData.podcast_description;
      }

      // Page title effects
      if (themeData.page_name_effect) {
        pageUpdates.page_name_effect = themeData.page_name_effect;
      }
      if (themeData.page_name_shadow_color) {
        pageUpdates.page_name_shadow_color = themeData.page_name_shadow_color;
      }
      if (themeData.page_name_shadow_intensity !== undefined) {
        pageUpdates.page_name_shadow_intensity = themeData.page_name_shadow_intensity;
      }
      if (themeData.page_name_shadow_depth !== undefined) {
        pageUpdates.page_name_shadow_depth = themeData.page_name_shadow_depth;
      }
      if (themeData.page_name_shadow_blur !== undefined) {
        pageUpdates.page_name_shadow_blur = themeData.page_name_shadow_blur;
      }
      if (themeData.page_name_border_color) {
        pageUpdates.page_name_border_color = themeData.page_name_border_color;
      }
      if (themeData.page_name_border_width !== undefined) {
        pageUpdates.page_name_border_width = themeData.page_name_border_width;
      }

      // Profile image styling
      if (themeData.profile_image_radius !== undefined) {
        pageUpdates.profile_image_radius = themeData.profile_image_radius;
      }
      if (themeData.profile_image_effect) {
        pageUpdates.profile_image_effect = themeData.profile_image_effect;
      }
      if (themeData.profile_image_shadow_color) {
        pageUpdates.profile_image_shadow_color = themeData.profile_image_shadow_color;
      }
      if (themeData.profile_image_shadow_intensity !== undefined) {
        pageUpdates.profile_image_shadow_intensity = themeData.profile_image_shadow_intensity;
      }
      if (themeData.profile_image_shadow_depth !== undefined) {
        pageUpdates.profile_image_shadow_depth = themeData.profile_image_shadow_depth;
      }
      if (themeData.profile_image_shadow_blur !== undefined) {
        pageUpdates.profile_image_shadow_blur = themeData.profile_image_shadow_blur;
      }

      if (Object.keys(pageUpdates).length > 0) {
        await updatePageMutation.mutateAsync(pageUpdates);
      }

      // Invalidate queries
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });

      // Call callback if provided
      if (onThemeGenerated) {
        onThemeGenerated(themeId);
      }

      // Close generator
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to generate theme');
      console.error('Theme generation error:', err);
    } finally {
      setIsGenerating(false);
    }
  }, [colors, coverImageUrl, podcastName, podcastDescription, createThemeMutation, updatePageMutation, queryClient, onThemeGenerated, onClose]);

  // Decode HTML entities
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  const truncatedName = podcastName 
    ? (() => {
        const decoded = decodeHtmlEntities(podcastName);
        return decoded.length > 30 ? decoded.substring(0, 27) + '...' : decoded;
      })()
    : 'Untitled Podcast';
  
  const truncatedDescription = podcastDescription
    ? (() => {
        const decoded = decodeHtmlEntities(podcastDescription);
        return decoded.length > 113 ? decoded.substring(0, 110) + '...' : decoded;
      })()
    : 'No description available';

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h2>Generate Theme from Podcast</h2>
          <p>Extract colors from your podcast cover art and create a custom theme</p>
        </div>
        <button
          type="button"
          className={styles.closeButton}
          onClick={onClose}
          aria-label="Close"
        >
          <X aria-hidden="true" size={20} weight="regular" />
        </button>
      </header>

      <div className={styles.content}>
        {/* Podcast Info */}
        <section className={styles.section}>
          <h3>Podcast Information</h3>
          {coverImageUrl && (
            <div className={styles.coverImage}>
              <img src={coverImageUrl} alt={podcastName || 'Podcast cover'} />
            </div>
          )}
          <div className={styles.podcastInfo}>
            <p className={styles.podcastName}>{truncatedName}</p>
            <p className={styles.podcastDescription}>{truncatedDescription}</p>
          </div>
        </section>

        {/* Color Extraction */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h3>Color Palette</h3>
            {colors.length === 0 && (
              <button
                type="button"
                className={styles.extractButton}
                onClick={handleExtractColors}
                disabled={isExtracting || !coverImageUrl}
              >
                {isExtracting ? (
                  <>
                    <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
                    Extracting...
                  </>
                ) : (
                  'Extract Colors'
                )}
              </button>
            )}
          </div>

          {isExtracting && (
            <div className={styles.loading}>
              <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
              <p>Extracting colors from cover image...</p>
            </div>
          )}

          {colors.length > 0 && (
            <>
              <div className={styles.colorSwatches}>
                {colors.map((color, index) => (
                  <div
                    key={index}
                    className={styles.swatch}
                    style={{ backgroundColor: color }}
                    title={color}
                  >
                    <span className={styles.swatchLabel}>{index + 1}</span>
                  </div>
                ))}
              </div>
              <div className={styles.colorActions}>
                <button
                  type="button"
                  className={styles.shuffleButton}
                  onClick={handleShuffle}
                  disabled={isShuffling}
                >
                  {isShuffling ? (
                    <>
                      <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
                      Shuffling...
                    </>
                  ) : (
                    <>
                      <Shuffle aria-hidden="true" size={16} weight="regular" />
                      Shuffle Colors
                    </>
                  )}
                </button>
              </div>
            </>
          )}
        </section>

        {/* Live Preview */}
        {colors.length >= 2 && (
          <section className={styles.section}>
            <h3>Preview</h3>
            <div className={styles.previewContainer}>
              <ThemePreview cssVars={previewCSSVars} />
            </div>
          </section>
        )}

        {/* Error Display */}
        {error && (
          <div className={styles.error}>
            <X aria-hidden="true" size={20} weight="regular" />
            <p>{error}</p>
          </div>
        )}

        {/* Actions */}
        <div className={styles.actions}>
          <button
            type="button"
            className={styles.cancelButton}
            onClick={onClose}
          >
            Cancel
          </button>
          <button
            type="button"
            className={styles.generateButton}
            onClick={handleGenerateTheme}
            disabled={isGenerating || colors.length < 2}
          >
            {isGenerating ? (
              <>
                <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
                Generating...
              </>
            ) : (
              <>
                <Check aria-hidden="true" size={16} weight="regular" />
                Generate Theme
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}

