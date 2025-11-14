<?php
/**
 * Public Page Display
 * Podn.Bio - Displays user's link-in-bio page
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/theme-helpers.php';
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

// Get theme and extract colors/fonts using Theme class
$themeClass = new Theme();
$theme = null;
if ($page['theme_id']) {
    $theme = $themeClass->getTheme($page['theme_id']);
}

$colors = getThemeColors($page, $theme);
$fonts = getThemeFonts($page, $theme);
$themeTokens = getThemeTokens($page, $theme);
$themeBodyClass = '';
if ($theme && !empty($theme['name'])) {
    $slug = strtolower(trim($theme['name']));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    if (!empty($slug)) {
        $themeBodyClass = 'theme-' . $slug;
    }
}

// Extract individual values
$primaryColor = $colors['primary'];
$secondaryColor = $colors['secondary'];
$accentColor = $colors['accent'];
$headingFont = $fonts['heading'];
$bodyFont = $fonts['body'];

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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;700;900&family=Bowlby+One+SC&family=Poppins:wght@400;600;700&family=Raleway:wght@900&family=Oswald:wght@400;600;700;900&family=Montserrat:wght@900&display=swap" rel="stylesheet">
    
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
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo h(truncate($page['podcast_description'] ?: 'Link in bio page', 160)); ?>">
    <meta property="og:title" content="<?php echo h($page['podcast_name'] ?: $page['username']); ?>">
    <meta property="og:description" content="<?php echo h(truncate($page['podcast_description'] ?: '', 160)); ?>">
    <meta property="og:image" content="<?php echo h(normalizeImageUrl($page['cover_image_url'] ?: '')); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <?php echo $cssGenerator->generateCompleteStyleBlock(); ?>
    
    <!-- Additional widget-specific styles -->
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
        }

        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--shell-background, color-mix(in srgb, #f5f7fb 94%, #0f172a 6%));
            box-sizing: border-box;
        }

        /* Mobile-only: No responsive breakpoints - page is always mobile */

        /* Page container and layout - Mobile-only */
        .page-container {
            width: var(--mobile-page-width);
            <?php if ($previewWidth): ?>
            max-width: <?php echo $previewWidth; ?>px;
            <?php else: ?>
            max-width: 420px;
            <?php endif; ?>
            margin: 0 auto;
            padding: 1rem;
            box-sizing: border-box;
        }
        
        /* Add top padding when podcast banner is present to prevent content from being hidden */
        <?php if ($showPodcastPlayer): ?>
        body:has(.podcast-top-banner) .page-container {
            padding-top: calc(1rem + 60px); /* 1rem base + banner height (approx 60px) */
        }
        <?php endif; ?>
        
        .profile-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-sm);
            margin-bottom: var(--space-lg);
        }
        
        .profile-image {
            object-fit: cover;
            margin: 0 auto;
            display: block;
        }
        
        .profile-image-size-small {
            width: 6.25rem;
            height: 6.25rem;
        }
        
        .profile-image-size-medium {
            width: 8.75rem;
            height: 8.75rem;
        }
        
        .profile-image-size-large {
            width: 11.25rem;
            height: 11.25rem;
        }
        
        .profile-image-shape-circle {
            border-radius: 50%;
        }
        
        .profile-image-shape-rounded {
            border-radius: 1.125rem;
        }
        
        .profile-image-shape-square {
            border-radius: 0;
        }
        
        .profile-image-shadow-none {
            box-shadow: none;
        }
        
        .profile-image-shadow-subtle {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-shadow-strong {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.2);
        }
        
        .profile-image-border-none {
            border: none;
        }
        
        .profile-image-border-thin {
            border: 2.625px solid rgba(15, 23, 42, 0.15);
        }
        
        .profile-image-border-thick {
            border: 5.25px solid rgba(15, 23, 42, 0.25);
        }
        
        .cover-image {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        .page-title {
            font-size: var(--type-scale-xl, 2rem);
            line-height: var(--type-line-height-tight, 1.2);
            font-family: var(--font-family-heading);
            font-weight: var(--type-weight-bold, 600);
            margin: var(--space-xs) 0;
            color: var(--page-title-color, var(--color-text-primary));
        }
        
        .page-description {
            color: var(--page-description-color, var(--color-text-secondary));
            opacity: 0.9;
            font-size: var(--type-scale-sm, 1rem);
            line-height: var(--type-line-height-normal, 1.5);
            margin-bottom: var(--space-lg);
        }
        
        .name-size-large {
            font-size: 1.125rem;
        }
        
        .name-size-xlarge {
            font-size: 1.375rem;
        }
        
        .name-size-xxlarge {
            font-size: 1.625rem;
        }
        
        .bio-size-small {
            font-size: 0.875rem;
        }
        
        .bio-size-medium {
            font-size: 1rem;
        }
        
        .bio-size-large {
            font-size: 1.125rem;
        }
        
        /* Podcast Player CSS has been moved to /css/podcast-player.css */
        
        /* Page Name Effects */
        /* Neon Effect */
        /* Gummy Effect */
        /* Water Effect */
        /* Outline Effect */
        /* Depth Layers Effect */
        
        /* Theme-driven overrides for page title effects */
        .page-title-effect-3d-shadow {
            color: var(--color-text-inverse);
            text-shadow:
                3px 3px 0 color-mix(in srgb, var(--color-accent-primary) 90%, transparent),
                6px 6px 0 color-mix(in srgb, var(--color-accent-primary) 70%, black 30%),
                9px 9px 0 color-mix(in srgb, var(--color-accent-primary) 50%, black 50%);
        }
        
        .page-title-effect-stroke-shadow {
            color: var(--color-accent-primary);
            -webkit-text-stroke: 0.18rem var(--color-text-inverse);
            text-stroke: 0.18rem var(--color-text-inverse);
            text-shadow: 0.35rem 0.35rem 0 color-mix(in srgb, var(--color-accent-primary) 45%, black 55%);
        }
        
        .page-title-effect-slashed .top::before {
            color: var(--color-text-inverse);
        }
        
        .page-title-effect-slashed .bot::before {
            color: color-mix(in srgb, var(--color-text-inverse) 75%, var(--color-accent-primary) 25%);
        }
        
        .page-title-effect-sweet-title .sweet-title {
            color: var(--color-text-inverse);
            text-shadow:
                4px 4px 0 color-mix(in srgb, var(--color-accent-primary) 80%, transparent),
                7px 7px 0 color-mix(in srgb, var(--color-accent-primary) 55%, black 45%);
        }
        
        .page-title-effect-sweet-title .sweet-title span::before {
            color: color-mix(in srgb, var(--color-accent-primary) 60%, var(--color-text-inverse) 40%);
        }
        
        .page-title-effect-3d-extrude {
            color: var(--color-accent-primary);
            text-shadow:
                1px 1px 0 color-mix(in srgb, var(--color-accent-primary) 80%, transparent),
                2px 2px 0 color-mix(in srgb, var(--color-accent-primary) 65%, black 35%),
                3px 3px 0 color-mix(in srgb, var(--color-accent-primary) 50%, black 50%),
                4px 4px 0 color-mix(in srgb, var(--color-accent-primary) 35%, black 65%);
        }
        
        .page-title-effect-dragon-text .svg-text__shaded {
            fill: var(--color-text-inverse);
            text-shadow: 0 3px 20px color-mix(in srgb, var(--color-accent-primary) 40%, black 60%);
        }
        
        .page-title-effect-dragon-text .svg-text__shaded__stroke {
            stroke: color-mix(in srgb, var(--color-accent-primary) 45%, transparent);
            animation: dragon-offset 4s ease-in-out infinite;
        }
        
        .page-title-effect-neon {
            color: var(--color-accent-primary);
            border-color: color-mix(in srgb, var(--color-accent-primary) 40%, var(--color-text-inverse) 60%);
            animation: neon-flicker 2s infinite alternate;
        }
        
        .page-title-effect-gummy {
            color: color-mix(in srgb, var(--color-accent-primary) 65%, white 35%);
            text-shadow:
                0 0.15ch 15px color-mix(in srgb, var(--color-accent-primary) 45%, black 55%),
                0 -0.15ch 0 var(--color-text-inverse);
        }
        
        .page-title-effect-water {
            color: var(--color-text-inverse);
            -webkit-text-stroke: 2px var(--color-accent-primary);
        }
        
        .page-title-effect-water::before {
            color: var(--color-accent-primary);
            animation: water-wave 4s ease-in-out infinite;
        }
        
        .page-title-effect-outline {
            -webkit-text-stroke-color: var(--color-text-inverse);
            text-shadow: 0.4rem 0.4rem color-mix(in srgb, var(--color-accent-primary) 55%, black 45%);
        }
        
        .page-title-effect-glitch {
            color: var(--color-text-inverse);
        }
        
        .page-title-effect-glitch::before {
            color: color-mix(in srgb, var(--color-accent-primary) 80%, transparent);
            animation: title-glitch-shift 0.6s infinite alternate;
        }
        
        .page-title-effect-glitch::after {
            color: color-mix(in srgb, var(--color-border-focus) 70%, transparent);
            animation: title-glitch-shift 0.6s infinite alternate reverse;
        }
        
        .page-title-effect-isometric-3d {
            color: var(--color-text-inverse);
            text-shadow:
                1px 1px 0 color-mix(in srgb, var(--color-accent-primary) 80%, transparent),
                2px 2px 0 color-mix(in srgb, var(--color-accent-primary) 60%, black 40%),
                3px 3px 0 color-mix(in srgb, var(--color-accent-primary) 45%, black 55%);
        }
        
        .page-title-effect-stencil {
            -webkit-text-stroke: 3px var(--color-text-inverse);
            text-stroke: 3px var(--color-text-inverse);
        }
        
        .page-title-effect-stencil::before {
            background: linear-gradient(to right, transparent 0%, transparent 45%, color-mix(in srgb, var(--color-accent-primary) 70%, transparent) 55%, transparent 100%);
        }
        
        .page-title-effect-cut-text::before {
            color: var(--color-text-inverse);
        }
        
        .page-title-effect-cut-text::after {
            color: color-mix(in srgb, var(--color-accent-primary) 70%, var(--color-text-inverse) 30%);
        }
        
        .page-title-effect-cyber-text {
            color: color-mix(in srgb, var(--color-text-inverse) 85%, transparent);
        }
        
        .page-title-effect-cyber-text::before {
            color: color-mix(in srgb, var(--color-accent-primary) 80%, transparent);
        }
        
        .page-title-effect-cyber-text::after {
            color: color-mix(in srgb, var(--color-border-focus) 70%, transparent);
        }
        
        .page-title-effect-depth-layers {
            color: var(--color-text-inverse);
            text-shadow:
                2px 2px 0 color-mix(in srgb, var(--color-accent-primary) 90%, transparent),
                4px 4px 0 color-mix(in srgb, var(--color-accent-primary) 70%, black 30%),
                6px 6px 0 color-mix(in srgb, var(--color-accent-primary) 50%, black 50%),
                10px 10px 20px color-mix(in srgb, var(--color-accent-primary) 25%, black 75%);
        }
        
        @keyframes neon-flicker {
            0%, 20%, 60%, 100% {
                box-shadow:
                    0 0 0.5rem var(--color-text-inverse),
                    0 0 1.5rem color-mix(in srgb, var(--color-accent-primary) 70%, transparent),
                    0 0 3rem color-mix(in srgb, var(--color-accent-primary) 50%, transparent);
                text-shadow:
                    0 0 0.5rem var(--color-text-inverse),
                    0 0 1.5rem color-mix(in srgb, var(--color-accent-primary) 70%, transparent),
                    0 0 3rem color-mix(in srgb, var(--color-accent-primary) 50%, transparent);
            }
            30%, 55% {
                box-shadow: none;
                text-shadow: none;
            }
        }
        
        @keyframes dragon-offset {
            0%, 70% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: 3.5em; }
        }
        
        @keyframes title-glitch-shift {
            0% { transform: translate(0, 0); }
            25% { transform: translate(-0.15rem, 0.1rem); }
            50% { transform: translate(0.2rem, -0.15rem); }
            75% { transform: translate(-0.2rem, -0.2rem); }
            100% { transform: translate(0.15rem, 0.15rem); }
        }
        
        @keyframes water-wave {
            0%, 100% {
                clip-path: polygon(0% 45%, 16% 44%, 33% 50%, 54% 60%, 70% 61%, 84% 59%, 100% 52%, 100% 100%, 0% 100%);
            }
            50% {
                clip-path: polygon(0% 60%, 15% 65%, 34% 66%, 51% 62%, 67% 50%, 84% 45%, 100% 46%, 100% 100%, 0% 100%);
            }
        }
        
        .widgets-container {
            display: flex;
            flex-direction: column;
            gap: var(--widget-spacing, var(--space-md));
            position: relative;
        }
        
        .widget-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            width: 100%;
            padding: var(--space-sm) var(--space-md);
            background: var(--widget-background, var(--color-background-surface));
            border: var(--widget-border-width, var(--border-width-hairline)) solid var(--widget-border-color, var(--color-border-default));
            border-radius: var(--widget-border-radius, var(--shape-corner-md));
            text-decoration: none;
            color: var(--color-text-on-surface);
            transition: transform var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1)), box-shadow var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            box-sizing: border-box;
            position: relative;
            z-index: auto;
            font-family: var(--widget-secondary-font, var(--font-family-body));
            box-shadow: var(--widget-box-shadow, var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12)));
        }
        
        /* Widgets without thumbnails/icons - center text */
        .widget-link-simple {
            justify-content: center;
            text-align: center;
        }
        
        .widget-link-simple .widget-content {
            padding: 0 !important;
        }
        
        .widget-link-simple .widget-title {
            margin: 0 !important;
            font-size: var(--type-scale-md, 1.333rem);
            font-weight: var(--type-weight-medium, 500);
        }
        
        /* Thumbnail wrapper for consistent sizing */
        .widget-thumbnail-wrapper {
            flex-shrink: 0;
            width: clamp(3rem, 16vw, 3.75rem);
            height: clamp(3rem, 16vw, 3.75rem);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--shape-corner-md, 0.75rem);
            overflow: hidden;
        }
        
        /* Icon wrapper for consistent sizing */
        .widget-icon-wrapper {
            flex-shrink: 0;
            width: clamp(3rem, 16vw, 3.75rem);
            height: clamp(3rem, 16vw, 3.75rem);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Other widgets (podcast, video, etc.) - full width */
        .widget-podcast,
        .widget-video,
        .widget-text,
        .widget-image {
            width: 100%;
        }
        
        .widget-item:hover {
            transform: translateY(calc(var(--space-2xs, 0.25rem) * -1));
            box-shadow: var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16));
        }
        
        /* Featured Widget Effects */
        .featured-widget {
            position: relative;
        }
        
        .featured-widget .widget-item {
            position: relative;
            z-index: 1;
        }
        
        /* Jiggle Effect - triggered randomly */
        @keyframes jiggle {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            25% { transform: translateX(-3px) rotate(-1deg); }
            75% { transform: translateX(3px) rotate(1deg); }
        }
        
        .featured-effect-jiggle .widget-item {
            animation: none; /* Animation triggered via JavaScript */
        }
        
        .featured-effect-jiggle.active .widget-item {
            animation: jiggle 0.5s ease-in-out;
        }
        
        /* Burn Effect - glowing ember-like glow */
        @keyframes burn {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 100, 0, 0.5), 0 0 20px rgba(255, 50, 0, 0.3); }
            50% { box-shadow: 0 0 20px rgba(255, 150, 0, 0.8), 0 0 40px rgba(255, 100, 0, 0.5), 0 0 60px rgba(255, 50, 0, 0.3); }
        }
        
        .featured-effect-burn .widget-item {
            animation: burn 1.5s ease-in-out infinite;
        }
        
        /* Rotating Glow - triggered randomly */
        @keyframes rotating-glow {
            0% { box-shadow: 0 0 15px rgba(0, 102, 255, 0.6), 0 0 30px rgba(0, 102, 255, 0.4); filter: hue-rotate(0deg); }
            25% { box-shadow: 0 0 15px rgba(255, 100, 200, 0.6), 0 0 30px rgba(255, 100, 200, 0.4); filter: hue-rotate(90deg); }
            50% { box-shadow: 0 0 15px rgba(100, 255, 100, 0.6), 0 0 30px rgba(100, 255, 100, 0.4); filter: hue-rotate(180deg); }
            75% { box-shadow: 0 0 15px rgba(255, 200, 100, 0.6), 0 0 30px rgba(255, 200, 100, 0.4); filter: hue-rotate(270deg); }
            100% { box-shadow: 0 0 15px rgba(0, 102, 255, 0.6), 0 0 30px rgba(0, 102, 255, 0.4); filter: hue-rotate(360deg); }
        }
        
        .featured-effect-rotating-glow .widget-item {
            animation: none; /* Animation triggered via JavaScript */
        }
        
        .featured-effect-rotating-glow.active .widget-item {
            animation: rotating-glow 2s linear;
        }
        
        /* Blink Effect */
        @keyframes blink {
            0%, 50%, 100% { opacity: 1; }
            25%, 75% { opacity: 0.3; }
        }
        
        .featured-effect-blink .widget-item {
            animation: blink 1.5s ease-in-out infinite;
        }
        
        /* Pulse Effect - triggered randomly */
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            50% { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0, 102, 255, 0.3); }
        }
        
        .featured-effect-pulse .widget-item {
            animation: none; /* Animation triggered via JavaScript */
        }
        
        .featured-effect-pulse.active .widget-item {
            animation: pulse 1s ease-in-out;
        }
        
        /* Shake Effect - triggered randomly */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
            20%, 40%, 60%, 80% { transform: translateX(4px); }
        }
        
        .featured-effect-shake .widget-item {
            animation: none; /* Animation triggered via JavaScript */
        }
        
        .featured-effect-shake.active .widget-item {
            animation: shake 0.6s ease-in-out;
        }
        
        /* Sparkles Effect */
        .featured-effect-sparkles {
            position: relative;
            overflow: visible;
        }
        
        .featured-effect-sparkles .sparkle {
            position: absolute;
            width: 24px;
            height: 24px;
            pointer-events: none;
            z-index: 10;
            opacity: 0;
        }
        
        .featured-effect-sparkles .sparkle svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.8));
        }
        
        .featured-effect-sparkles .sparkle.active {
            opacity: 1;
            animation: sparkleAnim 2s ease-in-out forwards;
        }
        
        @keyframes sparkleAnim {
            0% {
                opacity: 0;
                transform: scale(0) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: scale(1.2) rotate(180deg);
            }
            100% {
                opacity: 0;
                transform: scale(0) rotate(360deg);
            }
        }
        
        .widget-thumbnail {
            width: 100%;
            height: 100%;
            border-radius: var(--widget-border-radius, 8px);
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .widget-thumbnail-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.05);
            border-radius: var(--widget-border-radius, 8px);
            color: rgba(0, 0, 0, 0.3);
            font-size: 1.5rem;
        }
        
        .widget-icon {
            font-size: 1.5rem;
            color: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .widget-content {
            flex: 1;
            min-width: 0; /* Allow flex item to shrink below content size */
            font-family: var(--widget-secondary-font, var(--font-family-body));
            font-size: var(--type-scale-sm, 1rem);
            line-height: var(--type-line-height-normal, 1.5);
        }
        
        .widget-title {
            font-weight: var(--type-weight-medium, 500);
            margin: 0 0 var(--space-2xs, 0.25rem) 0;
            font-family: var(--widget-primary-font, var(--font-family-heading));
            color: var(--color-text-on-surface);
            font-size: var(--type-scale-md, 1.333rem);
        }
        
        .widget-description {
            font-size: var(--type-scale-sm, 1rem);
            color: var(--color-text-secondary);
            opacity: 0.9;
            margin: var(--space-2xs, 0.25rem) 0 0 0;
            font-family: var(--widget-secondary-font, var(--font-family-body));
            min-width: 0; /* Allow text to be constrained in flex container */
        }
        
        /* Marquee animation for Custom Link widget descriptions only */
        .widget-item .widget-description.marquee {
            overflow: hidden;
            white-space: nowrap;
            position: relative;
            width: 100%;
            max-width: 100%;
        }
        
        .widget-item .widget-description .marquee-content {
            display: inline-flex;
            white-space: nowrap;
            animation: widget-marquee-scroll linear infinite;
            animation-duration: var(--marquee-duration, 12s);
            will-change: transform; /* Optimize animation performance */
        }
        
        .widget-item .widget-description .marquee-content .marquee-text {
            display: inline-block;
            white-space: nowrap;
            padding-right: 2em; /* Space between duplicates for better visual separation */
        }
        
        @keyframes widget-marquee-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(var(--marquee-distance, -100px)); }
        }
        
        
        /* Video widget styles */
        .widget-video {
            padding: 0;
            border: none;
            background: transparent;
        }
        
        .widget-video-embed {
            margin-top: 0.5rem;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
        }
        
        .widget-video-embed iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        /* Text/HTML widget styles */
        .widget-text {
            text-align: left;
        }
        
        .widget-text-content {
            padding: 1rem;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Image widget styles */
        .widget-image {
            padding: 0;
            border: none;
            background: transparent;
            display: block;
        }
        
        .widget-image-content {
            width: 100%;
            height: auto;
            border-radius: 8px;
            display: block;
        }
        
        /* Podcast Player widget styles - MOVED TO /css/podcast-player.css */
        .social-icons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin: 1.5rem 0;
        }
        
        .social-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: var(--color-accent-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.5rem;
        }
        
        .social-icon i {
            display: block;
        }
        
        .social-icon svg {
            display: block;
            color: inherit;
        }
        
        .social-icon:hover {
            transform: translateY(-2px);
            opacity: 0.8;
        }
        
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 999;
        }
        
        .drawer-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        /* Episode drawer styles - MOVED TO /css/podcast-player.css */
        
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid color-mix(in srgb, var(--color-accent-primary) 50%, transparent);
        }
        
        .drawer-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--color-accent-primary);
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Mobile-only styles - always applied */
        /* Profile image size is controlled by .profile-image-size-* classes */

        /* Aurora Theme Styling */
        body.theme-aurora-skies {
            background: var(--gradient-page, var(--page-background));
            color: var(--color-text-on-background, var(--text-color));
            font-family: var(--font-family-body, var(--body-font), sans-serif);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body.theme-aurora-skies .page-container {
            background: transparent;
            border: none;
            box-shadow: none;
        }

        body.theme-aurora-skies::before,
        body.theme-aurora-skies::after {
            content: "";
            position: fixed;
            inset: auto;
            width: 65vw;
            height: 65vw;
            border-radius: 50%;
            background: radial-gradient(circle, color-mix(in srgb, var(--gradient-accent, var(--color-accent-primary)) 35%, transparent) 0%, rgba(0,0,0,0) 70%);
            opacity: 0.4;
            pointer-events: none;
            filter: blur(24px);
            z-index: -2;
        }

        body.theme-aurora-skies::before {
            top: -20vw;
            left: -15vw;
        }

        body.theme-aurora-skies::after {
            bottom: -25vw;
            right: -10vw;
        }

        body.theme-aurora-skies .profile-image {
            border: 3px solid color-mix(in srgb, var(--color-border-default) 55%, transparent);
            box-shadow: 0 16px 36px rgba(6, 10, 45, 0.35);
        }

        body.theme-aurora-skies .page-title {
            position: relative;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: transparent;
            background-image: var(--gradient-accent, linear-gradient(120deg, #7affd8 0%, #64a0ff 50%, #b174ff 100%));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px color-mix(in srgb, var(--color-accent-primary) 40%, transparent);
            margin-bottom: var(--space-sm);
        }

        body.theme-aurora-skies .page-title::after {
            content: "";
            display: block;
            margin: var(--space-xs) auto 0;
            width: clamp(6rem, 50%, 12rem);
            height: 2px;
            background: var(--gradient-accent, var(--color-accent-primary));
            border-radius: 999px;
            opacity: 0.85;
        }

        body.theme-aurora-skies .page-description {
            color: color-mix(in srgb, var(--color-text-secondary) 80%, #f3fbff 20%);
        }

        body.theme-aurora-skies .widget-item {
            background: color-mix(in srgb, var(--color-background-surface) 80%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-border-default) 45%, transparent);
            box-shadow: 0 18px 38px rgba(4, 9, 38, 0.32);
        }

        body.theme-aurora-skies .widget-item::before {
            display: none;
        }

        body.theme-aurora-skies .widget-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 44px rgba(4, 9, 38, 0.36);
        }

        body.theme-aurora-skies .widget-title {
            color: color-mix(in srgb, var(--color-text-primary) 92%, #ffffff 8%);
        }

        body.theme-aurora-skies .widget-description {
            color: color-mix(in srgb, var(--color-text-secondary) 78%, #d7e7ff 22%);
        }

        body.theme-aurora-skies .social-icon {
            color: var(--color-accent-primary);
            background: rgba(255, 255, 255, 0.04);
            border-radius: 14px;
        }

        body.theme-aurora-skies .social-icon:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 30px color-mix(in srgb, var(--color-accent-primary) 35%, transparent);
        }

        /* Podcast Player theme overrides - MOVED TO /css/podcast-player.css */

        .widget-heading {
            width: 100%;
            padding: var(--space-sm, 1rem) var(--space-md, 1.25rem);
            text-align: center;
        }

        .widget-heading-text {
            margin: 0;
            font-family: var(--heading-font, var(--font-family-heading));
        }

        .widget-heading-h1 .widget-heading-text {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: var(--type-weight-bold, 700);
        }

        .widget-heading-h2 .widget-heading-text {
            font-size: clamp(1.6rem, 3.25vw, 2.2rem);
            font-weight: var(--type-weight-semibold, 600);
        }

        .widget-heading-h3 .widget-heading-text {
            font-size: clamp(1.3rem, 2.75vw, 1.8rem);
            font-weight: var(--type-weight-medium, 500);
        }

        .widget-text-note {
            width: 100%;
            padding: var(--space-xs, 0.75rem) var(--space-sm, 1rem);
            font-size: 0.9rem;
            font-style: italic;
            color: rgba(15, 23, 42, 0.75);
            text-align: center;
        }

        .widget-text-note p {
            margin: 0;
        }

        .widget-divider {
            width: 100%;
            padding: var(--space-xs, 0.75rem) var(--space-md, 1.25rem);
        }

        .widget-divider-line {
            border: none;
            height: 3px;
            width: 100%;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.45);
        }

        .widget-divider-line-shadow {
            background: rgba(71, 85, 105, 0.6);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        }

        .widget-divider-line-gradient {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.85), rgba(124, 58, 237, 0.85));
        }
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
            
            <?php if ($page['cover_image_url']): ?>
                <img src="<?php echo h(normalizeImageUrl($page['cover_image_url'])); ?>" alt="Cover" class="cover-image">
            <?php endif; ?>
            
            <?php if ($page['podcast_name']): 
                $nameAlignment = $page['name_alignment'] ?? 'center';
                $nameTextSize = $page['name_text_size'] ?? 'large';
                $alignmentStyle = 'text-align: ' . h($nameAlignment) . ';';
                $sizeClass = 'name-size-' . h($nameTextSize);
                // Allow safe HTML tags (strong, em, u, br) but sanitize the rest
                $nameContent = $page['podcast_name'];
                // First convert newlines to <br>, then strip unwanted tags
                $nameContent = nl2br($nameContent);
                $nameContent = strip_tags($nameContent, '<strong><em><u><br>');
            ?>
                <h1 class="page-title <?php echo $sizeClass; ?>" style="<?php echo $alignmentStyle; ?>"><?php echo $nameContent; ?></h1>
            <?php elseif ($page['username']): ?>
                <h1 class="page-title"><?php echo h($page['username']); ?></h1>
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
                                <img class="episode-artwork-large" id="now-playing-artwork" src="" alt="Episode Artwork" style="display: none;">
                                <div class="artwork-placeholder" id="artwork-placeholder">
                                    <i class="fas fa-music"></i>
                                </div>
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
                        <div class="details-content">
                            <!-- Follow & Share Section -->
                            <div class="details-section" id="follow-section">
                                <h2 class="section-title">Follow & Share</h2>
                                <div id="follow-content">
                                    <!-- Follow content will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Tab -->
                    <div class="tab-panel" id="details-panel">
                        <div class="details-content">
                            <!-- Show Notes Section -->
                            <div class="details-section" id="shownotes-section">
                                <h2 class="section-title">Show Notes</h2>
                                <div class="shownotes-content" id="shownotes-content">
                                    <p class="empty-message">No episode selected</p>
                                </div>
                            </div>

                            <!-- Chapters Section -->
                            <div class="details-section" id="chapters-section">
                                <h2 class="section-title">Chapters</h2>
                                <div class="chapters-list" id="chapters-list">
                                    <div class="empty-state">No chapters available</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Episodes Tab -->
                    <div class="tab-panel" id="episodes-panel">
                        <div class="episodes-content">
                            <!-- Loading Skeleton -->
                            <div class="loading-skeleton" id="loading-skeleton">
                                <div class="skeleton-item"></div>
                                <div class="skeleton-item"></div>
                                <div class="skeleton-item"></div>
                                <div class="skeleton-item"></div>
                            </div>

                            <!-- Episodes List -->
                            <div class="episodes-list" id="episodes-list" style="display: none;">
                                <!-- Episodes will be inserted here -->
                            </div>

                            <!-- Error State -->
                            <div class="error-state" id="error-state" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Failed to load episodes</p>
                                <button class="retry-button" id="retry-button">Retry</button>
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
                        } else {
                            // Log when widget returns empty but exists in DB
                            error_log("Widget " . ($widget['id'] ?? 'unknown') . " (type: " . ($widget['widget_type'] ?? 'unknown') . ") returned empty render");
                        }
                    } catch (Exception $e) {
                        // Log error but don't break the page
                        error_log("Widget render error for widget ID " . ($widget['id'] ?? 'unknown') . ": " . $e->getMessage());
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
    <?php if ((!isset($page['footer_visible']) || $page['footer_visible']) && (!empty($page['footer_text']) || !empty($page['footer_copyright']) || !empty($page['footer_privacy_link']) || !empty($page['footer_terms_link']))): ?>
        <footer class="page-footer" style="margin-top: 2rem; padding: 1.5rem 1rem; text-align: center; border-top: 1px solid rgba(15, 23, 42, 0.1);">
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
    <?php endif; ?>
    
    <script>
        function openEmailDrawer() {
            const drawer = document.getElementById('email-drawer');
            const overlay = document.getElementById('email-overlay');
            if (drawer && overlay) {
                drawer.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEmailDrawer() {
            const drawer = document.getElementById('email-drawer');
            const overlay = document.getElementById('email-overlay');
            if (drawer && overlay) {
                drawer.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function subscribeEmail(event) {
            event.preventDefault();
            
            const form = event.target;
            const email = form.querySelector('#subscribe-email').value;
            const messageDiv = document.getElementById('subscribe-message');
            
            if (!email) {
                messageDiv.textContent = 'Please enter an email address';
                messageDiv.style.display = 'block';
                messageDiv.style.color = 'var(--color-accent-primary)';
                return;
            }
            
            const formData = new FormData();
            formData.append('page_id', <?php echo $page['id']; ?>);
            formData.append('email', email);
            
            fetch('/api/subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.textContent = data.message || 'Successfully subscribed!';
                    messageDiv.style.color = 'green';
                    form.reset();
                } else {
                    messageDiv.textContent = data.error || 'Failed to subscribe';
                    messageDiv.style.color = 'red';
                }
            })
            .catch(() => {
                messageDiv.style.display = 'block';
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.color = 'red';
            });
        }
        
        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEmailDrawer();
            }
        });
    </script>
    <script>
        // Random timing for movement-based Featured Widget effects
        // Creates illusion of "something alive" inside occasionally causing movement
        (function() {
            const movementEffects = ['jiggle', 'shake', 'pulse', 'rotating-glow'];
            const featuredWidgets = document.querySelectorAll('.featured-widget');
            
            featuredWidgets.forEach(widget => {
                const effectClass = Array.from(widget.classList).find(cls => cls.startsWith('featured-effect-'));
                if (!effectClass) return;
                
                const effect = effectClass.replace('featured-effect-', '');
                if (!movementEffects.includes(effect)) return; // Static effects (burn, blink) continue as normal
                
                function triggerAnimation() {
                    widget.classList.add('active');
                    setTimeout(() => {
                        widget.classList.remove('active');
                    }, effect === 'rotating-glow' ? 2000 : (effect === 'pulse' ? 1000 : 600));
                }
                
                // Initial trigger after random delay (0.5-2 seconds)
                setTimeout(triggerAnimation, 500 + Math.random() * 1500);
                
                // Continue triggering at random intervals (2-8 seconds)
                function scheduleNext() {
                    const delay = 2000 + Math.random() * 6000; // 2-8 seconds
                    setTimeout(() => {
                        triggerAnimation();
                        scheduleNext();
                    }, delay);
                }
                scheduleNext();
            });
        })();
        
        // Sparkles Effect for Featured Widgets
        (function() {
            // SVG sparkle path (star shape)
            const sparkleSVG = `
                <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M93.781 51.578C95 50.969 96 49.359 96 48c0-1.375-1-2.969-2.219-3.578 0 0-22.868-1.514-31.781-10.422-8.915-8.91-10.438-31.781-10.438-31.781C50.969 1 49.375 0 48 0s-2.969 1-3.594 2.219c0 0-1.5 22.87-10.438 31.781-8.938 8.908-31.781 10.422-31.781 10.422C1 44.031 0 45.625 0 48c0 1.359 1 2.969 2.219 3.578 0 0 22.843 1.514 31.781 10.422 8.938 8.911 10.438 31.781 10.438 31.781C45.031 95 46.625 96 48 96s2.969-1 3.578-2.219c0 0 1.514-22.87 10.438-31.781 8.913-8.908 31.781-10.422 31.781-10.422C94 51.031 95 49.359 95 48c0-1.375-1-2.969-2.219-3.578z" fill="var(--color-accent-primary)"/>
                </svg>
            `;
            
            const sparklesWidgets = document.querySelectorAll('.featured-widget.featured-effect-sparkles');
            
            sparklesWidgets.forEach(widget => {
                const widgetItem = widget.querySelector('.widget-item');
                if (!widgetItem) return;
                
                function createSparkle() {
                    const sparkle = document.createElement('div');
                    sparkle.className = 'sparkle';
                    sparkle.innerHTML = sparkleSVG;
                    
                    // Get widget dimensions
                    const widgetRect = widgetItem.getBoundingClientRect();
                    const maxX = widgetRect.width;
                    const maxY = widgetRect.height;
                    
                    // Position sparkle randomly but ensure it's visible
                    const x = Math.random() * (maxX - 48) + 24;
                    const y = Math.random() * (maxY - 48) + 24;
                    
                    sparkle.style.left = x + 'px';
                    sparkle.style.top = y + 'px';
                    
                    // Random delay and duration
                    const delay = Math.random() * 2;
                    const duration = 1.5 + Math.random() * 1;
                    sparkle.style.animationDelay = delay + 's';
                    sparkle.style.animationDuration = duration + 's';
                    
                    widgetItem.appendChild(sparkle);
                    
                    // Activate sparkle
                    setTimeout(() => {
                        sparkle.classList.add('active');
                    }, 10);
                    
                    // Remove sparkle after animation
                    setTimeout(() => {
                        if (sparkle.parentNode) {
                            sparkle.parentNode.removeChild(sparkle);
                        }
                    }, (delay + duration) * 1000);
                }
                
                // Create initial sparkles
                for (let i = 0; i < 5; i++) {
                    setTimeout(() => {
                        createSparkle();
                    }, i * 400);
                }
                
                // Continue creating sparkles at random intervals
                setInterval(() => {
                    if (Math.random() > 0.3) { // 70% chance to create a sparkle
                        createSparkle();
                    }
                }, 800 + Math.random() * 1200);
            });
        })();
    </script>
    <script>
        // Accelerometer-based Tilt Effect
        // Only activate if spatial-tilt class is present
        (function() {
            if (!document.body.classList.contains('spatial-tilt')) {
                return; // Exit if tilt effect is not enabled
            }
            
            // Check if Device Orientation API is available
            if (typeof DeviceOrientationEvent === 'undefined' || 
                typeof DeviceOrientationEvent.requestPermission === 'function') {
                // iOS 13+ requires permission
                const permissionButton = document.createElement('button');
                permissionButton.textContent = 'Enable Tilt Effect';
                permissionButton.style.position = 'fixed';
                permissionButton.style.bottom = '20px';
                permissionButton.style.right = '20px';
                permissionButton.style.padding = '12px 24px';
                permissionButton.style.background = 'var(--color-accent-primary)';
                permissionButton.style.color = 'var(--color-text-inverse)';
                permissionButton.style.border = 'none';
                permissionButton.style.borderRadius = 'var(--shape-corner-md, 0.75rem)';
                permissionButton.style.cursor = 'pointer';
                permissionButton.style.zIndex = '1000';
                permissionButton.style.fontWeight = '600';
                permissionButton.style.boxShadow = 'var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16))';
                permissionButton.onclick = function() {
                    DeviceOrientationEvent.requestPermission()
                        .then(response => {
                            if (response === 'granted') {
                                permissionButton.remove();
                                initTiltEffect();
                            }
                        })
                        .catch(() => {
                            permissionButton.textContent = 'Permission Denied';
                            permissionButton.style.background = 'var(--color-state-danger)';
                        });
                };
                document.body.appendChild(permissionButton);
            } else {
                // API available, initialize immediately
                initTiltEffect();
            }
            
            function initTiltEffect() {
                const widgets = document.querySelectorAll('.widget-item');
                if (widgets.length === 0) return;
                
                let lastUpdate = 0;
                const throttleMs = 16; // ~60fps
                let rafId = null;
                
                function handleOrientation(event) {
                    const now = Date.now();
                    if (now - lastUpdate < throttleMs) {
                        return; // Throttle updates
                    }
                    lastUpdate = now;
                    
                    // Cancel previous animation frame
                    if (rafId) {
                        cancelAnimationFrame(rafId);
                    }
                    
                    // Schedule update
                    rafId = requestAnimationFrame(() => {
                        applyTiltTransforms(event, widgets);
                    });
                }
                
                function applyTiltTransforms(event, widgetElements) {
                    // Get tilt values (beta: front-to-back, gamma: left-to-right)
                    const beta = event.beta || 0;  // -180 to 180
                    const gamma = event.gamma || 0; // -90 to 90
                    
                    // Normalize values and scale for subtle movement
                    const maxTilt = 15; // Maximum pixels to move
                    const xOffset = (gamma / 90) * maxTilt;  // Left-right tilt
                    const yOffset = (beta / 180) * maxTilt;   // Front-back tilt
                    
                    // Apply transforms with parallax effect (each widget moves slightly differently)
                    widgetElements.forEach((widget, index) => {
                        // Create subtle parallax by varying movement amount
                        const parallaxFactor = 0.7 + (index % 3) * 0.1; // 0.7, 0.8, or 0.9
                        const widgetX = xOffset * parallaxFactor;
                        const widgetY = yOffset * parallaxFactor;
                        
                        widget.style.transform = `translate(${widgetX}px, ${widgetY}px)`;
                    });
                }
                
                // Listen for device orientation events
                window.addEventListener('deviceorientation', handleOrientation, true);
                
                // Cleanup on page unload
                window.addEventListener('beforeunload', () => {
                    window.removeEventListener('deviceorientation', handleOrientation, true);
                    if (rafId) {
                        cancelAnimationFrame(rafId);
                    }
                });
            }
        })();
        
        // Marquee scrolling for Custom Link widget descriptions only
        (function() {
            function initWidgetMarquee(element) {
                // Only process widget descriptions within Custom Link widgets
                if (!element.closest('.widget-item') || !element.classList.contains('widget-description')) {
                    return;
                }
                
                // Reset processed flag to allow re-evaluation
                delete element.dataset.marqueeProcessed;
                
                // Skip if element contains SVG
                if (element.querySelector('svg')) {
                    return;
                }
                
                // Unwrap content first to get accurate measurements
                const contentSpan = element.querySelector('.marquee-content');
                if (contentSpan) {
                    // Extract original content from first marquee-text if it exists
                    const firstText = contentSpan.querySelector('.marquee-text');
                    if (firstText) {
                        element.innerHTML = firstText.innerHTML;
                    } else {
                        element.innerHTML = contentSpan.innerHTML;
                    }
                    element.classList.remove('marquee');
                }
                
                // Skip if already has marquee-text (already processed)
                if (element.querySelector('.marquee-text')) {
                    return;
                }
                
                // Get container width - measure parent container to know available space
                // Do this BEFORE any style changes
                const parentContainer = element.parentElement; // .widget-content
                const containerWidth = parentContainer ? parentContainer.clientWidth : element.clientWidth;
                
                if (containerWidth <= 0) {
                    // Container not ready yet, skip
                    return;
                }
                
                // Use a temporary span to measure text width without affecting layout
                const tempSpan = document.createElement('span');
                tempSpan.style.position = 'absolute';
                tempSpan.style.visibility = 'hidden';
                tempSpan.style.whiteSpace = 'nowrap';
                tempSpan.style.fontSize = window.getComputedStyle(element).fontSize;
                tempSpan.style.fontFamily = window.getComputedStyle(element).fontFamily;
                tempSpan.style.fontWeight = window.getComputedStyle(element).fontWeight;
                tempSpan.style.letterSpacing = window.getComputedStyle(element).letterSpacing;
                tempSpan.textContent = element.textContent;
                
                document.body.appendChild(tempSpan);
                const textWidth = tempSpan.offsetWidth;
                document.body.removeChild(tempSpan);
                
                if (textWidth > containerWidth && containerWidth > 0) {
                    // Text overflows when on single line - apply marquee
                    element.classList.add('marquee');
                    
                    // Wrap content in marquee-content span and duplicate for seamless loop
                    const content = element.innerHTML;
                    // Duplicate content for seamless scrolling
                    element.innerHTML = '<span class="marquee-content"><span class="marquee-text">' + content + '</span><span class="marquee-text">' + content + '</span></span>';
                    
                    const newContentSpan = element.querySelector('.marquee-content');
                    if (newContentSpan) {
                        // Force a reflow to get accurate measurements
                        void newContentSpan.offsetWidth;
                        const firstText = newContentSpan.querySelector('.marquee-text');
                        if (firstText) {
                            const textWidth = firstText.scrollWidth;
                            const duration = Math.max(8, Math.min(20, (textWidth / 40))); // 8-20 seconds based on length
                            
                            // Set CSS variables for animation
                            // Scroll by exactly one text width so the duplicate seamlessly continues
                            newContentSpan.style.setProperty('--marquee-distance', `-${textWidth}px`);
                            newContentSpan.style.setProperty('--marquee-duration', `${duration}s`);
                        }
                    }
                }
                
                element.dataset.marqueeProcessed = 'true';
            }
            
            let isProcessing = false;
            let debounceTimer = null;
            
            function applyWidgetMarquee() {
                // Prevent infinite loops
                if (isProcessing) {
                    return;
                }
                
                isProcessing = true;
                
                try {
                    // Only target widget descriptions within Custom Link widgets
                    document.querySelectorAll('.widget-item .widget-description').forEach(element => {
                        // Skip if already has marquee-text (already fully processed)
                        if (element.querySelector('.marquee-text')) {
                            return;
                        }
                        // Skip if already processed and has marquee-content
                        if (element.dataset.marqueeProcessed === 'true' && element.querySelector('.marquee-content')) {
                            return;
                        }
                        initWidgetMarquee(element);
                    });
                } finally {
                    isProcessing = false;
                }
            }
            
            // Debounced version for observer
            function debouncedApplyWidgetMarquee() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    // Only reset flags for elements that actually changed
                    document.querySelectorAll('.widget-item .widget-description').forEach(el => {
                        // Only reset if it's not currently being processed
                        if (!isProcessing) {
                            delete el.dataset.marqueeProcessed;
                        }
                    });
                    applyWidgetMarquee();
                }, 100); // 100ms debounce
            }
            
            // Run on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', applyWidgetMarquee);
            } else {
                applyWidgetMarquee();
            }
            
            // Watch for dynamic content changes (only Custom Link widget descriptions)
            // Use debounced version to prevent infinite loops
            const observer = new MutationObserver((mutations) => {
                // Only process if mutations are not from our own code
                let shouldProcess = false;
                for (const mutation of mutations) {
                    // Skip if the mutation is just attribute changes (like dataset)
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-marquee-processed') {
                        continue;
                    }
                    // Skip if mutation is from adding marquee-content or marquee-text (our own changes)
                    if (mutation.addedNodes.length > 0) {
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType === 1) {
                                // Skip our own marquee elements
                                if (node.classList && (node.classList.contains('marquee-content') || node.classList.contains('marquee-text'))) {
                                    continue;
                                }
                                // Also check if it's a child of a marquee element
                                if (node.closest && (node.closest('.marquee-content') || node.closest('.marquee-text'))) {
                                    continue;
                                }
                            }
                            shouldProcess = true;
                            break;
                        }
                    } else {
                        shouldProcess = true;
                    }
                    if (shouldProcess) break;
                }
                
                if (shouldProcess) {
                    debouncedApplyWidgetMarquee();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: false // Don't watch attributes to avoid our own changes
            });
        })();
    </script>
    
    <!-- Podcast Player JavaScript (only load if RSS feed exists and player is enabled) -->
    <?php if ($showPodcastPlayer): ?>
        <script src="/js/podcast-player-utils.js"></script>
        <script src="/js/podcast-player-rss-parser.js"></script>
        <script src="/js/podcast-player-audio.js"></script>
        <script src="/js/podcast-player-app.js"></script>
        <script>
            // Initialize Podcast Player Drawer - Namespaced
            (function() {
                'use strict';
                
                const drawer = document.getElementById('podcast-top-drawer');
                const toggleBtn = document.getElementById('podcast-drawer-toggle');
                const closeBtn = document.getElementById('podcast-drawer-close');
                const banner = document.getElementById('podcast-top-banner');
                
                if (!drawer || !toggleBtn) return;
                
                // Namespace for drawer functions
                const PodcastDrawerController = {
                    isPeeking: false,
                    
                    openDrawer: function() {
                        drawer.style.display = 'flex';
                        // Force reflow
                        void drawer.offsetWidth;
                        drawer.classList.remove('peek');
                        drawer.classList.add('open');
                        // Update banner state
                        if (banner) {
                            banner.classList.remove('drawer-peek');
                            banner.classList.add('drawer-open');
                        }
                        document.body.style.overflow = 'hidden';
                        this.isPeeking = false;
                    },
                    
                    closeDrawer: function() {
                        // Force hide scrollbars immediately
                        document.body.style.overflow = 'hidden';
                        document.body.style.overflowY = 'hidden';
                        document.body.style.overflowX = 'hidden';
                        document.documentElement.style.overflow = 'hidden';
                        document.documentElement.style.overflowY = 'hidden';
                        document.documentElement.style.overflowX = 'hidden';
                        
                        drawer.classList.remove('open');
                        drawer.classList.remove('peek');
                        // Update banner state
                        if (banner) {
                            banner.classList.remove('drawer-open', 'drawer-peek');
                        }
                        setTimeout(() => {
                            if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                                drawer.style.display = 'flex';
                            }
                            // Restore body overflow after animation completes
                            document.body.style.overflow = '';
                            document.body.style.overflowY = '';
                            document.body.style.overflowX = '';
                            document.documentElement.style.overflow = '';
                            document.documentElement.style.overflowY = '';
                            document.documentElement.style.overflowX = '';
                        }, 350); // Slightly longer than transition to ensure it's complete
                        this.isPeeking = false;
                    },
                    
                    peekDrawer: function() {
                        if (this.isPeeking || drawer.classList.contains('open')) return;
                        
                        drawer.style.display = 'flex';
                        // Force reflow
                        void drawer.offsetWidth;
                        drawer.classList.add('peek');
                        // Update banner state
                        if (banner) {
                            banner.classList.remove('drawer-open');
                            banner.classList.add('drawer-peek');
                        }
                        this.isPeeking = true;
                        
                        // Close after showing peek
                        setTimeout(() => {
                            if (drawer.classList.contains('peek') && !drawer.classList.contains('open')) {
                                drawer.classList.remove('peek');
                                // Update banner state
                                if (banner) {
                                    banner.classList.remove('drawer-peek');
                                }
                                setTimeout(() => {
                                    if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                                        drawer.style.display = 'flex';
                                    }
                                }, 300);
                                this.isPeeking = false;
                            }
                        }, 1500); // Show peek for 1.5 seconds
                    }
                };
                
                // Initialize player when drawer opens
                let playerInitialized = false;
                const initPlayer = function() {
                    if (!playerInitialized) {
                        // Prepare config with RSS feed and social icons
                        const rssFeedUrl = '<?php echo h($page['rss_feed_url']); ?>';
                        
                        if (!rssFeedUrl) {
                            console.error('RSS feed URL is not set');
                            return;
                        }
                        
                        const config = {
                            rssFeedUrl: rssFeedUrl,
                            rssProxyUrl: '/api/rss-proxy.php',
                            imageProxyUrl: '/api/podcast-image-proxy.php',
                            savedCoverImage: '<?php echo h($page['cover_image_url'] ?? ''); ?>',
                            platformLinks: {
                                apple: null,
                                spotify: null,
                                google: null
                            },
                            reviewLinks: {
                                apple: null,
                                spotify: null,
                                google: null
                            },
                            socialIcons: <?php echo json_encode($socialIcons ?? []); ?>,
                            cacheTTL: 3600000
                        };
                        
                        // Initialize player
                        try {
                            console.log('Initializing podcast player with RSS feed:', rssFeedUrl);
                            window.podcastPlayerApp = new PodcastPlayerApp(config, drawer);
                            playerInitialized = true;
                            console.log('Podcast player initialized successfully');
                        } catch (error) {
                            console.error('Failed to initialize podcast player:', error);
                        }
                    }
                };
                
                // Open drawer and initialize player when toggle is clicked
                toggleBtn.addEventListener('click', function() {
                    PodcastDrawerController.openDrawer();
                    // Wait for scripts to load before initializing
                    if (typeof PodcastPlayerApp === 'undefined') {
                        console.error('PodcastPlayerApp class not loaded. Check script loading order.');
                        return;
                    }
                    // Small delay to ensure drawer is visible before initializing
                    setTimeout(initPlayer, 100);
                });
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        PodcastDrawerController.closeDrawer();
                    });
                }
                
                // Close on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && (drawer.classList.contains('open') || drawer.classList.contains('peek'))) {
                        PodcastDrawerController.closeDrawer();
                    }
                });
                
                // Peek animation: Open drawer 30% after 4 seconds, then close
                const shouldAutoPeek = window.matchMedia('(max-width: 600px)').matches;
                const alreadyPeeked = (typeof sessionStorage !== 'undefined') && sessionStorage.getItem('podcastDrawerAutoPeeked') === 'true';
                if (shouldAutoPeek && !alreadyPeeked) {
                setTimeout(function() {
                    PodcastDrawerController.peekDrawer();
                        try {
                            sessionStorage.setItem('podcastDrawerAutoPeeked', 'true');
                        } catch (err) {
                            console.warn('Unable to persist auto-peek flag:', err);
                        }
                }, 4000);
                }
                
                // Expose controller to window for debugging (optional)
                window.PodcastDrawerController = PodcastDrawerController;
            })();
        </script>
    <?php endif; ?>
    </body>
</html>

