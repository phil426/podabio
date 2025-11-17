import { useMemo, type CSSProperties, useState, useEffect, useRef } from 'react';

import { usePageSnapshot } from '../../api/page';
import styles from './canvas-viewport.module.css';

export interface DevicePreset {
  id: string;
  name: string;
  width: number;
  height: number;
  aspectRatio: string;
}

interface CanvasViewportProps {
  selectedDevice: DevicePreset;
  previewScale?: number;
}

export function CanvasViewport({ selectedDevice, previewScale = 0.75 }: CanvasViewportProps): JSX.Element {
  const { data, isLoading, isError, error } = usePageSnapshot();
  const [iframeLoading, setIframeLoading] = useState(true);
  const [iframeError, setIframeError] = useState(false);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  const page = data?.page;
  const [dataVersion, setDataVersion] = useState(0);

  // Increment version when data changes to force iframe refresh
  useEffect(() => {
    if (data) {
      setDataVersion((prev) => prev + 1);
    }
  }, [data]);

  // Construct the public page URL with preview dimensions and cache-busting
  // The version ensures the iframe refreshes when data changes
  const publicPageUrl = useMemo(() => {
    if (!page?.username) return null;
    // Use the current origin and construct the public page URL
    const baseUrl = window.location.origin;
    // Pass device width as query parameter so page renders at exact device width
    // Add version and timestamp for cache-busting to ensure fresh content after updates
    const timestamp = Date.now();
    return `${baseUrl}/page.php?username=${encodeURIComponent(page.username)}&preview_width=${selectedDevice.width}&_v=${dataVersion}&_t=${timestamp}`;
  }, [page?.username, selectedDevice.width, dataVersion]);

  const previewDimensions = useMemo(() => {
    const scaledWidth = selectedDevice.width * 0.75;
    const toolbarWidth = scaledWidth * 1.5625; // 25% wider than previous (1.25 * 1.25)
    return {
      '--pod-canvas-preview-width': `${selectedDevice.width}px`,
      '--pod-canvas-preview-height': `${selectedDevice.height}px`,
      '--pod-canvas-preview-padding': '0',
      '--pod-canvas-preview-scaled-width': `${scaledWidth}px`,
      '--pod-canvas-toolbar-width': `${toolbarWidth}px`
    } as CSSProperties;
  }, [selectedDevice]);

  const deviceFrameStyle = useMemo(() => {
    return { background: '#ffffff' } as CSSProperties;
  }, []);

  // Reset loading state when URL changes
  useEffect(() => {
    if (publicPageUrl) {
      setIframeLoading(true);
      setIframeError(false);
      
      // Set a timeout to hide loading after 5 seconds (fallback)
      // This handles cases where onLoad doesn't fire
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      timeoutRef.current = setTimeout(() => {
        // Check if iframe has content loaded
        try {
          const iframe = iframeRef.current;
          if (iframe) {
            // Try to access iframe content to check if it's loaded
            if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
              setIframeLoading(false);
            } else if (iframe.contentWindow) {
              // Iframe window exists - assume it's loaded or loading
              setIframeLoading(false);
            } else {
              // Iframe exists but can't access content - assume loaded
              setIframeLoading(false);
            }
          }
        } catch (e) {
          // Cross-origin or other error - assume loaded (page is probably there)
          setIframeLoading(false);
        }
      }, 5000);
    }

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, [publicPageUrl]);

  const handleIframeLoad = () => {
    // Small delay to ensure content is rendered
    setTimeout(() => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      setIframeLoading(false);
      setIframeError(false);
    }, 100);
  };

  const handleIframeError = () => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }
    setIframeLoading(false);
    setIframeError(true);
  };

  return (
    <div className={styles.container} aria-label="Preview canvas" style={previewDimensions}>
      <div className={styles.canvas}>
        <div 
          className={styles.deviceFrame} 
          aria-label="Mobile preview" 
          style={{
            ...deviceFrameStyle,
            width: `${selectedDevice.width}px`,
            height: `${selectedDevice.height}px`,
            transform: `scale(${previewScale})`,
            transformOrigin: 'top center'
          }}
        >
          {isLoading || !publicPageUrl ? (
            <div className={styles.iframePlaceholder}>
              <p className={styles.placeholder}>Loading preview…</p>
            </div>
          ) : isError ? (
            <div className={styles.iframePlaceholder}>
              <p className={styles.error}>{error instanceof Error ? error.message : 'Unable to load page.'}</p>
            </div>
          ) : (
            <>
              {iframeLoading && (
                <div className={styles.iframeLoading}>
                  <p className={styles.placeholder}>Loading page…</p>
                </div>
              )}
              {iframeError && (
                <div className={styles.iframeError}>
                  <p className={styles.error}>Unable to load page preview. Please check your page settings.</p>
                </div>
              )}
              <iframe
                key={publicPageUrl}
                ref={iframeRef}
                src={publicPageUrl}
                className={styles.previewIframe}
                title="Mobile preview"
                onLoad={handleIframeLoad}
                onError={handleIframeError}
                sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox"
                style={{
                  opacity: iframeLoading || iframeError ? 0 : 1,
                  transition: 'opacity 0.3s ease-in-out'
                }}
              />
            </>
          )}
        </div>
      </div>
    </div>
  );
}

