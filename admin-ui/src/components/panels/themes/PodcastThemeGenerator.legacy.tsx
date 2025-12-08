/**
 * Podcast Theme Generator Component
 * Core component for generating themes from podcast cover art
 * Standalone and reusable
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Shuffle, CircleNotch, Check, X, MagnifyingGlass, Upload, Images, Rss } from '@phosphor-icons/react';
import { extractColorsFromImage, generateThemeFromPodcast, shuffleThemeColors, type GeneratedThemeData } from '../../../api/podcastTheme';
import { useCreateThemeMutation } from '../../../api/themes';
import { usePageAppearanceMutation, updatePageThemeId, searchPodcasts, type PodcastSearchResult, usePageSnapshot } from '../../../api/page';
import { queryKeys, normalizeImageUrl } from '../../../api/utils';
import { ThemePreview } from './preview/ThemePreview';
import { MediaLibraryDrawer } from '../../overlays/MediaLibraryDrawer';
import { useUploadToMediaLibraryMutation, type MediaItem } from '../../../api/media';
import styles from './podcast-theme-generator.module.css';

// Timing constants
const TIMING = {
  EXTRACTION_DELAY_MS: 500,
  PREVIEW_UPDATE_DELAY_MS: 10,
} as const;

// Default color palette (used to detect extraction failures)
const DEFAULT_COLORS = ['#2563eb', '#1d4ed8', '#3b82f6', '#60a5fa', '#93c5fd'] as const;

// TypeScript interfaces for theme data structures
interface TypographyColorTokens {
  heading: string;
  body: string;
  widget_heading: string;
  widget_body: string;
}

interface TypographyTokens {
  color: TypographyColorTokens;
}

interface AccentTokens {
  primary: string;
}

interface SemanticTokens {
  accent: AccentTokens;
}

interface ColorTokens {
  semantic: SemanticTokens;
}

interface TypedThemeData extends GeneratedThemeData {
  typography_tokens: TypographyTokens;
  color_tokens: ColorTokens;
}

interface PodcastThemeGeneratorProps {
  coverImageUrl: string | null; // Initial RSS feed cover image
  onClose: () => void;
  onThemeGenerated?: (themeId: number) => void;
}

type TabType = 'rss' | 'photo';

export function PodcastThemeGenerator({
  coverImageUrl: initialCoverImageUrl,
  onClose,
  onThemeGenerated
}: PodcastThemeGeneratorProps): JSX.Element {
  const queryClient = useQueryClient();
  const createThemeMutation = useCreateThemeMutation();
  const updatePageMutation = usePageAppearanceMutation();
  const uploadToMediaLibraryMutation = useUploadToMediaLibraryMutation();
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;

  // Tab state
  const [activeTab, setActiveTab] = useState<TabType>('rss');
  
  // RSS Tab state
  const [searchQuery, setSearchQuery] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [searchResults, setSearchResults] = useState<PodcastSearchResult[]>([]);
  const [selectedPodcast, setSelectedPodcast] = useState<PodcastSearchResult | null>(null);
  const [isUploadingArtwork, setIsUploadingArtwork] = useState(false);
  
  // Photo Tab state
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [uploadedImageUrl, setUploadedImageUrl] = useState<string | null>(null);
  
  // State for the active image URL (from media library)
  const [activeImageUrl, setActiveImageUrl] = useState<string | null>(null);
  
  // Use active image URL (from media library) as primary source
  // This ensures we always use the media library URL for display and extraction
  // Fallback to selected podcast artwork, uploaded image, current page cover_image, or initial RSS image if activeImageUrl not set yet
  const rawCoverImageUrl = activeImageUrl || selectedPodcast?.artwork_url || uploadedImageUrl || page?.cover_image || initialCoverImageUrl;
  const coverImageUrl = rawCoverImageUrl ? normalizeImageUrl(rawCoverImageUrl) : null;

  const [colors, setColors] = useState<string[]>([]);
  const [isExtracting, setIsExtracting] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isShuffling, setIsShuffling] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [previewCSSVars, setPreviewCSSVars] = useState<Record<string, string>>({});
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
  
  // Track the last image URL we extracted colors from to avoid re-extraction
  const lastExtractedImageUrl = useRef<string | null>(null);
  
  // Track if we've initialized from current theme (to prevent re-initialization)
  const hasInitializedFromTheme = useRef(false);

  // Helper function to set cover image in preview CSS vars (temporary preview only)
  // NOTE: This is for preview display only - does not affect saved profile_image
  const setProfileImageInPreview = useCallback((imageUrl: string | null) => {
    if (imageUrl) {
      setPreviewCSSVars(prev => ({
        ...prev,
        '--preview-profile-image-url': normalizeImageUrl(imageUrl)
      }));
    }
  }, []);

  // Helper function for development-only logging
  const devLog = useCallback((message: string, ...args: unknown[]) => {
    if (process.env.NODE_ENV === 'development') {
      console.log(message, ...args);
    }
  }, []);

  // Helper function for development-only error logging
  const devError = useCallback((message: string, ...args: unknown[]) => {
    if (process.env.NODE_ENV === 'development') {
      console.error(message, ...args);
    }
  }, []);
  
  // Auto-extract colors when active image URL changes (only if no colors exist yet and upload is complete)
  useEffect(() => {
    // Only extract if:
    // 1. We have an active image URL
    // 2. No colors have been extracted yet OR the image URL has changed
    // 3. Not currently extracting
    // 4. Not currently uploading (wait for upload to complete)
    // 5. The image URL is different from the last one we extracted from
    const isDifferentImage = activeImageUrl && activeImageUrl !== lastExtractedImageUrl.current;
    
    if (
      activeImageUrl && 
      isDifferentImage &&
      colors.length === 0 && 
      !isExtracting && 
      !isUploadingArtwork
    ) {
      // Auto-extract colors when image is ready
      const extract = async () => {
        if (!activeImageUrl) return;
        
        // Double-check we haven't already extracted for this URL
        if (lastExtractedImageUrl.current === activeImageUrl) {
          return;
        }
        
        setIsExtracting(true);
        setError(null);

        try {
          // Use the image URL for extraction (normalized)
          const imageUrlForExtraction = normalizeImageUrl(activeImageUrl);
          devLog('Extracting colors from image URL:', imageUrlForExtraction);
          
          // Wait a bit to ensure the image is fully available
          await new Promise(resolve => setTimeout(resolve, TIMING.EXTRACTION_DELAY_MS));
          
          // Attempt extraction - backend will handle URL accessibility
          const extractedColors = await extractColorsFromImage(imageUrlForExtraction);
          if (extractedColors.length < 5) {
            setError('Failed to extract 5 colors from image');
            return;
          }
          
          // CRITICAL: Check if we got default colors (backend fallback)
          const isDefaultColors = JSON.stringify(extractedColors.slice(0, 5).sort()) === JSON.stringify([...DEFAULT_COLORS].sort());
          
          if (isDefaultColors) {
            throw new Error('Color extraction failed - received default colors. The image may not be accessible.');
          }
          
          devLog('Extracted colors:', extractedColors);
          setColors(extractedColors.slice(0, 5));
          lastExtractedImageUrl.current = activeImageUrl; // Mark as extracted
          // Clear any previous errors on success
          setError(null);
        } catch (err) {
          const errorMessage = err instanceof Error ? err.message : 'Failed to extract colors';
          setError(errorMessage);
          devError('Color extraction error:', err);
          // Don't set colors if extraction failed - user should see the error
        } finally {
          setIsExtracting(false);
        }
      };
      
      // Delay to ensure image is fully loaded and available
      const timer = setTimeout(() => {
        extract();
      }, TIMING.EXTRACTION_DELAY_MS);
      
      return () => clearTimeout(timer);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeImageUrl, isUploadingArtwork]); // Depend on activeImageUrl and upload status

  // Extract colors from the current cover image
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
      devError('Color extraction error:', err);
    } finally {
      setIsExtracting(false);
    }
  }, [coverImageUrl, colors.length, devError]);
  
  // RSS Tab: Handle podcast search
  const handleSearchPodcasts = useCallback(async () => {
    if (!searchQuery.trim()) {
      setError('Please enter a search query');
      return;
    }
    
    setIsSearching(true);
    setError(null);
    setSearchResults([]);
    
    try {
      const response = await searchPodcasts(searchQuery.trim());
      if (response.success && response.data?.results) {
        setSearchResults(response.data.results);
      } else {
        setError(response.error || 'Failed to search podcasts');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to search podcasts');
    } finally {
      setIsSearching(false);
    }
  }, [searchQuery]);
  
  // RSS Tab: Handle podcast selection (uploads to media library, then auto-extracts colors)
  const handleSelectPodcast = useCallback(async (podcast: PodcastSearchResult) => {
    setUploadedImageUrl(null); // Clear uploaded image when selecting a podcast
    setActiveImageUrl(null); // Clear active image
    lastExtractedImageUrl.current = null; // Reset extraction tracking
    setSearchResults([]);
    setSearchQuery('');
    setColors([]); // Reset colors - will be auto-extracted after upload
    setError(null);
    setIsUploadingArtwork(true);
    
    // If podcast has artwork, add it to media library first before showing it
    if (podcast.artwork_url) {
      try {
        // Try direct fetch (works for same-origin URLs or CORS-enabled sources)
        let response: Response;
        try {
          response = await fetch(podcast.artwork_url, {
            mode: 'cors',
            credentials: 'omit',
          });
        } catch (corsError) {
          // CORS error - the image might not be accessible
          if (process.env.NODE_ENV === 'development') {
            console.warn('Direct fetch failed (CORS):', corsError);
          }
          throw new Error('Unable to fetch podcast artwork due to CORS restrictions. Please try a different podcast or upload an image directly.');
        }
        
        if (!response.ok) {
          throw new Error(`Failed to fetch artwork: ${response.status} ${response.statusText}`);
        }
        
        const blob = await response.blob();
        const file = new File([blob], `${podcast.name.replace(/[^a-z0-9]/gi, '_')}_cover.jpg`, { type: blob.type || 'image/jpeg' });
        
        const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
        if (uploadResult.media?.file_url) {
          // Set the uploaded URL as active image (from media library)
          const uploadedUrl = uploadResult.media.file_url;
          setActiveImageUrl(uploadedUrl);
          setSelectedPodcast({ ...podcast, artwork_url: uploadedUrl });
          // Colors will be auto-extracted via useEffect when activeImageUrl updates
        } else {
          throw new Error('Upload succeeded but no file URL was returned');
        }
      } catch (err) {
        devError('Failed to upload artwork to media library:', err);
        setError(err instanceof Error ? err.message : 'Failed to upload artwork to media library.');
        // Don't set selectedPodcast if upload fails - user should try again
      } finally {
        setIsUploadingArtwork(false);
      }
    } else {
      // No artwork URL, just set the podcast
      setSelectedPodcast(podcast);
      setIsUploadingArtwork(false);
    }
  }, [uploadToMediaLibraryMutation]);
  
  // Photo Tab: Handle image upload (uploads to media library, then auto-extracts colors)
  const handleImageUpload = useCallback(async (file: File) => {
    try {
      setError(null);
      setIsUploadingArtwork(true);
      lastExtractedImageUrl.current = null; // Reset extraction tracking
      const result = await uploadToMediaLibraryMutation.mutateAsync(file);
      if (result.media?.file_url) {
        setUploadedImageUrl(result.media.file_url);
        setActiveImageUrl(result.media.file_url); // Set as active image from media library
        setSelectedPodcast(null); // Clear selected podcast when uploading image
        setColors([]); // Reset colors - will be auto-extracted after upload
        // Colors will be auto-extracted via useEffect when activeImageUrl updates
      } else {
        setError('Upload succeeded but no file URL was returned');
      }
    } catch (err) {
      devError('Image upload error:', err);
      const errorMessage = err instanceof Error ? err.message : 'Failed to upload image';
      setError(errorMessage);
    } finally {
      setIsUploadingArtwork(false);
    }
  }, [uploadToMediaLibraryMutation]);
  
  // Photo Tab: Handle Media Library selection (loads from media library, then auto-extracts colors)
  const handleSelectFromMediaLibrary = useCallback((mediaItem: MediaItem) => {
    setUploadedImageUrl(mediaItem.file_url);
    setActiveImageUrl(mediaItem.file_url); // Set as active image from media library
    lastExtractedImageUrl.current = null; // Reset extraction tracking
    setSelectedPodcast(null); // Clear selected podcast when selecting from media library
    setColors([]); // Reset colors - will be auto-extracted after selection
    setMediaLibraryOpen(false);
    // Colors will be auto-extracted via useEffect when activeImageUrl updates
  }, []);
  
  // Initialize from current theme settings
  useEffect(() => {
    if (hasInitializedFromTheme.current || !page) return;
    
    hasInitializedFromTheme.current = true;
    
    // Initialize cover image from current page cover_image if available
    if (page.cover_image && !activeImageUrl && !selectedPodcast && !uploadedImageUrl && !initialCoverImageUrl) {
      setActiveImageUrl(page.cover_image);
    }
    
    // Extract colors from current theme if available
    if (page.colors && typeof page.colors === 'object' && !Array.isArray(page.colors)) {
      // Try to extract color array from theme colors object
      // The colors object might have a 'palette' or 'colors' array, or be structured differently
      const colorObj = page.colors as Record<string, unknown>;
      
      // Check for common color array patterns
      let extractedColors: string[] = [];
      
      if (Array.isArray(colorObj.palette)) {
        extractedColors = (colorObj.palette as string[]).slice(0, 5);
      } else if (Array.isArray(colorObj.colors)) {
        extractedColors = (colorObj.colors as string[]).slice(0, 5);
      } else if (Array.isArray(colorObj)) {
        extractedColors = (colorObj as string[]).slice(0, 5);
      } else {
        // Try to extract colors from color_tokens structure
        const colorTokens = colorObj.color_tokens as Record<string, unknown> | undefined;
        if (colorTokens) {
          const semantic = colorTokens.semantic as Record<string, unknown> | undefined;
          if (semantic) {
            const accent = semantic.accent as Record<string, unknown> | undefined;
            if (accent && typeof accent.primary === 'string') {
              extractedColors.push(accent.primary as string);
            }
          }
        }
      }
      
      // If we found colors, set them (but only if we don't already have colors)
      if (extractedColors.length >= 2 && colors.length === 0) {
        // Pad to 5 colors if needed (repeat last color)
        while (extractedColors.length < 5 && extractedColors.length > 0) {
          extractedColors.push(extractedColors[extractedColors.length - 1]);
        }
        if (extractedColors.length === 5) {
          setColors(extractedColors);
        }
      }
    }
    
    // Initialize preview with current theme settings if available
    if (page && colors.length === 0) {
      // Create a preview from current page settings
      const currentPreviewVars: Record<string, string> = {};
      
      // Backgrounds
      if (page.page_background) {
        currentPreviewVars['--page-background'] = page.page_background;
      }
      if (page.widget_background) {
        currentPreviewVars['--widget-background'] = page.widget_background;
      }
      if (page.widget_border_color) {
        currentPreviewVars['--widget-border-color'] = page.widget_border_color;
      }
      
      // Fonts
      if (page.page_primary_font) {
        currentPreviewVars['--page-title-font'] = `'${page.page_primary_font}', sans-serif`;
        currentPreviewVars['--page-primary-font'] = page.page_primary_font;
        currentPreviewVars['--font-family-heading'] = `'${page.page_primary_font}', sans-serif`;
      }
      if (page.page_secondary_font) {
        currentPreviewVars['--page-description-font'] = `'${page.page_secondary_font}', monospace`;
        currentPreviewVars['--page-secondary-font'] = page.page_secondary_font;
        currentPreviewVars['--font-family-body'] = `'${page.page_secondary_font}', sans-serif`;
      }
      if (page.widget_primary_font) {
        currentPreviewVars['--widget-heading-font'] = `'${page.widget_primary_font}', sans-serif`;
        currentPreviewVars['--widget-primary-font'] = page.widget_primary_font;
      }
      if (page.widget_secondary_font) {
        currentPreviewVars['--widget-body-font'] = `'${page.widget_secondary_font}', monospace`;
        currentPreviewVars['--widget-secondary-font'] = page.widget_secondary_font;
      }
      
      // Cover image for preview
      if (page.cover_image) {
        currentPreviewVars['--preview-profile-image-url'] = normalizeImageUrl(page.cover_image);
      }
      
      // Set preview vars if we have any
      if (Object.keys(currentPreviewVars).length > 0) {
        setPreviewCSSVars(currentPreviewVars);
      }
    }
  }, [page, activeImageUrl, selectedPodcast, uploadedImageUrl, initialCoverImageUrl, colors.length]);

  // On mount: If initial cover image exists, check if it's already in media library or upload it
  useEffect(() => {
    if (initialCoverImageUrl && !activeImageUrl && !selectedPodcast && !uploadedImageUrl) {
      const setupInitialImage = async () => {
        try {
          setIsUploadingArtwork(true);
          setError(null); // Clear any previous errors
          
          // Check if the image is already a media library URL
          const isMediaLibraryUrl = initialCoverImageUrl.includes('/uploads/media/') || 
                                    initialCoverImageUrl.includes('/uploads/') ||
                                    (initialCoverImageUrl.startsWith('/') && !initialCoverImageUrl.startsWith('//'));
          
          if (isMediaLibraryUrl) {
            // Already in media library or local path, use it directly
            // Reset extraction tracking since this is a new image
            lastExtractedImageUrl.current = null;
            setActiveImageUrl(initialCoverImageUrl);
            setIsUploadingArtwork(false);
            // Colors will be auto-extracted via useEffect when activeImageUrl is set
            return;
          }
          
          // Not in media library - try to upload it
          // But if upload fails, still try to use the original URL for extraction
          let response: Response;
          try {
            response = await fetch(initialCoverImageUrl, {
              mode: 'cors',
              credentials: 'omit',
            });
            
            if (!response.ok) {
              throw new Error(`Failed to fetch initial image: ${response.status} ${response.statusText}`);
            }
            
            const blob = await response.blob();
            const file = new File([blob], 'rss_cover.jpg', { type: blob.type || 'image/jpeg' });
            
            const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
            if (uploadResult.media?.file_url) {
              lastExtractedImageUrl.current = null; // Reset extraction tracking
              setActiveImageUrl(uploadResult.media.file_url);
            } else {
              throw new Error('Upload succeeded but no file URL was returned');
            }
          } catch (uploadError) {
            // Upload failed - but if it's a local/poda.bio URL, we can still try to use it
            if (process.env.NODE_ENV === 'development') {
              console.warn('Failed to upload initial image, but will try to use original URL:', uploadError);
            }
            
            // If it's a local URL or poda.bio URL, try using it directly
            // The backend ColorExtractor can handle local file paths
            if (initialCoverImageUrl.includes('poda.bio') || 
                initialCoverImageUrl.startsWith('/') || 
                initialCoverImageUrl.startsWith('http://localhost') ||
                initialCoverImageUrl.startsWith('https://localhost')) {
              devLog('Using initial image URL directly (local/poda.bio URL):', initialCoverImageUrl);
              lastExtractedImageUrl.current = null;
              setActiveImageUrl(initialCoverImageUrl);
              // Don't show error - we'll try to extract from the original URL
              setError(null);
            } else {
              // External URL that we can't upload - use it directly and try extraction
              devLog('Using external image URL directly, will attempt color extraction');
              lastExtractedImageUrl.current = null;
              setActiveImageUrl(initialCoverImageUrl);
              // Don't show error - we'll try to extract colors from the URL
              setError(null);
            }
          }
        } catch (err) {
          devError('Unexpected error setting up initial image:', err);
          // Even on error, try to use the original URL
          lastExtractedImageUrl.current = null;
          setActiveImageUrl(initialCoverImageUrl);
          setError('Warning: Could not upload image, but will attempt to extract colors from original URL.');
        } finally {
          setIsUploadingArtwork(false);
        }
      };
      
      setupInitialImage();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Only run on mount

  // Clear preview when component unmounts or colors are reset
  useEffect(() => {
    return () => {
      setPreviewCSSVars({});
    };
  }, []);

  // Update preview when colors change or selected image is available
  // CRITICAL: Force preview update when colors change to clear previous theme
  useEffect(() => {
    if (colors.length >= 2) {
      // Clear preview first to remove old CSS variables
      setPreviewCSSVars({});
      // Small delay to ensure clearing happens, then update
      const timer = setTimeout(() => {
        updatePreview();
      }, TIMING.PREVIEW_UPDATE_DELAY_MS);
      return () => clearTimeout(timer);
    } else if (coverImageUrl) {
      // If we have a selected image but no colors yet, just set the profile image
      // This allows the preview to show the selected image before colors are extracted
      // Use coverImageUrl (the image used for color extraction) for the profile image
      setProfileImageInPreview(coverImageUrl);
    } else {
      // Clear preview if we don't have enough colors or selected image
      setPreviewCSSVars({});
    }
  }, [colors, coverImageUrl, setProfileImageInPreview]);


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
      devError('Color shuffle error:', err);
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
      // If we have a selected image but not enough colors, at least set the profile image
      if (coverImageUrl) {
        setProfileImageInPreview(coverImageUrl);
      } else {
        // Clear preview when we don't have enough colors and no image
        setPreviewCSSVars({});
      }
      return;
    }

    try {
      const themeData = await generateThemeFromPodcast({
        coverImageUrl: coverImageUrl || '',
        colors
      });

      // CRITICAL: Clear previous CSS variables and set ALL new ones
      // This ensures no leftover values from previous themes
      // We need to set ALL CSS variables that ThemePreview uses, not just a few
      
      // Extract color values with proper type checking
      const typedThemeData = themeData as TypedThemeData;
      const typographyColor = typedThemeData.typography_tokens?.color;
      const headingColor = typographyColor?.heading || '#000000';
      const bodyColor = typographyColor?.body || '#666666';
      const widgetHeadingColor = typographyColor?.widget_heading || '#000000';
      const widgetBodyColor = typographyColor?.widget_body || '#666666';
      
      // Extract accent color with proper type checking
      const semanticTokens = typedThemeData.color_tokens?.semantic;
      const accentTokens = semanticTokens?.accent;
      const accentPrimary = accentTokens?.primary || '#2563eb';
      
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
        '--page-description-font': themeData.page_secondary_font ? `'${themeData.page_secondary_font}', monospace` : "'Space Mono', monospace",
        '--widget-heading-font': themeData.widget_primary_font ? `'${themeData.widget_primary_font}', sans-serif` : "'Zalando Sans Expanded', sans-serif",
        '--widget-body-font': themeData.widget_secondary_font ? `'${themeData.widget_secondary_font}', monospace` : "'Space Mono', monospace",
        '--page-primary-font': themeData.page_primary_font || 'Zalando Sans Expanded',
        '--page-secondary-font': themeData.page_secondary_font || 'Space Mono',
        '--widget-primary-font': themeData.widget_primary_font || 'Zalando Sans Expanded',
        '--widget-secondary-font': themeData.widget_secondary_font || 'Space Mono',
        '--font-family-heading': themeData.page_primary_font ? `'${themeData.page_primary_font}', sans-serif` : "'Zalando Sans Expanded', sans-serif",
        '--font-family-body': themeData.page_secondary_font ? `'${themeData.page_secondary_font}', sans-serif` : "'Space Mono', monospace",
        
        // Typography sizes - Set defaults to clear previous theme
        '--page-title-size': '32px',
        '--page-description-size': '16px',
        '--widget-heading-size': '20px',
        '--widget-body-size': '14px',
        
        // Accent colors - CRITICAL: Clear previous theme accents
        '--icon-color': accentPrimary,
        '--social-icon-color': accentPrimary,
        '--color-accent-primary': accentPrimary,
        
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
      
      // Also store the selected cover image URL for temporary preview display
      // This will be used by ThemePreview to update the profile image in the iframe temporarily
      // NOTE: This is for preview only - the actual cover_image is saved separately from profile_image
      // The coverImageUrl is the image used for color extraction (cover_image field)
      if (coverImageUrl) {
        cssVars['--preview-profile-image-url'] = normalizeImageUrl(coverImageUrl);
      }

      setPreviewCSSVars(cssVars);
    } catch (err) {
      devError('Preview update error:', err);
      // Clear preview on error
      setPreviewCSSVars({});
    }
  }, [colors, coverImageUrl, setProfileImageInPreview, devError]);

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

      // Ensure themeId is a number
      let themeId: number;
      if (typeof response.theme_id === 'string') {
        themeId = parseInt(response.theme_id, 10);
        if (isNaN(themeId)) {
          throw new Error('Invalid theme ID returned from server');
        }
      } else if (typeof response.theme_id === 'number') {
        themeId = response.theme_id;
      } else {
        throw new Error('Invalid theme ID type returned from server');
      }

      // Apply theme to page
      await updatePageThemeId(themeId);

      // Set cover image (separate from profile_image)
      // VALIDATION: Theme Wizard must NEVER set profile_image - only cover_image
      // This prevents overwriting the user's intended profile image
      const pageUpdates: Record<string, string | number> = {};
      if (coverImageUrl) {
        pageUpdates.cover_image = coverImageUrl;
        // NOTE: We intentionally do NOT set pageUpdates.profile_image here
        // The cover_image is used for theme generation only, not for page display
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
        // Convert pageUpdates to Payload format (Record<string, FormDataEntryValue | undefined>)
        const payload: Record<string, FormDataEntryValue | undefined> = {};
        for (const [key, value] of Object.entries(pageUpdates)) {
          payload[key] = value !== null && value !== undefined ? String(value) : undefined;
        }
        await updatePageMutation.mutateAsync(payload);
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
      devError('Theme generation error:', err);
    } finally {
      setIsGenerating(false);
    }
  }, [colors, coverImageUrl, createThemeMutation, updatePageMutation, queryClient, onThemeGenerated, onClose]);



  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h2>Theme Wizard</h2>
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
        {/* Tabs */}
        <div className={styles.tabs}>
          <button
            type="button"
            className={`${styles.tab} ${activeTab === 'rss' ? styles.tabActive : ''}`}
            onClick={() => {
              setActiveTab('rss');
              // Clear photo tab selections when switching to RSS
              if (activeTab !== 'rss') {
                setUploadedImageUrl(null);
              }
            }}
          >
            <Rss aria-hidden="true" size={16} weight="regular" />
            Theme from RSS
          </button>
          <button
            type="button"
            className={`${styles.tab} ${activeTab === 'photo' ? styles.tabActive : ''}`}
            onClick={() => {
              setActiveTab('photo');
              // Clear RSS tab selections when switching to Photo
              if (activeTab !== 'photo') {
                setSelectedPodcast(null);
              }
            }}
          >
            <Images aria-hidden="true" size={16} weight="regular" />
            Theme from Photo
          </button>
        </div>

        {/* RSS Tab Content */}
        {activeTab === 'rss' && (
          <section className={styles.card}>
            <h3 className={styles.cardTitle}>Search Podcast</h3>
            <div className={styles.cardContent}>
              <div className={styles.searchContainer}>
                <input
                  type="text"
                  className={styles.searchInput}
                  placeholder="Search for a podcast..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      handleSearchPodcasts();
                    }
                  }}
                />
                <button
                  type="button"
                  className={styles.searchButton}
                  onClick={handleSearchPodcasts}
                  disabled={isSearching || !searchQuery.trim()}
                >
                  {isSearching ? (
                    <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
                  ) : (
                    <MagnifyingGlass aria-hidden="true" size={16} weight="regular" />
                  )}
                </button>
              </div>
              
              {searchResults.length > 0 && (
                <div className={styles.searchResults}>
                  {searchResults.map((podcast) => (
                    <div
                      key={podcast.id}
                      className={`${styles.podcastResult} ${selectedPodcast?.id === podcast.id ? styles.podcastResultSelected : ''}`}
                      onClick={() => handleSelectPodcast(podcast)}
                      title={podcast.name}
                    >
                      {podcast.artwork_url ? (
                        <img
                          src={podcast.artwork_url}
                          alt={podcast.name}
                          className={styles.podcastResultImage}
                        />
                      ) : (
                        <div className={styles.podcastResultImagePlaceholder}>
                          <Images aria-hidden="true" size={24} weight="regular" />
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
              
            </div>
          </section>
        )}

        {/* Photo Tab Content */}
        {activeTab === 'photo' && (
          <section className={styles.card}>
            <h3 className={styles.cardTitle}>Select Image</h3>
            <div className={styles.cardContent}>
              <div className={styles.photoOptions}>
                <button
                  type="button"
                  className={styles.photoOptionButton}
                  onClick={() => setMediaLibraryOpen(true)}
                >
                  <Images aria-hidden="true" size={24} weight="regular" />
                  Choose from Library
                </button>
                <label className={styles.photoOptionButton}>
                  <Upload aria-hidden="true" size={24} weight="regular" />
                  Upload Image
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    style={{ display: 'none' }}
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) {
                        handleImageUpload(file);
                      }
                    }}
                  />
                </label>
              </div>
              
              {uploadedImageUrl && (
                <div className={styles.selectedImage}>
                  <div className={styles.coverImage}>
                    <img src={uploadedImageUrl} alt="Selected image" />
                  </div>
                </div>
              )}
            </div>
          </section>
        )}

        {/* Image Preview Card - Show when image is selected */}
        {isUploadingArtwork && (
          <section className={styles.card}>
            <h3 className={styles.cardTitle}>Selected Image</h3>
            <div className={styles.cardContent}>
              <div className={styles.loading}>
                <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
                <p>Uploading artwork to media library...</p>
              </div>
            </div>
          </section>
        )}
        {coverImageUrl && !isUploadingArtwork && (
          <section className={styles.card}>
            <h3 className={styles.cardTitle}>Selected Image</h3>
            <div className={styles.cardContent}>
              <div className={styles.coverImage}>
                <img 
                  src={coverImageUrl} 
                  alt="Selected image" 
                  onError={(e) => {
                    devError('Image failed to load:', coverImageUrl);
                    setError(`Failed to load image from: ${coverImageUrl}`);
                  }}
                />
              </div>
            </div>
          </section>
        )}
        
        {/* Media Library Drawer */}
        <MediaLibraryDrawer
          open={mediaLibraryOpen}
          onClose={() => setMediaLibraryOpen(false)}
          onSelect={handleSelectFromMediaLibrary}
        />

        {/* Color Palette Card */}
        <section className={styles.card}>
          <div className={styles.cardHeader}>
            <h3 className={styles.cardTitle}>Color Palette</h3>
                    {colors.length === 0 && isExtracting && (
                      <div className={styles.extracting}>
                        <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
                        Extracting colors...
                      </div>
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

        {/* Live Preview - Show when selected image is available or colors are extracted */}
        {coverImageUrl && (
          <section className={styles.section}>
            <h3 className={styles.cardTitle}>Preview</h3>
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

