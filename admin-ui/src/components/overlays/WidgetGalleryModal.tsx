import { useMemo, useState } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import clsx from 'clsx';
import {
  LinkSimple,
  YoutubeLogo,
  EnvelopeSimple,
  ShoppingBag,
  SquaresFour,
  Storefront,
  Minus,
  Rss,
  Playlist,
  Star,
  ShareNetwork,
  Cube,
  MagnifyingGlass,
  TrendUp,
  Shuffle,
  Envelope
} from '@phosphor-icons/react';

import type { AvailableWidget } from '../../api/types';
import styles from './widget-gallery-modal.module.css';

interface WidgetGalleryModalProps {
  open: boolean;
  widgets: AvailableWidget[];
  onClose: () => void;
  onAdd: (widgetType: string, label?: string) => void;
  isAdding?: boolean;
}

interface GalleryItem {
  id: string;
  widget_type: string;
  name: string;
  description?: string;
  category?: string;
  icon: JSX.Element;
}

const widgetIconMap: Record<string, JSX.Element> = {
  custom_link: <LinkSimple size={32} weight="duotone" />,
  youtube_video: <YoutubeLogo size={32} weight="duotone" />,
  email_subscription: <EnvelopeSimple size={32} weight="duotone" />,
  shopify_product: <ShoppingBag size={32} weight="duotone" />,
  shopify_product_list: <SquaresFour size={32} weight="duotone" />,
  shopify_collection: <Storefront size={32} weight="duotone" />,
  divider_rule: <Minus size={32} weight="duotone" />,
  rss_feed: <Rss size={32} weight="duotone" />,
  latest_episodes: <Playlist size={32} weight="duotone" />,
  spotlight: <Star size={32} weight="duotone" />,
  social_links: <ShareNetwork size={32} weight="duotone" />,
  giphy_search: <MagnifyingGlass size={32} weight="duotone" />,
  giphy_trending: <TrendUp size={32} weight="duotone" />,
  giphy_random: <Shuffle size={32} weight="duotone" />,

};

export function WidgetGalleryModal({ open, widgets, onClose, onAdd, isAdding }: WidgetGalleryModalProps): JSX.Element {
  const [searchTerm, setSearchTerm] = useState('');
  const [activeCategory, setActiveCategory] = useState<string>('all');

  const items: GalleryItem[] = useMemo(() => {
    return widgets
      .filter(w => !['invisible_spacer', 'group_container', 'profile_carousel', 'text_html', 'heading_block', 'text_note', 'image', 'podcast_player_custom', 'giphy_search', 'giphy_trending', 'giphy_random', 'people'].includes(w.widget_id ?? ''))
      .map((widget, index) => {
        const type = widget.type ?? widget.widget_id ?? `widget-${index}`;
        return {
          id: (widget.widget_id ?? type).toString(),
          widget_type: type,
          name: widget.name ?? widget.label ?? type,
          description: widget.description ?? '',
          category: widget.category ?? 'Other',
          icon: widgetIconMap[type] ?? <Cube size={32} weight="duotone" />
        };
      });
  }, [widgets]);

  const categories = useMemo(() => {
    const set = new Set<string>();
    set.add('all');
    items.forEach((item) => set.add(item.category ?? 'Other'));
    return Array.from(set);
  }, [items]);

  const filteredItems = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();
    return items.filter((item) => {
      const matchesCategory = activeCategory === 'all' || item.category === activeCategory;
      const matchesTerm = term.length === 0 || `${item.name} ${item.description}`.toLowerCase().includes(term);
      return matchesCategory && matchesTerm;
    });
  }, [items, activeCategory, searchTerm]);

  return (
    <Dialog.Root open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content
          className={`${styles.modal} glassPanel`}
          aria-label="Widget gallery"
        >
          <header className={styles.header}>
            <div>
              <Dialog.Title asChild>
                <h2>Add a block</h2>
              </Dialog.Title>
              <Dialog.Description asChild>
                <p>Browse reusable building blocks and drop them onto your PodaBio page.</p>
              </Dialog.Description>
            </div>
            <Dialog.Close asChild>
              <button type="button" className={styles.closeButton} aria-label="Close widget gallery">
                Close
              </button>
            </Dialog.Close>
          </header>

          <div className={styles.controls}>
            <label className={styles.searchLabel}>
              <span>Search</span>
              <input
                type="search"
                value={searchTerm}
                onChange={(event) => setSearchTerm(event.target.value)}
                placeholder="Search widgets"
              />
            </label>

            <div className={styles.categoryRow} role="tablist" aria-label="Widget categories">
              {categories.map((category) => (
                <button
                  key={category}
                  type="button"
                  role="tab"
                  aria-selected={activeCategory === category}
                  className={clsx(styles.categoryButton, activeCategory === category && styles.categoryButtonActive)}
                  onClick={() => setActiveCategory(category)}
                >
                  {category === 'all' ? 'All' : category}
                </button>
              ))}
            </div>
          </div>

          <section className={styles.gridSection} aria-live="polite">
            {filteredItems.length === 0 ? (
              <p className={styles.emptyState}>No widgets match your search. Try another term or category.</p>
            ) : (
              <ul className={styles.grid}>
                {filteredItems.map((item) => (
                  <li
                    key={item.id}
                    className={styles.card}
                    onClick={() => !isAdding && onAdd(item.widget_type, item.name)}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (!isAdding) onAdd(item.widget_type, item.name);
                      }
                    }}
                    aria-disabled={isAdding}
                  >
                    <div className={styles.cardPreview}>
                      {item.icon}
                    </div>
                    <div className={styles.cardBody}>
                      <div className={styles.cardHeader}>
                        <h3>{item.name}</h3>
                        <span className={styles.cardCategory}>{item.category ?? 'Other'}</span>
                      </div>
                      <p>{item.description || 'Reusable PodaBio content block.'}</p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
