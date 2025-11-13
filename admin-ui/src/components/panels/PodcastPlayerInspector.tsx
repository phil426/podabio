import { useEffect, useState, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuRss, LuPodcast } from 'react-icons/lu';

import { usePageSnapshot, usePageSettingsMutation } from '../../api/page';
import { queryKeys } from '../../api/utils';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './podcast-player-inspector.module.css';

interface PodcastPlayerInspectorProps {
  activeColor: TabColorTheme;
}

export function PodcastPlayerInspector({ activeColor }: PodcastPlayerInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;
  const pageSettingsMutation = usePageSettingsMutation();
  const rssFeedTimeoutRef = useRef<number | null>(null);

  const [enabled, setEnabled] = useState(Boolean(page?.podcast_player_enabled));
  const [rssFeedUrl, setRssFeedUrl] = useState(page?.rss_feed_url ?? '');
  const [status, setStatus] = useState<string | null>(null);
  const [statusTone, setStatusTone] = useState<'success' | 'error'>('success');

  useEffect(() => {
    setEnabled(Boolean(page?.podcast_player_enabled));
    setRssFeedUrl(page?.rss_feed_url ?? '');
  }, [page?.podcast_player_enabled, page?.rss_feed_url]);

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  // Debounce RSS feed URL updates
  useEffect(() => {
    if (rssFeedTimeoutRef.current) {
      window.clearTimeout(rssFeedTimeoutRef.current);
    }

    // Don't save on initial load
    if (rssFeedUrl === (page?.rss_feed_url ?? '')) {
      return;
    }

    rssFeedTimeoutRef.current = window.setTimeout(async () => {
      try {
        await pageSettingsMutation.mutateAsync({
          rss_feed_url: rssFeedUrl || undefined
        });
        setStatusTone('success');
        setStatus('RSS feed URL updated.');
      } catch (error) {
        setStatusTone('error');
        setStatus(error instanceof Error ? error.message : 'Failed to update RSS feed URL.');
      }
    }, 1000);

    return () => {
      if (rssFeedTimeoutRef.current) {
        window.clearTimeout(rssFeedTimeoutRef.current);
      }
    };
  }, [rssFeedUrl, page?.rss_feed_url, pageSettingsMutation]);

  const handleToggleEnabled = async () => {
    if (pageSettingsMutation.isPending) return;
    
    const newValue = !enabled ? '1' : '0';
    
    try {
      await pageSettingsMutation.mutateAsync({
        podcast_player_enabled: newValue
      });
      setEnabled(!enabled);
      setStatusTone('success');
      setStatus(`Podcast player ${!enabled ? 'enabled' : 'disabled'}.`);
    } catch (error) {
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Failed to update podcast player setting.');
    }
  };

  const handleRssFeedChange = (value: string) => {
    setRssFeedUrl(value);
  };

  return (
    <div 
      className={styles.inspector}
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div className={styles.headerIcon}>
          <LuPodcast aria-hidden="true" />
        </div>
        <div className={styles.headerContent}>
          <h2 className={styles.title}>Podcast Player</h2>
          <p className={styles.description}>Configure your podcast player settings</p>
        </div>
      </header>

      {status && (
        <div className={styles[`status_${statusTone}`]}>
          {status}
        </div>
      )}

      <div className={styles.section}>
        <div className={styles.field}>
          <label className={styles.label}>
            <span className={styles.labelText}>Enable Podcast Player</span>
            <button
              type="button"
              className={styles.toggleSwitch}
              onClick={handleToggleEnabled}
              disabled={pageSettingsMutation.isPending}
              aria-label={enabled ? 'Disable podcast player' : 'Enable podcast player'}
            >
              <input
                type="checkbox"
                checked={enabled}
                onChange={() => {}} // Controlled by button onClick
                disabled={pageSettingsMutation.isPending}
                readOnly
                tabIndex={-1}
              />
              <span className={styles.toggleSlider} />
              <span className={styles.toggleLabel}>
                {enabled ? 'Enabled' : 'Disabled'}
              </span>
            </button>
          </label>
          <p className={styles.helpText}>
            Show or hide the podcast player on your page
          </p>
        </div>
      </div>

      <div className={styles.section}>
        <div className={styles.field}>
          <label className={styles.label} htmlFor="rss-feed-url">
            <span className={styles.labelText}>RSS Feed URL</span>
            <div className={styles.inputWrapper}>
              <LuRss className={styles.inputIcon} aria-hidden="true" />
              <input
                id="rss-feed-url"
                type="url"
                className={styles.input}
                value={rssFeedUrl}
                onChange={(e) => handleRssFeedChange(e.target.value)}
                placeholder="https://example.com/feed.xml"
                disabled={pageSettingsMutation.isPending}
              />
            </div>
          </label>
          <p className={styles.helpText}>
            Enter your podcast RSS feed URL to populate the player with episodes
          </p>
        </div>
      </div>

      <div className={styles.section}>
        <h3 className={styles.sectionTitle}>Styling</h3>
        <p className={styles.comingSoon}>Styling options coming soon</p>
      </div>
    </div>
  );
}

