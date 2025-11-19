import { useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { createPortal } from 'react-dom';
import { CanvasViewport, type DevicePreset } from './CanvasViewport';
import styles from './preview-overlay.module.css';

interface PreviewOverlayProps {
  isOpen: boolean;
  selectedDevice: DevicePreset;
  previewScale?: number;
  onClose: () => void;
}

export function PreviewOverlay({
  isOpen,
  selectedDevice,
  previewScale = 0.75,
  onClose
}: PreviewOverlayProps): JSX.Element {
  const overlayRef = useRef<HTMLDivElement>(null);
  const iframeRef = useRef<HTMLIFrameElement>(null);

  // Block interactions in preview mode
  useEffect(() => {
    if (!isOpen) return;

    const handleClick = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      
      // Allow clicks on the overlay close button
      if (target.closest(`.${styles.closeButton}`)) {
        return;
      }

      // Block external link navigation
      const link = target.closest('a');
      if (link && link.href) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }

      // Block form submissions
      const form = target.closest('form');
      if (form) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }

      // Block button clicks that would navigate away
      const button = target.closest('button');
      if (button && (button.type === 'submit' || button.getAttribute('data-navigate'))) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }
    };

    const handleSubmit = (e: SubmitEvent) => {
      e.preventDefault();
      e.stopPropagation();
      return false;
    };

    // Intercept clicks in iframe (if accessible)
    const iframe = iframeRef.current;
    if (iframe?.contentWindow?.document) {
      try {
        iframe.contentWindow.document.addEventListener('click', handleClick, true);
        iframe.contentWindow.document.addEventListener('submit', handleSubmit, true);
      } catch (e) {
        // Cross-origin, can't access iframe content
      }
    }

    document.addEventListener('click', handleClick, true);
    document.addEventListener('submit', handleSubmit, true);

    return () => {
      document.removeEventListener('click', handleClick, true);
      document.removeEventListener('submit', handleSubmit, true);
      
      if (iframe?.contentWindow?.document) {
        try {
          iframe.contentWindow.document.removeEventListener('click', handleClick, true);
          iframe.contentWindow.document.removeEventListener('submit', handleSubmit, true);
        } catch (e) {
          // Ignore
        }
      }
    };
  }, [isOpen]);

  // Focus trap
  useEffect(() => {
    if (!isOpen) return;

    const overlay = overlayRef.current;
    if (!overlay) return;

    const focusableElements = overlay.querySelectorAll(
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
    overlay.addEventListener('keydown', handleTabKey);

    return () => {
      overlay.removeEventListener('keydown', handleTabKey);
    };
  }, [isOpen]);

  // Escape key to close
  useEffect(() => {
    if (!isOpen) return;

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  if (!isOpen) return <></>;

  const content = (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          ref={overlayRef}
          className={styles.overlay}
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.2 }}
          role="dialog"
          aria-modal="true"
          aria-label="Preview mode"
        >
          <div className={styles.header}>
            <div className={styles.headerLeft}>
              <span className={styles.badge}>Preview Mode</span>
              <p className={styles.headerText}>
                All links and forms are disabled. Use the podcast player controls to test playback.
              </p>
            </div>
            <button
              type="button"
              className={styles.closeButton}
              onClick={onClose}
              aria-label="Exit preview mode"
            >
              Exit Preview
            </button>
          </div>

          <div className={styles.viewportContainer}>
            <CanvasViewport
              selectedDevice={selectedDevice}
              previewScale={previewScale}
            />
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );

  return createPortal(content, document.body);
}

