import { Route, Routes } from 'react-router-dom';

import { TokenSynchronizer } from './components/app/TokenSynchronizer';
import { EditorShell } from './components/layout/EditorShell';
import { TokenProvider } from './design-system/theme/TokenProvider';
import { ThemeModeProvider } from './design-system/theme/ThemeModeProvider';
import { defaultTokenPreset } from './design-system/tokens';
import { FeatureFlagProvider } from './store/featureFlags';

export default function App(): JSX.Element {
  return (
    <TokenProvider initialTokens={defaultTokenPreset}>
      <ThemeModeProvider>
        <FeatureFlagProvider>
          <TokenSynchronizer>
            <Routes>
              <Route path="/*" element={<EditorShell />} />
            </Routes>
          </TokenSynchronizer>
        </FeatureFlagProvider>
      </ThemeModeProvider>
    </TokenProvider>
  );
}

