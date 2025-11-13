import { create } from 'zustand';

type ThemeInspectorState = {
  isThemeInspectorVisible: boolean;
  setThemeInspectorVisible: (visible: boolean) => void;
};

export const useThemeInspector = create<ThemeInspectorState>((set) => ({
  isThemeInspectorVisible: false,
  setThemeInspectorVisible: (visible) => set({ isThemeInspectorVisible: visible })
}));

