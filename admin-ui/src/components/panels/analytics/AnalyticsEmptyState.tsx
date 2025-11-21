import { ChartBar } from '@phosphor-icons/react';
import styles from '../analytics-dashboard.module.css';

interface AnalyticsEmptyStateProps {
  message?: string;
  subtext?: string;
}

export function AnalyticsEmptyState({ 
  message = 'No analytics data available yet.',
  subtext = 'Data will appear here after your links receive clicks.'
}: AnalyticsEmptyStateProps): JSX.Element {
  return (
    <div className={styles.emptyState}>
      <ChartBar className={styles.emptyIcon} aria-hidden="true" size={48} weight="regular" />
      <p>{message}</p>
      <p className={styles.emptySubtext}>{subtext}</p>
    </div>
  );
}

