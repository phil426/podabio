import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { useSubscriptionStatus } from '../../api/account';
import { startProTrial, subscribeToPro } from '../../api/payment';
import { queryKeys } from '../../api/utils';
import { TrialStatus } from './TrialStatus';
import styles from './billing-panel.module.css';

const PRO_MONTHLY_PRICE = 4.99;
const PRO_ANNUAL_PRICE = 53.89;

export function BillingPanel(): JSX.Element {
  const { data: subscription, isLoading } = useSubscriptionStatus();
  const queryClient = useQueryClient();
  const [billingInterval, setBillingInterval] = useState<'month' | 'year'>('month');
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const startTrialMutation = useMutation({
    mutationFn: (interval: 'month' | 'year') => startProTrial(interval),
    onSuccess: (data) => {
      if (data.checkout_url) {
        window.location.href = data.checkout_url;
      }
    },
    onError: (err: Error) => {
      setError(err.message);
      setIsProcessing(false);
    }
  });

  const subscribeMutation = useMutation({
    mutationFn: (interval: 'month' | 'year') => subscribeToPro(interval),
    onSuccess: (data) => {
      if (data.checkout_url) {
        window.location.href = data.checkout_url;
      }
    },
    onError: (err: Error) => {
      setError(err.message);
      setIsProcessing(false);
    }
  });

  const handleStartTrial = async () => {
    setError(null);
    setIsProcessing(true);
    startTrialMutation.mutate(billingInterval);
  };

  const handleSubscribe = async () => {
    setError(null);
    setIsProcessing(true);
    subscribeMutation.mutate(billingInterval);
  };

  if (isLoading) {
    return <p className={styles.emptyState}>Retrieving billing status…</p>;
  }

  if (!subscription) {
    return <p className={styles.emptyState}>We couldn't load your billing information right now.</p>;
  }

  const isRootAdmin = subscription.is_root_admin ?? false;
  const isTrial = subscription.is_trial ?? false;
  const isPro = subscription.plan_type === 'pro' || isRootAdmin;
  const currentBillingInterval = subscription.billing_interval ?? 'month';

  return (
    <div className={styles.container}>
      {isTrial && <TrialStatus subscription={subscription} />}

      {isRootAdmin && (
        <div className={styles.rootAdminBadge}>
          <strong>Root Admin</strong>
          <p>You have Pro features enabled as a root administrator.</p>
        </div>
      )}

      <section className={styles.card}>
        <header>
          <h2>Current plan</h2>
          <p>{isPro ? 'You have access to all Pro features.' : 'Upgrade to unlock more customization and analytics.'}</p>
        </header>
        <div className={styles.fieldGrid}>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Plan</span>
            <span className={styles.fieldValue}>
              {formatPlan(subscription.plan_type)} {isRootAdmin && '(Admin)'}
            </span>
          </div>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Status</span>
            <span className={styles.fieldValue}>{formatStatus(subscription.status, isTrial)}</span>
          </div>
          {isPro && !isRootAdmin && (
            <>
              {subscription.billing_interval && (
                <div className={styles.fieldRow}>
                  <span className={styles.fieldLabel}>Billing</span>
                  <span className={styles.fieldValue}>
                    {subscription.billing_interval === 'year' ? 'Annual' : 'Monthly'}
                  </span>
                </div>
              )}
              <div className={styles.fieldRow}>
                <span className={styles.fieldLabel}>Renewal</span>
                <span className={styles.fieldValue}>
                  {subscription.expires_at ? formatDate(subscription.expires_at) : 'Renews automatically'}
                </span>
              </div>
            </>
          )}
          {subscription.payment_method && (
            <div className={styles.fieldRow}>
              <span className={styles.fieldLabel}>Payment method</span>
              <span className={styles.fieldValue}>{subscription.payment_method}</span>
            </div>
          )}
        </div>
        {!isPro && (
          <footer className={styles.cardFooter}>
            <button
              type="button"
              className={styles.primaryButton}
              onClick={handleStartTrial}
              disabled={isProcessing}
              title="Start a 14-day free trial of Pro"
            >
              Start 14-Day Free Trial
            </button>
            <button
              type="button"
              className={styles.secondaryButton}
              onClick={() => window.open('/payment/support.php', '_blank')}
              title="Get help with billing and subscription questions"
            >
              Contact support
            </button>
          </footer>
        )}
        {isPro && !isRootAdmin && (
          <footer className={styles.cardFooter}>
            <button
              type="button"
              className={styles.secondaryButton}
              onClick={() => window.open('/payment/support.php', '_blank')}
              title="Manage your subscription or get help"
            >
              Manage subscription
            </button>
          </footer>
        )}
      </section>

      {!isPro && (
        <section className={styles.card}>
          <header>
            <h2>Upgrade to Pro</h2>
            <p>Get access to all features with our Pro plan.</p>
          </header>
          
          <div className={styles.billingToggle}>
            <button
              type="button"
              className={billingInterval === 'month' ? styles.toggleActive : styles.toggleButton}
              onClick={() => setBillingInterval('month')}
            >
              Monthly
            </button>
            <button
              type="button"
              className={billingInterval === 'year' ? styles.toggleActive : styles.toggleButton}
              onClick={() => setBillingInterval('year')}
            >
              Annual <span className={styles.savingsBadge}>Save 10%</span>
            </button>
          </div>

          <div className={styles.pricingDisplay}>
            <div className={styles.price}>
              ${billingInterval === 'year' ? PRO_ANNUAL_PRICE : PRO_MONTHLY_PRICE}
              <span className={styles.pricePeriod}>/{billingInterval === 'year' ? 'year' : 'month'}</span>
            </div>
            {billingInterval === 'year' && (
              <p className={styles.annualNote}>
                ${(PRO_ANNUAL_PRICE / 12).toFixed(2)}/month billed annually
              </p>
            )}
          </div>

          {error && <p className={styles.errorMessage}>{error}</p>}

          <footer className={styles.cardFooter}>
            <button
              type="button"
              className={styles.primaryButton}
              onClick={handleSubscribe}
              disabled={isProcessing}
              title="Subscribe to Pro plan"
            >
              {isProcessing ? 'Processing...' : `Subscribe to Pro - $${billingInterval === 'year' ? PRO_ANNUAL_PRICE : PRO_MONTHLY_PRICE}/${billingInterval === 'year' ? 'yr' : 'mo'}`}
            </button>
          </footer>
        </section>
      )}

      {subscription.invoices && subscription.invoices.length > 0 && (
        <section className={styles.card}>
          <header>
            <h2>Recent invoices</h2>
            <p>Download invoices for your records.</p>
          </header>
          <ul className={styles.invoiceList}>
            {subscription.invoices.map((invoice) => (
              <li key={invoice.id} className={styles.invoiceRow}>
                <div>
                  <p className={styles.invoiceAmount}>{formatCurrency(invoice.amount, invoice.currency)}</p>
                  <p className={styles.invoiceMeta}>
                    {formatDate(invoice.issued_at)} · {formatStatus(invoice.status)}
                  </p>
                </div>
                {invoice.hosted_invoice_url ? (
                  <a
                    className={styles.secondaryButton}
                    href={invoice.hosted_invoice_url}
                    target="_blank"
                    rel="noreferrer"
                    title="Open this invoice in a new tab"
                  >
                    View
                  </a>
                ) : (
                  <span className={styles.invoicePlaceholder}>—</span>
                )}
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}

function formatPlan(plan: string): string {
  const value = plan?.toLowerCase?.() ?? 'free';
  switch (value) {
    case 'pro':
      return 'Pro';
    case 'free':
    default:
      return 'Free';
  }
}

function formatStatus(status: string, isTrial?: boolean): string {
  if (isTrial) {
    return 'Trial';
  }
  const value = status?.toLowerCase() ?? 'active';
  switch (value) {
    case 'pending':
      return 'Payment pending';
    case 'failed':
      return 'Payment failed';
    case 'canceled':
      return 'Canceled';
    case 'active':
    default:
      return 'Active';
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

function formatCurrency(amount: number, currency?: string): string {
  try {
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency: (currency ?? 'usd').toUpperCase()
    }).format(amount / 100);
  } catch {
    return `${amount / 100} ${currency ?? 'USD'}`;
  }
}

