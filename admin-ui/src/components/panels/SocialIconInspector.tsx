import { useState, useEffect, useMemo, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { CheckCircle, CircleNotch, List } from '@phosphor-icons/react';

import { usePageSnapshot, updateSocialIcon, deleteSocialIcon, toggleSocialIconVisibility, addSocialIcon } from '../../api/page';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { queryKeys } from '../../api/utils';
import { type TabColorTheme } from '../layout/tab-colors';
import { useDebouncedCallback } from './themes/hooks/useDebouncedCallback';
import { SocialIconsManagerModal } from '../modals/SocialIconsManagerModal';

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
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);

  const isInitialMount = useRef(true);

  useEffect(() => {
    if (isAdding && addingPlatform) {
      setPlatformName(addingPlatform);
      setUrl('');
      setIsActive(true);
      setSaveStatus('idle');
      setStatusMessage(null);
      isInitialMount.current = true;
      return;
    }

    if (!selectedIcon) {
      setPlatformName('');
      setUrl('');
      setIsActive(true);
      setSaveStatus('idle');
      setStatusMessage(null);
      isInitialMount.current = true;
      return;
    }

    // Only update state if we are not currently saving (to avoid overwriting typing)
    // But autosave happens in background, so we should be careful.
    // If IDs match, we assume we are editing the same thing.
    // We update local state only if the selectedIcon CHANGED (i.e. user selected a different one)
    // OR if it's the first load.
    // We shouldn't overwrite local state with prop updates while user is typing.
    // But we need to detect id change.
    // The dependency array `[selectedIcon, isAdding, addingPlatform]` handles ID changes.
    // But `selectedIcon` changes on every mutation because snapshot updates.
    // We need to compare IDs.
  }, [isAdding, addingPlatform]);

  // Handle selectedIcon updates safely
  const prevSelectedIconId = useRef<string | undefined>(undefined);
  useEffect(() => {
    const newId = selectedIcon ? String(selectedIcon.id) : undefined;
    if (newId !== prevSelectedIconId.current) {
      // ID changed, update form
      if (selectedIcon) {
        setPlatformName(selectedIcon.platform_name);
        setUrl(selectedIcon.url || '');
        setIsActive(selectedIcon.is_active !== 0);
      }
      setSaveStatus('idle');
      setStatusMessage(null);
      isInitialMount.current = true;
      prevSelectedIconId.current = newId;
    }
  }, [selectedIcon]);


  const handleAdd = async () => {
    if (!platformName || !url) {
      setSaveStatus('error');
      setStatusMessage('Platform name and URL are required.');
      return;
    }

    try {
      new URL(url);
    } catch {
      setSaveStatus('error');
      setStatusMessage('Please enter a valid URL (e.g. https://...)');
      return;
    }

    try {
      setSaveStatus('saving');
      await addSocialIcon({
        platform_name: platformName,
        url: url
      });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setSaveStatus('saved');
      setStatusMessage('Social icon added successfully.');

      setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
        selectSocialIcon(null);
      }, 1500);
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save social icon.');
    }
  };

  const performUpdate = async () => {
    if (!selectedIcon) return;

    // Allow saving empty URL? Probably not.
    if (!url) return;

    try {
      setSaveStatus('saving');
      await updateSocialIcon({
        directory_id: String(selectedIcon.id),
        platform_name: platformName,
        url: url
      });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setSaveStatus('saved');
      setStatusMessage('Saved');
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage('Error saving');
    }
  };

  const debouncedUpdate = useDebouncedCallback(performUpdate, 1000);

  // Autosave trigger for updates
  useEffect(() => {
    if (isAdding || !selectedIcon) return;
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    const hasChanges =
      platformName !== selectedIcon.platform_name ||
      url !== (selectedIcon.url || '');

    if (hasChanges) {
      if (saveStatus !== 'saving') {
        // Optional: setStatusMessage('Saving...');
      }
      debouncedUpdate();
    }
  }, [platformName, url, isAdding, selectedIcon, debouncedUpdate, saveStatus]);

  // Clear success status after delay
  useEffect(() => {
    if (saveStatus === 'saved' && !isAdding) {
      const timer = setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [saveStatus, isAdding]);


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

  // State for manager modal
  const [isManagerOpen, setIsManagerOpen] = useState(false);

  if (!selectedIcon && !isAdding) {
    return (
      <section className={styles.wrapper}>
        <div className={styles.emptyState}>
          <p>Select a social icon to edit</p>
          <div style={{ marginTop: '16px', display: 'flex', justifyContent: 'center' }}>
            <button
              type="button"
              onClick={() => setIsManagerOpen(true)}
              className={styles.manageButton}
            >
              <List size={16} weight="bold" />
              Manage All Icons
            </button>
          </div>
        </div>
        <SocialIconsManagerModal
          isOpen={isManagerOpen}
          onClose={() => setIsManagerOpen(false)}
        />
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
      </header>

      {statusMessage && saveStatus === 'error' && (
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
        {isAdding ? (
          <button
            type="button"
            onClick={handleAdd}
            className={styles.saveButton}
            disabled={!platformName || !url || saveStatus === 'saving'}
          >
            {saveStatus === 'saving' ? 'Adding...' : 'Add'}
          </button>
        ) : (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '0.9rem', fontWeight: 500 }}>
              {saveStatus === 'saving' && (
                <>
                  <CircleNotch size={16} weight="bold" className="icon-spin" />
                  <span>Saving...</span>
                </>
              )}
              {saveStatus === 'saved' && (
                <>
                  <CheckCircle size={16} weight="fill" color="#22c55e" />
                  <span style={{ color: '#22c55e' }}>Saved</span>
                </>
              )}
              {saveStatus === 'error' && (
                <span style={{ color: '#ef4444' }}>Error</span>
              )}
            </div>

            <button
              type="button"
              onClick={handleDelete}
              className={styles.deleteButton}
            >
              Delete
            </button>
          </div>
        )}
      </footer>
    </section>
  );
}

