import { useMemo, useState } from 'react';

import { useWidgetAnalytics } from '../../api/analytics';
import { useWidgetSelection } from '../../state/widgetSelection';
import styles from './widget-analytics-panel.module.css';

const PERIOD_OPTIONS = [
  { id: 'day', label: '24h' },
  { id: 'week', label: '7d' },
  { id: 'month', label: '30d' }
];

export function WidgetAnalyticsPanel(): JSX.Element {
  const [period, setPeriod] = useState<string>('month');
  const { data, isLoading, isError, error, refetch, isFetching } = useWidgetAnalytics(period);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);

  const stats = useMemo(() => {
    if (!data?.widgets?.length) {
      return {
        list: [],
        maxViews: 0
      };
    }
    const maxViews = data.widgets.reduce((max, entry) => Math.max(max, entry.view_count), 0);
    const list = data.widgets.map((entry) => ({
      id: entry.widget_id.toString(),
      title: entry.title,
      views: entry.view_count,
      clicks: entry.click_count,
      ctr: entry.ctr
    }));
    return { list, maxViews };
  }, [data]);

  return (
    <section className={styles.panel} aria-label="Widget analytics">
      <header className={styles.header}>
        <div>
          <h3>Widget analytics</h3>
          <p>Track engagement for each block to decide what to highlight next.</p>
        </div>
        <div className={styles.periodToggle} role="radiogroup" aria-label="Analytics period">
          {PERIOD_OPTIONS.map((option) => (
            <button
              key={option.id}
              type="button"
              role="radio"
              aria-checked={option.id === period}
              className={option.id === period ? styles.periodActive : styles.periodButton}
              onClick={() => setPeriod(option.id)}
              disabled={isFetching && option.id === period}
            >
              {option.label}
            </button>
          ))}
        </div>
      </header>

      <div className={styles.summaryRow}>
        <div>
          <span className={styles.metricLabel}>Page views</span>
          <span className={styles.metricValue}>{formatNumber(data?.page_views)}</span>
        </div>
        <div>
          <span className={styles.metricLabel}>Total clicks</span>
          <span className={styles.metricValue}>{formatNumber(data?.total_clicks)}</span>
        </div>
        <button type="button" className={styles.refreshButton} onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? 'Refreshing…' : 'Refresh'}
        </button>
      </div>

      {isLoading ? (
        <p className={styles.placeholder}>Loading analytics…</p>
      ) : isError ? (
        <p className={styles.error}>{error instanceof Error ? error.message : 'Unable to load analytics.'}</p>
      ) : !stats.list.length ? (
        <p className={styles.placeholder}>No widget activity yet. After you publish, engagement data will appear here.</p>
      ) : (
        <ul className={styles.list}>
          {stats.list.map((entry) => (
            <li key={entry.id} className={entry.id === selectedWidgetId ? styles.rowActive : undefined}>
              <button
                type="button"
                onClick={() => selectWidget(entry.id)}
                className={styles.rowButton}
                aria-label={`Select ${entry.title}`}
              >
                <div className={styles.rowHeader}>
                  <span className={styles.rowTitle}>{entry.title}</span>
                  <span className={styles.rowMetric}>{formatCTR(entry.ctr)} CTR</span>
                </div>
                <div className={styles.barTrack} aria-hidden="true">
                  <span
                    className={styles.barFill}
                    style={{ width: `${stats.maxViews > 0 ? Math.max((entry.views / stats.maxViews) * 100, 6) : 6}%` }}
                  />
                </div>
                <div className={styles.rowMeta}>
                  <span>{formatNumber(entry.views)} views</span>
                  <span>{formatNumber(entry.clicks)} clicks</span>
                </div>
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

function formatNumber(value: number | undefined): string {
  if (!value) {
    return '0';
  }
  if (value > 999) {
    return `${(value / 1000).toFixed(1)}k`;
  }
  return value.toLocaleString();
}

function formatCTR(value: number | undefined): string {
  if (value === undefined || value === null) {
    return '0%';
  }
  return `${(value * 100).toFixed(1)}%`;
}

