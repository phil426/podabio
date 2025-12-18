/**
 * Widget Reorder Modal
 * Allows users to reorder widgets via drag and drop
 */

import { useMemo } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { X, AlignLeft } from '@phosphor-icons/react';
import {
  useWidgetsQuery,
  useReorderWidgetMutation,
  useAvailableWidgetsQuery
} from '../../api/widgets';
import { DraggableLayerList, type LayerItem } from '../system/DraggableLayerList';
import { normalizeImageUrl } from '../../api/utils';
import { getYouTubeThumbnail } from '../../utils/media';
import styles from './widget-reorder-modal.module.css';

interface WidgetReorderModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const widgetIconMap: Record<string, JSX.Element> = {
  rss_feed: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  latest_episodes: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  custom_link: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  spotlight: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  social_links: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  podcast_player_custom: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  youtube_video: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  text_html: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  image: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  email_subscription: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  heading_block: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  text_note: <AlignLeft aria-hidden="true" size={24} weight="regular" />,
  divider_rule: <AlignLeft aria-hidden="true" size={24} weight="regular" />,

};

export function WidgetReorderModal({ isOpen, onClose }: WidgetReorderModalProps): JSX.Element {
  const { data: widgets, isLoading } = useWidgetsQuery();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const reorderMutation = useReorderWidgetMutation();

  const layers = useMemo<LayerItem[]>(() => {
    if (!widgets) return [];

    const widgetLayers: LayerItem[] = widgets
      .filter((widget) => {
        // Filter out blog widgets
        const isBlogWidget = widget.widget_type.startsWith('blog_');
        return !isBlogWidget;
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
          isLocked: false,
          isFeatured: widget.is_featured === 1
        };
      });

    return widgetLayers;
  }, [availableWidgets, widgets]);

  const earliestOrder = useMemo(() => {
    if (!widgets?.length) return 1;
    return Math.min(...widgets.map((widget) => widget.display_order)) || 1;
  }, [widgets]);

  const handleReorder = (items: LayerItem[]) => {
    // Filter out any non-widget items (shouldn't be any, but just in case)
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

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.modal}>
          <div className={styles.header}>
            <div className={styles.headerContent}>
              <Dialog.Title className={styles.title}>Reorder Widgets</Dialog.Title>
              <Dialog.Description className={styles.description}>
                Drag widgets to reorder them on your page
              </Dialog.Description>
            </div>
            <Dialog.Close asChild>
              <button type="button" className={styles.closeButton} aria-label="Close">
                <X aria-hidden="true" size={16} weight="regular" />
              </button>
            </Dialog.Close>
          </div>

          <div className={styles.body}>
            {isLoading ? (
              <div className={styles.loading}>Loading widgets...</div>
            ) : layers.length === 0 ? (
              <div className={styles.empty}>No widgets to reorder</div>
            ) : (
              <DraggableLayerList
                items={layers}
                onReorder={handleReorder}
                startIndex={earliestOrder}
              />
            )}
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

