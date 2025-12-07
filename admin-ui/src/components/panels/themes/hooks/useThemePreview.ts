import { useCallback } from 'react';
import type { MutableRefObject } from 'react';
import { generateThemeFromPodcast, type GeneratedThemeData } from '../../../../api/podcastTheme';
import { generatePreviewCSSVars } from '../../../../utils/generatePreviewCSSVars';

interface UseThemePreviewParams {
  colors: string[];
  coverImageUrl: string | null;
  setPreviewCSSVars: (cssVars: Record<string, string>) => void;
  setProfileImageInPreview: (imageUrl: string | null) => void;
  setIsPreviewLoading?: (loading: boolean) => void;
  cachedThemeData: MutableRefObject<GeneratedThemeData | null>;
  cachedThemeKey: MutableRefObject<string>;
  devLog: (message: string, ...args: unknown[]) => void;
  devError: (message: string, ...args: unknown[]) => void;
}

interface UseThemePreviewResult {
  updatePreview: () => Promise<void>;
}

export function useThemePreview({
  colors,
  coverImageUrl,
  setPreviewCSSVars,
  setProfileImageInPreview,
  setIsPreviewLoading,
  cachedThemeData,
  cachedThemeKey,
  devLog,
  devError
}: UseThemePreviewParams): UseThemePreviewResult {
  const updatePreview = useCallback(async () => {
    if (colors.length < 2) {
      if (coverImageUrl) {
        setProfileImageInPreview(coverImageUrl);
      } else {
        setPreviewCSSVars({});
      }
      if (setIsPreviewLoading) {
        setIsPreviewLoading(false);
      }
      return;
    }

    if (setIsPreviewLoading) {
      setIsPreviewLoading(true);
    }

    try {
      const themeKey = colors.join(',');

      let themeData: GeneratedThemeData;

      if (cachedThemeData.current && cachedThemeKey.current === themeKey) {
        themeData = cachedThemeData.current;
        devLog('Using cached theme data for colors:', colors);
      } else {
        themeData = await generateThemeFromPodcast({
          coverImageUrl: coverImageUrl || '',
          colors
        });

        cachedThemeData.current = themeData;
        cachedThemeKey.current = themeKey;
        devLog('Generated and cached theme data for colors:', colors);
      }

      const cssVars = generatePreviewCSSVars(themeData, coverImageUrl);
      setPreviewCSSVars(cssVars);
    } catch (err) {
      devError('Preview update error:', err);
      setPreviewCSSVars({});
    } finally {
      if (setIsPreviewLoading) {
        setIsPreviewLoading(false);
      }
    }
  }, [
    cachedThemeData,
    cachedThemeKey,
    colors,
    coverImageUrl,
    devError,
    devLog,
    setPreviewCSSVars,
    setProfileImageInPreview,
    setIsPreviewLoading
  ]);

  return { updatePreview };
}

