import { createContext, useContext, ReactNode } from 'react';

interface FeatureFlags {
  accountWorkspaceEnabled: boolean;
}

const FeatureFlagContext = createContext<FeatureFlags>({
  accountWorkspaceEnabled: true
});

export function FeatureFlagProvider({ children }: { children: ReactNode }): JSX.Element {
  const rawFeatures = (window as Window & typeof globalThis & { __FEATURES__?: Record<string, unknown> }).__FEATURES__ ?? {};

  const value: FeatureFlags = {
    accountWorkspaceEnabled: Boolean(rawFeatures.account_workspace ?? true)
  };

  return <FeatureFlagContext.Provider value={value}>{children}</FeatureFlagContext.Provider>;
}

export function useFeatureFlag(): FeatureFlags {
  return useContext(FeatureFlagContext);
}

