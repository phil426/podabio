import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { requestJson } from './http';
import type { ApiResponse, PageSnapshotResponse, PublishStateResponse } from './types';
import { formPostInit, queryKeys } from './utils';

const PAGE_ENDPOINT = '/api/page.php';

type Payload = Record<string, FormDataEntryValue | undefined>;

export async function fetchPageSnapshot(): Promise<PageSnapshotResponse> {
  return requestJson<PageSnapshotResponse>(`${PAGE_ENDPOINT}?action=get_snapshot`, { method: 'GET' });
}

export function usePageSnapshot() {
  return useQuery({
    queryKey: queryKeys.pageSnapshot(),
    queryFn: fetchPageSnapshot
  });
}

export async function updatePageSettings(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_settings',
      ...payload
    })
  );
}

export interface ThemeApplicationData {
  page_background?: string | null;
  widget_background?: string | null;
  widget_border_color?: string | null;
  page_primary_font?: string | null;
  page_secondary_font?: string | null;
  widget_primary_font?: string | null;
  widget_secondary_font?: string | null;
  widget_styles?: Record<string, unknown> | string | null;
  spatial_effect?: string | null;
}

export async function updatePageThemeId(themeId: number | null, themeData?: ThemeApplicationData) {
  const payload: Record<string, string | null> = {
    theme_id: themeId !== null ? String(themeId) : ''
  };
  
  // If theme data is provided, send all theme fields
  // Pass null to clear page-level overrides (so theme values are used)
  if (themeData) {
    if (themeData.page_background !== undefined) {
      payload.page_background = themeData.page_background;
    }
    if (themeData.widget_background !== undefined) {
      payload.widget_background = themeData.widget_background;
    }
    if (themeData.widget_border_color !== undefined) {
      payload.widget_border_color = themeData.widget_border_color;
    }
    if (themeData.page_primary_font !== undefined) {
      payload.page_primary_font = themeData.page_primary_font;
    }
    if (themeData.page_secondary_font !== undefined) {
      payload.page_secondary_font = themeData.page_secondary_font;
    }
    if (themeData.widget_primary_font !== undefined) {
      payload.widget_primary_font = themeData.widget_primary_font;
    }
    if (themeData.widget_secondary_font !== undefined) {
      payload.widget_secondary_font = themeData.widget_secondary_font;
    }
    if (themeData.widget_styles !== undefined) {
      // Handle null case (to clear page override)
      if (themeData.widget_styles === null) {
        payload.widget_styles = null;
      } else {
        // Convert widget_styles to JSON string if it's an object
        if (typeof themeData.widget_styles === 'object' && themeData.widget_styles !== null) {
          payload.widget_styles = JSON.stringify(themeData.widget_styles);
        } else {
          payload.widget_styles = themeData.widget_styles as string | null;
        }
      }
    }
    if (themeData.spatial_effect !== undefined) {
      payload.spatial_effect = themeData.spatial_effect;
    }
  }
  
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_appearance',
      ...payload
    })
  );
}

export async function updatePageAppearance(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_appearance',
      ...payload
    })
  );
}

export async function updateEmailSettings(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_email_settings',
      ...payload
    })
  );
}

export async function addSocialIcon(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'add_directory',
      ...payload
    })
  );
}

export async function updateSocialIcon(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_directory',
      ...payload
    })
  );
}

export async function toggleSocialIconVisibility(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_social_icon_visibility',
      ...payload
    })
  );
}

export async function deleteSocialIcon(payload: Payload) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'delete_directory',
      ...payload
    })
  );
}

export async function reorderSocialIcons(iconOrders: Array<{ icon_id: number; display_order: number }>) {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'reorder_social_icons',
      icon_orders: JSON.stringify(iconOrders)
    })
  );
}

export async function updatePublishState(payload: Payload) {
  const response = await requestJson<PublishStateResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'update_publish_state',
      ...payload
    })
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to update publish status');
  }

  return response;
}

export function usePublishStateMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Payload) => updatePublishState(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });
}

export function usePageSettingsMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Payload) => updatePageSettings(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });
}

export function usePageAppearanceMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Payload) => updatePageAppearance(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });
}

export async function removeProfileImage() {
  return requestJson<ApiResponse>(
    PAGE_ENDPOINT,
    formPostInit({
      action: 'remove_image',
      type: 'profile'
    })
  );
}

export interface PodlinksResponse extends ApiResponse {
  data?: {
    podcast_name: string;
    platforms: Record<string, {
      found: boolean;
      url?: string | null;
      error?: string;
      skipped?: boolean;
    }>;
  };
}

export async function generatePodlinks() {
  return requestJson<PodlinksResponse>(
    '/api/podlinks.php',
    formPostInit({
      action: 'generate_podlinks'
    })
  );
}

