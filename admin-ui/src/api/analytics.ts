import { useQuery } from '@tanstack/react-query';

import { requestJson } from './http';
import type { WidgetAnalyticsResponse, LinkAnalyticsResponse } from './types';
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

export async function fetchLinkAnalytics(period: string) {
  const response = await requestJson<LinkAnalyticsResponse>(
    ANALYTICS_ENDPOINT,
    formPostInit({ action: 'link_analytics', period })
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load link analytics');
  }

  return response;
}

export function useLinkAnalytics(period: string) {
  return useQuery({
    queryKey: ['linkAnalytics', period],
    queryFn: () => fetchLinkAnalytics(period),
    staleTime: 60 * 1000
  });
}

