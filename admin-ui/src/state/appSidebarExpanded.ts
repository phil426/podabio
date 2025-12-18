import { create } from 'zustand';

interface AppSidebarExpandedState {
  isExpanded: boolean;
  toggleExpanded: () => void;
  setExpanded: (expanded: boolean) => void;
}

const STORAGE_KEY = 'appSidebarExpanded';

function loadInitialState(): boolean {
  if (typeof window === 'undefined') return true;
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored !== null) {
      return JSON.parse(stored) === true;
    }
  } catch {
    // Ignore errors
  }
  // Default to expanded (true) on first load
  return true;
}

function saveState(expanded: boolean): void {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(expanded));
  } catch {
    // Ignore errors
  }
}

export const useAppSidebarExpanded = create<AppSidebarExpandedState>((set) => ({
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

