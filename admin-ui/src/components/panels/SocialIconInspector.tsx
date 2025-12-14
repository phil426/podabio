import { useState, useEffect, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { X } from '@phosphor-icons/react';

import { usePageSnapshot, updateSocialIcon, deleteSocialIcon, toggleSocialIconVisibility, addSocialIcon } from '../../api/page';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { queryKeys } from '../../api/utils';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './social-icon-inspector.module.css';

import { ALL_PLATFORMS } from './social-platforms';
import { getPlatformIcon } from './social-icons';

interface SocialIconInspectorProps {
  activeColor: TabColorTheme;
  selectedId?: string | null;
  onSelect?: (id: string | null) => void;
}

export function SocialIconInspector({ activeColor, selectedId, onSelect }: SocialIconInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const globalSelectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const globalSelectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);

  // Use props if provided (controlled mode), otherwise fall back to global state
  const selectedSocialIconId = selectedId !== undefined ? selectedId : globalSelectedSocialIconId;
  const selectSocialIcon = onSelect || globalSelectSocialIcon;

  const selectedIcon = useMemo(() => {
    if (!selectedSocialIconId || selectedSocialIconId.startsWith('new:')) return undefined;
    return snapshot?.social_icons?.find((icon) => String(icon.id) === selectedSocialIconId);
  }, [selectedSocialIconId, snapshot?.social_icons]);

  const isAdding = selectedSocialIconId?.startsWith('new:') ?? false;
  const addingPlatform = isAdding ? selectedSocialIconId?.split(':')[1] : '';

  const [platformName, setPlatformName] = useState('');
  const [url, setUrl] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);

  useEffect(() => {
    if (isAdding && addingPlatform) {
      setPlatformName(addingPlatform);
      setUrl('');
      setIsActive(true);
      setSaveStatus('idle');
      setStatusMessage(null);
      return;
    }

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
  }, [selectedIcon, isAdding, addingPlatform]);

  const handleSave = async () => {
    if (!platformName || !url) {
      setSaveStatus('error');
      setStatusMessage('Platform name and URL are required.');
      return;
    }

    // Basic URL validation
    try {
      new URL(url);
    } catch {
      setSaveStatus('error');
      setStatusMessage('Please enter a valid URL (e.g. https://...)');
      return;
    }

    try {
      if (isAdding) {
        await addSocialIcon({
          platform_name: platformName,
          url: url
        });
        setStatusMessage('Social icon added successfully.');
      } else if (selectedIcon) {
        await updateSocialIcon({
          directory_id: String(selectedIcon.id),
          platform_name: platformName,
          url: url
        });
        setStatusMessage('Social icon updated successfully.');
      }

      setSaveStatus('success');
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });

      setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
        // If we just added, we should probably close or select the new one, but closing is safer for now
        if (isAdding) {
          selectSocialIcon(null);
        }
      }, 1500);
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save social icon.');
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

  if (!selectedIcon && !isAdding) {
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
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          {getPlatformIcon(platformName)}
          <div>
            <h3>{isAdding ? 'Add Social Icon' : 'Social Icon'}</h3>
            <p>{isAdding ? 'Configure new social link' : 'Edit social icon settings'}</p>
          </div>
        </div>
        <button
          type="button"
          className={styles.closeButton}
          onClick={() => selectSocialIcon(null)}
          aria-label="Close inspector"
        >
          <X size={16} weight="regular" aria-hidden="true" />
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
          {isAdding ? 'Add' : 'Save'}
        </button>
        {!isAdding && (
          <button
            type="button"
            onClick={handleDelete}
            className={styles.deleteButton}
          >
            Delete
          </button>
        )}
      </footer>
    </section>
  );
}

