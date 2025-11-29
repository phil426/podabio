import { getCsrfToken } from './utils';

interface UploadResponse {
  success: boolean;
  url?: string;
  path?: string;
  error?: string;
  message?: string;
}

export async function uploadProfileImage(file: File, saveToLibrary?: boolean): Promise<UploadResponse> {
  // Client-side validation: Check file size (5MB limit)
  const maxSize = 5 * 1024 * 1024; // 5MB in bytes
  if (file.size > maxSize) {
    throw new Error(`File size (${(file.size / 1024 / 1024).toFixed(2)}MB) exceeds the maximum allowed size of 5MB. Please choose a smaller image.`);
  }

  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'profile');
  formData.append('image', file);
  if (saveToLibrary) {
    formData.append('save_to_library', 'true');
  }

  const response = await fetch('/api/upload.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  });

  const payload = (await response.json()) as UploadResponse;

  if (!response.ok || !payload.success) {
    // Provide more helpful error messages
    let errorMessage = payload.error ?? 'Failed to upload profile image';
    if (errorMessage.includes('File size exceeds')) {
      errorMessage = `File size exceeds the maximum allowed size of 5MB. Please choose a smaller image.`;
    }
    throw new Error(errorMessage);
  }

  return payload;
}

export async function uploadWidgetThumbnail(file: File, saveToLibrary?: boolean): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'thumbnail');
  formData.append('image', file);
  if (saveToLibrary) {
    formData.append('save_to_library', 'true');
  }

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

export async function uploadBackgroundImage(file: File, saveToLibrary?: boolean): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('type', 'background');
  formData.append('image', file);
  if (saveToLibrary) {
    formData.append('save_to_library', 'true');
  }

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
