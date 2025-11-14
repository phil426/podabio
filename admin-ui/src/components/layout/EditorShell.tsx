import { useState, useRef, useEffect, useMemo } from 'react';
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
    if (activeTab === 'blog') {
      return {
        left: 28,
        center: 0,
        right: 72
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
      } else if (activeTab === 'blog') {
        leftPanelHandleRef.current.resize(panelSizes.left);
        centerPanelHandleRef.current.collapse();
        rightPanelHandleRef.current.resize(panelSizes.right);
      } else {
        leftPanelHandleRef.current.resize(panelSizes.left);
        rightPanelHandleRef.current.resize(panelSizes.right);
      }
    }
  }, [activeTab, panelSizes]);

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
      {activeTab !== 'analytics' && activeTab !== 'blog' && (
        <MobilePreviewBar selectedDevice={selectedDevice} onDeviceChange={onDeviceChange} style={barStyle} />
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
          <div ref={leftPanelRef} style={{ width: '100%', height: '100%' }}>
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
          minSize={activeTab === 'analytics' ? 100 : activeTab === 'blog' ? 0 : 36}
          maxSize={activeTab === 'blog' ? 0 : undefined}
          collapsible={activeTab === 'blog'}
          className="editor-shell__panel editor-shell__panel--center"
        >
          <div ref={centerPanelRef} style={{ width: '100%', height: '100%' }}>
            {activeTab === 'analytics' ? (
              <AnalyticsDashboard activeColor={activeColor} />
            ) : (
              <CanvasViewport selectedDevice={selectedDevice} />
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
          <div ref={rightPanelRef} style={{ width: '100%', height: '100%' }}>
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

