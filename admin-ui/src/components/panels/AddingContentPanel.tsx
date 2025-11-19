import { useMemo, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import clsx from 'clsx';
import { useAvailableWidgetsQuery, useAddWidgetMutation } from '../../api/widgets';
import { useWidgetSelection } from '../../state/widgetSelection';
import { normalizeImageUrl } from '../../api/utils';
import type { AvailableWidget } from '../../api/types';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './adding-content-panel.module.css';

interface AddingContentPanelProps {
  activeColor: TabColorTheme;
}

export function AddingContentPanel({ activeColor }: AddingContentPanelProps): JSX.Element {
  const { data: availableWidgets, isLoading } = useAvailableWidgetsQuery();
  const addMutation = useAddWidgetMutation();
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeCategory, setActiveCategory] = useState<string>('all');

  const items = useMemo(() => {
    if (!availableWidgets) return [];
    return availableWidgets.map((widget, index) => ({
      id: (widget.widget_id ?? widget.type ?? `widget-${index}`).toString(),
      widget_type: widget.type ?? widget.widget_id ?? `widget-${index}`,
      name: widget.name ?? widget.label ?? widget.type ?? 'Widget',
      description: widget.description ?? '',
      category: widget.category ?? 'Other',
      thumbnail: typeof widget.thumbnail === 'string' ? widget.thumbnail : undefined
    }));
  }, [availableWidgets]);

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

  const handleAddWidget = (widgetType: string, label?: string) => {
    addMutation.mutate(
      {
        widget_type: widgetType,
        title: label ?? widgetType,
      },
      {
        onSuccess: (response) => {
          const typed = (response ?? {}) as { widget_id?: number | string; data?: { widget_id?: number | string } };
          const widgetId = typed.widget_id ?? typed.data?.widget_id;
          if (widgetId) {
            selectWidget(String(widgetId));
          }
        }
      }
    );
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
              <h2>Add a block</h2>
              <p>Browse reusable building blocks and add them to your page</p>
            </header>

            <div className={styles.controls}>
              <label className={styles.searchLabel}>
                <span className={styles.searchLabelText}>Search</span>
                <input
                  type="search"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Search widgets"
                  className={styles.searchInput}
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
              {isLoading ? (
                <p className={styles.emptyState}>Loading widgets…</p>
              ) : filteredItems.length === 0 ? (
                <p className={styles.emptyState}>No widgets match your search. Try another term or category.</p>
              ) : (
                <ul className={styles.grid}>
                  <AnimatePresence mode="popLayout">
                    {filteredItems.map((item, index) => (
                      <motion.li
                        key={item.id}
                        className={styles.card}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9 }}
                        transition={{ duration: 0.2, delay: index * 0.02 }}
                        whileHover={{ y: -4 }}
                      >
                        <div className={styles.cardPreview}>
                          {item.thumbnail ? (
                            <img src={normalizeImageUrl(item.thumbnail)} alt="" />
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
                            onClick={() => handleAddWidget(item.widget_type, item.name)}
                            disabled={addMutation.isPending}
                            className={styles.addButton}
                          >
                            {addMutation.isPending ? 'Adding…' : 'Add block'}
                          </button>
                        </footer>
                      </motion.li>
                    ))}
                  </AnimatePresence>
                </ul>
              )}
            </section>
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  );
}

