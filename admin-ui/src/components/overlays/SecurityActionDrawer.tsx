import clsx from 'clsx';

import styles from './account-drawer.module.css';

export type SecurityAction = 'unlink_google' | 'remove_password';

const ACTION_COPY: Record<
  SecurityAction,
  {
    title: string;
    body: string;
    warning: string;
    cta: string;
  }
> = {
  unlink_google: {
    title: 'Unlink Google sign-in',
    body: 'If you continue, you\'ll remove Google as a login method for your PodaBio account. You will still be able to sign in with your email and password.',
    warning: 'Make sure your password is up to date before unlinking Google.',
    cta: 'Unlink Google'
  },
  remove_password: {
    title: 'Remove account password',
    body: 'Removing your password means Google will be the only way to access your PodaBio account. You must stay signed in with Google to avoid getting locked out.',
    warning: 'Only remove your password if you are comfortable using Google as the sole login method.',
    cta: 'Remove password'
  }
};

interface SecurityActionDrawerProps {
  open: boolean;
  action: SecurityAction;
  onClose: () => void;
  onConfirm: () => void;
  isProcessing?: boolean;
  error?: string | null;
  successMessage?: string | null;
}

export function SecurityActionDrawer({
  open,
  action,
  onClose,
  onConfirm,
  isProcessing,
  error,
  successMessage
}: SecurityActionDrawerProps): JSX.Element {
  const copy = ACTION_COPY[action];

  return (
    <div className={clsx(styles.backdrop, open && styles.backdropVisible)} aria-hidden={!open}>
      <aside className={clsx(styles.drawer, open && styles.drawerOpen)} aria-label={copy.title}>
        <header className={styles.header}>
          <div>
            <h2>{copy.title}</h2>
            <p>{copy.body}</p>
          </div>
          <button type="button" className={styles.closeButton} onClick={onClose}>
            Close
          </button>
        </header>

        <div className={styles.body}>
          <div className={styles.callout} data-tone="warning">
            <span>{copy.warning}</span>
          </div>
          {error && <span className={styles.error}>{error}</span>}
          {successMessage && <span className={styles.success}>{successMessage}</span>}
        </div>

        <div className={styles.footer}>
          <button
            type="button"
            className={styles.primaryButton}
            onClick={onConfirm}
            disabled={isProcessing}
          >
            {isProcessing ? 'Working…' : copy.cta}
          </button>
          <button type="button" className={styles.secondaryButton} onClick={onClose}>
            Cancel
          </button>
        </div>
      </aside>
    </div>
  );
}

