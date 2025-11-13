import { createContext, useContext, useEffect, useMemo, type ReactNode } from 'react';

import { lightTokenPreset } from '../tokens';
import { useTokens } from './TokenProvider';

export type ThemeMode = 'light';

interface ThemeModeContextValue {
  mode: ThemeMode;
  resolvedMode: ThemeMode;
  setMode: (next: ThemeMode) => void;
}

const ThemeModeContext = createContext<ThemeModeContextValue | undefined>(undefined);

interface ThemeModeProviderProps {
  children: ReactNode;
}

export function ThemeModeProvider({ children }: ThemeModeProviderProps): JSX.Element {
  const { setTokens } = useTokens();

  useEffect(() => {
    setTokens(lightTokenPreset);
  }, [setTokens]);

  const value = useMemo<ThemeModeContextValue>(
    () => ({
      mode: 'light',
      resolvedMode: 'light',
      setMode: () => {
        // no-op: dark mode removed
      }
    }),
    []
  );

  return <ThemeModeContext.Provider value={value}>{children}</ThemeModeContext.Provider>;
}

export function useThemeMode(): ThemeModeContextValue {
  const context = useContext(ThemeModeContext);
  if (!context) {
    throw new Error('useThemeMode must be used within a ThemeModeProvider');
  }
  return context;
}

