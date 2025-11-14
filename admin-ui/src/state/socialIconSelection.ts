import { create } from 'zustand';

interface SocialIconSelectionState {
  selectedSocialIconId: string | null;
  selectSocialIcon: (id: string | null) => void;
}

export const useSocialIconSelection = create<SocialIconSelectionState>((set) => ({
  selectedSocialIconId: null,
  selectSocialIcon: (id) => set({ selectedSocialIconId: id }),
}));

