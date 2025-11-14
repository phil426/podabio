import { useEffect, useState, useRef, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuRss, LuPodcast, LuLoader, LuCheck, LuX, LuCopy, LuCircleCheck, LuHeart, LuNewspaper } from 'react-icons/lu';
import { FaPodcast, FaSpotify, FaYoutube, FaAmazon, FaInstagram, FaTwitter, FaTiktok, FaFacebook, FaLinkedin, FaReddit, FaDiscord, FaTwitch, FaGithub, FaDribbble, FaMedium, FaSnapchat, FaPinterest } from 'react-icons/fa';

import { usePageSnapshot, usePageSettingsMutation, generatePodlinks } from '../../api/page';
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
  const [generatingPodlinks, setGeneratingPodlinks] = useState(false);
  const [podlinksResults, setPodlinksResults] = useState<{
    podcast_name: string;
    platforms: Record<string, {
      found: boolean;
      url?: string | null;
      error?: string;
      skipped?: boolean;
    }>;
  } | null>(() => {
    // Load persisted results from localStorage
    try {
      const saved = localStorage.getItem('podlinksResults');
      if (saved) {
        return JSON.parse(saved);
      }
    } catch (e) {
      // Ignore parse errors
    }
    return null;
  });
  const [copiedUrl, setCopiedUrl] = useState<string | null>(null);

  // Platform icon mapping (same as SettingsPanel)
  const getPlatformIcon = useMemo(() => {
    // Custom SVG icons for podcast platforms
    const PocketCastsIcon = () => (
      <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" />
        <path fillRule="evenodd" clipRule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z"/>
      </svg>
    );

    const CastroIcon = () => (
      <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z"/>
      </svg>
    );

    const OvercastIcon = () => (
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style={{ display: 'inline-block', verticalAlign: 'middle' }} aria-hidden="true">
        <path fill="currentColor" fillRule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clipRule="evenodd"/>
      </svg>
    );

    const iconMap: Record<string, JSX.Element> = {
      // Podcast Platforms
      apple_podcasts: <FaPodcast aria-hidden="true" />,
      spotify: <FaSpotify aria-hidden="true" />,
      youtube_music: <FaYoutube aria-hidden="true" />,
      iheart_radio: <LuHeart aria-hidden="true" />,
      amazon_music: <FaAmazon aria-hidden="true" />,
      pocket_casts: <PocketCastsIcon />,
      castro: <CastroIcon />,
      overcast: <OvercastIcon />,
      // Video/Social
      youtube: <FaYoutube aria-hidden="true" />,
      instagram: <FaInstagram aria-hidden="true" />,
      twitter: <FaTwitter aria-hidden="true" />,
      tiktok: <FaTiktok aria-hidden="true" />,
      substack: <LuNewspaper aria-hidden="true" />,
      // Social/Professional
      facebook: <FaFacebook aria-hidden="true" />,
      linkedin: <FaLinkedin aria-hidden="true" />,
      reddit: <FaReddit aria-hidden="true" />,
      discord: <FaDiscord aria-hidden="true" />,
      // Specialized
      twitch: <FaTwitch aria-hidden="true" />,
      github: <FaGithub aria-hidden="true" />,
      dribbble: <FaDribbble aria-hidden="true" />,
      medium: <FaMedium aria-hidden="true" />,
      snapchat: <FaSnapchat aria-hidden="true" />,
      pinterest: <FaPinterest aria-hidden="true" />
    };
    return (platformName: string) => iconMap[platformName.toLowerCase()] || <LuPodcast aria-hidden="true" />;
  }, []);

  // Convert existing social icons to podlinks results format
  const existingPodlinksResults = useMemo(() => {
    if (!snapshot?.social_icons || snapshot.social_icons.length === 0) {
      return null;
    }

    // Filter for podcast platforms that podlinks would generate
    const podcastPlatforms = [
      'apple_podcasts', 'spotify', 'youtube_music', 'iheart_radio', 'amazon_music',
      'pocket_casts', 'castro', 'overcast'
    ];

    const platforms: Record<string, {
      found: boolean;
      url?: string | null;
      error?: string;
      skipped?: boolean;
    }> = {};

    snapshot.social_icons.forEach(icon => {
      const platformName = icon.platform_name.toLowerCase();
      if (podcastPlatforms.includes(platformName) && icon.url) {
        platforms[platformName] = {
          found: true,
          url: icon.url,
          skipped: false
        };
      }
    });

    if (Object.keys(platforms).length === 0) {
      return null;
    }

    return {
      podcast_name: page?.podcast_name || 'Your Podcast',
      platforms
    };
  }, [snapshot?.social_icons, page?.podcast_name]);

  useEffect(() => {
    const wasEnabled = enabled;
    const isNowEnabled = Boolean(page?.podcast_player_enabled);
    setEnabled(isNowEnabled);
    setRssFeedUrl(page?.rss_feed_url ?? '');

    // If player was just disabled, clear podlinks results
    if (wasEnabled && !isNowEnabled) {
      setPodlinksResults(null);
      try {
        localStorage.removeItem('podlinksResults');
      } catch (e) {
        // Ignore storage errors
      }
    }
  }, [page?.podcast_player_enabled, page?.rss_feed_url, enabled]);

  // If player is enabled and we don't have results, try to load from existing social icons
  useEffect(() => {
    if (enabled && !podlinksResults && existingPodlinksResults) {
      setPodlinksResults(existingPodlinksResults);
    }
  }, [enabled, podlinksResults, existingPodlinksResults]);

  // Persist podlinks results to localStorage
  useEffect(() => {
    if (podlinksResults && enabled) {
      try {
        localStorage.setItem('podlinksResults', JSON.stringify(podlinksResults));
      } catch (e) {
        // Ignore storage errors
      }
    }
  }, [podlinksResults, enabled]);

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

  const handleCopyUrl = async (url: string) => {
    try {
      await navigator.clipboard.writeText(url);
      setCopiedUrl(url);
      setTimeout(() => setCopiedUrl(null), 2000);
    } catch (err) {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = url;
      textArea.style.position = 'fixed';
      textArea.style.opacity = '0';
      document.body.appendChild(textArea);
      textArea.select();
      try {
        document.execCommand('copy');
        setCopiedUrl(url);
        setTimeout(() => setCopiedUrl(null), 2000);
      } catch (e) {
        // Ignore errors
      }
      document.body.removeChild(textArea);
    }
  };

  const handleGeneratePodlinks = async () => {
    if (!rssFeedUrl) {
      setStatusTone('error');
      setStatus('Please set an RSS feed URL first.');
      return;
    }

    setGeneratingPodlinks(true);
    setPodlinksResults(null);
    setStatus(null);

    try {
      const response = await generatePodlinks();
      
      if (response.success && response.data) {
        setPodlinksResults(response.data);
        setStatusTone('success');
        setStatus('Podlinks generated successfully.');
        // Refresh page snapshot to get updated social icons
        await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      } else {
        setStatusTone('error');
        setStatus(response.error || 'Failed to generate podlinks.');
      }
    } catch (error) {
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Failed to generate podlinks.');
    } finally {
      setGeneratingPodlinks(false);
    }
  };

  return (
    <section 
      className={styles.wrapper}
      aria-label="Podcast player inspector"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>Podcast Player</h3>
          <p>Configure your podcast player settings</p>
        </div>
      </header>

      {status && (
        <div className={styles[`status_${statusTone}`]}>
          {status}
        </div>
      )}

      <div className={styles.fieldset}>
        <label className={styles.control}>
          <span>Enable Podcast Player</span>
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

      <div className={styles.fieldset}>
        <label className={styles.control} htmlFor="rss-feed-url">
          <span>RSS Feed URL</span>
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

      <div className={styles.fieldset}>
        <button
          type="button"
          className={styles.podlinksButton}
          disabled={pageSettingsMutation.isPending || generatingPodlinks}
          onClick={handleGeneratePodlinks}
        >
          {generatingPodlinks ? (
            <>
              <LuLoader className={styles.buttonSpinner} aria-hidden="true" />
              Generating...
            </>
          ) : (
            'Podlinks'
          )}
        </button>

        {podlinksResults && (
          <div className={styles.podlinksResults}>
            <p className={styles.podlinksResultsTitle}>
              Results for: <strong>{podlinksResults.podcast_name}</strong>
            </p>
            <div className={styles.podlinksPlatforms}>
              {Object.entries(podlinksResults.platforms).map(([platformName, platformData]) => (
                <div key={platformName} className={styles.podlinksPlatform}>
                  <div className={styles.podlinksPlatformHeader}>
                    <div className={styles.podlinksPlatformNameContainer}>
                      <span className={styles.podlinksPlatformIcon}>
                        {getPlatformIcon(platformName)}
                      </span>
                      <span className={styles.podlinksPlatformName}>
                        {platformName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                      </span>
                    </div>
                    {platformData.found ? (
                      <span className={styles.podlinksStatusFound}>
                        <LuCheck aria-hidden="true" />
                        Found
                      </span>
                    ) : (
                      <span className={styles.podlinksStatusNotFound}>
                        <LuX aria-hidden="true" />
                        Not found
                      </span>
                    )}
                  </div>
                  {platformData.found && platformData.url && (
                    <div className={styles.podlinksUrlContainer}>
                      <a
                        href={platformData.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={styles.podlinksUrl}
                      >
                        {platformData.url}
                      </a>
                      <button
                        type="button"
                        className={styles.podlinksCopyButton}
                        onClick={() => handleCopyUrl(platformData.url!)}
                        aria-label="Copy URL"
                        title="Copy URL"
                      >
                        {copiedUrl === platformData.url ? (
                          <LuCircleCheck className={styles.podlinksCopyIcon} aria-hidden="true" />
                        ) : (
                          <LuCopy className={styles.podlinksCopyIcon} aria-hidden="true" />
                        )}
                      </button>
                    </div>
                  )}
                  {!platformData.found && platformData.error && (
                    <p className={styles.podlinksError}>{platformData.error}</p>
                  )}
                  {platformData.skipped && (
                    <p className={styles.podlinksSkipped}>Skipped (already exists)</p>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
}

