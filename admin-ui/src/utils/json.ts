/**
 * JSON utilities for safe parsing
 */

/**
 * Safely parse JSON strings with a typed fallback.
 * Returns null if parsing fails.
 */
export function safeParse<T>(value: string | null | undefined): T | null {
  if (value == null) return null;
  try {
    const parsed = JSON.parse(value);
    return parsed as T;
  } catch {
    return null;
  }
}


