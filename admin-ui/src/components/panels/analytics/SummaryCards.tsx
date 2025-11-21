import { Eye, CursorClick, TrendUp, Pulse, ArrowUp, ArrowDown } from '@phosphor-icons/react';
import { type TabColorTheme } from '../../layout/tab-colors';
import { formatNumber, formatPercentage } from './analytics-utils';
import styles from '../analytics-dashboard.module.css';

interface SummaryCardsProps {
  data: {
    page_views?: number;
    total_clicks?: number;
    ctr?: number;
    top_links?: Array<{ id: number; clicks: number }>;
  };
  trend: { change: number; percentChange: number; isPositive: boolean } | null;
  activeColor: TabColorTheme;
}

export function SummaryCards({ data, trend, activeColor }: SummaryCardsProps): JSX.Element {
  return (
    <div className={styles.summaryGrid}>
      <SummaryCard
        icon={<Eye aria-hidden="true" size={20} weight="regular" />}
        label="Page Views"
        value={formatNumber(data.page_views ?? 0)}
        description="Total page visits"
        trend={trend}
        activeColor={activeColor}
      />
      <SummaryCard
        icon={<CursorClick aria-hidden="true" size={20} weight="regular" />}
        label="Total Clicks"
        value={formatNumber(data.total_clicks ?? 0)}
        description="All link clicks"
        trend={trend}
        activeColor={activeColor}
      />
      <SummaryCard
        icon={<TrendUp aria-hidden="true" size={20} weight="regular" />}
        label="Click-Through Rate"
        value={formatPercentage(data.ctr ?? 0)}
        description="Clicks per view"
        activeColor={activeColor}
      />
      <SummaryCard
        icon={<Pulse aria-hidden="true" size={20} weight="regular" />}
        label="Active Links"
        value={(data.top_links?.length ?? 0).toString()}
        description="Links with clicks"
        activeColor={activeColor}
      />
    </div>
  );
}

interface SummaryCardProps {
  icon: JSX.Element;
  label: string;
  value: string;
  description: string;
  trend?: { change: number; percentChange: number; isPositive: boolean } | null;
  activeColor: TabColorTheme;
}

function SummaryCard({ icon, label, value, description, trend, activeColor }: SummaryCardProps): JSX.Element {
  return (
    <div className={styles.summaryCard}>
      <div className={styles.summaryCardHeader}>
        <div className={styles.summaryIcon}>
          {icon}
        </div>
        {trend && (
          <div className={styles.summaryTrend}>
            <span className={`${styles.trendBadge} ${trend.isPositive ? styles.trendPositive : styles.trendNegative}`}>
              {trend.isPositive ? <ArrowUp aria-hidden="true" size={14} weight="regular" /> : <ArrowDown aria-hidden="true" size={14} weight="regular" />}
              {Math.abs(trend.percentChange).toFixed(1)}%
            </span>
          </div>
        )}
      </div>
      <div className={styles.summaryContent}>
        <span className={styles.summaryLabel}>{label}</span>
        <span className={styles.summaryValue}>{value}</span>
        <span className={styles.summaryDescription}>{description}</span>
      </div>
    </div>
  );
}

