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
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);

  // Prevent re-extraction once we have colors
  const handleExtractColors = useCallback(async () => {
    // CRITICAL: Once we have 5 colors, prevent re-extraction
    if (colors.length >= 5) {
      setError('Colors already extracted. Use shuffle to rearrange them.');
      return;
    }
    
    if (!coverImageUrl) {
      setError('No cover image URL provided');
      return;
    }

    setIsExtracting(true);
    setError(null);

    try {
      const extractedColors = await extractColorsFromImage(coverImageUrl);
      // Ensure we have exactly 5 colors
      if (extractedColors.length < 5) {
        setError('Failed to extract 5 colors from image');
        return;
      }
      setColors(extractedColors.slice(0, 5)); // Take exactly 5 colors
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to extract colors');
      console.error('Color extraction error:', err);
    } finally {
      setIsExtracting(false);
    }
  }, [coverImageUrl, colors.length]);

  // Extract colors on mount if cover image is available
  // CRITICAL: Only extract once - once we have 5 colors, we only shuffle those
  useEffect(() => {
    if (coverImageUrl && colors.length === 0) {
      handleExtractColors();
    }
  }, [coverImageUrl, colors.length, handleExtractColors]);
  
  // Clear preview when component unmounts or colors are reset
  useEffect(() => {
    return () => {
      setPreviewCSSVars({});
    };
  }, []);

  // Update preview when colors change
  // CRITICAL: Force preview update when colors change to clear previous theme
  useEffect(() => {
    if (colors.length >= 2) {
      // Clear preview first to remove old CSS variables
      setPreviewCSSVars({});
      // Small delay to ensure clearing happens, then update
      const timer = setTimeout(() => {
        updatePreview();
      }, 10);
      return () => clearTimeout(timer);
    } else {
      // Clear preview if we don't have enough colors
      setPreviewCSSVars({});
    }
  }, [colors]);

  const handleShuffle = useCallback(async () => {
    // CRITICAL: Only shuffle if we have exactly 5 colors (the extracted palette)
    // Once colors are extracted, we only shuffle those 5, never extract new ones
    if (colors.length !== 5) {
      setError('Please extract colors first (5 colors required)');
      return;
    }

    setIsShuffling(true);
    setError(null);

    try {
      // Shuffle only the existing 5 colors - no new extraction
      const shuffled = await shuffleThemeColors(colors);
      setColors(shuffled);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to shuffle colors');
      console.error('Color shuffle error:', err);
    } finally {
      setIsShuffling(false);
    }
  }, [colors]);

  // Drag and drop handlers
  const handleDragStart = (index: number) => {
    setDraggedIndex(index);
  };

  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (draggedIndex !== null && draggedIndex !== index) {
      setDragOverIndex(index);
    }
  };

  const handleDragLeave = () => {
    setDragOverIndex(null);
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    
    if (draggedIndex === null || draggedIndex === dropIndex) {
      setDraggedIndex(null);
      setDragOverIndex(null);
      return;
    }

    const newColors = [...colors];
    const draggedColor = newColors[draggedIndex];
    
    // Remove dragged color
    newColors.splice(draggedIndex, 1);
    
    // Insert at new position
    newColors.splice(dropIndex, 0, draggedColor);
    
    setColors(newColors);
    setDraggedIndex(null);
    setDragOverIndex(null);
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
    setDragOverIndex(null);
  };

  // Color role labels
  const getColorRole = (index: number): string => {
    const roles = [
      'Background Gradient Start',
      'Background Gradient End',
      'Page Title & Widget Background',
      'Body Text & Widget Text',
      'Accents & Borders'
    ];
    return roles[index] || `Color ${index + 1}`;
  };

  const updatePreview = useCallback(async () => {
    if (colors.length < 2) {
      // Clear preview when we don't have enough colors
      setPreviewCSSVars({});
      return;
    }

    try {
      const themeData = await generateThemeFromPodcast({
        coverImageUrl: coverImageUrl || '',
        podcastName,
        podcastDescription,
        colors
      });

      // CRITICAL: Clear previous CSS variables and set ALL new ones
      // This ensures no leftover values from previous themes
      // We need to set ALL CSS variables that ThemePreview uses, not just a few
      
      // Extract color values first (can't use const inside object literal)
      const headingColor = (themeData.typography_tokens?.color?.heading as string) || '#000000';
      const bodyColor = (themeData.typography_tokens?.color?.body as string) || '#666666';
      const widgetHeadingColor = (themeData.typography_tokens?.color?.widget_heading as string) || '#000000';
      const widgetBodyColor = (themeData.typography_tokens?.color?.widget_body as string) || '#666666';
      
      const cssVars: Record<string, string> = {
        // Backgrounds - CRITICAL: These must be set to clear previous theme
        '--page-background': themeData.page_background || '#ffffff',
        '--widget-background': themeData.widget_background || '#ffffff',
        '--widget-border-color': themeData.widget_border_color || '#e5e7eb',
        
        // Typography colors - CRITICAL: Clear previous theme colors
        // Set all possible variable names that CSS files might use
        '--page-title-color': headingColor,
        '--page-description-color': bodyColor,
        '--widget-heading-color': widgetHeadingColor,
        '--widget-body-color': widgetBodyColor,
        
        // Additional color variables that page.php and CSS files use
        '--heading-font-color': headingColor,
        '--body-font-color': bodyColor,
        '--widget-heading-font-color': widgetHeadingColor,
        '--widget-body-font-color': widgetBodyColor,
        '--color-text-primary': headingColor,
        '--color-text-secondary': bodyColor,
        '--text-color': bodyColor,
        
        // Typography fonts - CRITICAL: Set fonts to clear previous theme
        '--page-title-font': themeData.page_primary_font ? `'${themeData.page_primary_font}', sans-serif` : "'Inter', sans-serif",
        '--page-description-font': themeData.page_secondary_font ? `'${themeData.page_secondary_font}', sans-serif` : "'Inter', sans-serif",
        '--widget-heading-font': themeData.widget_primary_font ? `'${themeData.widget_primary_font}', sans-serif` : "'Inter', sans-serif",
        '--widget-body-font': themeData.widget_secondary_font ? `'${themeData.widget_secondary_font}', sans-serif` : "'Inter', sans-serif",
        '--page-primary-font': themeData.page_primary_font || 'Inter',
        '--page-secondary-font': themeData.page_secondary_font || 'Inter',
        '--widget-primary-font': themeData.widget_primary_font || 'Inter',
        '--widget-secondary-font': themeData.widget_secondary_font || 'Inter',
        '--font-family-heading': themeData.page_primary_font ? `'${themeData.page_primary_font}', sans-serif` : "'Inter', sans-serif",
        '--font-family-body': themeData.page_secondary_font ? `'${themeData.page_secondary_font}', sans-serif` : "'Inter', sans-serif",
        
        // Typography sizes - Set defaults to clear previous theme
        '--page-title-size': '32px',
        '--page-description-size': '16px',
        '--widget-heading-size': '20px',
        '--widget-body-size': '14px',
        
        // Accent colors - CRITICAL: Clear previous theme accents
        '--icon-color': (themeData.color_tokens?.semantic?.accent?.primary as string) || '#2563eb',
        '--social-icon-color': (themeData.color_tokens?.semantic?.accent?.primary as string) || '#2563eb',
        '--color-accent-primary': (themeData.color_tokens?.semantic?.accent?.primary as string) || '#2563eb',
        
        // Profile image - CRITICAL: Clear previous theme settings
        '--profile-image-radius': themeData.profile_image_radius ? `${themeData.profile_image_radius}%` : '15%',
        '--profile-image-size': '120px',
        '--profile-image-border-width': '0px',
        '--profile-image-border-color': 'transparent',
        '--profile-image-box-shadow': 'none',
        
        // Icon settings - CRITICAL: Clear previous theme
        '--icon-size': '32px',
        '--social-icon-size': '32px',
        '--icon-spacing': '1rem',
        '--social-icon-spacing': '1rem',
        
        // Widget styling
        '--widget-border-width': '2px',
        '--widget-border-radius': '12px',
        '--widget-spacing': '1rem',
        
        // Clear any effect-related variables from previous theme
        '--page-title-effect-class': '',
        '--page-title-text-shadow': 'none',
        '--widget-shadow-box-shadow': 'none',
        '--widget-glow-box-shadow': 'none',
      };

      setPreviewCSSVars(cssVars);
    } catch (err) {
      console.error('Preview update error:', err);
      // Clear preview on error
      setPreviewCSSVars({});
    }
  }, [colors, coverImageUrl, podcastName, podcastDescription]);

  const handleGenerateTheme = useCallback(async () => {
    // CRITICAL: Require exactly 5 colors for theme generation
    if (colors.length !== 5) {
      setError('Please extract 5 colors from the cover image first');
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
        {/* Podcast Info Card */}
        <section className={styles.card}>
          <h3 className={styles.cardTitle}>Podcast Information</h3>
          <div className={styles.cardContent}>
            {coverImageUrl && (
              <div className={styles.coverImage}>
                <img src={coverImageUrl} alt={podcastName || 'Podcast cover'} />
              </div>
            )}
            <div className={styles.podcastInfo}>
              <p className={styles.podcastName}>{truncatedName}</p>
              <p className={styles.podcastDescription}>{truncatedDescription}</p>
            </div>
          </div>
        </section>

        {/* Color Palette Card */}
        <section className={styles.card}>
          <div className={styles.cardHeader}>
            <h3 className={styles.cardTitle}>Color Palette</h3>
            {colors.length === 0 && (
              <button
                type="button"
                className={styles.extractButton}
                onClick={handleExtractColors}
                disabled={isExtracting || !coverImageUrl || colors.length >= 5}
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
            {colors.length >= 5 && (
              <p className={styles.infoText}>5 colors extracted. Drag to reorder or shuffle to rearrange.</p>
            )}
          </div>

          {isExtracting && (
            <div className={styles.loading}>
              <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
              <p>Extracting colors from cover image...</p>
            </div>
          )}

          {colors.length > 0 && (
            <div className={styles.cardContent}>
              <div className={styles.colorSwatches}>
                {colors.map((color, index) => (
                  <div
                    key={`${color}-${index}`}
                    className={`${styles.swatch} ${draggedIndex === index ? styles.dragging : ''} ${dragOverIndex === index ? styles.dragOver : ''}`}
                    style={{ backgroundColor: color }}
                    title={`${getColorRole(index)}: ${color}`}
                    draggable
                    onDragStart={() => handleDragStart(index)}
                    onDragOver={(e) => handleDragOver(e, index)}
                    onDragLeave={handleDragLeave}
                    onDrop={(e) => handleDrop(e, index)}
                    onDragEnd={handleDragEnd}
                  >
                    <span className={styles.swatchLabel}>{index + 1}</span>
                    <div className={styles.swatchRole}>{getColorRole(index)}</div>
                    <div className={styles.swatchColorCode}>{color}</div>
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
            </div>
          )}
        </section>

        {/* Live Preview */}
        {colors.length >= 2 && (
          <section className={styles.section}>
            <h3>Preview</h3>
            <div className={styles.previewContainer}>
              <ThemePreview 
                cssVars={previewCSSVars} 
                hotspotsVisible={false}
              />
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
            disabled={isGenerating || colors.length !== 5}
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

