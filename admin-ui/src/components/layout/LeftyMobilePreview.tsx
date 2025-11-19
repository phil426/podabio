import { useState, useEffect, useRef, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { createPortal } from 'react-dom';
import { DeviceMobile, X } from '@phosphor-icons/react';
import { CanvasViewport, type DevicePreset } from './CanvasViewport';
import styles from './lefty-mobile-preview.module.css';

interface LeftyMobilePreviewProps {
  activeColor: string;
}

const DEVICE_PRESETS: DevicePreset[] = [
  { id: 'iphone-15-pro-max', name: 'iPhone 15 Pro Max', width: 430, height: 932, aspectRatio: '19.5:9' },
  { id: 'iphone-15-pro', name: 'iPhone 15 Pro', width: 393, height: 852, aspectRatio: '19.5:9' },
  { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', width: 412, height: 915, aspectRatio: '19.3:9' },
  { id: 'pixel-8-pro', name: 'Pixel 8 Pro', width: 412, height: 915, aspectRatio: '19.5:9' },
  { id: 'iphone-15', name: 'iPhone 15', width: 390, height: 844, aspectRatio: '19.5:9' },
];

const PREVIEW_SCALE = 0.65;

export function LeftyMobilePreview({ activeColor }: LeftyMobilePreviewProps): JSX.Element {
  const [isExpanded, setIsExpanded] = useState(false);
  const [selectedDevice, setSelectedDevice] = useState<DevicePreset>(DEVICE_PRESETS[0]);
  const panelRef = useRef<HTMLDivElement>(null);
  const [position, setPosition] = useState({ x: 0, y: 0 });

  // Calculate container dimensions based on device size at 65% scale
  const containerDimensions = useMemo(() => {
    const scaledWidth = selectedDevice.width * PREVIEW_SCALE;
    const scaledHeight = selectedDevice.height * PREVIEW_SCALE;
    // Add info bar height only
    const infoBarHeight = 48; // Height of info bar
    return {
      width: scaledWidth,
      height: scaledHeight + infoBarHeight,
      deviceWidth: scaledWidth,
      deviceHeight: scaledHeight,
    };
  }, [selectedDevice]);

  const toggleExpanded = () => {
    setIsExpanded(!isExpanded);
    // Reset position when opening
    if (!isExpanded) {
      setPosition({ x: 0, y: 0 });
    }
  };

  // Escape key to close
  useEffect(() => {
    if (!isExpanded) return;

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setIsExpanded(false);
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isExpanded]);

  // Focus trap
  useEffect(() => {
    if (!isExpanded) return;

    const panel = panelRef.current;
    if (!panel) return;

    const focusableElements = panel.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0] as HTMLElement;
    const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

    const handleTabKey = (e: KeyboardEvent) => {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        if (document.activeElement === firstElement) {
          e.preventDefault();
          lastElement?.focus();
        }
      } else {
        if (document.activeElement === lastElement) {
          e.preventDefault();
          firstElement?.focus();
        }
      }
    };

    firstElement?.focus();
    panel.addEventListener('keydown', handleTabKey);

    return () => {
      panel.removeEventListener('keydown', handleTabKey);
    };
  }, [isExpanded]);

  return (
    <>
      {/* Floating Icon Button */}
      <motion.button
        type="button"
        className={styles.floatingButton}
        onClick={toggleExpanded}
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        aria-label={isExpanded ? 'Close mobile preview' : 'Open mobile preview'}
        aria-expanded={isExpanded}
        style={{
          '--active-color': activeColor,
        } as React.CSSProperties}
      >
        <DeviceMobile aria-hidden="true" size={20} weight="regular" />
      </motion.button>

      {/* Expanded Preview Panel - Portal to body */}
      {createPortal(
        <AnimatePresence>
          {isExpanded && (
            <>
              {/* Backdrop */}
              <motion.div
                className={styles.backdrop}
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.2 }}
                onClick={toggleExpanded}
              />
              {/* Preview Panel */}
              <motion.div
                ref={panelRef}
                className={styles.previewPanel}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ 
                  opacity: 1, 
                  scale: 1,
                }}
                exit={{ opacity: 0, scale: 0.9 }}
                transition={{ duration: 0.3, ease: 'easeOut' }}
                drag
                dragMomentum={false}
                dragElastic={0}
                onDrag={(_, info) => {
                  setPosition({ x: info.offset.x, y: info.offset.y });
                }}
                style={{
                  width: `${containerDimensions.width}px`,
                  height: `${containerDimensions.height}px`,
                  transform: `translate(calc(-50% + ${position.x}px), ${position.y}px)`,
                }}
                role="dialog"
                aria-modal="true"
                aria-label="Mobile preview"
              >
                {/* Information Bar */}
                <div className={styles.infoBar}>
                  <div className={styles.infoBarContent}>
                    <DeviceMobile aria-hidden="true" size={16} weight="regular" />
                    <span className={styles.deviceName}>{selectedDevice.name}</span>
                    <span className={styles.deviceDimensions}>
                      {selectedDevice.width} × {selectedDevice.height}px
                    </span>
                  </div>
                  <div className={styles.infoBarActions}>
                    <select
                      className={styles.deviceSelectCompact}
                      value={selectedDevice.id}
                      onChange={(e) => {
                        const device = DEVICE_PRESETS.find((d) => d.id === e.target.value);
                        if (device) setSelectedDevice(device);
                      }}
                      onPointerDown={(e) => e.stopPropagation()}
                      onMouseDown={(e) => e.stopPropagation()}
                    >
                      {DEVICE_PRESETS.map((device) => (
                        <option key={device.id} value={device.id}>
                          {device.name}
                        </option>
                      ))}
                    </select>
                    <button
                      type="button"
                      className={styles.closeButton}
                      onClick={(e) => {
                        e.stopPropagation();
                        toggleExpanded();
                      }}
                      onPointerDown={(e) => e.stopPropagation()}
                      onMouseDown={(e) => e.stopPropagation()}
                      aria-label="Close preview"
                    >
                      <X aria-hidden="true" size={18} weight="regular" />
                    </button>
                  </div>
                </div>

                {/* Drag Handle */}
                <div className={styles.dragHandle} />

                {/* Preview Frame */}
                <div 
                  className={styles.previewFrame}
                  style={{
                    width: `${containerDimensions.deviceWidth}px`,
                    height: `${containerDimensions.deviceHeight}px`,
                    overflow: 'hidden'
                  }}
                >
                  <CanvasViewport
                    selectedDevice={selectedDevice}
                    previewScale={PREVIEW_SCALE}
                  />
                </div>
              </motion.div>
            </>
          )}
        </AnimatePresence>,
        document.body
      )}
    </>
  );
}

