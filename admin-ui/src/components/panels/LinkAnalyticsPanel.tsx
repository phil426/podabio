import { useMemo, useState } from 'react';
import { LuTrendingUp, LuMousePointerClick, LuEye, LuChartColumn, LuRefreshCw, LuExternalLink } from 'react-icons/lu';

import { useLinkAnalytics } from '../../api/analytics';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './link-analytics-panel.module.css';

const PERIOD_OPTIONS = [
  { id: 'day', label: '24h' },
  { id: 'week', label: '7d' },
  { id: 'month', label: '30d' },
  { id: 'all', label: 'All time' }
];

export function LinkAnalyticsPanel({ activeColor }: { activeColor: TabColorTheme }): JSX.Element {
  const [period, setPeriod] = useState<string>('month');
  const { data, isLoading, isError, error, refetch, isFetching } = useLinkAnalytics(period);

  const chartData = useMemo(() => {
    if (!data?.time_series || data.time_series.length === 0) {
      return { maxClicks: 0, points: [] };
    }
    
    const maxClicks = Math.max(...data.time_series.map(d => d.clicks), 1);
    const points = data.time_series.map((point, index) => ({
      x: index,
      y: maxClicks > 0 ? (point.clicks / maxClicks) * 100 : 0,
      value: point.clicks,
      date: point.date
    }));
    
    return { maxClicks, points };
  }, [data?.time_series]);

  return (
    <section 
      className={styles.panel} 
      aria-label="Link analytics"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>Link Analytics</h3>
          <p>Track clicks and engagement for all your links</p>
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

      {isLoading ? (
        <div className={styles.loadingState}>
          <LuChartColumn className={styles.loadingIcon} aria-hidden="true" />
          <p>Loading analytics…</p>
        </div>
      ) : isError ? (
        <div className={styles.errorState}>
          <p>{error instanceof Error ? error.message : 'Unable to load analytics.'}</p>
        </div>
      ) : !data ? (
          <div className={styles.emptyState}>
            <LuChartColumn className={styles.emptyIcon} aria-hidden="true" />
            <p>No analytics data available yet.</p>
            <p className={styles.emptySubtext}>Data will appear here after your links receive clicks.</p>
          </div>
      ) : (
        <>
          {/* Summary Cards */}
          <div className={styles.summaryGrid}>
            <div className={styles.summaryCard}>
              <div className={styles.summaryIcon}>
                <LuEye aria-hidden="true" />
              </div>
              <div className={styles.summaryContent}>
                <span className={styles.summaryLabel}>Page Views</span>
                <span className={styles.summaryValue}>{formatNumber(data.page_views ?? 0)}</span>
              </div>
            </div>
            <div className={styles.summaryCard}>
              <div className={styles.summaryIcon}>
                <LuMousePointerClick aria-hidden="true" />
              </div>
              <div className={styles.summaryContent}>
                <span className={styles.summaryLabel}>Total Clicks</span>
                <span className={styles.summaryValue}>{formatNumber(data.total_clicks ?? 0)}</span>
              </div>
            </div>
            <div className={styles.summaryCard}>
              <div className={styles.summaryIcon}>
                <LuTrendingUp aria-hidden="true" />
              </div>
              <div className={styles.summaryContent}>
                <span className={styles.summaryLabel}>Click-Through Rate</span>
                <span className={styles.summaryValue}>{formatPercentage(data.ctr ?? 0)}</span>
              </div>
            </div>
          </div>

          {/* Time Series Chart */}
          {chartData.points.length > 0 && (
            <div className={styles.chartSection}>
              <div className={styles.chartHeader}>
                <h4>Clicks Over Time</h4>
                <button 
                  type="button" 
                  className={styles.refreshButton} 
                  onClick={() => refetch()} 
                  disabled={isFetching}
                  aria-label="Refresh data"
                >
                  <LuRefreshCw className={isFetching ? styles.spinning : ''} aria-hidden="true" />
                </button>
              </div>
              <div className={styles.chartContainer}>
                <svg className={styles.chart} viewBox="0 0 800 200" preserveAspectRatio="none">
                  <defs>
                    <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.3" />
                      <stop offset="100%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.05" />
                    </linearGradient>
                  </defs>
                  {/* Area fill */}
                  <path
                    d={`M 0 200 ${chartData.points.map((p, i) => `L ${(i / (chartData.points.length - 1 || 1)) * 800} ${200 - p.y * 2}`).join(' ')} L 800 200 Z`}
                    fill="url(#chartGradient)"
                    className={styles.chartArea}
                  />
                  {/* Line */}
                  <polyline
                    points={chartData.points.map((p, i) => `${(i / (chartData.points.length - 1 || 1)) * 800},${200 - p.y * 2}`).join(' ')}
                    fill="none"
                    stroke="var(--active-tab-color, #2563eb)"
                    strokeWidth="2.5"
                    className={styles.chartLine}
                  />
                  {/* Data points */}
                  {chartData.points.map((point, index) => (
                    <g key={index}>
                      <circle
                        cx={(index / (chartData.points.length - 1 || 1)) * 800}
                        cy={200 - point.y * 2}
                        r="4"
                        fill="var(--active-tab-color, #2563eb)"
                        className={styles.chartPoint}
                      />
                      <title>{`${point.date}: ${point.value} clicks`}</title>
                    </g>
                  ))}
                </svg>
                <div className={styles.chartLabels}>
                  {chartData.points.length > 0 && (
                    <>
                      <span className={styles.chartLabelStart}>
                        {formatDate(chartData.points[0].date)}
                      </span>
                      <span className={styles.chartLabelEnd}>
                        {formatDate(chartData.points[chartData.points.length - 1].date)}
                      </span>
                    </>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Top Links */}
          {data.top_links && data.top_links.length > 0 && (
            <div className={styles.topLinksSection}>
              <h4>Top Performing Links</h4>
              <ul className={styles.topLinksList}>
                {data.top_links.map((link, index) => (
                  <li key={link.id} className={styles.topLinkItem}>
                    <div className={styles.topLinkRank}>{index + 1}</div>
                    <div className={styles.topLinkContent}>
                      <div className={styles.topLinkHeader}>
                        <span className={styles.topLinkTitle}>{link.title}</span>
                        <span className={styles.topLinkCtr}>{formatPercentage(link.ctr)} CTR</span>
                      </div>
                      <div className={styles.topLinkMeta}>
                        <span className={styles.topLinkUrl}>
                          {link.url ? (
                            <a 
                              href={link.url} 
                              target="_blank" 
                              rel="noopener noreferrer"
                              className={styles.topLinkUrlLink}
                              onClick={(e) => e.stopPropagation()}
                            >
                              {truncateUrl(link.url)}
                              <LuExternalLink className={styles.externalIcon} aria-hidden="true" />
                            </a>
                          ) : (
                            <span className={styles.topLinkType}>{link.type}</span>
                          )}
                        </span>
                        <span className={styles.topLinkClicks}>{formatNumber(link.clicks)} clicks</span>
                      </div>
                      <div className={styles.topLinkBar}>
                        <div 
                          className={styles.topLinkBarFill}
                          style={{ 
                            width: `${data.top_links && data.top_links[0]?.clicks ? (link.clicks / data.top_links[0].clicks) * 100 : 0}%` 
                          }}
                        />
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {(!data.top_links || data.top_links.length === 0) && chartData.points.length === 0 && (
            <div className={styles.emptyState}>
              <LuChartColumn className={styles.emptyIcon} aria-hidden="true" />
              <p>No link activity yet.</p>
              <p className={styles.emptySubtext}>After you publish and share your page, click data will appear here.</p>
            </div>
          )}
        </>
      )}
    </section>
  );
}

function formatNumber(value: number): string {
  if (value === 0) return '0';
  if (value < 1000) return value.toString();
  if (value < 1000000) return `${(value / 1000).toFixed(1)}k`;
  return `${(value / 1000000).toFixed(1)}M`;
}

function formatPercentage(value: number): string {
  return `${value.toFixed(1)}%`;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function truncateUrl(url: string, maxLength: number = 40): string {
  if (url.length <= maxLength) return url;
  try {
    const urlObj = new URL(url);
    const host = urlObj.hostname.replace('www.', '');
    const path = urlObj.pathname;
    const full = `${host}${path}`;
    if (full.length <= maxLength) return full;
    return `${full.substring(0, maxLength - 3)}...`;
  } catch {
    return url.length > maxLength ? `${url.substring(0, maxLength - 3)}...` : url;
  }
}

