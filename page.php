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
        }
        
        .widget-item {
            display: block;
            padding: 1rem;
            background: var(--secondary-color);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            text-decoration: none;
            color: var(--primary-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
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
            padding: 0;
            border: none;
            background: transparent;
        }
        
        .shikwasa-podcast-container {
            margin-top: 0.5rem;
            width: 100%;
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
                    echo WidgetRenderer::render($widget);
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

