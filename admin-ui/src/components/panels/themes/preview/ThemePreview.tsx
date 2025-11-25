/**
 * Theme Preview Component
 * Renders a live preview of the page with current theme settings
 * No iframe - pure React component
 */

import { useEffect, useMemo, useState } from 'react';
import { DeviceMobile } from '@phosphor-icons/react';
import { usePageSnapshot } from '../../../../api/page';
import { normalizeImageUrl } from '../../../../api/utils';
import styles from './theme-preview.module.css';

interface DevicePreset {
  id: string;
  name: string;
  actualWidth: number; // Actual device width in CSS pixels
  actualHeight: number; // Actual device height in CSS pixels
}

// 6 Popular phone sizes - actual device dimensions
const DEVICE_PRESETS: DevicePreset[] = [
  { id: 'iphone-16-pro-max', name: 'iPhone 16 Pro Max', actualWidth: 430, actualHeight: 932 },
  { id: 'iphone-15-pro', name: 'iPhone 15 Pro', actualWidth: 393, actualHeight: 852 },
  { id: 'iphone-se', name: 'iPhone SE', actualWidth: 375, actualHeight: 667 },
  { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', actualWidth: 412, actualHeight: 915 },
  { id: 'pixel-8-pro', name: 'Pixel 8 Pro', actualWidth: 412, actualHeight: 915 },
  { id: 'iphone-15', name: 'iPhone 15', actualWidth: 390, actualHeight: 844 },
];

const PREVIEW_SCALE = 0.7; // 70% scale

interface ThemePreviewProps {
  cssVars: Record<string, string>;
  onHotspotClick?: (sectionId: string) => void;
  hotspotsVisible?: boolean;
}

export function ThemePreview({ cssVars, onHotspotClick, hotspotsVisible = true }: ThemePreviewProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;
  const socialIcons = snapshot?.social_icons || [];
  const [selectedDevice, setSelectedDevice] = useState<DevicePreset>(DEVICE_PRESETS[0]);

  // Decode HTML entities in text
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  // Get icon HTML for a platform (matches page.php logic)
  const getPlatformIcon = (platformName: string): JSX.Element => {
    const platform = platformName.toLowerCase();
    
    // Custom SVG icons for podcast platforms
    if (platform === 'pocket_casts') {
      return (
        <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'block', width: '1em', height: '1em' }}>
          <circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" />
          <path fillRule="evenodd" clipRule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z"/>
        </svg>
      );
    } else if (platform === 'castro') {
      return (
        <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'block', width: '1em', height: '1em' }}>
          <path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z"/>
        </svg>
      );
    } else if (platform === 'overcast') {
      return (
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style={{ display: 'block', width: '1em', height: '1em' }}>
          <path fill="currentColor" fillRule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clipRule="evenodd"/>
        </svg>
      );
    }
    
    // Font Awesome icons for other platforms
    const platformIcons: Record<string, string> = {
      // Podcast Platforms
      'apple_podcasts': 'fas fa-podcast',
      'spotify': 'fab fa-spotify',
      'youtube_music': 'fab fa-youtube',
      'iheart_radio': 'fas fa-heart',
      'amazon_music': 'fab fa-amazon',
      // Social Media Platforms
      'facebook': 'fab fa-facebook',
      'twitter': 'fab fa-twitter',
      'instagram': 'fab fa-instagram',
      'linkedin': 'fab fa-linkedin',
      'youtube': 'fab fa-youtube',
      'tiktok': 'fab fa-tiktok',
      'snapchat': 'fab fa-snapchat',
      'pinterest': 'fab fa-pinterest',
      'reddit': 'fab fa-reddit',
      'discord': 'fab fa-discord',
      'twitch': 'fab fa-twitch',
      'github': 'fab fa-github',
      'behance': 'fab fa-behance',
      'dribbble': 'fab fa-dribbble',
      'medium': 'fab fa-medium',
      'substack': 'fas fa-newspaper'
    };
    
    const iconClass = platformIcons[platform] || 'fas fa-link';
    return <i className={iconClass} />;
  };

  // Load Font Awesome for social icons
  useEffect(() => {
    // Check if Font Awesome is already loaded
    const existingLink = document.querySelector('link[href*="font-awesome"]');
    if (existingLink) {
      return; // Already loaded
    }

    // Load Font Awesome
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    link.integrity = 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==';
    link.crossOrigin = 'anonymous';
    link.setAttribute('referrerpolicy', 'no-referrer');
    document.head.appendChild(link);

    return () => {
      // Don't remove Font Awesome as it might be used elsewhere
    };
  }, []);

  // Load Google Fonts for preview
  useEffect(() => {
    // Get fonts from CSS variables
    const pageTitleFont = cssVars['--page-title-font'] || cssVars['--page-description-font'] || 'Inter';
    const pageBioFont = cssVars['--page-description-font'] || cssVars['--page-title-font'] || 'Inter';
    const widgetHeadingFont = cssVars['--widget-heading-font'] || 'Inter';
    const widgetBodyFont = cssVars['--widget-body-font'] || 'Inter';

    // Extract font names (remove quotes and sans-serif fallback)
    const extractFontName = (fontValue: string): string => {
      if (!fontValue) return 'Inter';
      // Remove quotes and sans-serif fallback
      const match = fontValue.match(/'([^']+)'/);
      return match ? match[1] : fontValue.split(',')[0].trim().replace(/['"]/g, '');
    };

    const fonts = [
      extractFontName(pageTitleFont),
      extractFontName(pageBioFont),
      extractFontName(widgetHeadingFont),
      extractFontName(widgetBodyFont)
    ].filter(Boolean);

    // Get unique fonts
    const uniqueFonts = Array.from(new Set(fonts));

    if (uniqueFonts.length > 0) {
      // Build Google Fonts URL
      const fontParams = uniqueFonts.map(font => {
        const fontUrl = font.replace(/\s+/g, '+');
        return `family=${fontUrl}:wght@400;600;700`;
      });

      const fontUrl = `https://fonts.googleapis.com/css2?${fontParams.join('&')}&display=swap`;

      // Remove existing preview font link
      const existingLink = document.querySelector('link[data-preview-fonts]');
      if (existingLink) {
        existingLink.remove();
      }

      // Add new font link
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = fontUrl;
      link.setAttribute('data-preview-fonts', 'true');
      document.head.appendChild(link);
    }

    return () => {
      const link = document.querySelector('link[data-preview-fonts]');
      if (link) {
        link.remove();
      }
    };
  }, [cssVars]);

  // Apply CSS variables to preview container (they cascade to children)
  // React supports CSS variables in inline styles using the variable name as a key
  const previewStyle: React.CSSProperties = {};
  Object.entries(cssVars).forEach(([name, value]) => {
    // CSS variables can be set directly in React inline styles
    (previewStyle as Record<string, string>)[name] = value;
  });

  // Calculate scaled dimensions for the frame
  const scaledDimensions = useMemo(() => ({
    width: selectedDevice.actualWidth * PREVIEW_SCALE,
    height: selectedDevice.actualHeight * PREVIEW_SCALE
  }), [selectedDevice]);

  const phoneStyle = useMemo(() => ({
    ...previewStyle,
    width: `${scaledDimensions.width}px`,
    height: `${scaledDimensions.height}px`
  }), [previewStyle, scaledDimensions]);

  // Style for the content wrapper that scales everything proportionally
  const contentWrapperStyle = useMemo(() => ({
    width: `${selectedDevice.actualWidth}px`,
    height: `${selectedDevice.actualHeight}px`,
    transform: `scale(${PREVIEW_SCALE})`,
    transformOrigin: 'top left'
  }), [selectedDevice]);

  return (
    <div className={styles.previewContainer}>
      <div className={styles.previewHeader}>
        <div className={styles.previewHeaderLeft}>
          <h3>Live Preview</h3>
          <p>See your changes in real-time</p>
        </div>
        <div className={styles.deviceSelector}>
          <DeviceMobile aria-hidden="true" size={16} weight="regular" />
          <select
            className={styles.deviceSelect}
            value={selectedDevice.id}
            onChange={(e) => {
              const device = DEVICE_PRESETS.find((d) => d.id === e.target.value);
              if (device) setSelectedDevice(device);
            }}
          >
            {DEVICE_PRESETS.map((device) => (
              <option key={device.id} value={device.id}>
                {device.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className={styles.previewWrapper}>
        <div className={styles.previewPhone} style={phoneStyle}>
          {/* Content wrapper that scales everything proportionally */}
          <div className={styles.contentWrapper} style={contentWrapperStyle}>
            {/* Background Hotspot - positioned in top left */}
            {hotspotsVisible && (
              <div
                className={`${styles.backgroundHotspot} ${styles.hotspot}`}
                data-hotspot="page-background"
                onClick={(e) => {
                  e.stopPropagation();
                  onHotspotClick?.('page-background');
                }}
                title="Page Background - Edit page background and vertical spacing"
              />
            )}
            
            {/* Non-functional Podcast Player Banner */}
            <div 
              className={`${styles.podcastBanner} ${hotspotsVisible ? styles.hotspot : ''}`}
              onClick={(e) => {
                if (!hotspotsVisible) return;
                e.stopPropagation();
                onHotspotClick?.('podcast-player-bar');
              }}
              title={hotspotsVisible ? "Podcast Player Bar - Edit player bar appearance" : undefined}
            >
              <button 
                className={styles.podcastBannerToggle} 
                type="button" 
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  if (hotspotsVisible) {
                    onHotspotClick?.('podcast-player-bar');
                  }
                }}
                style={{ pointerEvents: 'auto' }}
              >
                <i className="fas fa-podcast" aria-hidden="true"></i>
                <span>Tap to Listen</span>
                <i className="fas fa-chevron-down" aria-hidden="true"></i>
              </button>
            </div>
            <div className={styles.previewContent}>
            {/* Profile Section - Always show if profile_image exists */}
            {page?.profile_image && (
              <div 
                className={styles.profileSection}
                style={{
                  marginTop: cssVars['--profile-image-spacing-top'] || (cssVars['--page-vertical-spacing'] ? `calc(${cssVars['--page-vertical-spacing']} + 20px)` : '44px'),
                  marginBottom: cssVars['--profile-image-spacing-bottom'] || cssVars['--page-vertical-spacing'] || '24px'
                }}
              >
                <div
                  className={`${styles.profileImageContainer} ${hotspotsVisible ? styles.hotspot : ''}`}
                  data-hotspot="profile-image"
                  onClick={() => hotspotsVisible && onHotspotClick?.('profile-image')}
                  title={hotspotsVisible ? "Profile Image - Edit profile image settings" : undefined}
                >
                  <img 
                    src={normalizeImageUrl(page.profile_image)} 
                    alt="Profile" 
                    className={styles.profileImage}
                    style={{
                      width: cssVars['--profile-image-size'] || '120px',
                      height: cssVars['--profile-image-size'] || '120px',
                      borderRadius: cssVars['--profile-image-radius'] || '16%',
                      borderWidth: cssVars['--profile-image-border-width'] || '0px',
                      borderColor: cssVars['--profile-image-border-color'] || 'transparent',
                      borderStyle: (() => {
                        const borderWidth = cssVars['--profile-image-border-width'];
                        if (!borderWidth) return 'none';
                        // Handle both "2px" and "2" formats
                        const widthValue = typeof borderWidth === 'string' 
                          ? parseFloat(borderWidth.replace('px', '').trim()) 
                          : Number(borderWidth);
                        return widthValue > 0 ? 'solid' : 'none';
                      })(),
                      boxShadow: cssVars['--profile-image-box-shadow'] || 'none',
                      objectFit: 'cover',
                      display: 'block',
                      margin: '0 auto'
                    }}
                    onError={(e) => {
                      // Hide image if it fails to load
                      (e.target as HTMLImageElement).style.display = 'none';
                    }}
                  />
                </div>
              </div>
            )}

            {/* Page Title */}
            {page?.podcast_name && (
              <h1 
                className={`${styles.pageTitle} ${hotspotsVisible ? styles.hotspot : ''}`}
                data-hotspot="page-title"
                onClick={() => hotspotsVisible && onHotspotClick?.('page-title')}
                title={hotspotsVisible ? "Page Title - Edit page title settings" : undefined}
              >
                {page.podcast_name}
              </h1>
            )}

            {/* Page Bio */}
            {page?.podcast_description && (
              <p 
                className={`${styles.pageBio} ${hotspotsVisible ? styles.hotspot : ''}`}
                data-hotspot="page-bio"
                onClick={() => hotspotsVisible && onHotspotClick?.('page-description')}
                title={hotspotsVisible ? "Page Description - Edit page description settings" : undefined}
              >
                {decodeHtmlEntities(page.podcast_description)}
              </p>
            )}

            {/* Social Icons - Positioned between bio and widgets (matching page.php structure) */}
            {socialIcons.length > 0 && (
              <div 
                className={`${styles.socialIcons} ${hotspotsVisible ? styles.hotspot : ''}`}
                data-hotspot="social-icons"
                onClick={(e) => {
                  if (!hotspotsVisible) return;
                  // Only trigger if clicking on the container, not the links
                  if (e.target === e.currentTarget || (e.target as HTMLElement).closest(`.${styles.socialIcon}`)) {
                    e.preventDefault();
                    e.stopPropagation();
                    onHotspotClick?.('social-icons');
                  }
                }}
                title={hotspotsVisible ? "Social Icons - Edit social icon appearance" : undefined}
              >
                {socialIcons.map((icon) => (
                  <a
                    key={icon.id}
                    href={icon.url}
                    className={styles.socialIcon}
                    target="_blank"
                    rel="noopener noreferrer"
                    title={icon.platform_name}
                    onClick={(e) => {
                      if (!hotspotsVisible) return;
                      // In preview, prevent navigation and trigger hotspot instead
                      e.preventDefault();
                      e.stopPropagation();
                      onHotspotClick?.('social-icons');
                    }}
                  >
                    {getPlatformIcon(icon.platform_name)}
                  </a>
                ))}
              </div>
            )}

            {/* Sample Widget - wrapped in container to match page.php structure */}
            <div className={styles.widgetsContainer}>
              <div 
                className={styles.widget}
                data-hotspot="widget"
              >
                {/* Widget Styling Hotspot - positioned on the right */}
                {hotspotsVisible && (
                  <div
                    className={`${styles.widgetStylingHotspot} ${styles.hotspot}`}
                    onClick={(e) => {
                      e.stopPropagation();
                      onHotspotClick?.('widget-settings');
                    }}
                    title="Widget Settings - Edit widget background, border, radius, shadow, and glow"
                  />
                )}
                <h3 
                  className={`${styles.widgetHeading} ${hotspotsVisible ? styles.hotspot : ''}`}
                  onClick={(e) => {
                    if (!hotspotsVisible) return;
                    e.stopPropagation();
                    onHotspotClick?.('widget-text');
                  }}
                  title={hotspotsVisible ? "Widgets & Blocks Text Settings - Edit widget heading text" : undefined}
                >
                  Sample Heading
                </h3>
                <p className={styles.widgetBody}>
                  This is sample body text.
                </p>
              </div>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  );
}

