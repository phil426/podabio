import { useEffect, useRef, useCallback } from 'react';

type DebouncedFn<T extends any[]> = ((...args: T) => void) & { cancel: () => void };

export function useDebouncedCallback<T extends any[]>(
  callback: (...args: T) => void,
  delayMs: number
): DebouncedFn<T> {
  const timeoutRef = useRef<number | null>(null);
  const callbackRef = useRef(callback);

  useEffect(() => {
    callbackRef.current = callback;
  }, [callback]);

  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        window.clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  const cancel = useCallback(() => {
    if (timeoutRef.current) {
      window.clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }
  }, []);

  const debounced = useCallback((...args: T) => {
    if (timeoutRef.current) {
      window.clearTimeout(timeoutRef.current);
    }
    timeoutRef.current = window.setTimeout(() => {
      callbackRef.current(...args);
    }, delayMs);
  }, [delayMs]);

  (debounced as any).cancel = cancel;

  return debounced as DebouncedFn<T>;
}


