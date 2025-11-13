import { useMemo, useState } from 'react';
import clsx from 'clsx';

import type { AvailableWidget } from '../../api/types';

import styles from './widget-gallery-drawer.module.css';

interface WidgetGalleryDrawerProps {
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
  thumbnail?: string;
}

export function WidgetGalleryDrawer({ open, widgets, onClose, onAdd, isAdding }: WidgetGalleryDrawerProps): JSX.Element {
  const [searchTerm, setSearchTerm] = useState('');
  const [activeCategory, setActiveCategory] = useState<string>('all');

  const items: GalleryItem[] = useMemo(() => {
    return widgets.map((widget, index) => ({
      id: (widget.widget_id ?? widget.type ?? `widget-${index}`).toString(),
      widget_type: widget.type ?? widget.widget_id ?? `widget-${index}`,
      name: widget.name ?? widget.label ?? widget.type ?? 'Widget',
      description: widget.description ?? '',
      category: widget.category ?? 'Other',
      thumbnail: typeof widget.thumbnail === 'string' ? widget.thumbnail : undefined
    }));
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
    <div className={clsx(styles.backdrop, open && styles.backdropVisible)} aria-hidden={!open}>
      <aside className={clsx(styles.drawer, open && styles.drawerOpen)} aria-label="Widget gallery">
        <header className={styles.header}>
          <div>
            <h2>Add a block</h2>
            <p>Browse reusable building blocks and drop them onto your PodaBio page.</p>
          </div>
          <button type="button" className={styles.closeButton} onClick={onClose} aria-label="Close widget gallery">
            Close
          </button>
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
                <li key={item.id} className={styles.card}>
                  <div className={styles.cardPreview}>
                    {item.thumbnail ? (
                      <img src={item.thumbnail} alt="" />
                    ) : (
                      <div className={styles.previewPlaceholder} aria-hidden="true">
                        {item.name.slice(0, 2).toUpperCase()}
                      </div>
                    )}
                  </div>
                  <div className={styles.cardBody}>
                    <h3>{item.name}</h3>
                    <p>{item.description || 'Reusable PodaBio content block.'}</p>
                  </div>
                  <footer className={styles.cardFooter}>
                    <span className={styles.cardCategory}>{item.category ?? 'Other'}</span>
                    <button
                      type="button"
                      onClick={() => onAdd(item.widget_type, item.name)}
                      disabled={isAdding}
                    >
                      {isAdding ? 'Adding…' : 'Add block'}
                    </button>
                  </footer>
                </li>
              ))}
            </ul>
          )}
        </section>
      </aside>
    </div>
  );
}
