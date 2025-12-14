<?php
/**
 * Widget Registry
 * Defines all available widgets in the system with their metadata and configuration
 */

class WidgetRegistry
{

    /**
     * Get all available widgets
     * @return array
     */
    public static function getAllWidgets()
    {
        return [
            // Phase 1: Simple Widgets
            'custom_link' => [
                'widget_id' => 'custom_link',
                'name' => 'Custom Link',
                'description' => 'Add a clickable link with title and thumbnail',
                'thumbnail' => '/assets/widget-thumbnails/custom_link.png',
                'category' => 'links',
                'requires_api' => false,
                'config_fields' => [
                    'url' => ['type' => 'url', 'label' => 'URL', 'required' => true],
                    'description' => ['type' => 'textarea', 'label' => 'Description', 'required' => false, 'help' => 'Optional description text that will scroll if it overflows'],
                    'thumbnail_image' => ['type' => 'url', 'label' => 'Thumbnail Image URL', 'required' => false],
                    'icon' => ['type' => 'select', 'label' => 'Icon', 'required' => false, 'options' => 'fontawesome_icons']
                ]
            ],

            'youtube_video' => [
                'widget_id' => 'youtube_video',
                'name' => 'YouTube Video',
                'description' => 'Embed a YouTube video player',
                'thumbnail' => '/assets/widget-thumbnails/youtube_video.png',
                'category' => 'videos',
                'requires_api' => false,
                'config_fields' => [
                    'video_url' => ['type' => 'url', 'label' => 'YouTube Video URL', 'required' => true, 'help' => 'Paste the full YouTube URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ or https://youtu.be/dQw4w9WgXcQ)', 'placeholder' => 'https://www.youtube.com/watch?v=VIDEO_ID'],
                    'autoplay' => ['type' => 'checkbox', 'label' => 'Autoplay', 'required' => false],
                    'thumbnail_image' => ['type' => 'url', 'label' => 'Thumbnail Image URL', 'required' => false]
                ]
            ],

            'text_html' => [
                'widget_id' => 'text_html',
                'name' => 'Text/HTML Block',
                'description' => 'Add custom text or HTML content',
                'thumbnail' => '/assets/widget-thumbnails/text_html.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'content' => ['type' => 'textarea', 'label' => 'HTML Content', 'required' => true, 'help' => 'You can use HTML tags for formatting', 'rows' => 6]
                ]
            ],

            'heading_block' => [
                'widget_id' => 'heading_block',
                'name' => 'Heading',
                'description' => 'Create a prominent heading with size controls.',
                'thumbnail' => '/assets/widget-thumbnails/heading_block.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'text' => ['type' => 'text', 'label' => 'Heading text', 'required' => true, 'default' => 'New heading'],
                    'level' => [
                        'type' => 'select',
                        'label' => 'Heading level',
                        'required' => false,
                        'default' => 'h2',
                        'options' => [
                            'h1' => 'H1',
                            'h2' => 'H2',
                            'h3' => 'H3'
                        ]
                    ]
                ]
            ],

            'image' => [
                'widget_id' => 'image',
                'name' => 'Image',
                'description' => 'Display an image with optional link',
                'thumbnail' => '/assets/widget-thumbnails/image.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'image_url' => ['type' => 'url', 'label' => 'Image URL', 'required' => true],
                    'alt_text' => ['type' => 'text', 'label' => 'Alt Text', 'required' => false, 'help' => 'Description for screen readers'],
                    'link_url' => ['type' => 'url', 'label' => 'Link URL (optional)', 'required' => false]
                ]
            ],

            // PodNBio Player - Custom compact podcast widget
            'podcast_player_custom' => [
                'widget_id' => 'podcast_player_custom',
                'name' => 'PodNBio Player',
                'description' => 'Compact podcast player with bottom sheet drawer, chapters, and episode navigation',
                'thumbnail' => '/assets/widget-thumbnails/podcast_player_custom.png',
                'category' => 'podcast',
                'requires_api' => false,
                'config_fields' => [
                    'rss_feed_url' => ['type' => 'url', 'label' => 'RSS Feed URL', 'required' => true, 'help' => 'Enter your RSS feed URL to auto-populate title, description, and cover image.', 'placeholder' => 'https://example.com/podcast.rss'],
                    'thumbnail_image' => ['type' => 'url', 'label' => 'Cover Image (auto-filled from RSS)', 'required' => false, 'help' => 'Cover image from RSS feed']
                ],
                'auto_populate_from_rss' => true
            ],



            'contact_form' => [
                'widget_id' => 'contact_form',
                'name' => 'Contact Form',
                'description' => 'Allow visitors to send you a message directly',
                'thumbnail' => '/assets/widget-thumbnails/contact_form.png',
                'category' => 'forms',
                'requires_api' => false,
                'default_title' => 'Contact Me', // Placeholder title
                'config_fields' => [
                    'email_to' => ['type' => 'text', 'label' => 'Send to Email (Optional)', 'required' => false, 'help' => 'Leave blank to use your account email', 'placeholder' => 'you@example.com'],
                    'subject_prefix' => ['type' => 'text', 'label' => 'Subject Prefix', 'required' => false, 'default' => 'New Contact from PodaBio', 'help' => 'Prefix for the email subject line'],
                    'button_text' => ['type' => 'text', 'label' => 'Button Text', 'required' => false, 'default' => 'Contact Me'],
                    'description' => ['type' => 'text', 'label' => 'Description', 'required' => false, 'default' => 'Get in touch with me!'],
                    'success_message' => ['type' => 'text', 'label' => 'Success Message', 'required' => false, 'default' => 'Message sent! We\'ll get back to you soon.']
                ]
            ],

            'embed_code' => [
                'widget_id' => 'embed_code',
                'name' => 'Embed Code',
                'description' => 'Embed HTML, scripts, or iframes from other sites',
                'thumbnail' => '/assets/widget-thumbnails/embed_code.png',
                'category' => 'advanced',
                'requires_api' => false,
                'default_title' => 'My Embed',
                'config_fields' => [
                    'code_content' => ['type' => 'textarea', 'label' => 'Embed Code', 'required' => true, 'help' => 'Paste your HTML code here (iframe, script, etc.)', 'rows' => 6],
                    'display_mode' => [
                        'type' => 'select',
                        'label' => 'Display Mode',
                        'required' => true,
                        'default' => 'inline',
                        'options' => [
                            'inline' => 'Inline (Directly on page)',
                            'modal' => 'Popup (Opens in modal)'
                        ]
                    ],
                    'button_label' => ['type' => 'text', 'label' => 'Button Label (for Popup mode)', 'required' => false, 'default' => 'View Content'],
                    'description' => ['type' => 'text', 'label' => 'Description (Optional)', 'required' => false, 'default' => '']
                ]
            ],

            'image_gallery' => [
                'widget_id' => 'image_gallery',
                'name' => 'Photo Gallery',
                'description' => 'Grid of photos that open in a swipeable full-screen view',
                'thumbnail' => '/assets/widget-thumbnails/image_gallery.png',
                'category' => 'media',
                'requires_api' => false,
                'default_title' => 'My Photos',
                'config_fields' => [
                    'images' => ['type' => 'media_gallery', 'label' => 'Photos', 'required' => true, 'help' => 'Upload multiple photos'],
                    'grid_columns' => [
                        'type' => 'select',
                        'label' => 'Grid Columns',
                        'required' => true,
                        'default' => '3',
                        'options' => [
                            '2' => '2 Columns',
                            '3' => '3 Columns'
                        ]
                    ],
                    'description' => ['type' => 'text', 'label' => 'Description (Optional)', 'required' => false, 'default' => 'Check out my latest photos']
                ]
            ],


            // Shopify E-commerce Widgets


            // Instagram widgets removed
            //     'widget_id' => 'instagram_post',
            //     'name' => 'Instagram Post',
            //     'description' => 'Display a single Instagram post',
            //     'thumbnail' => '/assets/widget-thumbnails/instagram_post.png',
            //     'category' => 'social',
            //     'requires_api' => true,
            //     'config_fields' => [
            //         'media_id' => ['type' => 'text', 'label' => 'Media ID', 'required' => true, 'help' => 'The Instagram media ID (found in the post URL or API response)', 'placeholder' => '17841405309217644'],
            //         'show_caption' => ['type' => 'checkbox', 'label' => 'Show Caption', 'required' => false, 'default' => true],
            //         'show_timestamp' => ['type' => 'checkbox', 'label' => 'Show Timestamp', 'required' => false, 'default' => true]
            //     ]
            // ],

            // 'instagram_feed' => [
            //     'widget_id' => 'instagram_feed',
            //     'name' => 'Instagram Feed',
            //     'description' => 'Display your Instagram feed',
            //     'thumbnail' => '/assets/widget-thumbnails/instagram_feed.png',
            //     'category' => 'social',
            //     'requires_api' => true,
            //     'config_fields' => [
            //         'post_count' => ['type' => 'text', 'label' => 'Number of Posts', 'required' => false, 'default' => '12', 'help' => 'How many posts to display (1-100)'],
            //         'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['grid' => 'Grid', 'list' => 'List'], 'default' => 'grid'],
            //         'show_captions' => ['type' => 'checkbox', 'label' => 'Show Captions', 'required' => false, 'default' => false],
            //         'columns' => ['type' => 'text', 'label' => 'Grid Columns', 'required' => false, 'default' => '3', 'help' => 'Number of columns for grid layout (1-6)']
            //     ]
            // ],

            // 'instagram_gallery' => [
            //     'widget_id' => 'instagram_gallery',
            //     'name' => 'Instagram Gallery',
            //     'description' => 'Display Instagram posts in a gallery grid',
            //     'thumbnail' => '/assets/widget-thumbnails/instagram_gallery.png',
            //     'category' => 'social',
            //     'requires_api' => true,
            //     'config_fields' => [
            //         'post_count' => ['type' => 'text', 'label' => 'Number of Posts', 'required' => false, 'default' => '9', 'help' => 'How many posts to display (1-100)'],
            //         'columns' => ['type' => 'text', 'label' => 'Columns', 'required' => false, 'default' => '3', 'help' => 'Number of columns (1-6)'],
            //         'spacing' => ['type' => 'select', 'label' => 'Spacing', 'required' => false, 'options' => ['none' => 'None', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], 'default' => 'small']
            //     ]
            // ],

            // Giphy GIF Widgets




            // Additional widgets will be added as they're implemented
        ];
    }

    /**
     * Get widget by ID
     * @param string $widgetId
     * @return array|null
     */
    public static function getWidget($widgetId)
    {
        $widgets = self::getAllWidgets();
        return $widgets[$widgetId] ?? null;
    }

    /**
     * Get widgets by category
     * @param string $category
     * @return array
     */
    public static function getWidgetsByCategory($category)
    {
        $widgets = self::getAllWidgets();
        return array_filter($widgets, function ($widget) use ($category) {
            return $widget['category'] === $category;
        });
    }

    /**
     * Get all categories
     * @return array
     */
    public static function getCategories()
    {
        return [
            'links' => 'Links',
            'videos' => 'Videos',
            'content' => 'Content',
            'podcast' => 'Podcast',
            'social' => 'Social Media',
            'forms' => 'Forms & Subscriptions',
            'ecommerce' => 'E-commerce',
            'advanced' => 'Advanced'
        ];
    }

    /**
     * Check if Shopify is configured
     * @return bool
     */
    public static function isShopifyConfigured()
    {
        return !empty(defined('SHOPIFY_SHOP_DOMAIN') ? SHOPIFY_SHOP_DOMAIN : '')
            && !empty(defined('SHOPIFY_STOREFRONT_TOKEN') ? SHOPIFY_STOREFRONT_TOKEN : '');
    }

    /**
     * Check if Instagram is configured
     * @param int|null $userId Optional user ID to check for user-specific token
     * @return bool
     */
    public static function isInstagramConfigured($userId = null)
    {
        // Check for user-specific token if user ID provided
        if ($userId) {
            require_once __DIR__ . '/../config/database.php';
            $user = fetchOne(
                "SELECT instagram_access_token, instagram_token_expires_at 
                 FROM users 
                 WHERE id = ? AND instagram_access_token IS NOT NULL",
                [$userId]
            );

            if ($user && !empty($user['instagram_access_token'])) {
                // Check if token is expired
                if (!empty($user['instagram_token_expires_at'])) {
                    $expiresAt = strtotime($user['instagram_token_expires_at']);
                    return $expiresAt >= time();
                }
                return true;
            }
        }

        // Fallback to global config token
        return !empty(defined('INSTAGRAM_ACCESS_TOKEN') ? INSTAGRAM_ACCESS_TOKEN : '');
    }

    /**
     * Check if Giphy is configured
     * @return bool
     */
    public static function isGiphyConfigured()
    {
        return !empty(defined('GIPHY_API_KEY') ? GIPHY_API_KEY : '');
    }

    /**
     * Check if widget exists
     * @param string $widgetId
     * @return bool
     */
    public static function widgetExists($widgetId)
    {
        return self::getWidget($widgetId) !== null;
    }

    /**
     * Get available widgets (filter out coming soon if needed)
     * @param bool $includeComingSoon
     * @return array
     */
    public static function getAvailableWidgets($includeComingSoon = false)
    {
        $widgets = self::getAllWidgets();

        if (!$includeComingSoon) {
            $widgets = array_filter($widgets, function ($widget) {
                return !isset($widget['coming_soon']) || !$widget['coming_soon'];
            });
        }

        return $widgets;
    }
}

