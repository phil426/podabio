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

/**
 * Normalize image URL to work in current environment (local vs production)
 * Converts production URLs to local URLs when running on localhost
 */
export function normalizeImageUrl(url: string | null | undefined): string {
  if (!url) {
    return '';
  }

  // If already a relative path, make it absolute using current origin
  if (url.startsWith('/') && !url.startsWith('//')) {
    return window.location.origin + url;
  }

  // Get production URL from window (set by PHP)
  const productionUrl = window.__APP_URL__ ?? 'https://www.poda.bio';
  
  // If it's a full URL with production domain, convert to current base URL
  if (url.startsWith(productionUrl)) {
    const path = url.substring(productionUrl.length);
    return window.location.origin + path;
  }

  // If it's already a full URL with different domain, return as-is
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  // Otherwise assume it's a relative path
  return window.location.origin + '/' + url.replace(/^\//, '');
}

