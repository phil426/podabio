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
  page_background?: string;
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
      page_background: data.page_background
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
      page_background: data.page_background
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
      queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
    }
  });
}

