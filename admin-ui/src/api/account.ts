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

export async function updateAccountProfile(data: { name?: string; email?: string; avatar_url?: string | null }) {
  return requestJson<ApiResponse<AccountProfile>>('/api/account/profile.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  });
}

export async function removeAvatar() {
  return updateAccountProfile({ avatar_url: null });
}

export function useUpdateProfileMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: updateAccountProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export interface IntegrationsStatus {
  // Integration statuses can be added here in the future
}

export interface TwoFactorStatus {
  enabled: boolean;
  method: 'totp' | 'email' | 'both' | null;
  email?: string;
}

export interface TwoFactorSetupData {
  secret: string;
  qr_code_url: string;
}

export interface TwoFactorEnableResponse {
  backup_codes: string[];
  method: 'totp' | 'email' | 'both';
}

export async function fetchIntegrationsStatus(): Promise<IntegrationsStatus> {
  const response = await requestJson<ApiResponse<IntegrationsStatus>>('/api/account/integrations.php?action=get_status');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load integrations status');
  }

  return response.data ?? {};
}

/**
 * Fetch 2FA status
 */
export async function fetch2FAStatus(): Promise<TwoFactorStatus> {
  const response = await requestJson<ApiResponse<TwoFactorStatus>>('/api/account/2fa.php?action=get_status');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load 2FA status');
  }

  return response.data ?? { enabled: false, method: null };
}

export function use2FAStatus() {
  return useQuery({
    queryKey: ['2fa', 'status'],
    queryFn: fetch2FAStatus,
    staleTime: 2 * 60 * 1000
  });
}

/**
 * Generate TOTP setup (QR code)
 */
export async function generate2FASetup(): Promise<TwoFactorSetupData> {
  const response = await requestJson<ApiResponse<TwoFactorSetupData>>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'generate_setup' })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to generate 2FA setup');
  }

  return response.data!;
}

/**
 * Verify and enable 2FA
 */
export async function enable2FA(code: string, method: 'totp' | 'email' | 'both'): Promise<TwoFactorEnableResponse> {
  const response = await requestJson<ApiResponse<TwoFactorEnableResponse>>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ 
      action: 'verify_enable',
      code,
      method
    })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to enable 2FA');
  }

  return response.data!;
}

/**
 * Send email code for email-only 2FA setup
 */
export async function sendSetupEmailCode(): Promise<void> {
  const response = await requestJson<ApiResponse>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'send_setup_email_code' })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to send email code');
  }
}

/**
 * Enable email-only 2FA
 */
export async function enableEmail2FA(code: string): Promise<TwoFactorEnableResponse> {
  const response = await requestJson<ApiResponse<TwoFactorEnableResponse>>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ 
      action: 'enable_email',
      code
    })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to enable email 2FA');
  }

  return response.data!;
}

/**
 * Disable 2FA
 */
export async function disable2FA(password: string): Promise<void> {
  const response = await requestJson<ApiResponse>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ 
      action: 'disable',
      password
    })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to disable 2FA');
  }
}

/**
 * Regenerate backup codes
 */
export async function regenerateBackupCodes(): Promise<string[]> {
  const response = await requestJson<ApiResponse<{ backup_codes: string[] }>>('/api/account/2fa.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ action: 'regenerate_backup_codes' })
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to regenerate backup codes');
  }

  return response.data!.backup_codes;
}

/**
 * React Query hooks for 2FA mutations
 */
export function useGenerate2FASetupMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: generate2FASetup,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['2fa'] });
    }
  });
}

export function useEnable2FAMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ code, method }: { code: string; method: 'totp' | 'email' | 'both' }) => enable2FA(code, method),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['2fa'] });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export function useEnableEmail2FAMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: enableEmail2FA,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['2fa'] });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export function useDisable2FAMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: disable2FA,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['2fa'] });
      queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
    }
  });
}

export function useSendSetupEmailCodeMutation() {
  return useMutation({
    mutationFn: sendSetupEmailCode
  });
}

export function useRegenerateBackupCodesMutation() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: regenerateBackupCodes,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['2fa'] });
    }
  });
}

export function useIntegrationsStatus() {
  return useQuery({
    queryKey: ['integrations', 'status'],
    queryFn: fetchIntegrationsStatus,
    staleTime: 2 * 60 * 1000
  });
}


