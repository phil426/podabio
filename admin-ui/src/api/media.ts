import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getCsrfToken, queryKeys } from './utils';
import { refreshCsrfToken } from './http';

const MEDIA_ENDPOINT = '/api/media.php';

export interface MediaItem {
  id: number;
  user_id?: number;
  filename?: string; // Optional for stock
  file_path?: string; // Optional for stock
  file_url: string;
  file_size: number;
  mime_type?: string;
  uploaded_at?: string;
  created_at?: string;
  updated_at?: string;
  // Stock Photo Props
  url?: string;
  thumbnail?: string;
  author?: string;
  author_url?: string;
  provider?: 'unsplash' | 'pexels';
}

export interface MediaLibraryResponse {
  success: boolean;
  media?: MediaItem[];
  results?: MediaItem[]; // For stock search
  total?: number;
  page?: number;
  per_page?: number;
  total_pages?: number;
  error?: string;
}

export interface MediaItemResponse {
  success: boolean;
  media?: MediaItem;
  error?: string;
}

export interface MediaUploadResponse {
  success: boolean;
  media?: MediaItem;
  message?: string;
  error?: string;
}

export interface MediaListOptions {
  page?: number;
  per_page?: number;
  search?: string;
}

/**
 * Fetch user's media library
 */
export async function fetchMediaLibrary(options: MediaListOptions = {}): Promise<MediaLibraryResponse> {
  const params = new URLSearchParams();

  if (options.page) {
    params.append('page', String(options.page));
  }
  if (options.per_page) {
    params.append('per_page', String(options.per_page));
  }
  if (options.search) {
    params.append('search', options.search);
  }

  const url = params.toString() ? `${MEDIA_ENDPOINT}?${params.toString()}` : MEDIA_ENDPOINT;

  const response = await fetch(url, {
    credentials: 'include'
  });

  const data = (await response.json()) as MediaLibraryResponse;

  if (!response.ok || !data.success) {
    throw new Error(data.error ?? 'Failed to load media library');
  }

  return data;
}

/**
 * React Query hook to fetch media library
 */
export function useMediaLibraryQuery(options: MediaListOptions = {}) {
  return useQuery({
    queryKey: queryKeys.media(options),
    queryFn: () => fetchMediaLibrary(options),
    staleTime: 30 * 1000 // 30 seconds
  });
}

/**
 * Upload file to media library
 */
export async function uploadToMediaLibrary(file: File): Promise<MediaUploadResponse> {
  // Client-side validation: Check file size (5MB limit)
  const maxSize = 5 * 1024 * 1024; // 5MB in bytes
  if (file.size > maxSize) {
    throw new Error(`File size (${(file.size / 1024 / 1024).toFixed(2)}MB) exceeds the maximum allowed size of 5MB. Please choose a smaller image.`);
  }

  // Validate file type
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    throw new Error('Invalid file type. Please use JPEG, PNG, GIF, or WebP format.');
  }

  // Ensure we have a valid CSRF token
  let csrfToken = getCsrfToken();
  if (!csrfToken) {
    try {
      csrfToken = await refreshCsrfToken();
    } catch (err) {
      throw new Error('Failed to get CSRF token. Please refresh the page and try again.');
    }
  }

  const formData = new FormData();
  formData.append('csrf_token', csrfToken);
  formData.append('image', file);

  const response = await fetch(MEDIA_ENDPOINT, {
    method: 'POST',
    body: formData,
    credentials: 'include'
  });

  const payload = (await response.json()) as MediaUploadResponse;

  // If CSRF token error, try refreshing and retrying once
  if ((!response.ok || !payload.success) && (payload.error?.includes('CSRF') || payload.error?.includes('Invalid CSRF') || response.status === 403)) {
    try {
      const newCsrfToken = await refreshCsrfToken();
      const retryFormData = new FormData();
      retryFormData.append('csrf_token', newCsrfToken);
      retryFormData.append('image', file);

      const retryResponse = await fetch(MEDIA_ENDPOINT, {
        method: 'POST',
        body: retryFormData,
        credentials: 'include'
      });

      const retryPayload = (await retryResponse.json()) as MediaUploadResponse;

      if (!retryResponse.ok || !retryPayload.success) {
        let errorMessage = retryPayload.error ?? 'Failed to upload image';
        if (errorMessage.includes('File size exceeds')) {
          errorMessage = `File size exceeds the maximum allowed size of 5MB. Please choose a smaller image.`;
        }
        throw new Error(errorMessage);
      }

      return retryPayload;
    } catch (retryErr) {
      // If retry also fails, throw the original error
      let errorMessage = payload.error ?? 'Failed to upload image';
      if (errorMessage.includes('File size exceeds')) {
        errorMessage = `File size exceeds the maximum allowed size of 5MB. Please choose a smaller image.`;
      }
      throw new Error(errorMessage);
    }
  }

  if (!response.ok || !payload.success) {
    let errorMessage = payload.error ?? 'Failed to upload image';
    if (errorMessage.includes('File size exceeds')) {
      errorMessage = `File size exceeds the maximum allowed size of 5MB. Please choose a smaller image.`;
    }
    throw new Error(errorMessage);
  }

  return payload;
}

/**
 * Delete media item from library
 */
export async function deleteMediaItem(mediaId: number): Promise<{ success: boolean; message?: string; error?: string }> {
  const csrfToken = getCsrfToken();

  const response = await fetch(`${MEDIA_ENDPOINT}?id=${mediaId}&csrf_token=${encodeURIComponent(csrfToken)}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-Token': csrfToken
    },
    credentials: 'include'
  });

  const payload = (await response.json()) as { success: boolean; message?: string; error?: string };

  if (!response.ok || !payload.success) {
    throw new Error(payload.error ?? 'Failed to delete media item');
  }

  return payload;
}

/**
 * React Query mutation for uploading to media library
 */
export function useUploadToMediaLibraryMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (file: File) => uploadToMediaLibrary(file),
    onSuccess: () => {
      // Invalidate all media queries to refresh the list
      queryClient.invalidateQueries({ queryKey: ['media'] });
    }
  });
}

/**
 * React Query mutation for deleting from media library
 */
export function useDeleteMediaItemMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (mediaId: number) => deleteMediaItem(mediaId),
    onSuccess: () => {
      // Invalidate all media queries to refresh the list
      queryClient.invalidateQueries({ queryKey: ['media'] });
    }
  });
}



/**
 * Update media item (e.g. rename)
 */
export async function updateMediaItem(mediaId: number, data: { filename?: string }): Promise<{ success: boolean; message?: string; error?: string }> {
  const csrfToken = getCsrfToken();

  const response = await fetch(MEDIA_ENDPOINT, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
      id: mediaId,
      csrf_token: csrfToken,
      ...data
    }),
    credentials: 'include'
  });

  const payload = (await response.json()) as { success: boolean; message?: string; error?: string };

  if (!response.ok || !payload.success) {
    throw new Error(payload.error ?? 'Failed to update media item');
  }

  return payload;
}

/**
 * React Query mutation for updating media item
 */
export function useUpdateMediaItemMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (vars: { id: number; data: { filename?: string } }) => updateMediaItem(vars.id, vars.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['media'] });
    }
  });
}

/**
 * Search stock photos via proxy
 */
export async function searchStockPhotos(provider: 'unsplash' | 'pexels' | 'all', query: string, page: number = 1): Promise<MediaLibraryResponse> {
  const params = new URLSearchParams({
    provider,
    query,
    page: String(page)
  });

  const response = await fetch(`/api/stock-photos.php?${params.toString()}`, {
    credentials: 'include'
  });

  const data = (await response.json()) as MediaLibraryResponse;

  if (!response.ok || !data.success) {
    throw new Error(data.error ?? 'Failed to search stock photos');
  }

  return data;
}

/**
 * Import stock photo to local library
 */
export async function importStockPhoto(url: string, provider: string, author: string): Promise<MediaUploadResponse> {
  const response = await fetch('/api/stock-photos.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      url,
      provider,
      author
    }),
    credentials: 'include'
  });

  const data = (await response.json()) as MediaUploadResponse;

  if (!response.ok || !data.success) {
    throw new Error(data.error ?? 'Failed to import stock photo');
  }

  return data;
}

/**
 * React Query hook for stock photo search
 */
export function useStockPhotosQuery(provider: 'unsplash' | 'pexels' | 'all', query: string, page: number, enabled: boolean) {
  return useQuery({
    queryKey: ['stock-photos', provider, query, page],
    queryFn: () => searchStockPhotos(provider, query, page),
    enabled: enabled && !!query,
    staleTime: 60 * 1000 // 1 minute
  });
}
