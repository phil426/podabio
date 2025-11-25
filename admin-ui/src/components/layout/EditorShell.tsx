import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { useLocation } from 'react-router-dom';
import { Panel, PanelGroup, PanelResizeHandle, ImperativePanelHandle } from 'react-resizable-panels';

import { AccountSummaryPanel } from '../account/AccountSummaryPanel';
import { AccountWorkspace } from '../account/AccountWorkspace';
import { LeftRailNav } from './LeftRailNav';
import { LeftyContentPanel } from './LeftyContentPanel';
import { CanvasViewport, type DevicePreset } from './CanvasViewport';
import { PropertiesPanel } from './PropertiesPanel';
import { LeftyInformationPanel } from '../panels/lefty/LeftyInformationPanel';
import { tabColors, type LeftyTabValue } from './tab-colors';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { useWidgetSelection } from '../../state/widgetSelection';

import './editor-shell.css';

// Lefty is now the only admin panel

export function EditorShell(): JSX.Element {
  const location = useLocation();
  const isAccountRoute = location.pathname.startsWith('/account');
  
  const [activeTab, setActiveTab] = useState<LeftyTabValue>('layers');
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
    // Clear widget selection when leaving layers tab
    if (activeTab !== 'layers') {
      selectWidget(null);
    }
  }, [activeTab, selectSocialIcon, selectIntegration, selectWidget]);

  return (
    <div className="editor-shell">
      {isAccountRoute ? (
        <AccountPanels />
      ) : (
        <EditorPanels
          activeTab={activeTab as LeftyTabValue}
          onTabChange={handleTabChange as (tab: LeftyTabValue) => void}
          selectedDevice={selectedDevice}
        />
      )}
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
  const wrapperRef = useRef<HTMLDivElement>(null);
  const leftPanelRef = useRef<HTMLDivElement>(null);
  const centerPanelRef = useRef<HTMLDivElement>(null);
  const rightPanelRef = useRef<HTMLDivElement>(null);
  const leftPanelHandleRef = useRef<ImperativePanelHandle>(null);
  const centerPanelHandleRef = useRef<ImperativePanelHandle>(null);
  const rightPanelHandleRef = useRef<ImperativePanelHandle>(null);

  // Calculate panel sizes based on active tab (percentages that sum to 100)
  const panelSizes = useMemo(() => {
    if (activeTab === 'layers' || activeTab === 'podcast' || activeTab === 'themes' || activeTab === 'integration' || activeTab === 'analytics') {
      return {
        left: 60, // Left panel with rail + content panels
        center: 40, // Center panel for info panel
        right: 0 // Right panel hidden
      };
    }
    // Other tabs: left panel fills all space
    return {
      left: 100, // Left panel with rail + content panels (fills all space)
      center: 0, // Center panel hidden for now
      right: 0 // Right panel hidden for other tabs
    };
  }, [activeTab]);

  // Reset panel sizes when tab changes
  useEffect(() => {
    if (leftPanelHandleRef.current && centerPanelHandleRef.current && rightPanelHandleRef.current) {
      leftPanelHandleRef.current.resize(panelSizes.left);
          if (activeTab === 'layers' || activeTab === 'podcast' || activeTab === 'themes' || activeTab === 'integration' || activeTab === 'analytics') {
            centerPanelHandleRef.current.resize(panelSizes.center);
            rightPanelHandleRef.current.collapse(); // Hide right panel
          } else {
      centerPanelHandleRef.current.collapse(); // Hide center panel
        rightPanelHandleRef.current.collapse(); // Hide right panel for other tabs
      }
    }
  }, [activeTab, panelSizes]);

  // Prevent scroll event propagation to isolate panel scrolling
  // AGGRESSIVE approach: Block ALL wheel events and manually handle scrolling
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      // Find panels and their scroll containers
      const leftPanel = document.querySelector('.editor-shell__panel--left') as HTMLElement;
      const centerPanel = document.querySelector('.editor-shell__panel--center') as HTMLElement;
      const rightPanel = document.querySelector('.editor-shell__panel--right') as HTMLElement;

      // Find scroll containers - try multiple selectors
      const findScrollContainer = (panel: HTMLElement | null): HTMLElement | null => {
        if (!panel) return null;
        
        // Try Radix ScrollArea viewport first
        const radixViewport = panel.querySelector('[data-radix-scroll-area-viewport]') as HTMLElement;
        if (radixViewport) return radixViewport;
        
        // Try .viewport
        const viewport = panel.querySelector('.viewport') as HTMLElement;
        if (viewport) return viewport;
        
        // Try .scrollArea
        const scrollArea = panel.querySelector('.scrollArea') as HTMLElement;
        if (scrollArea) return scrollArea;
        
        // Try .container (for center)
        const container = panel.querySelector('[class*="container"]') as HTMLElement;
        if (container && container.scrollHeight > container.clientHeight) return container;
        
        return null;
      };

      const leftScrollContainer = findScrollContainer(leftPanel);
      const centerScrollContainer = findScrollContainer(centerPanel);
      const rightScrollContainer = findScrollContainer(rightPanel);

      const handleWheel = (e: WheelEvent) => {
        const target = e.target as HTMLElement;
        
        // Find which panel contains the target
        let scrollContainer: HTMLElement | null = null;
        let panelElement: HTMLElement | null = null;
        
        if (leftPanel?.contains(target)) {
          scrollContainer = leftScrollContainer;
          panelElement = leftPanel;
        } else if (centerPanel?.contains(target)) {
          scrollContainer = centerScrollContainer;
          panelElement = centerPanel;
        } else if (rightPanel?.contains(target)) {
          scrollContainer = rightScrollContainer;
          panelElement = rightPanel;
        }

        // If not over any panel, allow default (might be scrolling something else)
        if (!scrollContainer || !panelElement) {
          return;
        }

        // Check if this container can scroll
        const { scrollTop, scrollHeight, clientHeight } = scrollContainer;
        const canScroll = scrollHeight > clientHeight;
        
        if (!canScroll) {
          // Can't scroll this container, allow default behavior
          return;
        }

        const isAtTop = scrollTop <= 1;
        const isAtBottom = scrollTop + clientHeight >= scrollHeight - 1;
        
        // If at boundaries, allow event to propagate (might scroll parent)
        if ((e.deltaY < 0 && isAtTop) || (e.deltaY > 0 && isAtBottom)) {
          return;
        }

        // Stop propagation to prevent other panels from scrolling
        // But allow native scrolling for smooth performance
        e.stopImmediatePropagation();
        e.stopPropagation();
        // Don't preventDefault - let browser handle native smooth scrolling
      };

      // Add handler at window level with capture to catch everything
      window.addEventListener('wheel', handleWheel, { passive: false, capture: true });
      document.addEventListener('wheel', handleWheel, { passive: false, capture: true });

      return () => {
        window.removeEventListener('wheel', handleWheel, { capture: true } as EventListenerOptions);
        document.removeEventListener('wheel', handleWheel, { capture: true } as EventListenerOptions);
      };
    }, 300);

    return () => {
      clearTimeout(timeoutId);
    };
  }, []);


  return (
    <div ref={wrapperRef} className="editor-shell__panels-wrapper">
      <PanelGroup direction="horizontal" className="editor-shell__panels">
        <Panel 
          id="left-panel"
          order={1}
          ref={leftPanelHandleRef}
          defaultSize={panelSizes.left} 
          minSize={20}
          className="editor-shell__panel editor-shell__panel--left"
        >
          <div ref={leftPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', display: 'flex', flexDirection: 'row', background: 'transparent', position: 'relative', overflow: 'hidden' }}>
            <LeftRailNav 
              activeTab={activeTab} 
              onTabChange={onTabChange}
            />
            <div style={{ flex: 1, height: '100%', overflow: 'hidden', background: 'var(--pod-semantic-surface-panel, #ffffff)', position: 'relative' }}>
              <LeftyContentPanel activeTab={activeTab} activeColor={activeColor} onTabChange={onTabChange} />
            </div>
          </div>
        </Panel>
        <PanelResizeHandle 
          id="left-resizer"
          className="editor-shell__resizer"
          style={{ display: (activeTab === 'layers' || activeTab === 'podcast' || activeTab === 'analytics' || activeTab === 'themes' || activeTab === 'integration') ? 'block' : 'none' }}
        />
        <Panel 
          id="center-panel"
          order={2}
          ref={centerPanelHandleRef}
          defaultSize={panelSizes.center} 
          minSize={0}
          collapsible={true}
          className="editor-shell__panel editor-shell__panel--center"
        >
          <div ref={centerPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            {(activeTab === 'layers' || activeTab === 'podcast' || activeTab === 'themes' || activeTab === 'integration' || activeTab === 'analytics') ? (
              <LeftyInformationPanel activeColor={activeColor} activeTab={activeTab} />
            ) : null}
          </div>
        </Panel>
        <PanelResizeHandle 
          id="right-resizer"
          className="editor-shell__resizer"
          style={{ display: 'none' }}
        />
        <Panel 
          id="right-panel"
          order={3}
          ref={rightPanelHandleRef}
          defaultSize={panelSizes.right} 
          minSize={20}
          collapsible={true}
          className="editor-shell__panel editor-shell__panel--right"
        >
          <div ref={rightPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            {/* Right panel is now unused - information moved to center panel */}
          </div>
        </Panel>
      </PanelGroup>
    </div>
  );
}

function AccountPanels(): JSX.Element {
  return (
    <PanelGroup direction="horizontal" className="editor-shell__panels">
      <Panel defaultSize={60} minSize={50} className="editor-shell__panel editor-shell__panel--account-main">
        <AccountWorkspace />
      </Panel>
      <PanelResizeHandle className="editor-shell__resizer" />
      <Panel defaultSize={40} minSize={30} className="editor-shell__panel editor-shell__panel--account-aside">
        <AccountSummaryPanel />
      </Panel>
    </PanelGroup>
  );
}

