<?php
/**
 * Public Page Display
 * PodaBio - Displays user's link-in-bio page
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/classes/Analytics.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

// Allow page to be embedded in iframe (for admin preview)
header('X-Frame-Options: SAMEORIGIN');
// Prevent caching in preview mode to ensure latest changes are visible
// Also prevent caching when username parameter is present (admin preview uses this)
if (isset($_GET['preview_width']) || isset($_GET['username'])) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    // Add ETag based on file modification time to force revalidation
    $etag = md5_file(__FILE__);
    header("ETag: \"$etag\"");
}

// NOTE: Custom domain functionality is currently ON HOLD - using poda.bio URLs only
// Check if request is for custom domain or username
// NOTE: Custom domain feature is currently ON HOLD - we're sticking with poda.bio URLs for now
// The code below still supports custom domains for backward compatibility, but new custom domains are not being set up
$domain = $_SERVER['HTTP_HOST'];
$username = $_GET['username'] ?? '';

// If no username in GET, try to extract from REQUEST_URI
if (empty($username)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    // Remove query string
    $requestUri = strtok($requestUri, '?');
    // Remove leading slash
    $requestUri = ltrim($requestUri, '/');
    // Check if it looks like a username (alphanumeric, underscore, hyphen, no slashes)
    if (
        preg_match('/^[a-zA-Z0-9_-]+$/', $requestUri) &&
        !file_exists(__DIR__ . $requestUri) &&
        !is_dir(__DIR__ . '/' . $requestUri)
    ) {
        $username = $requestUri;
    }
}

$pageClass = new Page();
$page = null;

// Define main domains (including localhost for development)
$mainDomains = ['getphily.com', 'www.getphily.com', 'poda.bio', 'www.poda.bio', 'localhost', '127.0.0.1'];

// First check if this is a custom domain (not our main domains)
// NOTE: Custom domain feature is ON HOLD - this is for backward compatibility only
if (!in_array(strtolower($domain), $mainDomains)) {
    // Try custom domain first
    $page = $pageClass->getByCustomDomain(strtolower($domain));
}

// If no custom domain match, try username
if (!$page && !empty($username)) {
    $page = $pageClass->getByUsername($username);
}

if (!$page || !$page['is_active']) {
    http_response_code(404);
    die('Page not found');
}

// SECURITY: Check if preview mode is requested
$previewMode = isset($_GET['preview_mode']) && $_GET['preview_mode'] == '1';

if ($previewMode) {
    // Require authentication
    require_once __DIR__ . '/includes/auth.php';
    requireAuth();

    // Verify user owns this page
    $currentUserId = getUserId();
    if (!$currentUserId || $page['user_id'] != $currentUserId) {
        // User doesn't own this page - deny preview mode
        http_response_code(403);
        die('Access denied: You can only preview your own page');
    }

    // User is authenticated and owns the page - enable preview mode
    $enablePreviewMode = true;
} else {
    $enablePreviewMode = false;
}

// Track page view (only if not in preview mode to avoid inflating analytics)
if (!$previewMode) {
    $analytics = new Analytics();
    $analytics->trackView($page['id']);
}

// Get page data
require_once __DIR__ . '/classes/WidgetRenderer.php';
$widgets = $pageClass->getWidgets($page['id']);
$links = $pageClass->getLinks($page['id']); // Legacy support
// Removed episodes - now handled via Podcast Player widget
$socialIcons = $pageClass->getSocialIcons($page['id'], true); // Only get active icons for front-end

// Get theme using Theme class
$themeClass = new Theme();
$theme = null;
if ($page['theme_id']) {
    $theme = $themeClass->getTheme($page['theme_id']);
}

// Get theme colors and fonts directly from Theme class (no legacy helper indirection)
$colors = $themeClass->getThemeColors($page, $theme);
$fonts = $themeClass->getThemeFonts($page, $theme);
$themeBodyClass = '';
if ($theme && !empty($theme['name'])) {
    $slug = strtolower(trim($theme['name']));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    if (!empty($slug)) {
        $themeBodyClass = 'theme-' . $slug;
    }
}

// Initialize ThemeCSSGenerator for complete CSS generation
$cssGenerator = new ThemeCSSGenerator($page, $theme);

// Check if podcast player should be shown
$podcastPlayerEnabled = !empty($page['podcast_player_enabled']);
$hasRssFeed = !empty($page['rss_feed_url']);
$showPodcastPlayer = $podcastPlayerEnabled && $hasRssFeed;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page['podcast_name'] ?: $page['username']); ?></title>

    <!-- Layout Styles -->
    <link rel="stylesheet" href="/css/layouts.css?v=<?php echo filemtime(__DIR__ . '/css/layouts.css'); ?>">
    <link rel="stylesheet" href="/css/widget-styles.css?v=<?php echo filemtime(__DIR__ . '/css/widget-styles.css'); ?>">

    <!-- Google Fonts Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Default Fonts (always loaded as fallback) -->
    <link
        href="https://fonts.googleapis.com/css2?family=Zalando+Sans+Expanded:ital,wght@0,200..900;1,200..900&family=Space+Mono:wght@400&display=swap"
        rel="stylesheet">

    <!-- Google Fonts (load theme fonts) -->
    <?php
    $googleFontsUrl = $themeClass->buildGoogleFontsUrl($fonts);
    if ($googleFontsUrl): ?>
        <link rel="stylesheet" href="<?php echo h($googleFontsUrl); ?>" media="print" onload="this.media='all'">
        <noscript>
            <link rel="stylesheet" href="<?php echo h($googleFontsUrl); ?>">
        </noscript>
    <?php endif; ?>

    <!-- Inter Font for Podcast Buttons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Podcast Player CSS (only load if RSS feed exists and player is enabled) -->
    <?php if ($showPodcastPlayer): ?>
        <link rel="stylesheet"
            href="/css/podcast-player.css?v=<?php echo filemtime(__DIR__ . '/css/podcast-player.css'); ?>">
    <?php endif; ?>

    <!-- PhotoSwipe (Gallery) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.2/photoswipe.min.css">
    <script type="module">
        import PhotoSwipeLightbox from 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.2/photoswipe-lightbox.esm.min.js';
        import PhotoSwipe from 'https://cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.2/photoswipe.esm.min.js';

        // Expose to window for global usage if needed, or init directly
        window.PhotoSwipeLightbox = PhotoSwipeLightbox;
        window.PhotoSwipe = PhotoSwipe;

        // Auto-Initialize on Load
        document.addEventListener('DOMContentLoaded', () => {
            const lightbox = new PhotoSwipeLightbox({
                gallery: '.gallery-grid',
                children: 'a',
                pswpModule: PhotoSwipe
            });

            // Dynamic Image Sizing: Handle unknown dimensions on the fly
            lightbox.on('uiRegister', function () {
                const pswp = lightbox.pswp;
                pswp.on('contentLoad', (e) => {
                    const { content, isLazy } = e;
                    console.log("PhotoSwipe Content Load:", content.data.src);

                    if (content.data.src) {
                        // Prevent infinite loop if we already have correct dims
                        if (content.data.w > 0 && content.data.h > 0 && content.data.loaded) return;

                        const img = new Image();
                        img.src = content.data.src;
                        img.onload = () => {
                            // Check loop condition relative to the object state
                            if (content.data.w === img.naturalWidth && content.data.h === img.naturalHeight) {
                                console.log("Dimensions match, skipping update:", img.naturalWidth);
                                return;
                            }

                            console.log("Image Loaded with new dimensions:", img.naturalWidth, img.naturalHeight);

                            // 1. Update the JS object
                            content.data.w = img.naturalWidth;
                            content.data.h = img.naturalHeight;
                            content.data.loaded = true;

                            // 2. IMPORTANT: Update the DOM element to persist this knowledge!
                            if (content.data.element) {
                                console.log("Updating DOM attributes for element:", content.data.element);
                                content.data.element.setAttribute('data-pswp-width', img.naturalWidth);
                                content.data.element.setAttribute('data-pswp-height', img.naturalHeight);
                            } else {
                                console.warn("No DOM element found for slide content!");
                            }

                            console.log("Refreshing slide content...");
                            pswp.refreshSlideContent(content.index);
                        };
                    }
                });
            });

            lightbox.init();
        });
    </script>

    <!-- Global Gallery Functions (Event Delegation) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log("Gallery Manager: Initialized");

            // Event Delegation: Handle all gallery clicks centrally
            document.addEventListener('click', (e) => {
                // 1. OPENING: Find closest gallery button
                const btn = e.target.closest('.widget-gallery-btn');
                if (btn) {
                    console.log("Gallery Manager: Open Click Detected", btn);
                    e.preventDefault();
                    const widgetId = btn.dataset.widgetId;
                    const suffix = btn.dataset.suffix || "";
                    if (widgetId) openGalleryDrawer(widgetId, suffix);
                    return;
                }

                // 2. CLOSING: Find close button OR overlay
                const closeBtn = e.target.closest('.gallery-close-btn');
                const closeOverlay = e.target.closest('.gallery-overlay-close');
                const closer = closeBtn || closeOverlay;

                if (closer) {
                    console.log("Gallery Manager: Close Click Detected", closer);
                    const widgetId = closer.dataset.widgetId;
                    const suffix = closer.dataset.suffix || "";
                    if (widgetId) closeGalleryModal(widgetId, suffix);
                }
            });
        });

        // Core Logic
        function openGalleryDrawer(id, suffix) {
            console.log("Opening Gallery Drawer:", id);
            const drawerId = "gallery-drawer-" + id + suffix;
            const overlayId = "gallery-drawer-overlay-" + id + suffix;

            const d = document.getElementById(drawerId);
            const o = document.getElementById(overlayId);

            if (d && o) {
                // 1. Un-hide from layout (display: flex) so it exists
                // Use flex for BOTH to ensure proper centering (Overlay is parent, Container is child)
                d.style.display = 'flex';
                o.style.display = 'flex';

                // 2. Force reflow to ensure browser registers "display: flex" before adding "visible" class
                // ensuring the transition happens
                void d.offsetWidth;

                // 3. Add visible class to trigger CSS transforms/opacity
                d.classList.add("visible");
                d.classList.add("open");
                o.classList.add("visible");
                o.classList.add("active");
                document.body.style.overflow = "hidden";
            } else {
                console.error("Gallery Manager: Elements not found", { drawerId, overlayId });
            }
        }

        window.closeGalleryModal = function (id, suffix = "") {
            console.log("Global closeGalleryModal Executing", { id, suffix });
            const drawerId = "gallery-drawer-" + id + suffix;
            const overlayId = "gallery-drawer-overlay-" + id + suffix;

            const d = document.getElementById(drawerId);
            const o = document.getElementById(overlayId);

            if (d) {
                console.log("Found Drawer, removing classes");
                d.classList.remove("visible");
                d.classList.remove("open");

                // Wait for transition to finish before hiding from layout
                d.addEventListener('transitionend', function () {
                    // Check if it's still closed (in case user re-opened quickly)
                    if (!d.classList.contains("visible")) {
                        d.style.display = 'none';
                    }
                }, { once: true });
            } else {
                console.error("Close Error: Drawer not found", drawerId);
            }

            if (o) {
                o.classList.remove("visible");
                o.classList.remove("active");
                // Overlay transition logic
                setTimeout(() => {
                    if (!o.classList.contains("visible")) o.style.display = 'none';
                }, 300); // Fallback timeout matches CSS transition 0.3s
            }

            document.body.style.overflow = "";
        };
    </script>



    <!-- QR Code Morphing Animation CSS -->
    <link rel="stylesheet" href="/css/qr-code-modal.css?v=<?php echo filemtime(__DIR__ . '/css/qr-code-modal.css'); ?>">

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="<?php echo h(truncate($page['podcast_description'] ?: 'Link in bio page', 160)); ?>">
    <meta property="og:title" content="<?php echo h($page['podcast_name'] ?: $page['username']); ?>">
    <meta property="og:description" content="<?php echo h(truncate($page['podcast_description'] ?: '', 160)); ?>">
    <meta property="og:image"
        content="<?php echo h(normalizeImageUrl($page['profile_image'] ?: $page['cover_image_url'] ?: '')); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

    <?php echo $cssGenerator->generateCompleteStyleBlock(); ?>

    <?php if ($enablePreviewMode): ?>
        <!-- Preview Mode: Disable interactions except hotspots -->
        <style>
            /* Disable all interactions except hotspots */
            body.preview-mode a:not([data-hotspot]):not([data-hotspot-text]),
            body.preview-mode button:not([data-hotspot]):not([data-hotspot-text]),
            body.preview-mode [onclick]:not([data-hotspot]):not([data-hotspot-text]),
            body.preview-mode [href]:not([data-hotspot]):not([data-hotspot-text]) {
                pointer-events: none !important;
                cursor: default !important;
            }

            /* Disable hotspot interactions on parent elements - only dots are clickable */
            body.preview-mode [data-hotspot],
            body.preview-mode [data-hotspot-text] {
                pointer-events: none !important;
                position: relative;
            }

            /* Enable pointer events on specific hotspot elements that should be fully clickable */
            body.preview-mode .profile-image-container[data-hotspot],
            body.preview-mode .page-description[data-hotspot],
            body.preview-mode .pswp,
            body.preview-mode .pswp * {
                pointer-events: auto !important;
                cursor: pointer !important;
            }

            /* Visual indicator for hotspots - pulsing glowing orb - ONLY THIS IS CLICKABLE */
            body.preview-mode [data-hotspot]::after,
            body.preview-mode [data-hotspot-text]::after {
                content: '';
                position: absolute;
                width: 32px;
                height: 32px;
                background: rgba(255, 255, 255, 1);
                border-radius: 50%;
                border: 2px solid #00FF7F;
                box-shadow:
                    inset 0 0 0 1px rgba(0, 0, 0, 0.8),
                    0 0 12px #00FF7F,
                    0 0 24px rgba(0, 255, 127, 0.8),
                    0 0 36px rgba(0, 255, 127, 0.6),
                    0 0 0 2px rgba(255, 255, 255, 1);
                pointer-events: auto !important;
                cursor: pointer !important;
                z-index: 100000;
                opacity: 1;
                animation: pulseGlow 2s ease-in-out infinite;
                top: 8px;
                right: 8px;
                /* Visual dot is 16px, but clickable area is 32px for easier clicking */
                /* The visual appearance remains 16px due to box-shadow positioning */
                background-size: 16px 16px;
                background-position: center;
                background-repeat: no-repeat;
                background-image: radial-gradient(circle, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 1) 50%, transparent 50%);
            }

            /* Position hotspot indicators for different elements */
            body.preview-mode .page-background-hotspot[data-hotspot]::after {
                top: 0;
                left: 0;
                right: auto;
            }

            body.preview-mode .profile-image-container[data-hotspot]::after {
                top: 0;
                right: 0;
            }

            body.preview-mode .page-title[data-hotspot]::after,
            body.preview-mode .page-description[data-hotspot]::after {
                top: 0;
                right: -24px;
            }

            /* Widget hotspot indicators - use same green styling as others */
            body.preview-mode .widget-wrapper[data-hotspot]::after {
                top: 8px;
                right: 8px;
            }

            body.preview-mode [data-hotspot-text]::after {
                top: 8px;
                left: 8px;
                right: auto;
            }

            body.preview-mode .podcast-top-banner[data-hotspot]::after,
            body.preview-mode .social-icons[data-hotspot]::after {
                top: 8px;
                right: 8px;
            }

            /* Hover effect for hotspots */
            /* Hover effect only on the dot, not the entire element */
            body.preview-mode [data-hotspot]::after:hover,
            body.preview-mode [data-hotspot-text]::after:hover {
                transform: scale(1.1);
            }

            @keyframes pulseGlow {

                0%,
                100% {
                    opacity: 1;
                    transform: scale(1);
                }

                50% {
                    opacity: 0.8;
                    transform: scale(1.1);
                }
            }
        </style>
    <?php endif; ?>

    <!-- Extracted CSS files (must load after ThemeCSSGenerator output) -->
    <link rel="stylesheet" href="/css/profile.css?v=<?php echo filemtime(__DIR__ . '/css/profile.css'); ?>">
    <link rel="stylesheet" href="/css/typography.css?v=<?php echo filemtime(__DIR__ . '/css/typography.css'); ?>">
    <link rel="stylesheet" href="/css/widgets.css?v=<?php echo filemtime(__DIR__ . '/css/widgets.css'); ?>">
    <link rel="stylesheet" href="/css/social-icons.css?v=<?php echo filemtime(__DIR__ . '/css/social-icons.css'); ?>">
    <link rel="stylesheet" href="/css/drawers.css?v=<?php echo filemtime(__DIR__ . '/css/drawers.css'); ?>">

    <!-- Additional inline styles (PHP logic - must remain inline) -->
    <style>
        :root {
            <?php
            // Check if this is a preview request with specific width
            $previewWidth = isset($_GET['preview_width']) ? (int) $_GET['preview_width'] : null;
            if ($previewWidth && $previewWidth > 0 && $previewWidth <= 1000) {
                // Preview mode: use exact device width
                $mobilePageWidth = $previewWidth . 'px';
            } else {
                // Normal mode: fully responsive, max-width 600px for optimal reading
                $mobilePageWidth = 'min(100vw, 600px)';
            }
            ?>
            --mobile-page-width:
                <?php echo $mobilePageWidth; ?>
            ;
            --mobile-page-offset: max(0px, calc((100vw - var(--mobile-page-width)) / 2));
            --episode-drawer-width: var(--mobile-page-width);
        }

        html,
        body {
            height: 100%;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .widget-section-container {
            /* Fallback to dark gray for visibility on light/dark mixtures if vars missing */
            background: var(--surface-color, rgba(127, 127, 127, 0.05));
            border-radius: 16px;
            /* Denser padding */
            padding: 12px;
            margin-bottom: 16px;
            /* Visible border */
            border: 1px solid var(--border-color, rgba(127, 127, 127, 0.15));
            display: flex;
            flex-direction: column;
            /* Denser gap between items */
            gap: 8px;
        }

        .widget-section-header {
            margin: 0 0 4px 0;
            padding-bottom: 8px;
            /* border-bottom removed */
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary, rgba(127, 127, 127, 0.8));
            font-weight: 600;
            font-family: var(--font-secondary, sans-serif);
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        body {
            margin: 0;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Single column layout for all screen sizes */
        .page-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: none;
            /* No border directly on container */
            padding: var(--page-padding-vertical, 1rem) var(--page-padding-horizontal, 16px);
            box-sizing: border-box;
            position: relative;
            /* Critical for confining absolute-positioned drawers */
        }

        <?php if ($previewWidth): ?>
            /* Preview mode: use exact device width */
            .page-container {
                max-width:
                    <?php echo $previewWidth; ?>
                    px;
            }

        <?php endif; ?>

        /* Add top padding when podcast banner is present (Desktop Only override happens later) */
        <?php if ($showPodcastPlayer): ?>
            body:has(.podcast-top-banner) .page-container {
                padding-top: calc(var(--page-padding, 1rem) + 60px);
            }

        <?php endif; ?>

        /* Desktop: iPhone Frame Layout (matching page-preview.php) */
        @media (min-width: 1024px) {

            /* Use darker version of page background for desktop */
            body {
                background: var(--page-background-dark, var(--page-background)) !important;
            }

            .desktop-frame-container {
                display: flex !important;
                flex-direction: row !important;
                align-items: flex-start !important;
                justify-content: center !important;
                gap: 4rem !important;
                padding: 3rem 2rem !important;
                min-height: 100vh !important;
                width: 100% !important;
                box-sizing: border-box !important;
                background: var(--page-background-dark, var(--page-background)) !important;
            }

            /* Hide normal page container on desktop */
            .mobile-page-container {
                display: none !important;
            }

            /* iPhone 16 Frame Styles */
            .iphone-frame {
                width: 393px !important;
                height: 852px !important;
                background: #1a1a1a !important;
                border-radius: 55px !important;
                padding: 8px !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4),
                    0 0 0 2px rgba(255, 255, 255, 0.1) inset,
                    0 0 0 1px rgba(0, 0, 0, 0.5) !important;
                position: relative !important;
                flex-shrink: 0 !important;
                display: block !important;
                visibility: visible !important;
            }

            .iphone-screen {
                width: 100% !important;
                height: 100% !important;
                background: var(--page-screen-background, var(--color-background-base, #ffffff)) !important;
                border-radius: 47px !important;
                overflow: hidden !important;
                position: relative !important;
                display: flex !important;
                flex-direction: column !important;
            }

            /* Background Image inside iPhone Frame */
            .iphone-background-image {
                position: absolute;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                opacity: var(--page-background-image-active, 0);
            }

            .iphone-background-image::before {
                content: '';
                position: absolute;
                inset: 0;
                background-image: var(--page-background-image-url);
                background-size: cover;
                background-position: var(--page-background-image-focal-x, 50%) var(--page-background-image-focal-y, 50%);
                transform: scale(var(--page-background-image-scale, 1));
                filter: blur(var(--page-background-image-blur, 0px));
                transform-origin: var(--page-background-image-focal-x, 50%) var(--page-background-image-focal-y, 50%);
            }

            .iphone-background-image::after {
                content: '';
                position: absolute;
                inset: 0;
                background-color: var(--page-background-image-overlay, rgba(0, 0, 0, 0.4));
            }

            .iphone-content {
                flex: 1 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                position: relative !important;
                z-index: 2 !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .iphone-content::-webkit-scrollbar {
                display: none !important;
            }

            .iphone-content {
                -ms-overflow-style: none !important;
                scrollbar-width: none !important;
            }

            /* Page content inside iPhone frame */
            .iphone-content .page-container {
                max-width: 100%;
                padding: 0.25rem 1rem 1rem 1rem;
                background: transparent;
                min-height: auto;
            }

            /* Reduce top margin on profile header in iPhone frame */
            .iphone-content .profile-header {
                margin-top: 0;
                padding-top: 0;
            }

            /* Add more space under social icons */
            .iphone-content .social-icons {
                margin-bottom: 2.5rem !important;
            }

            /* Podcast player frame */
            .iphone-podcast-frame .iphone-content {
                padding: 0;
                background: #000000;
            }

            /* Desktop Left Column Customization */
            /* 1. Hide Podcast Banner */
            .desktop-frame-container .iphone-frame:not(.iphone-podcast-frame) .podcast-top-banner {
                display: none !important;
            }

            /* 2. Read-Only Widgets (Disable clicks, allow scrolling) */
            .desktop-frame-container .iphone-frame:not(.iphone-podcast-frame) .page-container {
                pointer-events: none !important;
                cursor: default !important;
            }

            /* Podcast Buttons Container */
            .podcast-buttons-container {
                width: 294.75px;
                /* 25% narrower than iPhone frame (393px * 0.75) */
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .podcast-buttons-wrapper {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 0;
            }

            .podcast-platform-button {
                display: flex;
                align-items: center;
                gap: 1rem;
                width: 100%;
                padding: 1rem 1.25rem;
                background: #000000;
                color: #ffffff;
                text-decoration: none;
                border-radius: 12px;
                transition: all 0.2s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }

            .podcast-platform-button:hover {
                background: #1a1a1a;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            }

            .podcast-button-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                flex-shrink: 0;
            }

            .podcast-button-icon svg,
            .podcast-button-icon i {
                width: 24px;
                height: 24px;
                font-size: 24px;
            }

            /* Platform-specific icon colors */
            .podcast-platform-button[data-platform="apple_podcasts"] .podcast-button-icon {
                color: #9933CC;
            }

            .podcast-platform-button[data-platform="spotify"] .podcast-button-icon {
                color: #1DB954;
            }

            .podcast-platform-button[data-platform="youtube"],
            .podcast-platform-button[data-platform="youtube_music"] .podcast-button-icon {
                color: #FF0000;
            }

            .podcast-platform-button[data-platform="google_podcasts"] .podcast-button-icon {
                color: #4285F4;
            }

            .podcast-platform-button[data-platform="pocket_casts"] .podcast-button-icon {
                color: #F43E37;
            }

            .podcast-platform-button[data-platform="overcast"] .podcast-button-icon {
                color: #FC7E0F;
            }

            .podcast-platform-button[data-platform="castro"] .podcast-button-icon {
                color: #00B265;
            }

            .podcast-platform-button[data-platform="amazon_music"] .podcast-button-icon {
                color: #146EB4;
            }

            .podcast-platform-button[data-platform="iheart_radio"] .podcast-button-icon {
                color: #C6002B;
            }

            .podcast-platform-button[data-platform="pandora"] .podcast-button-icon {
                color: #224099;
            }

            .podcast-platform-button[data-platform="rss"] .podcast-button-icon {
                color: #FFA500;
            }

            .podcast-button-text {
                flex: 1;
                font-size: 0.95rem;
                font-weight: 700;
                letter-spacing: -0.01em;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .podcast-platform-button {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-weight: 700;
            }
        }

        /* Tablet: Hide desktop frame container and buttons, use darker background */
        @media (min-width: 768px) and (max-width: 1023px) {
            /* body background removed to allow image to show through (standard mobile behavior) */

            .desktop-frame-container {
                display: none !important;
            }

            .mobile-page-container {
                display: block !important;
            }

            .podcast-buttons-container {
                display: none !important;
            }
        }



        /* Mobile: Normal single column layout */
        @media (max-width: 767px) {
            .desktop-frame-container {
                display: none !important;
            }

            .mobile-page-container {
                display: block !important;
            }
        }

        /* CRITICAL: Force Gallery Grid Styles Inline to prevent loading issues */
        /* CRITICAL: Force Gallery Grid Styles Inline to prevent loading issues */
        .gallery-drawer .gallery-grid,
        .gallery-grid {
            display: grid !important;
            gap: 2px !important;
            width: 100% !important;
            /* Default to 3 columns if specific class fails */
            grid-template-columns: repeat(3, 1fr) !important;
        }

        .gallery-grid-2 {
            grid-template-columns: repeat(2, 1fr) !important;
        }

        .gallery-grid-3 {
            grid-template-columns: repeat(3, 1fr) !important;
        }

        .gallery-grid-4 {
            grid-template-columns: repeat(4, 1fr) !important;
        }

        .gallery-grid-item {
            aspect-ratio: 1 !important;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            background: #f1f5f9;
            width: 100% !important;
            height: auto !important;
            display: block !important;
        }

        .gallery-grid-image {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            transition: transform 0.3s ease;
            display: block !important;
        }
    </style>
</head>

<body
    class="<?php echo trim($cssGenerator->getSpatialEffectClass() . ' ' . $themeBodyClass . ' layout-' . ($page['layout_option'] ?: 'standard') . ($enablePreviewMode ? ' preview-mode' : '')); ?>">

    <!-- Page Background Image (if active) -->
    <div id="page-background-image"></div>
    <style>
        #page-background-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            /* Behind everything */
            pointer-events: none;
            opacity: var(--page-background-image-active, 0);
            /* Control visibility via variable */
            transition: opacity 0.3s ease;
        }

        #page-background-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: var(--page-background-image-url);
            background-size: cover;
            background-position: var(--page-background-image-focal-x, 50%) var(--page-background-image-focal-y, 50%);
            transform: scale(var(--page-background-image-scale, 1));
            filter: blur(var(--page-background-image-blur, 0px));
            transform-origin: var(--page-background-image-focal-x, 50%) var(--page-background-image-focal-y, 50%);
            z-index: 1;
        }

        #page-background-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: var(--page-background-image-overlay, rgba(0, 0, 0, 0.4));
            z-index: 2;
        }
    </style>

    <!-- Mobile: Normal single column layout -->
    <div class="mobile-page-container">
        <div class="page-container">
            <?php
            $idSuffix = '-mobile';
            require __DIR__ . '/templates/page-content.php';
            ?>
        </div>
    </div>

    <!-- Desktop/Tablet: iPhone Frame Layout -->
    <div class="desktop-frame-container">
        <!-- Left iPhone: Page Content -->
        <div class="iphone-frame">
            <div class="iphone-screen">
                <div class="iphone-background-image"></div>
                <div class="iphone-content">
                    <div class="page-container">
                        <?php
                        $idSuffix = '-desktop';
                        // Prevent duplicate player ID on desktop (it's rendered in the right frame)
                        $tempShowPlayer = $showPodcastPlayer;
                        $showPodcastPlayer = false;
                        require __DIR__ . '/templates/page-content.php';
                        $showPodcastPlayer = $tempShowPlayer;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right iPhone: Podcast Player (only if podcast is enabled) -->
        <?php if ($showPodcastPlayer): ?>
            <div class="iphone-frame iphone-podcast-frame">
                <div class="iphone-screen">
                    <div class="iphone-content">
                        <?php
                        $idSuffix = '-desktop';
                        $drawerStyles = 'position: relative; transform: none; height: 100%; display: flex; flex-direction: column; background: #000;';
                        $showCloseButton = false;
                        require __DIR__ . '/templates/podcast-player.php';
                        $idSuffix = '';
                        $drawerStyles = '';
                        $showCloseButton = true;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Right Side: Full-Sized Podcast Buttons (only if podcast is enabled) -->
        <?php if ($showPodcastPlayer && !empty($socialIcons)): ?>
            <div class="podcast-buttons-container">
                <div class="podcast-buttons-wrapper" style="padding: 1rem 0;">
                    <?php
                    // Filter for podcast platform icons
                    $podcastPlatforms = [
                        'apple_podcasts',
                        'spotify',
                        'google_podcasts',
                        'youtube',
                        'youtube_music',
                        'pocket_casts',
                        'overcast',
                        'castro',
                        'stitcher',
                        'amazon_music',
                        'iheart_radio',
                        'pandora',
                        'castbox',
                        'podcast_addict',
                        'rss'
                    ];

                    $podcastIcons = array_filter($socialIcons, function ($icon) use ($podcastPlatforms) {
                        $platformName = strtolower($icon['platform_name'] ?? '');
                        return in_array($platformName, $podcastPlatforms);
                    });

                    foreach ($podcastIcons as $icon):
                        $platformName = strtolower($icon['platform_name'] ?? '');
                        $iconUrl = $icon['url'] ?? '#';
                        $displayName = $icon['platform_name'] ?? 'Unknown';

                        // Map platform names to display names with proper capitalization and spelling
                        $displayNames = [
                            'apple_podcasts' => 'Apple Podcasts',
                            'spotify' => 'Spotify',
                            'google_podcasts' => 'Google Podcasts',
                            'youtube' => 'YouTube',
                            'youtube_music' => 'YouTube Music',
                            'pocket_casts' => 'Pocket Casts',
                            'overcast' => 'Overcast',
                            'castro' => 'Castro',
                            'stitcher' => 'Stitcher',
                            'amazon_music' => 'Amazon Music',
                            'iheart_radio' => 'iHeartRadio',
                            'pandora' => 'Pandora',
                            'castbox' => 'Castbox',
                            'podcast_addict' => 'Podcast Addict',
                            'rss' => 'RSS Feed'
                        ];
                        $displayName = $displayNames[$platformName] ?? ucwords(str_replace('_', ' ', $platformName));

                        // Get icon HTML (same logic as used elsewhere)
                        $iconHtml = '';
                        if ($platformName === 'pocket_casts') {
                            $iconHtml = '<svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" /><path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z"/></svg>';
                        } elseif ($platformName === 'castro') {
                            $iconHtml = '<svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z"/></svg>';
                        } elseif ($platformName === 'overcast') {
                            $iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" fill-rule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clip-rule="evenodd"/></svg>';
                        } elseif ($platformName === 'rss') {
                            $iconHtml = '<i class="fas fa-rss"></i>';
                        } else {
                            $platformIcons = [
                                'apple_podcasts' => '<i class="fas fa-podcast"></i>',
                                'spotify' => '<i class="fab fa-spotify"></i>',
                                'google_podcasts' => '<i class="fab fa-google"></i>',
                                'youtube' => '<i class="fab fa-youtube"></i>',
                                'youtube_music' => '<i class="fab fa-youtube"></i>',
                                'amazon_music' => '<i class="fab fa-amazon"></i>',
                                'iheart_radio' => '<i class="fas fa-heart"></i>',
                                'pandora' => '<i class="fab fa-pandora"></i>',
                                'castbox' => '<i class="fas fa-box"></i>',
                                'podcast_addict' => '<i class="fas fa-headphones"></i>',
                                'stitcher' => '<i class="fas fa-bars"></i>'
                            ];
                            $iconHtml = $platformIcons[$platformName] ?? '<i class="fas fa-link"></i>';
                        }
                        ?>
                        <a href="<?php echo h($iconUrl); ?>" target="_blank" rel="noopener noreferrer"
                            class="podcast-platform-button" data-platform="<?php echo h($platformName); ?>">
                            <span class="podcast-button-icon"><?php echo $iconHtml; ?></span>
                            <span
                                class="podcast-button-text"><?php echo $platformName === 'rss' ? 'Get the RSS Feed' : 'Listen on ' . h($displayName); ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if (!empty($page['rss_feed_url'])): ?>
                        <a href="<?php echo h($page['rss_feed_url']); ?>" target="_blank" rel="noopener noreferrer"
                            class="podcast-platform-button" data-platform="rss">
                            <span class="podcast-button-icon"><i class="fas fa-rss"></i></span>
                            <span class="podcast-button-text">Get the RSS Feed</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Email Subscription Drawer -->
    <?php if (!empty($page['email_service_provider'])): ?>
        <div class="drawer-overlay" id="email-overlay" onclick="closeEmailDrawer()"></div>
        <div class="episode-drawer" id="email-drawer">
            <div class="drawer-header">
                <h2 style="margin: 0; color: var(--color-text-primary);">Subscribe to Email List</h2>
                <button class="drawer-close" onclick="closeEmailDrawer()" aria-label="Close">×</button>
            </div>
            <div style="padding: 1rem 0;">
                <p style="margin-bottom: 1rem; color: var(--color-text-secondary);">Get notified about new episodes and
                    updates.</p>
                <form id="email-subscribe-form" onsubmit="subscribeEmail(event)">
                    <div class="form-group">
                        <label for="subscribe-email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email
                            Address</label>
                        <input type="email" id="subscribe-email" name="email" required placeholder="your@email.com"
                            style="width: 100%; padding: 0.75rem; border: 2px solid var(--color-accent-primary); border-radius: 8px; font-size: 1rem; box-sizing: border-box;">
                    </div>
                    <button type="submit" class="widget-item" <?php if ($enablePreviewMode): ?>data-hotspot="widget-settings" <?php endif; ?>
                        style="margin-top: 1rem; width: 100%; text-align: center; cursor: pointer;">
                        Subscribe
                    </button>
                </form>
                <div id="subscribe-message" style="margin-top: 1rem; display: none;"></div>
            </div>
        </div>
        <script>         window.emailSubscripti onPageId = <?php echo $page['id']; ?>;
        </script>
        <script src="/js/email-subscription.js?v=<?php echo filemtime(__DIR__ . '/js/email-subscription.js'); ?>"></script>
    <?php endif; ?>

    <!-- Featured Widget Effects -->
    <script
        src="/js/featured-widget-effects.js?v=<?php echo filemtime(__DIR__ . '/js/featured-widget-effects.js'); ?>"></script>

    <!-- Spatial Tilt Effect -->
    <script src="/js/spatial-tilt.js?v=<?php echo filemtime(__DIR__ . '/js/spatial-tilt.js'); ?>"></script>

    <!-- Widget Marquee -->
    <script src="/js/widget-marquee.js?v=<?php echo filemtime(__DIR__ . '/js/widget-marquee.js'); ?>"></script>

    <!-- Podcast Player JavaScript -->
    <?php if ($showPodcastPlayer): ?>
        <script src="/js/podcast-player-utils.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-player-utils.js'); ?>"></script>
        <script src="/js/podcast-player-rss-parser.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-player-rss-parser.js'); ?>"></script>
        <script src="/js/podcast-player-audio.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-player-audio.js'); ?>"></script>
        <script src="/js/podcast-player-app.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-player-app.js'); ?>"></script>
        <script>
            window.podcastConfig = {
                rssFeedUrl: <?php echo json_encode($page['rss_feed_url'] ?? ''); ?>,
                savedCoverImage: <?php echo json_encode($page['cover_image_url'] ?? ''); ?>,
                socialIcons: <?php echo json_encode($socialIcons ?? []); ?>
            };
        </script>
        <script
            src="/js/podcast-drawer-init.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-drawer-init.js'); ?>"></script>

        <!-- Desktop Podcast Player Initialization -->
        <script>
            (function () {
                // Shared config generator
                function getPodcastConfig() {
                    if (!window.podcastConfig) return null;

                    // Extract platform links from social icons
                    const socialIcons = window.podcastConfig.socialIcons || [];
                    const platformLinks = {
                        apple: null,
                        spotify: null,
                        google: null
                    };
                    socialIcons.forEach(icon => {
                        const platformName = (icon.platform_name || '').toLowerCase();
                        const url = icon.url || '';
                        if (platformName === 'apple_podcasts' && url) {
                            platformLinks.apple = url;
                        } else if (platformName === 'spotify' && url) {
                            platformLinks.spotify = url;
                        } else if (platformName === 'google_podcasts' && url) {
                            platformLinks.google = url;
                        }
                    });

                    return {
                        rssFeedUrl: window.podcastConfig.rssFeedUrl,
                        rssProxyUrl: '/api/rss-proxy.php',
                        imageProxyUrl: '/api/podcast-image-proxy.php',
                        savedCoverImage: window.podcastConfig.savedCoverImage || '',
                        platformLinks: platformLinks,
                        reviewLinks: {
                            apple: null,
                            spotify: null,
                            google: null
                        },
                        socialIcons: socialIcons,
                        cacheTTL: 3600000
                    };
                }

                function initDesktopPlayer() {
                    const config = getPodcastConfig();
                    if (!config || typeof PodcastPlayerApp === 'undefined') return;

                    const drawer = document.getElementById('podcast-top-drawer-desktop');
                    if (!drawer) return; // Not desktop view

                    try {
                        // Pass '-desktop' suffix so the app looks for the correct element IDs
                        // e.g. 'episodes-list-desktop' instead of looking for 'episodes-list'
                        window.podcastPlayerAppDesktop = new PodcastPlayerApp(config, drawer, '-desktop');
                        console.log('Desktop podcast player initialized successfully');
                        if (config.rssFeedUrl) {
                            window.podcastPlayerAppDesktop.loadFeed().catch(console.error);
                        }
                    } catch (error) {
                        console.error('Failed to initialize desktop podcast player:', error);
                    }
                }

                function initMobilePlayer() {
                    const config = getPodcastConfig();
                    if (!config || typeof PodcastPlayerApp === 'undefined') return;

                    const drawerMobile = document.getElementById('podcast-top-drawer-mobile');
                    if (!drawerMobile) return; // Not mobile view or element missing

                    try {
                        // For mobile, we pass the suffix '-mobile' because elements kept their suffix
                        window.podcastPlayerAppMobile = new PodcastPlayerApp(config, drawerMobile, '-mobile');
                        console.log('Mobile podcast player initialized successfully');
                        if (config.rssFeedUrl) {
                            window.podcastPlayerAppMobile.loadFeed().catch(console.error);
                        }
                    } catch (error) {
                        console.error('Failed to initialize mobile podcast player:', error);
                    }
                }

                function init() {
                    if (typeof PodcastPlayerApp === 'undefined') {
                        console.error('PodcastPlayerApp is not defined');
                        return;
                    }
                    initDesktopPlayer();
                    initMobilePlayer();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    <?php endif; ?>

    <!-- QR Code Morphing Animation Script -->
    <script>
        (function () {
            // Apply QR code sizing - always square with consistent padding
            function adjustQRCodeSize(container) {
                const profileImage = container.querySelector('.profile-image');
                const qrCode = container.querySelector('.profile-qr-code');

                if (!profileImage || !qrCode) return;

                // Get computed styles
                const profileStyles = window.getComputedStyle(profileImage);
                const size = parseFloat(profileStyles.width) || parseFloat(profileStyles.height) || 120;

                // Get border width as a number (not including 'px')
                const borderWidth = parseFloat(profileStyles.borderWidth) || 0;

                // Ensure QR code container matches profile image size exactly
                qrCode.style.width = profileStyles.width;
                qrCode.style.height = profileStyles.height;

                // Copy border properties exactly from profile image - but only if border exists
                if (borderWidth > 0) {
                    const borderColor = profileStyles.borderColor || 'transparent';
                    const borderStyle = profileStyles.borderStyle || 'solid';
                    qrCode.style.border = borderWidth + 'px ' + borderStyle + ' ' + borderColor;
                } else {
                    qrCode.style.border = 'none';
                }

                // QR code is always square - no border-radius
                qrCode.style.borderRadius = '0';

                // Copy shadow to match profile image container
                qrCode.style.boxShadow = profileStyles.boxShadow;

                // Ensure the image is clipped
                qrCode.style.overflow = 'hidden';

                // Always use consistent padding for square QR code (12% of size)
                const padding = size * 0.12;
                qrCode.style.padding = padding + 'px';
            }

            // Profile image to QR code morphing animation
            // Track initialization state and listener references to prevent memory leaks
            let qrMorphingInitialized = false;
            let resizeListener = null;
            let clickListener = null;

            function initQRCodeMorphing() {
                // Prevent duplicate initialization
                if (qrMorphingInitialized) {
                    return;
                }

                const containers = document.querySelectorAll('.profile-image-container');
                const containerStates = new Map(); // Track state for each container

                // Single debounced resize listener for all containers
                let resizeTimeout;
                resizeListener = function () {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function () {
                        containers.forEach(function (container) {
                            adjustQRCodeSize(container);
                        });
                    }, 100);
                };
                window.addEventListener('resize', resizeListener);

                // Single document click listener for closing QR codes
                clickListener = function (e) {
                    containers.forEach(function (container) {
                        const state = containerStates.get(container);
                        if (state && state.isShowingQR && !container.contains(e.target)) {
                            container.classList.remove('show-qr', 'fade-mode');
                            state.isShowingQR = false;
                        }
                    });
                };
                document.addEventListener('click', clickListener);

                // Mark as initialized
                qrMorphingInitialized = true;

                containers.forEach(function (container) {
                    const profileImage = container.querySelector('.profile-image');
                    const qrCode = container.querySelector('.profile-qr-code');

                    if (!profileImage || !qrCode) return;

                    // Get existing state or create new one
                    let state = containerStates.get(container);
                    if (state) {
                        // Remove existing listeners if re-initializing
                        if (state.clickListener) {
                            container.removeEventListener('click', state.clickListener);
                        }
                        if (state.touchStartListener) {
                            container.removeEventListener('touchstart', state.touchStartListener);
                        }
                        if (state.touchEndListener) {
                            container.removeEventListener('touchend', state.touchEndListener);
                        }
                    } else {
                        // Initialize new container state
                        state = {
                            isShowingQR: false,
                            touchStartTime: 0,
                            clickListener: null,
                            touchStartListener: null,
                            touchEndListener: null
                        };
                        containerStates.set(container, state);
                    }

                    // Adjust QR code size based on profile image shape
                    adjustQRCodeSize(container);

                    // Preload QR code image
                    const qrImg = new Image();
                    qrImg.onload = function () {
                        qrCode.classList.add('loaded');
                    };
                    qrImg.src = qrCode.src;

                    function toggleQR() {
                        state.isShowingQR = !state.isShowingQR;
                        if (state.isShowingQR) {
                            container.classList.add('show-qr', 'fade-mode');
                        } else {
                            container.classList.remove('show-qr', 'fade-mode');
                        }
                    }

                    // Click handler - store reference for cleanup
                    state.clickListener = function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleQR();
                    };
                    container.addEventListener('click', state.clickListener);

                    // Touch handler for mobile - store references for cleanup
                    state.touchStartListener = function (e) {
                        state.touchStartTime = Date.now();
                    };
                    container.addEventListener('touchstart', state.touchStartListener, { passive: true });

                    state.touchEndListener = function (e) {
                        const touchDuration = Date.now() - state.touchStartTime;
                        // Only trigger if it was a quick tap (less than 300ms)
                        if (touchDuration < 300) {
                            e.preventDefault();
                            e.stopPropagation();
                            toggleQR();
                        }
                    };
                    container.addEventListener('touchend', state.touchEndListener);
                });
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initQRCodeMorphing);
            } else {
                initQRCodeMorphing();
            }
        })();
    </script>

    <?php if ($enablePreviewMode): ?>
        <script>
            (function () {
                // Only run if in iframe and preview mode is enabled
                if (window.parent === window) {
                    return; // Not in iframe
                }

                console.log('Page.php: Preview mode click handler initialized');

                // Single catch-all capture listener for ALL clicks
                document.addEventListener('click', function (e) {
                    // 1. Traverse up to find if we clicked a hotspot
                    let target = e.target;
                    let hotspot = null;

                    while (target && target !== document) {
                        if (target.hasAttribute && (target.hasAttribute('data-hotspot') || target.hasAttribute('data-hotspot-text'))) {
                            hotspot = target;
                            break;
                        }
                        target = target.parentElement;
                    }

                    // 2. If it IS a hotspot, send message and kill event
                    if (hotspot) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation(); // Kill other listeners

                        const sectionId = hotspot.getAttribute('data-hotspot') || hotspot.getAttribute('data-hotspot-text');
                        const widgetId = hotspot.getAttribute('data-widget-id');

                        // Get absolute viewport coordinates for the context menu
                        // We use the center of the element for consistent menu placement
                        const rect = hotspot.getBoundingClientRect();
                        const x = rect.left + (rect.width / 2);
                        const y = rect.top + (rect.height / 2);

                        console.log('Page.php: Hotspot clicked', {
                            sectionId,
                            widgetId,
                            x,
                            y
                        });

                        window.parent.postMessage({
                            type: 'hotspot-click',
                            sectionId: sectionId,
                            widgetId: widgetId,
                            x: x,
                            y: y
                        }, '*');

                        return false;
                    }

                    // 3. If NOT a hotspot, block interactive elements (links, buttons)
                    // We want to prevent navigation in preview mode
                    target = e.target;
                    let isInteractive = false;

                    while (target && target !== document) {
                        if (target.tagName === 'A' || target.tagName === 'BUTTON' || target.tagName === 'INPUT' || (target.hasAttribute && target.hasAttribute('onclick'))) {
                            isInteractive = true;
                            break;
                        }
                        target = target.parentElement;
                    }

                    if (isInteractive) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Page.php: Interactive click blocked in preview');
                    }

                    // Allow clicks on non-interactive elements (like selecting text)
                }, true); // Capture phase is CRITICAL to intercept before other listeners
            })();
        </script>
    <?php endif; ?>
    <!-- Widget Scripts -->
    <script src="js/contact-form.js"></script>
    <script src="js/email-subscription.js"></script>
    <script src="js/embed-code.js"></script>
    <!-- PhotoSwipe Root Element (Moved inside structure for confinement) -->
    <!-- Ideally this should be inside .page-container if we want it confined to the "phone" -->
    <!-- But standard PhotoSwipe expects body. We will use CSS overrides to confine it. -->
    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true"
        style="position: absolute !important; height: 100% !important;">
        <div class="pswp__bg"></div>
        <div class="pswp__scroll-wrap">
            <div class="pswp__container">
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
            </div>
            <div class="pswp__ui pswp__ui--hidden">
                <div class="pswp__top-bar">
                    <div class="pswp__counter"></div>
                    <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
                    <button class="pswp__button pswp__button--share" title="Share"></button>
                    <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                    <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
                    <div class="pswp__preloader">
                        <div class="pswp__preloader__icn">
                            <div class="pswp__preloader__cut">
                                <div class="pswp__preloader__donut"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                    <div class="pswp__share-tooltip"></div>
                </div>
                <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>
                <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>
                <div class="pswp__caption">
                    <div class="pswp__caption__center"></div>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- Close .page-container -->

    <!-- JavaScript Includes -->
</body>

</html>