<?php
/**
 * Widget Registry
 * Defines all available widgets in the system with their metadata and configuration
 */

class WidgetRegistry {
    
    /**
     * Get all available widgets
     * @return array
     */
    public static function getAllWidgets() {
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

            'text_note' => [
                'widget_id' => 'text_note',
                'name' => 'Italic Text',
                'description' => 'Add a small italic note for emphasis.',
                'thumbnail' => '/assets/widget-thumbnails/text_note.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'text' => ['type' => 'text', 'label' => 'Note text', 'required' => true, 'default' => 'Start writing your story…']
                ]
            ],

            'divider_rule' => [
                'widget_id' => 'divider_rule',
                'name' => 'Divider',
                'description' => 'Insert a horizontal rule to separate sections.',
                'thumbnail' => '/assets/widget-thumbnails/divider_rule.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'style' => [
                        'type' => 'select',
                        'label' => 'Style',
                        'required' => false,
                        'default' => 'flat',
                        'options' => [
                            'flat' => 'Flat',
                            'shadow' => 'Shadow',
                            'gradient' => 'Gradient'
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
            
            'email_subscription' => [
                'widget_id' => 'email_subscription',
                'name' => 'Email Subscription',
                'description' => 'Collect email subscriptions from visitors',
                'thumbnail' => '/assets/widget-thumbnails/email_subscription.png',
                'category' => 'forms',
                'requires_api' => false,
                'config_fields' => [
                    // Uses page-level email service settings, no additional config needed
                ]
            ],
            
            
            // Shopify E-commerce Widgets
            'shopify_product' => [
                'widget_id' => 'shopify_product',
                'name' => 'Shopify Product',
                'description' => 'Display a single product from your Shopify store',
                'thumbnail' => '/assets/widget-thumbnails/shopify_product.png',
                'category' => 'ecommerce',
                'requires_api' => true,
                'config_fields' => [
                    'product_handle' => ['type' => 'text', 'label' => 'Product Handle', 'required' => true, 'help' => 'The product handle/slug from your Shopify store (e.g., "my-awesome-product")', 'placeholder' => 'my-awesome-product'],
                    'show_description' => ['type' => 'checkbox', 'label' => 'Show Description', 'required' => false, 'default' => true],
                    'button_text' => ['type' => 'text', 'label' => 'Button Text', 'required' => false, 'default' => 'Buy Now', 'help' => 'Text for the purchase button']
                ]
            ],
            
            'shopify_product_list' => [
                'widget_id' => 'shopify_product_list',
                'name' => 'Shopify Product List',
                'description' => 'Display a list of products from your Shopify store',
                'thumbnail' => '/assets/widget-thumbnails/shopify_product_list.png',
                'category' => 'ecommerce',
                'requires_api' => true,
                'config_fields' => [
                    'product_count' => ['type' => 'text', 'label' => 'Number of Products', 'required' => false, 'default' => '10', 'help' => 'How many products to display (1-50)'],
                    'search_query' => ['type' => 'text', 'label' => 'Search Query (optional)', 'required' => false, 'help' => 'Filter products by search term (e.g., "shoes", "tag:featured")'],
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['list' => 'List', 'grid' => 'Grid'], 'default' => 'list'],
                    'show_prices' => ['type' => 'checkbox', 'label' => 'Show Prices', 'required' => false, 'default' => true]
                ]
            ],
            
            'shopify_collection' => [
                'widget_id' => 'shopify_collection',
                'name' => 'Shopify Collection',
                'description' => 'Display products from a Shopify collection',
                'thumbnail' => '/assets/widget-thumbnails/shopify_collection.png',
                'category' => 'ecommerce',
                'requires_api' => true,
                'config_fields' => [
                    'collection_handle' => ['type' => 'text', 'label' => 'Collection Handle', 'required' => true, 'help' => 'The collection handle/slug from your Shopify store (e.g., "featured-products")', 'placeholder' => 'featured-products'],
                    'product_count' => ['type' => 'text', 'label' => 'Number of Products', 'required' => false, 'default' => '10', 'help' => 'How many products to display (1-50)'],
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['list' => 'List', 'grid' => 'Grid'], 'default' => 'list'],
                    'show_collection_title' => ['type' => 'checkbox', 'label' => 'Show Collection Title', 'required' => false, 'default' => true],
                    'show_prices' => ['type' => 'checkbox', 'label' => 'Show Prices', 'required' => false, 'default' => true]
                ]
            ],
            
            // Instagram Social Media Widgets - Temporarily disabled
            // 'instagram_post' => [
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
            'giphy_search' => [
                'widget_id' => 'giphy_search',
                'name' => 'Giphy Search',
                'description' => 'Display GIFs from Giphy search',
                'thumbnail' => '/assets/widget-thumbnails/giphy_search.png',
                'category' => 'content',
                'requires_api' => true,
                'config_fields' => [
                    'search_query' => ['type' => 'text', 'label' => 'Search Query', 'required' => true, 'help' => 'Search term for GIFs (e.g., "happy", "cats", "celebrate")', 'placeholder' => 'happy'],
                    'gif_count' => ['type' => 'text', 'label' => 'Number of GIFs', 'required' => false, 'default' => '12', 'help' => 'How many GIFs to display (1-50)'],
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['grid' => 'Grid', 'list' => 'List'], 'default' => 'grid'],
                    'columns' => ['type' => 'text', 'label' => 'Grid Columns', 'required' => false, 'default' => '3', 'help' => 'Number of columns for grid layout (1-6)'],
                    'rating' => ['type' => 'select', 'label' => 'Content Rating', 'required' => false, 'options' => ['g' => 'G (General)', 'pg' => 'PG (Parental Guidance)', 'pg-13' => 'PG-13', 'r' => 'R (Restricted)'], 'default' => 'g']
                ]
            ],
            
            'giphy_trending' => [
                'widget_id' => 'giphy_trending',
                'name' => 'Giphy Trending',
                'description' => 'Display trending GIFs from Giphy',
                'thumbnail' => '/assets/widget-thumbnails/giphy_trending.png',
                'category' => 'content',
                'requires_api' => true,
                'config_fields' => [
                    'gif_count' => ['type' => 'text', 'label' => 'Number of GIFs', 'required' => false, 'default' => '12', 'help' => 'How many GIFs to display (1-50)'],
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['grid' => 'Grid', 'list' => 'List'], 'default' => 'grid'],
                    'columns' => ['type' => 'text', 'label' => 'Grid Columns', 'required' => false, 'default' => '3', 'help' => 'Number of columns for grid layout (1-6)'],
                    'rating' => ['type' => 'select', 'label' => 'Content Rating', 'required' => false, 'options' => ['g' => 'G (General)', 'pg' => 'PG (Parental Guidance)', 'pg-13' => 'PG-13', 'r' => 'R (Restricted)'], 'default' => 'g']
                ]
            ],
            
            'giphy_random' => [
                'widget_id' => 'giphy_random',
                'name' => 'Giphy Random',
                'description' => 'Display a random GIF from Giphy',
                'thumbnail' => '/assets/widget-thumbnails/giphy_random.png',
                'category' => 'content',
                'requires_api' => true,
                'config_fields' => [
                    'tag' => ['type' => 'text', 'label' => 'Tag (optional)', 'required' => false, 'help' => 'Filter random GIF by tag (e.g., "cats", "funny")', 'placeholder' => 'cats'],
                    'rating' => ['type' => 'select', 'label' => 'Content Rating', 'required' => false, 'options' => ['g' => 'G (General)', 'pg' => 'PG (Parental Guidance)', 'pg-13' => 'PG-13', 'r' => 'R (Restricted)'], 'default' => 'g'],
                    'show_title' => ['type' => 'checkbox', 'label' => 'Show Title', 'required' => false, 'default' => false]
                ]
            ],
            
            'rolodex' => [
                'widget_id' => 'rolodex',
                'name' => 'Rolodex',
                'description' => 'Display expandable content cards that can be opened to reveal details',
                'thumbnail' => '/assets/widget-thumbnails/rolodex.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'items' => ['type' => 'textarea', 'label' => 'Items (JSON)', 'required' => true, 'help' => 'JSON array of items with title, description, and optional url. Example: [{"title":"Item 1","description":"Details here","url":"https://example.com"}]', 'rows' => 8],
                    'default_expanded' => ['type' => 'checkbox', 'label' => 'Default Expanded', 'required' => false, 'default' => false, 'help' => 'Show all items expanded by default']
                ]
            ],
            
            // Additional widgets will be added as they're implemented
        ];
    }
    
    /**
     * Get widget by ID
     * @param string $widgetId
     * @return array|null
     */
    public static function getWidget($widgetId) {
        $widgets = self::getAllWidgets();
        return $widgets[$widgetId] ?? null;
    }
    
    /**
     * Get widgets by category
     * @param string $category
     * @return array
     */
    public static function getWidgetsByCategory($category) {
        $widgets = self::getAllWidgets();
        return array_filter($widgets, function($widget) use ($category) {
            return $widget['category'] === $category;
        });
    }
    
    /**
     * Get all categories
     * @return array
     */
    public static function getCategories() {
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
    public static function isShopifyConfigured() {
        return !empty(defined('SHOPIFY_SHOP_DOMAIN') ? SHOPIFY_SHOP_DOMAIN : '') 
            && !empty(defined('SHOPIFY_STOREFRONT_TOKEN') ? SHOPIFY_STOREFRONT_TOKEN : '');
    }
    
    /**
     * Check if Instagram is configured
     * @param int|null $userId Optional user ID to check for user-specific token
     * @return bool
     */
    public static function isInstagramConfigured($userId = null) {
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
    public static function isGiphyConfigured() {
        return !empty(defined('GIPHY_API_KEY') ? GIPHY_API_KEY : '');
    }
    
    /**
     * Check if widget exists
     * @param string $widgetId
     * @return bool
     */
    public static function widgetExists($widgetId) {
        return self::getWidget($widgetId) !== null;
    }
    
    /**
     * Get available widgets (filter out coming soon if needed)
     * @param bool $includeComingSoon
     * @return array
     */
    public static function getAvailableWidgets($includeComingSoon = false) {
        $widgets = self::getAllWidgets();
        
        if (!$includeComingSoon) {
            $widgets = array_filter($widgets, function($widget) {
                return !isset($widget['coming_soon']) || !$widget['coming_soon'];
            });
        }
        
        return $widgets;
    }
}

