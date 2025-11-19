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

// Check if request is for custom domain or username
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
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $requestUri) && 
        !file_exists(__DIR__ . $requestUri) && 
        !is_dir(__DIR__ . '/' . $requestUri)) {
        $username = $requestUri;
    }
}

$pageClass = new Page();
$page = null;

// Define main domains (including localhost for development)
$mainDomains = ['getphily.com', 'www.getphily.com', 'poda.bio', 'www.poda.bio', 'localhost', '127.0.0.1'];

// First check if this is a custom domain (not our main domains)
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

// Track page view
$analytics = new Analytics();
$analytics->trackView($page['id']);

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page['podcast_name'] ?: $page['username']); ?> - <?php echo h(APP_NAME); ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%230066ff'/><text x='50' y='70' font-size='60' font-weight='bold' text-anchor='middle' fill='white' font-family='Arial, sans-serif'>P</text></svg>">
    <link rel="alternate icon" href="/favicon.php">
    
    <!-- Google Fonts -->
    <?php
    // Build Google Fonts URL using Theme class
    $fontUrl = $themeClass->buildGoogleFontsUrl($fonts);
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo h($fontUrl); ?>" rel="stylesheet">
    <!-- Additional fonts for page name effects -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;700;900&family=Bowlby+One+SC&family=Poppins:wght@400;600;700&family=Raleway:wght@900&family=Oswald:wght@400;600;700;900&family=Montserrat:wght@900&family=Bungee:wght@400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Podcast Player Styles (only load if RSS feed exists and player is enabled) -->
    <?php 
    $podcastPlayerEnabled = isset($page['podcast_player_enabled']) ? (bool)$page['podcast_player_enabled'] : false;
    $hasRssFeed = !empty($page['rss_feed_url']);
    $showPodcastPlayer = $podcastPlayerEnabled && $hasRssFeed;
    ?>
    <?php if ($showPodcastPlayer): ?>
        <link rel="stylesheet" href="/css/podcast-player.css?v=<?php echo filemtime(__DIR__ . '/css/podcast-player.css'); ?>&_nocache=<?php echo time(); ?>">
    <?php endif; ?>
    
    <!-- Special Effects CSS (extracted for better caching) -->
    <link rel="stylesheet" href="/css/special-effects.css?v=<?php echo filemtime(__DIR__ . '/css/special-effects.css'); ?>">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo h(truncate($page['podcast_description'] ?: 'Link in bio page', 160)); ?>">
    <meta property="og:title" content="<?php echo h($page['podcast_name'] ?: $page['username']); ?>">
    <meta property="og:description" content="<?php echo h(truncate($page['podcast_description'] ?: '', 160)); ?>">
    <meta property="og:image" content="<?php echo h(normalizeImageUrl($page['profile_image'] ?: $page['cover_image_url'] ?: '')); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <?php echo $cssGenerator->generateCompleteStyleBlock(); ?>
    
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
            $previewWidth = isset($_GET['preview_width']) ? (int)$_GET['preview_width'] : null;
            if ($previewWidth && $previewWidth > 0 && $previewWidth <= 1000) {
                // Preview mode: use exact device width
                $mobilePageWidth = $previewWidth . 'px';
            } else {
                // Normal mode: responsive with max width
                $mobilePageWidth = 'min(100vw, 420px)';
            }
            ?>
            --mobile-page-width: <?php echo $mobilePageWidth; ?>;
            --mobile-page-offset: max(0px, calc((100vw - var(--mobile-page-width)) / 2));
            --episode-drawer-width: var(--mobile-page-width);
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
            /* Hide scrollbars but keep scrolling */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        /* REMOVED: body background style - now handled directly in ThemeCSSGenerator with !important */
        /* This prevents any conflicts or CSS variable resolution issues */
        body {
            margin: 0;
            min-height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        /* Mobile-only: No responsive breakpoints - page is always mobile */

        /* Page container and layout - Mobile-only */
        .page-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: var(--mobile-page-width);
            <?php if ($previewWidth): ?>
            max-width: <?php echo $previewWidth; ?>px;
            <?php else: ?>
            max-width: 420px;
            <?php endif; ?>
            margin: 0 auto;
            padding: var(--page-padding, 1rem);
            box-sizing: border-box;
        }
        
        /* Add top padding when podcast banner is present to prevent content from being hidden */
        <?php if ($showPodcastPlayer): ?>
        body:has(.podcast-top-banner) .page-container {
            padding-top: calc(var(--page-padding, 1rem) + 60px); /* page padding + banner height (approx 60px) */
        }
        <?php endif; ?>
        
        /* Profile styles - MOVED TO /css/profile.css */
        /* Typography styles - MOVED TO /css/typography.css */
        /* Widget container styles - MOVED TO /css/widgets.css */
        /* Social icons styles - MOVED TO /css/social-icons.css */
        /* Drawer styles - MOVED TO /css/drawers.css */
        /* Podcast Player CSS - MOVED TO /css/podcast-player.css */
        /* Special Effects CSS - MOVED TO /css/special-effects.css */
        
        /* Mobile-only styles - always applied */
        /* Profile image size is controlled by .profile-image-size-* classes */

        /* REMOVED: All widget-specific styling - now handled by ThemeCSSGenerator */
    </style>
</head>
<body class="<?php echo trim($cssGenerator->getSpatialEffectClass() . ' ' . $themeBodyClass); ?>">
    <div class="page-container">
        <?php if (!isset($page['profile_visible']) || $page['profile_visible']): ?>
        <div class="profile-header">
            <?php if ($page['profile_image']): 
                $imageShape = $page['profile_image_shape'] ?? 'circle';
                $imageShadow = $page['profile_image_shadow'] ?? 'subtle';
                $imageSize = $page['profile_image_size'] ?? 'medium';
                $imageBorder = $page['profile_image_border'] ?? 'none';
                $shapeClass = 'profile-image-shape-' . $imageShape;
                $shadowClass = 'profile-image-shadow-' . $imageShadow;
                $sizeClass = 'profile-image-size-' . $imageSize;
                $borderClass = 'profile-image-border-' . $imageBorder;
            ?>
                <img src="<?php echo h(normalizeImageUrl($page['profile_image'])); ?>" alt="Profile" class="profile-image <?php echo h($shapeClass . ' ' . $shadowClass . ' ' . $sizeClass . ' ' . $borderClass); ?>" onerror="this.onerror=null; this.style.display='none';">
            <?php endif; ?>
            
            <?php if ($page['podcast_name']): 
                $nameAlignment = $page['name_alignment'] ?? 'center';
                $nameTextSize = $page['name_text_size'] ?? 'large';
                $alignmentStyle = 'text-align: ' . h($nameAlignment) . ';';
                $sizeClass = 'name-size-' . h($nameTextSize);
                
                // Apply page name effect class if set
                $effectClass = '';
                if (!empty($page['page_name_effect'])) {
                    $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
                }
                
                // Allow safe HTML tags (strong, em, u, br) but sanitize the rest
                $nameContent = $page['podcast_name'];
                // First convert newlines to <br>, then strip unwanted tags
                $nameContent = nl2br($nameContent);
                $nameContent = strip_tags($nameContent, '<strong><em><u><br>');
            ?>
                <h1 class="page-title <?php echo trim($sizeClass . ' ' . $effectClass); ?>" style="<?php echo $alignmentStyle; ?>"><?php echo $nameContent; ?></h1>
            <?php elseif ($page['username']): 
                // Apply page name effect class if set
                $effectClass = '';
                if (!empty($page['page_name_effect'])) {
                    $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
                }
            ?>
                <h1 class="page-title <?php echo $effectClass; ?>"><?php echo h($page['username']); ?></h1>
            <?php endif; ?>
            
            <?php if ($page['podcast_description']): 
                $bioAlignment = $page['bio_alignment'] ?? 'center';
                $bioTextSize = $page['bio_text_size'] ?? 'medium';
                $alignmentStyle = 'text-align: ' . h($bioAlignment) . ';';
                $sizeClass = 'bio-size-' . h($bioTextSize);
                // Allow safe HTML tags (strong, em, u, br) but sanitize the rest
                $bioContent = $page['podcast_description'];
                // First convert newlines to <br>, then strip unwanted tags
                $bioContent = nl2br($bioContent);
                $bioContent = strip_tags($bioContent, '<strong><em><u><br>');
            ?>
                <p class="page-description <?php echo $sizeClass; ?>" style="<?php echo $alignmentStyle; ?>"><?php echo $bioContent; ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Podcast Player Top Banner (positioned independently, moves with drawer) -->
        <?php if ($showPodcastPlayer): ?>
            <div class="podcast-top-banner" id="podcast-top-banner">
                <button class="podcast-banner-toggle" id="podcast-drawer-toggle" aria-label="Open Podcast Player" title="Open Podcast Player">
                    <i class="fas fa-podcast"></i>
                    <span>Tap to Listen</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Podcast Player Top Drawer -->
        <?php if ($showPodcastPlayer): ?>
            <div class="podcast-top-drawer" id="podcast-top-drawer">
                
                <!-- Tab Navigation -->
                <nav class="tab-navigation" id="tab-navigation">
                    <button class="tab-button active" data-tab="now-playing" id="tab-now-playing">Now Playing</button>
                    <button class="tab-button" data-tab="follow" id="tab-follow">Follow</button>
                    <button class="tab-button" data-tab="details" id="tab-details">Details</button>
                    <button class="tab-button" data-tab="episodes" id="tab-episodes">Episodes</button>
                </nav>

                <!-- Tab Content Container -->
                <div class="tab-content-container" id="tab-content-container">
                    <!-- Now Playing Tab -->
                    <div class="tab-panel active" id="now-playing-panel">
                        <div class="now-playing-content">
                            <!-- Full Width Cover Artwork -->
                            <div class="episode-artwork-fullwidth" id="now-playing-artwork-container">
                                <?php if (!empty($page['cover_image_url'])): ?>
                                    <img class="episode-artwork-large" id="now-playing-artwork" src="<?php echo h(normalizeImageUrl($page['cover_image_url'])); ?>" alt="Podcast Cover">
                                    <div class="artwork-placeholder" id="artwork-placeholder" style="display: none;">
                                        <i class="fas fa-music"></i>
                                    </div>
                                <?php else: ?>
                                    <img class="episode-artwork-large" id="now-playing-artwork" src="" alt="Episode Artwork" style="display: none;">
                                    <div class="artwork-placeholder" id="artwork-placeholder">
                                        <i class="fas fa-music"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Progress Section (Below Artwork, Above Controls) -->
                            <div class="progress-section-large" id="progress-section-now-playing">
                                <div class="time-display">
                                    <span id="current-time-display">0:00</span>
                                    <span id="remaining-time-display">-0:00</span>
                                </div>
                                <div class="progress-bar-now-playing" id="progress-bar-now-playing">
                                    <div class="progress-fill-now-playing" id="progress-fill-now-playing"></div>
                                    <div class="progress-scrubber-now-playing" id="progress-scrubber-now-playing"></div>
                                </div>
                            </div>

                            <!-- Player Controls (Below Progress) -->
                            <div class="player-controls-section">
                                <!-- Primary Controls -->
                                <div class="primary-controls">
                                    <button class="control-button-large skip-back-large" id="skip-back-large" aria-label="Skip back 10 seconds">
                                        <span class="skip-label-large">10</span>
                                        <i class="fas fa-backward"></i>
                                    </button>
                                    <button class="control-button-large play-pause-large-now" id="play-pause-large-now" aria-label="Play/Pause">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button class="control-button-large skip-forward-large" id="skip-forward-large" aria-label="Skip forward 45 seconds">
                                        <span class="skip-label-large">45</span>
                                        <i class="fas fa-forward"></i>
                                    </button>
                                </div>

                                <!-- Secondary Controls Bar -->
                                <div class="secondary-controls-bar">
                                    <button class="secondary-control-btn speed-control-btn" id="speed-control-btn" aria-label="Playback Speed">
                                        <span id="speed-display">1x</span>
                                    </button>
                                    <button class="secondary-control-btn timer-control-btn" id="timer-control-btn" aria-label="Sleep Timer">
                                        <i class="fas fa-moon"></i>
                                        <span id="timer-display">Off</span>
                                    </button>
                                    <button class="secondary-control-btn share-control-btn" id="share-control-btn" aria-label="Share">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <button class="secondary-control-btn more-control-btn" id="more-control-btn" aria-label="More Options">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </div>

                                <!-- Speed Selector (Inline) -->
                                <div class="inline-speed-selector" id="inline-speed-selector" style="display: none;">
                                    <h3 class="inline-selector-title">Playback Speed</h3>
                                    <div class="speed-options-inline" id="speed-options-inline">
                                        <!-- Speed options will be inserted here -->
                                    </div>
                                </div>

                                <!-- Timer Selector (Inline) -->
                                <div class="inline-timer-selector" id="inline-timer-selector" style="display: none;">
                                    <h3 class="inline-selector-title">Sleep Timer</h3>
                                    <div class="timer-options-inline" id="timer-options-inline">
                                        <!-- Timer options will be inserted here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Follow Tab -->
                    <div class="tab-panel" id="follow-panel">
                        <div class="follow-tab-content">
                            <div id="follow-content">
                                <!-- Follow content will be inserted here -->
                            </div>
                        </div>
                    </div>

                    <!-- Details Tab -->
                    <div class="tab-panel" id="details-panel">
                        <div class="details-tab-content">
                            <!-- Show Notes Section -->
                            <div class="details-section-modern" id="shownotes-section">
                                <div class="section-header-modern">
                                    <i class="fas fa-file-alt section-icon"></i>
                                    <h2 class="section-title-modern">Show Notes</h2>
                                </div>
                                <div class="shownotes-content-modern" id="shownotes-content">
                                    <div class="empty-state-modern">
                                        <i class="fas fa-info-circle"></i>
                                        <p>No episode selected</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Chapters Section -->
                            <div class="details-section-modern" id="chapters-section">
                                <div class="section-header-modern">
                                    <i class="fas fa-list-ul section-icon"></i>
                                    <h2 class="section-title-modern">Chapters</h2>
                                </div>
                                <div class="chapters-list-modern" id="chapters-list">
                                    <div class="empty-state-modern">
                                        <i class="fas fa-list"></i>
                                        <p>No chapters available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Episodes Tab -->
                    <div class="tab-panel" id="episodes-panel">
                        <div class="episodes-tab-content">
                            <!-- Header with count -->
                            <div class="episodes-header" id="episodes-header" style="display: none;">
                                <h2 class="episodes-title">All Episodes</h2>
                                <span class="episodes-count" id="episodes-count"></span>
                            </div>

                            <!-- Loading Skeleton -->
                            <div class="loading-skeleton-modern" id="loading-skeleton">
                                <div class="skeleton-item-modern">
                                    <div class="skeleton-artwork"></div>
                                    <div class="skeleton-text">
                                        <div class="skeleton-line skeleton-line-title"></div>
                                        <div class="skeleton-line skeleton-line-meta"></div>
                                    </div>
                                </div>
                                <div class="skeleton-item-modern">
                                    <div class="skeleton-artwork"></div>
                                    <div class="skeleton-text">
                                        <div class="skeleton-line skeleton-line-title"></div>
                                        <div class="skeleton-line skeleton-line-meta"></div>
                                    </div>
                                </div>
                                <div class="skeleton-item-modern">
                                    <div class="skeleton-artwork"></div>
                                    <div class="skeleton-text">
                                        <div class="skeleton-line skeleton-line-title"></div>
                                        <div class="skeleton-line skeleton-line-meta"></div>
                                    </div>
                                </div>
                                <div class="skeleton-item-modern">
                                    <div class="skeleton-artwork"></div>
                                    <div class="skeleton-text">
                                        <div class="skeleton-line skeleton-line-title"></div>
                                        <div class="skeleton-line skeleton-line-meta"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Episodes List -->
                            <div class="episodes-list-modern" id="episodes-list" style="display: none;">
                                <!-- Episodes will be inserted here -->
                            </div>

                            <!-- Error State -->
                            <div class="error-state-modern" id="error-state" style="display: none;">
                                <div class="error-icon-wrapper">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h3 class="error-title">Failed to load episodes</h3>
                                <p class="error-message">Please check your connection and try again.</p>
                                <button class="retry-button-modern" id="retry-button">
                                    <i class="fas fa-redo"></i>
                                    <span>Retry</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toast Notification -->
                <div class="toast" id="toast" style="display: none;">
                    <span id="toast-message"></span>
                </div>

                <!-- Audio Element -->
                <audio id="podcast-audio-player" preload="metadata"></audio>

                <!-- Drawer Footer Close Button -->
                <div class="podcast-drawer-footer">
                    <button type="button" class="podcast-drawer-footer-button" id="podcast-drawer-close" aria-label="Close Podcast Player">
                        <i class="fas fa-chevron-up"></i>
                        <span>Close Player</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Social Icons -->
        <?php if (!empty($socialIcons)): ?>
            <div class="social-icons">
                <?php foreach ($socialIcons as $icon): ?>
                    <a href="<?php echo h($icon['url']); ?>" 
                       class="social-icon" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       title="<?php echo h($icon['platform_name']); ?>">
                        <?php
                        // Icon mapping for platforms (social media + podcast platforms)
                        // Most icons use Font Awesome, but some use custom SVG files
                        $platformName = strtolower($icon['platform_name']);
                        $iconHtml = '';
                        
                        // Custom SVG icons for podcast platforms (inline SVG for color control)
                        // Size matches Font Awesome icons: 1em (inherits from .social-icon font-size: 1.5rem)
                        if ($platformName === 'pocket_casts') {
                            $iconHtml = '<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display: block; width: 1em; height: 1em;"><circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" /><path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z"/></svg>';
                        } elseif ($platformName === 'castro') {
                            $iconHtml = '<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display: block; width: 1em; height: 1em;"><path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z"/></svg>';
                        } elseif ($platformName === 'overcast') {
                            $iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style="display: block; width: 1em; height: 1em;"><path fill="currentColor" fill-rule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clip-rule="evenodd"/></svg>';
                        } else {
                            // Font Awesome icons for other platforms
                            $platformIcons = [
                                // Podcast Platforms
                                'apple_podcasts' => '<i class="fas fa-podcast"></i>',
                                'spotify' => '<i class="fab fa-spotify"></i>',
                                'youtube_music' => '<i class="fab fa-youtube"></i>',
                                'iheart_radio' => '<i class="fas fa-heart"></i>',
                                'amazon_music' => '<i class="fab fa-amazon"></i>',
                                // Social Media Platforms
                                'facebook' => '<i class="fab fa-facebook"></i>',
                                'twitter' => '<i class="fab fa-twitter"></i>',
                                'instagram' => '<i class="fab fa-instagram"></i>',
                                'linkedin' => '<i class="fab fa-linkedin"></i>',
                                'youtube' => '<i class="fab fa-youtube"></i>',
                                'tiktok' => '<i class="fab fa-tiktok"></i>',
                                'snapchat' => '<i class="fab fa-snapchat"></i>',
                                'pinterest' => '<i class="fab fa-pinterest"></i>',
                                'reddit' => '<i class="fab fa-reddit"></i>',
                                'discord' => '<i class="fab fa-discord"></i>',
                                'twitch' => '<i class="fab fa-twitch"></i>',
                                'github' => '<i class="fab fa-github"></i>',
                                'behance' => '<i class="fab fa-behance"></i>',
                                'dribbble' => '<i class="fab fa-dribbble"></i>',
                                'medium' => '<i class="fab fa-medium"></i>',
                                'substack' => '<i class="fas fa-newspaper"></i>'
                            ];
                            $iconHtml = $platformIcons[$platformName] ?? '<i class="fas fa-link"></i>';
                        }
                        echo $iconHtml;
                        ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="widgets-container">
            <?php 
            // Render widgets using WidgetRenderer
            if (!empty($widgets)):
                foreach ($widgets as $widget): 
                    $widget['page_id'] = $page['id']; // Ensure page_id is set for renderer
                    $isFeatured = !empty($widget['is_featured']);
                    $featuredEffect = $widget['featured_effect'] ?? '';
                    
                    try {
                        $rendered = WidgetRenderer::render($widget, $page);
                        if (!empty($rendered)) {
                            // Wrap in featured container if featured
                            if ($isFeatured && $featuredEffect) {
                                echo '<div class="featured-widget featured-effect-' . h($featuredEffect) . '">';
                            }
                            echo $rendered;
                            if ($isFeatured && $featuredEffect) {
                                echo '</div>';
                            }
                        }
                    } catch (Exception $e) {
                        // Silently fail - don't break the page if one widget fails to render
                        echo '<!-- Widget render error: ' . htmlspecialchars($e->getMessage()) . ' -->';
                    }
                endforeach;
            // Fallback to legacy links if no widgets exist
            elseif (!empty($links)):
                foreach ($links as $link): ?>
                    <a href="/click.php?link_id=<?php echo $link['id']; ?>&page_id=<?php echo $page['id']; ?>" 
                       class="widget-item" 
                       target="_blank" 
                       rel="noopener noreferrer">
                        <?php if ($link['thumbnail_image']): ?>
                            <div class="widget-thumbnail-wrapper">
                                <img src="<?php echo h(normalizeImageUrl($link['thumbnail_image'])); ?>" 
                                     alt="<?php echo h($link['title']); ?>" 
                                     class="widget-thumbnail"
                                     onerror="this.onerror=null; this.style.display='none'; var wrapper=this.closest('.widget-thumbnail-wrapper'); if(wrapper){var fallback=wrapper.querySelector('.widget-thumbnail-fallback'); if(fallback)fallback.style.display='flex';}">
                                <div class="widget-thumbnail-fallback" style="display:none; width:100%; height:100%; background:rgba(0,0,0,0.05); border-radius:inherit; align-items:center; justify-content:center; color:rgba(0,0,0,0.3); font-size:1.5rem;">
                                    <i class="fas fa-link"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="widget-content">
                            <div class="widget-title"><?php echo h($link['title']); ?></div>
                        </div>
                    </a>
                <?php endforeach;
            endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php 
    $footerVisible = !isset($page['footer_visible']) || $page['footer_visible'];
    $hasFooterContent = !empty($page['footer_text']) || !empty($page['footer_copyright']) || !empty($page['footer_privacy_link']) || !empty($page['footer_terms_link']);
    
    // Show footer if visible and has content
    if ($footerVisible && $hasFooterContent): ?>
        <footer class="page-footer" style="margin-top: auto; padding: 1.5rem 1rem; text-align: center; border-top: 1px solid rgba(15, 23, 42, 0.1);">
            <?php if (!empty($page['footer_text'])): ?>
                <p style="margin: 0 0 1rem 0; color: var(--color-text-secondary, #6b7280); font-size: 0.9rem;"><?php echo nl2br(h($page['footer_text'])); ?></p>
            <?php endif; ?>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--color-text-secondary, #6b7280);">
                <?php if (!empty($page['footer_copyright'])): ?>
                    <span><?php echo h($page['footer_copyright']); ?></span>
                <?php endif; ?>
                <?php if (!empty($page['footer_privacy_link'])): ?>
                    <a href="<?php echo h($page['footer_privacy_link']); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-text-secondary, #6b7280); text-decoration: underline;">Privacy Policy</a>
                <?php endif; ?>
                <?php if (!empty($page['footer_terms_link'])): ?>
                    <a href="<?php echo h($page['footer_terms_link']); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-text-secondary, #6b7280); text-decoration: underline;">Terms of Service</a>
                <?php endif; ?>
            </div>
        </footer>
    <?php endif; ?>
    
    <!-- Email Subscription Drawer -->
    <?php if (!empty($page['email_service_provider'])): ?>
        <div class="drawer-overlay" id="email-overlay" onclick="closeEmailDrawer()"></div>
        <div class="episode-drawer" id="email-drawer">
            <div class="drawer-header">
                <h2 style="margin: 0; color: var(--color-text-primary);">Subscribe to Email List</h2>
                <button class="drawer-close" onclick="closeEmailDrawer()" aria-label="Close">×</button>
            </div>
            <div style="padding: 1rem 0;">
                <p style="margin-bottom: 1rem; color: var(--color-text-secondary);">Get notified about new episodes and updates.</p>
                <form id="email-subscribe-form" onsubmit="subscribeEmail(event)">
                    <div class="form-group">
                        <label for="subscribe-email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email Address</label>
                        <input type="email" 
                               id="subscribe-email" 
                               name="email" 
                               required 
                               placeholder="your@email.com"
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--color-accent-primary); border-radius: 8px; font-size: 1rem; box-sizing: border-box;">
                    </div>
                    <button type="submit" 
                            class="widget-item" 
                            style="margin-top: 1rem; width: 100%; text-align: center; cursor: pointer;">
                        Subscribe
                    </button>
                </form>
                <div id="subscribe-message" style="margin-top: 1rem; display: none;"></div>
            </div>
        </div>
        <script>
            // Pass page ID to email subscription script
            window.emailSubscriptionPageId = <?php echo $page['id']; ?>;
        </script>
        <script src="/js/email-subscription.js?v=<?php echo filemtime(__DIR__ . '/js/email-subscription.js'); ?>"></script>
    <?php endif; ?>
    
    <!-- Featured Widget Effects (extracted for better caching) -->
    <script src="/js/featured-widget-effects.js?v=<?php echo filemtime(__DIR__ . '/js/featured-widget-effects.js'); ?>"></script>
    
    <!-- Spatial Tilt Effect (extracted for better caching) -->
    <script src="/js/spatial-tilt.js?v=<?php echo filemtime(__DIR__ . '/js/spatial-tilt.js'); ?>"></script>
    
    <!-- Widget Marquee (extracted for better caching) -->
    <script src="/js/widget-marquee.js?v=<?php echo filemtime(__DIR__ . '/js/widget-marquee.js'); ?>"></script>
    
    <!-- Podcast Player JavaScript (only load if RSS feed exists and player is enabled) -->
    <?php if ($showPodcastPlayer): ?>
        <script src="/js/podcast-player-utils.js"></script>
        <script src="/js/podcast-player-rss-parser.js"></script>
        <script src="/js/podcast-player-audio.js"></script>
        <script src="/js/podcast-player-app.js"></script>
        <script>
            // Pass podcast config to drawer init script
            window.podcastConfig = {
                rssFeedUrl: <?php echo json_encode($page['rss_feed_url'] ?? ''); ?>,
                savedCoverImage: <?php echo json_encode($page['cover_image_url'] ?? ''); ?>,
                socialIcons: <?php echo json_encode($socialIcons ?? []); ?>
            };
        </script>
        <script src="/js/podcast-drawer-init.js?v=<?php echo filemtime(__DIR__ . '/js/podcast-drawer-init.js'); ?>"></script>
    <?php endif; ?>
    </body>
</html>

