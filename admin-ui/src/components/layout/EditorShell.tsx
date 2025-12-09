import { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import { LeftRailNav } from './LeftRailNav';
import { LeftyContentPanel } from './LeftyContentPanel';
import { CanvasViewport, type DevicePreset } from './CanvasViewport';
import { PropertiesPanel } from './PropertiesPanel';
import { LeftyInformationPanel } from '../panels/lefty/LeftyInformationPanel';
import { WelcomeOnboardingModal } from '../overlays/WelcomeOnboardingModal';
import { tabColors, type LeftyTabValue } from './tab-colors';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { useWidgetSelection } from '../../state/widgetSelection';
import { IntegrationModal } from '../modals/IntegrationModal';
import { SocialIconModal } from '../modals/SocialIconModal';

import './editor-shell.css';

// Lefty is now the only admin panel

export function EditorShell(): JSX.Element {
  const location = useLocation();
  const navigate = useNavigate();

  // Detect if we're on an account route and switch to account tab
  const isAccountRoute = location.pathname.startsWith('/account');

  const [activeTab, setActiveTab] = useState<LeftyTabValue>(() => {
    // If on account route, default to account tab
    if (isAccountRoute) {
      return 'account';
    }
    return 'shadow-preview';
  });

  const [selectedDevice] = useState(() => {
    // Top 5 most popular non-folding phones (2024)
    const DEVICE_PRESETS = [
      { id: 'iphone-15-pro-max', name: 'iPhone 15 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
      { id: 'iphone-15-pro', name: 'iPhone 15 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
      { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
      { id: 'pixel-8-pro', name: 'Pixel 8 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
      { id: 'iphone-15', name: 'iPhone 15', width: 390, height: 844, aspectRatio: '19.5:9' }
    ];
    return DEVICE_PRESETS[0];
  });

  // Redirect account routes to account tab
  useEffect(() => {
    if (isAccountRoute) {
      setActiveTab('account');
      // Extract sub-tab from path (e.g., /account/profile -> #profile)
      const pathSegments = location.pathname.split('/').filter(Boolean);
      if (pathSegments.length > 1 && pathSegments[0] === 'account') {
        const subTab = pathSegments[1];
        if (['profile', 'security', 'billing'].includes(subTab)) {
          navigate({ pathname: '/', hash: `#${subTab}` }, { replace: true });
        } else {
          navigate({ pathname: '/', hash: '#profile' }, { replace: true });
        }
      } else {
        // Just /account, redirect to / with profile hash
        navigate({ pathname: '/', hash: '#profile' }, { replace: true });
      }
    }
  }, [isAccountRoute, location.pathname, navigate]);

  // Clear selections when switching tabs to prevent stale inspectors
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);

  // Handle tab change
  const handleTabChange = (tab: LeftyTabValue) => {
    setActiveTab(tab);
  };

  useEffect(() => {
    // Clear social icon selection when leaving settings/integration tabs
    if (activeTab !== 'integration') {
      selectSocialIcon(null);
      selectIntegration(null);
    }
    // Clear widget selection when switching tabs
    selectWidget(null);
  }, [activeTab, selectSocialIcon, selectIntegration, selectWidget]);

  return (
    <div className="editor-shell" style={{ height: '100vh', display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
      <EditorPanels
        activeTab={activeTab as LeftyTabValue}
        onTabChange={handleTabChange as (tab: LeftyTabValue) => void}
        selectedDevice={selectedDevice}
      />
    </div>
  );
}

interface EditorPanelsProps {
  activeTab: LeftyTabValue;
  onTabChange: (tab: LeftyTabValue) => void;
  selectedDevice: DevicePreset;
}

function EditorPanels({ activeTab, onTabChange, selectedDevice }: EditorPanelsProps): JSX.Element {
  const activeColor = tabColors[activeTab];

  // Pure Grid Layout - No Resizable Panels wrapper interference
  return (
    <div className="editor-shell__panels-wrapper" style={{
      display: 'grid',
      gridTemplateColumns: 'auto minmax(0, 1fr)',
      height: '100%',
      width: '100%',
      overflow: 'hidden'
    }}>
      {/* Left Rail Column - Auto width based on content (animating rail) */}
      <div className="editor-shell__panel editor-shell__panel--left" style={{
        position: 'relative',
        height: '100%',
        zIndex: 10
      }}>
        <LeftRailNav
          activeTab={activeTab}
          onTabChange={onTabChange}
        />
      </div>

      {/* Main Content Column - Fills remaining space */}
      <div className="editor-shell__panel editor-shell__panel--center" style={{
        position: 'relative',
        height: '100%',
        width: '100%',
        overflow: 'hidden',
        minWidth: 0, /* CSS Grid blowout prevention */
        zIndex: 0
      }}>
        <LeftyContentPanel activeTab={activeTab} activeColor={activeColor} onTabChange={onTabChange} />

        {/* Info Panel conditionally rendered overlay or side-by-side if needed later */}
        {(activeTab === 'podcast' || activeTab === 'integration' || activeTab === 'analytics') && (
          <div style={{ position: 'absolute', top: 0, right: 0, bottom: 0, width: '40%', pointerEvents: 'none' }}>
            {/* Placeholder for future non-modal info panel if design requires split view again */}
          </div>
        )}
      </div>

      {/* Onboarding modal for new users */}
      <WelcomeOnboardingModal />

      {/* Integration Modals */}
      <IntegrationModal />
      <SocialIconModal />
    </div>
  );
}


