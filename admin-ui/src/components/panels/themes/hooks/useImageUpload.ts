import { useCallback, useEffect, useState } from 'react';
import type { MutableRefObject } from 'react';
import type { PodcastSearchResult } from '../../../../api/page';
import type { MediaItem } from '../../../../api/media';
import { trackTelemetry } from '../../../../services/telemetry';
import { validateImageFile, validateImageDimensions } from '../../../../utils/themeWizardValidation';

interface UploadMutation {
  mutateAsync: (file: File) => Promise<{ media?: { file_url?: string } }>;
}

interface UseImageUploadParams {
  initialCoverImageUrl: string | null;
  uploadToMediaLibraryMutation: UploadMutation;
  setActiveImageUrl: (url: string | null) => void;
  setUploadedImageUrl: (url: string | null) => void;
  setSelectedPodcast: (podcast: PodcastSearchResult | null) => void;
  setColors: (colors: string[]) => void;
  setError: (message: string | null) => void;
  setMediaLibraryOpen: (open: boolean) => void;
  lastExtractedImageUrl: MutableRefObject<string | null>;
  setSearchResults: (results: PodcastSearchResult[]) => void;
  setSearchQuery: (value: string) => void;
  devLog: (message: string, ...args: unknown[]) => void;
  devError: (message: string, ...args: unknown[]) => void;
}

interface UseImageUploadResult {
  isUploadingArtwork: boolean;
  handleSelectPodcast: (podcast: PodcastSearchResult) => Promise<void>;
  handleImageUpload: (file: File) => Promise<void>;
  handleSelectFromMediaLibrary: (mediaItem: MediaItem) => void;
}

export function useImageUpload({
  initialCoverImageUrl,
  uploadToMediaLibraryMutation,
  setActiveImageUrl,
  setUploadedImageUrl,
  setSelectedPodcast,
  setColors,
  setError,
  setMediaLibraryOpen,
  lastExtractedImageUrl,
  setSearchResults,
  setSearchQuery,
  devLog,
  devError
}: UseImageUploadParams): UseImageUploadResult {
  const [isUploadingArtwork, setIsUploadingArtwork] = useState(false);

  const handleSelectPodcast = useCallback(
    async (podcast: PodcastSearchResult) => {
      setUploadedImageUrl(null);
      setActiveImageUrl(null);
      lastExtractedImageUrl.current = null;
      setColors([]);
      setSearchResults([]);
      setSearchQuery('');
      setError(null);
      setIsUploadingArtwork(true);

      if (podcast.artwork_url) {
        try {
          let response: Response;
          try {
            response = await fetch(podcast.artwork_url, {
              mode: 'cors',
              credentials: 'omit'
            });
          } catch (corsError) {
            if (process.env.NODE_ENV === 'development') {
              console.warn('Direct fetch failed (CORS):', corsError);
            }
            throw new Error(
              "Image can't be accessed due to CORS restrictions. Please try a different podcast or upload an image directly."
            );
          }

          if (!response.ok) {
            throw new Error(`Failed to fetch artwork: ${response.status} ${response.statusText}`);
          }

          const blob = await response.blob();
          const file = new File([blob], `${podcast.name.replace(/[^a-z0-9]/gi, '_')}_cover.jpg`, {
            type: blob.type || 'image/jpeg'
          });

          const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
          if (uploadResult.media?.file_url) {
            const uploadedUrl = uploadResult.media.file_url;
            setActiveImageUrl(uploadedUrl);
            setSelectedPodcast({ ...podcast, artwork_url: uploadedUrl });
            trackTelemetry({
              event: 'theme_wizard.image_uploaded',
              metadata: { source: 'rss', success: true }
            });
          } else {
            throw new Error('Upload succeeded but no file URL was returned');
          }
        } catch (err) {
          devError('Failed to upload artwork to media library:', err);
          const errorMessage = err instanceof Error ? err.message : 'Failed to upload artwork to media library.';
          setError(errorMessage);
          trackTelemetry({
            event: 'theme_wizard.image_uploaded',
            metadata: { source: 'rss', success: false, error: errorMessage }
          });
        } finally {
          setIsUploadingArtwork(false);
        }
      } else {
        setSelectedPodcast(podcast);
        setIsUploadingArtwork(false);
      }
    },
    [
      devError,
      lastExtractedImageUrl,
      setActiveImageUrl,
      setColors,
      setError,
      setSearchQuery,
      setSearchResults,
      setSelectedPodcast,
      setUploadedImageUrl,
      uploadToMediaLibraryMutation
    ]
  );

  const handleImageUpload = useCallback(
    async (file: File) => {
      try {
        setError(null);
        
        // Validate file type and size
        const fileValidation = validateImageFile(file);
        if (!fileValidation.valid) {
          setError(fileValidation.error || 'Invalid file');
          trackTelemetry({
            event: 'theme_wizard.image_uploaded',
            metadata: { source: 'photo', success: false, error: fileValidation.error }
          });
          return;
        }

        // Validate image dimensions
        setIsUploadingArtwork(true);
        const dimensionValidation = await validateImageDimensions(file);
        if (!dimensionValidation.valid) {
          setIsUploadingArtwork(false);
          setError(dimensionValidation.error || 'Invalid image dimensions');
          trackTelemetry({
            event: 'theme_wizard.image_uploaded',
            metadata: { source: 'photo', success: false, error: dimensionValidation.error }
          });
          return;
        }

        lastExtractedImageUrl.current = null;
        const result = await uploadToMediaLibraryMutation.mutateAsync(file);
        if (result.media?.file_url) {
          setUploadedImageUrl(result.media.file_url);
          setActiveImageUrl(result.media.file_url);
          setSelectedPodcast(null);
          setColors([]);
          trackTelemetry({
            event: 'theme_wizard.image_uploaded',
            metadata: { source: 'photo', success: true }
          });
        } else {
          setError('Upload succeeded but no file URL was returned');
        }
      } catch (err) {
        devError('Image upload error:', err);
        const errorMessage = err instanceof Error ? err.message : 'Failed to upload image';
        setError(errorMessage);
        trackTelemetry({
          event: 'theme_wizard.image_uploaded',
          metadata: { source: 'photo', success: false, error: errorMessage }
        });
      } finally {
        setIsUploadingArtwork(false);
      }
    },
    [devError, lastExtractedImageUrl, setActiveImageUrl, setColors, setError, setSelectedPodcast, setUploadedImageUrl, uploadToMediaLibraryMutation]
  );

  const handleSelectFromMediaLibrary = useCallback(
    (mediaItem: MediaItem) => {
      setUploadedImageUrl(mediaItem.file_url);
      setActiveImageUrl(mediaItem.file_url);
      lastExtractedImageUrl.current = null;
      setSelectedPodcast(null);
      setColors([]);
      setMediaLibraryOpen(false);
      trackTelemetry({
        event: 'theme_wizard.image_uploaded',
        metadata: { source: 'media_library', success: true }
      });
    },
    [lastExtractedImageUrl, setActiveImageUrl, setColors, setMediaLibraryOpen, setSelectedPodcast, setUploadedImageUrl]
  );

  // On mount: If initial cover image exists, check if it's already in media library or upload it
  useEffect(() => {
    if (!initialCoverImageUrl) {
      return;
    }

    let isCancelled = false;

    const setupInitialImage = async () => {
      try {
        setIsUploadingArtwork(true);
        setError(null);

        const isMediaLibraryUrl =
          initialCoverImageUrl.includes('/uploads/media/') ||
          initialCoverImageUrl.includes('/uploads/') ||
          (initialCoverImageUrl.startsWith('/') && !initialCoverImageUrl.startsWith('//'));

        if (isMediaLibraryUrl) {
          if (isCancelled) return;
          lastExtractedImageUrl.current = null;
          setActiveImageUrl(initialCoverImageUrl);
          return;
        }

        let response: Response;
        try {
          response = await fetch(initialCoverImageUrl, {
            mode: 'cors',
            credentials: 'omit'
          });

          if (!response.ok) {
            throw new Error(`Failed to fetch initial image: ${response.status} ${response.statusText}`);
          }

          const blob = await response.blob();
          const file = new File([blob], 'rss_cover.jpg', { type: blob.type || 'image/jpeg' });

          const uploadResult = await uploadToMediaLibraryMutation.mutateAsync(file);
          if (uploadResult.media?.file_url) {
            if (isCancelled) return;
            lastExtractedImageUrl.current = null;
            setActiveImageUrl(uploadResult.media.file_url);
          } else {
            throw new Error('Upload succeeded but no file URL was returned');
          }
        } catch (uploadError) {
          if (process.env.NODE_ENV === 'development') {
            console.warn('Failed to upload initial image, but will try to use original URL:', uploadError);
          }

          if (
            initialCoverImageUrl.includes('poda.bio') ||
            initialCoverImageUrl.startsWith('/') ||
            initialCoverImageUrl.startsWith('http://localhost') ||
            initialCoverImageUrl.startsWith('https://localhost')
          ) {
            if (isCancelled) return;
            devLog('Using initial image URL directly (local/poda.bio URL):', initialCoverImageUrl);
            lastExtractedImageUrl.current = null;
            setActiveImageUrl(initialCoverImageUrl);
            setError(null);
          } else {
            if (isCancelled) return;
            devLog('Using external image URL directly, will attempt color extraction');
            lastExtractedImageUrl.current = null;
            setActiveImageUrl(initialCoverImageUrl);
            setError(null);
          }
        }
      } catch (err) {
        devError('Unexpected error setting up initial image:', err);
        if (isCancelled) return;
        lastExtractedImageUrl.current = null;
        setActiveImageUrl(initialCoverImageUrl);
        setError('Warning: Could not upload image, but will attempt to extract colors from original URL.');
      } finally {
        if (!isCancelled) {
          setIsUploadingArtwork(false);
        }
      }
    };

    setupInitialImage();

    return () => {
      isCancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return {
    isUploadingArtwork,
    handleSelectPodcast,
    handleImageUpload,
    handleSelectFromMediaLibrary
  };
}

