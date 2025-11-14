import { create } from 'zustand';

interface IntegrationSelectionState {
  selectedIntegrationId: string | null;
  selectIntegration: (id: string | null) => void;
}

export const useIntegrationSelection = create<IntegrationSelectionState>((set) => ({
  selectedIntegrationId: null,
  selectIntegration: (id) => set({ selectedIntegrationId: id })
}));

