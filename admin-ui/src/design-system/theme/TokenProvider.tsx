import {
  createContext,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode
} from 'react';

import type { TokenBundle } from '../tokens';

interface TokenContextValue {
  tokens: TokenBundle;
  setTokens: (next: TokenBundle) => void;
}

const TokenContext = createContext<TokenContextValue | undefined>(undefined);

interface TokenProviderProps {
  initialTokens: TokenBundle;
  children: ReactNode;
}

export function TokenProvider({ initialTokens, children }: TokenProviderProps): JSX.Element {
  const [tokens, setTokens] = useState<TokenBundle>(initialTokens);

  // NOTE: We do NOT apply tokens to the document root here.
  // Theme tokens are for the FRONTEND/public page only, not the admin UI.
  // The admin UI uses fixed CSS values with fallbacks (see global.css and component CSS files).
  // If you need to apply tokens for preview purposes, do it in a specific preview container/iframe.

  const value = useMemo<TokenContextValue>(
    () => ({
      tokens,
      setTokens
    }),
    [tokens]
  );

  return <TokenContext.Provider value={value}>{children}</TokenContext.Provider>;
}

export function useTokens(): TokenContextValue {
  const context = useContext(TokenContext);
  if (!context) {
    throw new Error('useTokens must be used within a TokenProvider');
  }
  return context;
}

export function applyTokensToDocument(bundle: TokenBundle): void {
  if (typeof document === 'undefined') {
    return;
  }
  const cssVars = buildCssVariableMap(bundle);
  const root = document.documentElement;

  Object.entries(cssVars).forEach(([name, value]) => {
    root.style.setProperty(name, value);
  });
}

function buildCssVariableMap(bundle: TokenBundle): Record<string, string> {
  const map: Record<string, string> = {};

  const coreVars = flattenObject('core', bundle.core);
  const semanticVars = flattenObject('semantic', bundle.semantic);
  const componentVars = flattenObject('component', bundle.component);

  Object.entries(coreVars).forEach(([path, value]) => {
    map[toCssVariableName(path)] = formatCoreValue(path, value);
  });

  Object.entries(semanticVars).forEach(([path, value]) => {
    const resolved = resolveReference(value, map) ?? value;
    map[toCssVariableName(path)] =
      typeof resolved === 'number' ? String(resolved) : (resolved as string);
  });

  Object.entries(componentVars).forEach(([path, value]) => {
    const normalized = typeof value === 'number' ? value.toString() : value;
    const resolved = resolveReference(normalized, map) ?? normalized;
    map[toCssVariableName(path)] =
      typeof resolved === 'number' ? String(resolved) : (resolved as string);
  });

  return map;
}

function flattenObject(base: string, input: unknown): Record<string, string | number> {
  const result: Record<string, string | number> = {};

  const recurse = (path: string[], value: unknown) => {
    if (value === null || value === undefined) {
      return;
    }

    if (typeof value === 'object' && !Array.isArray(value)) {
      Object.entries(value as Record<string, unknown>).forEach(([key, nested]) => {
        recurse([...path, key], nested);
      });
    } else {
      result[[base, ...path].join('.')] = value as string | number;
    }
  };

  recurse([], input);
  return result;
}

function toCssVariableName(path: string): string {
  return `--pod-${path.replace(/\./g, '-')}`;
}

function resolveReference(
  value: string | number,
  existing: Record<string, string>
): string | undefined {
  if (typeof value !== 'string') {
    return undefined;
  }

  const normalized = normalizeReference(value);
  if (!normalized) {
    return undefined;
  }

  const cssVar = toCssVariableName(normalized);
  if (existing[cssVar]) {
    return `var(${cssVar})`;
  }

  return undefined;
}

function normalizeReference(value: string): string | undefined {
  if (value.startsWith('core.') || value.startsWith('semantic.') || value.startsWith('component.')) {
    return value;
  }

  if (value.startsWith('color.')) {
    return `core.${value}`;
  }

  if (value.startsWith('space.')) {
    return `core.${value}`;
  }

  if (value.startsWith('type.')) {
    return `core.typography.${value.replace('type.', '')}`;
  }

  return undefined;
}

function formatCoreValue(path: string, value: string | number): string {
  if (typeof value !== 'string') {
    return String(value);
  }

  if (path.startsWith('core.space.scale')) {
    return `${value}rem`;
  }

  if (path.startsWith('core.typography.scale')) {
    return `${value}rem`;
  }

  if (path.startsWith('core.motion.duration')) {
    return `${value}ms`;
  }

  return value;
}

