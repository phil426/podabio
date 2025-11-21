// Legacy tabs for backward compatibility
export type LegacyTabValue = 'structure' | 'design' | 'analytics' | 'integrations' | 'settings';

// New Lefty tabs
export type LeftyTabValue = 'layers' | 'podcast' | 'integration' | 'analytics' | 'themes';

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
  // Legacy tabs (kept for type compatibility, but not used in UI)
  structure: unifiedAccent,
  design: unifiedAccent,
  integrations: unifiedAccent,
  settings: unifiedAccent,
  // New Lefty tabs
  'layers': unifiedAccent,
  'podcast': unifiedAccent,
  'integration': unifiedAccent,
  'analytics': unifiedAccent,
  'themes': unifiedAccent,
};

