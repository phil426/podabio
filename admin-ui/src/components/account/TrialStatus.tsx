import type { BillingInfo } from '../../api/types';
import styles from './trial-status.module.css';

interface TrialStatusProps {
  subscription: BillingInfo;
}

export function TrialStatus({ subscription }: TrialStatusProps): JSX.Element | null {
  if (!subscription.is_trial || !subscription.trial_ends_at) {
    return null;
  }

  const trialEndDate = new Date(subscription.trial_ends_at);
  const now = new Date();
  const daysRemaining = Math.max(0, Math.ceil((trialEndDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)));

  if (daysRemaining === 0) {
    return (
      <div className={styles.container} data-trial-status="ending">
        <div className={styles.content}>
          <strong>Trial ending today</strong>
          <p>Your trial ends today. Upgrade to continue using Pro features.</p>
        </div>
      </div>
    );
  }

  if (daysRemaining <= 3) {
    return (
      <div className={styles.container} data-trial-status="ending-soon">
        <div className={styles.content}>
          <strong>{daysRemaining} day{daysRemaining !== 1 ? 's' : ''} remaining</strong>
          <p>Your trial ends {formatDate(subscription.trial_ends_at)}. Upgrade now to continue using Pro features.</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container} data-trial-status="active">
      <div className={styles.content}>
        <strong>{daysRemaining} days remaining</strong>
        <p>You're on a free trial of Pro. After {formatDate(subscription.trial_ends_at)}, you'll be automatically charged.</p>
      </div>
    </div>
  );
}

function formatDate(iso: string): string {
  try {
    const date = new Date(iso);
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  } catch {
    return iso;
  }
}

