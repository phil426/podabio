import { useMemo, useState } from 'react';
import { LuTrendingUp, LuMousePointerClick, LuEye, LuChartColumn, LuRefreshCw, LuExternalLink, LuArrowUp, LuArrowDown, LuActivity } from 'react-icons/lu';

import { useLinkAnalytics } from '../../api/analytics';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './analytics-dashboard.module.css';

const PERIOD_OPTIONS = [
  { id: 'day', label: '24h' },
  { id: 'week', label: '7d' },
  { id: 'month', label: '30d' },
  { id: 'all', label: 'All time' }
];

export function AnalyticsDashboard({ activeColor }: { activeColor: TabColorTheme }): JSX.Element {
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

  // Calculate trend (comparing first half vs second half of time series)
  const trend = useMemo(() => {
    if (!data?.time_series || data.time_series.length < 2) return null;
    const mid = Math.floor(data.time_series.length / 2);
    const firstHalf = data.time_series.slice(0, mid).reduce((sum, d) => sum + d.clicks, 0);
    const secondHalf = data.time_series.slice(mid).reduce((sum, d) => sum + d.clicks, 0);
    const change = secondHalf - firstHalf;
    const percentChange = firstHalf > 0 ? ((change / firstHalf) * 100) : 0;
    return { change, percentChange, isPositive: change >= 0 };
  }, [data?.time_series]);

  return (
    <div 
      className={styles.dashboard} 
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      {/* Header */}
      <header className={styles.header}>
        <div className={styles.headerContent}>
          <div>
            <h1 className={styles.title}>Analytics Dashboard</h1>
            <p className={styles.subtitle}>Track clicks and engagement for all your links</p>
          </div>
          <div className={styles.headerActions}>
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
            <button 
              type="button" 
              className={styles.refreshButton} 
              onClick={() => refetch()} 
              disabled={isFetching}
              aria-label="Refresh data"
            >
              <LuRefreshCw className={`${styles.refreshIcon} ${isFetching ? styles.spinning : ''}`} aria-hidden="true" />
              Refresh
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className={styles.content}>
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
                <div className={styles.summaryCardHeader}>
                  <div className={styles.summaryIcon}>
                    <LuEye aria-hidden="true" />
                  </div>
                  <div className={styles.summaryTrend}>
                    {trend && (
                      <span className={`${styles.trendBadge} ${trend.isPositive ? styles.trendPositive : styles.trendNegative}`}>
                        {trend.isPositive ? <LuArrowUp aria-hidden="true" /> : <LuArrowDown aria-hidden="true" />}
                        {Math.abs(trend.percentChange).toFixed(1)}%
                      </span>
                    )}
                  </div>
                </div>
                <div className={styles.summaryContent}>
                  <span className={styles.summaryLabel}>Page Views</span>
                  <span className={styles.summaryValue}>{formatNumber(data.page_views ?? 0)}</span>
                  <span className={styles.summaryDescription}>Total page visits</span>
                </div>
              </div>

              <div className={styles.summaryCard}>
                <div className={styles.summaryCardHeader}>
                  <div className={styles.summaryIcon}>
                    <LuMousePointerClick aria-hidden="true" />
                  </div>
                  <div className={styles.summaryTrend}>
                    {trend && (
                      <span className={`${styles.trendBadge} ${trend.isPositive ? styles.trendPositive : styles.trendNegative}`}>
                        {trend.isPositive ? <LuArrowUp aria-hidden="true" /> : <LuArrowDown aria-hidden="true" />}
                        {Math.abs(trend.percentChange).toFixed(1)}%
                      </span>
                    )}
                  </div>
                </div>
                <div className={styles.summaryContent}>
                  <span className={styles.summaryLabel}>Total Clicks</span>
                  <span className={styles.summaryValue}>{formatNumber(data.total_clicks ?? 0)}</span>
                  <span className={styles.summaryDescription}>All link clicks</span>
                </div>
              </div>

              <div className={styles.summaryCard}>
                <div className={styles.summaryCardHeader}>
                  <div className={styles.summaryIcon}>
                    <LuTrendingUp aria-hidden="true" />
                  </div>
                </div>
                <div className={styles.summaryContent}>
                  <span className={styles.summaryLabel}>Click-Through Rate</span>
                  <span className={styles.summaryValue}>{formatPercentage(data.ctr ?? 0)}</span>
                  <span className={styles.summaryDescription}>Clicks per view</span>
                </div>
              </div>

              <div className={styles.summaryCard}>
                <div className={styles.summaryCardHeader}>
                  <div className={styles.summaryIcon}>
                    <LuActivity aria-hidden="true" />
                  </div>
                </div>
                <div className={styles.summaryContent}>
                  <span className={styles.summaryLabel}>Active Links</span>
                  <span className={styles.summaryValue}>{data.top_links?.length ?? 0}</span>
                  <span className={styles.summaryDescription}>Links with clicks</span>
                </div>
              </div>
            </div>

            {/* Main Chart Section */}
            {chartData.points.length > 0 && (
              <div className={styles.chartSection}>
                <div className={styles.chartHeader}>
                  <div>
                    <h2 className={styles.chartTitle}>Clicks Over Time</h2>
                    <p className={styles.chartSubtitle}>Daily click activity for the selected period</p>
                  </div>
                </div>
                <div className={styles.chartContainer}>
                  <svg className={styles.chart} viewBox="0 0 1000 300" preserveAspectRatio="none">
                    <defs>
                      <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.4" />
                        <stop offset="50%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.15" />
                        <stop offset="100%" stopColor="var(--active-tab-color, #2563eb)" stopOpacity="0.05" />
                      </linearGradient>
                      <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                          <feMergeNode in="coloredBlur"/>
                          <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                      </filter>
                    </defs>
                    {/* Grid lines */}
                    {[0, 25, 50, 75, 100].map((y) => (
                      <line
                        key={y}
                        x1="0"
                        y1={300 - (y * 2.4)}
                        x2="1000"
                        y2={300 - (y * 2.4)}
                        stroke="rgba(15, 23, 42, 0.08)"
                        strokeWidth="1"
                        strokeDasharray="4,4"
                      />
                    ))}
                    {/* Area fill */}
                    <path
                      d={`M 0 300 ${chartData.points.map((p, i) => `L ${(i / (chartData.points.length - 1 || 1)) * 1000} ${300 - p.y * 2.4}`).join(' ')} L 1000 300 Z`}
                      fill="url(#chartGradient)"
                      className={styles.chartArea}
                    />
                    {/* Line */}
                    <polyline
                      points={chartData.points.map((p, i) => `${(i / (chartData.points.length - 1 || 1)) * 1000},${300 - p.y * 2.4}`).join(' ')}
                      fill="none"
                      stroke="var(--active-tab-color, #2563eb)"
                      strokeWidth="3"
                      className={styles.chartLine}
                      filter="url(#glow)"
                    />
                    {/* Data points */}
                    {chartData.points.map((point, index) => (
                      <g key={index}>
                        <circle
                          cx={(index / (chartData.points.length - 1 || 1)) * 1000}
                          cy={300 - point.y * 2.4}
                          r="5"
                          fill="var(--active-tab-color, #2563eb)"
                          className={styles.chartPoint}
                          stroke="#ffffff"
                          strokeWidth="2"
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
                  <div className={styles.chartStats}>
                    <div className={styles.chartStat}>
                      <span className={styles.chartStatLabel}>Peak</span>
                      <span className={styles.chartStatValue}>{chartData.maxClicks} clicks</span>
                    </div>
                    <div className={styles.chartStat}>
                      <span className={styles.chartStatLabel}>Average</span>
                      <span className={styles.chartStatValue}>
                        {chartData.points.length > 0 
                          ? Math.round(chartData.points.reduce((sum, p) => sum + p.value, 0) / chartData.points.length)
                          : 0} clicks/day
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Top Links Section */}
            {data.top_links && data.top_links.length > 0 && (
              <div className={styles.topLinksSection}>
                <div className={styles.sectionHeader}>
                  <div>
                    <h2 className={styles.sectionTitle}>Top Performing Links</h2>
                    <p className={styles.sectionSubtitle}>Your most clicked links ranked by performance</p>
                  </div>
                </div>
                <div className={styles.topLinksGrid}>
                  {data.top_links.slice(0, 12).map((link, index) => (
                    <div key={link.id} className={styles.topLinkCard}>
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
                                {truncateUrl(link.url, 35)}
                                <LuExternalLink className={styles.externalIcon} aria-hidden="true" />
                              </a>
                            ) : (
                              <span className={styles.topLinkType}>{link.type}</span>
                            )}
                          </span>
                        </div>
                        <div className={styles.topLinkStats}>
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
                    </div>
                  ))}
                </div>
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
      </div>
    </div>
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

