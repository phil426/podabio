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
                'thumbnail' => '/assets/widget-thumbnails/link.png',
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
                'thumbnail' => '/assets/widget-thumbnails/youtube.png',
                'category' => 'videos',
                'requires_api' => false,
                'config_fields' => [
                    'video_url' => ['type' => 'url', 'label' => 'YouTube Video URL', 'required' => true, 'help' => 'Paste the full YouTube URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ or https://youtu.be/dQw4w9WgXcQ)', 'placeholder' => 'https://www.youtube.com/watch?v=VIDEO_ID'],
                    'autoplay' => ['type' => 'checkbox', 'label' => 'Autoplay', 'required' => false]
                ]
            ],
            
            'text_html' => [
                'widget_id' => 'text_html',
                'name' => 'Text/HTML Block',
                'description' => 'Add custom text or HTML content',
                'thumbnail' => '/assets/widget-thumbnails/text.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'content' => ['type' => 'textarea', 'label' => 'HTML Content', 'required' => true, 'help' => 'You can use HTML tags for formatting', 'rows' => 6]
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
                'thumbnail' => '/assets/widget-thumbnails/podcast-custom.png',
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
                'thumbnail' => '/assets/widget-thumbnails/email.png',
                'category' => 'forms',
                'requires_api' => false,
                'config_fields' => [
                    // Uses page-level email service settings, no additional config needed
                ]
            ],
            
            // Blog Widgets
            'blog_latest_posts' => [
                'widget_id' => 'blog_latest_posts',
                'name' => 'Latest Blog Posts',
                'description' => 'Display recent blog posts in a list or grid',
                'thumbnail' => '/assets/widget-thumbnails/blog-latest.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'post_count' => ['type' => 'text', 'label' => 'Number of Posts', 'required' => false, 'default' => '5', 'help' => 'How many posts to display (1-20)'],
                    'layout' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'options' => ['list' => 'List', 'grid' => 'Grid'], 'default' => 'list'],
                    'show_excerpt' => ['type' => 'checkbox', 'label' => 'Show Excerpt', 'required' => false, 'default' => true],
                    'category_id' => ['type' => 'select', 'label' => 'Filter by Category (optional)', 'required' => false, 'options' => []]
                ]
            ],
            
            'blog_category_filter' => [
                'widget_id' => 'blog_category_filter',
                'name' => 'Blog Category Filter',
                'description' => 'Filter blog posts by category',
                'thumbnail' => '/assets/widget-thumbnails/blog-filter.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'category_id' => ['type' => 'select', 'label' => 'Category', 'required' => false, 'options' => [], 'help' => 'Leave empty to show all categories'],
                    'post_count' => ['type' => 'text', 'label' => 'Posts per Category', 'required' => false, 'default' => '5', 'help' => 'Number of posts to show per category']
                ]
            ],
            
            'blog_related_posts' => [
                'widget_id' => 'blog_related_posts',
                'name' => 'Related Blog Posts',
                'description' => 'Show related posts based on a reference post',
                'thumbnail' => '/assets/widget-thumbnails/blog-related.png',
                'category' => 'content',
                'requires_api' => false,
                'config_fields' => [
                    'post_slug' => ['type' => 'text', 'label' => 'Reference Post Slug', 'required' => false, 'help' => 'Slug of the post to find related posts for'],
                    'post_count' => ['type' => 'text', 'label' => 'Number of Posts', 'required' => false, 'default' => '5', 'help' => 'How many related posts to show']
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

