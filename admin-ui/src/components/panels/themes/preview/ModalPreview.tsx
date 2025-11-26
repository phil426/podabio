/**
 * Modal Preview Component
 * Shows a live preview of the element being edited in the modal header
 */

import { useMemo, useEffect, type CSSProperties } from 'react';
import { previewRenderer } from '../utils/previewRenderer';
import { usePageSnapshot } from '../../../../api/page';
import { normalizeImageUrl } from '../../../../api/utils';
import type { ThemeRecord } from '../../../api/types';
import styles from './modal-preview.module.css';

interface ModalPreviewProps {
  sectionId: string | null;
  theme: ThemeRecord | null;
  uiState: Record<string, unknown>;
}

export function ModalPreview({ sectionId, theme, uiState }: ModalPreviewProps): JSX.Element | null {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;
  const socialIcons = snapshot?.social_icons || [];

  // Load Font Awesome if needed
  useEffect(() => {
    const existingLink = document.querySelector('link[href*="font-awesome"]');
    if (existingLink) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    link.integrity = 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==';
    link.crossOrigin = 'anonymous';
    link.setAttribute('referrerpolicy', 'no-referrer');
    document.head.appendChild(link);
  }, []);

  // Get icon HTML for a platform (matches ThemePreview logic)
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
      'apple_podcasts': 'fas fa-podcast',
      'spotify': 'fab fa-spotify',
      'youtube_music': 'fab fa-youtube',
      'iheart_radio': 'fas fa-heart',
      'amazon_music': 'fab fa-amazon',
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

  // Generate CSS variables for preview
  const cssVars = useMemo(() => {
    return previewRenderer.generateCSSVariables(theme, uiState, page as Record<string, unknown> | undefined);
  }, [theme, uiState, page]);

  if (!sectionId) return null;

  // Render preview based on section
  switch (sectionId) {
    case 'page-title':
      const effectClass = cssVars['--page-title-effect-class'] || '';
      const pageTitle = page?.podcast_name || page?.username || 'Sample Page Title';
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <h1 
            className={`${styles.pageTitlePreview} ${effectClass}`}
            style={{
              color: cssVars['--page-title-color'] || '#0f172a',
              fontFamily: cssVars['--page-title-font'] || "'Inter', sans-serif",
              fontSize: cssVars['--page-title-size'] || '24px',
              fontWeight: cssVars['--page-title-weight'] || '600',
              fontStyle: cssVars['--page-title-style'] || 'normal',
              lineHeight: cssVars['--page-title-spacing'] || '1.2',
              textShadow: cssVars['--page-title-text-shadow'] || 'none'
            }}
          >
            {pageTitle}
          </h1>
        </div>
      );

    case 'page-description':
      const bioColor = cssVars['--page-description-color'] || '#4b5563';
      const isGradient = typeof bioColor === 'string' && (
        bioColor.includes('gradient') || 
        bioColor.includes('linear-gradient') || 
        bioColor.includes('radial-gradient')
      );
      
      const bioStyle: CSSProperties = {
        fontFamily: cssVars['--page-description-font'] || "'Inter', sans-serif",
        fontSize: cssVars['--page-description-size'] || '16px',
        fontWeight: cssVars['--page-bio-weight'] || '400',
        fontStyle: cssVars['--page-bio-style'] || 'normal',
        lineHeight: cssVars['--page-bio-spacing'] || '1.5'
      };
      
      if (isGradient) {
        bioStyle.backgroundImage = bioColor;
        bioStyle.WebkitBackgroundClip = 'text';
        bioStyle.backgroundClip = 'text';
        bioStyle.WebkitTextFillColor = 'transparent';
        bioStyle.color = 'transparent';
      } else {
        bioStyle.color = bioColor;
      }
      
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <p 
            className={styles.pageDescriptionPreview}
            style={bioStyle}
          >
            This is sample body text that shows how the page description will look with your current settings.
          </p>
        </div>
      );

    case 'profile-image':
      const profileImageSize = cssVars['--profile-image-size'] || '120px';
      const profileImageRadius = cssVars['--profile-image-radius'] || '50%';
      const profileImageBorderWidth = cssVars['--profile-image-border-width'] || '0px';
      const profileImageBorderColor = cssVars['--profile-image-border-color'] || 'transparent';
      const profileImageBoxShadow = cssVars['--profile-image-box-shadow'] || 'none';
      const profileImage = page?.profile_image;
      
      const imageStyle = {
        width: profileImageSize,
        height: profileImageSize,
        borderRadius: profileImageRadius,
        borderWidth: profileImageBorderWidth,
        borderColor: profileImageBorderColor,
        borderStyle: (() => {
          const widthValue = typeof profileImageBorderWidth === 'string' 
            ? parseFloat(profileImageBorderWidth.replace('px', '').trim()) 
            : Number(profileImageBorderWidth);
          return widthValue > 0 ? 'solid' : 'none';
        })(),
        boxShadow: profileImageBoxShadow,
        objectFit: 'cover' as const,
        display: 'block' as const
      };
      
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div className={styles.profileImagePreview}>
            {profileImage ? (
              <img
                src={normalizeImageUrl(profileImage)}
                alt="Profile"
                className={styles.profileImage}
                style={imageStyle}
                onError={(e) => {
                  // Hide image and show placeholder if image fails to load
                  (e.target as HTMLImageElement).style.display = 'none';
                  const placeholder = (e.target as HTMLImageElement).parentElement?.querySelector(`.${styles.profileImagePlaceholder}`) as HTMLElement;
                  if (placeholder) placeholder.style.display = 'block';
                }}
              />
            ) : null}
            <div
              className={styles.profileImagePlaceholder}
              style={{
                ...imageStyle,
                display: profileImage ? 'none' : 'block'
              }}
            />
          </div>
        </div>
      );

    case 'page-background':
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div 
            className={styles.pageBackgroundPreview}
            style={{
              background: cssVars['--page-background'] || '#ffffff',
              minHeight: '60px',
              borderRadius: '8px',
              border: '1px solid rgba(15, 23, 42, 0.1)'
            }}
          />
        </div>
      );

    case 'widget-settings':
    case 'widget-text':
      const widgetBackground = cssVars['--widget-background'] || '#ffffff';
      const widgetBorderColor = cssVars['--widget-border-color'] || '#e2e8f0';
      const widgetBorderWidthRaw = cssVars['--widget-border-width'] || '1px';
      // Parse border width - if it's 0 or "0px", hide the border
      const borderWidthNum = parseFloat(String(widgetBorderWidthRaw).replace('px', ''));
      const widgetBorderWidth = borderWidthNum > 0 ? widgetBorderWidthRaw : '0px';
      const widgetBorderRadius = cssVars['--widget-border-radius'] || '12px';
      
      // Get border effect to determine which shadow/glow to use
      const borderEffect = (uiState['widget-border-effect'] as string) || 'none';
      let widgetBoxShadow = 'none';
      
      if (borderEffect === 'shadow') {
        widgetBoxShadow = cssVars['--widget-shadow-box-shadow'] || 'none';
      } else if (borderEffect === 'glow') {
        widgetBoxShadow = cssVars['--widget-glow-box-shadow'] || 'none';
      }
      
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div 
            className={styles.widgetPreview}
            style={{
              background: widgetBackground,
              borderColor: widgetBorderColor,
              borderWidth: widgetBorderWidth,
              borderStyle: borderWidthNum > 0 ? 'solid' : 'none',
              borderRadius: widgetBorderRadius,
              boxShadow: widgetBoxShadow,
              padding: '16px',
              minHeight: '60px'
            }}
          >
            {sectionId === 'widget-text' && (
              <>
                <h3 
                  style={{
                    color: cssVars['--widget-heading-color'] || '#0f172a',
                    fontFamily: cssVars['--widget-heading-font'] || "'Inter', sans-serif",
                    fontSize: cssVars['--widget-heading-size'] || '20px',
                    fontWeight: cssVars['--widget-heading-weight'] || '600',
                    margin: '0 0 8px 0'
                  }}
                >
                  Sample Heading
                </h3>
                <p 
                  style={{
                    color: cssVars['--widget-body-color'] || '#4b5563',
                    fontFamily: cssVars['--widget-body-font'] || "'Inter', sans-serif",
                    fontSize: cssVars['--widget-body-size'] || '14px',
                    fontWeight: cssVars['--widget-body-weight'] || '400',
                    margin: 0
                  }}
                >
                  This is sample body text
                </p>
              </>
            )}
          </div>
        </div>
      );

    case 'podcast-player-bar':
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div 
            className={styles.podcastPlayerPreview}
            style={{
              background: cssVars['--podcast-player-background'] || 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
              borderColor: cssVars['--podcast-player-border-color'] || 'rgba(255, 255, 255, 0.2)',
              borderWidth: cssVars['--podcast-player-border-width'] || '1px',
              borderStyle: 'solid',
              borderBottomWidth: cssVars['--podcast-player-border-width'] || '1px',
              boxShadow: cssVars['--podcast-player-box-shadow'] || '0 6px 16px rgba(15, 23, 42, 0.16)',
              padding: '12px 16px',
              borderRadius: '8px',
              color: cssVars['--podcast-player-text-color'] || '#ffffff',
              fontSize: '14px',
              fontWeight: '500'
            }}
          >
            <i className="fas fa-podcast" style={{ marginRight: '8px' }}></i>
            Tap to Listen
          </div>
        </div>
      );

    case 'social-icons': {
      const iconSize = cssVars['--icon-size'] || cssVars['--social-icon-size'] || '32px';
      let iconColor = cssVars['--icon-color'] || cssVars['--social-icon-color'] || '#2563eb';
      const iconSpacing = cssVars['--icon-spacing'] || cssVars['--social-icon-spacing'] || '1rem';
      
      // Extract solid color if gradient was set (fallback to default)
      if (typeof iconColor === 'string' && iconColor.includes('gradient')) {
        iconColor = '#2563eb';
      }
      
      const iconStyle: CSSProperties = {
        width: iconSize,
        height: iconSize,
        color: iconColor,
        fontSize: typeof iconSize === 'string' 
          ? `calc(${iconSize} * 0.625)` 
          : `${(parseFloat(String(iconSize)) || 32) * 0.625}px`
      };
      
      // Use actual social icons from snapshot, or show sample icons if none exist
      const iconsToShow = socialIcons.length > 0 ? socialIcons.slice(0, 3) : [
        { id: 1, platform_name: 'twitter' },
        { id: 2, platform_name: 'instagram' },
        { id: 3, platform_name: 'facebook' }
      ];
      
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div 
            className={styles.socialIconsPreview}
            style={{ 
              gap: iconSpacing,
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center'
            }}
          >
            {iconsToShow.map((icon) => (
              <div key={icon.id} className={styles.socialIcon} style={iconStyle}>
                {getPlatformIcon(icon.platform_name)}
              </div>
            ))}
          </div>
        </div>
      );
    }

    default:
      return null;
  }
}

