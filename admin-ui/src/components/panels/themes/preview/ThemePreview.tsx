/**
 * Theme Preview Component
 * Renders a live preview of the page with current theme settings
 * No iframe - pure React component
 */

import { useEffect, useMemo, useState, useRef } from 'react';
import { usePageSnapshot } from '../../../../api/page';
import { normalizeImageUrl } from '../../../../api/utils';
import { ContextMenu, type ContextMenuOption } from './ContextMenu';
import { Pencil, Palette, Eye, EyeSlash, Trash, FolderOpen, Sparkle, Star, Lock, LockOpen } from '@phosphor-icons/react';
import { sectionRegistry } from '../utils/sectionRegistry';
import type { TabColorTheme } from '../../../layout/tab-colors';
import { ThemeLibraryModal } from './ThemeLibraryModal';

import styles from './theme-preview.module.css';

interface DevicePreset {
  id: string;
  name: string;
  actualWidth: number; // Actual device width in CSS pixels
  actualHeight: number; // Actual device height in CSS pixels
}

// 6 Popular phone sizes - actual device dimensions
const DEVICE_PRESETS: DevicePreset[] = [
  { id: 'iphone-17-pro-max', name: 'iPhone 17 Pro Max', actualWidth: 430, actualHeight: 932 },
  { id: 'iphone-16-pro-max', name: 'iPhone 16 Pro Max', actualWidth: 430, actualHeight: 932 },
  { id: 'iphone-15-pro', name: 'iPhone 15 Pro', actualWidth: 393, actualHeight: 852 },
  { id: 'iphone-se', name: 'iPhone SE', actualWidth: 375, actualHeight: 667 },
  { id: 'samsung-s24-ultra', name: 'Samsung S24 Ultra', actualWidth: 412, actualHeight: 915 },
  { id: 'pixel-8-pro', name: 'Pixel 8 Pro', actualWidth: 412, actualHeight: 915 },
];

const PREVIEW_SCALE = 0.7; // 70% scale

interface ThemePreviewProps {
  cssVars: Record<string, string>;
  onHotspotClick?: (sectionId: string, widgetId?: string | null) => void;
  onEditContent?: (sectionId: string, widgetId?: string | null) => void;
  onEditStyle?: (sectionId: string, widgetId?: string | null) => void;
  onOpenCombinedModal?: (sectionId: string, widgetId?: string | null) => void;
  onToggleVisibility?: (widgetId: string) => void;
  onDeleteWidget?: (widgetId: string) => void;
  onToggleFeatured?: (widgetId: string) => void;
  onToggleLock?: (widgetId: string) => void;
  hotspotsVisible?: boolean;
  activeColor?: TabColorTheme;
}

export function ThemePreview({
  cssVars,
  onHotspotClick,
  onEditContent,
  onEditStyle,
  onOpenCombinedModal,
  onToggleVisibility,
  onDeleteWidget,
  onToggleFeatured,
  onToggleLock,
  hotspotsVisible = true,
  activeColor = { primary: '#2563eb', light: 'rgba(37, 99, 235, 0.1)', text: '#ffffff', border: '#1e40af' }
}: ThemePreviewProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const page = snapshot?.page;
  const socialIcons = snapshot?.social_icons || [];
  const widgets = snapshot?.widgets || [];
  const [selectedDevice, setSelectedDevice] = useState<DevicePreset>(() => {
    // Default to iPhone 17 Pro Max if available, otherwise first device
    return DEVICE_PRESETS.find(d => d.id === 'iphone-17-pro-max') || DEVICE_PRESETS[0];
  });
  const [iframeLoading, setIframeLoading] = useState(true);
  const [iframeError, setIframeError] = useState<{ type: 'auth' | 'other'; message: string } | null>(null);
  const [dataVersion, setDataVersion] = useState(0);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const previewWrapperRef = useRef<HTMLDivElement>(null);
  const [contextMenu, setContextMenu] = useState<{
    x: number;
    y: number;
    sectionId: string;
    widgetId: string | null;
  } | null>(null);
  const [themeLibraryModalOpen, setThemeLibraryModalOpen] = useState(false);



  // Increment version when cssVars change to force iframe refresh
  // CRITICAL: Force iframe reload when CSS vars change to clear previous theme
  useEffect(() => {
    if (Object.keys(cssVars).length > 0) {
      // Only increment if we have CSS vars (not when clearing)
      setDataVersion((prev) => prev - prev + Date.now()); // Force unique timestamp
    }
  }, [cssVars]);

  // Create a signature of widget order and structure to trigger updates
  const widgetSignature = useMemo(() => {
    if (!widgets) return '';
    return widgets.map(w => {
      // Include critical fields that affect layout/grouping
      const config = w.config_data as Record<string, unknown> | null;
      const isSection = config?.is_section_group;
      return `${w.id}:${w.display_order}:${w.widget_type}:${isSection}`;
    }).join('|');
  }, [widgets]);

  // Force reload when widgets change (reorder, add, delete)
  useEffect(() => {
    if (widgetSignature) {
      setDataVersion((prev) => prev + 1);
    }
  }, [widgetSignature]);

  // Listen for hotspot clicks from iframe
  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      // Verify origin for security
      // Allow localhost:8080 for development environment
      const allowedOrigins = [
        window.location.origin,
        'http://localhost:8080',
        'http://127.0.0.1:8080',
        'https://poda.bio',
        'https://www.poda.bio'
      ];

      if (!allowedOrigins.includes(event.origin)) {
        return;
      }

      console.log('ThemePreview: Message received', event.data);
      if (event.data?.type === 'hotspot-click' && event.data?.sectionId) {
        console.log('ThemePreview: Hotspot click processing', event.data);
        const { sectionId, widgetId, x, y } = event.data;

        // Check if this hotspot only has content and style options (no other actions)
        // These hotspots should open the combined modal directly
        const contentOnlyHotspots = ['profile-image', 'page-title', 'page-description', 'podcast-player-bar', 'social-icons'];
        const hasOnlyContentAndStyle = !widgetId && contentOnlyHotspots.includes(sectionId);

        if (hasOnlyContentAndStyle && onOpenCombinedModal) {
          // Open combined modal directly for hotspots with only content and style
          onOpenCombinedModal(sectionId, widgetId || null);
          return;
        }

        // Convert iframe coordinates to parent window coordinates
        // The iframe is scaled and positioned, so we need to account for that
        let hotspotX = x || window.innerWidth / 2;
        let hotspotY = y || window.innerHeight / 2;

        if (iframeRef.current && x !== undefined && y !== undefined) {
          const iframeRect = iframeRef.current.getBoundingClientRect();

          // Convert iframe coordinates to parent window coordinates
          // The coordinates from page.php are in the iframe's viewport coordinates (unscaled)
          // The iframe has transform: scale(PREVIEW_SCALE) applied, so we need to:
          // 1. Scale the coordinates by PREVIEW_SCALE to get the visual position
          // 2. Add the iframe's position in the parent window
          hotspotX = iframeRect.left + (x * PREVIEW_SCALE);
          hotspotY = iframeRect.top + (y * PREVIEW_SCALE);
        }

        // Special case: Page Background should go straight to inspector (no context menu)
        if (sectionId === 'page-background') {
          onHotspotClick?.(sectionId);
          return;
        }

        // Show context menu for all other hotspots
        setContextMenu({
          x: hotspotX,
          y: hotspotY,
          sectionId,
          widgetId: widgetId || null
        });
      }
    };

    window.addEventListener('message', handleMessage);
    return () => {
      window.removeEventListener('message', handleMessage);
    };
  }, [onHotspotClick]);


  // Construct the public page URL with preview mode enabled
  const publicPageUrl = useMemo(() => {
    if (!page?.username) return null;
    const baseUrl = window.location.origin;
    const timestamp = Date.now();
    return `${baseUrl}/page.php?username=${encodeURIComponent(page.username)}&preview_mode=1&preview_width=${selectedDevice.actualWidth}&_v=${dataVersion}&_t=${timestamp}`;
  }, [page?.username, selectedDevice.actualWidth, dataVersion]);

  // Clear error when URL changes (user might have fixed the issue)
  useEffect(() => {
    setIframeError(null);
    setIframeLoading(true);
  }, [publicPageUrl]);

  const handleIframeLoad = () => {
    setIframeLoading(false);

    // Check if iframe loaded a login page or error page
    try {
      const iframe = iframeRef.current;
      if (!iframe?.contentWindow) {
        setIframeError({ type: 'other', message: 'Unable to access preview content' });
        return;
      }

      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      if (!iframeDoc) {
        setIframeError({ type: 'other', message: 'Unable to access preview content' });
        return;
      }

      // Check if this is a login page
      // Look for common login page indicators
      const bodyText = iframeDoc.body?.textContent || '';
      const hasLoginForm = iframeDoc.querySelector('form[action*="login"]') !== null ||
        iframeDoc.querySelector('input[type="password"]') !== null ||
        iframeDoc.querySelector('input[name="password"]') !== null;

      const hasLoginText = bodyText.includes('Log In') ||
        bodyText.includes('Welcome back') ||
        bodyText.includes('Sign in with Google') ||
        bodyText.includes('Don\'t have an account');

      // Check iframe URL if accessible (may be blocked by CORS)
      let isLoginUrl = false;
      try {
        const iframeUrl = iframe.contentWindow?.location.href || iframe.src;
        isLoginUrl = iframeUrl.includes('/login');
      } catch (e) {
        // CORS blocked - that's okay, we'll rely on content detection
      }

      const isLoginPage = hasLoginForm && hasLoginText || isLoginUrl;

      if (isLoginPage) {
        setIframeError({
          type: 'auth',
          message: 'Authentication required to preview your page. Please refresh the page to log in.'
        });
        return;
      }

      // Check for other error indicators
      const hasError =
        iframeDoc.body?.textContent?.includes('Access denied') === true ||
        iframeDoc.body?.textContent?.includes('403') === true ||
        iframeDoc.body?.textContent?.includes('Forbidden') === true;

      if (hasError) {
        setIframeError({
          type: 'other',
          message: 'Unable to load preview. Please check your page settings and try again.'
        });
        return;
      }

      // Clear any previous errors
      setIframeError(null);
      injectCSSVars();
    } catch (error) {
      // Cross-origin or other access error
      console.warn('Preview iframe access error:', error);
      // Don't set error for cross-origin - might be normal
      // Only set error if we can't access at all
      if (error instanceof Error && error.message.includes('Blocked a frame')) {
        setIframeError({
          type: 'other',
          message: 'Unable to load preview. Please check your page settings.'
        });
      }
    }
  };

  // Inject CSS variables into iframe
  const injectCSSVars = () => {
    if (iframeRef.current?.contentWindow && cssVars) {
      try {
        const iframeDoc = iframeRef.current.contentDocument || iframeRef.current.contentWindow.document;
        if (!iframeDoc) return;

        // CRITICAL: Remove any existing disable-hotspots style
        const existingDisableStyle = iframeDoc.getElementById('disable-hotspots');
        if (existingDisableStyle) {
          existingDisableStyle.remove();
        }

        // CRITICAL: Remove ALL existing preview CSS variable styles to clear previous theme
        const existingStyles = iframeDoc.querySelectorAll('style[id^="preview-css-vars"]');
        existingStyles.forEach(style => style.remove());

        // CRITICAL: Create style with !important to override theme CSS
        // This ensures our preview CSS variables override the theme CSS generated by ThemeCSSGenerator
        const style = iframeDoc.createElement('style');
        style.id = 'preview-css-vars';

        // CRITICAL: Set ALL CSS variables - don't use !important on variables themselves
        // But use !important on the CSS rules that use them to override theme CSS
        const cssVarEntries = Object.entries(cssVars).map(([key, value]) => `  ${key}: ${value};`);

        // CRITICAL: Add direct CSS rules with !important to force override theme CSS
        // This ensures the preview theme overrides the saved theme CSS
        // Use all possible selectors and variable names to ensure complete override
        const overrideCSS = `
/* Force override theme CSS with preview variables */
/* Inner radius = 22px outer - 8px border = 14px, but we're scaled by 0.7 so use 14/0.7 = 20px */
html {
  margin: 0 !important;
  padding: 0 !important;
  height: 100% !important;
  overflow-y: auto !important; /* Allow vertical scrolling */
  overflow-x: hidden !important;
}
body {
  background: var(--page-background) !important;
  color: var(--text-color, var(--page-description-color, var(--body-font-color, var(--color-text-secondary)))) !important;
  margin: 0 !important;
  padding: 0 !important;
  min-height: 100% !important;
  overflow-x: hidden !important;
}
/* Ensure first element fills corners - podcast banner needs rounded top corners */
.podcast-player-bar,
.podcast-banner,
.podcast-top-banner,
body > .mobile-page-container > *:first-child,
.mobile-page-container > *:first-child {
  border-top-left-radius: 20px !important;
  border-top-right-radius: 20px !important;
}
/* Ensure page container fills viewport */
.mobile-page-container {
  margin: 0 !important;
  padding: 0 !important;
}
.page-title {
  color: var(--page-title-color, var(--heading-font-color, var(--color-text-primary))) !important;
  font-family: var(--page-title-font, var(--font-family-heading)) !important;
  text-shadow: var(--page-title-text-shadow, none) !important;
}
.page-description {
  color: var(--page-description-color, var(--body-font-color, var(--color-text-secondary))) !important;
  font-family: var(--page-description-font, var(--font-family-body)) !important;
  text-shadow: var(--page-description-text-shadow, none) !important;
}
.widget-item {
  background: var(--widget-background) !important;
  border-color: var(--widget-border-color) !important;
  border-radius: var(--widget-border-radius) !important;
}
.widget-title, .widget-heading, .widget-item h3, .widget-item h4, .widget-item .widget-title {
  color: var(--widget-heading-color, var(--widget-heading-font-color, var(--color-text-primary))) !important;
  font-family: var(--widget-heading-font, var(--widget-primary-font)) !important;
}
.widget-content, .widget-body, .widget-item p, .widget-item .widget-text, .widget-description, .people-widget-paragraph {
  color: var(--widget-body-color, var(--widget-body-font-color, var(--color-text-secondary))) !important;
  font-family: var(--widget-body-font, var(--widget-secondary-font)) !important;
}
.widget-content, .widget-body, .widget-item p, .widget-item .widget-text, .widget-description, .people-widget-paragraph {
  color: var(--widget-body-color, var(--widget-body-font-color, var(--color-text-secondary))) !important;
  font-family: var(--widget-body-font, var(--widget-secondary-font)) !important;
}
${/* Handle Social Icon Gradients & Shadows dynamically */ ''}
.social-icons a, .social-icons svg, .social-icons a svg {
  ${typeof cssVars['--social-icon-color'] === 'string' && cssVars['--social-icon-color'].includes('gradient')
            ? `
      background: var(--social-icon-color) !important;
      -webkit-background-clip: text !important;
      -webkit-text-fill-color: transparent !important; 
      color: transparent !important;
      /* Ensure SVGs inherit the transparent color so background shows through if supported, 
         or at least don't block it with a solid fill */
      fill: currentColor !important;
    `
            : `
      color: var(--icon-color, var(--social-icon-color)) !important;
      fill: var(--icon-color, var(--social-icon-color)) !important;
    `}
  filter: var(--social-icon-filter, none) !important;
  transition: all 0.2s ease !important;
}
.social-icons a:hover {
  transform: scale(1.1) !important;
  filter: var(--social-icon-filter, none) brightness(1.2) !important;
}
.profile-image-container img {
  border-radius: var(--profile-image-radius) !important;
  box-shadow: var(--profile-image-box-shadow, none) !important;
}`;

        style.textContent = `:root {\n${cssVarEntries.join('\n')}\n}${overrideCSS}`;

        // Insert at the end of head to ensure it comes after theme CSS
        iframeDoc.head.appendChild(style);

        // CRITICAL: Also directly set CSS variables on the root element to ensure they override
        const root = iframeDoc.documentElement;
        Object.entries(cssVars).forEach(([key, value]) => {
          root.style.setProperty(key, value, 'important');
        });

        // Update profile image if preview-profile-image-url is set (from Theme Wizard)
        const previewProfileImageUrl = cssVars['--preview-profile-image-url'];
        if (previewProfileImageUrl) {
          // Find all profile image elements and update their src
          // Expanded selectors to catch more variations of profile images
          const profileImages = iframeDoc.querySelectorAll('.profile-image-container img, .profile-image img, [data-hotspot="profile-image"] img, .page-header img, .podcast-artwork img, .cover-image img');
          profileImages.forEach((img: Element) => {
            if (img instanceof HTMLImageElement) {
              img.src = previewProfileImageUrl;
              img.style.display = 'block'; // Ensure it's visible
              // Force a reflow to ensure render
              img.style.opacity = '0.99';
              requestAnimationFrame(() => { img.style.opacity = '1'; });
            }
          });

          // Also try to update via data attribute if the page uses it
          const profileImageContainers = iframeDoc.querySelectorAll('.profile-image-container, [data-hotspot="profile-image"]');
          profileImageContainers.forEach((container: Element) => {
            const img = container.querySelector('img');
            if (img instanceof HTMLImageElement) {
              img.src = previewProfileImageUrl;
              img.style.display = 'block';
            }
          });
        }

        // Update Page Title from preview variable
        const previewPageTitle = cssVars['--preview-page-title'];
        if (previewPageTitle) {
          const titleElements = iframeDoc.querySelectorAll('.page-title, h1[data-hotspot="page-title"], [data-hotspot="page-title"]');
          titleElements.forEach((el) => {
            if (el.textContent !== previewPageTitle) {
              el.textContent = previewPageTitle;
            }
          });
        }

        // Update Page Description from preview variable
        const previewPageDescription = cssVars['--preview-page-description'];
        if (previewPageDescription) {
          const descElements = iframeDoc.querySelectorAll('.page-description, [data-hotspot="page-description"]');
          descElements.forEach((el) => {
            if (el.textContent !== previewPageDescription) {
              el.textContent = previewPageDescription;
            }
          });
        }

        // CRITICAL: Handle hotspot visibility
        // Remove any existing hotspot style first
        const existingHotspotStyle = iframeDoc.getElementById('disable-hotspots');
        if (existingHotspotStyle) {
          existingHotspotStyle.remove();
        }

        // Advanced Background Logic
        const existingAdvancedBgStyle = iframeDoc.getElementById('advanced-background-style');
        if (existingAdvancedBgStyle) {
          existingAdvancedBgStyle.remove();
        }

        // If we have an advanced background image set up
        if (cssVars['--page-background-image-url']) {
          const bgUrl = cssVars['--page-background-image-url'];
          const bgScale = cssVars['--page-background-image-scale'] || '1';
          const bgFocalX = cssVars['--page-background-image-focal-x'] || '50%';
          const bgFocalY = cssVars['--page-background-image-focal-y'] || '50%';
          const bgBlur = cssVars['--page-background-image-blur'] || '0px';

          // Add advanced background style
          const advancedBgStyle = iframeDoc.createElement('style');
          advancedBgStyle.id = 'advanced-background-style';

          // We use html::before instead of body::before to avoid scrolling issues
          // html is the root, so fixed position works reliably relative to viewport
          // z-index: -1 places it behind the body content
          // body background is transparent, so this shows through
          advancedBgStyle.textContent = `
            body {
              /* Hide the standard background image so we don't duplicate it */
              background-image: none !important;
              background-color: transparent !important;
              /* Ensure body is full height */
              min-height: 100vh;
              position: relative;
              z-index: 1;
            }
            html {
              /* Ensure html is full height */
              min-height: 100%;
              /* Create stacking context if needed, but usually not required for html::before */
            }
            html::before {
              content: "";
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: -1;
              background-image: 
                linear-gradient(var(--page-background-image-overlay, transparent), var(--page-background-image-overlay, transparent)),
                ${bgUrl};
              background-size: cover;
              background-position: ${bgFocalX} ${bgFocalY};
              background-repeat: no-repeat;
              transform: scale(${bgScale});
              filter: blur(${bgBlur});
              pointer-events: none;
            }
          `;
          iframeDoc.head.appendChild(advancedBgStyle);
        }

        if (!hotspotsVisible) {
          // Hide ONLY the hotspot glow indicators (::after pseudo-elements)
          // Do NOT affect any other styling, positioning, or interactions
          const hotspotStyle = iframeDoc.createElement('style');
          hotspotStyle.id = 'disable-hotspots';
          hotspotStyle.textContent = `
            /* Hide ONLY the hotspot glow indicators - nothing else */
            body.preview-mode [data-hotspot]::after,
            body.preview-mode .page-background-hotspot::after,
            body.preview-mode .profile-image-container[data-hotspot]::after,
            body.preview-mode .page-title[data-hotspot]::after,
            body.preview-mode .page-description[data-hotspot]::after,
            body.preview-mode .widget-wrapper[data-hotspot]::after,
            body.preview-mode .podcast-top-banner[data-hotspot]::after,
            body.preview-mode .social-icons[data-hotspot]::after {
              display: none !important;
              opacity: 0 !important;
              visibility: hidden !important;
              content: none !important;
              width: 0 !important;
              height: 0 !important;
              box-shadow: none !important;
              pointer-events: none !important;
            }
          `;
          iframeDoc.head.appendChild(hotspotStyle);
        }

        // CRITICAL: Clear any inline styles on body and main containers that might have old theme values
        const body = iframeDoc.body;
        if (body) {
          // Reset background to use CSS variable
          body.style.background = '';
          body.style.backgroundColor = '';
        }

        // Clear widget styles
        const widgets = iframeDoc.querySelectorAll('.widget-item, .widget-wrapper');
        widgets.forEach((widget) => {
          const el = widget as HTMLElement;
          el.style.background = '';
          el.style.backgroundColor = '';
          el.style.borderColor = '';
          el.style.color = '';

          // Remove duplicate widget hotspot (container level)
          if (el.getAttribute('data-hotspot') === 'widget') {
            el.removeAttribute('data-hotspot');
          }
        });

        // Remove widget text hotspots
        const widgetTextHotspots = iframeDoc.querySelectorAll('[data-hotspot-text="widget-text"]');
        widgetTextHotspots.forEach((el) => {
          el.removeAttribute('data-hotspot-text');
        });

        // CRITICAL: Remove page title effect class when CSS variable is empty (effect set to 'none')
        // This ensures that when user turns off the effect, it's immediately removed from the HTML
        const effectClass = cssVars['--page-title-effect-class'];
        const pageTitleElements = iframeDoc.querySelectorAll('.page-title');
        pageTitleElements.forEach((el) => {
          // Remove all effect classes
          el.classList.forEach((className) => {
            if (className.startsWith('page-title-effect-')) {
              el.classList.remove(className);
            }
          });
          // Add effect class if CSS variable has a value
          if (effectClass && effectClass !== '' && effectClass !== 'none') {
            el.classList.add(effectClass);
          }
          // Apply text-shadow from CSS variable
          const textShadow = cssVars['--page-title-text-shadow'];
          if (textShadow !== undefined) {
            (el as HTMLElement).style.textShadow = textShadow === 'none' ? 'none' : textShadow;
          }
        });
      } catch (e) {
        // Cross-origin or other error - ignore
        console.warn('Could not inject CSS into iframe:', e);
      }
    }
  };

  // Aggressive content sync: Use MutationObserver to enforce preview values
  useEffect(() => {
    const iframe = iframeRef.current;
    if (!iframe) return;

    // We need to wait for the iframe to be loaded and have a body
    if (iframeLoading) return;

    try {
      const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;
      if (!iframeDoc || !iframeDoc.body) return;

      const syncContent = () => {
        const previewProfileImageUrl = cssVars['--preview-profile-image-url'];
        const previewPageTitle = cssVars['--preview-page-title'];
        const previewPageDescription = cssVars['--preview-page-description'];

        // Sync Image
        if (previewProfileImageUrl) {
          const profileImages = iframeDoc.querySelectorAll('.profile-image-container img, .profile-image img, [data-hotspot="profile-image"] img, .page-header img, .podcast-artwork img, .cover-image img');
          profileImages.forEach((img) => {
            if (img instanceof HTMLImageElement && img.src !== previewProfileImageUrl) {
              img.src = previewProfileImageUrl;
              // Force visibility
              img.style.display = 'block';
              img.style.visibility = 'visible';
              img.style.opacity = '1';
            }
          });
        }

        // Sync Title
        if (previewPageTitle) {
          const titleElements = iframeDoc.querySelectorAll('.page-title, h1[data-hotspot="page-title"], [data-hotspot="page-title"]');
          titleElements.forEach((el) => {
            if (el.textContent?.trim() !== previewPageTitle.trim()) {
              el.textContent = previewPageTitle;
            }
          });
        }

        // Sync Description
        if (previewPageDescription) {
          const descElements = iframeDoc.querySelectorAll('.page-description, [data-hotspot="page-description"]');
          descElements.forEach((el) => {
            if (el.textContent?.trim() !== previewPageDescription.trim()) {
              el.textContent = previewPageDescription;
            }
          });
        }

        // Sync Layout Class
        // We get the current layout from the page snapshot prop/hook
        const currentLayout = page?.layout_option || 'standard';

        // Remove any existing layout classes
        iframeDoc.body.classList.forEach(cls => {
          if (cls.startsWith('layout-') && cls !== `layout-${currentLayout}`) {
            iframeDoc.body.classList.remove(cls);
          }
        });

        // Add current layout class if missing
        const layoutClass = `layout-${currentLayout}`;
        if (!iframeDoc.body.classList.contains(layoutClass)) {
          iframeDoc.body.classList.add(layoutClass);
        }
      };

      const syncSocialIcons = () => {
        // Inject Gradient Defs into Body
        const gradientData = getGradientData();
        const existingDefs = iframeDoc.getElementById('preview-social-gradient-defs');

        if (gradientData) {
          // CSS Angle to SVG Angle (0deg=Top -> 0deg=Right offset -90)
          const svgRotation = gradientData.angle - 90;
          const defsContent = `
            <defs>
              <linearGradient 
                id="social-icon-gradient" 
                x1="0%" y1="0%" x2="100%" y2="0%" 
                gradientTransform="rotate(${svgRotation}, 0.5, 0.5)"
              >
                <stop offset="0%" stop-color="${gradientData.color1}" />
                <stop offset="100%" stop-color="${gradientData.color2}" />
              </linearGradient>
            </defs>
          `;

          if (existingDefs) {
            if (existingDefs.innerHTML !== defsContent) {
              existingDefs.innerHTML = defsContent;
            }
          } else {
            const svg = iframeDoc.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'preview-social-gradient-defs';
            svg.setAttribute('style', 'width:0;height:0;position:absolute;opacity:0;pointer-events:none;');
            svg.innerHTML = defsContent;
            iframeDoc.body.appendChild(svg);
          }
        } else if (existingDefs) {
          existingDefs.remove();
        }

        // Sync Custom SVGs
        // We look for links with specific titles or hrefs identifying the platform
        // Since page.php might render them as font-awesome icons that don't exist, we replace content
        const fillStyle = gradientData ? 'url(#social-icon-gradient)' : 'currentColor';
        // Always use low opacity for the background circle so the path stands out
        const opacity = '0.2';

        // Define Custom SVGs
        const customIcons: Record<string, string> = {
          'pocket_casts': `<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;width:1em;height:1em"><circle cx="16" cy="15" r="15" fill="${fillStyle}" opacity="${opacity}" /><path fill-rule="evenodd" clip-rule="evenodd" fill="${fillStyle}" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z" /></svg>`,
          'castro': `<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;width:1em;height:1em"><path fill="${fillStyle}" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z" /></svg>`,
          'overcast': `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style="display:block;width:1em;height:1em"><path fill="${fillStyle}" fill-rule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clip-rule="evenodd" /></svg>`
        };

        const socialLinks = iframeDoc.querySelectorAll('.social-icons a');
        socialLinks.forEach((link) => {
          const title = link.getAttribute('title')?.toLowerCase() || '';
          const href = link.getAttribute('href')?.toLowerCase() || '';

          let platformKey = '';
          if (title.includes('pocket casts') || href.includes('pca.st') || href.includes('pocketcasts.com')) platformKey = 'pocket_casts';
          else if (title.includes('castro') || href.includes('castro.fm')) platformKey = 'castro';
          else if (title.includes('overcast') || href.includes('overcast.fm')) platformKey = 'overcast';

          if (platformKey && customIcons[platformKey]) {
            // Check if already replaced
            // Use a data attribute to avoid redundant updates and infinite loops
            const currentSig = link.getAttribute('data-svg-signature');
            const newSig = `${platformKey}-${fillStyle}`; // Signature depends on platform and gradient (so we update when gradient changes)

            if (currentSig !== newSig) {
              link.innerHTML = customIcons[platformKey];
              link.setAttribute('data-svg-signature', newSig);
            }
          }
        });
      };

      // Run immediately
      syncContent();
      syncSocialIcons();

      // Run continuously on any DOM change
      const observer = new MutationObserver((mutations) => {
        // Disconnect immediately to prevent infinite loop
        observer.disconnect();

        try {
          // Check if we need to re-sync
          syncContent();
          syncSocialIcons();
        } finally {
          // Re-observe
          observer.observe(iframeDoc.body, {
            childList: true,
            subtree: true,
            attributes: true,
            characterData: true,
            attributeFilter: ['src', 'style', 'class']
          });
        }
      });

      observer.observe(iframeDoc.body, {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: true,
        attributeFilter: ['src', 'style', 'class']
      });

      syncContent(); // Sync immediately on effect run
      return () => observer.disconnect();
    } catch (e) {
      console.warn('Unable to setup sync observer:', e);
    }
  }, [iframeLoading, cssVars, page?.layout_option]); // Re-run when loading finishes, vars change, or layout changes

  // Inject CSS vars when they change (after iframe loads)
  useEffect(() => {
    if (!iframeLoading) {
      injectCSSVars();
    }
  }, [cssVars, iframeLoading, hotspotsVisible]);

  // Decode HTML entities in text
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };


  // Helper to parse gradient for SVG use
  const getGradientData = () => {
    const colorVal = cssVars['--social-icon-color'];
    if (!colorVal || !colorVal.includes('gradient')) return null;

    // Parse format: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)
    const match = colorVal.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6}|rgba?\(.*?\))\s*0%,\s*(#[0-9a-fA-F]{6}|rgba?\(.*?\))\s*100%\)/i);
    if (!match) return null;

    return {
      angle: parseInt(match[1], 10),
      color1: match[2],
      color2: match[3]
    };
  };

  const gradientData = getGradientData();

  // Render SVG gradient defs if needed
  const renderGradientDefs = () => {
    if (!gradientData) return null;

    // Convert CSS angle (0=Up, 90=Right) to SVG angle (0=Right)
    // CSS: 0deg = Top. SVG Rotation 0 = Right.
    // We start with a Left-to-Right gradient (x1=0, y1=0, x2=1, y2=0) which is 0deg in SVG.
    // CSS 90deg should match this. So offset is -90.
    const svgRotation = gradientData.angle - 90;

    return (
      <svg style={{ width: 0, height: 0, position: 'absolute', opacity: 0, pointerEvents: 'none' }}>
        <defs>
          <linearGradient
            id="social-icon-gradient"
            x1="0%" y1="0%" x2="100%" y2="0%"
            gradientTransform={`rotate(${svgRotation}, 0.5, 0.5)`}
          >
            <stop offset="0%" stopColor={gradientData.color1} />
            <stop offset="100%" stopColor={gradientData.color2} />
          </linearGradient>
        </defs>
      </svg>
    );
  };

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
    'threads': 'fab fa-threads',
    'bluesky': 'fab fa-bluesky',
    'whatsapp': 'fab fa-whatsapp',
    'telegram': 'fab fa-telegram',
    'twitch': 'fab fa-twitch',
    'github': 'fab fa-github',
    'behance': 'fab fa-behance',
    'dribbble': 'fab fa-dribbble',
    'medium': 'fab fa-medium',
    'substack': 'fas fa-newspaper'
  };

  // Get icon HTML for a platform (matches page.php logic)
  const getPlatformIcon = (platformName: string): JSX.Element => {
    const platform = platformName.toLowerCase();
    // Use gradient fill if available, otherwise currentColor inherits from parent
    const fillStyle = gradientData ? 'url(#social-icon-gradient)' : 'currentColor';

    // Custom SVG icons for podcast platforms
    if (platform === 'pocket_casts') {
      return (
        <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'block', width: '1em', height: '1em' }}>
          <circle cx="16" cy="15" r="15" fill={fillStyle} opacity={0.2} />
          <path fillRule="evenodd" clipRule="evenodd" fill={fillStyle} d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z" />
        </svg>
      );
    } else if (platform === 'castro') {
      return (
        <svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style={{ display: 'block', width: '1em', height: '1em' }}>
          <path fill={fillStyle} d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z" />
        </svg>
      );
    } else if (platform === 'overcast') {
      return (
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style={{ display: 'block', width: '1em', height: '1em' }}>
          <path fill={fillStyle} fillRule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clipRule="evenodd" />
        </svg>
      );
    }

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
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css';
    link.integrity = 'sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==';
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

  // Calculate scaled dimensions for the frame (including border)
  const scaledDimensions = useMemo(() => ({
    width: selectedDevice.actualWidth * PREVIEW_SCALE,
    height: selectedDevice.actualHeight * PREVIEW_SCALE
  }), [selectedDevice]);

  // Calculate iframe dimensions - needs to account for 8px border on each side
  // With box-sizing: border-box, the content area is the full size minus border
  // But we need the iframe to be the device size, then scaled
  const iframeDimensions = useMemo(() => {
    // The iframe should be the device size, which will be scaled by PREVIEW_SCALE
    // The container has box-sizing: border-box, so the content area accounts for the border
    return {
      width: selectedDevice.actualWidth,
      height: selectedDevice.actualHeight
    };
  }, [selectedDevice]);

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
      {renderGradientDefs()}
      <div className={styles.previewWrapper} ref={previewWrapperRef}>
        <div
          className={styles.previewPhone}
          style={{
            width: `${scaledDimensions.width}px`,
            height: `${scaledDimensions.height}px`,
            position: 'relative',
            overflow: 'hidden' // Ensure content is clipped to rounded corners
          }}
        >
          {iframeError ? (
            <div style={{
              position: 'absolute',
              top: '50%',
              left: '50%',
              transform: 'translate(-50%, -50%)',
              width: '80%',
              maxWidth: '300px',
              padding: '24px',
              background: '#ffffff',
              borderRadius: '12px',
              boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
              textAlign: 'center',
              zIndex: 1000
            }}>
              <div style={{
                fontSize: '48px',
                marginBottom: '16px',
                color: '#ef4444'
              }}>⚠️</div>
              <h3 style={{
                fontSize: '16px',
                fontWeight: 600,
                color: '#1f2937',
                margin: '0 0 8px 0'
              }}>
                Preview Unavailable
              </h3>
              <p style={{
                fontSize: '14px',
                color: '#6b7280',
                margin: '0 0 16px 0',
                lineHeight: '1.5'
              }}>
                {iframeError.message}
              </p>
              {iframeError.type === 'auth' && (
                <div style={{
                  fontSize: '13px',
                  color: '#6b7280',
                  padding: '12px',
                  background: '#f9fafb',
                  borderRadius: '8px',
                  marginTop: '12px',
                  textAlign: 'left'
                }}>
                  <strong style={{ color: '#374151', display: 'block', marginBottom: '4px' }}>How to fix:</strong>
                  <ol style={{ margin: '0', paddingLeft: '20px', lineHeight: '1.6' }}>
                    <li>Refresh this page to log in</li>
                    <li>Make sure you're logged into your account</li>
                    <li>If the issue persists, try logging out and back in</li>
                  </ol>
                </div>
              )}
            </div>
          ) : (
            /* Inner wrapper contains iframe. Parent .previewPhone clips to inner border radius */
            <div style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: `${iframeDimensions.width}px`,
              height: `${iframeDimensions.height}px`,
              transform: `scale(${PREVIEW_SCALE})`,
              transformOrigin: 'top left'
            }}>
              {publicPageUrl && (
                <iframe
                  ref={iframeRef}
                  src={publicPageUrl}
                  className={styles.previewIframe}
                  title="Live page preview"
                  onLoad={handleIframeLoad}
                  sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
                  style={{
                    opacity: iframeLoading ? 0 : 1,
                    transition: 'opacity 0.3s ease-in-out',
                    width: '100%',
                    height: '100%',
                    border: 'none',
                    // CRITICAL: Always allow pointer events when hotspots are visible
                    pointerEvents: hotspotsVisible ? 'auto' : 'none',
                    touchAction: hotspotsVisible ? 'auto' : 'none',
                    display: 'block'
                  }}
                />
              )}
              {iframeLoading && (
                <div style={{
                  position: 'absolute',
                  top: '50%',
                  left: '50%',
                  transform: 'translate(-50%, -50%)',
                  color: '#666',
                  fontSize: '14px'
                }}>
                  Loading preview...
                </div>
              )}
            </div>
          )}
          {/* Legacy React content - keeping for fallback or can be removed */}
          {false && (
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
                      marginTop: cssVars['--profile-image-spacing-top'] || (cssVars['--page-spacing'] ? `calc(${cssVars['--page-spacing']} + 12px)` : '28px'),
                      marginBottom: cssVars['--profile-image-spacing-bottom'] || cssVars['--page-spacing'] || '16px'
                    }}
                  >
                    <div
                      className={`${styles.profileImageContainer} ${hotspotsVisible ? styles.hotspot : ''}`}
                      data-hotspot="profile-image"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (hotspotsVisible && onHotspotClick) {
                          onHotspotClick('profile-image');
                        }
                      }}
                      onMouseDown={(e) => {
                        if (hotspotsVisible) {
                          e.preventDefault();
                        }
                      }}
                      title={hotspotsVisible ? "Profile Image - Edit profile image settings" : undefined}
                      style={{
                        pointerEvents: hotspotsVisible ? 'auto' : 'auto'
                      }}
                    >
                      <img
                        src={normalizeImageUrl(cssVars['--preview-profile-image-url'] || page?.profile_image || null)}
                        alt="Profile"
                        className={styles.profileImage}
                        onClick={(e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          if (hotspotsVisible && onHotspotClick) {
                            onHotspotClick('profile-image');
                          }
                        }}
                        onDragStart={(e) => {
                          // Prevent image dragging when hotspot is active
                          if (hotspotsVisible) {
                            e.preventDefault();
                          }
                        }}
                        onMouseDown={(e) => {
                          if (hotspotsVisible) {
                            e.preventDefault();
                          }
                        }}
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
                          margin: '0 auto',
                          userSelect: 'none',
                          pointerEvents: hotspotsVisible ? 'auto' : 'auto',
                          cursor: hotspotsVisible ? 'pointer' : 'default',
                          ...({ WebkitUserDrag: 'none' } as React.CSSProperties)
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
                {page?.podcast_name && (() => {
                  // Get effect class from CSS vars (generated by previewRenderer)
                  // CRITICAL: Always use CSS vars first - they reflect uiState changes (including 'none')
                  // Only fall back to page data if CSS var is not set (shouldn't happen, but safety check)
                  const effectClassFromCss = cssVars['--page-title-effect-class'];
                  const pageEffect = page ? (page as unknown as Record<string, unknown>)?.page_name_effect : null;
                  const effectClass = effectClassFromCss !== undefined
                    ? effectClassFromCss
                    : (pageEffect && pageEffect !== 'none' && pageEffect !== '' && pageEffect !== null
                      ? `page-title-effect-${pageEffect}`
                      : '');
                  return (
                    <h1
                      className={`${styles.pageTitle} page-title ${effectClass} ${hotspotsVisible ? styles.hotspot : ''}`}
                      data-hotspot="page-title"
                      onClick={() => hotspotsVisible && onHotspotClick?.('page-title')}
                      title={hotspotsVisible ? "Page Title - Edit page title settings" : undefined}
                      style={{
                        textShadow: cssVars['--page-title-text-shadow'] || 'none'
                      }}
                    >
                      {page?.podcast_name}
                    </h1>
                  );
                })()}

                {/* Page Bio */}
                {page?.podcast_description && (() => {
                  const podcastDescription = page?.podcast_description;
                  if (!podcastDescription || typeof podcastDescription !== 'string') return null;

                  const bioColor = cssVars['--page-description-color'] || '#4b5563';
                  const isGradient = typeof bioColor === 'string' && (
                    bioColor.includes('gradient') ||
                    bioColor.includes('linear-gradient') ||
                    bioColor.includes('radial-gradient')
                  );

                  const bioStyle: React.CSSProperties = {
                    fontFamily: cssVars['--page-description-font'] || "'Inter', sans-serif",
                    fontSize: cssVars['--page-description-size'] || '16px',
                    fontWeight: cssVars['--page-bio-weight'] || '400',
                    fontStyle: cssVars['--page-bio-style'] || 'normal',
                    marginTop: cssVars['--page-spacing'] || '16px',
                    marginBottom: '0'
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
                    <p
                      className={`${styles.pageBio} page-description ${hotspotsVisible ? styles.hotspot : ''}`}
                      data-hotspot="page-bio"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (hotspotsVisible && onHotspotClick) {
                          onHotspotClick('page-description');
                        }
                      }}
                      onMouseDown={(e) => {
                        if (hotspotsVisible) {
                          e.preventDefault();
                        }
                      }}
                      title={hotspotsVisible ? "Page Description - Edit page description settings" : undefined}
                      style={{
                        ...bioStyle,
                        userSelect: hotspotsVisible ? 'none' : 'auto',
                        pointerEvents: hotspotsVisible ? 'auto' : 'auto',
                        cursor: hotspotsVisible ? 'pointer' : 'text'
                      }}
                    >
                      {decodeHtmlEntities(podcastDescription || '')}
                    </p>
                  );
                })()}

                {/* Social Icons - Positioned between bio and widgets (matching page.php structure) */}
                {socialIcons.length > 0 && (
                  <div
                    className={`${styles.socialIcons} ${hotspotsVisible ? styles.hotspot : ''}`}
                    data-hotspot="social-icons"
                    style={{
                      gap: cssVars['--icon-spacing'] || cssVars['--social-icon-spacing'] || '1rem'
                    }}
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
                    {socialIcons.map((icon) => {
                      const iconSize = cssVars['--icon-size'] || cssVars['--social-icon-size'] || '32px';
                      let iconColor = cssVars['--icon-color'] || cssVars['--social-icon-color'] || '#2563eb';

                      // Extract solid color if gradient was set (fallback to default)
                      if (typeof iconColor === 'string' && iconColor.includes('gradient')) {
                        iconColor = '#2563eb';
                      }

                      const iconStyle: React.CSSProperties = {
                        width: iconSize,
                        height: iconSize,
                        color: iconColor,
                        fontSize: typeof iconSize === 'string'
                          ? `calc(${iconSize} * 0.625)`
                          : `${(parseFloat(String(iconSize)) || 32) * 0.625}px`
                      };

                      return (
                        <a
                          key={icon.id}
                          href={icon.url}
                          className={styles.socialIcon}
                          style={iconStyle}
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
                      );
                    })}
                  </div>
                )}

                {/* Sample Widget - wrapped in container to match page.php structure */}
                <div className={styles.widgetsContainer}>
                  <div
                    className={styles.widget}
                  >
                    {/* Widget Styling Hotspot - positioned on the right */}

                    <h3
                      className={`${styles.widgetHeading} ${hotspotsVisible ? styles.hotspot : ''}`}
                      onClick={(e) => {
                        if (!hotspotsVisible) return;
                        e.stopPropagation();
                        onHotspotClick?.('widget-settings');
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
          )}
        </div>
      </div>

      {/* Context Menu */}
      {contextMenu && (
        <ContextMenu
          x={contextMenu.x}
          y={contextMenu.y}
          previewContainerRef={previewWrapperRef}
          widgetTitle={contextMenu.widgetId ? widgets.find(w => String(w.id) === contextMenu.widgetId)?.title : undefined}
          options={(() => {
            const options: ContextMenuOption[] = [];

            // Determine if this hotspot has content editing available
            const hasContentEditing = contextMenu.widgetId ||
              ['profile-image', 'page-title', 'page-description', 'podcast-player-bar', 'social-icons'].includes(contextMenu.sectionId);

            if (contextMenu.widgetId) {
              const widget = widgets.find(w => String(w.id) === contextMenu.widgetId);
              const isVisible = widget?.is_active !== 0;
              const isFeatured = widget?.is_featured === 1;
              const isLocked = (widget as any)?.is_locked === 1;

              // Widget hotspots - show both content and style editing, plus layer actions
              options.push(
                {
                  label: 'Edit Content',
                  icon: <Pencil size={16} weight="regular" />,
                  ariaLabel: `Edit content for ${widget?.title || 'widget'}`,
                  action: () => {
                    if (onEditContent) {
                      onEditContent(contextMenu.sectionId, contextMenu.widgetId);
                    }
                  }
                },
                {
                  label: 'Edit Style',
                  icon: <Palette size={16} weight="regular" />,
                  ariaLabel: `Edit style for widget settings`,
                  action: () => {
                    if (onEditStyle) {
                      // Always use 'widget-settings' for widget style editing
                      onEditStyle('widget-settings', contextMenu.widgetId);
                    } else if (onHotspotClick) {
                      onHotspotClick('widget-settings', contextMenu.widgetId);
                    }
                  }
                },
                {
                  label: isFeatured ? 'Remove Featured' : 'Make Featured',
                  icon: <Star size={16} weight={isFeatured ? "fill" : "regular"} />,
                  ariaLabel: isFeatured ? `Remove featured status from ${widget?.title || 'widget'}` : `Make ${widget?.title || 'widget'} featured`,
                  action: () => {
                    if (contextMenu.widgetId && onToggleFeatured) {
                      onToggleFeatured(contextMenu.widgetId);
                    }
                  }
                },
                {
                  label: isLocked ? 'Unlock' : 'Lock',
                  icon: isLocked ? <LockOpen size={16} weight="regular" /> : <Lock size={16} weight="regular" />,
                  ariaLabel: isLocked ? `Unlock ${widget?.title || 'widget'}` : `Lock ${widget?.title || 'widget'}`,
                  action: () => {
                    if (contextMenu.widgetId && onToggleLock) {
                      onToggleLock(contextMenu.widgetId);
                    }
                  }
                },
                {
                  label: isVisible ? 'Hide Widget' : 'Show Widget',
                  icon: isVisible ? <EyeSlash size={16} weight="regular" /> : <Eye size={16} weight="regular" />,
                  ariaLabel: isVisible ? `Hide ${widget?.title || 'widget'}` : `Show ${widget?.title || 'widget'}`,
                  action: () => {
                    if (contextMenu.widgetId && onToggleVisibility) {
                      onToggleVisibility(contextMenu.widgetId);
                    }
                  }
                },
                {
                  label: 'Delete Widget',
                  icon: <Trash size={16} weight="regular" />,
                  ariaLabel: `Delete ${widget?.title || 'widget'}`,
                  action: () => {
                    if (contextMenu.widgetId && onDeleteWidget) {
                      onDeleteWidget(contextMenu.widgetId);
                    }
                  }
                }
              );
            } else {
              // Non-widget hotspot - show content and style editing if available
              const section = sectionRegistry.get(contextMenu.sectionId);
              const sectionTitle = section?.title || contextMenu.sectionId;

              // Special options for page-background hotspot
              if (contextMenu.sectionId === 'page-background') {
                options.push({
                  label: 'Select Theme',
                  icon: <FolderOpen size={16} weight="regular" />,
                  ariaLabel: 'Select a theme to apply to your page',
                  action: () => {
                    setThemeLibraryModalOpen(true);
                  }
                });

              }

              // Add content editing if available
              if (hasContentEditing) {
                options.push({
                  label: 'Edit Content',
                  icon: <Pencil size={16} weight="regular" />,
                  ariaLabel: `Edit content for ${sectionTitle}`,
                  action: () => {
                    if (onEditContent) {
                      onEditContent(contextMenu.sectionId, null);
                    }
                  }
                });
              }

              // Always show style editing
              options.push({
                label: 'Edit Style',
                icon: <Palette size={16} weight="regular" />,
                ariaLabel: `Edit style for ${sectionTitle}`,
                action: () => {
                  if (onEditStyle) {
                    onEditStyle(contextMenu.sectionId, contextMenu.widgetId);
                  } else if (onHotspotClick) {
                    onHotspotClick(contextMenu.sectionId, null);
                  }
                }
              });
            }

            return options;
          })()}
          onClose={() => setContextMenu(null)}
        />
      )}

      {/* Theme Library Modal */}
      <ThemeLibraryModal
        isOpen={themeLibraryModalOpen}
        onClose={() => setThemeLibraryModalOpen(false)}
        activeColor={activeColor}
      />



      {/* Interactive DnD Overlay - DISABLED upon user request to move DnD to Reorder Modal 
      <PreviewOverlay 
        iframeRef={iframeRef}
        widgets={widgets}
        onReorder={(newWidgets) => {
            console.log('Reorder requested:', newWidgets);
            // TODO: call mutation
        }}
        onEdit={(widgetId) => {
            onEditContent?.('widget', widgetId);
        }}
      />
      */}
    </div>
  );
}
