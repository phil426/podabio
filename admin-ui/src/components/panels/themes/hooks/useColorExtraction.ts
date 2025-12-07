import { useCallback, useEffect, useState } from 'react';
import type { MutableRefObject } from 'react';
import { extractColorsFromImage } from '../../../../api/podcastTheme';
import { normalizeImageUrl } from '../../../../api/utils';
import { trackTelemetry } from '../../../../services/telemetry';

interface ColorCacheEntry {
  colors: string[];
  timestamp: number;
}

interface UseColorExtractionParams {
  activeImageUrl: string | null;
  coverImageUrl: string | null;
  colors: string[];
  isUploadingArtwork: boolean;
  lastExtractedImageUrl: MutableRefObject<string | null>;
  abortControllerRef: MutableRefObject<AbortController | null>;
  setColors: (colors: string[]) => void;
  setError: (message: string | null) => void;
  setSuccessMessage: (message: string | null) => void;
  devLog: (message: string, ...args: unknown[]) => void;
  devError: (message: string, ...args: unknown[]) => void;
  defaultColors: readonly string[];
  extractionDelayMs: number;
}

// Per-image color cache (persists across component remounts)
const colorCache = new Map<string, ColorCacheEntry>();
const CACHE_TTL_MS = 24 * 60 * 60 * 1000; // 24 hours
const EXTRACTION_TIMEOUT_MS = 30000; // 30 seconds

interface UseColorExtractionResult {
  isExtracting: boolean;
  handleExtractColors: () => Promise<void>;
}

export function useColorExtraction({
  activeImageUrl,
  coverImageUrl,
  colors,
  isUploadingArtwork,
  lastExtractedImageUrl,
  abortControllerRef,
  setColors,
  setError,
  setSuccessMessage,
  devLog,
  devError,
  defaultColors,
  extractionDelayMs
}: UseColorExtractionParams): UseColorExtractionResult {
  const [isExtracting, setIsExtracting] = useState(false);

  // Auto-extract colors when active image URL changes (only if no colors exist yet and upload is complete)
  useEffect(() => {
    const isDifferentImage = activeImageUrl && activeImageUrl !== lastExtractedImageUrl.current;

    if (
      activeImageUrl &&
      isDifferentImage &&
      colors.length === 0 &&
      !isExtracting &&
      !isUploadingArtwork
    ) {
      const extract = async () => {
        if (!activeImageUrl) return;

        if (lastExtractedImageUrl.current === activeImageUrl) {
          return;
        }

        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
        }

        const abortController = new AbortController();
        abortControllerRef.current = abortController;

        setIsExtracting(true);
        setError(null);

        try {
          const imageUrlForExtraction = normalizeImageUrl(activeImageUrl);
          devLog('Extracting colors from image URL:', imageUrlForExtraction);

          await new Promise((resolve) => setTimeout(resolve, extractionDelayMs));

          if (abortController.signal.aborted) {
            return;
          }

          // Check cache first
          const cacheKey = imageUrlForExtraction;
          const cached = colorCache.get(cacheKey);
          if (cached && Date.now() - cached.timestamp < CACHE_TTL_MS) {
            devLog('Using cached colors for image:', cacheKey);
            setColors(cached.colors.slice(0, 5));
            lastExtractedImageUrl.current = activeImageUrl;
            setError(null);
            setSuccessMessage('Colors loaded from cache!');
            trackTelemetry({
              event: 'theme_wizard.color_extraction',
              metadata: { success: true, colorCount: cached.colors.length, source: 'cache' }
            });
            return;
          }

          // Extract with timeout
          const extractionPromise = extractColorsFromImage(imageUrlForExtraction);
          const timeoutPromise = new Promise<never>((_, reject) => {
            setTimeout(() => reject(new Error('Color extraction timed out after 30 seconds')), EXTRACTION_TIMEOUT_MS);
          });

          const extractedColors = await Promise.race([extractionPromise, timeoutPromise]);
          if (extractedColors.length < 5) {
            setError('Failed to extract 5 colors from image');
            return;
          }

          const isDefaultColors =
            JSON.stringify(extractedColors.slice(0, 5).sort()) ===
            JSON.stringify([...defaultColors].sort());

          if (isDefaultColors) {
            throw new Error(
              'Color extraction failed - received default colors. The image may not be accessible.'
            );
          }

          devLog('Extracted colors:', extractedColors);
          const extractedColorsArray = extractedColors.slice(0, 5);
          
          // Cache the extracted colors
          colorCache.set(cacheKey, {
            colors: extractedColorsArray,
            timestamp: Date.now()
          });
          
          setColors(extractedColorsArray);
          lastExtractedImageUrl.current = activeImageUrl;
          setError(null);
          setSuccessMessage('Colors extracted successfully!');
          trackTelemetry({
            event: 'theme_wizard.color_extraction',
            metadata: { success: true, colorCount: extractedColorsArray.length, source: 'auto' }
          });
        } catch (err) {
          if (abortController.signal.aborted) {
            devLog('Color extraction cancelled');
            return;
          }

          let errorMessage: string;
          if (err instanceof Error) {
            const errMsg = err.message;
            if (errMsg.includes('CORS') || errMsg.includes('cors')) {
              errorMessage =
                "Image can't be accessed due to CORS restrictions. Please upload the image directly instead.";
            } else if (errMsg.includes('default colors') || errMsg.includes('not be accessible')) {
              errorMessage =
                "Couldn't extract colors from this image. The image may not be accessible. Try uploading the image directly or use a different image.";
            } else if (errMsg.includes('Failed to extract')) {
              errorMessage =
                "Couldn't extract colors from this image. Try a different image or upload directly.";
            } else {
              errorMessage = errMsg;
            }
          } else {
            errorMessage = "Couldn't extract colors. Try a different image or upload directly.";
          }

          setError(errorMessage);
          devError('Color extraction error:', err);
          trackTelemetry({
            event: 'theme_wizard.color_extraction',
            metadata: { success: false, error: errorMessage }
          });
        } finally {
          if (!abortController.signal.aborted) {
            setIsExtracting(false);
          }
        }
      };

      const timer = setTimeout(() => {
        extract();
      }, extractionDelayMs);

      return () => clearTimeout(timer);
    }
  }, [
    abortControllerRef,
    activeImageUrl,
    colors.length,
    defaultColors,
    devError,
    devLog,
    extractionDelayMs,
    isExtracting,
    isUploadingArtwork,
    lastExtractedImageUrl,
    setColors,
    setError,
    setSuccessMessage
  ]);

  // Manual extraction handler
  const handleExtractColors = useCallback(async () => {
    if (colors.length >= 5) {
      setError('Colors already extracted. Use shuffle to rearrange them.');
      return;
    }

    if (!coverImageUrl) {
      setError('No cover image selected. Please select an image first.');
      return;
    }

    setIsExtracting(true);
    setError(null);
    setSuccessMessage(null);

    try {
      // Check cache first
      const cacheKey = coverImageUrl;
      const cached = colorCache.get(cacheKey);
      if (cached && Date.now() - cached.timestamp < CACHE_TTL_MS) {
        devLog('Using cached colors for manual extraction:', cacheKey);
        const cachedColorsArray = cached.colors.slice(0, 5);
        setColors(cachedColorsArray);
        setSuccessMessage('Colors loaded from cache!');
        lastExtractedImageUrl.current = coverImageUrl;
        trackTelemetry({
          event: 'theme_wizard.color_extraction',
          metadata: { success: true, colorCount: cachedColorsArray.length, source: 'cache' }
        });
        return;
      }

      // Extract with timeout
      const extractionPromise = extractColorsFromImage(coverImageUrl);
      const timeoutPromise = new Promise<never>((_, reject) => {
        setTimeout(() => reject(new Error('Color extraction timed out after 30 seconds')), EXTRACTION_TIMEOUT_MS);
      });

      const extractedColors = await Promise.race([extractionPromise, timeoutPromise]);
      if (extractedColors.length < 5) {
        setError("Couldn't extract 5 colors from this image. Try a different image or upload directly.");
        trackTelemetry({
          event: 'theme_wizard.color_extraction',
          metadata: { success: false, error: 'Insufficient colors', colorCount: extractedColors.length }
        });
        return;
      }
      const extractedColorsArray = extractedColors.slice(0, 5);
      
      // Cache the extracted colors
      colorCache.set(cacheKey, {
        colors: extractedColorsArray,
        timestamp: Date.now()
      });
      
      setColors(extractedColorsArray);
      setSuccessMessage('Colors extracted successfully!');
      lastExtractedImageUrl.current = coverImageUrl;
      trackTelemetry({
        event: 'theme_wizard.color_extraction',
        metadata: { success: true, colorCount: extractedColorsArray.length, source: 'manual' }
      });
    } catch (err) {
      let errorMessage: string;
      if (err instanceof Error) {
        const errMsg = err.message;
        if (errMsg.includes('CORS') || errMsg.includes('cors')) {
          errorMessage =
            "Image can't be accessed due to CORS restrictions. Please upload the image directly instead.";
        } else if (errMsg.includes('default colors') || errMsg.includes('not be accessible')) {
          errorMessage =
            "Couldn't extract colors from this image. The image may not be accessible. Try uploading the image directly or use a different image.";
        } else {
          errorMessage = `Couldn't extract colors: ${errMsg}. Try a different image or upload directly.`;
        }
      } else {
        errorMessage = "Couldn't extract colors. Try a different image or upload directly.";
      }
      setError(errorMessage);
      devError('Color extraction error:', err);
      trackTelemetry({
        event: 'theme_wizard.color_extraction',
        metadata: { success: false, error: errorMessage, source: 'manual' }
      });
    } finally {
      setIsExtracting(false);
    }
  }, [colors.length, coverImageUrl, devError, setColors, setError, setSuccessMessage]);

  return { isExtracting, handleExtractColors };
}

