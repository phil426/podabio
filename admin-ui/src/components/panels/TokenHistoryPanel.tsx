import { useMemo, useState } from 'react';
import clsx from 'clsx';

import { useTokenHistoryQuery, useRollbackTokensMutation } from '../../api/tokens';
import type { TokenHistoryEntry } from '../../api/types';
import styles from './token-history-panel.module.css';

interface DiffItem {
  path: string;
  value: unknown;
}

export function TokenHistoryPanel(): JSX.Element {
  const { data: history = [], isLoading, isError, error, refetch } = useTokenHistoryQuery();
  const { mutateAsync: rollback, isPending } = useRollbackTokensMutation();
  const [previewId, setPreviewId] = useState<number | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const selectedEntry = useMemo(() => history.find((entry) => entry.id === previewId) ?? null, [history, previewId]);
  const diffEntries = useMemo(() => (selectedEntry ? flattenOverrides(selectedEntry.overrides) : []), [selectedEntry]);

  const handleRestore = async (entry: TokenHistoryEntry) => {
    try {
      await rollback(entry.id);
      setStatus('Token overrides restored.');
      setPreviewId(null);
      refetch();
    } catch (err) {
      setStatus(err instanceof Error ? err.message : 'Unable to restore snapshot.');
    }
  };

  if (isLoading) {
    return (
      <section className={styles.panel} aria-label="Token history">
        <p>Loading token history…</p>
      </section>
    );
  }

  if (isError) {
    return (
      <section className={styles.panel} aria-label="Token history">
        <p className={styles.error}>{error instanceof Error ? error.message : 'Unable to load token history.'}</p>
      </section>
    );
  }

  return (
    <section className={styles.panel} aria-label="Token history">
      <header className={styles.header}>
        <div>
          <h3>Token history</h3>
          <p>Every save snapshot lives here. Preview differences or roll back instantly.</p>
        </div>
        {status && <span className={styles.status}>{status}</span>}
      </header>

      {history.length === 0 ? (
        <p className={styles.placeholder}>Save tokens to create your first snapshot.</p>
      ) : (
        <ul className={styles.list}>
          {history.map((entry) => (
            <li key={entry.id} className={clsx(styles.row, previewId === entry.id && styles.rowActive)}>
              <div className={styles.rowMeta}>
                <span className={styles.timestamp}>{formatDate(entry.created_at)}</span>
                {entry.created_by_email && <span className={styles.author}>{entry.created_by_email}</span>}
              </div>
              <div className={styles.rowActions}>
                <button type="button" onClick={() => setPreviewId(previewId === entry.id ? null : entry.id)}>
                  {previewId === entry.id ? 'Hide diff' : 'Preview diff'}
                </button>
                <button type="button" onClick={() => handleRestore(entry)} disabled={isPending}>
                  Restore
                </button>
              </div>
              {previewId === entry.id && (
                <div className={styles.diffPanel}>
                  {diffEntries.length === 0 ? (
                    <p className={styles.placeholder}>No differences recorded for this snapshot.</p>
                  ) : (
                    <ul>
                      {diffEntries.map((item) => (
                        <li key={item.path}>
                          <code>{item.path}</code>
                          <span>{renderValue(item.value)}</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              )}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

function flattenOverrides(overrides: Record<string, unknown>, prefix = ''): DiffItem[] {
  const entries: DiffItem[] = [];

  Object.entries(overrides ?? {}).forEach(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;

    if (value && typeof value === 'object' && !Array.isArray(value)) {
      entries.push(...flattenOverrides(value as Record<string, unknown>, path));
    } else {
      entries.push({ path, value });
    }
  });

  return entries;
}

function renderValue(value: unknown): string {
  if (typeof value === 'string') return value;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  if (value === null || value === undefined) return '—';
  return JSON.stringify(value);
}

function formatDate(value: string): string {
  try {
    const date = new Date(value);
    return new Intl.DateTimeFormat(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date);
  } catch {
    return value;
  }
}

