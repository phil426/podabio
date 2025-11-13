import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { requestJson } from './http';
import type {
  ApiResponse,
  AvailableWidget,
  AvailableWidgetsResponse,
  WidgetListResponse,
  WidgetRecord
} from './types';
import { formPostInit, queryKeys } from './utils';

const WIDGETS_ENDPOINT = '/api/widgets.php';

export async function fetchWidgets(): Promise<WidgetRecord[]> {
  const response = await requestJson<WidgetListResponse>(WIDGETS_ENDPOINT, formPostInit({ action: 'get' }));

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load widgets');
  }

  return response.widgets.map(normalizeWidget);
}

export async function fetchAvailableWidgets(): Promise<AvailableWidget[]> {
  const response = await requestJson<AvailableWidgetsResponse>(
    WIDGETS_ENDPOINT,
    formPostInit({ action: 'get_available' })
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load library');
  }

  const data = response.available_widgets ?? response.widgets ?? [];
  if (Array.isArray(data)) {
    return data;
  }
  return Object.values(data);
}

export function useWidgetsQuery() {
  return useQuery({
    queryKey: queryKeys.widgets(),
    queryFn: fetchWidgets
  });
}

export function useAvailableWidgetsQuery() {
  return useQuery({
    queryKey: queryKeys.availableWidgets(),
    queryFn: fetchAvailableWidgets
  });
}

type WidgetMutationPayload = Record<string, FormDataEntryValue | undefined>;

function useWidgetMutation(action: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: WidgetMutationPayload) =>
      requestJson<ApiResponse>(WIDGETS_ENDPOINT, formPostInit({ action, ...payload })),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.widgets() });
      queryClient.invalidateQueries({ queryKey: queryKeys.analytics('month') });
    },
    onError: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.widgets() });
    }
  });
}

export const useAddWidgetMutation = () => useWidgetMutation('add');
export const useUpdateWidgetMutation = () => useWidgetMutation('update');
export const useDeleteWidgetMutation = () => useWidgetMutation('delete');
export const useReorderWidgetMutation = () => useWidgetMutation('reorder');

function normalizeWidget(widget: WidgetRecord): WidgetRecord {
  if (widget && typeof widget.config_data === 'string') {
    try {
      return {
        ...widget,
        config_data: JSON.parse(widget.config_data)
      };
    } catch {
      return {
        ...widget,
        config_data: null
      };
    }
  }
  return widget;
}

