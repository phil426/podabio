import { describe, it, expect, vi, beforeEach, afterEach, beforeAll } from 'vitest';
import {
  validateImageFile,
  validateImageDimensions,
  validateHexColor,
  validateColorArray
} from '../themeWizardValidation';

describe('themeWizardValidation', () => {
  describe('validateImageFile', () => {
    it('should validate a valid JPEG file', () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(true);
      expect(result.error).toBeUndefined();
    });

    it('should validate a valid PNG file', () => {
      const file = new File(['test'], 'test.png', { type: 'image/png' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(true);
    });

    it('should validate a valid WebP file', () => {
      const file = new File(['test'], 'test.webp', { type: 'image/webp' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(true);
    });

    it('should reject an invalid file type', () => {
      const file = new File(['test'], 'test.pdf', { type: 'application/pdf' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('Invalid file type');
    });

    it('should reject a file that is too large', () => {
      // Create a file larger than 10MB
      const largeContent = new Array(11 * 1024 * 1024).fill('a').join('');
      const file = new File([largeContent], 'large.jpg', { type: 'image/jpeg' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('exceeds');
    });

    it('should reject an empty file', () => {
      const file = new File([], 'empty.jpg', { type: 'image/jpeg' });
      const result = validateImageFile(file);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('empty');
    });
  });

  describe('validateImageDimensions', () => {
    let createObjectURLSpy: any;
    let revokeObjectURLSpy: any;

    beforeAll(() => {
      // Polyfill createObjectURL/revokeObjectURL for JSDOM
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (URL as any).createObjectURL = (URL as any).createObjectURL || vi.fn(() => 'blob:mock');
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (URL as any).revokeObjectURL = (URL as any).revokeObjectURL || vi.fn();
    });

    beforeEach(() => {
      createObjectURLSpy = vi.spyOn(URL, 'createObjectURL');
      revokeObjectURLSpy = vi.spyOn(URL, 'revokeObjectURL');
    });

    afterEach(() => {
      vi.restoreAllMocks();
    });

    it('should validate an image with valid dimensions', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      const mockUrl = 'blob:test';
      createObjectURLSpy.mockReturnValue(mockUrl);

      // Mock Image constructor
      const mockImage = {
        width: 500,
        height: 500,
        onload: null as (() => void) | null,
        onerror: null as (() => void) | null,
        src: ''
      };

      vi.spyOn(global, 'Image').mockImplementation(() => {
        setTimeout(() => {
          if (mockImage.onload) {
            mockImage.onload();
          }
        }, 0);
        return mockImage as unknown as HTMLImageElement;
      });

      const resultPromise = validateImageDimensions(file);
      mockImage.onload?.();
      const result = await resultPromise;

      expect(result.valid).toBe(true);
      expect(createObjectURLSpy).toHaveBeenCalledWith(file);
      expect(revokeObjectURLSpy).toHaveBeenCalledWith(mockUrl);
    });

    it('should reject an image that is too small', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      const mockUrl = 'blob:test';
      createObjectURLSpy.mockReturnValue(mockUrl);

      const mockImage = {
        width: 50,
        height: 50,
        onload: null as (() => void) | null,
        onerror: null as (() => void) | null,
        src: ''
      };

      vi.spyOn(global, 'Image').mockImplementation(() => {
        setTimeout(() => {
          if (mockImage.onload) {
            mockImage.onload();
          }
        }, 0);
        return mockImage as unknown as HTMLImageElement;
      });

      const resultPromise = validateImageDimensions(file);
      mockImage.onload?.();
      const result = await resultPromise;

      expect(result.valid).toBe(false);
      expect(result.error).toContain('too small');
    });

    it('should reject an image that is too large', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      const mockUrl = 'blob:test';
      createObjectURLSpy.mockReturnValue(mockUrl);

      const mockImage = {
        width: 15000,
        height: 15000,
        onload: null as (() => void) | null,
        onerror: null as (() => void) | null,
        src: ''
      };

      vi.spyOn(global, 'Image').mockImplementation(() => {
        setTimeout(() => {
          if (mockImage.onload) {
            mockImage.onload();
          }
        }, 0);
        return mockImage as unknown as HTMLImageElement;
      });

      const resultPromise = validateImageDimensions(file);
      mockImage.onload?.();
      const result = await resultPromise;

      expect(result.valid).toBe(false);
      expect(result.error).toContain('too large');
    });

    it('should handle image load errors', async () => {
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      const mockUrl = 'blob:test';
      createObjectURLSpy.mockReturnValue(mockUrl);

      const mockImage = {
        width: 0,
        height: 0,
        onload: null as (() => void) | null,
        onerror: null as (() => void) | null,
        src: ''
      };

      vi.spyOn(global, 'Image').mockImplementation(() => {
        setTimeout(() => {
          if (mockImage.onerror) {
            mockImage.onerror();
          }
        }, 0);
        return mockImage as unknown as HTMLImageElement;
      });

      const resultPromise = validateImageDimensions(file);
      mockImage.onerror?.();
      const result = await resultPromise;

      expect(result.valid).toBe(false);
      expect(result.error).toContain('corrupted');
    });
  });

  describe('validateHexColor', () => {
    it('should validate a valid 6-digit hex color', () => {
      const result = validateHexColor('#FF0000');
      expect(result.valid).toBe(true);
    });

    it('should validate a valid 3-digit hex color', () => {
      const result = validateHexColor('#F00');
      expect(result.valid).toBe(true);
    });

    it('should validate a hex color without #', () => {
      const result = validateHexColor('FF0000');
      expect(result.valid).toBe(true);
    });

    it('should reject an invalid hex color', () => {
      const result = validateHexColor('not-a-color');
      expect(result.valid).toBe(false);
      expect(result.error).toContain('Invalid hex color');
    });

    it('should reject an empty string', () => {
      const result = validateHexColor('');
      expect(result.valid).toBe(false);
    });

    it('should reject a non-string value', () => {
      const result = validateHexColor(null as unknown as string);
      expect(result.valid).toBe(false);
    });
  });

  describe('validateColorArray', () => {
    it('should validate an array of valid hex colors', () => {
      const colors = ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF'];
      const result = validateColorArray(colors);
      expect(result.valid).toBe(true);
    });

    it('should reject an empty array', () => {
      const result = validateColorArray([]);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('At least one color');
    });

    it('should reject a non-array value', () => {
      const result = validateColorArray('not-an-array' as unknown as string[]);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('array');
    });

    it('should reject an array with invalid colors', () => {
      const colors = ['#FF0000', 'invalid-color', '#0000FF'];
      const result = validateColorArray(colors);
      expect(result.valid).toBe(false);
      expect(result.error).toContain('index 1');
    });
  });
});

