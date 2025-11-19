import { useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';

import { useWidgetSelection } from '../../../state/widgetSelection';
import { useSocialIconSelection } from '../../../state/socialIconSelection';
import { useIntegrationSelection } from '../../../state/integrationSelection';
import { WidgetInspector } from '../WidgetInspector';
import { ProfileInspector } from '../ProfileInspector';
import { FooterInspector } from '../FooterInspector';
import { PodcastPlayerInspector } from '../PodcastPlayerInspector';
import { FeaturedBlockInspector } from '../FeaturedBlockInspector';
import { SocialIconInspector } from '../SocialIconInspector';
import { IntegrationInspector } from '../IntegrationInspector';
import { useThemeInspector } from '../../../state/themeInspector';
import { ThemeEditorPanel } from '../ThemeEditorPanel';
import { useThemeLibraryQuery, type ThemeLibraryResult } from '../../../api/themes';
import { usePageSnapshot } from '../../../api/page';
import { queryKeys } from '../../../api/utils';
import type { ThemeRecord } from '../../../api/types';
import type { TokenBundle, SemanticTokenGroup } from '../../../design-system/tokens';

import { type TabColorTheme, type LeftyTabValue } from '../../layout/tab-colors';

import styles from './lefty-properties-panel.module.css';

interface LeftyPropertiesPanelProps {
  activeColor: TabColorTheme;
  activeTab?: LeftyTabValue;
}

export function LeftyPropertiesPanel({ activeColor, activeTab = 'layers' }: LeftyPropertiesPanelProps): JSX.Element {
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const showThemeInspector = useThemeInspector((state) => state.isThemeInspectorVisible);
  const queryClient = useQueryClient();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const { data: snapshot } = usePageSnapshot();

  const activeTheme = useMemo(
    () => deriveActiveTheme(themeLibrary, snapshot?.page?.theme_id ?? null),
    [themeLibrary, snapshot?.page?.theme_id]
  );

  // Find selected widget to check if it's featured
  const selectedWidget = useMemo(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const isFeaturedWidget = selectedWidget?.is_featured === 1;

  // Determine which inspector to show based on activeTab and selection state
  // This ensures inspectors from other tabs don't persist when switching tabs
  let inspector: JSX.Element | null = null;

  // Gate inspectors by activeTab - Lefty-specific tabs only
  const isLeftyLayerTab = activeTab === 'layers';
  const isLeftyIntegrationTab = activeTab === 'integration';

  if (isLeftyLayerTab) {
    // Layers tab: Show widget/page inspectors or default to Profile
    // CRITICAL: Check for 'page:footer' FIRST with exact match before any other checks
    if (selectedWidgetId === 'page:footer') {
      inspector = <FooterInspector activeColor={activeColor} />;
    } else if (selectedWidgetId?.startsWith('page:')) {
      if (selectedWidgetId === 'page:profile') {
        inspector = <ProfileInspector focus="profile" activeColor={activeColor} />;
      } else if (selectedWidgetId === 'page:podcast-player') {
        inspector = <PodcastPlayerInspector activeColor={activeColor} />;
      } else {
        // Legacy support for old IDs
        if (selectedWidgetId === 'page:short-bio') {
          inspector = <ProfileInspector focus="bio" activeColor={activeColor} />;
        } else {
          inspector = <ProfileInspector focus="image" activeColor={activeColor} />;
        }
      }
    } else if (selectedWidgetId) {
      // Show FeaturedBlockInspector if widget is featured, otherwise show WidgetInspector
      if (isFeaturedWidget) {
        inspector = (
          <>
            <FeaturedBlockInspector activeColor={activeColor} />
            <WidgetInspector activeColor={activeColor} />
          </>
        );
      } else {
        inspector = <WidgetInspector activeColor={activeColor} />;
      }
    } else if (activeTab === 'layers') {
      // Default to Profile inspector when on layers tab and nothing is selected
      inspector = <ProfileInspector focus="profile" activeColor={activeColor} />;
    }
    // Note: ThemeEditorPanel is handled separately via showThemeInspector state
  } else if (isLeftyIntegrationTab) {
    // Integration tab: Show IntegrationInspector only if integration is selected
    if (selectedIntegrationId !== null) {
      inspector = <IntegrationInspector activeColor={activeColor} />;
    }
    // No default inspector for integrations tab
  } else if (activeTab === 'analytics' || activeTab === 'preview') {
    // Analytics/Preview tab: No inspector (right panel is collapsed)
    inspector = null;
  } else if (activeTab === 'colors' || activeTab === 'typography' || activeTab === 'special-effects' || activeTab === 'podcast') {
    // These tabs don't need inspectors in the center panel
    inspector = null;
  }

  return (
    <div 
      className={styles.container} 
      aria-label="Properties panel"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <div className={styles.scrollArea}>
        {showThemeInspector && (
          <>
            <ThemeEditorPanel 
              activeColor={activeColor} 
              theme={activeTheme}
              onSave={async () => {
                // Explicitly refetch to ensure preview updates immediately
                await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
              }}
            />
          </>
        )}

        {inspector}
      </div>
    </div>
  );
}

function deriveActiveTheme(
  library: ThemeLibraryResult | undefined,
  themeId: number | null
): ThemeRecord | null {
  const systemThemes = library?.system ?? [];
  const userThemes = library?.user ?? [];

  if (themeId == null) {
    return systemThemes[0] ?? userThemes[0] ?? null;
  }

  const combined = [...userThemes, ...systemThemes];
  return combined.find((theme) => theme.id === themeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
}

