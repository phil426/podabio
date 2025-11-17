import { FormEvent, useMemo, useState } from 'react';
import clsx from 'clsx';

import styles from './account-drawer.module.css';

interface CreatePageDrawerProps {
  open: boolean;
  onClose: () => void;
  onSubmit: (username: string) => Promise<void> | void;
  isProcessing?: boolean;
  error?: string | null;
  successMessage?: string | null;
  suggestedUsername?: string | null;
}

function sanitize(value: string): string {
  return value.toLowerCase().replace(/[^a-z0-9-_]/g, '').slice(0, 30);
}

export function CreatePageDrawer({
  open,
  onClose,
  onSubmit,
  isProcessing,
  error,
  successMessage,
  suggestedUsername
}: CreatePageDrawerProps): JSX.Element {
  const [username, setUsername] = useState('');
  const preview = useMemo(() => sanitize(username), [username]);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const normalized = sanitize(username);
    if (!normalized || isProcessing) return;
    await onSubmit(normalized);
    setUsername('');
  };

  return (
    <div className={clsx(styles.backdrop, open && styles.backdropVisible)} aria-hidden={!open}>
      <aside className={clsx(styles.drawer, open && styles.drawerOpen)} aria-label="Create your PodaBio page">
        <header className={styles.header}>
          <div>
            <h2>Create your first page</h2>
            <p>Claim a username to generate your PodaBio page and unlock the Studio editor.</p>
          </div>
          <button type="button" className={styles.closeButton} onClick={onClose}>
            Close
          </button>
        </header>

        <form id="create-page-drawer-form" className={styles.form} onSubmit={handleSubmit}>
          <label>
            Username
            <input
              type="text"
              value={username}
              onChange={(event) => setUsername(event.target.value)}
              placeholder={suggestedUsername ?? 'your-show-name'}
              pattern="[a-zA-Z0-9_-]{3,30}"
              minLength={3}
              maxLength={30}
              required
              autoFocus
            />
            <span className={styles.helperText}>
              Use letters, numbers, underscores, or dashes. Your page will live at{' '}
              <strong>{`${window.__APP_URL__ ?? ''}/${preview || suggestedUsername || 'your-page'}`}</strong>
            </span>
          </label>

          {error && <span className={styles.error}>{error}</span>}
          {successMessage && <span className={styles.success}>{successMessage}</span>}
        </form>

        <div className={styles.footer}>
          <button type="submit" form="create-page-drawer-form" className={styles.primaryButton} disabled={isProcessing || preview.length < 3}>
            {isProcessing ? 'Creating…' : 'Create page'}
          </button>
          <button type="button" className={styles.secondaryButton} onClick={onClose}>
            Cancel
          </button>
        </div>
      </aside>
    </div>
  );
}

