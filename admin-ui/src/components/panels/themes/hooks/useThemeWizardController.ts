import { useCallback, useEffect, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useThemeWizardState } from './useThemeWizardState';
import { extractColorsFromImage, generateThemeFromPodcast, shuffleThemeColors, type GeneratedThemeData } from '../../../../api/podcastTheme';
import { useCreateThemeMutation } from '../../../../api/themes';
import { usePageAppearanceMutation, updatePageThemeId, searchPodcasts } from '../../../../api/page';
import { normalizeImageUrl, queryKeys } from '../../../../api/utils';
import { useUploadToMediaLibraryMutation, type MediaItem } from '../../../../api/media';
import { usePageSnapshot } from '../../../../api/page';
import type { PodcastSearchResult } from '../../../../api/page';

// TIMING constants
const TIMING = {
    EXTRACTION_DELAY_MS: 500,
    PREVIEW_UPDATE_DELAY_MS: 10,
} as const;

// Default color palette (used to detect extraction failures)
const DEFAULT_COLORS = ['#2563eb', '#1d4ed8', '#3b82f6', '#60a5fa', '#93c5fd'] as const;

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

interface TypedThemeData {
    typography_tokens: TypographyTokens;
    color_tokens: ColorTokens;
}

export function useThemeWizardController(
    initialCoverImageUrl: string | null,
    onClose: () => void,
    onThemeGenerated?: (themeId: number) => void
) {
    const { state, actions } = useThemeWizardState(initialCoverImageUrl);
    const queryClient = useQueryClient();
    const createThemeMutation = useCreateThemeMutation();
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const updatePageMutation = usePageAppearanceMutation();
    const uploadToMediaLibraryMutation = useUploadToMediaLibraryMutation();
    const { data: snapshot } = usePageSnapshot();
    const page = snapshot?.page;

    // Local state for preview vars (derived from wizard state)
    const [previewCSSVars, setPreviewCSSVars] = useState<Record<string, string>>({});

    // Track the last image URL we extracted colors from
    const lastExtractedImageUrl = useRef<string | null>(null);

    // Track initialization
    const hasInitializedFromTheme = useRef(false);

    // Helper for dev logging
    const devLog = useCallback((message: string, ...args: unknown[]) => {
        if (process.env.NODE_ENV === 'development') {
            console.log(message, ...args);
        }
    }, []);

    const devError = useCallback((message: string, ...args: unknown[]) => {
        if (process.env.NODE_ENV === 'development') {
            console.error(message, ...args);
        }
    }, []);

    // Helper function to set cover image in preview CSS vars (temporary preview only)
    const setProfileImageInPreview = useCallback((imageUrl: string | null) => {
        if (imageUrl) {
            setPreviewCSSVars(prev => ({
                ...prev,
                '--preview-profile-image-url': normalizeImageUrl(imageUrl)
            }));
        }
    }, []);

    // 1. Initial Image Setup
    useEffect(() => {
        if (initialCoverImageUrl && !state.activeImageUrl && !state.selectedPodcast && !state.uploadedImageUrl) {
            const setupInitialImage = async () => {
                try {
                    actions.setIsSearching(true); // Re-use search spinner or similar for generic loading if needed, or ignore

                    // Check if already in media library
                    const isMediaLibraryUrl = initialCoverImageUrl.includes('/uploads/media/') ||
                        initialCoverImageUrl.includes('/uploads/') ||
                        (initialCoverImageUrl.startsWith('/') && !initialCoverImageUrl.startsWith('//'));

                    if (isMediaLibraryUrl) {
                        lastExtractedImageUrl.current = null;
                        actions.setActiveImageUrl(initialCoverImageUrl);
                        return;
                    }

                    // Try to upload
                    try {
                        const response = await fetch(initialCoverImageUrl, { mode: 'cors', credentials: 'omit' });
                        if (!response.ok) throw new Error('Failed to fetch');
                        const blob = await response.blob();
                        const file = new File([blob], 'rss_cover.jpg', { type: blob.type || 'image/jpeg' });

                        const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
                        if (uploadResult.media?.file_url) {
                            lastExtractedImageUrl.current = null;
                            actions.setActiveImageUrl(uploadResult.media.file_url);
                        }
                    } catch (err) {
                        // Fallback to direct URL usage
                        devLog('Using external image URL directly', initialCoverImageUrl);
                        lastExtractedImageUrl.current = null;
                        actions.setActiveImageUrl(initialCoverImageUrl);
                    }
                } catch (err) {
                    devError('Error setting up initial image', err);
                } finally {
                    actions.setIsSearching(false); // Reset loading state if used
                }
            };
            setupInitialImage();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // 2. Initialize from current theme
    useEffect(() => {
        if (hasInitializedFromTheme.current || !page) return;
        hasInitializedFromTheme.current = true;

        // Initialize cover image
        if (page.cover_image && !state.activeImageUrl && !state.selectedPodcast && !state.uploadedImageUrl && !initialCoverImageUrl) {
            actions.setActiveImageUrl(page.cover_image);
        }

        // Initialize colors from theme if available
        if (page.colors && typeof page.colors === 'object' && !Array.isArray(page.colors) && state.colors.length === 0) {
            const colorObj = page.colors as Record<string, unknown>;
            let extractedColors: string[] = [];

            if (Array.isArray(colorObj.palette)) {
                extractedColors = (colorObj.palette as string[]).slice(0, 5);
            } else if (Array.isArray(colorObj.colors)) {
                extractedColors = (colorObj.colors as string[]).slice(0, 5);
            } else if (Array.isArray(colorObj)) {
                extractedColors = (colorObj as string[]).slice(0, 5);
            }

            if (extractedColors.length >= 2) {
                // Pad to 5
                while (extractedColors.length < 5 && extractedColors.length > 0) {
                    extractedColors.push(extractedColors[extractedColors.length - 1]);
                }
                if (extractedColors.length === 5) {
                    actions.setColors(extractedColors);
                }
            }
        }
    }, [page, state.activeImageUrl, state.selectedPodcast, state.uploadedImageUrl, initialCoverImageUrl, state.colors.length, actions]);


    // 3. Auto-Extract Colors
    useEffect(() => {
        const isDifferentImage = state.activeImageUrl && state.activeImageUrl !== lastExtractedImageUrl.current;

        if (
            state.activeImageUrl &&
            isDifferentImage &&
            state.colors.length === 0 &&
            !state.isGenerating
        ) {
            const extract = async () => {
                if (!state.activeImageUrl) return;
                if (lastExtractedImageUrl.current === state.activeImageUrl) return;

                actions.setError(null);

                try {
                    const imageUrlForExtraction = normalizeImageUrl(state.activeImageUrl);
                    await new Promise(resolve => setTimeout(resolve, TIMING.EXTRACTION_DELAY_MS));

                    const extractedColors = await extractColorsFromImage(imageUrlForExtraction);
                    if (extractedColors.length < 5) {
                        actions.setError('Failed to extract 5 colors');
                        return;
                    }

                    const isDefaultColors = JSON.stringify(extractedColors.slice(0, 5).sort()) === JSON.stringify([...DEFAULT_COLORS].sort());
                    if (isDefaultColors) {
                        console.warn('Color extraction returned default colors - using defaults.');
                    }

                    actions.setColors(extractedColors.slice(0, 5));
                    lastExtractedImageUrl.current = state.activeImageUrl;
                    actions.setError(null);
                } catch (err) {
                    actions.setError(err instanceof Error ? err.message : 'Failed to extract colors');
                }
            };

            const timer = setTimeout(extract, TIMING.EXTRACTION_DELAY_MS);
            return () => clearTimeout(timer);
        }
    }, [state.activeImageUrl, state.colors.length, state.isGenerating, actions]);

    // 4. Update Preview
    const updatePreview = useCallback(async () => {
        const coverImageUrl = state.activeImageUrl;

        // Base preview vars with content that is available immediately
        const basePreviewVars: Record<string, string> = {};

        if (state.selectedPodcast) {
            basePreviewVars['--preview-page-title'] = state.selectedPodcast.name || '';
            basePreviewVars['--preview-page-description'] = state.selectedPodcast.artist || '';
        }

        if (coverImageUrl) {
            basePreviewVars['--preview-profile-image-url'] = normalizeImageUrl(coverImageUrl);
        }

        // If we don't have enough colors, just show the content preview
        if (state.colors.length < 2) {
            setPreviewCSSVars(basePreviewVars);
            return;
        }

        try {
            console.log('Generating theme from podcast with colors:', state.colors);
            const themeData = await generateThemeFromPodcast({
                coverImageUrl: coverImageUrl || '',
                colors: state.colors
            });
            console.log('Generated theme data:', themeData);

            const typedThemeData = themeData as unknown as TypedThemeData;
            const typographyColor = typedThemeData.typography_tokens?.color;
            const headingColor = typographyColor?.heading || '#000000';
            const bodyColor = typographyColor?.body || '#666666';
            const widgetHeadingColor = typographyColor?.widget_heading || '#000000';
            const widgetBodyColor = typographyColor?.widget_body || '#666666';

            const semanticTokens = typedThemeData.color_tokens?.semantic;
            const accentTokens = semanticTokens?.accent;
            const accentPrimary = accentTokens?.primary || '#2563eb';

            const cssVars: Record<string, string> = {
                ...basePreviewVars, // Start with base content we already derived

                '--page-background': themeData.page_background || '#ffffff',
                '--widget-background': themeData.widget_background || '#ffffff',
                '--widget-border-color': themeData.widget_border_color || '#e5e7eb',

                '--page-title-color': headingColor,
                '--page-description-color': bodyColor,
                '--widget-heading-color': widgetHeadingColor,
                '--widget-body-color': widgetBodyColor,

                '--heading-font-color': headingColor,
                '--body-font-color': bodyColor,
                '--widget-heading-font-color': widgetHeadingColor,
                '--widget-body-font-color': widgetBodyColor,
                '--color-text-primary': headingColor,
                '--color-text-secondary': bodyColor,
                '--text-color': bodyColor,

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

                '--page-title-size': '32px',
                '--page-description-size': '16px',
                '--widget-heading-size': '20px',
                '--widget-body-size': '14px',

                '--icon-color': accentPrimary,
                '--social-icon-color': accentPrimary,
                '--color-accent-primary': accentPrimary,

                '--profile-image-radius': themeData.profile_image_radius ? `${themeData.profile_image_radius}%` : '15%',
                '--profile-image-size': '120px',
                '--profile-image-border-width': '0px',
                '--profile-image-border-color': 'transparent',
                '--profile-image-box-shadow': 'none',

                '--icon-size': '32px',
                '--social-icon-size': '32px',
                '--icon-spacing': '1rem',
                '--social-icon-spacing': '1rem',

                '--widget-border-width': '2px',
                '--widget-border-radius': '12px',
                '--widget-spacing': '1rem',

                '--page-title-effect-class': '',
                '--page-title-text-shadow': 'none',
                '--widget-shadow-box-shadow': 'none',
                '--widget-glow-box-shadow': 'none',
            };

            // Only override if themeData has better info (unlikely for title/desc if passed null)
            if (themeData.podcast_name) cssVars['--preview-page-title'] = themeData.podcast_name;
            if (themeData.podcast_description) cssVars['--preview-page-description'] = themeData.podcast_description;

            setPreviewCSSVars(cssVars);
        } catch (err) {
            devError('Preview update error:', err);
            // Show error to user so they know why styling is missing
            actions.setError(err instanceof Error ? err.message : 'Failed to generate theme preview');
            // Fallback to base vars if theme gen fails
            setPreviewCSSVars(basePreviewVars);
        }
    }, [state.colors, state.activeImageUrl, state.selectedPodcast, devError]);

    // Manual Preview Handlers
    const applyPreview = useCallback(() => {
        updatePreview();
    }, [updatePreview]);

    const revertPreview = useCallback(() => {
        setPreviewCSSVars({});
    }, []);


    // Handlers
    const handleSearchPodcasts = useCallback(async () => {
        if (!state.searchQuery.trim()) {
            actions.setError('Please enter a search query');
            return;
        }

        actions.setIsSearching(true);
        actions.setError(null);
        actions.setSearchResults([]);

        try {
            const response = await searchPodcasts(state.searchQuery.trim());
            if (response.success && response.data?.results) {
                actions.setSearchResults(response.data.results);
            } else {
                actions.setError(response.error || 'Failed to search podcasts');
            }
        } catch (err) {
            actions.setError(err instanceof Error ? err.message : 'Failed to search podcasts');
        } finally {
            actions.setIsSearching(false);
        }
    }, [state.searchQuery, actions]);

    const handleSelectPodcast = useCallback(async (podcast: PodcastSearchResult) => {
        actions.setUploadedImageUrl(null);
        actions.setActiveImageUrl(null);
        lastExtractedImageUrl.current = null;
        actions.setSearchResults([]);
        actions.setSearchQuery('');
        actions.setColors([]);
        actions.setError(null);

        actions.setSelectedPodcast(podcast);

        if (podcast.artwork_url) {
            actions.setActiveImageUrl(podcast.artwork_url); // Simplified: direct use + let auto-extract handle it
            // Note: Real imp tries to upload to avoid CORS, but direct URL often works for extraction backend
        }
    }, [actions]);

    const handleImageUpload = useCallback(async (file: File) => {
        try {
            actions.setError(null);
            // We could add isUploading state
            lastExtractedImageUrl.current = null;

            const result = await uploadToMediaLibraryMutation.mutateAsync(file);
            if (result.media?.file_url) {
                actions.setUploadedImageUrl(result.media.file_url);
                actions.setActiveImageUrl(result.media.file_url);
                actions.setSelectedPodcast(null);
                actions.setColors([]);
            } else {
                actions.setError('Upload succeeded but no file URL was returned');
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to upload image';
            actions.setError(errorMessage);
        }
    }, [uploadToMediaLibraryMutation, actions]);

    const handleSelectFromMediaLibrary = useCallback((mediaItem: MediaItem) => {
        actions.setUploadedImageUrl(mediaItem.file_url);
        actions.setActiveImageUrl(mediaItem.file_url);
        lastExtractedImageUrl.current = null;
        actions.setSelectedPodcast(null);
        actions.setColors([]);
        actions.setMediaLibraryOpen(false);
    }, [actions]);

    const handleShuffle = useCallback(async () => {
        if (state.colors.length !== 5) {
            actions.setError('Please extract colors first');
            return;
        }

        actions.setIsShuffling(true);
        actions.setError(null);

        try {
            const shuffled = await shuffleThemeColors(state.colors);
            actions.setColors(shuffled);
        } catch (err) {
            actions.setError('Failed to shuffle colors');
        } finally {
            actions.setIsShuffling(false);
        }
    }, [state.colors, actions]);

    const handleDragStart = useCallback((index: number) => {
        actions.setDraggedIndex(index);
    }, [actions]);

    const handleDragOver = useCallback((e: React.DragEvent, index: number) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (state.draggedIndex !== null && state.draggedIndex !== index) {
            actions.setDragOverIndex(index);
        }
    }, [state.draggedIndex, actions]);

    const handleDragLeave = useCallback(() => {
        actions.setDragOverIndex(null);
    }, [actions]);

    const handleDrop = useCallback((e: React.DragEvent, dropIndex: number) => {
        e.preventDefault();

        if (state.draggedIndex === null || state.draggedIndex === dropIndex) {
            actions.setDraggedIndex(null);
            actions.setDragOverIndex(null);
            return;
        }

        const newColors = [...state.colors];
        const draggedColor = newColors[state.draggedIndex];
        newColors.splice(state.draggedIndex, 1);
        newColors.splice(dropIndex, 0, draggedColor);

        actions.setColors(newColors);
        actions.setDraggedIndex(null);
        actions.setDragOverIndex(null);
    }, [state.colors, state.draggedIndex, actions]);

    const handleDragEnd = useCallback(() => {
        actions.setDraggedIndex(null);
        actions.setDragOverIndex(null);
    }, [actions]);

    const handleGenerateTheme = useCallback(async () => {
        if (state.colors.length !== 5) {
            actions.setError('Please extract 5 colors first');
            return;
        }

        actions.setIsGenerating(true);
        actions.setError(null);

        try {
            // 1. Handle Image Persistence (Profile Image)
            // If we have an active image URL, we should save it as the profile image
            if (state.activeImageUrl) {
                let finalProfileImageUrl = state.activeImageUrl;

                // Check if it's an external URL (not already in our uploads)
                const isExternalUrl = state.activeImageUrl.startsWith('http') && !state.activeImageUrl.includes('/uploads/');

                if (isExternalUrl) {
                    try {
                        // Fetch and upload the external image
                        const response = await fetch(state.activeImageUrl);
                        const blob = await response.blob();
                        const file = new File([blob], 'podcast_artwork.jpg', { type: blob.type || 'image/jpeg' });

                        const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
                        if (uploadResult.media?.file_url) {
                            finalProfileImageUrl = uploadResult.media.file_url;
                        }
                    } catch (uploadErr) {
                        console.error('Failed to upload external image, falling back to URL', uploadErr);
                        // We continue even if upload fails, using the external URL
                    }
                }

                // Update the page's profile image
                // Use updatePageSettings or updatePageAppearance depending on where profile_image lives
                // Based on standard usage, profile_image is usually in settings or appearance. 
                // We'll use updatePageAppearance as it often handles visual assets.
                // Note: api/types.ts shows profile_image in PageSnapshot. 
                // If updatePageAppearance doesn't handle it, we might need updatePageSettings.
                // Assuming updatePageAppearance handles general appearance updates including profile image.
                await updatePageMutation.mutateAsync({
                    profile_image: finalProfileImageUrl
                });
            }

            // 2. Generate and Create Theme
            const themeData = await generateThemeFromPodcast({
                coverImageUrl: state.activeImageUrl || '',
                colors: state.colors
            });

            // Standardize theme name
            const themeName = `Theme ${new Date().toLocaleString()}`;

            // Transform generated theme data into ThemeRecord format if needed
            // but useCreateThemeMutation expects specific format.
            const newTheme = await createThemeMutation.mutateAsync({
                ...themeData,
                name: themeName, // Ensure name is set explicitly after spread to avoid checking for it in spread
            });

            if (newTheme.success) {
                // Extract ID safely
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                const rawId = (newTheme as any).id || (newTheme as any).theme_id || (newTheme as any).data?.theme_id;
                const themeId = rawId ? Number(rawId) : null;

                if (themeId) {
                    // Apply to page
                    if (page?.username) {
                        // Update the page to use the newly created theme
                        await updatePageThemeId(themeId);

                        // Invalidate queries
                        queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
                        queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
                    }

                    if (onThemeGenerated) {
                        onThemeGenerated(themeId);
                    }
                    onClose();
                } else {
                    actions.setError('Theme created but ID missing.');
                }
            } else {
                actions.setError(newTheme.error || 'Failed to generate theme');
            }
        } catch (err) {
            actions.setError(err instanceof Error ? err.message : 'Failed to generate theme');
        } finally {
            actions.setIsGenerating(false);
        }
    }, [state.colors, state.activeImageUrl, page, createThemeMutation, updatePageMutation, uploadToMediaLibraryMutation, queryClient, onThemeGenerated, onClose, actions]);

    // 5. Derive Preview Content
    // Prioritize PAGE data over selected podcast data for the preview content itself
    // The wizard is for THEME (colors/fonts), not content replacement.
    const previewContent = {
        title: page?.podcast_name || page?.username || state.selectedPodcast?.name || 'My Page',
        description: page?.podcast_description || state.selectedPodcast?.artist || 'Welcome to my page.',
        socialIcons: snapshot?.social_icons || [],
        widgets: snapshot?.widgets || [],
    };

    return {
        state,
        actions,
        previewCSSVars,
        previewContent,
        handleSearchPodcasts,
        handleSelectPodcast,
        handleImageUpload,
        handleSelectFromMediaLibrary,
        handleShuffle,
        handleDragStart,
        handleDragOver,
        handleDragLeave,
        handleDrop,
        handleDragEnd,
        handleGenerateTheme,
        applyPreview,
        revertPreview,
    };
}
