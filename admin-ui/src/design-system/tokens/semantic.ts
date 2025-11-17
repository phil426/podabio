import type { SemanticTokenGroup } from './TokenTypes';

export const semanticTokensLight: SemanticTokenGroup = {
  surface: {
    page: 'color.base.slate-50',
    panel: 'color.base.white',
    canvas: 'color.base.slate-100',
    inverse: 'color.base.slate-900',
    overlay: 'color.alpha.overlay-60'
  },
  text: {
    primary: 'color.base.slate-900',
    secondary: 'color.alpha.night-70',
    inverse: 'color.base.white',
    muted: 'color.alpha.night-50',
    accent: 'color.brand.sky'
  },
  accent: {
    primary: 'color.brand.sky',
    secondary: 'color.brand.magenta',
    outline: 'color.brand.turquoise'
  },
  state: {
    success: '#24d3a3',
    warning: '#f8a947',
    critical: '#ff6188',
    informational: '#7f9cff'
  },
  density: {
    compact: 0.85,
    cozy: 1,
    comfortable: 1.15
  },
  focus: {
    ring: 'color.elevation.shadow.focus',
    halo: 'color.alpha.shadow-strong'
  },
  divider: {
    subtle: 'rgba(9, 18, 39, 0.08)',
    strong: 'rgba(9, 18, 39, 0.14)'
  }
};

