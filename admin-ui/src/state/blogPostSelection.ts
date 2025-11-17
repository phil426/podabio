import { create } from 'zustand';

interface BlogPostSelectionState {
  selectedBlogPostId: number | null;
  selectBlogPost: (id: number | null) => void;
}

export const useBlogPostSelection = create<BlogPostSelectionState>((set) => ({
  selectedBlogPostId: null,
  selectBlogPost: (id) => set({ selectedBlogPostId: id })
}));

