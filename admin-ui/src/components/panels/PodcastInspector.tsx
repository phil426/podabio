import { useEffect, useState, useRef, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Rss, CircleNotch, Check, X, MagnifyingGlass, Copy, CheckCircle, PlayCircle, Link } from '@phosphor-icons/react';

import { usePageSnapshot, usePageSettingsMutation, generatePodlinks, searchPodcasts } from '../../api/page';
import { queryKeys, normalizeImageUrl } from '../../api/utils';
import type { PageSnapshotResponse } from '../../api/types';
import { type TabColorTheme } from '../layout/tab-colors';
import { getPlatformIcon } from './social-icons';

import styles from './podcast-inspector.module.css';

interface PodcastInspectorProps {
  activeColor: TabColorTheme;
}

export function PodcastInspector({ activeColor }: PodcastInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;
  const pageSettingsMutation = usePageSettingsMutation();
  const rssFeedTimeoutRef = useRef<number | null>(null);

  const [enabled, setEnabled] = useState(Boolean(page?.podcast_player_enabled));
  const [rssFeedUrl, setRssFeedUrl] = useState(page?.rss_feed_url ?? '');

  // Status states
  const [rssStatus, setRssStatus] = useState<{ msg: string, tone: 'success' | 'error' } | null>(null);
  const [playerStatus, setPlayerStatus] = useState<{ msg: string, tone: 'success' | 'error' } | null>(null);
  const [searchStatus, setSearchStatus] = useState<{ msg: string, tone: 'success' | 'error' } | null>(null);
  const [podlinksStatus, setPodlinksStatus] = useState<{ msg: string, tone: 'success' | 'error' } | null>(null);

  const [generatingPodlinks, setGeneratingPodlinks] = useState(false);
  const [isLoadingFeed, setIsLoadingFeed] = useState(false);
  const [copiedUrl, setCopiedUrl] = useState<string | null>(null);

  // Podlinks State
  const [podlinksResults, setPodlinksResults] = useState<{
    podcast_name: string;
    platforms: Record<string, {
      found: boolean;
      url?: string | null;
      error?: string;
      skipped?: boolean;
    }>;
  } | null>(null);

  // Search State
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<Array<{
    id: string;
    name: string;
    artist: string;
    url: string;
    feed_url: string | null;
    artwork_url: string | null;
  }>>([]);
  const [isSearching, setIsSearching] = useState(false);

  // Predefined podcast platforms to check against (matching backend config/constants.php)
  const PREDEFINED_PODCAST_PLATFORMS = [
    'apple_podcasts',
    'spotify',
    'youtube_music',
    'iheart_radio',
    'amazon_music',
    'pocket_casts',
    'castro',
    'overcast'
  ];

  // Sync internal state with backend data
  useEffect(() => {
    setEnabled(Boolean(page?.podcast_player_enabled));
    if (page?.rss_feed_url && page.rss_feed_url !== rssFeedUrl) {
      setRssFeedUrl(page.rss_feed_url);
    }

    // Initialize podlinks from existing social icons if not recently generated
    if (!podlinksResults && snapshot?.social_icons && snapshot.social_icons.length > 0) {
      const existingPlatforms: Record<string, { found: boolean; url?: string | null; error?: string; dashed?: boolean }> = {};
      let hasAnyPodcastLinks = false;

      // Check which expected platforms exist in social_icons
      PREDEFINED_PODCAST_PLATFORMS.forEach(platform => {
        const foundIcon = snapshot.social_icons.find((icon: any) => icon.platform_name === platform);

        if (foundIcon) {
          existingPlatforms[platform] = {
            found: true,
            url: foundIcon.url
          };
          hasAnyPodcastLinks = true;
        } else {
          // For persistent view, we only show what we have, or maybe show missing states too?
          // User asked "smartlinks that have already been found and stored to have those cards be persistent".
          // We can show "Not detected" for continuity with the generator view.
          existingPlatforms[platform] = {
            found: false
          };
        }
      });

      if (hasAnyPodcastLinks) {
        setPodlinksResults({
          podcast_name: page?.podcast_name || 'Your Podcast',
          platforms: existingPlatforms
        });
      }
    }
  }, [page?.podcast_player_enabled, page?.rss_feed_url, snapshot?.social_icons]);

  // Status helper
  const showStatus = (setFn: any, msg: string, tone: 'success' | 'error') => {
    setFn({ msg, tone });
    setTimeout(() => setFn(null), 3000);
  };


  // --- Handlers ---

  const handleToggleEnabled = async () => {
    if (pageSettingsMutation.isPending) return;
    const newValue = !enabled ? '1' : '0';
    try {
      await pageSettingsMutation.mutateAsync({ podcast_player_enabled: newValue });
      setEnabled(!enabled);
      showStatus(setPlayerStatus, `Podcast player ${!enabled ? 'enabled' : 'disabled'}.`, 'success');
    } catch (error) {
      showStatus(setPlayerStatus, 'Failed to update settings.', 'error');
    }
  };

  const handleManualSaveRss = async () => {
    if (!rssFeedUrl) return;
    setIsLoadingFeed(true);
    setRssStatus(null);
    try {
      await pageSettingsMutation.mutateAsync({ rss_feed_url: rssFeedUrl, force_rss_processing: '1' });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
      showStatus(setRssStatus, 'RSS feed updated successfully.', 'success');
    } catch (error) {
      showStatus(setRssStatus, 'Failed to update RSS feed.', 'error');
    } finally {
      setIsLoadingFeed(false);
    }
  };

  const handleGeneratePodlinks = async () => {
    if (!rssFeedUrl) return;
    setGeneratingPodlinks(true);
    setPodlinksResults(null);
    try {
      const response = await generatePodlinks(rssFeedUrl);
      if (response.success && response.data) {
        setPodlinksResults(response.data);
        showStatus(setPodlinksStatus, 'Podlinks generated.', 'success');
        await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      } else {
        showStatus(setPodlinksStatus, 'Failed to generate links.', 'error');
      }
    } catch (e) {
      showStatus(setPodlinksStatus, 'Error generating links.', 'error');
    } finally {
      setGeneratingPodlinks(false);
    }
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) return;
    setIsSearching(true);
    setSearchResults([]);
    try {
      const response = await searchPodcasts(searchQuery.trim());
      if (response.success && response.data?.results) {
        setSearchResults(response.data.results);
      } else {
        showStatus(setSearchStatus, 'No podcasts found.', 'error');
      }
    } catch (e) {
      showStatus(setSearchStatus, 'Search failed.', 'error');
    } finally {
      setIsSearching(false);
    }
  };

  const selectPodcastFromSearch = async (podcast: any) => {
    if (!podcast.feed_url) return;
    setRssFeedUrl(podcast.feed_url);
    setSearchResults([]);
    setSearchQuery('');
    // Auto save
    setIsLoadingFeed(true);
    try {
      await pageSettingsMutation.mutateAsync({ rss_feed_url: podcast.feed_url, force_rss_processing: '1' });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      showStatus(setRssStatus, `Selected ${podcast.name}`, 'success');
    } catch (e) {
      showStatus(setSearchStatus, 'Failed to save selected podcast.', 'error');
    } finally {
      setIsLoadingFeed(false);
    }
  };

  const handleCopyUrl = async (url: string) => {
    try {
      await navigator.clipboard.writeText(url);
      setCopiedUrl(url);
      setTimeout(() => setCopiedUrl(null), 2000);
    } catch (err) { console.error('Failed to copy'); }
  };

  const renderStatusDiv = (status: { msg: string, tone: 'success' | 'error' } | null) => status && (
    <div style={{ marginBottom: '1rem' }} className={status.tone === 'success' ? styles.status_success : styles.status_error}>
      {status.msg}
    </div>
  );

  const feedParsed = Boolean(page?.podcast_name);

  return (
    <div className={styles.gridContainer}>

      {/* Search Card */}
      <div className={styles.inspectorCard}>
        <div className={styles.wrapper}>
          <div className={styles.header}>
            <div>
              <h3>Search Podcast</h3>
              <p>Find your podcast by name</p>
            </div>
            <MagnifyingGlass size={20} color="rgba(255,255,255,0.5)" />
          </div>
          {renderStatusDiv(searchStatus)}
          <div className={styles.fieldset}>
            <div className={styles.rssFeedContainer}>
              <div className={styles.inputWrapper}>
                <MagnifyingGlass className={styles.inputIcon} size={16} />
                <input
                  className={styles.input}
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search by title..."
                  onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                />
              </div>
              <button
                className={styles.actionButton}
                onClick={handleSearch}
                disabled={isSearching || !searchQuery}
              >
                {isSearching ? <CircleNotch className="spin" /> : 'Search'}
              </button>
            </div>
          </div>
          {searchResults.length > 0 && (
            <div className={styles.searchResults}>
              {searchResults.map(p => (
                <div key={p.id} className={styles.searchResultItem} onClick={() => selectPodcastFromSearch(p)}>
                  <img src={p.artwork_url || ''} className={styles.searchResultArtwork} alt="" />
                  <div className={styles.searchResultContent}>
                    <div className={styles.searchResultName}>{p.name}</div>
                    <div className={styles.searchResultArtist}>{p.artist}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* RSS Feed Card */}
      <div className={styles.inspectorCard}>
        <div className={styles.wrapper}>
          <div className={styles.header}>
            <div>
              <h3>RSS Feed</h3>
              <p>Connect your podcast feed</p>
            </div>
            <Rss size={20} color="rgba(255,255,255,0.5)" />
          </div>
          {renderStatusDiv(rssStatus)}
          <div className={styles.fieldset}>
            <label className={styles.control}>
              <span>RSS Feed URL</span>
              <div className={styles.rssFeedContainer}>
                <div className={styles.inputWrapper}>
                  <Rss className={styles.inputIcon} size={16} />
                  <input
                    className={styles.input}
                    value={rssFeedUrl}
                    onChange={(e) => setRssFeedUrl(e.target.value)}
                    placeholder="https://..."
                  />
                </div>
                <button
                  className={styles.actionButton}
                  onClick={handleManualSaveRss}
                  disabled={isLoadingFeed || !rssFeedUrl}
                >
                  {isLoadingFeed ? <CircleNotch className="spin" /> : 'Save'}
                </button>
              </div>
            </label>
          </div>
          {feedParsed && (
            <div className={styles.fieldset}>
              <div className={styles.podcastInfoCompact}>
                <div className={styles.podcastInfoImage}>
                  {page?.cover_image_url ? (
                    <img src={normalizeImageUrl(page.cover_image_url)} alt="Cover" />
                  ) : <div style={{ width: '100%', height: '100%', background: '#333' }} />}
                </div>
                <div className={styles.podcastInfoContent}>
                  <div className={styles.podcastInfoName}>{page?.podcast_name}</div>
                  <div className={styles.podcastInfoDescription}>{page?.podcast_description}</div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Web Player Card */}
      <div className={styles.inspectorCard}>
        <div className={styles.wrapper}>
          <div className={styles.header}>
            <div>
              <h3>Web Player</h3>
              <p>Manage visibility</p>
            </div>
            <PlayCircle size={20} color="rgba(255,255,255,0.5)" />
          </div>
          {renderStatusDiv(playerStatus)}
          <div className={styles.fieldset}>
            <div className={styles.toggleRow} onClick={handleToggleEnabled}>
              <div className={styles.control}>
                <span>Enable Player</span>
                <p className={styles.helperText}>Shows the persistent player at the top of your page.</p>
              </div>
              <button type="button" className={styles.toggleSwitch}>
                <input type="checkbox" checked={enabled} readOnly />
                <span className={styles.toggleSlider} />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Podlinks Card */}
      <div className={styles.inspectorCard}>
        <div className={styles.wrapper}>
          <div className={styles.header}>
            <div>
              <h3>Smart Links</h3>
              <p>Auto-generate platform links</p>
            </div>
            <Link size={20} color="rgba(255,255,255,0.5)" />
          </div>
          {renderStatusDiv(podlinksStatus)}
          <div className={styles.fieldset}>
            <p style={{ marginTop: 0, marginBottom: '1rem', color: 'rgba(255,255,255,0.7)', fontSize: '0.9rem', lineHeight: 1.5 }}>
              Automatically find your podcast on Spotify, Apple Podcasts, and more.
            </p>
            <button
              className={styles.actionButton}
              style={{ width: '100%', justifyContent: 'center', padding: '0.75rem' }}
              onClick={handleGeneratePodlinks}
              disabled={generatingPodlinks || !rssFeedUrl}
            >
              {generatingPodlinks ? (
                <>
                  <CircleNotch className="spin" size={16} /> Generating...
                </>
              ) : 'Generate Smart Links'}
            </button>
          </div>
          {podlinksResults && (
            <div className={styles.podlinksResults}>
              <div className={styles.podlinksGrid}>
                {Object.entries(podlinksResults.platforms).map(([platformName, platformData]) => (
                  <div key={platformName} className={styles.podlinksCard}>
                    <div className={styles.podlinksCardHeader}>
                      <div className={styles.podlinksIconWrapper}>
                        {getPlatformIcon(platformName)}
                      </div>
                      <div>
                        <div className={styles.podlinksPlatformName}>
                          {platformName.replace(/_/g, ' ')}
                        </div>
                        <div className={styles.podlinksStatus}>
                          {platformData.found ? 'Available' : 'Not detected'}
                        </div>
                      </div>
                    </div>

                    <div className={styles.podlinksActions}>
                      {platformData.found ? (
                        <>
                          <div className={`${styles.podlinksBadge} ${styles.badgeFound}`}>
                            <CheckCircle size={14} weight="fill" />
                            <span>Found</span>
                          </div>
                          {platformData.url && (
                            <button
                              className={`${styles.copyButton} ${copiedUrl === platformData.url ? styles.copyButtonSuccess : ''}`}
                              onClick={() => handleCopyUrl(platformData.url!)}
                              title="Copy Link"
                            >
                              {copiedUrl === platformData.url ? <Check size={14} weight="bold" /> : <Copy size={14} />}
                            </button>
                          )}
                        </>
                      ) : (
                        <div className={`${styles.podlinksBadge} ${styles.badgeMissing}`}>
                          <X size={14} weight="bold" />
                          <span>Missing</span>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
