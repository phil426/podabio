import { useState } from 'react';
import { LuCheck, LuX, LuLoader, LuPlus, LuTrash2, LuGripVertical, LuLink2 } from 'react-icons/lu';

import { usePageSnapshot, addSocialIcon, updateSocialIcon, deleteSocialIcon, toggleSocialIconVisibility, reorderSocialIcons } from '../../api/page';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';

import styles from './settings-panel.module.css';

// Platform definitions matching editor.php
const ALL_PLATFORMS: Record<string, string> = {
  // High Priority - Podcast Platforms
  apple_podcasts: 'Apple Podcasts',
  spotify: 'Spotify',
  youtube_music: 'YouTube Music',
  iheart_radio: 'iHeart Radio',
  amazon_music: 'Amazon Music',
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

// Platform icon mapping (using Font Awesome class names as reference, but we'll use SVG icons)
const getPlatformIcon = (platformName: string): JSX.Element => {
  const iconMap: Record<string, JSX.Element> = {
    apple_podcasts: <LuLink2 aria-hidden="true" />,
    spotify: <LuLink2 aria-hidden="true" />,
    youtube_music: <LuLink2 aria-hidden="true" />,
    iheart_radio: <LuLink2 aria-hidden="true" />,
    amazon_music: <LuLink2 aria-hidden="true" />,
    youtube: <LuLink2 aria-hidden="true" />,
    instagram: <LuLink2 aria-hidden="true" />,
    twitter: <LuLink2 aria-hidden="true" />,
    tiktok: <LuLink2 aria-hidden="true" />,
    substack: <LuLink2 aria-hidden="true" />,
    facebook: <LuLink2 aria-hidden="true" />,
    linkedin: <LuLink2 aria-hidden="true" />,
    reddit: <LuLink2 aria-hidden="true" />,
    discord: <LuLink2 aria-hidden="true" />,
    twitch: <LuLink2 aria-hidden="true" />,
    github: <LuLink2 aria-hidden="true" />,
    dribbble: <LuLink2 aria-hidden="true" />,
    medium: <LuLink2 aria-hidden="true" />,
    snapchat: <LuLink2 aria-hidden="true" />,
    pinterest: <LuLink2 aria-hidden="true" />
  };
  return iconMap[platformName] || <LuLink2 aria-hidden="true" />;
};

export function SettingsPanel(): JSX.Element {
  const { data: snapshot, isLoading } = usePageSnapshot();
  const queryClient = useQueryClient();
  const [editingId, setEditingId] = useState<number | string | null>(null);
  const [editingUrl, setEditingUrl] = useState('');
  const [editingPlatform, setEditingPlatform] = useState('');
  const [addingPlatform, setAddingPlatform] = useState('');
  const [addingUrl, setAddingUrl] = useState('');
  const [status, setStatus] = useState<string | null>(null);

  const socialIcons = snapshot?.social_icons || [];
  const pageId = snapshot?.page?.id;

  const addMutation = useMutation({
    mutationFn: (payload: { platform_name: string; url: string }) => addSocialIcon(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setAddingPlatform('');
      setAddingUrl('');
      setStatus('Social icon added successfully.');
      setTimeout(() => setStatus(null), 3000);
    },
    onError: () => {
      setStatus('Failed to add social icon.');
      setTimeout(() => setStatus(null), 3000);
    }
  });

  const updateMutation = useMutation({
    mutationFn: (payload: { directory_id: number | string; platform_name: string; url: string }) => 
      updateSocialIcon(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setEditingId(null);
      setEditingUrl('');
      setEditingPlatform('');
      setStatus('Social icon updated successfully.');
      setTimeout(() => setStatus(null), 3000);
    },
    onError: () => {
      setStatus('Failed to update social icon.');
      setTimeout(() => setStatus(null), 3000);
    }
  });

  const deleteMutation = useMutation({
    mutationFn: (payload: { directory_id: number | string }) => deleteSocialIcon(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatus('Social icon deleted successfully.');
      setTimeout(() => setStatus(null), 3000);
    },
    onError: () => {
      setStatus('Failed to delete social icon.');
      setTimeout(() => setStatus(null), 3000);
    }
  });

  const toggleMutation = useMutation({
    mutationFn: (payload: { icon_id: number | string; is_active: boolean }) => 
      toggleSocialIconVisibility(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });

  const handleAdd = () => {
    if (!addingPlatform || !addingUrl || !pageId) return;
    addMutation.mutate({
      platform_name: addingPlatform,
      url: addingUrl
    });
  };

  const handleUpdate = (iconId: number | string) => {
    if (!editingPlatform || !editingUrl) return;
    updateMutation.mutate({
      directory_id: iconId,
      platform_name: editingPlatform,
      url: editingUrl
    });
  };

  const handleDelete = (iconId: number | string) => {
    if (!confirm('Are you sure you want to delete this social icon?')) return;
    deleteMutation.mutate({ directory_id: iconId });
  };

  const handleToggle = (iconId: number | string, currentActive: boolean) => {
    toggleMutation.mutate({
      icon_id: iconId,
      is_active: !currentActive
    });
  };

  const startEdit = (icon: { id: number | string; platform_name: string; url: string }) => {
    setEditingId(icon.id);
    setEditingPlatform(icon.platform_name);
    setEditingUrl(icon.url);
  };

  const cancelEdit = () => {
    setEditingId(null);
    setEditingUrl('');
    setEditingPlatform('');
  };

  // Get existing platform names
  const existingPlatforms = new Set(socialIcons.map(icon => icon.platform_name));
  const availablePlatforms = Object.entries(ALL_PLATFORMS).filter(
    ([key]) => !existingPlatforms.has(key)
  );

  if (isLoading) {
    return (
      <div className={styles.container}>
        <div className={styles.loadingState}>
          <LuLoader className={styles.spinner} />
          <p>Loading settings…</p>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      <section className={styles.section}>
        <header className={styles.header}>
          <h3 className={styles.title}>Social Icons</h3>
          <p className={styles.description}>
            Add links to your social media profiles and platforms. Each platform is pre-configured - just add your URL to get started.
          </p>
        </header>

        {status && (
          <div className={styles.statusBanner}>
            {status.includes('successfully') ? (
              <LuCheck aria-hidden="true" />
            ) : (
              <LuX aria-hidden="true" />
            )}
            <span>{status}</span>
          </div>
        )}

        {/* Existing Social Icons */}
        {socialIcons.length > 0 && (
          <div className={styles.iconsList}>
            {socialIcons.map((icon) => {
              const isEditing = editingId === icon.id;
              const isActive = icon.is_active !== 0;

              return (
                <div key={icon.id} className={styles.iconCard}>
                  <div className={styles.iconHeader}>
                    <div className={styles.iconInfo}>
                      <div className={styles.iconIcon}>
                        {getPlatformIcon(icon.platform_name)}
                      </div>
                      <div className={styles.iconDetails}>
                        <p className={styles.iconName}>
                          {ALL_PLATFORMS[icon.platform_name] || icon.platform_name}
                        </p>
                        {isEditing ? (
                          <div className={styles.editForm}>
                            <select
                              value={editingPlatform}
                              onChange={(e) => setEditingPlatform(e.target.value)}
                              className={styles.platformSelect}
                            >
                              {Object.entries(ALL_PLATFORMS).map(([key, name]) => (
                                <option key={key} value={key}>
                                  {name}
                                </option>
                              ))}
                            </select>
                            <input
                              type="url"
                              value={editingUrl}
                              onChange={(e) => setEditingUrl(e.target.value)}
                              placeholder="https://..."
                              className={styles.urlInput}
                            />
                            <div className={styles.editActions}>
                              <button
                                type="button"
                                onClick={() => handleUpdate(icon.id)}
                                className={styles.saveButton}
                                disabled={updateMutation.isPending}
                              >
                                {updateMutation.isPending ? (
                                  <LuLoader className={styles.buttonSpinner} />
                                ) : (
                                  'Save'
                                )}
                              </button>
                              <button
                                type="button"
                                onClick={cancelEdit}
                                className={styles.cancelButton}
                              >
                                Cancel
                              </button>
                            </div>
                          </div>
                        ) : (
                          <p className={styles.iconUrl}>
                            {icon.url || <em>No URL added</em>}
                          </p>
                        )}
                      </div>
                    </div>
                    {!isEditing && (
                      <div className={styles.iconActions}>
                        <label className={styles.toggleSwitch}>
                          <input
                            type="checkbox"
                            checked={isActive}
                            onChange={() => handleToggle(icon.id, isActive)}
                            disabled={toggleMutation.isPending}
                          />
                          <span className={styles.toggleSlider} />
                        </label>
                        <button
                          type="button"
                          onClick={() => startEdit(icon)}
                          className={styles.editButton}
                        >
                          Edit
                        </button>
                        <button
                          type="button"
                          onClick={() => handleDelete(icon.id)}
                          className={styles.deleteButton}
                          disabled={deleteMutation.isPending}
                        >
                          {deleteMutation.isPending ? (
                            <LuLoader className={styles.buttonSpinner} />
                          ) : (
                            <LuTrash2 aria-hidden="true" />
                          )}
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* Add New Social Icon */}
        {availablePlatforms.length > 0 && (
          <div className={styles.addSection}>
            <h4 className={styles.addTitle}>Add Social Icon</h4>
            <div className={styles.addForm}>
              <select
                value={addingPlatform}
                onChange={(e) => setAddingPlatform(e.target.value)}
                className={styles.platformSelect}
              >
                <option value="">Select Platform</option>
                {availablePlatforms.map(([key, name]) => (
                  <option key={key} value={key}>
                    {name}
                  </option>
                ))}
              </select>
              <input
                type="url"
                value={addingUrl}
                onChange={(e) => setAddingUrl(e.target.value)}
                placeholder="https://..."
                className={styles.urlInput}
              />
              <button
                type="button"
                onClick={handleAdd}
                className={styles.addButton}
                disabled={!addingPlatform || !addingUrl || addMutation.isPending}
              >
                {addMutation.isPending ? (
                  <>
                    <LuLoader className={styles.buttonSpinner} />
                    <span>Adding…</span>
                  </>
                ) : (
                  <>
                    <LuPlus aria-hidden="true" />
                    <span>Add</span>
                  </>
                )}
              </button>
            </div>
          </div>
        )}

        {availablePlatforms.length === 0 && socialIcons.length > 0 && (
          <p className={styles.allAdded}>All available platforms have been added.</p>
        )}
      </section>
    </div>
  );
}

