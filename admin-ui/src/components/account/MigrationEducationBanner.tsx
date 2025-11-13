import { useEffect, useMemo, useState } from 'react';

import { trackTelemetry } from '../../services/telemetry';
import styles from './migration-education-banner.module.css';

const STORAGE_KEY = 'podabio.accountEducation.dismissedAt';

export function MigrationEducationBanner(): JSX.Element | null {
  const [isVisible, setIsVisible] = useState<boolean>(() => {
    if (typeof window === 'undefined') {
      return true;
    }
    return !localStorage.getItem(STORAGE_KEY);
  });

  useEffect(() => {
    if (isVisible) {
      trackTelemetry({ event: 'education.account_banner_viewed' });
    }
  }, [isVisible]);

  const checklist = useMemo(
    () => [
      { id: 'profile', title: 'Profile snapshot', description: 'Confirm your display name and contact email after migration.' },
      { id: 'security', title: 'Security health', description: 'Link Google or reset your password to ensure frictionless logins.' },
      {
        id: 'billing',
        title: 'Billing history',
        description: 'Find invoices and upgrade options without leaving Studio.'
      }
    ],
    []
  );

  if (!isVisible) {
    return null;
  }

  const handleDismiss = () => {
    localStorage.setItem(STORAGE_KEY, new Date().toISOString());
    setIsVisible(false);
    trackTelemetry({ event: 'education.account_banner_dismissed' });
  };

  const handleGuidedTour = () => {
    trackTelemetry({ event: 'education.account_banner_guided_tour' });
    window.open('https://poda.bio/studio-migration-guide', '_blank', 'noopener');
  };

  const handleChecklistClick = (target: string) => {
    trackTelemetry({ event: 'education.account_banner_checklist', metadata: { target } });
  };

  return (
    <section className={styles.banner} role="status" aria-live="polite">
      <div className={styles.badge}>New</div>
      <div className={styles.content}>
        <h2>Welcome to the PodaBio Studio account hub</h2>
        <p>
          We’ve moved account management into Studio. Follow the quick checklist to migrate confidently, or take the guided
          tour to learn what’s new.
        </p>
        <div className={styles.actions}>
          <button type="button" className={styles.primaryButton} onClick={handleGuidedTour}>
            Take the guided tour
          </button>
          <button type="button" className={styles.secondaryButton} onClick={handleDismiss}>
            Dismiss
          </button>
        </div>
      </div>
      <ul className={styles.checklist}>
        {checklist.map((item) => (
          <li key={item.id}>
            <button
              type="button"
              onClick={() => {
                handleChecklistClick(item.id);
                const anchor = document.querySelector(`[data-account-section='${item.id}']`);
                if (anchor && 'scrollIntoView' in anchor) {
                  anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
              }}
            >
              <span className={styles.checkIcon} aria-hidden="true">
                ✓
              </span>
              <div>
                <span className={styles.checkTitle}>{item.title}</span>
                <span className={styles.checkDescription}>{item.description}</span>
              </div>
            </button>
          </li>
        ))}
      </ul>
    </section>
  );
}

