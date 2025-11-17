import { useNavigate } from 'react-router-dom';

import { useAccountProfile, useSubscriptionStatus, useAuthMethods } from '../../api/account';
import styles from './account-summary-panel.module.css';

export function AccountSummaryPanel(): JSX.Element {
  const navigate = useNavigate();
  const { data: profile } = useAccountProfile();
  const { data: subscription } = useSubscriptionStatus();
  const { data: methods } = useAuthMethods();

  return (
    <aside className={styles.container}>
      <section className={styles.card}>
        <header>
          <h2>Plan status</h2>
          <p>{formatPlan(subscription?.plan_type ?? 'free')} subscriber</p>
        </header>
        <div className={styles.statBlock}>
          <span className={styles.statLabel}>Next renewal</span>
          <span className={styles.statValue}>
            {subscription?.expires_at ? formatDate(subscription.expires_at) : 'Renews monthly'}
          </span>
        </div>
        <div className={styles.statBlock}>
          <span className={styles.statLabel}>Payment method</span>
          <span className={styles.statValue}>{subscription?.payment_method ?? 'Not set'}</span>
        </div>
        <footer>
          <button type="button" className={styles.primaryButton} onClick={() => window.open('/payment/checkout.php', '_blank')}>
            Manage plan
          </button>
        </footer>
      </section>

      <section className={styles.card}>
        <header>
          <h2>Login health</h2>
          <p>Recommended to keep at least two login methods active.</p>
        </header>
        <div className={styles.pillRow}>
          <span className={styles.pill} data-active={methods?.has_password ?? false}>
            Password
          </span>
          <span className={styles.pill} data-active={methods?.has_google ?? false}>
            Google
          </span>
          <span className={styles.pill} data-active="false">
            2FA (soon)
          </span>
        </div>
        <footer>
          <button type="button" className={styles.secondaryButton} onClick={() => navigate('/account/security')}>
            Update security
          </button>
        </footer>
      </section>

      <section className={styles.card}>
        <header>
          <h2>Profile summary</h2>
          <p>Quick snapshot of your account identity.</p>
        </header>
        <div className={styles.summaryRow}>
          <span className={styles.summaryLabel}>Display name</span>
          <span className={styles.summaryValue}>{profile?.name || '—'}</span>
        </div>
        <div className={styles.summaryRow}>
          <span className={styles.summaryLabel}>Email</span>
          <span className={styles.summaryValue}>{profile?.email ?? '—'}</span>
        </div>
        <footer>
          <button type="button" className={styles.secondaryButton} onClick={() => navigate('/account/profile')}>
            Edit contact info
          </button>
        </footer>
      </section>
    </aside>
  );
}

function formatPlan(plan: string): string {
  const value = plan?.toLowerCase?.() ?? 'free';
  switch (value) {
    case 'premium':
      return 'Premium';
    case 'pro':
      return 'Pro';
    case 'team':
      return 'Team';
    case 'free':
    default:
      return 'Free';
  }
}

function formatDate(iso: string): string {
  try {
    const date = new Date(iso);
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(date);
  } catch {
    return iso;
  }
}

