import { ArrowClockwise, ChartBar } from '@phosphor-icons/react';
import styles from '../analytics-dashboard.module.css';

const PERIOD_OPTIONS = [
  { id: 'day', label: '24h' },
  { id: 'week', label: '7d' },
  { id: 'month', label: '30d' },
  { id: 'all', label: 'All time' }
];

interface AnalyticsHeaderProps {
  period: string;
  onPeriodChange: (period: string) => void;
  onRefresh: () => void;
  isFetching: boolean;
}

export function AnalyticsHeader({ period, onPeriodChange, onRefresh, isFetching }: AnalyticsHeaderProps): JSX.Element {
  return (
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
                onClick={() => onPeriodChange(option.id)}
                disabled={isFetching && option.id === period}
              >
                {option.label}
              </button>
            ))}
          </div>
          <button 
            type="button" 
            className={styles.refreshButton} 
            onClick={onRefresh} 
            disabled={isFetching}
            aria-label="Refresh data"
          >
            <ArrowClockwise className={`${styles.refreshIcon} ${isFetching ? styles.spinning : ''}`} aria-hidden="true" size={16} weight="regular" />
            Refresh
          </button>
        </div>
      </div>
    </header>
  );
}

