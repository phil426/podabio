import { coreTokens } from './core';
import { semanticTokensLight } from './semantic';
import { componentTokensLight } from './component';
import type { TokenBundle } from './TokenTypes';

export const lightTokenPreset: TokenBundle = {
  core: coreTokens,
  semantic: semanticTokensLight,
  component: componentTokensLight
};

export const defaultTokenPreset = lightTokenPreset;

export * from './TokenTypes';
export { semanticTokensLight, componentTokensLight };

