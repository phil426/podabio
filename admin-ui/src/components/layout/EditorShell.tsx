import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { useLocation } from 'react-router-dom';
import { Panel, PanelGroup, PanelResizeHandle, ImperativePanelHandle } from 'react-resizable-panels';

import { AccountSummaryPanel } from '../account/AccountSummaryPanel';
import { AccountWorkspace } from '../account/AccountWorkspace';
import { LeftRail } from './LeftRail';
import { CanvasViewport } from './CanvasViewport';
import { PropertiesPanel } from './PropertiesPanel';
import { TopBar } from './TopBar';
import { TabBar } from './TabBar';
import { MobilePreviewBar } from './MobilePreviewBar';
import { AnalyticsDashboard } from '../panels/AnalyticsDashboard';
import { tabColors, type TabValue } from './tab-colors';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { useIntegrationSelection } from '../../state/integrationSelection';

import './editor-shell.css';

export function EditorShell(): JSX.Element {
  const location = useLocation();
  const isAccountRoute = location.pathname.startsWith('/account');
  const [activeTab, setActiveTab] = useState<TabValue>('structure');
  const [selectedDevice, setSelectedDevice] = useState(() => {
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

  useEffect(() => {
    // Clear social icon selection when leaving settings tab
    if (activeTab !== 'settings') {
      selectSocialIcon(null);
    }
    // Clear integration selection when leaving integrations tab
    if (activeTab !== 'integrations') {
      selectIntegration(null);
    }
    // Note: Widget selection is already cleared in LeftRail when leaving structure/design tabs
  }, [activeTab, selectSocialIcon, selectIntegration]);

  return (
    <div className="editor-shell">
      <TopBar />
      {isAccountRoute ? (
        <AccountPanels />
      ) : (
        <>
          <TabBar activeTab={activeTab} onTabChange={setActiveTab} />
          <EditorPanels activeTab={activeTab} onTabChange={setActiveTab} selectedDevice={selectedDevice} onDeviceChange={setSelectedDevice} />
        </>
      )}
    </div>
  );
}

interface DevicePreset {
  id: string;
  name: string;
  width: number;
  height: number;
  aspectRatio: string;
}

interface EditorPanelsProps {
  activeTab: TabValue;
  onTabChange: (tab: TabValue) => void;
  selectedDevice: DevicePreset;
  onDeviceChange: (device: DevicePreset) => void;
}

function EditorPanels({ activeTab, onTabChange, selectedDevice, onDeviceChange }: EditorPanelsProps): JSX.Element {
  const [previewScale, setPreviewScale] = useState(0.75);
  const activeColor = tabColors[activeTab];
  const wrapperRef = useRef<HTMLDivElement>(null);
  const leftPanelRef = useRef<HTMLDivElement>(null);
  const centerPanelRef = useRef<HTMLDivElement>(null);
  const rightPanelRef = useRef<HTMLDivElement>(null);
  const leftPanelHandleRef = useRef<ImperativePanelHandle>(null);
  const centerPanelHandleRef = useRef<ImperativePanelHandle>(null);
  const rightPanelHandleRef = useRef<ImperativePanelHandle>(null);
  const [barStyle, setBarStyle] = useState<React.CSSProperties>({});

  // Calculate panel sizes based on active tab
  const panelSizes = useMemo(() => {
    if (activeTab === 'analytics') {
      return {
        left: 0,
        center: 100,
        right: 0
      };
    }
    return {
      left: 22,
      center: 46,
      right: 32
    };
  }, [activeTab]);

  // Reset panel sizes when tab changes
  useEffect(() => {
    if (leftPanelHandleRef.current && rightPanelHandleRef.current && centerPanelHandleRef.current) {
      if (activeTab === 'analytics') {
        leftPanelHandleRef.current.collapse();
        rightPanelHandleRef.current.collapse();
      } else {
        leftPanelHandleRef.current.resize(panelSizes.left);
        rightPanelHandleRef.current.resize(panelSizes.right);
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

  useEffect(() => {
    const updateBarPosition = () => {
      if (leftPanelRef.current && centerPanelRef.current && rightPanelRef.current && wrapperRef.current) {
        const centerRect = centerPanelRef.current.getBoundingClientRect();
        const rightRect = rightPanelRef.current.getBoundingClientRect();
        const wrapperRect = wrapperRef.current.getBoundingClientRect();
        
        // Position bar to span only the center panel, not overlaying resizers
        // Start from the left edge of the center panel (after left resizer)
        // End at the right edge of the center panel (before right resizer)
        const left = centerRect.left - wrapperRect.left;
        const right = wrapperRect.right - centerRect.right;
        
        setBarStyle({
          left: `${left}px`,
          right: `${right}px`,
        });
      }
    };

    updateBarPosition();
    const resizeObserver = new ResizeObserver(updateBarPosition);
    
    if (leftPanelRef.current) resizeObserver.observe(leftPanelRef.current);
    if (centerPanelRef.current) resizeObserver.observe(centerPanelRef.current);
    if (rightPanelRef.current) resizeObserver.observe(rightPanelRef.current);
    if (wrapperRef.current) resizeObserver.observe(wrapperRef.current);

    window.addEventListener('resize', updateBarPosition);

    return () => {
      resizeObserver.disconnect();
      window.removeEventListener('resize', updateBarPosition);
    };
  }, []);

  return (
    <div ref={wrapperRef} className="editor-shell__panels-wrapper">
      {activeTab !== 'analytics' && (
        <MobilePreviewBar 
          selectedDevice={selectedDevice} 
          onDeviceChange={onDeviceChange} 
          previewScale={previewScale}
          onScaleChange={setPreviewScale}
          style={barStyle} 
        />
      )}
      <PanelGroup direction="horizontal" className="editor-shell__panels">
        <Panel 
          id="left-panel"
          order={1}
          ref={leftPanelHandleRef}
          defaultSize={panelSizes.left} 
          minSize={activeTab === 'analytics' ? 0 : 18}
          maxSize={activeTab === 'analytics' ? 0 : undefined}
          collapsible={activeTab === 'analytics'}
          className="editor-shell__panel editor-shell__panel--left"
        >
          <div ref={leftPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', display: 'flex', flexDirection: 'column', background: 'transparent' }}>
            <LeftRail 
              activeTab={activeTab} 
              onTabChange={onTabChange}
              activeColor={activeColor}
            />
          </div>
        </Panel>
        <PanelResizeHandle 
          id="left-resizer"
          className="editor-shell__resizer"
          style={{ display: activeTab === 'analytics' ? 'none' : 'block' }}
        />
        <Panel 
          id="center-panel"
          order={2}
          ref={centerPanelHandleRef}
          defaultSize={panelSizes.center} 
          minSize={activeTab === 'analytics' ? 100 : 36}
          className="editor-shell__panel editor-shell__panel--center"
        >
          <div ref={centerPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            {activeTab === 'analytics' ? (
              <AnalyticsDashboard activeColor={activeColor} />
            ) : (
              <CanvasViewport selectedDevice={selectedDevice} previewScale={previewScale} />
            )}
          </div>
        </Panel>
        <PanelResizeHandle 
          id="right-resizer"
          className="editor-shell__resizer"
          style={{ display: activeTab === 'analytics' ? 'none' : 'block' }}
        />
        <Panel 
          id="right-panel"
          order={3}
          ref={rightPanelHandleRef}
          defaultSize={panelSizes.right} 
          minSize={activeTab === 'analytics' ? 0 : 26}
          maxSize={activeTab === 'analytics' ? 0 : undefined}
          collapsible={activeTab === 'analytics'}
          className="editor-shell__panel editor-shell__panel--right"
        >
          <div ref={rightPanelRef} style={{ width: '100%', height: '100%', maxHeight: '100%', display: 'flex', flexDirection: 'column' }}>
            <PropertiesPanel activeColor={activeColor} activeTab={activeTab} />
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

