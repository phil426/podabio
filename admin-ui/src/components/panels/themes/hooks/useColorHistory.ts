/**
 * Color History Hook for Undo/Redo
 * Tracks color array history and provides undo/redo functionality
 */

import { useState, useCallback, useRef, useEffect } from 'react';

const MAX_HISTORY_SIZE = 50;

interface ColorHistoryState {
  past: string[][];
  present: string[];
  future: string[];
}

export interface UseColorHistoryResult {
  colors: string[];
  canUndo: boolean;
  canRedo: boolean;
  setColors: (colors: string[]) => void;
  undo: () => void;
  redo: () => void;
  clearHistory: () => void;
}

export function useColorHistory(initialColors: string[] = []): UseColorHistoryResult {
  const [history, setHistory] = useState<ColorHistoryState>({
    past: [],
    present: initialColors,
    future: [],
  });

  // Track if we should record history (prevents recording during undo/redo)
  const shouldRecordHistory = useRef(true);
  const previousColorsRef = useRef<string[]>(initialColors);
  const isInternalUpdateRef = useRef(false);

  // Record history when colors change (but not during undo/redo)
  useEffect(() => {
    if (!shouldRecordHistory.current) {
      shouldRecordHistory.current = true;
      previousColorsRef.current = history.present;
      return;
    }

    const currentColors = history.present;
    const previousColors = previousColorsRef.current;

    // Only record if colors actually changed
    if (JSON.stringify(currentColors) !== JSON.stringify(previousColors)) {
      setHistory((prev) => {
        const newPast = [...prev.past, previousColors];
        
        // Limit history size
        const trimmedPast = newPast.length > MAX_HISTORY_SIZE 
          ? newPast.slice(-MAX_HISTORY_SIZE)
          : newPast;

        return {
          past: trimmedPast,
          present: currentColors,
          future: [], // Clear future when new change is made
        };
      });

      previousColorsRef.current = currentColors;
    }
  }, [history.present]);

  const setColors = useCallback((colors: string[]) => {
    setHistory((prev) => {
      // Don't record history if this is an internal update (undo/redo)
      if (isInternalUpdateRef.current) {
        isInternalUpdateRef.current = false;
        return {
          ...prev,
          present: colors,
        };
      }
      
      // Normal update - will be recorded by useEffect
      return {
        ...prev,
        present: colors,
      };
    });
  }, []);

  const undo = useCallback(() => {
    setHistory((prev) => {
      if (prev.past.length === 0) {
        return prev;
      }

      const previous = prev.past[prev.past.length - 1];
      const newPast = prev.past.slice(0, -1);

      isInternalUpdateRef.current = true;
      previousColorsRef.current = prev.present;

      return {
        past: newPast,
        present: previous,
        future: [prev.present, ...prev.future],
      };
    });
  }, []);

  const redo = useCallback(() => {
    setHistory((prev) => {
      if (prev.future.length === 0) {
        return prev;
      }

      const next = prev.future[0];
      const newFuture = prev.future.slice(1);

      isInternalUpdateRef.current = true;
      previousColorsRef.current = prev.present;

      return {
        past: [...prev.past, prev.present],
        present: next,
        future: newFuture,
      };
    });
  }, []);

  const clearHistory = useCallback(() => {
    setHistory((prev) => ({
      past: [],
      present: prev.present,
      future: [],
    }));
  }, []);

  return {
    colors: history.present,
    canUndo: history.past.length > 0,
    canRedo: history.future.length > 0,
    setColors,
    undo,
    redo,
    clearHistory,
  };
}

