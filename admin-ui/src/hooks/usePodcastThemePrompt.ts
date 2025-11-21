/**
 * usePodcastThemePrompt Hook
 * Manages prompt and generator state for podcast theme generation
 * Reusable across components
 */

import { useState, useCallback, useEffect } from 'react';
import { usePageSnapshot } from '../api/page';

interface UsePodcastThemePromptReturn {
  showPrompt: boolean;
  openGenerator: () => void;
  closeGenerator: () => void;
  closePrompt: () => void;
  isGeneratorOpen: boolean;
  generatorProps: {
    coverImageUrl: string | null;
    podcastName: string | null;
    podcastDescription: string | null;
  };
}

export function usePodcastThemePrompt(): UsePodcastThemePromptReturn {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;

  const [showPrompt, setShowPrompt] = useState(false);
  const [isGeneratorOpen, setIsGeneratorOpen] = useState(false);

  // Check if podcast data is available
  const hasPodcastData = Boolean(page?.cover_image_url);

  const openGenerator = useCallback(() => {
    setShowPrompt(false);
    setIsGeneratorOpen(true);
  }, []);

  const closeGenerator = useCallback(() => {
    setIsGeneratorOpen(false);
  }, []);

  const closePrompt = useCallback(() => {
    setShowPrompt(false);
  }, []);

  // Auto-show prompt when podcast data becomes available
  useEffect(() => {
    if (hasPodcastData && page?.cover_image_url && !showPrompt && !isGeneratorOpen) {
      // Only show if we haven't shown it before (check localStorage)
      const hasShownBefore = localStorage.getItem(`podcast-theme-prompt-shown-${page.id}`);
      if (!hasShownBefore) {
        setShowPrompt(true);
        // Mark as shown (but don't persist forever - allow showing again after some time)
        localStorage.setItem(`podcast-theme-prompt-shown-${page.id}`, Date.now().toString());
      }
    }
  }, [hasPodcastData, page?.cover_image_url, page?.id, showPrompt, isGeneratorOpen]);

  return {
    showPrompt,
    openGenerator,
    closeGenerator,
    closePrompt,
    isGeneratorOpen,
    generatorProps: {
      coverImageUrl: page?.cover_image_url || null,
      podcastName: page?.podcast_name || null,
      podcastDescription: page?.podcast_description || null,
    },
  };
}

