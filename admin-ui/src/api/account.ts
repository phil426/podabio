import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { requestJson } from './http';
import type { AccountProfile, AuthMethodRecord, BillingInfo, ApiResponse } from './types';
import { queryKeys } from './utils';

export async function fetchAccountProfile(): Promise<AccountProfile> {
  const response = await requestJson<ApiResponse<AccountProfile>>('/api/account/profile.php');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load account profile');
  }

  return (response.data ?? response) as AccountProfile;
}

export function useAccountProfile() {
  return useQuery({
    queryKey: queryKeys.accountProfile(),
    queryFn: fetchAccountProfile,
    staleTime: 5 * 60 * 1000
  });
}

export async function fetchAuthMethods(): Promise<AuthMethodRecord> {
  const response = await requestJson<ApiResponse<AuthMethodRecord>>('/api/account/auth-methods.php');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load auth methods');
  }

  return (response.data ?? response) as AuthMethodRecord;
}

export function useAuthMethods() {
  return useQuery({
    queryKey: queryKeys.authMethods(),
    queryFn: fetchAuthMethods
  });
}

export async function fetchSubscriptionStatus(): Promise<BillingInfo> {
  const response = await requestJson<ApiResponse<BillingInfo>>('/api/account/subscription.php');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load subscription');
  }

  return (response.data ?? response) as BillingInfo;
}

export function useSubscriptionStatus() {
  return useQuery({
    queryKey: queryKeys.subscriptionStatus(),
    queryFn: fetchSubscriptionStatus
  });
}

export function useRefreshAccountData() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => ({ success: true }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
      queryClient.invalidateQueries({ queryKey: queryKeys.authMethods() });
      queryClient.invalidateQueries({ queryKey: queryKeys.subscriptionStatus() });
      queryClient.invalidateQueries({ queryKey: ['integrations', 'status'] });
    }
  });
}

export async function unlinkGoogleAccount() {
  return requestJson<ApiResponse>('/api/account/security.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'unlink_google' })
  });
}

export async function removePassword() {
  return requestJson<ApiResponse>('/api/account/security.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'remove_password' })
  });
}

export async function createUserPage(username: string) {
  return requestJson<ApiResponse<{ page_id?: number | null }>>('/api/account/page.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'create_page', username })
  });
}

export function useUnlinkGoogleMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: unlinkGoogleAccount,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.authMethods() });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export function useRemovePasswordMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: removePassword,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.authMethods() });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export function useCreatePageMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: createUserPage,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      queryClient.invalidateQueries({ queryKey: queryKeys.widgets() });
    }
  });
}

export interface IntegrationsStatus {
  instagram: {
    connected: boolean;
    expired: boolean;
    link_url: string;
    configured?: boolean;
  };
}

export async function fetchIntegrationsStatus(): Promise<IntegrationsStatus> {
  const response = await requestJson<ApiResponse<IntegrationsStatus>>('/api/account/integrations.php?action=get_status');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load integrations status');
  }

  return response.data ?? { instagram: { connected: false, expired: false, link_url: '' } };
}

export function useIntegrationsStatus() {
  return useQuery({
    queryKey: ['integrations', 'status'],
    queryFn: fetchIntegrationsStatus,
    staleTime: 2 * 60 * 1000
  });
}

export async function disconnectInstagram() {
  return requestJson<ApiResponse>('/api/account/integrations.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'disconnect_instagram' })
  });
}

export function useDisconnectInstagramMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: disconnectInstagram,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['integrations', 'status'] });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

