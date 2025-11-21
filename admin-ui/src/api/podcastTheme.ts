/**
 * Podcast Theme API Client
 * Handles color extraction, theme generation, and color shuffling
 */

import { requestJson } from './http';
import type { ApiResponse } from './types';

export interface ColorPalette {
  colors: string[];
}

export interface PodcastThemeData {
  coverImageUrl: string;
  podcastName?: string | null;
  podcastDescription?: string | null;
  colors?: string[];
}

export interface GeneratedThemeData {
  name: string;
  color_tokens: Record<string, unknown>;
  typography_tokens: Record<string, unknown>;
  page_background: string;
  widget_background: string;
  widget_border_color: string;
  page_primary_font: string;
  page_secondary_font: string;
  widget_primary_font: string;
  widget_secondary_font: string;
  widget_styles?: Record<string, unknown>;
  page_name_effect?: string;
  page_name_shadow_color?: string;
  page_name_shadow_intensity?: number;
  page_name_shadow_depth?: number;
  page_name_shadow_blur?: number;
  page_name_border_color?: string;
  page_name_border_width?: number;
  profile_image_radius?: number;
  profile_image_effect?: string;
  profile_image_shadow_color?: string;
  profile_image_shadow_intensity?: number;
  profile_image_shadow_depth?: number;
  profile_image_shadow_blur?: number;
  podcast_name?: string;
  podcast_description?: string;
}

export interface ExtractColorsResponse extends ApiResponse {
  colors: string[];
}

export interface GenerateThemeResponse extends ApiResponse {
  theme_data: GeneratedThemeData;
}

export interface ShuffleColorsResponse extends ApiResponse {
  colors: string[];
}

const PODCAST_THEME_ENDPOINT = '/api/podcast-theme.php';

function formPostInit(data: Record<string, unknown>): RequestInit {
  const formData = new FormData();
  Object.entries(data).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      if (typeof value === 'object') {
        formData.append(key, JSON.stringify(value));
      } else {
        formData.append(key, String(value));
      }
    }
  });
  return {
    method: 'POST',
    body: formData,
  };
}

/**
 * Extract colors from an image URL
 */
export async function extractColorsFromImage(imageUrl: string): Promise<string[]> {
  const response = await requestJson<ExtractColorsResponse>(
    PODCAST_THEME_ENDPOINT,
    formPostInit({
      action: 'extract_colors',
      image_url: imageUrl,
    })
  );
  
  if (!response.success || !response.colors) {
    throw new Error(response.error || 'Failed to extract colors');
  }
  
  return response.colors;
}

/**
 * Generate theme from podcast data and colors
 */
export async function generateThemeFromPodcast(
  data: PodcastThemeData
): Promise<GeneratedThemeData> {
  const payload: Record<string, unknown> = {
    action: 'generate_theme',
    colors: data.colors || [],
  };
  
  if (data.podcastName) {
    payload.podcast_name = data.podcastName;
  }
  
  if (data.podcastDescription) {
    payload.podcast_description = data.podcastDescription;
  }
  
  const response = await requestJson<GenerateThemeResponse>(
    PODCAST_THEME_ENDPOINT,
    formPostInit(payload)
  );
  
  if (!response.success || !response.theme_data) {
    throw new Error(response.error || 'Failed to generate theme');
  }
  
  return response.theme_data;
}

/**
 * Shuffle colors while maintaining contrast ratios
 */
export async function shuffleThemeColors(colors: string[]): Promise<string[]> {
  const response = await requestJson<ShuffleColorsResponse>(
    PODCAST_THEME_ENDPOINT,
    formPostInit({
      action: 'shuffle_colors',
      colors: colors,
    })
  );
  
  if (!response.success || !response.colors) {
    throw new Error(response.error || 'Failed to shuffle colors');
  }
  
  return response.colors;
}

