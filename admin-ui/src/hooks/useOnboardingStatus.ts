import { useState, useCallback } from 'react';

export const ONBOARDING_STORAGE_KEY = 'podabio_onboarding_completed';
export const ONBOARDING_VERSION = '1'; // Increment to show again after major updates

/**
 * Hook to check if onboarding has been completed
 */
export function useOnboardingStatus(): { completed: boolean; reset: () => void } {
  const [completed, setCompleted] = useState(() => {
    return localStorage.getItem(ONBOARDING_STORAGE_KEY) === ONBOARDING_VERSION;
  });
  
  const reset = useCallback(() => {
    localStorage.removeItem(ONBOARDING_STORAGE_KEY);
    setCompleted(false);
  }, []);
  
  return { completed, reset };
}

