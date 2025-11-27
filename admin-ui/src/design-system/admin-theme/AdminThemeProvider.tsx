import {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode
} from 'react';
import { adminThemeTokens, adminThemeTokensToCSSVars, type AdminThemeMode } from './tokens';

interface AdminThemeContextValue {
  mode: AdminThemeMode;
  setMode: (mode: AdminThemeMode) => void;
  toggleMode: () => void;
}

const AdminThemeContext = createContext<AdminThemeContextValue | undefined>(undefined);

interface AdminThemeProviderProps {
  children: ReactNode;
  defaultMode?: AdminThemeMode;
}

const STORAGE_KEY = 'admin-ui-theme-mode';

/**
 * AdminThemeProvider
 * 
 * CRITICAL: This provider manages admin UI theme variables (--admin-*)
 * These are completely separate from user page theme variables (--pod-*)
 * 
 * The provider:
 * 1. Manages theme state (light/dark)
 * 2. Applies CSS variables to :root based on active theme
 * 3. Persists user preference to localStorage
 * 4. Provides context for components to access/change theme
 */
export function AdminThemeProvider({
  children,
  defaultMode = 'dark'
}: AdminThemeProviderProps): JSX.Element {
  // Initialize from localStorage or default
  const [mode, setModeState] = useState<AdminThemeMode>(() => {
    if (typeof window === 'undefined') {
      return defaultMode;
    }
    
    const stored = localStorage.getItem(STORAGE_KEY) as AdminThemeMode | null;
    if (stored === 'light' || stored === 'dark') {
      return stored;
    }
    
    return defaultMode;
  });

  // Apply CSS variables to :root when theme changes
  useEffect(() => {
    const tokens = adminThemeTokens[mode];
    const cssVars = adminThemeTokensToCSSVars(tokens);
    
    const root = document.documentElement;
    
    // Apply all CSS variables
    Object.entries(cssVars).forEach(([name, value]) => {
      root.style.setProperty(name, value);
    });
    
    // Also set data attribute for CSS selectors if needed
    root.setAttribute('data-admin-theme', mode);
    
    // Persist to localStorage
    localStorage.setItem(STORAGE_KEY, mode);
  }, [mode]);

  const setMode = (newMode: AdminThemeMode) => {
    setModeState(newMode);
  };

  const toggleMode = () => {
    setModeState((current) => (current === 'dark' ? 'light' : 'dark'));
  };

  const value: AdminThemeContextValue = {
    mode,
    setMode,
    toggleMode
  };

  return (
    <AdminThemeContext.Provider value={value}>
      {children}
    </AdminThemeContext.Provider>
  );
}

/**
 * Hook to access admin theme context
 * 
 * @throws Error if used outside AdminThemeProvider
 */
export function useAdminTheme(): AdminThemeContextValue {
  const context = useContext(AdminThemeContext);
  if (!context) {
    throw new Error('useAdminTheme must be used within AdminThemeProvider');
  }
  return context;
}

