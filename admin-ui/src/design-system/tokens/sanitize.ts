import type { TokenBundle, ComponentTokenGroup } from './TokenTypes';
import { defaultTokenPreset } from '.';

function deepMerge<T extends Record<string, any>>(base: T, overrides?: Partial<T>): T {
  if (!overrides) {
    return { ...base };
  }

  const result: any = { ...base };

  Object.entries(overrides).forEach(([key, value]) => {
    if (value === undefined || value === null) {
      return;
    }

    const baseValue = base[key];

    if (typeof value === 'object' && !Array.isArray(value) && baseValue && typeof baseValue === 'object' && !Array.isArray(baseValue)) {
      result[key] = deepMerge(baseValue as any, value as any);
    } else {
      result[key] = value;
    }
  });

  return result as T;
}

function deepMergeBundle(base: TokenBundle, overrides?: Partial<TokenBundle>): TokenBundle {
  return {
    core: deepMerge(base.core, overrides?.core as any) as TokenBundle['core'],
    semantic: deepMerge(base.semantic, overrides?.semantic as any) as TokenBundle['semantic'],
    component: deepMerge(base.component, overrides?.component as any) as TokenBundle['component']
  };
}

function enforceComponentDefaults(component: ComponentTokenGroup): ComponentTokenGroup {
  const merged = { ...component };

  const enforcedKeys: Array<keyof ComponentTokenGroup> = [
    'layout.topbar',
    'layout.left-rail',
    'layout.canvas',
    'layout.properties',
    'panel.drawer'
  ];

  enforcedKeys.forEach((key) => {
    const defaults = defaultTokenPreset.component[key] ?? {};
    const existing = merged[key] ?? {};

    if (Object.keys(defaults).length === 0) {
      return;
    }

    merged[key] = {
      ...defaults,
      ...existing,
      background: defaults.background
    };

    if ('borderBottom' in defaults) {
      merged[key].borderBottom = defaults.borderBottom;
    }

    if ('borderRight' in defaults) {
      merged[key].borderRight = defaults.borderRight;
    }

    if ('borderLeft' in defaults) {
      merged[key].borderLeft = defaults.borderLeft;
    }
  });

  return merged;
}

export function sanitizeTokenBundle(bundle?: TokenBundle | null): TokenBundle {
  const merged = deepMergeBundle(defaultTokenPreset, bundle ?? undefined);

  merged.semantic = {
    ...merged.semantic,
    surface: { ...defaultTokenPreset.semantic.surface },
    text: {
      ...defaultTokenPreset.semantic.text,
      accent: merged.semantic.text.accent
    },
    divider: { ...defaultTokenPreset.semantic.divider },
    focus: { ...defaultTokenPreset.semantic.focus },
    density: { ...defaultTokenPreset.semantic.density }
  };

  merged.component = enforceComponentDefaults(merged.component);

  return merged;
}


