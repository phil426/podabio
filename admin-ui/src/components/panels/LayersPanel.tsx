import { useMemo, useCallback, useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import {
  User,
  AlignLeft,
  ApplePodcastsLogo,
  Eye,
  EyeSlash,
  Pencil,
  Trash,
  Star,
  Lock,
  Plus,
  X,
  Cards
} from '@phosphor-icons/react';
import {
  useAddWidgetMutation,
  useAvailableWidgetsQuery,
  useDeleteWidgetMutation,
  useReorderWidgetMutation,
  useUpdateWidgetMutation,
  useWidgetsQuery
} from '../../api/widgets';
import { usePageSnapshot, usePageSettingsMutation } from '../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys, normalizeImageUrl } from '../../api/utils';
import { useWidgetSelection } from '../../state/widgetSelection';
import { DraggableLayerList, type LayerItem } from '../system/DraggableLayerList';
import { getYouTubeThumbnail } from '../../utils/media';
import type { TabColorTheme, LeftyTabValue } from '../layout/tab-colors';
import { AddingContentPanel } from './AddingContentPanel';
import { WidgetInspectorModal } from './WidgetInspectorModal';
import styles from './layers-panel.module.css';

interface LayersPanelProps {
  activeColor: TabColorTheme;
  onTabChange?: (tab: LeftyTabValue) => void;
}

const widgetIconMap: Record<string, JSX.Element> = {
  rss_feed: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  latest_episodes: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  custom_link: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  spotlight: <Star aria-hidden="true" size={24} weight="regular" />,
  social_links: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  podcast_player_custom: <ApplePodcastsLogo aria-hidden="true" size={24} weight="regular" />,
  youtube_video: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  text_html: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  image: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  email_subscription: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  heading_block: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  text_note: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  divider_rule: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  instagram_post: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  instagram_feed: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  instagram_gallery: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  people: <Cards aria-hidden="true" size={24} weight="regular" />,
  rolodex: <Cards aria-hidden="true" size={24} weight="regular" />
};

export function LayersPanel({ activeColor, onTabChange }: LayersPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;
  const { data: widgets, isLoading } = useWidgetsQuery();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const reorderMutation = useReorderWidgetMutation();
  const addMutation = useAddWidgetMutation();
  const updateMutation = useUpdateWidgetMutation();
  const deleteMutation = useDeleteWidgetMutation();
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const queryClient = useQueryClient();
  const [lockedItems, setLockedItems] = useState<Set<string>>(new Set());
  const pageSettingsMutation = usePageSettingsMutation();
  const [showAddPanel, setShowAddPanel] = useState(false);
  const [modalWidgetId, setModalWidgetId] = useState<string | null>(null);

  const layers = useMemo<LayerItem[]>(() => {
    const profileLayer: LayerItem = {
      id: 'page:profile',
      label: 'Profile',
      description: 'Edit your profile image and bio.',
      icon: <User aria-hidden="true" size={24} weight="regular" />,
      displayOrder: -1,
      isActive: page?.profile_visible !== false,
      isLocked: true
    };

    const widgetLayers: LayerItem[] = widgets ? widgets
      .filter((widget) => {
        const isBlogWidget = widget.widget_type.startsWith('blog_');
        if (isBlogWidget) {
          return false;
        }
        return true;
      })
      .map((widget) => {
        const icon = widgetIconMap[widget.widget_type] ?? <AlignLeft aria-hidden="true" size={24} weight="regular" />;
        const config =
          widget.config_data && typeof widget.config_data === 'object'
            ? (widget.config_data as Record<string, unknown>)
            : {};
        const rawThumbnail =
          typeof config.thumbnail_image === 'string' && config.thumbnail_image.trim() !== ''
            ? (config.thumbnail_image as string)
            : undefined;
        const derivedYouTubeThumbnail =
          widget.widget_type === 'youtube_video'
            ? getYouTubeThumbnail(
                typeof config.video_url === 'string' ? (config.video_url as string) : undefined
              ) ?? undefined
            : undefined;
        const thumbnail = rawThumbnail ?? derivedYouTubeThumbnail;

        return {
          id: String(widget.id),
          label: widget.title,
          description: availableWidgets?.find((option) => option.type === widget.widget_type)?.description ?? widget.widget_type,
          icon: icon,
          thumbnail: thumbnail ? normalizeImageUrl(thumbnail) : undefined,
          displayOrder: widget.display_order,
          isActive: widget.is_active === 1,
          isLocked: lockedItems.has(String(widget.id)),
          isFeatured: widget.is_featured === 1
        };
      }) : [];

    const footerLayer: LayerItem = {
      id: 'page:footer',
      label: 'Footer',
      description: 'Update the note shown at the bottom of your page.',
      icon: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
      displayOrder: 999998,
      isActive: page?.footer_visible !== false,
      isLocked: true
    };

    return [profileLayer, ...widgetLayers, footerLayer];
  }, [availableWidgets, widgets, lockedItems, page]);

  const earliestOrder = useMemo(() => {
    if (!widgets?.length) return 1;
    return Math.min(...widgets.map((widget) => widget.display_order)) || 1;
  }, [widgets]);

  const handleReorder = (items: LayerItem[]) => {
    const widgetItems = items.filter((layer) => !layer.id.startsWith('page:'));
    
    reorderMutation.mutate({
      widget_orders: JSON.stringify(
        widgetItems.map((layer, index) => ({
          widget_id: Number(layer.id),
          display_order: earliestOrder + index
        }))
      )
    });
  };

  const handleEditLayer = (id: string) => {
    const widget = widgets?.find((entry) => String(entry.id) === id);
    if (!widget) return;
    // Open the modal - WidgetInspector will receive widgetId as prop, so we don't need to select globally
    setModalWidgetId(id);
  };

  const handleCloseModal = () => {
    // Deselect widget when closing modal to prevent drawer/panel from showing
    if (modalWidgetId) {
      selectWidget(null);
    }
    setModalWidgetId(null);
    // Optionally clear widget selection when modal closes
    // selectWidget(null);
  };

  const handleToggleVisibility = (id: string) => {
    if (id === 'page:profile') {
      const currentVisibility = page?.profile_visible !== false;
      pageSettingsMutation.mutate({
        profile_visible: currentVisibility ? '0' : '1'
      });
      return;
    }


    if (id === 'page:footer') {
      const currentVisibility = page?.footer_visible !== false;
      pageSettingsMutation.mutate({
        footer_visible: currentVisibility ? '0' : '1'
      });
      return;
    }

    const widget = widgets?.find((entry) => String(entry.id) === id);
    if (!widget) return;

    updateMutation.mutate({ widget_id: id, is_active: widget.is_active ? '0' : '1' });
  };

  const handleDeleteLayer = (id: string) => {
    const widget = widgets?.find((entry) => String(entry.id) === id);
    if (!widget) return;

    const confirmDelete = window.confirm(`Delete "${widget.title}"? This cannot be undone.`);
    if (!confirmDelete) return;

    deleteMutation.mutate({ widget_id: id });

    if (selectedWidgetId === id) {
      selectWidget(null);
    }
  };

  const handleToggleLock = (id: string) => {
    if (id === 'page:profile' || id === 'page:footer') {
      return;
    }
    setLockedItems((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const handleSelectLayer = (item: LayerItem) => {
    selectWidget(item.id);
  };

  const handleToggleFeatured = (id: string, event?: React.MouseEvent) => {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    
    const widget = widgets?.find((entry) => String(entry.id) === id);
    if (!widget) return;

    const isCurrentlyFeatured = widget.is_featured === 1;
    const newFeaturedState = !isCurrentlyFeatured;

    if (newFeaturedState) {
      widgets?.forEach((w) => {
        if (String(w.id) !== id && w.is_featured === 1) {
          updateMutation.mutate({
            widget_id: String(w.id),
            is_featured: '0',
            featured_effect: ''
          });
        }
      });

      updateMutation.mutate({
        widget_id: id,
        is_featured: '1',
        featured_effect: widget.featured_effect || 'jiggle'
      });
    } else {
      updateMutation.mutate({
        widget_id: id,
        is_featured: '0',
        featured_effect: ''
      });
    }
  };

  return (
    <div
      className={styles.panel}
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
      } as React.CSSProperties}
    >
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <div className={styles.content}>
            <header className={styles.header}>
              <h2>Layers</h2>
              <p>Manage and organize the elements on your page</p>
            </header>

            <button
              type="button"
              onClick={() => setShowAddPanel(true)}
              className={styles.addButton}
              aria-label="Add a new block"
            >
              <Plus aria-hidden="true" size={20} weight="regular" />
              <span>Add a block</span>
            </button>

            {isLoading ? (
              <p className={styles.loading}>Loading layers…</p>
            ) : (
              <DraggableLayerList
                items={layers}
                onReorder={handleReorder}
                startIndex={earliestOrder}
                onSelect={handleSelectLayer}
                selectedId={selectedWidgetId}
                renderActions={(item) => {
                  const VisibilityIcon = item.isActive ? Eye : EyeSlash;
                  const isPageItem = item.id.startsWith('page:');
                  const isLocked = item.isLocked ?? false;
                  const isAlwaysLocked = item.id === 'page:profile' || item.id === 'page:footer';
                  return (
                    <div className={styles.layerActions}>
                      {!isAlwaysLocked && (
                        <button
                          type="button"
                          className={styles.layerActionButton}
                          onClick={() => handleToggleLock(item.id)}
                          aria-label={isLocked ? `Unlock ${item.label}` : `Lock ${item.label}`}
                            title={isLocked ? 'Unlock this block so it can move' : 'Lock this block to prevent accidental moves'}
                            data-locked={isLocked ? 'true' : 'false'}
                          >
                            <Lock aria-hidden="true" size={16} weight="regular" />
                          </button>
                        )}
                        <button
                          type="button"
                          className={styles.layerActionButton}
                          onClick={() => handleToggleVisibility(item.id)}
                          aria-label={item.isActive ? `Hide ${item.label}` : `Show ${item.label}`}
                          title={item.isActive ? 'Hide this block on your live page' : 'Show this block on your live page'}
                          disabled={isPageItem ? pageSettingsMutation.isPending : updateMutation.isPending}
                          data-active={item.isActive ? 'true' : 'false'}
                        >
                          <VisibilityIcon aria-hidden="true" size={16} weight="regular" />
                        </button>
                        {!isPageItem && (
                          <>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={() => handleEditLayer(item.id)}
                              aria-label={`Edit ${item.label}`}
                              title="Open settings for this block in a modal"
                              disabled={updateMutation.isPending}
                            >
                              <Pencil aria-hidden="true" size={16} weight="regular" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={() => handleDeleteLayer(item.id)}
                              aria-label={`Delete ${item.label}`}
                              title="Delete this block from your page"
                              disabled={deleteMutation.isPending}
                            >
                              <Trash aria-hidden="true" size={16} weight="regular" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={(e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                handleToggleFeatured(item.id, e);
                              }}
                              aria-label={item.isFeatured ? 'Unmark as featured' : 'Mark as featured'}
                              title={item.isFeatured ? 'Stop highlighting this block on your page' : 'Highlight this block with a featured effect'}
                              disabled={updateMutation.isPending}
                              data-featured={item.isFeatured ? 'true' : 'false'}
                            >
                              <Star aria-hidden="true" size={16} weight="regular" />
                          </button>
                        </>
                      )}
                    </div>
                  );
                }}
              />
            )}
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>

      {/* Add Panel Drawer */}
      <AnimatePresence>
        {showAddPanel && (
          <>
            <motion.div
              className={styles.drawerBackdrop}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowAddPanel(false)}
            />
            <motion.div
              className={styles.addDrawer}
              initial={{ x: '100%' }}
              animate={{ x: 0 }}
              exit={{ x: '100%' }}
              transition={{ type: 'spring', damping: 25, stiffness: 200 }}
            >
              <div className={styles.drawerHeader}>
                <h3>Add a block</h3>
                <button
                  type="button"
                  onClick={() => setShowAddPanel(false)}
                  className={styles.closeButton}
                  aria-label="Close"
                >
                  <X aria-hidden="true" size={20} weight="regular" />
                </button>
              </div>
              <div className={styles.drawerContent}>
                <AddingContentPanel activeColor={activeColor} />
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>

      {/* Widget Inspector Modal */}
      <WidgetInspectorModal
        activeColor={activeColor}
        widgetId={modalWidgetId}
        onClose={handleCloseModal}
      />

    </div>
  );
}

