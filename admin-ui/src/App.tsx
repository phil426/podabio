import { Route, Routes } from 'react-router-dom';

import { TokenSynchronizer } from './components/app/TokenSynchronizer';
import { EditorShell } from './components/layout/EditorShell';
import { ColorPickerDemo } from './components/demo/ColorPickerDemo';
import { PagePropertiesToolbarDemo } from './components/demo/PagePropertiesToolbarDemo';
import { PageSettingsDemo } from './components/demo/PageSettingsDemo';
import { DocumentationViewer } from './components/docs/DocumentationViewer';
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
              <Route path="/demo/color-picker" element={<ColorPickerDemo />} />
              <Route path="/demo/color-picker.php" element={<ColorPickerDemo />} />
              <Route path="/demo/page-properties-toolbar" element={<PagePropertiesToolbarDemo />} />
              <Route path="/demo/page-properties-toolbar.php" element={<PagePropertiesToolbarDemo />} />
              <Route path="/demo/page-settings" element={<PageSettingsDemo />} />
              <Route path="/demo/page-settings.php" element={<PageSettingsDemo />} />
              <Route path="/studio-docs" element={<DocumentationViewer />} />
              <Route path="/studio-docs.php" element={<DocumentationViewer />} />
              <Route path="/*" element={<EditorShell />} />
            </Routes>
          </TokenSynchronizer>
        </FeatureFlagProvider>
      </ThemeModeProvider>
    </TokenProvider>
  );
}

