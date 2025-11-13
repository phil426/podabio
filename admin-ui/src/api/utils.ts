import { buildFormData } from './http';

export function getCsrfToken(): string {
  if (typeof window === 'undefined') {
    return '';
  }
  return window.__CSRF_TOKEN__ ?? '';
}

export const queryKeys = {
  tokens: () => ['tokens'] as const,
  pageSnapshot: () => ['page', 'snapshot'] as const,
  widgets: () => ['widgets'] as const,
  availableWidgets: () => ['widgets', 'available'] as const,
  analytics: (period: string) => ['analytics', period] as const,
  accountProfile: () => ['account', 'profile'] as const,
  authMethods: () => ['account', 'auth-methods'] as const,
  subscriptionStatus: () => ['account', 'subscription'] as const,
  themes: () => ['themes', 'library'] as const,
  tokenHistory: () => ['tokens', 'history'] as const,
  blogPosts: (params?: { page?: number; limit?: number; category_id?: number }) => ['blog', 'posts', params] as const,
  blogPost: (postId: number | null) => ['blog', 'post', postId] as const,
  blogCategories: () => ['blog', 'categories'] as const
};

export function formPostInit(payload: Record<string, FormDataEntryValue | undefined>): RequestInit {
  return {
    method: 'POST',
    body: buildFormData({ csrf_token: getCsrfToken(), ...payload })
  };
}

