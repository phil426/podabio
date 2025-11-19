// Legacy tabs for backward compatibility
export type LegacyTabValue = 'structure' | 'design' | 'analytics' | 'integrations' | 'settings';

// New Lefty tabs
export type LeftyTabValue = 'layers' | 'colors' | 'special-effects' | 'podcast' | 'integration' | 'analytics' | 'preview';

// Combined type for all tabs
export type TabValue = LegacyTabValue | LeftyTabValue;

export interface TabColorTheme {
  primary: string;
  light: string;
  border: string;
  text: string;
}

// Unified accent color for all tabs - bright cyan that works well on dark backgrounds
const unifiedAccent = {
  primary: 'rgba(34, 211, 238, 0.2)', // Bright cyan background
  light: 'rgba(34, 211, 238, 0.1)',
  border: 'rgba(34, 211, 238, 0.3)',
  text: '#22d3ee' // Bright cyan text
};

export const tabColors: Record<TabValue, TabColorTheme> = {
  // Legacy tabs
  structure: unifiedAccent,
  design: unifiedAccent,
  analytics: unifiedAccent,
  integrations: unifiedAccent,
  settings: unifiedAccent,
  // New Lefty tabs
  'layers': unifiedAccent,
  'colors': unifiedAccent,
  'special-effects': unifiedAccent,
  'podcast': unifiedAccent,
  'integration': unifiedAccent,
  'preview': unifiedAccent,
};

