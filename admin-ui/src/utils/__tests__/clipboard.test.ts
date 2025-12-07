import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { copyToClipboard, hexToRgb, hexToHsl } from '../clipboard';

describe('clipboard utilities', () => {
  const originalClipboard = navigator.clipboard;

  beforeEach(() => {
    // @ts-expect-error override for test
    navigator.clipboard = {
      writeText: vi.fn().mockResolvedValue(undefined),
    };
  });

  afterEach(() => {
    // @ts-expect-error restore
    navigator.clipboard = originalClipboard;
    vi.restoreAllMocks();
  });

  it('copies text via modern clipboard API', async () => {
    const result = await copyToClipboard('hello');
    expect(result.success).toBe(true);
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('hello');
  });

  it('handles empty text', async () => {
    const result = await copyToClipboard('');
    expect(result.success).toBe(false);
  });

  it('converts hex to rgb', () => {
    expect(hexToRgb('#ff0000')).toBe('rgb(255, 0, 0)');
    expect(hexToRgb('#00ff00')).toBe('rgb(0, 255, 0)');
    expect(hexToRgb('invalid')).toBeNull();
  });

  it('converts hex to hsl', () => {
    expect(hexToHsl('#ff0000')).toBe('hsl(0, 100%, 50%)');
    expect(hexToHsl('#00ff00')).toBe('hsl(120, 100%, 50%)');
    expect(hexToHsl('invalid')).toBeNull();
  });
});


