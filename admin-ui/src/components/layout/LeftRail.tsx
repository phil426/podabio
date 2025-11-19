import * as ScrollArea from '@radix-ui/react-scroll-area';
import * as Tabs from '@radix-ui/react-tabs';
import { useCallback, useMemo, useState, useEffect } from 'react';
import { type IconType } from 'react-icons';
import clsx from 'clsx';
import {
  LuAlignLeft,
  LuBookOpen,
  LuFilter,
  LuGlobe,
  LuHeading2,
  LuImage,
  LuItalic,
  LuLayoutGrid,
  LuLink2,
  LuMail,
  LuMinus,
  LuNewspaper,
  LuPodcast,
  LuDollarSign,
  LuEye,
  LuEyeOff,
  LuPencil,
  LuPickaxe,
  LuRss,
  LuSparkles,
  LuCrown,
  LuStar,
  LuUserRound,
  LuTrash,
  LuYoutube,
  LuLock,
  LuPlus,
  LuType
} from 'react-icons/lu';

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
import { useThemeLibraryQuery } from '../../api/themes';
import { useWidgetSelection } from '../../state/widgetSelection';
import { DraggableLayerList, type LayerItem } from '../system/DraggableLayerList';
import { WidgetGalleryDrawer } from '../overlays/WidgetGalleryDrawer';
import type { ApiResponse } from '../../api/types';
import { getYouTubeThumbnail } from '../../utils/media';
import { ThemeLibraryPanel } from '../panels/ThemeLibraryPanel';
import { IntegrationsPanel } from '../panels/IntegrationsPanel';
import { SettingsPanel } from '../panels/SettingsPanel';

import styles from './left-rail.module.css';
import { useThemeInspector } from '../../state/themeInspector';

type BaseStructureItem = {
  id: string;
  label: string;
  description: string;
  Icon: IconType;
};

const baseStructureItems: BaseStructureItem[] = [];

const widgetIconMap: Record<string, IconType> = {
  rss_feed: LuRss,
  latest_episodes: LuSparkles,
  custom_link: LuLink2,
  spotlight: LuStar,
  social_links: LuGlobe,
  podcast_player_custom: LuPodcast,
  youtube_video: LuYoutube,
  text_html: LuAlignLeft,
  image: LuImage,
  email_subscription: LuMail,
  heading_block: LuHeading2,
  text_note: LuItalic,
  divider_rule: LuMinus,
  instagram_post: LuImage,
  instagram_feed: LuLayoutGrid,
  instagram_gallery: LuLayoutGrid
};

const fallbackWidgetIcon: IconType = LuLayoutGrid;

const quickAddDescriptors: QuickAddDescriptor[] = [
  {
    id: 'heading',
    label: 'Add Heading',
    keywords: ['heading'],
    preset: {
      widgetType: 'heading_block',
      title: 'Heading',
      config: {
        text: 'New heading',
        level: 'h2'
      }
    }
  },
  {
    id: 'text',
    label: 'Add Text',
    keywords: ['text', 'paragraph'],
    preset: {
      widgetType: 'text_note',
      title: 'Note',
      config: {
        text: 'Start writing your story…'
      }
    }
  },
  {
    id: 'line',
    label: 'Add Line',
    keywords: ['divider', 'line', 'separator'],
    preset: {
      widgetType: 'divider_rule',
      title: 'Divider',
      config: {
        style: 'flat'
      }
    }
  }
];

type QuickAddPreset = {
  widgetType: string;
  title: string;
  config?: Record<string, unknown>;
};

type QuickAddDescriptor = {
  id: string;
  label: string;
  keywords: string[];
  preset: QuickAddPreset;
};

import { type TabValue, type TabColorTheme } from './tab-colors';

interface LeftRailProps {
  activeTab: TabValue;
  onTabChange: (tab: TabValue) => void;
  activeColor: TabColorTheme;
}

export function LeftRail({ activeTab, onTabChange, activeColor }: LeftRailProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const page = snapshot?.page;
  
  // Derive active theme (same logic as PropertiesPanel)
  const activeTheme = useMemo(() => {
    const systemThemes = themeLibrary?.system ?? [];
    const userThemes = themeLibrary?.user ?? [];
    const themeId = page?.theme_id ?? null;
    
    if (themeId == null) {
      return systemThemes[0] ?? userThemes[0] ?? null;
    }
    
    const combined = [...userThemes, ...systemThemes];
    return combined.find((theme) => theme.id === themeId) ?? systemThemes[0] ?? userThemes[0] ?? null;
  }, [themeLibrary, page?.theme_id]);
  const { data: widgets, isLoading: widgetsLoading, isError: widgetsError, error: widgetsErrorObj } = useWidgetsQuery();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const reorderMutation = useReorderWidgetMutation();
  const addMutation = useAddWidgetMutation();
  const updateMutation = useUpdateWidgetMutation();
  const deleteMutation = useDeleteWidgetMutation();
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const setThemeInspectorVisible = useThemeInspector((state) => state.setThemeInspectorVisible);
  const queryClient = useQueryClient();
  const [isGalleryOpen, setGalleryOpen] = useState(false);
  const [lockedItems, setLockedItems] = useState<Set<string>>(new Set()); // Widget locks only (Profile and Footer are always locked)

  const quickAddOptions = useMemo(() => {
    return quickAddDescriptors.map((descriptor) => {
      const match = availableWidgets?.find((widget) => {
        const haystack = `${widget.label ?? ''} ${widget.name ?? ''} ${widget.type ?? ''}`.toLowerCase();
        return descriptor.keywords.some((keyword) => haystack.includes(keyword));
      });

      return {
        ...descriptor,
        widgetType: (match?.type ?? match?.widget_id ?? descriptor.preset.widgetType) as string,
        widgetLabel: match?.label ?? match?.name ?? match?.type ?? descriptor.preset.title
      };
    });
  }, [availableWidgets]);

  const layers = useMemo<LayerItem[]>(() => {
    // Add Profile layer at the top (always locked)
    const profileLayer: LayerItem = {
      id: 'page:profile',
      label: 'Profile',
      description: 'Edit your profile image and bio.',
      icon: <LuUserRound aria-hidden="true" />,
      displayOrder: -1, // Negative to keep at top
      isActive: page?.profile_visible !== false, // Default to true if not set
      isLocked: true // Always locked at top
    };

    // Map widgets to layers
    // Filter out inactive blog widgets
        const widgetLayers: LayerItem[] = widgets ? widgets
          .filter((widget) => {
            // Hide all blog widgets (feature retired)
            const isBlogWidget = widget.widget_type.startsWith('blog_');
            if (isBlogWidget) {
              return false; // Hide all blog widgets
            }
            return true;
          })
      .map((widget) => {
        const IconComponent = widgetIconMap[widget.widget_type] ?? fallbackWidgetIcon;
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
          description:
            availableWidgets?.find((option) => option.type === widget.widget_type)?.description ?? widget.widget_type,
          icon: <IconComponent aria-hidden="true" />,
          thumbnail: thumbnail ? normalizeImageUrl(thumbnail) : undefined,
          displayOrder: widget.display_order,
          isActive: widget.is_active === 1,
          isLocked: lockedItems.has(String(widget.id)),
          isFeatured: widget.is_featured === 1
        };
      }) : [];

    // Add Footer layer at the bottom (always locked)
    const footerLayer: LayerItem = {
      id: 'page:footer',
      label: 'Footer',
      description: 'Update the note shown at the bottom of your page.',
      icon: <LuAlignLeft aria-hidden="true" />,
      displayOrder: 999998, // Very high to keep at bottom
      isActive: page?.footer_visible !== false, // Default to true if not set
      isLocked: true // Always locked at bottom
    };

    // Add Podcast Player layer (not locked, not draggable) - appears after footer
    const podcastPlayerLayer: LayerItem = {
      id: 'page:podcast-player',
      label: 'Podcast Player',
      description: 'Enable audio player for your podcast',
      icon: <LuPodcast aria-hidden="true" />,
      displayOrder: 999999, // After footer
      isActive: Boolean(page?.podcast_player_enabled),
      isLocked: false // Not locked, but we'll hide the grip handle
    };

    // Return Profile first, then widgets, then Footer, then Podcast Player last
    return [profileLayer, ...widgetLayers, footerLayer, podcastPlayerLayer];
  }, [availableWidgets, widgets, lockedItems, page]);

  const earliestOrder = useMemo(() => {
    if (!widgets?.length) return 1;
    return Math.min(...widgets.map((widget) => widget.display_order)) || 1;
  }, [widgets]);

  const handleAddWidget = useCallback(
    (type: string, label?: string, config?: Record<string, unknown>) => {
      addMutation.mutate(
        {
          widget_type: type,
          title: label ?? type,
          ...(config ? { config_data: JSON.stringify(config) } : {})
        },
        {
          onSuccess: (response) => {
            setGalleryOpen(false);
            const typed = (response ?? {}) as ApiResponse & {
              widget_id?: number | string;
              data?: { widget_id?: number | string };
            };
            const widgetId = typed.widget_id ?? typed.data?.widget_id;
            if (widgetId) {
              selectWidget(String(widgetId));
            }
          }
        }
      );
    },
    [addMutation, selectWidget]
  );

  const handleQuickAdd = (option: (typeof quickAddOptions)[number]) => {
    if (!option.widgetType) {
      setGalleryOpen(true);
      return;
    }

    const shouldUsePreset = option.widgetType === option.preset.widgetType;
    handleAddWidget(
      option.widgetType,
      option.widgetLabel ?? option.preset.title,
      shouldUsePreset ? option.preset.config : undefined
    );
  };

  const handleReorder = (items: LayerItem[]) => {
    // Filter out non-widget items (like 'page:profile', 'page:footer')
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
    selectWidget(id);
  };

  const pageSettingsMutation = usePageSettingsMutation();

  const handleToggleVisibility = (id: string) => {
    // Handle Profile visibility
    if (id === 'page:profile') {
      const currentVisibility = page?.profile_visible !== false; // Default to true
      pageSettingsMutation.mutate({
        profile_visible: currentVisibility ? '0' : '1'
      });
      return;
    }

    // Handle Podcast Player visibility
    if (id === 'page:podcast-player') {
      const currentVisibility = Boolean(page?.podcast_player_enabled);
      pageSettingsMutation.mutate({
        podcast_player_enabled: currentVisibility ? '0' : '1'
      });
      return;
    }

    // Handle Footer visibility
    if (id === 'page:footer') {
      const currentVisibility = page?.footer_visible !== false; // Default to true
      pageSettingsMutation.mutate({
        footer_visible: currentVisibility ? '0' : '1'
      });
      return;
    }

    // Handle widget visibility
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
    // Don't allow toggling lock for Profile or Footer (always locked)
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

  const handlePrimaryAdd = () => {
    setGalleryOpen(true);
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

    // If marking as featured, unfeature all other widgets first
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

      // Mark this widget as featured with default effect if none set
      updateMutation.mutate({
        widget_id: id,
        is_featured: '1',
        featured_effect: widget.featured_effect || 'jiggle'
      });
    } else {
      // Unfeature this widget
      updateMutation.mutate({
        widget_id: id,
        is_featured: '0',
        featured_effect: ''
      });
    }
  };


  useEffect(() => {
    // Auto-open theme inspector when Design tab is active
    if (activeTab === 'design') {
      setThemeInspectorVisible(true);
    } else {
      setThemeInspectorVisible(false);
    }
  }, [activeTab, setThemeInspectorVisible]);

  useEffect(() => {
    // Set initial selection for structure tab only if nothing is selected, clear for others
    // CRITICAL: Don't reset if user has explicitly selected something (like Footer)
    if (activeTab === 'structure') {
      const currentSelection = useWidgetSelection.getState().selectedWidgetId;
      // Only set default if there's truly no selection - don't override user's explicit choice
      if (!currentSelection) {
        selectWidget('page:profile');
      }
    } else {
      selectWidget(null);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeTab]);

  return (
    <div 
      className={styles.container} 
      aria-label="Layers and assets panel"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <Tabs.Root
        className={styles.tabsRoot}
        value={activeTab}
        onValueChange={(value) => {
          const newTab = (value as TabValue) ?? 'structure';
          onTabChange(newTab);
        }}
      >
        {(activeTab === 'structure' || activeTab === 'design') && (
          <Tabs.List className={styles.innerTabList} aria-label="Page layout and look">
            <Tabs.Trigger value="structure" className={styles.innerTabTrigger}>
              Layout
            </Tabs.Trigger>
            <Tabs.Trigger value="design" className={styles.innerTabTrigger}>
              Look
            </Tabs.Trigger>
          </Tabs.List>
        )}

        <Tabs.Content value="structure" className={styles.tabContent}>
          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
          <section className={styles.section}>
            {widgetsLoading ? (
              <p>Loading layers…</p>
            ) : widgetsError ? (
              <p className={styles.errorText}>{widgetsErrorObj instanceof Error ? widgetsErrorObj.message : 'Unable to load widgets.'}</p>
            ) : (
              <>
                {baseStructureItems.length > 0 && (
                  <div className={styles.structurePrimer}>
                    <div className={styles.staticHeader}>
                      <h3>Static Layers</h3>
                      <span>Always on your page.</span>
                    </div>
                    <ul className={styles.baseList}>
                      {baseStructureItems.map((item) => (
                        <li key={item.id}>
                          <button
                            type="button"
                            className={styles.baseButton}
                            data-active={selectedWidgetId === item.id ? 'true' : 'false'}
                            onClick={() => selectWidget(item.id)}
                          >
                            <span className={styles.baseIcon} aria-hidden="true">
                              <item.Icon />
                            </span>
                            <div>
                              <p className={styles.baseLabel}>{item.label}</p>
                              <p className={styles.baseDescription}>{item.description}</p>
                            </div>
                          </button>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                <div className={styles.quickAddContainer}>
                  <button
                    type="button"
                    className={styles.primaryAddButton}
                    onClick={handlePrimaryAdd}
                    disabled={addMutation.isPending || !availableWidgets?.length}
                    title="Add Block"
                  >
                    <LuPlus aria-hidden="true" />
                    <span>Add Block</span>
                  </button>
                  <div className={styles.quickAddButtons}>
                    {quickAddOptions.map((option) => {
                      const iconMap: Record<string, JSX.Element> = {
                        heading: <LuHeading2 aria-hidden="true" />,
                        text: <LuType aria-hidden="true" />,
                        line: <LuMinus aria-hidden="true" />
                      };
                      return (
                        <button
                          key={option.id}
                          type="button"
                          className={styles.quickAddButton}
                          disabled={addMutation.isPending}
                          onClick={() => handleQuickAdd(option)}
                          title={option.label}
                        >
                          {iconMap[option.id] || <LuPlus aria-hidden="true" />}
                        </button>
                      );
                    })}
                  </div>
                </div>

                <DraggableLayerList
                    items={layers}
                    onReorder={handleReorder}
                    startIndex={earliestOrder}
                    onSelect={handleSelectLayer}
                    selectedId={selectedWidgetId}
                    renderActions={(item) => {
                      const VisibilityIcon = item.isActive ? LuEye : LuEyeOff;
                      const isPageItem = item.id.startsWith('page:');
                      const isLocked = item.isLocked ?? false;
                      const isAlwaysLocked = item.id === 'page:profile' || item.id === 'page:footer';
                      const isPodcastPlayer = item.id === 'page:podcast-player';
                      return (
                      <div className={styles.layerActions}>
                        {!isAlwaysLocked && !isPodcastPlayer && (
                          <button
                            type="button"
                            className={styles.layerActionButton}
                            onClick={() => handleToggleLock(item.id)}
                            aria-label={isLocked ? `Unlock ${item.label}` : `Lock ${item.label}`}
                            title={isLocked ? 'Unlock this block so it can move' : 'Lock this block to prevent accidental moves'}
                            data-locked={isLocked ? 'true' : 'false'}
                          >
                            <LuLock aria-hidden="true" />
                          </button>
                        )}
                        {/* Visibility icon for Profile, Footer, and widgets */}
                        <button
                          type="button"
                          className={styles.layerActionButton}
                          onClick={() => handleToggleVisibility(item.id)}
                          aria-label={item.isActive ? `Hide ${item.label}` : `Show ${item.label}`}
                            title={item.isActive ? 'Hide this block on your live page' : 'Show this block on your live page'}
                          disabled={isPageItem ? pageSettingsMutation.isPending : updateMutation.isPending}
                          data-active={item.isActive ? 'true' : 'false'}
                        >
                          <VisibilityIcon aria-hidden="true" />
                        </button>
                        {!isPageItem && (
                          <>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={() => handleEditLayer(item.id)}
                              aria-label={`Edit ${item.label}`}
                              title="Open settings for this block in the right-hand panel"
                              disabled={updateMutation.isPending}
                            >
                              <LuPencil aria-hidden="true" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={() => handleDeleteLayer(item.id)}
                              aria-label={`Delete ${item.label}`}
                              title="Delete this block from your page"
                              disabled={deleteMutation.isPending}
                            >
                              <LuTrash aria-hidden="true" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              onClick={(e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                handleToggleFeatured(item.id, e);
                              }}
                              onMouseDown={(e) => {
                                e.stopPropagation();
                                e.preventDefault();
                              }}
                              onPointerDown={(e) => {
                                e.stopPropagation();
                              }}
                              aria-label={item.isFeatured ? 'Unmark as featured' : 'Mark as featured'}
                              title={item.isFeatured ? 'Stop highlighting this block on your page' : 'Highlight this block with a featured effect'}
                              disabled={updateMutation.isPending}
                              data-featured={item.isFeatured ? 'true' : 'false'}
                            >
                              <LuStar aria-hidden="true" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              aria-label="Premium tier (coming soon)"
                              title="Premium"
                              disabled
                            >
                              <LuCrown aria-hidden="true" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              aria-label="Monetization (coming soon)"
                              title="Monetize"
                              disabled
                            >
                              <LuDollarSign aria-hidden="true" />
                            </button>
                            <button
                              type="button"
                              className={styles.layerActionButton}
                              aria-label="Optimize (coming soon)"
                              title="Optimize"
                              disabled
                            >
                              <LuPickaxe aria-hidden="true" />
                            </button>
                          </>
                        )}
                      </div>
                       );
                     }}
                  />
              </>
            )}
          </section>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
        </Tabs.Content>

        <Tabs.Content value="design" className={styles.tabContent}>
          <p className={styles.tabDescription}>Browse design presets and customize your themes.</p>
          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <div className={styles.designStack}>
                <ThemeLibraryPanel />
              </div>
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
        </Tabs.Content>
        <Tabs.Content value="style" className={styles.tabContent}>
          {/* Intentionally left minimal for now – Style tab is driven by the Properties panel */}
          <div className={styles.analyticsPlaceholder}>
            <p>Use the right-hand panel to edit typography, spacing, shapes, and motion.</p>
          </div>
        </Tabs.Content>
        <Tabs.Content value="analytics" className={styles.tabContent}>
          <div className={styles.analyticsPlaceholder}>
            <p>Analytics dashboard is displayed in the center panel</p>
          </div>
        </Tabs.Content>


        <Tabs.Content value="integrations" className={styles.tabContent}>
          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <IntegrationsPanel />
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
        </Tabs.Content>

        <Tabs.Content value="settings" className={styles.tabContent}>
          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <SettingsPanel />
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
        </Tabs.Content>
      </Tabs.Root>

      <WidgetGalleryDrawer
        open={isGalleryOpen}
        widgets={availableWidgets ?? []}
        onClose={() => setGalleryOpen(false)}
        onAdd={handleAddWidget}
        isAdding={addMutation.isPending}
      />
    </div>
  );
}

