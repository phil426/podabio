/**
 * Build Page Updates from Theme Data
 * Utility function to build page update payload from generated theme data
 * Extracted from PodcastThemeGenerator to improve maintainability and testability
 */

import type { GeneratedThemeData } from '../api/podcastTheme';

/**
 * Builds a page updates object from theme data
 * Only includes fields that are defined in the theme data
 * @param themeData - The generated theme data
 * @returns Record of page update fields to values
 */
export function buildPageUpdates(themeData: GeneratedThemeData): Record<string, string | number | null> {
  const pageUpdates: Record<string, string | number | null> = {};

  // Backgrounds
  if (themeData.page_background) {
    pageUpdates.page_background = themeData.page_background;
  }
  if (themeData.widget_background) {
    pageUpdates.widget_background = themeData.widget_background;
  }
  if (themeData.widget_border_color) {
    pageUpdates.widget_border_color = themeData.widget_border_color;
  }

  // Typography fonts
  if (themeData.page_primary_font) {
    pageUpdates.page_primary_font = themeData.page_primary_font;
  }
  if (themeData.page_secondary_font) {
    pageUpdates.page_secondary_font = themeData.page_secondary_font;
  }
  if (themeData.widget_primary_font) {
    pageUpdates.widget_primary_font = themeData.widget_primary_font;
  }
  if (themeData.widget_secondary_font) {
    pageUpdates.widget_secondary_font = themeData.widget_secondary_font;
  }

  // Widget styles
  if (themeData.widget_styles) {
    // Convert widget_styles to JSON string if it's an object
    if (typeof themeData.widget_styles === 'object' && themeData.widget_styles !== null) {
      pageUpdates.widget_styles = JSON.stringify(themeData.widget_styles);
    } else {
      pageUpdates.widget_styles = themeData.widget_styles as string;
    }
  }

  // Page name styling
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

  return pageUpdates;
}

/**
 * Converts page updates object to payload format for API
 * @param pageUpdates - The page updates object
 * @returns Payload format (Record<string, FormDataEntryValue | undefined>)
 */
export function convertPageUpdatesToPayload(
  pageUpdates: Record<string, string | number | null>
): Record<string, FormDataEntryValue | undefined> {
  const payload: Record<string, FormDataEntryValue | undefined> = {};
  for (const [key, value] of Object.entries(pageUpdates)) {
    payload[key] = value !== null && value !== undefined ? String(value) : undefined;
  }
  return payload;
}


