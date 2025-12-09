/**
 * UIState Page Preview Component
 * Renders the full page preview using React inside a Shadow DOM to ensure style isolation.
 */

import { useMemo, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import type { SocialIconRecord, WidgetRecord } from '../../../../api/types';
import { normalizeImageUrl } from '../../../../api/utils';
import styles from './ui-state-preview.module.css';

interface UIStatePagePreviewProps {
  cssVars: Record<string, string>;
  scale?: number;
  title?: string;
  description?: string;
  socialIcons?: SocialIconRecord[];
  widgets?: WidgetRecord[];
}

export function UIStatePagePreview({
  cssVars,
  scale = 1,
  title = 'My Podcast',
  description = 'Welcome to my podcast page.',
  socialIcons = [],
  widgets = []
}: UIStatePagePreviewProps): JSX.Element {
  const hostRef = useRef<HTMLDivElement>(null);
  const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);

  // Initialize Shadow Root
  useEffect(() => {
    if (hostRef.current) {
      const root = hostRef.current.shadowRoot || hostRef.current.attachShadow({ mode: 'open' });
      setShadowRoot(root as unknown as ShadowRoot);
    }
  }, []);

  // Construct CSS string from variables
  const varLines = useMemo(() => {
    return Object.entries(cssVars)
      .map(([key, value]) => `  ${key}: ${value};`)
      .join('\n');
  }, [cssVars]);

  // Load Font Awesome into Shadow DOM
  useEffect(() => {
    if (!shadowRoot) return;

    // Check if link already exists
    if (shadowRoot.querySelector('link[href*="font-awesome"]')) return;

    const link = shadowRoot.ownerDocument.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    link.integrity = 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==';
    link.crossOrigin = 'anonymous';
    shadowRoot.appendChild(link);
  }, [shadowRoot]);

  // Styles from ShadowPreviewCard
  const shadowStyles = useMemo(() => `
      :host {
        all: initial;
        display: block;
        font-family: var(--font-family-body, 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif);
        color: var(--page-description-color, #475569);
        background: var(--page-background, #ffffff);
${varLines}
      }
      .mobile-page-container {
        width: 100%;
        min-height: 100vh;
        background: var(--page-background, #ffffff);
        padding: var(--page-spacing, 16px);
        box-sizing: border-box;
      }
      .page-container {
        max-width: 100%;
        margin: 0 auto;
        box-sizing: border-box;
      }
      .profile-header {
        text-align: center;
        margin-bottom: 24px;
      }
      .profile-image-container {
        margin: 0 auto 16px;
        display: inline-block;
      }
      .profile-image {
        width: var(--profile-image-size, 120px);
        height: var(--profile-image-size, 120px);
        border-radius: var(--profile-image-radius, 16%);
        border-width: var(--profile-image-border-width, 0px);
        border-color: var(--profile-image-border-color, transparent);
        border-style: ${cssVars['--profile-image-border-width'] && parseFloat(String(cssVars['--profile-image-border-width']).replace('px', '')) > 0 ? 'solid' : 'none'};
        box-shadow: var(--profile-image-box-shadow, none);
        object-fit: cover;
        display: block;
      }
      .page-title {
        font-family: var(--page-title-font, var(--font-family-heading, inherit));
        font-size: var(--page-title-size, 24px);
        font-weight: var(--page-title-weight, 700);
        color: var(--page-title-color, #0f172a);
        margin: 0 0 12px 0;
        text-align: center;
      }
      .page-description {
        font-family: var(--page-description-font, var(--font-family-body, inherit));
        font-size: var(--page-bio-size, 14px);
        font-weight: var(--page-bio-weight, 400);
        color: var(--page-description-color, #475569);
        margin: 0 0 24px 0;
        text-align: center;
        line-height: 1.5;
        white-space: pre-wrap;
      }
      .social-icons {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: var(--social-icon-spacing, var(--icon-spacing, 16px));
        margin: 0 0 24px 0;
      }
      .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--social-icon-size, var(--icon-size, 32px));
        height: var(--social-icon-size, var(--icon-size, 32px));
        color: var(--social-icon-color, var(--icon-color, #2563eb));
        text-decoration: none;
        font-size: calc(var(--social-icon-size, var(--icon-size, 32px)) * 0.625);
      }
      .widgets-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .widget-item {
        background: var(--widget-background, #ffffff);
        border: var(--widget-border-width, 1px) solid var(--widget-border-color, #e2e8f0);
        border-radius: var(--widget-border-radius, 12px);
        padding: 16px;
        box-shadow: var(--widget-shadow-depth, none);
      }
      .widget-title {
        font-family: var(--widget-heading-font, var(--font-family-heading, inherit));
        font-size: var(--widget-heading-size, 16px);
        font-weight: var(--widget-heading-weight, 600);
        color: var(--widget-heading-color, #0f172a);
        margin: 0 0 8px 0;
      }
      .widget-description {
        font-family: var(--widget-body-font, var(--font-family-body, inherit));
        font-size: var(--widget-body-size, 14px);
        font-weight: var(--widget-body-weight, 400);
        color: var(--widget-body-color, #475569);
        margin: 0;
        line-height: 1.5;
      }
      .widget-description p { margin: 0; }
      .widget-description a { color: inherit; text-decoration: underline; }
    `, [varLines, cssVars]);

  // Helper for icons (matches ShadowPreviewCard logic/font awesome)
  const getPlatformIcon = (platformName: string): JSX.Element => {
    const platform = platformName.toLowerCase();
    const map: Record<string, string> = {
      'twitter': 'fab fa-twitter',
      'instagram': 'fab fa-instagram',
      'facebook': 'fab fa-facebook',
      'linkedin': 'fab fa-linkedin',
      'youtube': 'fab fa-youtube',
      'tiktok': 'fab fa-tiktok',
      'website': 'fas fa-link',
      'email': 'fas fa-envelope',
      'spotify': 'fab fa-spotify',
      'apple_podcasts': 'fas fa-podcast',
      'twitch': 'fab fa-twitch',
      'github': 'fab fa-github',
      'discord': 'fab fa-discord'
    };
    const iconClass = map[platform] || 'fas fa-link';
    return <i className={iconClass} />;
  };

  // Helper to extract content from widget config
  const getWidgetBody = (widget: WidgetRecord) => {
    try {
      if (!widget.config_data) return null;

      // If content is a direct string
      if (typeof widget.config_data === 'string') {
        return <div dangerouslySetInnerHTML={{ __html: widget.config_data }} />;
      }

      // If config_data is an object
      if (typeof widget.config_data === 'object' && widget.config_data !== null) {
        // Check for common content fields
        const content = widget.config_data['content'] || widget.config_data['html'] || widget.config_data['description'] || widget.config_data['body'];

        if (content && typeof content === 'string') {
          return <div dangerouslySetInnerHTML={{ __html: content }} />;
        }

        // For Link widgets
        if (widget.widget_type === 'link' && widget.config_data['url']) {
          return (
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <i className="fas fa-link" style={{ fontSize: '12px', opacity: 0.7 }} />
              <span>{String(widget.config_data['url']).replace(/^https?:\/\//, '')}</span>
            </div>
          );
        }
      }
      return null;
    } catch (e) {
      return null;
    }
  };

  const profileImageSrc = cssVars['--profile-image-url'];

  return (
    <div className={styles.previewContainer}>
      <div
        style={{
          width: `${430 * scale}px`,
          height: `${932 * scale}px`,
          position: 'relative',
          margin: '0 auto', // Ensure horizontal centering
        }}
      >
        <div
          ref={hostRef}
          style={{
            width: '430px',
            height: '932px',
            transform: `scale(${scale})`,
            transformOrigin: 'top left',
            background: 'transparent',
            overflow: 'hidden'
          }}
        />
      </div>
      {shadowRoot && createPortal(
        <>
          <style>{shadowStyles}</style>
          <div className="mobile-page-container">
            <div className="page-container">
              {/* Profile Image */}
              {profileImageSrc && (
                <div className="profile-header">
                  <div className="profile-image-container">
                    <img
                      src={normalizeImageUrl(profileImageSrc)}
                      alt="Profile"
                      className="profile-image"
                      onError={(e) => {
                        (e.target as HTMLImageElement).style.display = 'none';
                      }}
                    />
                  </div>
                </div>
              )}

              {/* Page Title */}
              {title && (
                <h1 className="page-title">{title}</h1>
              )}

              {/* Page Description */}
              {description && (
                <p className="page-description">{description}</p>
              )}

              {/* Social Icons */}
              {socialIcons.length > 0 && (
                <div className="social-icons">
                  {socialIcons.map((icon) => (
                    <a
                      key={icon.id}
                      href={icon.url}
                      className="social-icon"
                      target="_blank"
                      rel="noopener noreferrer"
                      title={icon.platform_name}
                      onClick={(e) => e.preventDefault()}
                    >
                      {getPlatformIcon(icon.platform_name)}
                    </a>
                  ))}
                </div>
              )}

              {/* Widgets */}
              <div className="widgets-container">
                {widgets.length > 0 ? (
                  widgets.filter(w => w.is_active !== 0).map(widget => (
                    <div key={widget.id} className="widget-item">
                      <div className="widget-title">{widget.title}</div>
                      <div className="widget-description">
                        {getWidgetBody(widget) || 'Content unavailable'}
                      </div>
                    </div>
                  ))
                ) : (
                  // Fallback if no widgets
                  <div className="widget-item">
                    <div className="widget-title">Welcome</div>
                    <div className="widget-description">
                      Add widgets to your page to see them appear here.
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </>,
        shadowRoot as unknown as Element
      )}
    </div>

  );
}
