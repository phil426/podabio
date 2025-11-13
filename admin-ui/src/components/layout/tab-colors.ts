export type TabValue = 'structure' | 'design' | 'analytics' | 'blog' | 'integrations' | 'settings';

export interface TabColorTheme {
  primary: string;
  light: string;
  border: string;
  text: string;
}

export const tabColors: Record<TabValue, TabColorTheme> = {
  structure: {
    primary: 'rgba(239, 68, 68, 0.15)', // Pastel red
    light: 'rgba(239, 68, 68, 0.06)',
    border: 'rgba(239, 68, 68, 0.25)',
    text: '#dc2626'
  },
  design: {
    primary: 'rgba(249, 115, 22, 0.15)', // Pastel orange
    light: 'rgba(249, 115, 22, 0.06)',
    border: 'rgba(249, 115, 22, 0.25)',
    text: '#ea580c'
  },
  analytics: {
    primary: 'rgba(234, 179, 8, 0.15)', // Pastel yellow
    light: 'rgba(234, 179, 8, 0.06)',
    border: 'rgba(234, 179, 8, 0.25)',
    text: '#ca8a04'
  },
  blog: {
    primary: 'rgba(34, 197, 94, 0.15)', // Pastel green
    light: 'rgba(34, 197, 94, 0.06)',
    border: 'rgba(34, 197, 94, 0.25)',
    text: '#16a34a'
  },
  integrations: {
    primary: 'rgba(59, 130, 246, 0.15)', // Pastel blue
    light: 'rgba(59, 130, 246, 0.06)',
    border: 'rgba(59, 130, 246, 0.25)',
    text: '#2563eb'
  },
  settings: {
    primary: 'rgba(139, 92, 246, 0.15)', // Pastel violet
    light: 'rgba(139, 92, 246, 0.06)',
    border: 'rgba(139, 92, 246, 0.25)',
    text: '#7c3aed'
  }
};

