import { create } from 'zustand';

interface EasyModeState {
    isOpen: boolean;
    view: 'all' | 'layout' | 'shape' | 'spacing' | 'vibe' | 'typography';
    setOpen: (isOpen: boolean, view?: 'all' | 'layout' | 'shape' | 'spacing' | 'vibe' | 'typography') => void;
    toggle: () => void;
}

export const useEasyModeState = create<EasyModeState>((set) => ({
    isOpen: false,
    view: 'all',
    setOpen: (isOpen, view = 'all') => set({ isOpen, view }),
    toggle: () => set((state) => ({ isOpen: !state.isOpen })),
}));
