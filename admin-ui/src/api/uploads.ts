import { getCsrfToken } from './utils';

interface UploadResponse {
  success: boolean;
  url?: string;
  path?: string;
  error?: string;
  message?: string;
}

export async function uploadProfileImage(file: File): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'profile');
  formData.append('image', file);

  const response = await fetch('/api/upload.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  });

  const payload = (await response.json()) as UploadResponse;

  if (!response.ok || !payload.success) {
    throw new Error(payload.error ?? 'Failed to upload profile image');
  }

  return payload;
}

export async function uploadWidgetThumbnail(file: File): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'thumbnail');
  formData.append('image', file);

  const response = await fetch('/api/upload.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  });

  const payload = (await response.json()) as UploadResponse;

  if (!response.ok || !payload.success) {
    throw new Error(payload.error ?? 'Failed to upload thumbnail image');
  }

  return payload;
}

export async function uploadBackgroundImage(file: File): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'background');
  formData.append('image', file);

  const response = await fetch('/api/upload.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  });

  const payload = (await response.json()) as UploadResponse;

  if (!response.ok || !payload.success) {
    throw new Error(payload.error ?? 'Failed to upload background image');
  }

  return payload;
}
