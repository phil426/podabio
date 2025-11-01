<?php
/**
 * Public Page Display
 * Podn.Bio - Displays user's link-in-bio page
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/classes/Analytics.php';

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
$socialIcons = $pageClass->getSocialIcons($page['id']);

// Get theme
$theme = null;
if ($page['theme_id']) {
    $theme = $pageClass->getTheme($page['theme_id']);
}

// Parse theme colors/fonts
$colors = $page['colors'] ? json_decode($page['colors'], true) : ($theme ? json_decode($theme['colors'], true) : []);
$fonts = $page['fonts'] ? json_decode($page['fonts'], true) : ($theme ? json_decode($theme['fonts'], true) : []);

// Default values
$primaryColor = $colors['primary'] ?? '#000000';
$secondaryColor = $colors['secondary'] ?? '#ffffff';
$accentColor = $colors['accent'] ?? '#0066ff';
$headingFont = $fonts['heading'] ?? 'Inter';
$bodyFont = $fonts['body'] ?? 'Inter';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page['podcast_name'] ?: $page['username']); ?> - <?php echo h(APP_NAME); ?></title>
    
    <!-- Google Fonts -->
    <?php
    // Load fonts if custom fonts are set
    $headingFont = $fonts['heading'] ?? 'Inter';
    $bodyFont = $fonts['body'] ?? 'Inter';
    
    // Build Google Fonts URL
    $headingFontUrl = str_replace(' ', '+', $headingFont);
    $bodyFontUrl = str_replace(' ', '+', $bodyFont);
    $fontUrl = "https://fonts.googleapis.com/css2?family={$headingFontUrl}:wght@400;600;700&family={$bodyFontUrl}:wght@400;500&display=swap";
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo h($fontUrl); ?>" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo h(truncate($page['podcast_description'] ?: 'Link in bio page', 160)); ?>">
    <meta property="og:title" content="<?php echo h($page['podcast_name'] ?: $page['username']); ?>">
    <meta property="og:description" content="<?php echo h(truncate($page['podcast_description'] ?: '', 160)); ?>">
    <meta property="og:image" content="<?php echo h($page['cover_image_url'] ?: ''); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    
    <style>
        :root {
            --primary-color: <?php echo h($primaryColor); ?>;
            --secondary-color: <?php echo h($secondaryColor); ?>;
            --accent-color: <?php echo h($accentColor); ?>;
        }
        
        body {
            font-family: '<?php echo h($bodyFont); ?>', sans-serif;
            margin: 0;
            padding: 0;
            background: <?php echo h($secondaryColor); ?>;
            color: <?php echo h($primaryColor); ?>;
        }
        
        h1, h2, h3 {
            font-family: '<?php echo h($headingFont); ?>', sans-serif;
        }
        
        <?php if ($page['background_image']): ?>
        body {
            background-image: url('<?php echo h($page['background_image']); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        <?php endif; ?>
        
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
            color: var(--primary-color);
        }
        
        .page-description {
            color: var(--primary-color);
            opacity: 0.8;
            margin-bottom: 2rem;
        }
        
        .widgets-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }
        
        .widget-item {
            display: block;
            padding: 1rem;
            background: var(--secondary-color);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: auto;
        }
        
        /* Link widgets without thumbnails - full width button style */
        .widget-link-simple {
            padding: 0.875rem 1.25rem !important;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        .widget-link-simple .widget-content {
            padding: 0 !important;
        }
        
        .widget-link-simple .widget-title {
            margin: 0 !important;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        /* Link widgets with thumbnails - full width flex layout */
        .widget-item:has(.widget-thumbnail) {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
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
        
        .widget-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .widget-content {
            flex: 1;
        }
        
        .widget-title {
            font-weight: 600;
            margin: 0 0 0.25rem 0;
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
            padding: 1rem;
            min-height: 140px;
            display: flex;
            flex-direction: column;
        }
        
        .podcast-header-compact {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
        }
        
        .rss-icon {
            color: #ff6600;
            font-size: 1.25rem;
            opacity: 0.8;
        }
        
        .podcast-main-content {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .podcast-cover-compact {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f0f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .podcast-info-compact {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .podcast-title-compact {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.3;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .episode-title-compact {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--text-color);
            opacity: 0.7;
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
            gap: 0.5rem;
        }
        
        .podcast-controls-compact {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .skip-back-btn,
        .play-pause-btn,
        .skip-forward-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            padding: 0;
            position: relative;
            gap: 0.1rem;
        }
        
        .play-pause-btn {
            width: 48px;
            height: 48px;
            font-size: 1rem;
        }
        
        .skip-label {
            font-size: 0.65rem;
            line-height: 1;
            margin: 0;
            font-weight: 600;
        }
        
        .skip-back-btn:hover,
        .play-pause-btn:hover,
        .skip-forward-btn:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
            transform: scale(1.05);
        }
        
        .volume-btn,
        .expand-drawer-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
            padding: 0;
        }
        
        .expand-drawer-btn {
            background: var(--primary-color);
            color: var(--secondary-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .expand-drawer-btn:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .drawer-icon-toggle {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .expand-drawer-btn.active .drawer-icon-toggle {
            transform: rotate(180deg);
        }
        
        .volume-btn:hover {
            background: var(--primary-color);
            color: var(--secondary-color);
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .current-time,
        .total-time {
            font-size: 0.75rem;
            color: var(--text-color);
            opacity: 0.8;
            white-space: nowrap;
            min-width: 40px;
            text-align: center;
        }
        
        .progress-bar-wrapper {
            flex: 1;
            position: relative;
            height: 40px;
            display: flex;
            align-items: center;
        }
        
        .waveform-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 40px;
            display: block;
            z-index: 1;
        }
        
        .progress-bar {
            position: relative;
            width: 100%;
            height: 4px;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 2px;
            cursor: pointer;
            z-index: 2;
        }
        
        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--progress-width, 0%);
            background-color: var(--primary-color);
            border-radius: 2px;
            transition: width 0.1s linear;
            pointer-events: none;
        }
        
        .progress-scrubber {
            position: absolute;
            top: 50%;
            left: var(--progress-width, 0%);
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            background-color: var(--primary-color);
            border-radius: 50%;
            border: 2px solid var(--secondary-color);
            cursor: grab;
            z-index: 3;
            transition: left 0.1s linear;
            pointer-events: none;
        }
        
        .progress-scrubber:active {
            cursor: grabbing;
        }
        
        .progress-bar-wrapper:hover .progress-scrubber {
            transform: translate(-50%, -50%) scale(1.2);
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
        
        @media (max-width: 768px) {
            .podcast-compact-player {
                padding: 0.5rem;
                gap: 0.5rem;
            }
            
            .podcast-cover-compact {
                width: 60px;
                height: 60px;
            }
            
            .skip-back-btn,
            .play-pause-btn,
            .skip-forward-btn,
            .expand-drawer-btn {
                width: 30px;
                height: 30px;
                font-size: 0.7rem;
            }
            
            .play-pause-btn {
                width: 34px;
                height: 34px;
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
        
        .shikwasa-podcast-container {
            padding: 1rem;
            width: 100%;
        }
        
        /* Style Shikwasa player to match widget theme */
        .shikwasa-podcast-container .shk-player {
            font-family: inherit;
            color: var(--text-color);
            background: transparent;
            border: none;
        }
        
        .shikwasa-podcast-container .shk-player__title,
        .shikwasa-podcast-container .shk-player__episode-title {
            color: var(--text-color);
            font-weight: 600;
            font-family: inherit;
        }
        
        .shikwasa-podcast-container .shk-player__controls {
            background: transparent;
        }
        
        .shikwasa-podcast-container .shk-player__progress {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .shikwasa-podcast-container .shk-player__time {
            color: var(--text-color);
            font-family: inherit;
        }
        
        /* Playlist Styles */
        .drawer-playlist {
            padding: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
        }
        
        .drawer-playlist .playlist-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .drawer-playlist .playlist-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .drawer-playlist .playlist-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            border: 2px solid transparent;
        }
        
        .drawer-playlist .playlist-item:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color);
        }
        
        .drawer-playlist .playlist-item.active {
            background: rgba(0, 102, 255, 0.1);
            border-color: var(--primary-color);
        }
        
        .drawer-playlist .playlist-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .drawer-playlist .playlist-info {
            flex: 1;
            min-width: 0;
        }
        
        .drawer-playlist .playlist-episode-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-color);
        }
        
        .drawer-playlist .playlist-episode-description {
            font-size: 0.875rem;
            color: var(--text-color);
            opacity: 0.7;
            line-height: 1.4;
        }
        
        /* Podcast Full Player Widget Styles (always visible, no drawer) */
        .widget-podcast-full {
            /* Inherits standard widget styling */
        }
        
        .podcast-header-full {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: flex-start;
        }
        
        .podcast-cover-full {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f0f0;
        }
        
        .podcast-header-info {
            flex: 1;
            min-width: 0;
        }
        
        .podcast-title-full {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: var(--text-color);
        }
        
        .episode-title-full {
            font-size: 1rem;
            color: var(--text-color);
            opacity: 0.8;
            margin: 0;
            line-height: 1.4;
        }
        
        .shikwasa-podcast-container-full {
            margin: 1.5rem 0;
            width: 100%;
        }
        
        /* Episode List - Always Visible */
        .podcast-playlist-full {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .podcast-playlist-full .playlist-title-full {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: var(--text-color);
        }
        
        .episode-list-full {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .episode-item-full {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 0.75rem;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .episode-item-full:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color);
        }
        
        .episode-item-full.active {
            background: rgba(0, 102, 255, 0.1);
            border-color: var(--primary-color);
        }
        
        .episode-thumbnail-full {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .episode-info-full {
            flex: 1;
            min-width: 0;
        }
        
        .episode-name-full {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 1rem;
        }
        
        .episode-desc-full {
            font-size: 0.875rem;
            color: var(--text-color);
            opacity: 0.7;
            line-height: 1.4;
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
            background: var(--secondary-color);
            border: 2px solid var(--primary-color);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
<body>
    <div class="page-container">
        <div class="profile-header">
            <?php if ($page['profile_image']): ?>
                <img src="<?php echo h($page['profile_image']); ?>" alt="Profile" class="profile-image">
            <?php endif; ?>
            
            <?php if ($page['cover_image_url']): ?>
                <img src="<?php echo h($page['cover_image_url']); ?>" alt="Cover" class="cover-image">
            <?php endif; ?>
            
            <h1 class="page-title"><?php echo h($page['podcast_name'] ?: $page['username']); ?></h1>
            
            <?php if ($page['podcast_description']): ?>
                <p class="page-description"><?php echo nl2br(h($page['podcast_description'])); ?></p>
            <?php endif; ?>
        </div>
        
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
                    try {
                        $rendered = WidgetRenderer::render($widget);
                        if (!empty($rendered)) {
                            echo $rendered;
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
            
            <!-- Email Subscribe Widget -->
            <?php if (!empty($page['email_service_provider'])): ?>
                <button onclick="openEmailDrawer()" class="widget-item" style="cursor: pointer; text-align: left;">
                    <div class="widget-content">
                        <div class="widget-title">📧 Subscribe to Email List</div>
                    </div>
                </button>
            <?php endif; ?>
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
</body>
</html>

