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
        /* Page container and layout */
        .page-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 36px;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-color);
        }
        
        .cover-image {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        .page-title {
            font-size: 2rem;
            margin: 0.5rem 0;
            color: var(--page-title-color, var(--primary-color));
        }
        
        .page-description {
            color: var(--page-description-color, var(--primary-color));
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        /* Podcast Top Banner - Attached to drawer bottom */
        .podcast-top-banner {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color, #0066ff) 0%, rgba(0, 102, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 10001;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }
        
        .podcast-top-drawer.open .podcast-top-banner {
            transform: translateY(100%);
            opacity: 0;
            pointer-events: none;
        }
        
        .podcast-top-drawer:not(.open) .podcast-top-banner {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        
        .podcast-banner-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            background: transparent;
            color: #FFFFFF;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        
        .podcast-banner-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .podcast-banner-toggle:active {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .podcast-banner-toggle i:first-child {
            font-size: 14px;
        }
        
        .podcast-banner-toggle i:last-child {
            font-size: 11px;
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
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #000000;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            overflow: hidden;
        }
        
        .podcast-top-drawer .podcast-top-banner {
            position: absolute;
            bottom: 0;
        }
        
        .podcast-top-drawer.open {
            transform: translateY(0);
        }
        
        .podcast-top-drawer.peek {
            transform: translateY(-70%);
        }
        
        .podcast-drawer-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            color: #FFFFFF;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .podcast-drawer-close:hover {
            background-color: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        
        .podcast-drawer-close:active {
            transform: scale(0.95);
        }
        
        /* Page Name Effects */
        .page-title-effect-3d-shadow {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(1.5rem, 8vw, 4rem);
            letter-spacing: clamp(0.5rem, 2vw, 1rem);
            color: #fff;
            text-shadow:
                2px 2px 0 #f44336,
                4px 4px 0 #e91e63,
                6px 6px 0 #9c27b0,
                8px 8px 0 #673ab7,
                10px 10px 0 #3f51b5,
                12px 12px 0 #2196f3,
                14px 14px 0 #03a9f4,
                16px 16px 0 #00bcd4;
        }
        
        .page-title-effect-stroke-shadow {
            font-size: clamp(2rem, calc(1em + 15vmin), 5rem);
            font-weight: 900;
            color: tomato;
            --x-offset: -0.0625em;
            --y-offset: 0.0625em;
            --stroke: 0.025em;
            --background-color: white;
            --stroke-color: lightblue;
            text-shadow: 
                var(--x-offset)
                var(--y-offset)
                0px
                var(--background-color), 
                calc(var(--x-offset) - var(--stroke))
                calc(var(--y-offset) + var(--stroke))
                0px
                var(--stroke-color);
        }
        
        @supports (text-shadow: 1px 1px 1px 1px black) {
            .page-title-effect-stroke-shadow {
                text-shadow:
                    var(--x-offset)
                    var(--y-offset)
                    0px
                    0px
                    var(--background-color), 
                    var(--x-offset) 
                    var(--y-offset)
                    var(--stroke)
                    0px
                    var(--stroke-color);
            }
        }
        
        .page-title-effect-slashed {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        
        .page-title-effect-slashed .top,
        .page-title-effect-slashed .bot {
            position: absolute;
            text-align: center;
            font-size: clamp(2rem, 8vw, 4rem);
            text-transform: uppercase;
            overflow: hidden;
            color: white;
            text-shadow: 3px 3px 3px rgba(0, 0, 0, 0.5);
        }
        
        .page-title-effect-slashed .top {
            top: 0;
            left: 5px;
            right: 0;
            bottom: 50%;
        }
        
        .page-title-effect-slashed .top::before {
            content: attr(title);
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            transform: rotate(5deg);
        }
        
        .page-title-effect-slashed .bot {
            top: 50%;
            left: 0;
            right: 5px;
            bottom: 0;
        }
        
        .page-title-effect-slashed .bot::before {
            content: attr(title);
            position: absolute;
            top: -50px;
            left: 0;
            right: 0;
            transform: rotate(5deg);
        }
        
        .page-title-effect-sweet-title {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-title-effect-sweet-title .sweet-title {
            color: #fde9ff;
            font-weight: 900;
            text-transform: uppercase;
            font-size: clamp(2rem, 10vw, 4rem);
            line-height: 0.75em;
            text-align: center;
            font-family: 'Exo 2', sans-serif;
            text-shadow: 3px 1px 1px #4af7ff, 2px 2px 1px #165bfb, 4px 2px 1px #4af7ff,
                3px 3px 1px #165bfb, 5px 3px 1px #4af7ff, 4px 4px 1px #165bfb,
                6px 4px 1px #4af7ff, 5px 5px 1px #165bfb, 7px 5px 1px #4af7ff,
                6px 6px 1px #165bfb, 8px 6px 1px #4af7ff, 7px 7px 1px #165bfb,
                9px 7px 1px #4af7ff;
        }
        
        .page-title-effect-sweet-title .sweet-title span {
            display: block;
            position: relative;
        }
        
        .page-title-effect-sweet-title .sweet-title span::before {
            content: attr(data-text);
            position: absolute;
            text-shadow: 2px 2px 1px #e94aa1, -1px -1px 1px #c736f9,
                -2px 2px 1px #e94aa1, 1px -1px 1px #f736f9;
            z-index: 1;
        }
        
        .page-title-effect-sweet-title .sweet-title span:nth-child(1) {
            padding-right: 2.25rem;
        }
        
        .page-title-effect-sweet-title .sweet-title span:nth-child(2) {
            padding-left: 2.25rem;
        }
        
        .page-title-effect-long-shadow {
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 700;
            color: #fff;
            text-shadow: 
                0px 0px #E13E06,
                1px 1px #E13E06,
                2px 2px #E13E06,
                3px 3px #E13E06,
                4px 4px #E13E06,
                5px 5px #E13E06,
                6px 6px #E13E06,
                7px 7px #E13E06,
                8px 8px #E13E06,
                9px 9px #E13E06,
                10px 10px #E13E06,
                11px 11px #E13E06,
                12px 12px #E13E06,
                13px 13px #E13E06,
                14px 14px #E13E06,
                15px 15px #E13E06,
                16px 16px #E13E06,
                17px 17px #E13E06,
                18px 18px #E13E06,
                19px 19px #E13E06,
                20px 20px #E13E06,
                21px 21px #E13E06,
                22px 22px #E13E06,
                23px 23px #E13E06,
                24px 24px #E13E06,
                25px 25px #E13E06,
                26px 26px #E13E06,
                27px 27px #E13E06,
                28px 28px #E13E06,
                29px 29px #E13E06,
                30px 30px #E13E06,
                31px 31px #E13E06,
                32px 32px #E13E06,
                33px 33px #E13E06,
                34px 34px #E13E06,
                35px 35px #E13E06,
                36px 36px #E13E06,
                37px 37px #E13E06,
                38px 38px #E13E06,
                39px 39px #E13E06,
                40px 40px #E13E06,
                41px 41px #E13E06,
                42px 42px #E13E06,
                43px 43px #E13E06,
                44px 44px #E13E06,
                45px 45px #E13E06,
                46px 46px #E13E06,
                47px 47px #E13E06,
                48px 48px #E13E06,
                49px 49px #E13E06,
                50px 50px #E13E06;
        }
        
        .page-title-effect-3d-extrude {
            font-family: 'Bowlby One SC', sans-serif;
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 400;
            color: hsl(0, 100%, 55%);
            text-transform: uppercase;
            text-shadow: 
                1px 1px 0 hsl(200, 100%, 15%),
                2px 2px 0 hsl(200, 100%, 15%),
                3px 3px 0 hsl(200, 100%, 15%),
                4px 4px 0 hsl(200, 100%, 15%),
                5px 5px 0 hsl(200, 100%, 15%),
                6px 6px 0 hsl(200, 100%, 15%),
                7px 7px 0 hsl(200, 100%, 15%),
                8px 8px 0 hsl(200, 100%, 15%),
                9px 9px 0 hsl(200, 100%, 15%),
                10px 10px 0 hsl(200, 100%, 15%),
                11px 11px 0 hsl(200, 100%, 15%),
                12px 12px 0 hsl(200, 100%, 15%),
                13px 13px 0 hsl(200, 100%, 15%),
                14px 14px 0 hsl(200, 100%, 15%),
                15px 15px 0 hsl(200, 100%, 15%),
                16px 16px 0 hsl(200, 100%, 15%),
                17px 17px 0 hsl(200, 100%, 15%),
                18px 18px 0 hsl(200, 100%, 15%),
                19px 19px 0 hsl(200, 100%, 15%),
                20px 20px 0 hsl(200, 100%, 15%),
                21px 21px 0 hsl(200, 100%, 15%),
                22px 22px 0 hsl(200, 100%, 15%),
                23px 23px 0 hsl(200, 100%, 15%),
                24px 24px 0 hsl(200, 100%, 15%),
                25px 25px 0 hsl(200, 100%, 15%),
                26px 26px 0 hsl(200, 100%, 15%),
                27px 27px 0 hsl(200, 100%, 15%),
                28px 28px 0 hsl(200, 100%, 15%),
                29px 29px 0 hsl(200, 100%, 15%),
                30px 30px 0 hsl(200, 100%, 15%),
                31px 31px 0 hsl(200, 100%, 15%),
                32px 32px 0 hsl(200, 100%, 15%),
                33px 33px 0 hsl(200, 100%, 15%),
                34px 34px 0 hsl(200, 100%, 15%),
                35px 35px 0 hsl(200, 100%, 15%),
                36px 36px 0 hsl(200, 100%, 15%),
                37px 37px 0 hsl(200, 100%, 15%),
                38px 38px 0 hsl(200, 100%, 15%),
                39px 39px 0 hsl(200, 100%, 15%),
                40px 40px 0 hsl(200, 100%, 15%),
                41px 41px 0 hsl(200, 100%, 15%),
                42px 42px 0 hsl(200, 100%, 15%),
                43px 43px 0 hsl(200, 100%, 15%),
                44px 44px 0 hsl(200, 100%, 15%),
                45px 45px 0 hsl(200, 100%, 15%);
            transform: translate3d(-15px, -15px, 0);
            position: relative;
            z-index: 1;
        }
        
        .page-title-effect-dragon-text {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
        }
        
        .page-title-effect-dragon-text .svg-text {
            width: 100%;
            max-width: 800px;
            height: auto;
        }
        
        .page-title-effect-dragon-text .svg-text__shaded {
            font-family: 'Open Sans', sans-serif;
            font-size: 120px;
            font-weight: 300;
            fill: #f0f0f0;
            text-shadow: 0 1px 1px rgba(33, 33, 33, 0.15),
                0 3px 10px rgba(33, 33, 33, 0.15),
                0 3px 20px rgba(33, 33, 33, 0.35);
        }
        
        .page-title-effect-dragon-text .svg-text__shaded__sub {
            font-size: 6px;
            font-family: 'Open Sans', sans-serif;
        }
        
        .page-title-effect-dragon-text .svg-text__shaded__stroke {
            stroke-dasharray: 3em 0.5em;
            stroke-dashoffset: 0;
            transition: all 0.6s ease-in-out;
        }
        
        .page-title-effect-dragon-text:hover .svg-text__shaded__stroke {
            animation: offsetAnim 4.2s ease-in-out infinite;
        }
        
        @keyframes offsetAnim {
            70%, 100% {
                stroke-dashoffset: 3.5em;
            }
        }
        
        /* Jello Effect */
        .page-title-effect-jello {
            font-family: system-ui, sans-serif;
            font-size: clamp(1.5rem, 1em + 30vmin, 4rem);
            font-weight: 900;
            color: tomato;
            --x-offset: -0.0625em;
            --y-offset: 0.0625em;
            --stroke: 0.025em;
            --background-color: white;
            --stroke-color: lightblue;
            text-shadow:
                var(--x-offset)
                var(--y-offset)
                0px
                var(--background-color),
                calc( var(--x-offset) - var(--stroke) )
                calc( var(--y-offset) + var(--stroke) )
                0px
                var(--stroke-color);
        }
        @supports ( text-shadow: 1px 1px 1px 1px black ) {
            .page-title-effect-jello {
                text-shadow:
                    var(--x-offset)
                    var(--y-offset)
                    0px
                    0px
                    var(--background-color),
                    var(--x-offset)
                    var(--y-offset)
                    var(--stroke)
                    0px
                    var(--stroke-color);
            }
        }
        
        /* Neon Effect */
        .page-title-effect-neon {
            font-family: 'Exo 2', sans-serif;
            font-size: clamp(3rem, 13vw, 8rem);
            font-weight: 200;
            font-style: italic;
            color: #fff;
            padding: 1rem 2rem 1.5rem;
            border: 0.2rem solid #fff;
            border-radius: 1rem;
            text-transform: uppercase;
            animation: flicker 1.5s infinite alternate;
            --neon-text-color: #f40;
            --neon-border-color: #08f;
        }
        @keyframes flicker {
            0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% {
                text-shadow:
                    -0.1rem -0.1rem 0.5rem #fff,
                    0.1rem 0.1rem 0.5rem #fff,
                    0 0 1rem var(--neon-text-color),
                    0 0 2rem var(--neon-text-color),
                    0 0 3rem var(--neon-text-color),
                    0 0 4rem var(--neon-text-color),
                    0 0 5rem var(--neon-text-color);
                box-shadow:
                    0 0 .25rem #fff,
                    inset 0 0 .25rem #fff,
                    0 0 1rem var(--neon-border-color),
                    inset 0 0 1rem var(--neon-border-color),
                    0 0 2rem var(--neon-border-color),
                    inset 0 0 2rem var(--neon-border-color);
            }
            20%, 24%, 55% {
                text-shadow: none;
                box-shadow: none;
            }
        }
        .page-title-effect-neon::selection {
            background-color: var(--neon-text-color);
            text-shadow: none;
        }
        
        /* Gummy Effect */
        .page-title-effect-gummy {
            font-family: system-ui, sans-serif;
            font-size: clamp(3rem, 15vw, 8rem);
            letter-spacing: -.15ch;
            line-height: .75;
            --hue: 320;
            /* Fallback colors for browsers that don't support oklch */
            color: #e91e63;
            text-shadow:
                0 .15ch 15px rgba(0, 0, 0, 0.3),
                0 -2px 0 rgba(255, 255, 255, 0.9);
        }
        @supports (color: oklch(0% 0 0)) {
            .page-title-effect-gummy {
                --bg: oklch(35% .3 var(--hue));
                --text: oklch(85% .1 var(--hue));
                --shadow: oklch(25% .2 var(--hue));
                --highlight: oklch(98% .05 var(--hue));
                color: var(--text);
                text-shadow:
                    0 .15ch 15px var(--shadow),
                    0 -2px 0 var(--highlight);
            }
        }
        
        /* Water Effect */
        .page-title-effect-water {
            position: relative;
            font-family: "Poppins", sans-serif;
            font-size: clamp(3em, 8vw, 8em);
            color: #fff;
            -webkit-text-stroke: 2px #03a9f4;
        }
        .page-title-effect-water::before {
            content: attr(data-text);
            position: absolute;
            color: #03a9f4;
            animation: animate-water 4s ease-in-out infinite;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        @keyframes animate-water {
            0%, 100% {
                clip-path: polygon(0% 45%, 16% 44%, 33% 50%, 54% 60%, 70% 61%, 84% 59%, 100% 52%, 100% 100%, 0% 100%);
            }
            50% {
                clip-path: polygon(0% 60%, 15% 65%, 34% 66%, 51% 62%, 67% 50%, 84% 45%, 100% 46%, 100% 100%, 0% 100%);
            }
        }
        
        /* Outline Effect */
        .page-title-effect-outline {
            font-family: 'Raleway', sans-serif;
            font-size: clamp(3rem, 12vw, 120px);
            letter-spacing: 0.1em;
            -webkit-text-fill-color: transparent;
            -webkit-text-stroke-width: 3px;
            -webkit-text-stroke-color: white;
            text-shadow:
                8px 8px #ff1f8f,
                20px 20px #000000;
        }
        
        /* Rainbow/Layered Effect */
        .page-title-effect-rainbow {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: clamp(5vw, 20vw, 10rem);
            text-align: center;
            line-height: 1;
            margin: 0;
            position: relative;
            color: var(--page-title-color, var(--primary-color));
        }
        .page-title-effect-rainbow::before,
        .page-title-effect-rainbow::after,
        .page-title-effect-rainbow span::before,
        .page-title-effect-rainbow span::after {
            content: attr(data-heading);
            position: absolute;
            overflow: hidden;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
        }
        .page-title-effect-rainbow::before {
            color: #d61c5b;
            height: 34%;
            z-index: 5;
        }
        .page-title-effect-rainbow::after {
            color: #f1573f;
            height: 50%;
            z-index: 4;
        }
        .page-title-effect-rainbow span {
            position: relative;
            display: inline-block;
        }
        .page-title-effect-rainbow span::before {
            color: #feb213;
            height: 66%;
            z-index: 3;
        }
        .page-title-effect-rainbow span::after {
            color: #6abc4e;
            height: 80%;
            z-index: 2;
        }
        
        /* Badge Shield Effect */
        .page-title-effect-badge-shield {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(2rem, 10vw, 5rem);
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 3rem;
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.3),
                inset 0 -3px 10px rgba(0, 0, 0, 0.2),
                inset 0 3px 10px rgba(255, 255, 255, 0.2);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            display: inline-block;
        }
        
        /* Isometric 3D Effect */
        .page-title-effect-isometric-3d {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(2.5rem, 12vw, 6rem);
            font-weight: 900;
            text-transform: uppercase;
            color: #fff;
            transform: skewY(-5deg) rotateX(10deg);
            text-shadow:
                1px 1px 0 #667eea,
                2px 2px 0 #667eea,
                3px 3px 0 #764ba2,
                4px 4px 0 #764ba2,
                5px 5px 0 #764ba2,
                6px 6px 0 #764ba2,
                7px 7px 0 #764ba2,
                8px 8px 10px rgba(0, 0, 0, 0.4);
            letter-spacing: 0.05em;
        }
        
        /* Geometric Cutout Effect */
        .page-title-effect-geometric-cutout {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(3rem, 14vw, 7rem);
            font-weight: 900;
            text-transform: uppercase;
            color: #000;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }
        .page-title-effect-geometric-cutout::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            height: 60%;
            background: linear-gradient(45deg, transparent 40%, #fff 40%, #fff 60%, transparent 60%);
            mix-blend-mode: screen;
            z-index: 10;
        }
        
        /* Stencil Effect */
        .page-title-effect-stencil {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(3rem, 14vw, 7rem);
            font-weight: 900;
            text-transform: uppercase;
            color: transparent;
            -webkit-text-stroke: 3px #000;
            text-stroke: 3px #000;
            letter-spacing: 0.2em;
            position: relative;
        }
        .page-title-effect-stencil::before {
            content: attr(data-text);
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent 0%, transparent 45%, #000 45%, #000 55%, transparent 55%, transparent 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Pattern Fill Effect */
        .page-title-effect-pattern-fill {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(3rem, 14vw, 7rem);
            font-weight: 900;
            text-transform: uppercase;
            background-image: 
                repeating-linear-gradient(
                    45deg,
                    #667eea 0px,
                    #667eea 10px,
                    #764ba2 10px,
                    #764ba2 20px
                );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        
        /* Depth Layers Effect */
        .page-title-effect-depth-layers {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(2.5rem, 12vw, 6rem);
            font-weight: 900;
            text-transform: uppercase;
            color: #fff;
            text-shadow:
                2px 2px 0 #667eea,
                4px 4px 0 #764ba2,
                6px 6px 0 #f093fb,
                8px 8px 0 #4facfe,
                10px 10px 0 #00f2fe,
                12px 12px 20px rgba(0, 0, 0, 0.3);
        }
        
        .widgets-container {
            display: flex;
            flex-direction: column;
            gap: var(--widget-spacing, 1rem);
            position: relative;
        }
        
        .widget-item {
            display: block;
            padding: 1rem;
            background: var(--widget-background, var(--secondary-color));
            border: var(--widget-border-width, 2px) solid var(--widget-border-color, var(--primary-color));
            border-radius: var(--widget-border-radius, 12px);
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
            /* Box shadow is set by ThemeCSSGenerator based on border effect */
            /* For shadow: uses --widget-box-shadow */
            /* For glow: box-shadow is set to none and glow uses ::before pseudo-element */
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: auto;
            font-family: var(--widget-secondary-font, var(--page-secondary-font, var(--body-font)), sans-serif);
        }
        
        /* All widgets use horizontal flexbox layout (Linktree style) */
        .widget-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            padding: 0.875rem 1rem;
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
            font-size: 1.0625rem; /* 17px - increased one step */
            font-weight: 400; /* Normal weight, not bold */
        }
        
        /* Thumbnail wrapper for consistent sizing */
        .widget-thumbnail-wrapper {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Icon wrapper for consistent sizing */
        .widget-icon-wrapper {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            font-family: var(--widget-secondary-font, var(--page-secondary-font, var(--body-font)), sans-serif);
            font-size: 1rem; /* 16px - increased one step */
        }
        
        .widget-title {
            font-weight: 400; /* Normal weight, not bold */
            margin: 0 0 0.25rem 0;
            font-family: var(--widget-primary-font, var(--page-primary-font, var(--heading-font)), sans-serif);
            color: var(--text-color);
            font-size: 1.125rem; /* 18px - increased one step */
        }
        
        .widget-description {
            font-size: 1rem; /* 16px - matches widget-content */
            color: var(--text-color);
            opacity: 0.8;
            margin: 0.25rem 0 0 0;
            font-family: var(--widget-secondary-font, var(--page-secondary-font, var(--body-font)), sans-serif);
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
            color: #ff6600;
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
            color: var(--primary-color);
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
            color: var(--primary-color);
            opacity: 0.65;
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
            background: var(--primary-color);
            color: var(--secondary-color);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.75rem;
            padding: 0;
            position: relative;
            gap: 0.05rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
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
            background: rgba(255, 255, 255, 0.2);
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
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0;
            position: relative;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
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
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.8125rem;
            padding: 0;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .expand-drawer-btn {
            background: var(--primary-color);
            color: var(--secondary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
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
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
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
            background: var(--primary-color);
            opacity: 0.95;
        }
        
        .volume-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(0, 0, 0, 0.12);
            transform: scale(1.08);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
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
            color: var(--primary-color);
            opacity: 0.7;
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
            background: var(--primary-color);
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
            background: var(--secondary-color);
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            cursor: grab;
            z-index: 10;
            transform: translate(-50%, -50%);
            transition: left 0.1s linear, transform 0.2s ease, box-shadow 0.2s ease;
            pointer-events: auto;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
            touch-action: none;
        }
        
        .progress-scrubber:active {
            cursor: grabbing;
            transform: translate(-50%, -50%) scale(1.4);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
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
            background: var(--secondary-color);
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
            background: var(--primary-color);
            border-radius: 3px 3px 0 0;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .tab-btn.active {
            opacity: 1;
            color: var(--primary-color);
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
            background: var(--primary-color);
            border-radius: 0 2px 2px 0;
            transition: height 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .chapter-item:hover {
            background: rgba(0, 0, 0, 0.04);
            transform: translateX(4px);
        }
        
        .chapter-item.active {
            background: rgba(0, 102, 255, 0.08);
            border-color: rgba(0, 102, 255, 0.2);
        }
        
        .chapter-item.active::before {
            height: 60%;
        }
        
        .chapter-time {
            font-weight: 600;
            color: var(--primary-color);
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
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
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
            background: var(--primary-color);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .follow-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--secondary-color);
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
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f0f0;
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
            border: 2px solid var(--primary-color);
            background: var(--secondary-color);
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .podcast-widget-minimal .minimal-play-pause:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
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
            background: var(--primary-color);
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
            border: 1px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .podcast-widget-minimal .minimal-expand:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
        }
        
        .podcast-error {
            padding: 1rem;
            color: #dc3545;
            text-align: center;
        }
        
        /* Expanded Drawer */
        .podcast-widget-drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--secondary-color);
            border-top: 2px solid var(--primary-color);
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
            background: var(--secondary-color);
            z-index: 10;
        }
        
        .podcast-widget-drawer .drawer-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .podcast-widget-drawer .drawer-close:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
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
            color: var(--primary-color);
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
            right: -400px;
            width: 400px;
            max-width: 90vw;
            height: 100vh;
            background: var(--secondary-color);
            border-left: 2px solid var(--primary-color);
            box-shadow: -4px 0 12px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        
        .episode-drawer.open {
            right: 0;
        }
        
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .drawer-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary-color);
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
    </style>
</head>
<body class="<?php echo $cssGenerator->getSpatialEffectClass(); ?>">
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
                                <stop offset="0%" style="stop-color:#f0f0f0;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#fff;stop-opacity:1" />
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
            <?php elseif ($pageNameEffect === 'rainbow'): ?>
                <h1 class="page-title-effect-rainbow" data-heading="<?php echo $pageTitleText; ?>">
                    <span><?php echo $pageTitleText; ?></span>
                </h1>
            <?php elseif ($pageNameEffect === 'stencil'): ?>
                <h1 class="page-title-effect-stencil" data-text="<?php echo $pageTitleText; ?>"><?php echo $pageTitleText; ?></h1>
            <?php else: ?>
                <h1 class="page-title <?php echo $pageNameEffect ? 'page-title-effect-' . h($pageNameEffect) : ''; ?>"><?php echo $pageTitleText; ?></h1>
            <?php endif; ?>
            
            <?php if ($page['podcast_description']): ?>
                <p class="page-description"><?php echo nl2br(h($page['podcast_description'])); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Podcast Player Top Drawer -->
        <?php if (!empty($page['rss_feed_url'])): ?>
            <div class="podcast-top-drawer" id="podcast-top-drawer" style="display: none;">
                <!-- Podcast Player Top Banner (attached to drawer bottom) -->
                <div class="podcast-top-banner" id="podcast-top-banner">
                    <button class="podcast-banner-toggle" id="podcast-drawer-toggle" aria-label="Open Podcast Player" title="Open Podcast Player">
                        <i class="fas fa-podcast"></i>
                        <span>Tap to Listen</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
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
                <h2 style="margin: 0; color: var(--primary-color);">Subscribe to Email List</h2>
                <button class="drawer-close" onclick="closeEmailDrawer()" aria-label="Close">×</button>
            </div>
            <div style="padding: 1rem 0;">
                <p style="margin-bottom: 1rem; color: var(--primary-color);">Get notified about new episodes and updates.</p>
                <form id="email-subscribe-form" onsubmit="subscribeEmail(event)">
                    <div class="form-group">
                        <label for="subscribe-email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email Address</label>
                        <input type="email" 
                               id="subscribe-email" 
                               name="email" 
                               required 
                               placeholder="your@email.com"
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--primary-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box;">
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
                messageDiv.style.color = 'var(--primary-color)';
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
                    <path d="M93.781 51.578C95 50.969 96 49.359 96 48c0-1.375-1-2.969-2.219-3.578 0 0-22.868-1.514-31.781-10.422-8.915-8.91-10.438-31.781-10.438-31.781C50.969 1 49.375 0 48 0s-2.969 1-3.594 2.219c0 0-1.5 22.87-10.438 31.781-8.938 8.908-31.781 10.422-31.781 10.422C1 44.031 0 45.625 0 48c0 1.359 1 2.969 2.219 3.578 0 0 22.843 1.514 31.781 10.422 8.938 8.911 10.438 31.781 10.438 31.781C45.031 95 46.625 96 48 96s2.969-1 3.578-2.219c0 0 1.514-22.87 10.438-31.781 8.913-8.908 31.781-10.422 31.781-10.422C94 51.031 95 49.359 95 48c0-1.375-1-2.969-2.219-3.578z" fill="#FFD700"/>
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
                permissionButton.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 12px 24px; background: #0066ff; color: white; border: none; border-radius: 8px; cursor: pointer; z-index: 1000; font-weight: 600; box-shadow: 0 4px 12px rgba(0,102,255,0.3);';
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
                            permissionButton.style.background = '#dc3545';
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
                        document.body.style.overflow = 'hidden';
                        this.isPeeking = false;
                    },
                    
                    closeDrawer: function() {
                        drawer.classList.remove('open');
                        drawer.classList.remove('peek');
                        setTimeout(() => {
                            if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                                drawer.style.display = 'none';
                            }
                        }, 300);
                        document.body.style.overflow = '';
                        this.isPeeking = false;
                    },
                    
                    peekDrawer: function() {
                        if (this.isPeeking || drawer.classList.contains('open')) return;
                        
                        drawer.style.display = 'flex';
                        // Force reflow
                        void drawer.offsetWidth;
                        drawer.classList.add('peek');
                        this.isPeeking = true;
                        
                        // Close after showing peek
                        setTimeout(() => {
                            if (drawer.classList.contains('peek') && !drawer.classList.contains('open')) {
                                drawer.classList.remove('peek');
                                setTimeout(() => {
                                    if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                                        drawer.style.display = 'none';
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

