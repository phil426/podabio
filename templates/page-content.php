<?php
/**
 * Page Content Template
 * 
 * Renders the main content of the page including:
 * - Profile Header
 * - Podcast Player (Top Bar & Drawer)
 * - Social Icons
 * - Widgets
 * - Footer
 * 
 * Expected variables in scope:
 * @var array $page Page data
 * @var array $theme Theme data
 * @var array $socialIcons Social icons array
 * @var array $widgets Widgets array
 * @var array $links Legacy links array
 * @var bool $enablePreviewMode Whether preview mode is active
 * @var bool $showPodcastPlayer Whether podcast player is enabled
 * @var string $idSuffix Optional suffix for HTML IDs (e.g. '-desktop') to prevent duplicates
 */
$idSuffix = $idSuffix ?? '';
?>

<?php if (!isset($page['profile_visible']) || $page['profile_visible']): ?>
    <div class="profile-header">
        <?php if ($page['profile_image']): ?>
            <div class="profile-image-container" data-qr-url="/api/qr-code.php?username=<?php echo h($page['username']); ?>"
                <?php if ($enablePreviewMode): ?> data-hotspot="profile-image" <?php endif; ?>>
                <img src="<?php echo h(normalizeImageUrl($page['profile_image'])); ?>" alt="Profile" class="profile-image"
                    style="
                width: var(--profile-image-size, 120px);
                height: var(--profile-image-size, 120px);
                border-radius: var(--profile-image-radius, 16%);
                border-width: var(--profile-image-border-width, 0px);
                border-color: var(--profile-image-border-color, transparent);
                border-style: <?php echo (!empty($page['profile_image_border_width']) && $page['profile_image_border_width'] > 0) ? 'solid' : 'none'; ?>;
                box-shadow: var(--profile-image-box-shadow, none);
                object-fit: cover;
            " onerror="this.onerror=null; this.style.display='none';" />
                <img src="/api/qr-code.php?username=<?php echo h($page['username']); ?>" alt="QR Code" class="profile-qr-code"
                    style="
                width: var(--profile-image-size, 120px);
                height: var(--profile-image-size, 120px);
                border-radius: 0;
                border: none !important;
                box-shadow: none;
                object-fit: contain;
                background: #ffffff;
                box-sizing: border-box;
            " onerror="this.onerror=null; this.style.display='none';" />
            </div>
        <?php endif; ?>

        <?php if ($page['podcast_name']):
            $nameTextSize = $page['name_text_size'] ?? 'large';
            // Alignment now handled via CSS variable --page-title-alignment
            $sizeClass = 'name-size-' . h($nameTextSize);

            $effectClass = '';
            if (!empty($page['page_name_effect'])) {
                $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
            }

            $nameContent = $page['podcast_name'];
            $nameContent = nl2br($nameContent);
            $nameContent = strip_tags($nameContent, '<strong><em><u><br>');
            ?>
            <h1 class="page-title <?php echo trim($sizeClass . ' ' . $effectClass); ?>"
                style="font-family: var(--font-family-heading, inherit);" <?php if ($enablePreviewMode): ?>
                    data-hotspot="page-title" <?php endif; ?>><?php echo $nameContent; ?></h1>
        <?php elseif ($page['username']):
            $effectClass = '';
            if (!empty($page['page_name_effect'])) {
                $effectClass = 'page-title-effect-' . h($page['page_name_effect']);
            }
            ?>
            <h1 class="page-title <?php echo $effectClass; ?>" style="font-family: var(--font-family-heading, inherit);" <?php if ($enablePreviewMode): ?> data-hotspot="page-title" <?php endif; ?>><?php echo h($page['username']); ?></h1>
        <?php endif; ?>

        <?php if ($page['podcast_description']):
            $bioAlignment = $page['bio_alignment'] ?? 'center';
            $bioTextSize = $page['bio_text_size'] ?? 'medium';
            // Alignment now handled via CSS variable --page-description-alignment
            $sizeClass = 'bio-size-' . h($bioTextSize);

            // Get bio color from theme typography tokens
            $bioColor = null;
            if ($theme && !empty($theme['typography_tokens'])) {
                $typographyTokens = is_string($theme['typography_tokens'])
                    ? json_decode($theme['typography_tokens'], true)
                    : $theme['typography_tokens'];
                // Check if json_decode succeeded and result is an array before accessing
                if (is_array($typographyTokens) && isset($typographyTokens['color']['body'])) {
                    $bioColor = $typographyTokens['color']['body'];
                }
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
            <p class="page-description <?php echo $sizeClass; ?>"
                style="<?php echo $colorStyle; ?> font-family: var(--font-family-body, inherit);" <?php if ($enablePreviewMode): ?> data-hotspot="page-description" <?php endif; ?>>
                <?php echo $bioContent; ?>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Page Background Hotspot (positioned below podcast bar) -->
<?php if ($enablePreviewMode): ?>
    <div class="page-background-hotspot" data-hotspot="page-background"
        style="position: absolute; top: <?php echo $showPodcastPlayer ? '60px' : '16px'; ?>; left: 16px; width: 24px; height: 24px; z-index: 99999; pointer-events: auto; cursor: pointer;">
    </div>
<?php endif; ?>

<!-- Podcast Player Top Banner -->
<?php if ($showPodcastPlayer): ?>
    <div class="podcast-top-banner" id="podcast-top-banner<?php echo $idSuffix; ?>" <?php if ($enablePreviewMode): ?>
            data-hotspot="podcast-player-bar" <?php endif; ?>>
        <button class="podcast-banner-toggle" id="podcast-drawer-toggle<?php echo $idSuffix; ?>"
            aria-label="Open Podcast Player" title="Open Podcast Player">
            <i class="fas fa-podcast"></i>
            <span>Tap to Listen</span>
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
<?php endif; ?>

<!-- Podcast Player Top Drawer -->
<?php if ($showPodcastPlayer): ?>
    <?php require __DIR__ . '/podcast-player.php'; ?>
<?php endif; ?>

<!-- Social Icons -->
<?php if (!empty($socialIcons)): ?>
    <div class="social-icons" <?php if ($enablePreviewMode): ?> data-hotspot="social-icons" <?php endif; ?>>
        <?php foreach ($socialIcons as $icon): ?>
            <a href="<?php echo h($icon['url']); ?>" class="social-icon" target="_blank" rel="noopener noreferrer"
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
    if (!empty($widgets)) {
        $inSection = false; // Track if we are inside a section container
    
        foreach ($widgets as $widget):
            $widget['page_id'] = $page['id'];
            $isFeatured = !empty($widget['is_featured']);
            $featuredEffect = $widget['featured_effect'] ?? '';

            // Check if this is a section header
            $config = $widget['config_data'] ? (is_array($widget['config_data']) ? $widget['config_data'] : json_decode($widget['config_data'], true)) : [];
            $isSectionHeader = $widget['widget_type'] === 'section_header' || ($widget['widget_type'] === 'heading_block' && !empty($config['is_section_group']));

            // IF NEW SECTION HEADER:
            // 1. Close previous section if any
            if ($isSectionHeader) {
                if ($inSection) {
                    echo '</div>'; // Close previous .widget-section-container
                    $inSection = false;
                }

                // 2. Open new section container
                echo '<div class="widget-section-container">';
                $inSection = true;

                // 3. Render Custom Header (skip default renderer)
                echo '<div class="widget-section-header">';
                echo h($widget['title'] ?: 'Group');
                echo '</div>';

                // 4. Skip default rendering for this widget
                continue;
            }

            // Special Logic: Divider OR Invisible Spacer closes a section (Ungroup items below it)
            if (($widget['widget_type'] === 'divider_rule' || $widget['widget_type'] === 'invisible_spacer') && $inSection) {
                echo '</div>'; // Close .widget-section-container
                $inSection = false;
                // Continue to render the spacer itself (outside the group)
            }

            // STANDARD WIDGET RENDERING
            try {
                $rendered = WidgetRenderer::render($widget, $page, $idSuffix);
                if (!empty($rendered)) {
                    // Wrap widget with hotspot attributes in preview mode
                    if ($enablePreviewMode) {
                        echo '<div class="widget-wrapper" data-hotspot="widget-settings" data-widget-id="' . htmlspecialchars($widget['id']) . '">';
                    }
                    if ($isFeatured && $featuredEffect) {
                        echo '<div class="featured-widget featured-effect-' . h($featuredEffect) . '">';
                    }
                    // Add hotspot to widget text content
                    if ($enablePreviewMode) {
                        // Inject hotspot into widget-item elements and text content
                        $rendered = preg_replace(
                            '/(<div[^>]*class="[^"]*widget-item[^"]*"[^>]*)(>)/i',
                            '$1 data-hotspot-text="widget-text"$2',
                            $rendered
                        );
                        // Also add to widget title and heading text elements
                        $rendered = preg_replace(
                            '/(<(div|h1|h2|h3|p)[^>]*class="[^"]*(widget-title|widget-heading-text|widget-text-content)[^"]*"[^>]*)(>)/i',
                            '$1 data-hotspot-text="widget-text"$4',
                            $rendered
                        );
                    }
                    echo $rendered;
                    if ($isFeatured && $featuredEffect) {
                        echo '</div>';
                    }
                    if ($enablePreviewMode) {
                        echo '</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<!-- Widget render error: ' . htmlspecialchars($e->getMessage()) . ' -->';
            }
        endforeach;

        // Close final section if open
        if (isset($inSection) && $inSection) {
            echo '</div>';
        }
    } elseif (!empty($links)) {
        foreach ($links as $link): ?>
            <a href="/click.php?link_id=<?php echo $link['id']; ?>&page_id=<?php echo $page['id']; ?>" class="widget-item" <?php if ($enablePreviewMode): ?>data-hotspot="widget-settings" <?php endif; ?> target="_blank"
                rel="noopener noreferrer">
                <?php if ($link['thumbnail_image']): ?>
                    <div class="widget-thumbnail-wrapper">
                        <img src="<?php echo h(normalizeImageUrl($link['thumbnail_image'])); ?>"
                            alt="<?php echo h($link['title']); ?>" class="widget-thumbnail"
                            onerror="this.onerror=null; this.style.display='none'; var wrapper=this.closest('.widget-thumbnail-wrapper'); if(wrapper){var fallback=wrapper.querySelector('.widget-thumbnail-fallback'); if(fallback)fallback.style.display='flex';}">
                        <div class="widget-thumbnail-fallback"
                            style="display:none; width:100%; height:100%; background:rgba(0,0,0,0.05); border-radius:inherit; align-items:center; justify-content:center; color:rgba(0,0,0,0.3); font-size:1.5rem;">
                            <i class="fas fa-link"></i>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="widget-content">
                    <div class="widget-title"><?php echo h($link['title']); ?></div>
                </div>
            </a>
        <?php endforeach;
    } ?>
</div>

<!-- Footer -->
<?php
$footerVisible = !isset($page['footer_visible']) || $page['footer_visible'];
$hasFooterContent = !empty($page['footer_text']) || !empty($page['footer_copyright']) || !empty($page['footer_privacy_link']) || !empty($page['footer_terms_link']);

if ($footerVisible && $hasFooterContent): ?>
    <footer class="page-footer"
        style="margin-top: auto; padding: 1.5rem 1rem; text-align: center; border-top: 1px solid rgba(15, 23, 42, 0.1);">
        <?php if (!empty($page['footer_text'])): ?>
            <p style="margin: 0 0 1rem 0; color: var(--color-text-secondary, #6b7280); font-size: 0.9rem;">
                <?php echo nl2br(h($page['footer_text'])); ?>
            </p>
        <?php endif; ?>
        <div
            style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 1rem; font-size: 0.85rem; color: var(--color-text-secondary, #6b7280);">
            <?php if (!empty($page['footer_copyright'])): ?>
                <span><?php echo h($page['footer_copyright']); ?></span>
            <?php endif; ?>
            <?php if (!empty($page['footer_privacy_link'])): ?>
                <a href="<?php echo h($page['footer_privacy_link']); ?>" target="_blank" rel="noopener noreferrer"
                    style="color: var(--color-text-secondary, #6b7280); text-decoration: underline;">Privacy Policy</a>
            <?php endif; ?>
            <?php if (!empty($page['footer_terms_link'])): ?>
                <a href="<?php echo h($page['footer_terms_link']); ?>" target="_blank" rel="noopener noreferrer"
                    style="color: var(--color-text-secondary, #6b7280); text-decoration: underline;">Terms of
                    Service</a>
            <?php endif; ?>
        </div>
    </footer>
<?php endif; ?>