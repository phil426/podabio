import { useQuery } from '@tanstack/react-query';

import { requestJson } from './http';
import type { WidgetAnalyticsResponse } from './types';
import { formPostInit, queryKeys } from './utils';

const ANALYTICS_ENDPOINT = '/api/analytics.php';

export async function fetchWidgetAnalytics(period: string) {
  const response = await requestJson<WidgetAnalyticsResponse>(
    ANALYTICS_ENDPOINT,
    formPostInit({ action: 'widget_analytics', period })
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load analytics');
  }

  return response;
}

export function useWidgetAnalytics(period: string) {
  return useQuery({
    queryKey: queryKeys.analytics(period),
    queryFn: () => fetchWidgetAnalytics(period),
    staleTime: 60 * 1000
  });
}

