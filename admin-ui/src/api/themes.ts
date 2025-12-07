import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { requestJson } from './http';
import type { ApiResponse, ThemeLibraryResponse, ThemeRecord } from './types';
import { formPostInit, queryKeys } from './utils';

const THEMES_ENDPOINT = '/api/themes.php';

export interface ThemeLibraryResult {
  system: ThemeRecord[];
  user: ThemeRecord[];
}

export async function fetchThemeLibrary(): Promise<ThemeLibraryResult> {
  const response = await requestJson<ThemeLibraryResponse>(`${THEMES_ENDPOINT}?scope=all`);

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load themes');
  }

  return {
    system: response.system ?? response.themes ?? [],
    user: response.user ?? []
  };
}

export function useThemeLibraryQuery() {
  return useQuery({
    queryKey: queryKeys.themes(),
    queryFn: fetchThemeLibrary,
    staleTime: 5 * 60 * 1000
  });
}

export async function cloneTheme(themeId: number, name?: string) {
  return requestJson<ApiResponse>(THEMES_ENDPOINT, formPostInit({
    action: 'clone',
    theme_id: String(themeId),
    name: name ?? ''
  }));
}

export async function deleteTheme(themeId: number) {
  return requestJson<ApiResponse>(THEMES_ENDPOINT, formPostInit({
    action: 'delete',
    theme_id: String(themeId)
  }));
}

export async function renameTheme(themeId: number, name: string) {
  return requestJson<ApiResponse>(THEMES_ENDPOINT, formPostInit({
    action: 'rename',
    theme_id: String(themeId),
    name
  }));
}

export function useCloneThemeMutation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ themeId, name }: { themeId: number; name?: string }) => cloneTheme(themeId, name),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
    }
  });
}

export function useDeleteThemeMutation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (themeId: number) => deleteTheme(themeId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
    }
  });
}

export function useRenameThemeMutation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ themeId, name }: { themeId: number; name: string }) => renameTheme(themeId, name),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
    }
  });
}

export interface CreateThemeData {
  name: string;
  color_tokens?: Record<string, unknown>;
  typography_tokens?: Record<string, unknown>;
  spacing_tokens?: Record<string, unknown>;
  shape_tokens?: Record<string, unknown>;
  motion_tokens?: Record<string, unknown>;
  iconography_tokens?: Record<string, unknown>;
  page_background?: string;
  widget_background?: string;
  widget_border_color?: string;
  page_primary_font?: string;
  page_secondary_font?: string;
  widget_primary_font?: string;
  widget_secondary_font?: string;
  widget_styles?: Record<string, unknown>;
}

export interface UpdateThemeData {
  themeId: number;
  data: CreateThemeData;
}

export async function createTheme(data: CreateThemeData) {
  return requestJson<ApiResponse>(THEMES_ENDPOINT, formPostInit({
    action: 'create',
    name: data.name,
    theme_data: JSON.stringify({
      color_tokens: data.color_tokens,
      typography_tokens: data.typography_tokens,
      spacing_tokens: data.spacing_tokens,
      shape_tokens: data.shape_tokens,
      motion_tokens: data.motion_tokens,
      iconography_tokens: data.iconography_tokens,
      page_background: data.page_background,
      widget_background: data.widget_background,
      widget_border_color: data.widget_border_color,
      page_primary_font: data.page_primary_font,
      page_secondary_font: data.page_secondary_font,
      widget_primary_font: data.widget_primary_font,
      widget_secondary_font: data.widget_secondary_font,
      widget_styles: data.widget_styles
    })
  }));
}

export async function updateTheme(themeId: number, data: CreateThemeData) {
  return requestJson<ApiResponse>(THEMES_ENDPOINT, formPostInit({
    action: 'update',
    theme_id: String(themeId),
    name: data.name,
    theme_data: JSON.stringify({
      color_tokens: data.color_tokens,
      typography_tokens: data.typography_tokens,
      spacing_tokens: data.spacing_tokens,
      shape_tokens: data.shape_tokens,
      motion_tokens: data.motion_tokens,
      iconography_tokens: data.iconography_tokens,
      page_background: data.page_background,
      widget_background: data.widget_background,
      widget_border_color: data.widget_border_color,
      page_primary_font: data.page_primary_font,
      page_secondary_font: data.page_secondary_font,
      widget_primary_font: data.widget_primary_font,
      widget_secondary_font: data.widget_secondary_font,
      widget_styles: data.widget_styles
    })
  }));
}

export function useCreateThemeMutation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateThemeData) => createTheme(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
    }
  });
}

export function useUpdateThemeMutation() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ themeId, data }: UpdateThemeData) => updateTheme(themeId, data),
    onSuccess: () => {
      // Invalidate and refetch to ensure theme cards update immediately
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      queryClient.refetchQueries({ queryKey: queryKeys.themes() });
    }
  });
}

/**
 * Get or create the user's single theme.
 * Ensures only one user theme exists per user.
 * If no user theme exists, creates one based on the first system theme.
 * @returns Promise resolving to the user theme ID
 */
export async function getOrCreateUserTheme(): Promise<number> {
  // Fetch theme library to get user themes
  const library = await fetchThemeLibrary();
  
  // If user already has themes, use the first one (we'll consolidate to one later)
  if (library.user && library.user.length > 0) {
    // If multiple exist, we'll need to consolidate, but for now return the first
    // The backend will handle ensuring only one exists
    return library.user[0].id;
  }
  
  // No user theme exists - create one based on first system theme
  const firstSystemTheme = library.system?.[0];
  if (!firstSystemTheme) {
    throw new Error('No system themes available to create user theme');
  }
  
  // Create user theme by copying system theme settings
  const themeData: CreateThemeData = {
    name: 'My Theme',
    color_tokens: typeof firstSystemTheme.color_tokens === 'string' 
      ? JSON.parse(firstSystemTheme.color_tokens) 
      : firstSystemTheme.color_tokens,
    typography_tokens: typeof firstSystemTheme.typography_tokens === 'string'
      ? JSON.parse(firstSystemTheme.typography_tokens)
      : firstSystemTheme.typography_tokens,
    spacing_tokens: typeof firstSystemTheme.spacing_tokens === 'string'
      ? JSON.parse(firstSystemTheme.spacing_tokens)
      : firstSystemTheme.spacing_tokens,
    shape_tokens: typeof firstSystemTheme.shape_tokens === 'string'
      ? JSON.parse(firstSystemTheme.shape_tokens)
      : firstSystemTheme.shape_tokens,
    motion_tokens: typeof firstSystemTheme.motion_tokens === 'string'
      ? JSON.parse(firstSystemTheme.motion_tokens)
      : firstSystemTheme.motion_tokens,
    iconography_tokens: typeof firstSystemTheme.iconography_tokens === 'string'
      ? JSON.parse(firstSystemTheme.iconography_tokens)
      : firstSystemTheme.iconography_tokens,
    page_background: firstSystemTheme.page_background ?? undefined,
    widget_background: firstSystemTheme.widget_background ?? undefined,
    widget_border_color: firstSystemTheme.widget_border_color ?? undefined,
    page_primary_font: firstSystemTheme.page_primary_font ?? undefined,
    page_secondary_font: firstSystemTheme.page_secondary_font ?? undefined,
    widget_primary_font: firstSystemTheme.widget_primary_font ?? undefined,
    widget_secondary_font: firstSystemTheme.widget_secondary_font ?? undefined,
    widget_styles: typeof firstSystemTheme.widget_styles === 'string'
      ? JSON.parse(firstSystemTheme.widget_styles)
      : firstSystemTheme.widget_styles
  };
  
  const response = await createTheme(themeData);
  
  if (!response.success) {
    throw new Error(response.error ?? 'Failed to create user theme');
  }
  
  // Extract theme_id from response
  const typedResponse = response as ApiResponse & { theme_id?: number; data?: { theme_id?: number } };
  const themeId = typedResponse.theme_id ?? typedResponse.data?.theme_id;
  
  if (!themeId || typeof themeId !== 'number') {
    throw new Error('Failed to get theme ID from create response');
  }
  
  return themeId;
}

