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

export async function updatePageThemeId(themeId: number | null, themeData?: { page_background?: string | null; widget_background?: string | null }) {
  const payload: Record<string, string | null> = {
    theme_id: themeId !== null ? String(themeId) : ''
  };
  
  // If theme data is provided, set page_background and widget_background
  // Pass null to clear page-level overrides (so theme values are used)
  if (themeData) {
    if (themeData.page_background !== undefined) {
      payload.page_background = themeData.page_background;
    }
    if (themeData.widget_background !== undefined) {
      payload.widget_background = themeData.widget_background;
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

