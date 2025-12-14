import { useState, useMemo, useCallback } from 'react';
import { SHAPE_PRESETS, COLOR_PRESETS, TYPOGRAPHY_PRESETS, SPACING_PRESETS } from '../design-system/presets/defaults';
import type { EasyModeConfig } from '../design-system/presets/types';
import { lightenColor, darkenColor, optimizeColorPalette, hexToRgb, getLuminance } from '../utils/color-extraction';
import { extractColorsFromImage, generateThemeFromPodcast } from '../api/podcastTheme';
import { normalizeImageUrl } from '../api/utils';

interface UseEasyModeProps {
    uiState: Record<string, unknown>;
    onFieldChange: (fieldId: string, value: unknown) => void;
    profileImageUrl?: string | null;
}

export type EasyModeType = 'EASY' | 'ADVANCED';

interface UseEasyModeReturn {
    mode: EasyModeType;
    setMode: (mode: EasyModeType) => void;
    activePresets: EasyModeConfig;
    applyShapePreset: (presetId: string) => void;
    applyColorPreset: (presetId: string) => void;
    applyTypographyPreset: (presetId: string) => void;
    applySpacingPreset: (presetId: string) => void;
    applyAutoPreset: () => Promise<void>;
    isAutoGenerating: boolean;
    presets: {
        shapes: typeof SHAPE_PRESETS;
        colors: typeof COLOR_PRESETS;
        typography: typeof TYPOGRAPHY_PRESETS;
        spacing: typeof SPACING_PRESETS;
    };
}

export function useEasyMode({ uiState, onFieldChange, profileImageUrl }: UseEasyModeProps): UseEasyModeReturn {
    const [mode, setMode] = useState<EasyModeType>('EASY');
    const [isAutoGenerating, setIsAutoGenerating] = useState(false);

    // Detect active presets based on current uiState
    const activePresets = useMemo(() => {
        const config: EasyModeConfig = {};

        // Check Shape
        const matchedShape = SHAPE_PRESETS.find(preset => {
            return Object.entries(preset.tokens).every(([key, value]) => {
                // Loose equality check might be safer for numbers/strings in forms
                // eslint-disable-next-line eqeqeq
                return uiState[key] == value;
            });
        });
        if (matchedShape) config.activeShapeId = matchedShape.id;

        // Check Color
        const matchedColor = COLOR_PRESETS.find(preset => {
            return Object.entries(preset.tokens).every(([key, value]) => {
                // eslint-disable-next-line eqeqeq
                return uiState[key] == value;
            });
        });
        if (matchedColor) config.activeColorId = matchedColor.id;

        // Check Typography
        const matchedTypography = TYPOGRAPHY_PRESETS.find(preset => {
            return Object.entries(preset.tokens).every(([key, value]) => {
                // eslint-disable-next-line eqeqeq
                return uiState[key] == value;
            });
        });
        if (matchedTypography) config.activeTypographyId = matchedTypography.id;

        // Check Spacing
        const matchedSpacing = SPACING_PRESETS.find(preset => {
            return Object.entries(preset.tokens).every(([key, value]) => {
                // eslint-disable-next-line eqeqeq
                return uiState[key] == value;
            });
        });
        if (matchedSpacing) config.activeSpacingId = matchedSpacing.id;

        return config;
    }, [uiState]);

    const applyShapePreset = useCallback((presetId: string) => {
        const preset = SHAPE_PRESETS.find(p => p.id === presetId);
        if (!preset) return;

        Object.entries(preset.tokens).forEach(([key, value]) => {
            onFieldChange(key, value);
        });
    }, [onFieldChange]);

    const applyColorPreset = useCallback((presetId: string) => {
        const preset = COLOR_PRESETS.find(p => p.id === presetId);
        if (!preset) return;

        Object.entries(preset.tokens).forEach(([key, value]) => {
            onFieldChange(key, value);
        });
    }, [onFieldChange]);

    const applyTypographyPreset = useCallback((presetId: string) => {
        const preset = TYPOGRAPHY_PRESETS.find(p => p.id === presetId);
        if (!preset) return;

        Object.entries(preset.tokens).forEach(([key, value]) => {
            onFieldChange(key, value);
        });
    }, [onFieldChange]);

    const applySpacingPreset = useCallback((presetId: string) => {
        const preset = SPACING_PRESETS.find(p => p.id === presetId);
        if (!preset) return;

        Object.entries(preset.tokens).forEach(([key, value]) => {
            onFieldChange(key, value);
        });
    }, [onFieldChange]);


    const applyAutoPreset = useCallback(async () => {
        if (!profileImageUrl) {
            console.warn('No profile image available for auto vibe');
            return;
        }

        setIsAutoGenerating(true);
        try {
            // Normalize URL (handles relative paths, prod urls, etc.) same as Theme Wizard
            const normalizedUrl = normalizeImageUrl(profileImageUrl);
            console.log('AutoVibe: Extracting from', normalizedUrl);

            // Use the existing robust storage-aware extraction API
            // This handles CORS, backend proxying, and multiple formats automatically
            const colors = await extractColorsFromImage(normalizedUrl);
            console.log('AutoVibe: Extracted raw colors', colors);

            if (colors && colors.length > 0) {
                // Optimize the palette! (User requested distinct/contrast/vibrant)
                const optimizedColors = optimizeColorPalette(colors);
                console.log('AutoVibe: Optimized colors', optimizedColors);

                // Call the Full Theme Generator to get professional color mappings
                // This matches the "Theme Wizard" logic exactly, but with better input colors
                const themeData = await generateThemeFromPodcast({
                    coverImageUrl: normalizedUrl, // Use normalized URL
                    colors: optimizedColors // Use improved palette
                });

                console.log('AutoVibe: Generated theme data', themeData);

                // Extract colors from the generated theme data
                // The structure matches what PodcastThemeGenerator uses
                // We need to cast it to handle the loose typing of the API response
                const anyData = themeData as any;
                const typographyColor = anyData.typography_tokens?.color;

                // Fallbacks if generator misses something
                const headingColor = typographyColor?.heading || optimizedColors[0]; // Use optimized primary as fallback
                const bodyColor = typographyColor?.body || '#334155';
                const widgetHeadingColor = typographyColor?.widget_heading || headingColor;
                const widgetBodyColor = typographyColor?.widget_body || bodyColor;

                // Force solid background if gradient is returned (User request)
                let pageBackground = themeData.page_background || '#ffffff';
                if (pageBackground.toLowerCase().includes('gradient') && optimizedColors[0]) {
                    // Create a subtle solid tint of the dominant color
                    pageBackground = lightenColor(optimizedColors[0], 0.92);
                }

                // Ensure Widget Contrast
                const widgetBg = (themeData.widget_background || '#ffffff') as string;
                // Helper to check contrast (very simple approx)
                const ensureContrast = (fgHex: string, bgHex: string, minRatio = 4.5): string => {
                    // Simple logic: if bg is light and fg is light, darken fg
                    // We assume widget bg is usually light for now
                    if (bgHex.toLowerCase() === '#ffffff' || bgHex.toLowerCase().includes('255, 255, 255')) {
                        let current = fgHex;
                        let lum = getLuminanceWithHex(current);
                        let steps = 0;
                        while (lum > 0.5 && steps < 5) { // If too light
                            current = darkenColor(current, 0.2); // Darken by 20%
                            lum = getLuminanceWithHex(current);
                            steps++;
                        }
                        return current;
                    }
                    return fgHex;
                };

                // Helper wrapper for hex based luminance
                const getLuminanceWithHex = (hex: string) => {
                    const rgb = hexToRgb(hex);
                    return getLuminance(rgb.r, rgb.g, rgb.b);
                };

                const safeWidgetHeading = ensureContrast(widgetHeadingColor as string, widgetBg);
                const safeWidgetBody = ensureContrast(widgetBodyColor as string, widgetBg);
                // For social icons, use the primary color but ensure high contrast
                const highContrastPrimary = ensureContrast(optimizedColors[0], pageBackground, 4.5);


                const autoTokens = {
                    'page-background': pageBackground,
                    'widget-background': widgetBg,
                    'widget-border-color': themeData.widget_border_color || '#e2e8f0',
                    'widget-heading-color': safeWidgetHeading,
                    'widget-body-color': safeWidgetBody,
                    'page-title-color': headingColor,
                    // Also set page description color if available
                    'page-bio-color': bodyColor,
                    'social-icon-color': highContrastPrimary
                };

                console.log('AutoVibe: Applying rich tokens', autoTokens);
                Object.entries(autoTokens).forEach(([key, value]) => {
                    onFieldChange(key, value);
                });
            } else {
                throw new Error('No colors extracted (empty array)');
            }

        } catch (error) {
            console.error('Failed to apply auto vibe', error);
            // Fallback: If we can't extract, just apply a nice default (Soft/Light-ish)
            const fallbackTokens = {
                'page-background': '#f8fafc',
                'widget-background': '#ffffff',
                'widget-border-color': '#e2e8f0',
                'widget-heading-color': '#0f172a',
                'widget-body-color': '#64748b',
                'page-title-color': '#0f172a',
            };
            Object.entries(fallbackTokens).forEach(([key, value]) => {
                onFieldChange(key, value);
            });
        } finally {
            setIsAutoGenerating(false);
        }
    }, [profileImageUrl, onFieldChange]);

    return {
        mode,
        setMode,
        activePresets,
        applyShapePreset,
        applyColorPreset,
        applyTypographyPreset,
        applySpacingPreset,
        applyAutoPreset,
        isAutoGenerating,
        presets: {
            shapes: SHAPE_PRESETS,
            colors: COLOR_PRESETS,
            typography: TYPOGRAPHY_PRESETS,
            spacing: SPACING_PRESETS
        }
    };
}
