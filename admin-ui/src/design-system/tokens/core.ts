import type { CoreTokenGroup } from './TokenTypes';

export const coreTokens: CoreTokenGroup = {
  color: {
    'base.slate-950': '#04070f',
    'base.slate-900': '#091227',
    'base.slate-850': '#101b36',
    'base.slate-800': '#172447',
    'base.slate-700': '#212f59',
    'base.slate-300': '#d6dff9',
    'base.slate-200': '#e3e9ff',
    'base.slate-150': '#ecf1ff',
    'base.slate-100': '#f5f7ff',
    'base.slate-50': '#fafcff',
    'base.white': '#ffffff',
    'brand.sunrise': '#fbcf7d',
    'brand.sky': '#5fd7ff',
    'brand.turquoise': '#48f2d3',
    'brand.magenta': '#ff75d1',
    'brand.violet': '#9f8eff',
    'brand.glow-amber': '#f9a742',
    'alpha.overlay-60': 'rgba(9, 18, 39, 0.6)',
    'alpha.shadow-ambient': 'rgba(9, 18, 39, 0.14)',
    'alpha.shadow-strong': 'rgba(9, 18, 39, 0.28)',
    'alpha.night-70': 'rgba(9, 18, 39, 0.72)',
    'alpha.night-50': 'rgba(9, 18, 39, 0.55)',
    'alpha.white-80': 'rgba(255, 255, 255, 0.85)',
    'alpha.white-60': 'rgba(255, 255, 255, 0.62)',
    'alpha.midnight-70': 'rgba(4, 12, 26, 0.72)'
  },
  typography: {
    font: {
      heading: "'Nunito Sans Expanded', 'Nunito Sans', -apple-system, BlinkMacSystemFont, sans-serif",
      body: "'Space Mono', 'Courier New', monospace",
      metatext: "'Space Mono', 'Courier New', monospace"
    },
    scale: {
      xl: 2.488,
      lg: 1.777,
      md: 1.333,
      sm: 1.111,
      xs: 0.889
    },
    weight: {
      normal: 400,
      medium: 500,
      semibold: 600,
      bold: 700
    },
    lineHeight: {
      tight: 1.2,
      normal: 1.5,
      relaxed: 1.7
    },
    tracking: {
      tight: -0.01,
      normal: 0,
      wide: 0.02
    }
  },
  space: {
    scale: {
      '2xs': 0.25,
      xs: 0.5,
      sm: 0.75,
      md: 1,
      lg: 1.5,
      xl: 2,
      '2xl': 3
    }
  },
  shape: {
    radius: {
      none: '0px',
      sm: '6px',
      md: '12px',
      lg: '18px',
      pill: '9999px'
    },
    borderWidth: {
      hairline: '1px',
      thin: '2px',
      thick: '4px'
    }
  },
  motion: {
    duration: {
      instant: 80,
      fast: 150,
      normal: 250,
      slow: 400
    },
    easing: {
      standard: 'cubic-bezier(0.4, 0, 0.2, 1)',
      decelerate: 'cubic-bezier(0.0, 0, 0.2, 1)',
      accelerate: 'cubic-bezier(0.4, 0, 1, 1)'
    }
  },
  elevation: {
    shadow: {
      level0: 'none',
      level1: '0 8px 18px rgba(9, 18, 39, 0.12)',
      level2: '0 20px 48px rgba(9, 18, 39, 0.22)',
      focus: '0 0 0 3px rgba(37, 99, 235, 0.4)'
    },
    zIndex: {
      base: 0,
      panel: 10,
      overlay: 100,
      toaster: 1000
    }
  }
};

