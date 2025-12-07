/**
 * Theme Wizard State Management Hook
 * Centralized state management using useReducer for better organization
 */

import { useReducer, useCallback, useEffect } from 'react';
import type { PodcastSearchResult } from '../../../../api/page';
import { safeParse } from '../../../../utils/json';

export type TabType = 'rss' | 'photo';

export interface ThemeWizardState {
  // Tab state
  activeTab: TabType;
  
  // RSS Tab state
  searchQuery: string;
  isSearching: boolean;
  searchResults: PodcastSearchResult[];
  selectedPodcast: PodcastSearchResult | null;
  
  // Photo Tab state
  mediaLibraryOpen: boolean;
  uploadedImageUrl: string | null;
  activeImageUrl: string | null;
  
  // Color state
  colors: string[];
  draggedIndex: number | null;
  dragOverIndex: number | null;
  
  // UI state
  isGenerating: boolean;
  isShuffling: boolean;
  error: string | null;
  successMessage: string | null;
  isPreviewLoading: boolean;
}

export type ThemeWizardAction =
  | { type: 'SET_ACTIVE_TAB'; payload: TabType }
  | { type: 'SET_SEARCH_QUERY'; payload: string }
  | { type: 'SET_IS_SEARCHING'; payload: boolean }
  | { type: 'SET_SEARCH_RESULTS'; payload: PodcastSearchResult[] }
  | { type: 'SET_SELECTED_PODCAST'; payload: PodcastSearchResult | null }
  | { type: 'SET_MEDIA_LIBRARY_OPEN'; payload: boolean }
  | { type: 'SET_UPLOADED_IMAGE_URL'; payload: string | null }
  | { type: 'SET_ACTIVE_IMAGE_URL'; payload: string | null }
  | { type: 'SET_COLORS'; payload: string[] }
  | { type: 'SET_DRAGGED_INDEX'; payload: number | null }
  | { type: 'SET_DRAG_OVER_INDEX'; payload: number | null }
  | { type: 'SET_IS_GENERATING'; payload: boolean }
  | { type: 'SET_IS_SHUFFLING'; payload: boolean }
  | { type: 'SET_ERROR'; payload: string | null }
  | { type: 'SET_SUCCESS_MESSAGE'; payload: string | null }
  | { type: 'SET_IS_PREVIEW_LOADING'; payload: boolean }
  | { type: 'RESET_RSS_TAB' }
  | { type: 'RESET_PHOTO_TAB' }
  | { type: 'RESET_COLORS' }
  | { type: 'CLEAR_ERRORS' };

const STORAGE_KEY = 'theme-wizard-state';

const initialState: ThemeWizardState = {
  activeTab: 'rss',
  searchQuery: '',
  isSearching: false,
  searchResults: [],
  selectedPodcast: null,
  mediaLibraryOpen: false,
  uploadedImageUrl: null,
  activeImageUrl: null,
  colors: [],
  draggedIndex: null,
  dragOverIndex: null,
  isGenerating: false,
  isShuffling: false,
  error: null,
  successMessage: null,
  isPreviewLoading: false,
};

function themeWizardReducer(
  state: ThemeWizardState,
  action: ThemeWizardAction
): ThemeWizardState {
  switch (action.type) {
    case 'SET_ACTIVE_TAB':
      return { ...state, activeTab: action.payload };
    
    case 'SET_SEARCH_QUERY':
      return { ...state, searchQuery: action.payload };
    
    case 'SET_IS_SEARCHING':
      return { ...state, isSearching: action.payload };
    
    case 'SET_SEARCH_RESULTS':
      return { ...state, searchResults: action.payload };
    
    case 'SET_SELECTED_PODCAST':
      return { ...state, selectedPodcast: action.payload };
    
    case 'SET_MEDIA_LIBRARY_OPEN':
      return { ...state, mediaLibraryOpen: action.payload };
    
    case 'SET_UPLOADED_IMAGE_URL':
      return { ...state, uploadedImageUrl: action.payload };
    
    case 'SET_ACTIVE_IMAGE_URL':
      return { ...state, activeImageUrl: action.payload };
    
    case 'SET_COLORS':
      return { ...state, colors: action.payload };
    
    case 'SET_DRAGGED_INDEX':
      return { ...state, draggedIndex: action.payload };
    
    case 'SET_DRAG_OVER_INDEX':
      return { ...state, dragOverIndex: action.payload };
    
    case 'SET_IS_GENERATING':
      return { ...state, isGenerating: action.payload };
    
    case 'SET_IS_SHUFFLING':
      return { ...state, isShuffling: action.payload };
    
    case 'SET_ERROR':
      return { ...state, error: action.payload };
    
    case 'SET_SUCCESS_MESSAGE':
      return { ...state, successMessage: action.payload };
    
    case 'SET_IS_PREVIEW_LOADING':
      return { ...state, isPreviewLoading: action.payload };
    
    case 'RESET_RSS_TAB':
      return {
        ...state,
        searchQuery: '',
        searchResults: [],
        selectedPodcast: null,
        uploadedImageUrl: null,
      };
    
    case 'RESET_PHOTO_TAB':
      return {
        ...state,
        selectedPodcast: null,
        uploadedImageUrl: null,
        mediaLibraryOpen: false,
      };
    
    case 'RESET_COLORS':
      return {
        ...state,
        colors: [],
        draggedIndex: null,
        dragOverIndex: null,
      };
    
    case 'CLEAR_ERRORS':
      return {
        ...state,
        error: null,
        successMessage: null,
      };
    
    default:
      return state;
  }
}

function loadPersistedState(): Partial<ThemeWizardState> | null {
  if (typeof window === 'undefined') return null;
  const raw = window.sessionStorage.getItem(STORAGE_KEY);
  const parsed = safeParse<Partial<ThemeWizardState>>(raw);
  return parsed ?? null;
}

function persistState(state: Partial<ThemeWizardState>): void {
  if (typeof window === 'undefined') return;
  try {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch {
    // Ignore storage errors (quota, private mode)
  }
}

export function useThemeWizardState(initialCoverImageUrl: string | null) {
  const persistedState = loadPersistedState();

  const [state, dispatch] = useReducer(themeWizardReducer, {
    ...initialState,
    activeImageUrl: persistedState?.activeImageUrl ?? initialCoverImageUrl,
    activeTab: persistedState?.activeTab ?? initialState.activeTab,
    searchQuery: persistedState?.searchQuery ?? initialState.searchQuery,
    selectedPodcast: persistedState?.selectedPodcast ?? initialState.selectedPodcast,
    uploadedImageUrl: persistedState?.uploadedImageUrl ?? initialState.uploadedImageUrl,
    colors: persistedState?.colors ?? initialState.colors,
  });

  // Action creators for convenience
  const actions = {
    setActiveTab: useCallback((tab: TabType) => {
      dispatch({ type: 'SET_ACTIVE_TAB', payload: tab });
    }, []),
    
    setSearchQuery: useCallback((query: string) => {
      dispatch({ type: 'SET_SEARCH_QUERY', payload: query });
    }, []),
    
    setIsSearching: useCallback((isSearching: boolean) => {
      dispatch({ type: 'SET_IS_SEARCHING', payload: isSearching });
    }, []),
    
    setSearchResults: useCallback((results: PodcastSearchResult[]) => {
      dispatch({ type: 'SET_SEARCH_RESULTS', payload: results });
    }, []),
    
    setSelectedPodcast: useCallback((podcast: PodcastSearchResult | null) => {
      dispatch({ type: 'SET_SELECTED_PODCAST', payload: podcast });
    }, []),
    
    setMediaLibraryOpen: useCallback((open: boolean) => {
      dispatch({ type: 'SET_MEDIA_LIBRARY_OPEN', payload: open });
    }, []),
    
    setUploadedImageUrl: useCallback((url: string | null) => {
      dispatch({ type: 'SET_UPLOADED_IMAGE_URL', payload: url });
    }, []),
    
    setActiveImageUrl: useCallback((url: string | null) => {
      dispatch({ type: 'SET_ACTIVE_IMAGE_URL', payload: url });
    }, []),
    
    setColors: useCallback((colors: string[]) => {
      dispatch({ type: 'SET_COLORS', payload: colors });
    }, []),
    
    setDraggedIndex: useCallback((index: number | null) => {
      dispatch({ type: 'SET_DRAGGED_INDEX', payload: index });
    }, []),
    
    setDragOverIndex: useCallback((index: number | null) => {
      dispatch({ type: 'SET_DRAG_OVER_INDEX', payload: index });
    }, []),
    
    setIsGenerating: useCallback((isGenerating: boolean) => {
      dispatch({ type: 'SET_IS_GENERATING', payload: isGenerating });
    }, []),
    
    setIsShuffling: useCallback((isShuffling: boolean) => {
      dispatch({ type: 'SET_IS_SHUFFLING', payload: isShuffling });
    }, []),
    
    setError: useCallback((error: string | null) => {
      dispatch({ type: 'SET_ERROR', payload: error });
    }, []),
    
    setSuccessMessage: useCallback((message: string | null) => {
      dispatch({ type: 'SET_SUCCESS_MESSAGE', payload: message });
    }, []),
    
    setIsPreviewLoading: useCallback((loading: boolean) => {
      dispatch({ type: 'SET_IS_PREVIEW_LOADING', payload: loading });
    }, []),
    
    resetRSSTab: useCallback(() => {
      dispatch({ type: 'RESET_RSS_TAB' });
    }, []),
    
    resetPhotoTab: useCallback(() => {
      dispatch({ type: 'RESET_PHOTO_TAB' });
    }, []),
    
    resetColors: useCallback(() => {
      dispatch({ type: 'RESET_COLORS' });
    }, []),
    
    clearErrors: useCallback(() => {
      dispatch({ type: 'CLEAR_ERRORS' });
    }, []),
  };

  // Persist a minimal subset of state to sessionStorage
  useEffect(() => {
    const persistableState: Partial<ThemeWizardState> = {
      activeTab: state.activeTab,
      searchQuery: state.searchQuery,
      selectedPodcast: state.selectedPodcast,
      uploadedImageUrl: state.uploadedImageUrl,
      activeImageUrl: state.activeImageUrl,
      colors: state.colors,
    };
    persistState(persistableState);
  }, [state.activeTab, state.searchQuery, state.selectedPodcast, state.uploadedImageUrl, state.activeImageUrl, state.colors]);

  return { state, actions };
}

