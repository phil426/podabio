import { useState, useEffect, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuX } from 'react-icons/lu';

import { usePageSnapshot, updateSocialIcon, deleteSocialIcon, toggleSocialIconVisibility } from '../../api/page';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { queryKeys } from '../../api/utils';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './social-icon-inspector.module.css';

// Platform definitions matching SettingsPanel
const ALL_PLATFORMS: Record<string, string> = {
  // High Priority - Podcast Platforms
  apple_podcasts: 'Apple Podcasts',
  spotify: 'Spotify',
  youtube_music: 'YouTube Music',
  iheart_radio: 'iHeart Radio',
  amazon_music: 'Amazon Music',
  pocket_casts: 'Pocket Casts',
  castro: 'Castro',
  overcast: 'Overcast',
  // Medium-High Priority - Video/Social
  youtube: 'YouTube',
  instagram: 'Instagram',
  twitter: 'Twitter / X',
  tiktok: 'TikTok',
  substack: 'Substack',
  // Medium Priority - Social/Professional
  facebook: 'Facebook',
  linkedin: 'LinkedIn',
  reddit: 'Reddit',
  discord: 'Discord',
  // Lower Priority - Specialized
  twitch: 'Twitch',
  github: 'GitHub',
  dribbble: 'Dribbble',
  medium: 'Medium',
  snapchat: 'Snapchat',
  pinterest: 'Pinterest'
};

interface SocialIconInspectorProps {
  activeColor: TabColorTheme;
}

export function SocialIconInspector({ activeColor }: SocialIconInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);

  const selectedIcon = useMemo(() => {
    if (!selectedSocialIconId || !snapshot?.social_icons) return undefined;
    return snapshot.social_icons.find((icon) => String(icon.id) === selectedSocialIconId);
  }, [selectedSocialIconId, snapshot?.social_icons]);

  const [platformName, setPlatformName] = useState('');
  const [url, setUrl] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!selectedIcon) {
      setPlatformName('');
      setUrl('');
      setIsActive(true);
      setSaveStatus('idle');
      setStatusMessage(null);
      return;
    }

    setPlatformName(selectedIcon.platform_name);
    setUrl(selectedIcon.url || '');
    setIsActive(selectedIcon.is_active !== 0);
    setSaveStatus('idle');
    setStatusMessage(null);
  }, [selectedIcon]);

  const handleSave = async () => {
    if (!selectedIcon || !platformName || !url) {
      setSaveStatus('error');
      setStatusMessage('Platform name and URL are required.');
      return;
    }

    try {
      await updateSocialIcon({
        directory_id: String(selectedIcon.id),
        platform_name: platformName,
        url: url
      });
      
      setSaveStatus('success');
      setStatusMessage('Social icon updated successfully.');
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      
      setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 3000);
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to update social icon.');
    }
  };

  const handleDelete = async () => {
    if (!selectedIcon) return;
    
    if (!confirm('Are you sure you want to delete this social icon?')) return;

    try {
      await deleteSocialIcon({
        directory_id: String(selectedIcon.id)
      });
      
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      selectSocialIcon(null);
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to delete social icon.');
    }
  };

  const handleToggleVisibility = async () => {
    if (!selectedIcon) return;

    try {
      await toggleSocialIconVisibility({
        icon_id: String(selectedIcon.id),
        is_active: String(!isActive)
      });
      
      setIsActive(!isActive);
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to toggle visibility.');
    }
  };

  if (!selectedIcon) {
    return (
      <section className={styles.wrapper}>
        <div className={styles.emptyState}>
          <p>Select a social icon to edit</p>
        </div>
      </section>
    );
  }

  return (
    <section 
      className={styles.wrapper}
      aria-label="Social icon inspector"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>Social Icon</h3>
          <p>Edit social icon settings</p>
        </div>
        <button
          type="button"
          className={styles.closeButton}
          onClick={() => selectSocialIcon(null)}
          aria-label="Close inspector"
        >
          <LuX aria-hidden="true" />
        </button>
      </header>

      {statusMessage && (
        <div className={styles[`status_${saveStatus}`]}>
          {statusMessage}
        </div>
      )}

      <div className={styles.fieldset}>
        <div className={styles.control}>
          <label htmlFor="social-icon-platform">
            <span>Platform</span>
            <select
              id="social-icon-platform"
              value={platformName}
              onChange={(e) => setPlatformName(e.target.value)}
              className={styles.select}
            >
              {Object.entries(ALL_PLATFORMS).map(([key, name]) => (
                <option key={key} value={key}>
                  {name}
                </option>
              ))}
            </select>
          </label>
        </div>

        <div className={styles.control}>
          <label htmlFor="social-icon-url">
            <span>URL</span>
            <input
              id="social-icon-url"
              type="url"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              placeholder="https://..."
              className={styles.input}
            />
          </label>
        </div>

        <div className={styles.control}>
          <label className={styles.toggleRow}>
            <span>Visible</span>
            <label className={styles.toggleSwitch}>
              <input
                type="checkbox"
                checked={isActive}
                onChange={handleToggleVisibility}
              />
              <span className={styles.toggleSlider} />
            </label>
          </label>
        </div>
      </div>

      <footer className={styles.footer}>
        <button
          type="button"
          onClick={handleSave}
          className={styles.saveButton}
          disabled={!platformName || !url}
        >
          Save
        </button>
        <button
          type="button"
          onClick={handleDelete}
          className={styles.deleteButton}
        >
          Delete
        </button>
      </footer>
    </section>
  );
}

