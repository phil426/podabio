import { useState } from 'react';
import { Check, X, CircleNotch, Plus, MusicNote, Heart, Newspaper, Eye, EyeSlash, DotsSixVertical } from '@phosphor-icons/react';
import { FaSpotify, FaYoutube, FaInstagram, FaTwitter, FaTiktok, FaFacebook, FaLinkedin, FaReddit, FaDiscord, FaTwitch, FaGithub, FaDribbble, FaMedium, FaSnapchat, FaPinterest, FaAmazon, FaPodcast } from 'react-icons/fa';
import {
  DndContext,
  PointerSensor,
  useSensor,
  useSensors,
  closestCenter,
  DragEndEvent
} from '@dnd-kit/core';
import {
  SortableContext,
  verticalListSortingStrategy,
  arrayMove,
  useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { usePageSnapshot, addSocialIcon, toggleSocialIconVisibility, reorderSocialIcons } from '../../api/page';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import { useSocialIconSelection } from '../../state/socialIconSelection';

import styles from './settings-panel.module.css';

// Platform definitions matching editor.php
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

// Icon components using the uploaded SVG files
// These are loaded from /icons/ directory in the public folder
// Using inline SVG with currentColor for proper color inheritance
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

// Platform icon mapping with specific brand icons
const getPlatformIcon = (platformName: string): JSX.Element => {
  const iconMap: Record<string, JSX.Element> = {
    // Podcast Platforms
    apple_podcasts: <FaPodcast aria-hidden="true" />,
    spotify: <FaSpotify aria-hidden="true" />,
    youtube_music: <FaYoutube aria-hidden="true" />,
    iheart_radio: <Heart aria-hidden="true" size={20} weight="regular" />,
    amazon_music: <FaAmazon aria-hidden="true" />,
    pocket_casts: <PocketCastsIcon />,
    castro: <CastroIcon />,
    overcast: <OvercastIcon />,
    // Video/Social
    youtube: <FaYoutube aria-hidden="true" />,
    instagram: <FaInstagram aria-hidden="true" />,
    twitter: <FaTwitter aria-hidden="true" />,
    tiktok: <FaTiktok aria-hidden="true" />,
    substack: <Newspaper aria-hidden="true" size={20} weight="regular" />,
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
  return iconMap[platformName] || <MusicNote aria-hidden="true" size={20} weight="regular" />;
};

interface SortableIconCardProps {
  icon: { id: number | string; platform_name: string; url: string | null; is_active: number };
  isSelected: boolean;
  onSelect: (iconId: number | string) => void;
  onToggleVisibility: (e: React.MouseEvent, iconId: number | string, currentActive: boolean) => void;
  toggleVisibilityPending: boolean;
  getPlatformIcon: (platformName: string) => JSX.Element;
}

function SortableIconCard({
  icon,
  isSelected,
  onSelect,
  onToggleVisibility,
  toggleVisibilityPending,
  getPlatformIcon
}: SortableIconCardProps): JSX.Element {
  const isActive = icon.is_active !== 0;
  
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: String(icon.id)
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 2 : undefined
  };

  return (
    <div 
      ref={setNodeRef}
      style={style}
      className={`${styles.iconCard} ${isSelected ? styles.iconCardSelected : ''}`}
      data-dnd-kit-dragging={isDragging ? 'true' : undefined}
      onClick={() => onSelect(icon.id)}
      role="button"
      tabIndex={0}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onSelect(icon.id);
        }
      }}
    >
      <span 
        className={styles.gripIcon} 
        {...attributes} 
        {...listeners}
        aria-hidden="true"
      >
        <DotsSixVertical size={16} weight="regular" />
      </span>
      <div className={styles.iconIcon} data-active={isActive ? 'true' : 'false'}>
        {getPlatformIcon(icon.platform_name)}
      </div>
      <div className={styles.iconDetails}>
        <p className={styles.iconName}>
          {ALL_PLATFORMS[icon.platform_name] || icon.platform_name}
        </p>
        <p className={styles.iconUrl}>
          {icon.url || <em>No URL added</em>}
        </p>
      </div>
      <button
        type="button"
        className={styles.visibilityButton}
        onClick={(e) => onToggleVisibility(e, icon.id, isActive)}
        disabled={toggleVisibilityPending}
        aria-label={isActive ? `Hide ${ALL_PLATFORMS[icon.platform_name] || icon.platform_name}` : `Show ${ALL_PLATFORMS[icon.platform_name] || icon.platform_name}`}
        title={isActive ? 'Hide' : 'Show'}
        data-active={isActive ? 'true' : 'false'}
      >
        {isActive ? <Eye aria-hidden="true" size={16} weight="regular" /> : <EyeSlash aria-hidden="true" size={16} weight="regular" />}
      </button>
    </div>
  );
}

export function SettingsPanel(): JSX.Element {
  const { data: snapshot, isLoading } = usePageSnapshot();
  const queryClient = useQueryClient();
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
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

  const handleAdd = () => {
    if (!addingPlatform || !addingUrl || !pageId) return;
    addMutation.mutate({
      platform_name: addingPlatform,
      url: addingUrl
    });
  };

  const handleSelect = (iconId: number | string) => {
    const idString = String(iconId);
    if (selectedSocialIconId === idString) {
      selectSocialIcon(null);
    } else {
      selectSocialIcon(idString);
    }
  };

  const toggleVisibilityMutation = useMutation({
    mutationFn: (payload: { icon_id: number | string; is_active: boolean }) => 
      toggleSocialIconVisibility({
        icon_id: String(payload.icon_id),
        is_active: String(payload.is_active)
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });

  const handleToggleVisibility = (e: React.MouseEvent, iconId: number | string, currentActive: boolean) => {
    e.stopPropagation(); // Prevent card selection when clicking visibility icon
    toggleVisibilityMutation.mutate({
      icon_id: iconId,
      is_active: !currentActive
    });
  };

  const reorderMutation = useMutation({
    mutationFn: (iconOrders: Array<{ icon_id: number; display_order: number }>) => 
      reorderSocialIcons(iconOrders),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    }
  });

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: { distance: 6 }
    })
  );

  const handleDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    
    const oldIndex = socialIcons.findIndex((icon) => String(icon.id) === active.id);
    const newIndex = socialIcons.findIndex((icon) => String(icon.id) === over.id);
    
    if (oldIndex === -1 || newIndex === -1) return;
    
    const reordered = arrayMove(socialIcons, oldIndex, newIndex);
    
    // Create the order array for the API
    const iconOrders = reordered.map((icon, index) => ({
      icon_id: icon.id,
      display_order: index + 1
    }));
    
    reorderMutation.mutate(iconOrders);
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
          <CircleNotch className={styles.spinner} size={20} weight="regular" />
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
              <Check aria-hidden="true" size={16} weight="regular" />
            ) : (
              <X aria-hidden="true" size={16} weight="regular" />
            )}
            <span>{status}</span>
          </div>
        )}

        {/* Social Icons List with Add Form */}
        <div className={styles.iconsList}>
          {/* Add New Social Icon - positioned above the list */}
          {availablePlatforms.length > 0 && (
            <div className={styles.addCard}>
              <div className={styles.addCardHeader}>
                <Plus className={styles.addCardIcon} aria-hidden="true" size={16} weight="regular" />
                <span className={styles.addCardTitle}>Add Social Icon</span>
              </div>
              <div className={styles.addCardForm}>
                <div className={styles.addCardField}>
                  <select
                    value={addingPlatform}
                    onChange={(e) => setAddingPlatform(e.target.value)}
                    className={styles.addCardSelect}
                  >
                    <option value="">Select platform</option>
                    {availablePlatforms.map(([key, name]) => (
                      <option key={key} value={key}>
                        {name}
                      </option>
                    ))}
                  </select>
                </div>
                <div className={styles.addCardField}>
                  <input
                    type="url"
                    value={addingUrl}
                    onChange={(e) => setAddingUrl(e.target.value)}
                    placeholder="https://..."
                    className={styles.addCardInput}
                  />
                </div>
                <button
                  type="button"
                  onClick={handleAdd}
                  className={styles.addCardButton}
                  disabled={!addingPlatform || !addingUrl || addMutation.isPending}
                >
                  {addMutation.isPending ? (
                    <>
                      <CircleNotch className={styles.buttonSpinner} size={16} weight="regular" />
                      <span>Adding…</span>
                    </>
                  ) : (
                    <>
                      <Plus aria-hidden="true" size={16} weight="regular" />
                      <span>Add</span>
                    </>
                  )}
                </button>
              </div>
            </div>
          )}

          {/* Existing Social Icons */}
          {socialIcons.length > 0 && (
            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
              <SortableContext items={socialIcons.map(icon => String(icon.id))} strategy={verticalListSortingStrategy}>
                {socialIcons.map((icon) => (
                  <SortableIconCard
                    key={icon.id}
                    icon={icon}
                    isSelected={selectedSocialIconId === String(icon.id)}
                    onSelect={handleSelect}
                    onToggleVisibility={handleToggleVisibility}
                    toggleVisibilityPending={toggleVisibilityMutation.isPending}
                    getPlatformIcon={getPlatformIcon}
                  />
                ))}
              </SortableContext>
            </DndContext>
          )}

          {availablePlatforms.length === 0 && socialIcons.length > 0 && (
            <p className={styles.allAdded}>All available platforms have been added.</p>
          )}
        </div>
      </section>
    </div>
  );
}

