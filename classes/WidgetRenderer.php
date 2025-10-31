<?php
/**
 * Widget Renderer
 * Handles rendering of widgets on the public page based on widget type
 */

require_once __DIR__ . '/WidgetRegistry.php';

class WidgetRenderer {
    
    /**
     * Render a widget
     * @param array $widget Widget data from database
     * @return string HTML output
     */
    public static function render($widget) {
        if (!$widget || !isset($widget['widget_type'])) {
            return '';
        }
        
        $widgetType = $widget['widget_type'];
        $configData = is_string($widget['config_data']) 
            ? json_decode($widget['config_data'], true) 
            : ($widget['config_data'] ?? []);
        
        // Get widget definition
        $widgetDef = WidgetRegistry::getWidget($widgetType);
        
        if (!$widgetDef) {
            // Fallback: render as custom link (for backward compatibility)
            return self::renderCustomLink($widget, $configData);
        }
        
        // Render based on widget type
        switch ($widgetType) {
            case 'custom_link':
                return self::renderCustomLink($widget, $configData);
                
            case 'youtube_video':
                return self::renderYouTubeVideo($widget, $configData);
                
            case 'text_html':
                return self::renderTextHtml($widget, $configData);
                
            case 'image':
                return self::renderImage($widget, $configData);
                
            case 'podcast_player':
                return self::renderPodcastPlayer($widget, $configData);
                
            default:
                // Fallback rendering
                return self::renderCustomLink($widget, $configData);
        }
    }
    
    /**
     * Render custom link widget
     */
    private static function renderCustomLink($widget, $configData) {
        $url = $configData['url'] ?? '';
        $title = $widget['title'] ?? 'Untitled';
        $thumbnail = $configData['thumbnail_image'] ?? null;
        $icon = $configData['icon'] ?? null;
        $disclosure = $configData['disclosure_text'] ?? null;
        
        if (!$url) {
            return '';
        }
        
        $pageId = $widget['page_id'] ?? 0;
        $widgetId = $widget['id'] ?? 0;
        
        $clickUrl = "/click.php?link_id={$widgetId}&page_id={$pageId}";
        
        $html = '<a href="' . htmlspecialchars($clickUrl) . '" class="widget-item" target="_blank" rel="noopener noreferrer">';
        
        if ($thumbnail) {
            $html .= '<img src="' . htmlspecialchars($thumbnail) . '" alt="' . htmlspecialchars($title) . '" class="widget-thumbnail">';
        } elseif ($icon) {
            $html .= '<div class="widget-icon"><i class="' . htmlspecialchars($icon) . '"></i></div>';
        }
        
        $html .= '<div class="widget-content">';
        $html .= '<div class="widget-title">' . htmlspecialchars($title) . '</div>';
        if ($disclosure) {
            $html .= '<div class="widget-disclosure">' . htmlspecialchars($disclosure) . '</div>';
        }
        $html .= '</div>';
        $html .= '</a>';
        
        return $html;
    }
    
    /**
     * Render YouTube video widget
     */
    private static function renderYouTubeVideo($widget, $configData) {
        $videoId = $configData['video_id'] ?? '';
        $title = $widget['title'] ?? 'Video';
        
        if (!$videoId) {
            return '';
        }
        
        // Extract video ID from URL if full URL provided
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoId, $matches)) {
            $videoId = $matches[1];
        }
        
        $html = '<div class="widget-item widget-video">';
        $html .= '<div class="widget-content">';
        $html .= '<div class="widget-title">' . htmlspecialchars($title) . '</div>';
        $html .= '<div class="widget-video-embed">';
        $html .= '<iframe width="100%" height="315" src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render text/HTML widget
     */
    private static function renderTextHtml($widget, $configData) {
        $title = $widget['title'] ?? '';
        $content = $configData['content'] ?? '';
        
        if (!$content) {
            return '';
        }
        
        $html = '<div class="widget-item widget-text">';
        if ($title) {
            $html .= '<div class="widget-content">';
            $html .= '<div class="widget-title">' . htmlspecialchars($title) . '</div>';
            $html .= '</div>';
        }
        $html .= '<div class="widget-text-content">';
        // Allow HTML but sanitize dangerous tags
        $html .= self::sanitizeHtml($content);
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render image widget
     */
    private static function renderImage($widget, $configData) {
        $imageUrl = $configData['image_url'] ?? '';
        $title = $widget['title'] ?? 'Image';
        $linkUrl = $configData['link_url'] ?? null;
        
        if (!$imageUrl) {
            return '';
        }
        
        $html = '';
        
        if ($linkUrl) {
            $pageId = $widget['page_id'] ?? 0;
            $widgetId = $widget['id'] ?? 0;
            $clickUrl = "/click.php?link_id={$widgetId}&page_id={$pageId}";
            $html .= '<a href="' . htmlspecialchars($clickUrl) . '" class="widget-item widget-image" target="_blank" rel="noopener noreferrer">';
        } else {
            $html .= '<div class="widget-item widget-image">';
        }
        
        $html .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($title) . '" class="widget-image-content">';
        
        if ($linkUrl) {
            $html .= '</a>';
        } else {
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Render podcast player widget (placeholder)
     */
    private static function renderPodcastPlayer($widget, $configData) {
        // To be implemented in Phase 2
        $title = $widget['title'] ?? 'Podcast Episode';
        return '<div class="widget-item widget-podcast"><div class="widget-content"><div class="widget-title">' . htmlspecialchars($title) . '</div><div class="widget-note">Podcast player coming soon</div></div></div>';
    }
    
    /**
     * Sanitize HTML content (allow safe tags only)
     */
    private static function sanitizeHtml($html) {
        // Allow common safe HTML tags
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        $html = strip_tags($html, $allowedTags);
        
        // Remove javascript: and data: URLs from links
        $html = preg_replace('/(<a[^>]+href=["\'])(javascript:|data:)/i', '$1#', $html);
        
        return $html;
    }
}

