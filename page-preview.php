<?php
/**
 * Page Preview with iPhone 16 Frames
 * Displays user's link-in-bio page in two iPhone 16 mockups
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

// Check if request is for custom domain or username
$domain = $_SERVER['HTTP_HOST'];
$username = $_GET['username'] ?? '';

// If no username in GET, try to extract from REQUEST_URI
if (empty($username)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $requestUri = strtok($requestUri, '?');
    $requestUri = ltrim($requestUri, '/');
    // Handle both /page-preview.php/username and /page-preview.php?username=...
    $requestUri = preg_replace('/^page-preview\.php\/?/', '', $requestUri);
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $requestUri)) {
        $username = $requestUri;
    }
}

$pageClass = new Page();
$page = null;

// Define main domains
$mainDomains = ['getphily.com', 'www.getphily.com', 'poda.bio', 'www.poda.bio', 'localhost', '127.0.0.1'];

if (!in_array(strtolower($domain), $mainDomains)) {
    $page = $pageClass->getByCustomDomain(strtolower($domain));
}

if (!$page && !empty($username)) {
    $page = $pageClass->getByUsername($username);
}

if (!$page || !$page['is_active']) {
    http_response_code(404);
    die('Page not found');
}

// Get page data
require_once __DIR__ . '/classes/WidgetRenderer.php';
$widgets = $pageClass->getWidgets($page['id']);
$links = $pageClass->getLinks($page['id']);
$socialIcons = $pageClass->getSocialIcons($page['id'], true);

// Get theme
$themeClass = new Theme();
$theme = null;
if ($page['theme_id']) {
    $theme = $themeClass->getTheme($page['theme_id']);
}

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
    <title>Preview: <?php echo h($page['podcast_name'] ?: $page['username']); ?></title>
    
    <!-- Google Fonts (load theme fonts) -->
    <?php 
    $googleFontsUrl = $themeClass->buildGoogleFontsUrl($fonts);
    if ($googleFontsUrl): ?>
        <link rel="stylesheet" href="<?php echo h($googleFontsUrl); ?>" media="print" onload="this.media='all'">
        <noscript><link rel="stylesheet" href="<?php echo h($googleFontsUrl); ?>"></noscript>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if ($showPodcastPlayer): ?>
        <link rel="stylesheet" href="/css/podcast-player.css?v=<?php echo filemtime(__DIR__ . '/css/podcast-player.css'); ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="/css/special-effects.css?v=<?php echo filemtime(__DIR__ . '/css/special-effects.css'); ?>">
    <link rel="stylesheet" href="/css/profile.css?v=<?php echo filemtime(__DIR__ . '/css/profile.css'); ?>">
    <link rel="stylesheet" href="/css/qr-code-modal.css?v=<?php echo filemtime(__DIR__ . '/css/qr-code-modal.css'); ?>">
    <link rel="stylesheet" href="/css/typography.css?v=<?php echo filemtime(__DIR__ . '/css/typography.css'); ?>">
    <link rel="stylesheet" href="/css/widgets.css?v=<?php echo filemtime(__DIR__ . '/css/widgets.css'); ?>">
    <link rel="stylesheet" href="/css/social-icons.css?v=<?php echo filemtime(__DIR__ . '/css/social-icons.css'); ?>">
    <link rel="stylesheet" href="/css/drawers.css?v=<?php echo filemtime(__DIR__ . '/css/drawers.css'); ?>">
    
    <?php echo $cssGenerator->generateCompleteStyleBlock(); ?>
    
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-x: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .preview-container {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            justify-content: center !important;
            gap: 4rem !important;
            padding: 3rem 2rem !important;
            min-height: 100vh !important;
            width: 100% !important;
            box-sizing: border-box !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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
        
        /* No notch on preview frames */
        .iphone-frame::before {
            display: none !important;
        }
        
        .iphone-screen {
            width: 100% !important;
            height: 100% !important;
            background: var(--color-background-base, #ffffff) !important;
            border-radius: 47px !important;
            overflow: hidden !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
        }
        
        .iphone-screen::before {
            display: none !important;
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
            padding: 1rem;
            background: transparent;
            min-height: auto;
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
        
        @media (max-width: 1024px) {
            .preview-container {
                flex-direction: column;
                align-items: center;
                gap: 2rem;
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body class="<?php echo trim($cssGenerator->getSpatialEffectClass() . ' ' . $themeBodyClass); ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;">
    <div class="preview-container">
        <!-- Left iPhone: Page Content -->
        <div class="iphone-frame">
            <div class="iphone-screen">
                <div class="iphone-content">
                    <div class="page-container">
                        <?php if (!isset($page['profile_visible']) || $page['profile_visible']): ?>
                        <div class="profile-header">
                            <?php if ($page['profile_image']): ?>
                                <div class="profile-image-container" data-qr-url="/api/qr-code.php?username=<?php echo h($page['username']); ?>">
                                    <img 
                                        src="<?php echo h(normalizeImageUrl($page['profile_image'])); ?>" 
                                        alt="Profile" 
                                        class="profile-image"
                                        style="
                                            width: var(--profile-image-size, 120px);
                                            height: var(--profile-image-size, 120px);
                                            border-radius: var(--profile-image-radius, 16%);
                                            border-width: var(--profile-image-border-width, 0px);
                                            border-color: var(--profile-image-border-color, transparent);
                                            border-style: <?php echo (!empty($page['profile_image_border_width']) && $page['profile_image_border_width'] > 0) ? 'solid' : 'none'; ?>;
                                            box-shadow: var(--profile-image-box-shadow, none);
                                            object-fit: cover;
                                        "
                                        onerror="this.onerror=null; this.style.display='none';"
                                    />
                                    <img 
                                        src="/api/qr-code.php?username=<?php echo h($page['username']); ?>" 
                                        alt="QR Code" 
                                        class="profile-qr-code"
                                        style="
                                            width: var(--profile-image-size, 120px);
                                            height: var(--profile-image-size, 120px);
                                            border-radius: 0;
                                            border: none !important;
                                            box-shadow: none;
                                            object-fit: contain;
                                            background: #ffffff;
                                            box-sizing: border-box;
                                        "
                                        onerror="this.onerror=null; this.style.display='none';"
                                    />
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($page['podcast_name']): 
                                $nameAlignment = $page['name_alignment'] ?? 'center';
                                $nameTextSize = $page['name_text_size'] ?? 'large';
                                $alignmentStyle = 'text-align: ' . h($nameAlignment) . '; ';
                                $sizeClass = 'name-size-' . h($nameTextSize);
                                
                                $effectClass = '';
                                if (!empty($page['page_name_effect'])) {
                                    $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
                                }
                                
                                $nameContent = $page['podcast_name'];
                                $nameContent = nl2br($nameContent);
                                $nameContent = strip_tags($nameContent, '<strong><em><u><br>');
                            ?>
                                <h1 class="page-title <?php echo trim($sizeClass . ' ' . $effectClass); ?>" style="<?php echo $alignmentStyle; ?> font-family: var(--font-family-heading, inherit);"><?php echo $nameContent; ?></h1>
                            <?php elseif ($page['username']): 
                                $effectClass = '';
                                if (!empty($page['page_name_effect'])) {
                                    $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
                                }
                            ?>
                                <h1 class="page-title <?php echo $effectClass; ?>" style="font-family: var(--font-family-heading, inherit);"><?php echo h($page['username']); ?></h1>
                            <?php endif; ?>
                            
                            <?php if ($page['podcast_description']): 
                                $bioAlignment = $page['bio_alignment'] ?? 'center';
                                $bioTextSize = $page['bio_text_size'] ?? 'medium';
                                $alignmentStyle = 'text-align: ' . h($bioAlignment) . '; ';
                                $sizeClass = 'bio-size-' . h($bioTextSize);
                                
                                // Get bio color from theme typography tokens
                                $bioColor = null;
                                if ($theme && !empty($theme['typography_tokens'])) {
                                    $typographyTokens = is_string($theme['typography_tokens']) 
                                        ? json_decode($theme['typography_tokens'], true) 
                                        : $theme['typography_tokens'];
                                    $bioColor = $typographyTokens['color']['body'] ?? null;
                                }
                                
                                // Build style with color/gradient support
                                $colorStyle = '';
                                if ($bioColor && $bioColor !== '') {
                                    if (strpos($bioColor, 'gradient') !== false || strpos($bioColor, 'linear-gradient') !== false || strpos($bioColor, 'radial-gradient') !== false) {
                                        // Gradient: use background-image with text clipping
                                        $colorStyle = 'background-image: ' . h($bioColor) . '; ';
                                        $colorStyle .= '-webkit-background-clip: text; ';
                                        $colorStyle .= 'background-clip: text; ';
                                        $colorStyle .= '-webkit-text-fill-color: transparent; ';
                                        $colorStyle .= 'color: transparent; '; // Fallback
                                    } else {
                                        // Solid color: use color property
                                        $colorStyle = 'color: ' . h($bioColor) . '; ';
                                    }
                                }
                                
                                $bioContent = $page['podcast_description'];
                                $bioContent = nl2br($bioContent);
                                $bioContent = strip_tags($bioContent, '<strong><em><u><br>');
                            ?>
                                <p class="page-description <?php echo $sizeClass; ?>" style="<?php echo $alignmentStyle . $colorStyle; ?> font-family: var(--font-family-body, inherit);"><?php echo $bioContent; ?></p>
                            <?php endif; ?>
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
                                        $platformName = strtolower($icon['platform_name']);
                                        $iconHtml = '';
                                        
                                        if ($platformName === 'pocket_casts') {
                                            $iconHtml = '<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display: block; width: 1em; height: 1em;"><circle cx="16" cy="15" r="15" fill="currentColor" opacity="0.1" /><path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor" d="M16 32c8.837 0 16-7.163 16-16S24.837 0 16 0 0 7.163 0 16s7.163 16 16 16Zm0-28.444C9.127 3.556 3.556 9.127 3.556 16c0 6.873 5.571 12.444 12.444 12.444v-3.11A9.333 9.333 0 1 1 25.333 16h3.111c0-6.874-5.571-12.445-12.444-12.445ZM8.533 16A7.467 7.467 0 0 0 16 23.467v-2.715A4.751 4.751 0 1 1 20.752 16h2.715a7.467 7.467 0 0 0-14.934 0Z"/></svg>';
                                        } elseif ($platformName === 'castro') {
                                            $iconHtml = '<svg width="1em" height="1em" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display: block; width: 1em; height: 1em;"><path fill="currentColor" d="M16 0c-8.839 0-16 7.161-16 16s7.161 16 16 16c8.839 0 16-7.161 16-16s-7.161-16-16-16zM15.995 18.656c-3.645 0-3.645-5.473 0-5.473 3.651 0 3.651 5.473 0 5.473zM22.656 25.125l-2.683-3.719c5.303-3.876 2.553-12.267-4.009-12.256-6.568 0.016-9.281 8.417-3.964 12.271l-2.688 3.724c-3.995-2.891-5.676-8.025-4.161-12.719 1.521-4.687 5.891-7.869 10.823-7.864 6.277 0 11.365 5.088 11.365 11.364 0.005 3.641-1.735 7.063-4.683 9.199z"/></svg>';
                                        } elseif ($platformName === 'overcast') {
                                            $iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="1em" height="1em" style="display: block; width: 1em; height: 1em;"><path fill="currentColor" fill-rule="evenodd" d="M12 2.25A9.75 9.75 0 0 0 2.25 12a9.753 9.753 0 0 0 6.238 9.098l2.26 -7.538a2 2 0 1 1 2.502 0l2.262 7.538A9.753 9.753 0 0 0 21.75 12 9.75 9.75 0 0 0 12 2.25Zm0 19.5a9.788 9.788 0 0 1 -2.076 -0.221l0.078 -0.258L12 19.473l1.998 1.798 0.078 0.258A9.788 9.788 0 0 1 12 21.75ZM0.75 12C0.75 5.787 5.787 0.75 12 0.75S23.25 5.787 23.25 12 18.213 23.25 12 23.25 0.75 18.213 0.75 12Zm12.695 7.428 -0.698 -0.628 0.402 -0.361 0.296 0.99ZM12 18.128l0.83 -0.748 -0.83 -2.77 -0.83 2.77 0.83 0.747Zm-1.445 1.3 0.698 -0.628 -0.402 -0.361 -0.296 0.99ZM6.95 6.9a0.75 0.75 0 0 1 0.15 1.05c-0.44 0.586 -1.35 2.265 -1.35 4.05 0 1.785 0.91 3.464 1.35 4.05a0.75 0.75 0 1 1 -1.2 0.9c-0.56 -0.747 -1.65 -2.735 -1.65 -4.95 0 -2.215 1.09 -4.203 1.65 -4.95a0.75 0.75 0 0 1 1.05 -0.15Zm2.08 2.07a0.75 0.75 0 0 1 0 1.06c-0.238 0.238 -0.78 1.025 -0.78 1.97 0 0.945 0.542 1.732 0.78 1.97a0.75 0.75 0 1 1 -1.06 1.06c-0.43 -0.428 -1.22 -1.575 -1.22 -3.03 0 -1.455 0.79 -2.602 1.22 -3.03a0.75 0.75 0 0 1 1.06 0Zm9.07 -1.92a0.75 0.75 0 0 0 -1.2 0.9c0.44 0.586 1.35 2.265 1.35 4.05 0 1.785 -0.91 3.464 -1.35 4.05a0.75 0.75 0 1 0 1.2 0.9c0.56 -0.747 1.65 -2.735 1.65 -4.95 0 -2.215 -1.09 -4.203 -1.65 -4.95Zm-3.13 1.92a0.75 0.75 0 0 1 1.06 0c0.43 0.428 1.22 1.575 1.22 3.03 0 1.455 -0.79 2.602 -1.22 3.03a0.75 0.75 0 1 1 -1.06 -1.06c0.238 -0.238 0.78 -1.025 0.78 -1.97 0 -0.945 -0.542 -1.732 -0.78 -1.97a0.75 0.75 0 0 1 0 -1.06Z" clip-rule="evenodd"/></svg>';
                                        } else {
                                            $platformIcons = [
                                                'apple_podcasts' => '<i class="fas fa-podcast"></i>',
                                                'spotify' => '<i class="fab fa-spotify"></i>',
                                                'youtube_music' => '<i class="fab fa-youtube"></i>',
                                                'iheart_radio' => '<i class="fas fa-heart"></i>',
                                                'amazon_music' => '<i class="fab fa-amazon"></i>',
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
                            if (!empty($widgets)):
                                foreach ($widgets as $widget): 
                                    $widget['page_id'] = $page['id'];
                                    $isFeatured = !empty($widget['is_featured']);
                                    $featuredEffect = $widget['featured_effect'] ?? '';
                                    
                                    try {
                                        $rendered = WidgetRenderer::render($widget, $page);
                                        if (!empty($rendered)) {
                                            if ($isFeatured && $featuredEffect) {
                                                echo '<div class="featured-widget featured-effect-' . h($featuredEffect) . '">';
                                            }
                                            echo $rendered;
                                            if ($isFeatured && $featuredEffect) {
                                                echo '</div>';
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<!-- Widget render error: ' . htmlspecialchars($e->getMessage()) . ' -->';
                                    }
                                endforeach;
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
                        
                        <!-- Footer -->
                        <?php 
                        $footerVisible = !isset($page['footer_visible']) || $page['footer_visible'];
                        $hasFooterContent = !empty($page['footer_text']) || !empty($page['footer_copyright']) || !empty($page['footer_privacy_link']) || !empty($page['footer_terms_link']);
                        
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
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right iPhone: Podcast Player (only if podcast is enabled) -->
        <?php if ($showPodcastPlayer): ?>
        <div class="iphone-frame iphone-podcast-frame">
            <div class="iphone-screen">
                <div class="iphone-content">
                    <div class="podcast-top-drawer" id="podcast-top-drawer-preview" style="position: relative; transform: none; height: 100%; display: flex; flex-direction: column; background: #000;">
                        <!-- Tab Navigation -->
                        <nav class="tab-navigation" id="tab-navigation-preview">
                            <button class="tab-button active" data-tab="now-playing" id="tab-now-playing-preview">Now Playing</button>
                            <button class="tab-button" data-tab="follow" id="tab-follow-preview">Follow</button>
                            <button class="tab-button" data-tab="details" id="tab-details-preview">Details</button>
                            <button class="tab-button" data-tab="episodes" id="tab-episodes-preview">Episodes</button>
                        </nav>

                        <!-- Tab Content Container -->
                        <div class="tab-content-container" id="tab-content-container-preview" style="flex: 1; overflow-y: auto;">
                            <!-- Now Playing Tab -->
                            <div class="tab-panel active" id="now-playing-panel-preview" style="display: block;">
                                <div class="now-playing-content">
                                    <!-- Full Width Cover Artwork -->
                                    <div class="episode-artwork-fullwidth" id="now-playing-artwork-container-preview">
                                        <?php if (!empty($page['cover_image_url'])): ?>
                                            <img class="episode-artwork-large" id="now-playing-artwork-preview" src="<?php echo h(normalizeImageUrl($page['cover_image_url'])); ?>" alt="Podcast Cover">
                                            <div class="artwork-placeholder" id="artwork-placeholder-preview" style="display: none;">
                                                <i class="fas fa-music"></i>
                                            </div>
                                        <?php else: ?>
                                            <img class="episode-artwork-large" id="now-playing-artwork-preview" src="" alt="Episode Artwork" style="display: none;">
                                            <div class="artwork-placeholder" id="artwork-placeholder-preview">
                                                <i class="fas fa-music"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Progress Section -->
                                    <div class="progress-section-large" id="progress-section-now-playing-preview">
                                        <div class="time-display">
                                            <span id="current-time-display-preview">0:00</span>
                                            <span id="remaining-time-display-preview">-0:00</span>
                                        </div>
                                        <div class="progress-bar-now-playing" id="progress-bar-now-playing-preview">
                                            <div class="progress-fill-now-playing" id="progress-fill-now-playing-preview"></div>
                                            <div class="progress-scrubber-now-playing" id="progress-scrubber-now-playing-preview"></div>
                                        </div>
                                    </div>

                                    <!-- Player Controls -->
                                    <div class="player-controls-section">
                                        <div class="primary-controls">
                                            <button class="control-button-large skip-back-large" id="skip-back-large-preview" aria-label="Skip back 10 seconds">
                                                <span class="skip-label-large">10</span>
                                                <i class="fas fa-backward"></i>
                                            </button>
                                            <button class="control-button-large play-pause-large-now" id="play-pause-large-now-preview" aria-label="Play/Pause">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="control-button-large skip-forward-large" id="skip-forward-large-preview" aria-label="Skip forward 45 seconds">
                                                <span class="skip-label-large">45</span>
                                                <i class="fas fa-forward"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Follow Tab -->
                            <div class="tab-panel" id="follow-panel-preview" style="display: none;">
                                <div class="follow-tab-content">
                                    <div id="follow-content-preview"></div>
                                </div>
                            </div>

                            <!-- Details Tab -->
                            <div class="tab-panel" id="details-panel-preview" style="display: none;">
                                <div class="details-tab-content">
                                    <div class="details-section-modern" id="shownotes-section-preview">
                                        <div class="section-header-modern">
                                            <i class="fas fa-file-alt section-icon"></i>
                                            <h2 class="section-title-modern">Show Notes</h2>
                                        </div>
                                        <div class="shownotes-content-modern" id="shownotes-content-preview">
                                            <div class="empty-state-modern">
                                                <i class="fas fa-info-circle"></i>
                                                <p>No episode selected</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="details-section-modern" id="chapters-section-preview">
                                        <div class="section-header-modern">
                                            <i class="fas fa-list section-icon"></i>
                                            <h2 class="section-title-modern">Chapters</h2>
                                        </div>
                                        <div class="chapters-list-modern" id="chapters-list-preview">
                                            <div class="empty-state-modern">
                                                <i class="fas fa-list"></i>
                                                <p>No chapters available</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Episodes Tab -->
                            <div class="tab-panel" id="episodes-panel-preview" style="display: none;">
                                <div class="episodes-tab-content">
                                    <!-- Loading Skeleton -->
                                    <div id="loading-skeleton" style="display: none;">
                                        <div class="skeleton-item"></div>
                                        <div class="skeleton-item"></div>
                                        <div class="skeleton-item"></div>
                                    </div>
                                    
                                    <!-- Error State -->
                                    <div id="error-state" style="display: none;">
                                        <p>Failed to load episodes</p>
                                    </div>
                                    
                                    <!-- Episodes List -->
                                    <div class="episodes-list-modern" id="episodes-list"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Audio Element -->
                        <audio id="podcast-audio-player-preview" preload="metadata"></audio>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Featured Widget Effects -->
    <script src="/js/featured-widget-effects.js?v=<?php echo filemtime(__DIR__ . '/js/featured-widget-effects.js'); ?>"></script>
    
    <!-- Spatial Tilt Effect -->
    <script src="/js/spatial-tilt.js?v=<?php echo filemtime(__DIR__ . '/js/spatial-tilt.js'); ?>"></script>
    
    <!-- Widget Marquee -->
    <script src="/js/widget-marquee.js?v=<?php echo filemtime(__DIR__ . '/js/widget-marquee.js'); ?>"></script>
    
    <!-- Podcast Player JavaScript -->
    <?php if ($showPodcastPlayer): ?>
        <script src="/js/podcast-player-utils.js"></script>
        <script src="/js/podcast-player-rss-parser.js"></script>
        <script src="/js/podcast-player-audio.js"></script>
        <script src="/js/podcast-player-app.js"></script>
        <script>
            window.podcastConfig = {
                rssFeedUrl: <?php echo json_encode($page['rss_feed_url'] ?? ''); ?>,
                savedCoverImage: <?php echo json_encode($page['cover_image_url'] ?? ''); ?>,
                socialIcons: <?php echo json_encode($socialIcons ?? []); ?>
            };
            
            // Initialize podcast player for preview
            (function() {
                const initPreviewPlayer = () => {
                    if (typeof PodcastPlayerApp === 'undefined') {
                        console.error('PodcastPlayerApp is not defined');
                        return;
                    }
                    
                    const drawer = document.getElementById('podcast-top-drawer-preview');
                    if (!drawer) {
                        console.error('Podcast drawer element not found');
                        return;
                    }
                    
                    // Update drawer container ID first
                    drawer.id = 'podcast-top-drawer';
                    
                    // Update ALL element IDs to standard IDs that PodcastPlayerApp expects
                    // This includes tab panels, buttons, and all content elements
                    const idUpdates = {
                        // Tab navigation
                        'tab-navigation-preview': 'tab-navigation',
                        'tab-now-playing-preview': 'tab-now-playing',
                        'tab-follow-preview': 'tab-follow',
                        'tab-details-preview': 'tab-details',
                        'tab-episodes-preview': 'tab-episodes',
                        'tab-content-container-preview': 'tab-content-container',
                        
                        // Tab panels
                        'now-playing-panel-preview': 'now-playing-panel',
                        'follow-panel-preview': 'follow-panel',
                        'details-panel-preview': 'details-panel',
                        'episodes-panel-preview': 'episodes-panel',
                        
                        // Now playing elements
                        'now-playing-artwork-container-preview': 'now-playing-artwork-container',
                        'now-playing-artwork-preview': 'now-playing-artwork',
                        'artwork-placeholder-preview': 'artwork-placeholder',
                        'progress-section-now-playing-preview': 'progress-section-now-playing',
                        'current-time-display-preview': 'current-time-display',
                        'remaining-time-display-preview': 'remaining-time-display',
                        'progress-bar-now-playing-preview': 'progress-bar-now-playing',
                        'progress-fill-now-playing-preview': 'progress-fill-now-playing',
                        'progress-scrubber-now-playing-preview': 'progress-scrubber-now-playing',
                        'skip-back-large-preview': 'skip-back-large',
                        'play-pause-large-now-preview': 'play-pause-large-now',
                        'skip-forward-large-preview': 'skip-forward-large',
                        
                        // Other tabs
                        'follow-content-preview': 'follow-content',
                        'shownotes-content-preview': 'shownotes-content',
                        'shownotes-section-preview': 'shownotes-section',
                        'chapters-list-preview': 'chapters-list',
                        'chapters-section-preview': 'chapters-section'
                        // Note: loading-skeleton, error-state, and episodes-list already have correct IDs (no -preview suffix)
                    };
                    
                    // Update IDs to standard names
                    Object.keys(idUpdates).forEach(oldId => {
                        const el = document.getElementById(oldId);
                        if (el) {
                            el.id = idUpdates[oldId];
                        }
                    });
                    
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
                    
                    const config = {
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
                    
                    try {
                        console.log('Initializing preview podcast player with RSS feed:', config.rssFeedUrl);
                        console.log('Drawer container:', drawer);
                        console.log('Platform links:', platformLinks);
                        console.log('Social icons:', socialIcons);
                        console.log('Looking for elements:');
                        console.log('  - follow-content:', drawer.querySelector('#follow-content'));
                        console.log('  - episodes-list:', drawer.querySelector('#episodes-list'));
                        console.log('  - shownotes-content:', drawer.querySelector('#shownotes-content'));
                        console.log('  - chapters-list:', drawer.querySelector('#chapters-list'));
                        window.podcastPlayerAppPreview = new PodcastPlayerApp(config, drawer);
                        console.log('Preview podcast player initialized successfully');
                    } catch (error) {
                        console.error('Failed to initialize preview podcast player:', error);
                    }
                };
                
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initPreviewPlayer);
                } else {
                    initPreviewPlayer();
                }
            })();
        </script>
    <?php endif; ?>
    
    <!-- QR Code Morphing Animation Script -->
    <script>
        (function() {
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
                resizeListener = function() {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function() {
                        containers.forEach(function(container) {
                            adjustQRCodeSize(container);
                        });
                    }, 100);
                };
                window.addEventListener('resize', resizeListener);
                
                // Single document click listener for closing QR codes
                clickListener = function(e) {
                    containers.forEach(function(container) {
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
                
                containers.forEach(function(container) {
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
                    qrImg.onload = function() {
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
                    state.clickListener = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleQR();
                    };
                    container.addEventListener('click', state.clickListener);
                    
                    // Touch handler for mobile - store references for cleanup
                    state.touchStartListener = function(e) {
                        state.touchStartTime = Date.now();
                    };
                    container.addEventListener('touchstart', state.touchStartListener, { passive: true });
                    
                    state.touchEndListener = function(e) {
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
</body>
</html>

