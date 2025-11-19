import { useEffect, useRef, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { X } from '@phosphor-icons/react';
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
import { usePageSnapshot } from '../../../api/page';
import type { TabColorTheme } from '../../layout/tab-colors';
import type { LeftyTabValue } from '../../layout/tab-colors';
import styles from './lefty-inspector-drawer.module.css';

interface LeftyInspectorDrawerProps {
  activeColor: TabColorTheme;
  activeTab: LeftyTabValue;
}

export function LeftyInspectorDrawer({ activeColor, activeTab }: LeftyInspectorDrawerProps): JSX.Element {
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const { data: snapshot } = usePageSnapshot();
  const drawerRef = useRef<HTMLDivElement>(null);
  const [selectedItemPosition, setSelectedItemPosition] = useState<{ top: number; left: number; height?: number } | null>(null);

  // Find the selected item's position in the DOM
  useEffect(() => {
    if (!selectedWidgetId) {
      setSelectedItemPosition(null);
      return;
    }

    const findSelectedElement = () => {
      // Try to find the selected item in the DraggableLayerList
      // Look for elements with data-layer-id or data-selected attributes
      const selectedElement = 
        document.querySelector(`[data-layer-id="${selectedWidgetId}"]`) ||
        document.querySelector(`[data-selected="true"][data-id="${selectedWidgetId}"]`) ||
        document.querySelector(`[data-id="${selectedWidgetId}"]`);
      
      if (selectedElement) {
        const rect = selectedElement.getBoundingClientRect();
        const container = drawerRef.current?.closest('.lefty-content-panel__container') || 
                         drawerRef.current?.parentElement;
        if (container) {
          const containerRect = container.getBoundingClientRect();
          const top = rect.bottom - containerRect.top + 8; // 8px gap below item
          // Calculate available height: container height minus top position
          const availableHeight = containerRect.height - top;
          // Use 60vh or available height, whichever is smaller
          const maxHeight = Math.min(window.innerHeight * 0.6, availableHeight);
          setSelectedItemPosition({
            top,
            left: 0, // Full width for now, can be adjusted to align with item
            height: Math.max(200, maxHeight) // Minimum 200px height
          });
        }
      } else {
        // Fallback: position at bottom of content area
        setSelectedItemPosition(null);
      }
    };

    // Small delay to ensure DOM is updated
    const timeoutId = setTimeout(findSelectedElement, 100);
    return () => clearTimeout(timeoutId);
  }, [selectedWidgetId]);

  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);

  const handleClose = () => {
    selectWidget(null);
    selectSocialIcon(null);
    selectIntegration(null);
  };

  // Determine which inspector to show
  const selectedWidget = snapshot?.widgets?.find((widget) => String(widget.id) === selectedWidgetId);
  const isFeaturedWidget = selectedWidget?.is_featured === 1;

  let inspector: JSX.Element | null = null;
  const isOpen = Boolean(selectedWidgetId || selectedSocialIconId || selectedIntegrationId);

  // Gate inspectors by activeTab - Lefty-specific tabs only
  const isLeftyLayerTab = activeTab === 'layers';
  const isLeftyIntegrationTab = activeTab === 'integration';

  if (isLeftyLayerTab && selectedWidgetId) {
    // Layers tab: Show widget/page inspectors
    // Note: Podcast player is handled by switching to podcast tab, not showing in inspector drawer
    if (selectedWidgetId === 'page:footer') {
      inspector = <FooterInspector activeColor={activeColor} />;
    } else if (selectedWidgetId?.startsWith('page:')) {
      if (selectedWidgetId === 'page:profile') {
        inspector = <ProfileInspector focus="profile" activeColor={activeColor} />;
      } else if (selectedWidgetId === 'page:podcast-player') {
        // Podcast player should navigate to podcast tab, not show in drawer
        inspector = null;
      } else {
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
    }
  } else if (isLeftyIntegrationTab && selectedIntegrationId !== null) {
    inspector = <IntegrationInspector activeColor={activeColor} />;
  }

  return (
    <AnimatePresence>
      {isOpen && inspector && (
        <>
          {/* Backdrop */}
          <motion.div
            className={styles.backdrop}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            onClick={handleClose}
          />
          {/* Drawer */}
          <motion.div
            ref={drawerRef}
            className={styles.drawer}
            initial={{ y: '100%', opacity: 0 }}
            animate={{ 
              y: 0, 
              opacity: 1,
              ...(selectedItemPosition ? { top: selectedItemPosition.top } : {})
            }}
            exit={{ y: '100%', opacity: 0 }}
            transition={{ 
              type: 'spring',
              damping: 25,
              stiffness: 200,
              duration: 0.3
            }}
            style={{
              '--active-tab-color': activeColor.text,
              '--active-tab-bg': activeColor.primary,
              ...(selectedItemPosition ? { 
                left: `${selectedItemPosition.left}px`,
                ...(selectedItemPosition.height ? { height: `${selectedItemPosition.height}px`, maxHeight: `${selectedItemPosition.height}px` } : {})
              } : {})
            } as React.CSSProperties}
          >
            <div className={styles.drawerHeader}>
              <h3 className={styles.drawerTitle}>Properties</h3>
              <button
                type="button"
                className={styles.closeButton}
                onClick={handleClose}
                aria-label="Close inspector"
              >
                <X aria-hidden="true" size={20} weight="regular" />
              </button>
            </div>
            <ScrollArea.Root className={styles.scrollArea}>
              <ScrollArea.Viewport className={styles.viewport}>
                <div className={styles.drawerContent}>
                  {inspector}
                </div>
              </ScrollArea.Viewport>
              <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
                <ScrollArea.Thumb className={styles.thumb} />
              </ScrollArea.Scrollbar>
            </ScrollArea.Root>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}

