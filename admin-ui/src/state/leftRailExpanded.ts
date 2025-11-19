import { create } from 'zustand';

interface LeftRailExpandedState {
  isExpanded: boolean;
  toggleExpanded: () => void;
  setExpanded: (expanded: boolean) => void;
}

const STORAGE_KEY = 'leftRailExpanded';

function loadInitialState(): boolean {
  if (typeof window === 'undefined') return false;
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored !== null) {
      return JSON.parse(stored) === true;
    }
  } catch {
    // Ignore errors
  }
  return false;
}

function saveState(expanded: boolean): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(expanded));
  } catch {
    // Ignore errors
  }
}

export const useLeftRailExpanded = create<LeftRailExpandedState>((set) => ({
  isExpanded: loadInitialState(),
  toggleExpanded: () => {
    set((state) => {
      const newExpanded = !state.isExpanded;
      saveState(newExpanded);
      return { isExpanded: newExpanded };
    });
  },
  setExpanded: (expanded: boolean) => {
    saveState(expanded);
    set({ isExpanded: expanded });
  },
}));

