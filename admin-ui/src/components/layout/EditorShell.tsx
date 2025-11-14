import { useState, useRef, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { Panel, PanelGroup, PanelResizeHandle } from 'react-resizable-panels';

import { AccountSummaryPanel } from '../account/AccountSummaryPanel';
import { AccountWorkspace } from '../account/AccountWorkspace';
import { LeftRail } from './LeftRail';
import { CanvasViewport } from './CanvasViewport';
import { PropertiesPanel } from './PropertiesPanel';
import { TopBar } from './TopBar';
import { TabBar } from './TabBar';
import { MobilePreviewBar } from './MobilePreviewBar';
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
  const [barStyle, setBarStyle] = useState<React.CSSProperties>({});

  useEffect(() => {
    const updateBarPosition = () => {
      if (leftPanelRef.current && centerPanelRef.current && wrapperRef.current) {
        const leftRect = leftPanelRef.current.getBoundingClientRect();
        const centerRect = centerPanelRef.current.getBoundingClientRect();
        const wrapperRect = wrapperRef.current.getBoundingClientRect();
        
        const left = leftRect.left - wrapperRect.left + leftRect.width;
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
    if (wrapperRef.current) resizeObserver.observe(wrapperRef.current);

    window.addEventListener('resize', updateBarPosition);

    return () => {
      resizeObserver.disconnect();
      window.removeEventListener('resize', updateBarPosition);
    };
  }, []);

  return (
    <div ref={wrapperRef} className="editor-shell__panels-wrapper">
      <MobilePreviewBar selectedDevice={selectedDevice} onDeviceChange={onDeviceChange} style={barStyle} />
      <PanelGroup direction="horizontal" className="editor-shell__panels">
        <Panel defaultSize={22} minSize={18} className="editor-shell__panel editor-shell__panel--left">
          <div ref={leftPanelRef} style={{ width: '100%', height: '100%' }}>
            <LeftRail 
              activeTab={activeTab} 
              onTabChange={onTabChange}
              activeColor={activeColor}
            />
          </div>
        </Panel>
        <PanelResizeHandle className="editor-shell__resizer" />
        <Panel defaultSize={46} minSize={36} className="editor-shell__panel editor-shell__panel--center">
          <div ref={centerPanelRef} style={{ width: '100%', height: '100%' }}>
            <CanvasViewport selectedDevice={selectedDevice} />
          </div>
        </Panel>
        <PanelResizeHandle className="editor-shell__resizer" />
        <Panel defaultSize={32} minSize={26} className="editor-shell__panel editor-shell__panel--right">
          <PropertiesPanel activeColor={activeColor} activeTab={activeTab} />
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

