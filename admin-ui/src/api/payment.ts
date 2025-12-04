import { requestJson, buildFormData } from './http';
import type { ApiResponse } from './types';
import { getCsrfToken } from './utils';

export interface PaymentResponse {
  success: boolean;
  checkout_url?: string;
  session_id?: string;
  error?: string;
}

export interface SubscriptionStatus {
  plan_type: 'free' | 'pro';
  status: 'active' | 'expired' | 'canceled' | 'trial';
  expires_at?: string | null;
  trial_ends_at?: string | null;
  is_trial?: boolean;
  billing_interval?: 'month' | 'year';
  payment_method?: string | null;
  is_root_admin?: boolean;
}

/**
 * Start 14-day free trial for Pro plan
 */
export async function startProTrial(billingInterval: 'month' | 'year' = 'month'): Promise<PaymentResponse> {
  const response = await requestJson<ApiResponse<PaymentResponse>>('/api/payment/start-trial.php', {
    method: 'POST',
    body: buildFormData({
      billing_interval: billingInterval,
      csrf_token: getCsrfToken()
    }),
    parseJson: true
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to start trial');
  }

  if (response.data) {
    return response.data;
  }
  
  // Fallback: if response itself is PaymentResponse
  return response as PaymentResponse;
}

/**
 * Subscribe to Pro plan (monthly or annual)
 */
export async function subscribeToPro(billingInterval: 'month' | 'year' = 'month'): Promise<PaymentResponse> {
  const response = await requestJson<ApiResponse<PaymentResponse>>('/api/payment/process.php', {
    method: 'POST',
    body: buildFormData({
      plan: 'pro',
      billing_interval: billingInterval,
      csrf_token: getCsrfToken()
    }),
    parseJson: true
  });

  if (!response.success) {
    throw new Error(response.error ?? 'Failed to create subscription');
  }

  if (response.data) {
    return response.data;
  }
  
  // Fallback: if response itself is PaymentResponse
  return response as PaymentResponse;
}

/**
 * Cancel subscription
 */
export async function cancelSubscription(): Promise<ApiResponse> {
  return requestJson<ApiResponse>('/api/payment/cancel-subscription.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'cancel_subscription'
    })
  });
}

/**
 * Get current subscription/trial status
 */
export async function getSubscriptionStatus(): Promise<SubscriptionStatus> {
  const response = await requestJson<ApiResponse<SubscriptionStatus>>('/api/account/subscription.php');

  if (!response.success) {
    throw new Error(response.error ?? 'Unable to load subscription status');
  }

  return (response.data ?? response) as SubscriptionStatus;
}

