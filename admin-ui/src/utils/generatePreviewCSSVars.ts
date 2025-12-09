/**
 * Generate Preview CSS Variables
 * Utility function to generate CSS variables for theme preview
 * Extracted from PodcastThemeGenerator to improve maintainability and testability
 */

import { normalizeImageUrl } from '../api/utils';
import type { GeneratedThemeData } from '../api/podcastTheme';

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

interface TypedThemeData extends Omit<GeneratedThemeData, 'typography_tokens' | 'color_tokens'> {
  typography_tokens: TypographyTokens;
  color_tokens: ColorTokens;
}

/**
 * Generates CSS variables object from theme data for preview
 * @param themeData - The generated theme data
 * @param coverImageUrl - Optional cover image URL for profile image
 * @returns Record of CSS variable names to values
 */
export function generatePreviewCSSVars(
  themeData: GeneratedThemeData,
  coverImageUrl: string | null = null
): Record<string, string> {
  // Extract color values with proper type checking
  const typedThemeData = themeData as unknown as TypedThemeData;
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
  // The coverImageUrl parameter is the image used for color extraction (cover_image field)
  if (coverImageUrl) {
    cssVars['--preview-profile-image-url'] = normalizeImageUrl(coverImageUrl);
  }

  return cssVars;
}

