/**
 * Modal Preview Component
 * Shows a live preview of the element being edited in the modal header
 */

import { useMemo, useEffect } from 'react';
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

  // Generate CSS variables for preview
  const cssVars = useMemo(() => {
    return previewRenderer.generateCSSVariables(theme, uiState);
  }, [theme, uiState]);

  if (!sectionId) return null;

  // Render preview based on section
  switch (sectionId) {
    case 'page-title':
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <h1 
            className={styles.pageTitlePreview}
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
            Sample Page Title
          </h1>
        </div>
      );

    case 'page-description':
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <p 
            className={styles.pageDescriptionPreview}
            style={{
              color: cssVars['--page-description-color'] || '#4b5563',
              fontFamily: cssVars['--page-description-font'] || "'Inter', sans-serif",
              fontSize: cssVars['--page-description-size'] || '16px',
              fontWeight: cssVars['--page-bio-weight'] || '400',
              fontStyle: cssVars['--page-bio-style'] || 'normal',
              lineHeight: cssVars['--page-bio-spacing'] || '1.5'
            }}
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
      const widgetBorderWidth = cssVars['--widget-border-width'] || '1px';
      const widgetBorderRadius = cssVars['--widget-border-radius'] || '12px';
      const widgetGlowBoxShadow = cssVars['--widget-glow-box-shadow'] || 'none';
      
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div 
            className={styles.widgetPreview}
            style={{
              background: widgetBackground,
              borderColor: widgetBorderColor,
              borderWidth: widgetBorderWidth,
              borderStyle: 'solid',
              borderRadius: widgetBorderRadius,
              boxShadow: widgetGlowBoxShadow,
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

    case 'social-icons':
      return (
        <div className={styles.previewContainer}>
          <div className={styles.previewLabel}>Preview</div>
          <div className={styles.socialIconsPreview}>
            <div 
              className={styles.socialIcon}
              style={{
                width: cssVars['--icon-size'] || '32px',
                height: cssVars['--icon-size'] || '32px',
                color: cssVars['--icon-color'] || '#64748b'
              }}
            >
              <i className="fab fa-twitter"></i>
            </div>
            <div 
              className={styles.socialIcon}
              style={{
                width: cssVars['--icon-size'] || '32px',
                height: cssVars['--icon-size'] || '32px',
                color: cssVars['--icon-color'] || '#64748b'
              }}
            >
              <i className="fab fa-instagram"></i>
            </div>
            <div 
              className={styles.socialIcon}
              style={{
                width: cssVars['--icon-size'] || '32px',
                height: cssVars['--icon-size'] || '32px',
                color: cssVars['--icon-color'] || '#64748b'
              }}
            >
              <i className="fab fa-facebook"></i>
            </div>
          </div>
        </div>
      );

    default:
      return null;
  }
}

