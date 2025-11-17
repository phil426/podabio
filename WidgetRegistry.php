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
                    'thumbnail_image' => ['type' => 'url', 'label' => 'Thumbnail Image URL', 'required' => false],
                    'icon' => ['type' => 'text', 'label' => 'Icon (Font Awesome class)', 'required' => false, 'help' => 'e.g., fas fa-link'],
                    'disclosure_text' => ['type' => 'textarea', 'label' => 'Disclosure Text (for affiliate links)', 'required' => false]
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
                    'video_id' => ['type' => 'text', 'label' => 'YouTube Video ID', 'required' => true, 'help' => 'The video ID from the YouTube URL (e.g., "dQw4w9WgXcQ" from youtube.com/watch?v=dQw4w9WgXcQ)', 'placeholder' => 'dQw4w9WgXcQ'],
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

