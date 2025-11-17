export type TabValue = 'structure' | 'design' | 'analytics' | 'integrations' | 'settings';

export interface TabColorTheme {
  primary: string;
  light: string;
  border: string;
  text: string;
}

export const tabColors: Record<TabValue, TabColorTheme> = {
  structure: {
    primary: 'rgba(37, 99, 235, 0.12)', // Blue
    light: 'rgba(37, 99, 235, 0.06)',
    border: 'rgba(37, 99, 235, 0.2)',
    text: '#2563eb'
  },
  design: {
    primary: 'rgba(37, 99, 235, 0.12)', // Blue
    light: 'rgba(37, 99, 235, 0.06)',
    border: 'rgba(37, 99, 235, 0.2)',
    text: '#2563eb'
  },
  analytics: {
    primary: 'rgba(37, 99, 235, 0.12)', // Blue
    light: 'rgba(37, 99, 235, 0.06)',
    border: 'rgba(37, 99, 235, 0.2)',
    text: '#2563eb'
  },
  integrations: {
    primary: 'rgba(37, 99, 235, 0.12)', // Blue
    light: 'rgba(37, 99, 235, 0.06)',
    border: 'rgba(37, 99, 235, 0.2)',
    text: '#2563eb'
  },
  settings: {
    primary: 'rgba(37, 99, 235, 0.12)', // Blue
    light: 'rgba(37, 99, 235, 0.06)',
    border: 'rgba(37, 99, 235, 0.2)',
    text: '#2563eb'
  },
};

