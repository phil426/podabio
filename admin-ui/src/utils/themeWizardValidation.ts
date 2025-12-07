/**
 * Validation utilities for Theme Wizard
 * Provides file, image, and color validation functions
 */

export interface ValidationResult {
  valid: boolean;
  error?: string;
}

const MAX_FILE_SIZE_MB = 10;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
const MIN_IMAGE_DIMENSION = 100; // pixels
const MAX_IMAGE_DIMENSION = 10000; // pixels

/**
 * Validates if a file is a valid image file
 */
export function validateImageFile(file: File): ValidationResult {
  // Check file type
  if (!ALLOWED_IMAGE_TYPES.includes(file.type.toLowerCase())) {
    return {
      valid: false,
      error: `Invalid file type. Allowed types: ${ALLOWED_IMAGE_TYPES.map(t => t.split('/')[1].toUpperCase()).join(', ')}`
    };
  }

  // Check file size
  if (file.size > MAX_FILE_SIZE_BYTES) {
    return {
      valid: false,
      error: `File size exceeds ${MAX_FILE_SIZE_MB}MB limit. Current size: ${(file.size / 1024 / 1024).toFixed(2)}MB`
    };
  }

  if (file.size === 0) {
    return {
      valid: false,
      error: 'File is empty'
    };
  }

  return { valid: true };
}

/**
 * Validates image dimensions by loading the image
 */
export function validateImageDimensions(file: File): Promise<ValidationResult> {
  return new Promise((resolve) => {
    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
      URL.revokeObjectURL(url);

      const width = img.width;
      const height = img.height;

      if (width < MIN_IMAGE_DIMENSION || height < MIN_IMAGE_DIMENSION) {
        resolve({
          valid: false,
          error: `Image dimensions too small. Minimum: ${MIN_IMAGE_DIMENSION}x${MIN_IMAGE_DIMENSION}px. Current: ${width}x${height}px`
        });
        return;
      }

      if (width > MAX_IMAGE_DIMENSION || height > MAX_IMAGE_DIMENSION) {
        resolve({
          valid: false,
          error: `Image dimensions too large. Maximum: ${MAX_IMAGE_DIMENSION}x${MAX_IMAGE_DIMENSION}px. Current: ${width}x${height}px`
        });
        return;
      }

      resolve({ valid: true });
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      resolve({
        valid: false,
        error: 'Failed to load image. The file may be corrupted.'
      });
    };

    img.src = url;
  });
}

/**
 * Validates if a color string is a valid hex color
 */
export function validateHexColor(color: string): ValidationResult {
  if (!color || typeof color !== 'string') {
    return {
      valid: false,
      error: 'Color must be a non-empty string'
    };
  }

  // Remove leading # if present
  const hex = color.startsWith('#') ? color.slice(1) : color;

  // Check if it's a valid hex color (3 or 6 characters, only hex digits)
  const hexPattern = /^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/;
  if (!hexPattern.test(hex)) {
    return {
      valid: false,
      error: `Invalid hex color format: ${color}. Expected format: #RRGGBB or #RGB`
    };
  }

  return { valid: true };
}

/**
 * Validates an array of colors
 */
export function validateColorArray(colors: string[]): ValidationResult {
  if (!Array.isArray(colors)) {
    return {
      valid: false,
      error: 'Colors must be an array'
    };
  }

  if (colors.length === 0) {
    return {
      valid: false,
      error: 'At least one color is required'
    };
  }

  for (let i = 0; i < colors.length; i++) {
    const result = validateHexColor(colors[i]);
    if (!result.valid) {
      return {
        valid: false,
        error: `Color at index ${i}: ${result.error}`
      };
    }
  }

  return { valid: true };
}


