import { create } from 'zustand';

interface WidgetSelectionState {
  selectedWidgetId: string | null;
  selectWidget: (id: string | null) => void;
}

export const useWidgetSelection = create<WidgetSelectionState>((set) => ({
  selectedWidgetId: null,
  selectWidget: (id) => set({ selectedWidgetId: id })
}));


