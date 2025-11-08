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

// Check if request is for custom domain or username
$domain = $_SERVER['HTTP_HOST'];
$username = $_GET['username'] ?? '';

$pageClass = new Page();
$page = null;

// First check if this is a custom domain (not our main domains)
$mainDomains = ['getphily.com', 'www.getphily.com', 'podn.bio', 'www.podn.bio'];
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
    
    <!-- Podcast Player Styles (only load if RSS feed exists) -->
    <?php if (!empty($page['rss_feed_url'])): ?>
        <link rel="stylesheet" href="/css/podcast-player.css">
        <link rel="stylesheet" href="/css/podcast-player-controls.css">
    <?php endif; ?>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo h(truncate($page['podcast_description'] ?: 'Link in bio page', 160)); ?>">
    <meta property="og:title" content="<?php echo h($page['podcast_name'] ?: $page['username']); ?>">
    <meta property="og:description" content="<?php echo h(truncate($page['podcast_description'] ?: '', 160)); ?>">
    <meta property="og:image" content="<?php echo h($page['cover_image_url'] ?: ''); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <?php echo $cssGenerator->generateCompleteStyleBlock(); ?>
    
    <!-- Additional widget-specific styles -->
    <style>
        :root {
            --mobile-page-width: min(100vw, 420px);
            --mobile-page-offset: max(0px, calc((100vw - var(--mobile-page-width)) / 2));
            --episode-drawer-width: var(--mobile-page-width);
        }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--shell-background, color-mix(in srgb, #f5f7fb 94%, #0f172a 6%));
        }

        @media (min-width: 600px) {
            body {
                padding: var(--space-xl, 2.5rem) 0;
            }
        }

        /* Page container and layout */
        .page-container {
            width: var(--mobile-page-width);
            max-width: 420px;
            margin: 0 auto;
            padding: var(--space-lg) var(--space-md);
            box-sizing: border-box;
        }
        
        .profile-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-sm);
            margin-bottom: var(--space-lg);
        }
        
        .profile-image {
            width: 7.5rem;
            height: 7.5rem;
            border-radius: 50%;
            object-fit: cover;
            margin-top: var(--space-xl);
            margin-bottom: var(--space-sm);
            border: var(--border-width-regular, 2px) solid var(--color-border-default);
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
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
        
        /* Podcast Top Banner - Attached to drawer bottom */
        .podcast-top-banner {
            position: fixed;
            top: 0;
            left: var(--mobile-page-offset);
            right: auto;
            width: var(--mobile-page-width);
            max-width: 420px;
            box-sizing: border-box;
            background: var(--color-accent-primary);
            background: linear-gradient(135deg, var(--color-accent-primary) 0%, color-mix(in srgb, var(--color-accent-primary) 75%, black 25%) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 10001;
            box-shadow: var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16));
            border-bottom: 1px solid color-mix(in srgb, var(--color-text-inverse) 20%, transparent);
            opacity: 1;
            pointer-events: auto;
            transition: transform var(--motion-duration-standard, 250ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1)), opacity var(--motion-duration-standard, 250ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            /* Banner starts at top of viewport when drawer is closed */
            transform: translateY(0);
        }
        
        /* When drawer opens, banner moves down and hides */
        .podcast-top-banner.drawer-open {
            opacity: 0;
            pointer-events: none;
            transform: translateY(100vh);
        }
        
        /* During peek, banner moves down proportionally */
        .podcast-top-banner.drawer-peek {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(calc(100vh * 0.3));
        }
        
        .podcast-banner-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2xs, 0.25rem);
            width: 100%;
            padding: var(--space-xs, 0.5rem) var(--space-sm, 0.75rem);
            background: transparent;
            color: var(--color-text-on-accent);
            border: none;
            font-size: var(--type-scale-xs, 0.889rem);
            font-weight: var(--type-weight-medium, 500);
            cursor: pointer;
            transition: background var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1)), transform var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            text-align: center;
        }
        
        .podcast-banner-toggle:hover {
            background: color-mix(in srgb, var(--color-text-inverse) 15%, transparent);
        }
        
        .podcast-banner-toggle:active {
            background: color-mix(in srgb, var(--color-text-inverse) 25%, transparent);
        }
        
        .podcast-banner-toggle i:first-child {
            font-size: 11.2px;
        }
        
        .podcast-banner-toggle i:last-child {
            font-size: 8.8px;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }
        
        .podcast-banner-toggle:hover i:last-child {
            transform: translateY(2px);
        }
        
        
        /* Podcast Top Drawer */
        .podcast-top-drawer {
            position: fixed;
            top: 0;
            left: var(--mobile-page-offset);
            right: auto;
            bottom: 0;
            width: var(--mobile-page-width);
            max-width: 420px;
            height: 100vh;
            max-height: 100vh;
            background-color: var(--color-background-surface-raised, rgba(15, 23, 42, 0.95));
            z-index: 10000;
            display: flex;
            flex-direction: column;
            transform: translateY(-100%);
            transition: transform var(--motion-duration-standard, 250ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            overflow: hidden !important;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
            box-sizing: border-box;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
        }
        
        /* Prevent body scrollbars when drawer is open or closing */
        body:has(.podcast-top-drawer.open),
        body:has(.podcast-top-drawer.peek) {
            overflow: hidden !important;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
        }
        
        html:has(.podcast-top-drawer.open),
        html:has(.podcast-top-drawer.peek) {
            overflow: hidden !important;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
        }
        
        
        .podcast-top-drawer.open {
            transform: translateY(0);
        }
        
        .podcast-top-drawer.peek {
            transform: translateY(-70%);
        }
        
        .podcast-drawer-close {
            position: absolute;
            top: var(--space-sm, 0.75rem);
            right: var(--space-sm, 0.75rem);
            width: 2.75rem;
            height: 2.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: color-mix(in srgb, var(--color-text-inverse) 15%, transparent);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: var(--shape-corner-pill, 9999px);
            color: var(--color-text-inverse);
            font-size: 1.25rem;
            cursor: pointer;
            transition: background var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1)), transform var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            z-index: 10;
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
        }
        
        .podcast-drawer-close:hover {
            background-color: color-mix(in srgb, var(--color-text-inverse) 25%, transparent);
            transform: scale(1.1);
        }
        
        .podcast-drawer-close:active {
            transform: scale(0.95);
        }
        
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
        .widget-podcast-custom,
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
        
        /* Podcast Player widget styles */
        .widget-podcast {
            /* Inherits standard widget styling from .widget-item */
            /* No overrides - uses same padding, border, colors, and font as other widgets */
            position: relative;
        }
        
        .widget-podcast .widget-content {
            width: 100%;
        }
        
        .podcast-widget-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        /* PodNBio Player - Custom Compact Widget Styles */
        .widget-podcast-custom {
            position: relative;
            overflow: visible;
            transition: height 0.4s cubic-bezier(0.32, 0.72, 0, 1);
            isolation: isolate;
            z-index: 1;
        }
        
        .widget-podcast-custom .widget-content {
            position: relative;
            overflow: visible;
            z-index: 1;
        }
        
        .podcast-compact-player {
            position: relative;
            padding: 0.875rem;
            min-height: 110px;
            display: flex;
            flex-direction: column;
        }
        
        .podcast-header-compact {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 10;
        }
        
        .rss-icon {
            color: var(--color-accent-primary);
            font-size: 1rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .rss-icon:hover {
            opacity: 1;
        }
        
        .podcast-main-content {
            display: flex;
            gap: 0.875rem;
            align-items: flex-start;
            min-height: 110px;
        }
        
        .podcast-cover-compact {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.02) 100%);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .podcast-info-compact {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }
        
        .podcast-title-compact {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-text-on-surface);
            line-height: 1.25;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            letter-spacing: -0.01em;
        }
        
        .episode-title-compact {
            font-size: 0.8125rem;
            font-weight: 400;
            color: var(--color-text-secondary);
            line-height: 1.3;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .podcast-controls-compact {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            margin-top: 0.375rem;
        }
        
        .skip-back-btn,
        .skip-forward-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            font-size: 0.75rem;
            padding: 0;
            position: relative;
            gap: 0.05rem;
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .skip-back-btn::before,
        .skip-forward-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: color-mix(in srgb, var(--color-text-inverse) 20%, transparent);
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }
        
        .skip-back-btn:hover::before,
        .skip-forward-btn:hover::before {
            width: 100%;
            height: 100%;
        }
        
        .play-pause-btn {
            width: 56px;
            height: 56px;
            font-size: 1.125rem;
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            padding: 0;
            position: relative;
            box-shadow: var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16));
            z-index: 2;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .play-pause-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            transition: width 0.5s ease, height 0.5s ease;
        }
        
        .play-pause-btn:hover::after {
            width: 120%;
            height: 120%;
        }
        
        .skip-label {
            font-size: 0.625rem;
            line-height: 1;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.3px;
            opacity: 0.9;
            z-index: 1;
        }
        
        .skip-back-btn:hover,
        .play-pause-btn:hover,
        .skip-forward-btn:hover {
            transform: scale(1.08) translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18), 0 2px 4px rgba(0, 0, 0, 0.12);
        }
        
        .skip-back-btn:active,
        .play-pause-btn:active,
        .skip-forward-btn:active {
            transform: scale(1.02) translateY(0);
            transition: transform 0.1s ease;
        }
        
        .skip-back-btn i,
        .skip-forward-btn i {
            transition: transform 0.3s ease;
            z-index: 1;
        }
        
        .skip-back-btn:hover i,
        .skip-forward-btn:hover i {
            transform: scale(1.15);
        }
        
        .play-pause-btn i {
            transition: transform 0.3s ease;
            z-index: 1;
        }
        
        .play-pause-btn:hover i {
            transform: scale(1.1);
        }
        
        .volume-btn,
        .expand-drawer-btn {
            width: 32px;
            height: 32px;
            min-width: 32px;
            min-height: 32px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 0, 0, 0.08);
            background: rgba(255, 255, 255, 0.9);
            color: var(--color-accent-primary);
            cursor: pointer;
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            transition: all var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            font-size: 0.8125rem;
            padding: 0;
            position: relative;
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
        }
        
        .expand-drawer-btn {
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
            border-color: var(--color-accent-primary);
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
        }
        
        .expand-drawer-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }
        
        .expand-drawer-btn:hover::before {
            width: 140%;
            height: 140%;
        }
        
        .expand-drawer-btn:hover {
            transform: scale(1.1) translateY(-1px);
            box-shadow: var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16));
        }
        
        .expand-drawer-btn:active {
            transform: scale(1.05) translateY(0);
        }
        
        .drawer-icon-toggle {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            position: relative;
        }
        
        .expand-drawer-btn.active .drawer-icon-toggle {
            transform: rotate(180deg);
        }
        
        .expand-drawer-btn.active {
            background: color-mix(in srgb, var(--color-accent-primary) 90%, transparent);
            opacity: 0.95;
        }
        
        .volume-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(0, 0, 0, 0.12);
            transform: scale(1.08);
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
        }
        
        .volume-btn:active {
            transform: scale(0.96);
        }
        
        .volume-btn i,
        .expand-drawer-btn i {
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .volume-btn:hover i {
            transform: scale(1.2);
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .current-time,
        .total-time {
            font-size: 0.6875rem;
            color: var(--color-text-secondary);
            white-space: nowrap;
            min-width: 36px;
            text-align: center;
            font-variant-numeric: tabular-nums;
            font-weight: 500;
        }
        
        .progress-bar-wrapper {
            flex: 1;
            position: relative;
            height: 32px;
            display: flex;
            align-items: center;
            padding: 0 6px;
        }
        
        .progress-bar {
            position: relative;
            width: calc(100% - 12px);
            height: 4px;
            background: rgba(0, 0, 0, 0.08);
            border-radius: 2px;
            cursor: pointer;
            z-index: 2;
            overflow: visible;
            margin: 0 auto;
        }
        
        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--progress-width, 0%);
            background: var(--color-accent-primary);
            border-radius: 2px;
            transition: width 0.1s linear;
            pointer-events: none;
        }
        
        
        .progress-scrubber {
            position: absolute;
            top: 50%;
            left: var(--progress-width, 0%);
            width: 12px;
            height: 12px;
            background: var(--color-text-on-background);
            border-radius: 50%;
            border: 2px solid var(--color-accent-primary);
            cursor: grab;
            z-index: 10;
            transform: translate(-50%, -50%);
            transition: left 0.1s linear, transform 0.2s ease, box-shadow 0.2s ease;
            pointer-events: auto;
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
            touch-action: none;
        }
        
        .progress-scrubber:active {
            cursor: grabbing;
            transform: translate(-50%, -50%) scale(1.4);
            box-shadow: var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16));
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        
        .progress-scrubber.dragging {
            transition: none;
        }
        
        .progress-bar-wrapper:hover .progress-scrubber {
            transform: translate(-50%, -50%) scale(1.3);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
        
        .progress-bar-wrapper:hover .progress-fill {
            opacity: 0.9;
        }
        
        /* Compact Drawer Tray - Expands from widget, pushes content down */
        .podcast-bottom-sheet {
            position: relative;
            width: 100%;
            height: 0;
            overflow: hidden;
            background: var(--color-background-surface, var(--color-background-base));
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            margin-top: 0.5rem;
            transition: height 0.4s cubic-bezier(0.32, 0.72, 0, 1);
            will-change: height;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            z-index: 1;
        }
        
        .podcast-bottom-sheet:not(.hidden) {
            height: 400px;
        }
        
        .drawer-backdrop {
            display: none; /* No backdrop needed for compact tray */
        }
        
        .drawer-content-wrapper {
            padding: 0;
            max-height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .drawer-drag-handle {
            width: 36px;
            height: 5px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
            margin: 12px auto 8px;
            cursor: grab;
            transition: background 0.2s ease;
        }
        
        .drawer-drag-handle:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .drawer-tabs {
            display: flex;
            gap: 0;
            margin: 0;
            padding: 0 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            background: rgba(0, 0, 0, 0.02);
        }
        
        .tab-btn {
            flex: 1;
            padding: 14px 16px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--text-color);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            opacity: 0.6;
        }
        
        .tab-btn::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 60%;
            height: 3px;
            background: var(--color-accent-primary);
            border-radius: 3px 3px 0 0;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .tab-btn.active {
            opacity: 1;
            color: var(--color-accent-primary);
        }
        
        .tab-btn.active::after {
            transform: translateX(-50%) scaleX(1);
        }
        
        .tab-btn:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.02);
        }
        
        .drawer-panels {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
            min-height: 0;
        }
        
        .drawer-panels::-webkit-scrollbar {
            width: 6px;
        }
        
        .drawer-panels::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .drawer-panels::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .drawer-panels::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .tab-panel {
            display: none;
            animation: fadeIn 0.25s ease-in-out;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .show-notes-content {
            color: var(--text-color);
            line-height: 1.65;
            font-size: 0.9375rem;
            padding: 0.5rem 0;
        }
        
        .chapters-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .chapter-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 6px;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .chapter-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background: var(--color-accent-primary);
            border-radius: 0 2px 2px 0;
            transition: height 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chapter-item:hover {
            background: rgba(0, 0, 0, 0.04);
            transform: translateX(4px);
        }
        
        .chapter-item.active {
            background: color-mix(in srgb, var(--color-accent-primary) 10%, transparent);
            border-color: color-mix(in srgb, var(--color-accent-primary) 35%, transparent);
        }
        
        .chapter-item.active::before {
            height: 60%;
        }
        
        .chapter-time {
            font-weight: 600;
            color: var(--color-accent-primary);
            min-width: 56px;
            font-size: 0.8125rem;
            font-variant-numeric: tabular-nums;
        }
        
        .chapter-title {
            flex: 1;
            color: var(--text-color);
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        .chapters-empty {
            color: var(--text-color);
            opacity: 0.5;
            text-align: center;
            padding: 3rem 1rem;
            font-size: 0.875rem;
        }
        
        .episodes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .episode-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid transparent;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .episode-item::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .episode-item:hover {
            background: rgba(0, 0, 0, 0.04);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .episode-item:hover::after {
            left: 100%;
        }
        
        .episode-item.active {
            background: rgba(0, 102, 255, 0.08);
            border-color: rgba(0, 102, 255, 0.2);
            box-shadow: 0 0 0 1px rgba(0, 102, 255, 0.1);
        }
        
        .episode-thumbnail {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        
        .episode-item:hover .episode-thumbnail {
            transform: scale(1.05);
        }
        
        .episode-info {
            flex: 1;
            min-width: 0;
        }
        
        .episode-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-color);
            font-size: 0.9375rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .episode-desc {
            font-size: 0.8125rem;
            color: var(--text-color);
            opacity: 0.6;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Follow Tab Styles */
        .follow-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
            padding: 0.5rem 0;
        }
        
        .follow-button {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.95);
            border: 1.5px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            color: var(--color-accent-primary);
            text-decoration: none;
            transition: all var(--motion-duration-fast, 150ms) var(--motion-easing-standard, cubic-bezier(0.4,0,0.2,1));
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: var(--shadow-level-1, 0 2px 6px rgba(15, 23, 42, 0.12));
            position: relative;
            overflow: hidden;
        }
        
        .follow-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--color-accent-primary);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .follow-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            border-color: var(--color-accent-primary);
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
        }
        
        .follow-button:hover::before {
            opacity: 0.1;
        }
        
        .follow-button i {
            font-size: 1.125rem;
            width: 20px;
            text-align: center;
            position: relative;
            z-index: 1;
            transition: transform 0.2s ease;
        }
        
        .follow-button:hover i {
            transform: scale(1.1);
        }
        
        .follow-button-label {
            position: relative;
            z-index: 1;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .follow-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--text-color);
            opacity: 0.6;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .follow-buttons {
                grid-template-columns: 1fr;
                gap: 0.625rem;
            }
            
            .follow-button {
                padding: 1rem 1.125rem;
            }
            
            .podcast-compact-player {
                padding: 0.75rem;
                min-height: 110px;
            }
            
            .podcast-main-content {
                min-height: 110px;
                gap: 0.75rem;
            }
            
            .podcast-cover-compact {
                width: 90px;
                height: 90px;
            }
            
            .skip-back-btn,
            .skip-forward-btn {
                width: 36px;
                height: 36px;
                font-size: 0.6875rem;
            }
            
            .play-pause-btn {
                width: 52px;
                height: 52px;
                font-size: 1rem;
            }
            
            .expand-drawer-btn {
                width: 30px;
                height: 30px;
                font-size: 0.75rem;
            }
            
            .podcast-bottom-sheet {
                max-height: 90vh;
                border-radius: 0;
            }
        }
        
        /* Minimal Collapsed View */
        .podcast-widget-minimal {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }
        
        .podcast-widget-minimal .podcast-cover {
            width: 80px;
            height: 80px;
            border-radius: var(--shape-corner-md, 0.75rem);
            object-fit: cover;
            flex-shrink: 0;
            background: var(--color-background-surface, var(--color-background-base));
        }
        
        .podcast-widget-minimal .podcast-info {
            flex: 1;
            min-width: 0;
        }
        
        .podcast-widget-minimal .podcast-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .podcast-widget-minimal .episode-title {
            font-size: 0.875rem;
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .podcast-widget-minimal .minimal-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }
        
        .podcast-widget-minimal .minimal-play-pause {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--color-accent-primary);
            background: var(--color-background-surface, rgba(255, 255, 255, 0.95));
            color: var(--color-accent-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .podcast-widget-minimal .minimal-play-pause:hover {
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
            transform: scale(1.05);
        }
        
        .podcast-widget-minimal .minimal-progress {
            flex: 1;
            height: 4px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }
        
        .podcast-widget-minimal .minimal-progress-bar {
            height: 100%;
            background: var(--color-accent-primary);
            border-radius: 2px;
            transition: width 0.1s linear;
            width: 0%;
        }
        
        .podcast-widget-minimal .minimal-time {
            font-size: 0.75rem;
            color: var(--text-color);
            opacity: 0.7;
            min-width: 40px;
            text-align: right;
            flex-shrink: 0;
        }
        
        .podcast-widget-minimal .minimal-expand {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--color-accent-primary);
            background: transparent;
            color: var(--color-accent-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .podcast-widget-minimal .minimal-expand:hover {
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
        }
        
        .podcast-error {
            padding: 1rem;
            color: var(--color-state-danger);
            text-align: center;
        }
        
        /* Expanded Drawer */
        .podcast-widget-drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--color-background-surface, var(--color-background-base));
            border-top: 2px solid var(--color-accent-primary);
            border-radius: 20px 20px 0 0;
            max-height: 85vh;
            overflow-y: auto;
            z-index: 1000;
            transform: translateY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            will-change: transform;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        
        .podcast-widget-drawer.hidden {
            transform: translateY(100%);
            pointer-events: none;
            visibility: hidden;
        }
        
        .podcast-widget-drawer .drawer-header {
            display: flex;
            justify-content: flex-end;
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background: var(--color-background-surface, var(--color-background-base));
            z-index: 10;
        }
        
        .podcast-widget-drawer .drawer-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--color-accent-primary);
            background: transparent;
            color: var(--color-accent-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .podcast-widget-drawer .drawer-close:hover {
            background: var(--color-accent-primary);
            color: var(--color-text-on-accent);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .podcast-widget-minimal {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .podcast-widget-minimal .podcast-cover {
                width: 100%;
                height: auto;
                max-height: 200px;
            }
            
            .podcast-widget-drawer {
                max-height: 90vh;
                border-radius: 0;
            }
            
            .podcast-header-full {
                flex-direction: column;
            }
            
            .podcast-cover-full {
                width: 100%;
                height: auto;
                max-height: 250px;
            }
        }
        
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
        
        .episode-drawer {
            position: fixed;
            top: 0;
            right: calc(-1 * (var(--mobile-page-offset) + var(--episode-drawer-width, min(90vw, 26rem))));
            width: var(--episode-drawer-width, min(90vw, 26rem));
            max-width: var(--episode-drawer-width, min(90vw, 26rem));
            height: 100vh;
            background: var(--color-background-surface-raised, var(--color-background-base));
            border-left: 2px solid var(--color-accent-primary);
            box-shadow: -4px 0 12px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }
        
        .episode-drawer.open {
            right: var(--mobile-page-offset);
        }
        
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
        
        @media (max-width: 600px) {
            .page-container {
                padding: 1rem;
            }
            
            .profile-image {
                width: 100px;
                height: 100px;
            }
            
        }

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

        /* Podcast banner and toggle */
        body.theme-aurora-skies .podcast-top-banner {
            background: var(--gradient-podcast, linear-gradient(135deg, #040610 0%, #101730 65%, #1c2854 100%));
            border-bottom: 1px solid rgba(122, 255, 216, 0.18);
            box-shadow: 0 26px 60px rgba(3, 6, 30, 0.55);
        }

        body.theme-aurora-skies .podcast-banner-toggle {
            color: var(--color-text-on-accent);
            text-transform: uppercase;
            letter-spacing: 0.16em;
            background: transparent;
            font-weight: 600;
            text-shadow: 0 0 6px rgba(0, 0, 0, 0.35);
        }

        body.theme-aurora-skies .podcast-banner-toggle:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--color-text-on-accent);
        }

        /* Podcast drawer (dark mode enforced) */
        body.theme-aurora-skies .podcast-top-drawer,
        body.theme-aurora-skies .episode-drawer,
        body.theme-aurora-skies .podcast-bottom-sheet {
            background: linear-gradient(160deg, rgba(8, 10, 25, 0.98) 0%, rgba(12, 18, 42, 0.95) 65%, rgba(9, 14, 30, 0.92) 100%);
            color: #f4f8ff;
            border-top: 1px solid rgba(122, 255, 216, 0.15);
        }

        body.theme-aurora-skies .podcast-drawer-close,
        body.theme-aurora-skies .drawer-close,
        body.theme-aurora-skies .episode-drawer .drawer-close {
            color: color-mix(in srgb, var(--color-accent-primary) 80%, #ffffff 20%);
        }

        body.theme-aurora-skies .podcast-compact-player {
            background: rgba(8, 12, 30, 0.92);
            border: 1px solid rgba(122, 255, 216, 0.12);
            box-shadow: 0 22px 54px rgba(3, 6, 24, 0.45);
        }

        body.theme-aurora-skies .podcast-title-compact {
            color: #f4f8ff;
        }

        body.theme-aurora-skies .episode-title-compact {
            color: rgba(207, 215, 255, 0.85);
        }

        body.theme-aurora-skies .play-pause-btn,
        body.theme-aurora-skies .skip-back-btn,
        body.theme-aurora-skies .skip-forward-btn {
            background: var(--gradient-accent, var(--color-accent-primary));
            color: var(--color-text-on-accent);
            border: none;
        }

        body.theme-aurora-skies .play-pause-btn {
            box-shadow: 0 12px 30px rgba(8, 16, 52, 0.55);
        }

        body.theme-aurora-skies .progress-bar {
            background: rgba(122, 255, 216, 0.2);
        }

        body.theme-aurora-skies .progress-fill {
            background: var(--gradient-accent, var(--color-accent-primary));
            box-shadow: 0 0 12px rgba(122, 255, 216, 0.45);
        }

        body.theme-aurora-skies .progress-scrubber {
            border-color: rgba(122, 255, 216, 0.6);
            background: #ffffff;
        }

        body.theme-aurora-skies .volume-btn,
        body.theme-aurora-skies .expand-drawer-btn {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            color: var(--color-accent-primary);
        }

        @keyframes auroraPulse {
            0%, 100% { box-shadow: 0 12px 32px rgba(8, 16, 52, 0.4), 0 0 14px rgba(122, 255, 216, 0.25); }
            50% { box-shadow: 0 14px 36px rgba(8, 16, 52, 0.55), 0 0 20px rgba(122, 255, 216, 0.4); }
        }

        @keyframes auroraFlow {
            0% { transform: translate3d(-2%, 0, 0) scale(1); opacity: 0.75; }
            50% { transform: translate3d(2%, -1%, 0) scale(1.03); opacity: 1; }
            100% { transform: translate3d(-2%, 0, 0) scale(1); opacity: 0.75; }
        }

        body.theme-aurora-skies .podcast-top-banner::before {
            content: "";
            position: absolute;
            inset: -30%;
            background: radial-gradient(circle at 50% 20%, color-mix(in srgb, var(--aurora-glow-color, var(--color-accent-primary)) 45%, transparent) 0%, rgba(0,0,0,0) 65%);
            opacity: 0.45;
            animation: auroraFlow 14s ease-in-out infinite;
            pointer-events: none;
        }
    </style>
</head>
<body class="<?php echo trim($cssGenerator->getSpatialEffectClass() . ' ' . $themeBodyClass); ?>">
    <div class="page-container">
        <div class="profile-header">
            <?php if ($page['profile_image']): ?>
                <img src="<?php echo h($page['profile_image']); ?>" alt="Profile" class="profile-image">
            <?php endif; ?>
            
            <?php if ($page['cover_image_url']): ?>
                <img src="<?php echo h($page['cover_image_url']); ?>" alt="Cover" class="cover-image">
            <?php endif; ?>
            
            <?php
            $pageNameEffect = $page['page_name_effect'] ?? '';
            $pageTitleText = h($page['podcast_name'] ?: $page['username']);
            
            if ($pageNameEffect === 'slashed'): ?>
                <div class="page-title-effect-slashed">
                    <div class="top" title="<?php echo $pageTitleText; ?>"></div>
                    <div class="bot" title="<?php echo $pageTitleText; ?>"></div>
                </div>
            <?php elseif ($pageNameEffect === 'sweet-title'): ?>
                <div class="page-title-effect-sweet-title">
                    <div class="sweet-title">
                        <?php
                        $words = explode(' ', $pageTitleText);
                        foreach ($words as $index => $word): ?>
                            <span data-text="<?php echo h($word); ?>"><?php echo h($word); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($pageNameEffect === 'dragon-text'): ?>
                <div class="page-title-effect-dragon-text">
                    <svg class="svg-text" viewBox="0 0 1000 300">
                        <defs>
                            <linearGradient id="textGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:var(--color-text-inverse);stop-opacity:1" />
                                <stop offset="100%" style="stop-color:var(--color-text-inverse);stop-opacity:0.85" />
                            </linearGradient>
                        </defs>
                        <text x="500" y="200" text-anchor="middle" class="svg-text__shaded" fill="url(#textGradient)">
                            <?php echo $pageTitleText; ?>
                            <tspan class="svg-text__shaded__sub"><?php echo $pageTitleText; ?></tspan>
                        </text>
                    </svg>
                </div>
            <?php elseif ($pageNameEffect === 'water'): ?>
                <h2 class="page-title-effect-water" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h2>
            <?php elseif ($pageNameEffect === 'glitch'): ?>
                <h1 class="page-title page-title-effect-glitch" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h1>
            <?php elseif ($pageNameEffect === 'cut-text'): ?>
                <h1 class="page-title page-title-effect-cut-text" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h1>
            <?php elseif ($pageNameEffect === 'cyber-text'): ?>
                <h1 class="page-title page-title-effect-cyber-text" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h1>
            <?php elseif ($pageNameEffect === 'stencil'): ?>
                <h1 class="page-title-effect-stencil" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h1>
            <?php else: ?>
                <h1 class="page-title <?php echo $pageNameEffect ? 'page-title-effect-' . h($pageNameEffect) : ''; ?>"><?php echo $pageTitleText; ?></h1>
            <?php endif; ?>
            
            <?php if ($page['podcast_description']): ?>
                <p class="page-description"><?php echo nl2br(h($page['podcast_description'])); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Podcast Player Top Banner (positioned independently, moves with drawer) -->
        <?php if (!empty($page['rss_feed_url'])): ?>
            <div class="podcast-top-banner" id="podcast-top-banner">
                <button class="podcast-banner-toggle" id="podcast-drawer-toggle" aria-label="Open Podcast Player" title="Open Podcast Player">
                    <i class="fas fa-podcast"></i>
                    <span>Tap to Listen</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Podcast Player Top Drawer -->
        <?php if (!empty($page['rss_feed_url'])): ?>
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
                                <!-- Close Button Overlay -->
                                <button class="podcast-drawer-close" id="podcast-drawer-close" aria-label="Close Podcast Player">
                                    <i class="fas fa-times"></i>
                                </button>
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
                        // All icons use Font Awesome for consistency
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
                            'medium' => '<i class="fab fa-medium"></i>'
                        ];
                        $iconHtml = $platformIcons[strtolower($icon['platform_name'])] ?? '<i class="fas fa-link"></i>';
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
                        $rendered = WidgetRenderer::render($widget);
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
                            <img src="<?php echo h($link['thumbnail_image']); ?>" 
                                 alt="<?php echo h($link['title']); ?>" 
                                 class="widget-thumbnail">
                        <?php endif; ?>
                        <div class="widget-content">
                            <div class="widget-title"><?php echo h($link['title']); ?></div>
                        </div>
                    </a>
                <?php endforeach;
            endif; ?>
        </div>
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
    
    <!-- Podcast Player JavaScript (only load if RSS feed exists) -->
    <?php if (!empty($page['rss_feed_url'])): ?>
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
                setTimeout(function() {
                    PodcastDrawerController.peekDrawer();
                }, 4000);
                
                // Expose controller to window for debugging (optional)
                window.PodcastDrawerController = PodcastDrawerController;
            })();
        </script>
    <?php endif; ?>
    </body>
</html>

