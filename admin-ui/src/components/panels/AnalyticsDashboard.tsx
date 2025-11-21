import { useMemo, useState } from 'react';
import { ChartBar } from '@phosphor-icons/react';

import { useLinkAnalytics } from '../../api/analytics';
import { type TabColorTheme } from '../layout/tab-colors';
import { AnalyticsHeader } from './analytics/AnalyticsHeader';
import { AnalyticsEmptyState } from './analytics/AnalyticsEmptyState';
import { SummaryCards } from './analytics/SummaryCards';
import { ClickChart } from './analytics/ClickChart';
import { TopLinksList } from './analytics/TopLinksList';
import { processChartData, calculateTrend } from './analytics/analytics-utils';

import styles from './analytics-dashboard.module.css';

export function AnalyticsDashboard({ activeColor }: { activeColor: TabColorTheme }): JSX.Element {
  const [period, setPeriod] = useState<string>('month');
  const { data, isLoading, isError, error, refetch, isFetching } = useLinkAnalytics(period);

  const chartData = useMemo(() => {
    return processChartData(data?.time_series ?? []);
  }, [data?.time_series]);

  const trend = useMemo(() => {
    return calculateTrend(data?.time_series ?? []);
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
      <AnalyticsHeader
        period={period}
        onPeriodChange={setPeriod}
        onRefresh={() => refetch()}
        isFetching={isFetching}
      />

      <div className={styles.content}>
        {isLoading ? (
          <div className={styles.loadingState}>
            <ChartBar className={styles.loadingIcon} aria-hidden="true" size={24} weight="regular" />
            <p>Loading analytics…</p>
          </div>
        ) : isError ? (
          <div className={styles.errorState}>
            <p>{error instanceof Error ? error.message : 'Unable to load analytics.'}</p>
          </div>
        ) : !data ? (
          <AnalyticsEmptyState />
        ) : (
          <>
            <SummaryCards data={data} trend={trend} activeColor={activeColor} />
            {chartData.points.length > 0 && (
              <ClickChart chartData={chartData} activeColor={activeColor} />
            )}
            {data.top_links && data.top_links.length > 0 && (
              <TopLinksList links={data.top_links} activeColor={activeColor} />
            )}
            {(!data.top_links || data.top_links.length === 0) && chartData.points.length === 0 && (
              <AnalyticsEmptyState 
                message="No link activity yet."
                subtext="After you publish and share your page, click data will appear here."
              />
            )}
          </>
        )}
      </div>
    </div>
  );
}

