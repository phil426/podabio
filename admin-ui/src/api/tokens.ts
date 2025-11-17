import { useMutation, useQuery, useQueryClient, type UseQueryResult } from '@tanstack/react-query';

import { requestJson } from './http';
import type { TokenHistoryEntry, TokensResponse } from './types';
import type { TokenBundle } from '../design-system/tokens';
import { queryKeys } from './utils';

const TOKENS_ENDPOINT = '/api/tokens.php';

export async function fetchTokens(): Promise<TokenBundle> {
  const response = await requestJson<TokensResponse>(TOKENS_ENDPOINT);

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to load design tokens');
  }

  return response.tokens;
}

export async function saveTokenOverrides(tokens: Partial<TokenBundle>) {
  const response = await requestJson<{ success: boolean; error?: string }>(TOKENS_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(tokens)
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to save token overrides');
  }

  return response;
}

export async function fetchTokenHistory(): Promise<TokenHistoryEntry[]> {
  const response = await requestJson<{ success: boolean; history?: TokenHistoryEntry[]; error?: string }>(
    `${TOKENS_ENDPOINT}?action=history`
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load token history');
  }

  return response.history ?? [];
}

export async function rollbackTokenHistory(historyId: number): Promise<Partial<TokenBundle>> {
  const response = await requestJson<{ success: boolean; overrides?: Partial<TokenBundle>; error?: string }>(
    TOKENS_ENDPOINT,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action: 'rollback', history_id: historyId })
    }
  );

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to rollback token snapshot');
  }

  return response.overrides ?? {};
}

export function useTokensQuery(): UseQueryResult<TokenBundle> {
  return useQuery({
    queryKey: queryKeys.tokens(),
    queryFn: fetchTokens,
    staleTime: 5 * 60 * 1000,
    retry: false
  });
}

export function useSaveTokensMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: saveTokenOverrides,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tokens() });
      queryClient.invalidateQueries({ queryKey: queryKeys.tokenHistory() });
    }
  });
}

export function useTokenHistoryQuery() {
  return useQuery({
    queryKey: queryKeys.tokenHistory(),
    queryFn: fetchTokenHistory,
    staleTime: 60 * 1000
  });
}

export function useRollbackTokensMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: rollbackTokenHistory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tokens() });
      queryClient.invalidateQueries({ queryKey: queryKeys.tokenHistory() });
    }
  });
}

