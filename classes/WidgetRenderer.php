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
                
            case 'podcast_player_full':
                return self::renderPodcastPlayerFull($widget, $configData);
                
            case 'podcast_player_custom':
                return self::renderPodcastPlayerCustom($widget, $configData);
                
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
     * Render podcast player widget (Shikwasa-based) with minimal view and expandable drawer
     */
    private static function renderPodcastPlayer($widget, $configData) {
        try {
            $title = $widget['title'] ?? 'Podcast Player';
            $rssFeedUrl = $configData['rss_feed_url'] ?? '';
            $widgetId = isset($widget['id']) ? (int)$widget['id'] : 0;
            
            if (empty($rssFeedUrl)) {
                return '<div class="widget-item widget-podcast"><div class="widget-content"><div class="widget-title">' . htmlspecialchars($title) . '</div><div class="widget-note" style="color: #dc3545;">RSS Feed URL is required</div></div></div>';
            }
            
            // Validate widgetId
            if ($widgetId <= 0) {
                return '<div class="widget-item widget-podcast"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Invalid widget ID</div></div></div>';
            }
        
        $containerId = 'shikwasa-podcast-' . $widgetId;
        $playerContainerId = 'shikwasa-player-container-' . $widgetId;
        
        $html = '<div class="widget-item widget-podcast" id="widget-podcast-' . $widgetId . '">';
        $html .= '<div class="widget-content">';
        
        // Podcast Title (optional, above player)
        if ($title) {
            $html .= '<div class="podcast-widget-title" id="podcast-widget-title-' . $widgetId . '">' . htmlspecialchars($title) . '</div>';
        }
        
        // Full Shikwasa Player (always visible, no drawer, no playlist)
        $html .= '<div id="' . htmlspecialchars($playerContainerId) . '" class="shikwasa-podcast-container" data-rss-url="' . htmlspecialchars($rssFeedUrl) . '"></div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Add Shikwasa initialization script
        $html .= '<script>
        (function() {
            const widgetId = ' . (int)$widgetId . ';
            const containerId = ' . json_encode($playerContainerId) . ';
            const rssUrl = ' . json_encode($rssFeedUrl) . ';
            
            let playerInstance = null;
            let episodes = [];
            let currentEpisodeIndex = 0;
            let feedData = null;
            let podcastCoverImage = null;
            
            // Load Shikwasa library if not already loaded
            if (!window.Shikwasa) {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = "https://cdn.jsdelivr.net/npm/shikwasa@2/dist/shikwasa.min.css";
                document.head.appendChild(link);
                
                const script = document.createElement("script");
                script.src = "https://cdn.jsdelivr.net/npm/shikwasa@2/dist/shikwasa.min.js";
                script.onload = function() {
                    fetchAndParseRSS();
                };
                document.head.appendChild(script);
            } else {
                fetchAndParseRSS();
            }
            
            function fetchAndParseRSS() {
                fetch("https://api.rss2json.com/v1/api.json?rss_url=" + encodeURIComponent(rssUrl))
                    .then(response => response.json())
                    .then(data => {
                        feedData = data;
                        if (data.status === "ok" && data.items && data.items.length > 0) {
                            // Parse episodes from RSS feed
                            episodes = data.items.map(item => {
                                let audioUrl = null;
                                
                                // Method 1: Check enclosure (standard RSS)
                                if (item.enclosure && item.enclosure.link) {
                                    audioUrl = item.enclosure.link;
                                } else if (item.enclosure && item.enclosure.url) {
                                    audioUrl = item.enclosure.url;
                                }
                                
                                // Method 2: Check media:content (iTunes RSS)
                                if (!audioUrl && item.media && item.media.content) {
                                    if (Array.isArray(item.media.content)) {
                                        const audioContent = item.media.content.find(c => c.$.type && c.$.type.startsWith("audio/"));
                                        if (audioContent && audioContent.$.url) {
                                            audioUrl = audioContent.$.url;
                                        }
                                    } else if (item.media.content.$.url) {
                                        audioUrl = item.media.content.$.url;
                                    }
                                }
                                
                                // Method 3: Check for links in description/content
                                if (!audioUrl && item.description) {
                                    const match = item.description.match(/href=["\']([^"\']+\.(mp3|m4a|ogg|wav))["\']/i);
                                    if (match) {
                                        audioUrl = match[1];
                                    }
                                }
                                
                                return {
                                    title: item.title || "Untitled Episode",
                                    audio: audioUrl,
                                    cover: data.feed?.image || item.thumbnail || item.enclosure?.image || "",
                                    description: item.description || item.content || "",
                                    pubDate: item.pubDate || ""
                                };
                            }).filter(ep => ep.audio);
                            
                            if (episodes.length > 0) {
                                // Get podcast cover from RSS feed
                                podcastCoverImage = feedData.feed?.image || episodes[0].cover || "";
                                
                                // Update widget title if RSS feed has title
                                const titleEl = document.getElementById("podcast-widget-title-" + widgetId);
                                if (feedData.feed?.title && titleEl) {
                                    titleEl.textContent = feedData.feed.title;
                                }
                                
                                // Initialize player immediately (full player, no drawer, no playlist)
                                initializePlayer();
                            } else {
                                showError("No playable episodes found in RSS feed.");
                            }
                        } else {
                            showError("Failed to load RSS feed. Please check your feed URL.");
                        }
                    })
                    .catch(error => {
                        console.error("RSS fetch error:", error);
                        showError("Error loading podcast feed. Please try again later.");
                    });
            }
            
            function initializePlayer() {
                if (episodes.length === 0) return;
                
                const container = document.getElementById(containerId);
                if (!container) return;
                
                // Get theme color from CSS variable
                const primaryColor = getComputedStyle(document.documentElement).getPropertyValue("--primary-color") || 
                                    getComputedStyle(document.documentElement).getPropertyValue("--accent-color") || 
                                    "#0066ff";
                
                // Prepare first episode with podcast cover image on the player
                const firstEpisode = {
                    title: episodes[currentEpisodeIndex].title,
                    audio: episodes[currentEpisodeIndex].audio,
                    cover: podcastCoverImage || episodes[currentEpisodeIndex].cover || "",
                    description: episodes[currentEpisodeIndex].description || ""
                };
                
                try {
                    playerInstance = new Shikwasa.Player({
                        container: container,
                        audio: firstEpisode,
                        playlist: [], // No playlist - single episode only
                        themeColor: primaryColor.trim(),
                        theme: "auto"
                    });
                    
                    // Apply additional styling
                    setTimeout(() => {
                        const playerEl = container.querySelector(".shk-player");
                        if (playerEl) {
                            playerEl.style.fontFamily = "inherit";
                            playerEl.style.color = getComputedStyle(document.documentElement).getPropertyValue("--text-color") || "inherit";
                        }
                    }, 100);
                } catch (error) {
                    console.error("Failed to initialize Shikwasa player:", error);
                    showError("Failed to load podcast player.");
                }
            }
            
            function showError(message) {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = "<div class=\\"podcast-error\\">" + message + "</div>";
                }
            }
        })();
        </script>';
        
        return $html;
        } catch (Exception $e) {
            error_log("Podcast player render error: " . $e->getMessage());
            return '<div class="widget-item widget-podcast"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Error loading podcast player. Please check your configuration.</div></div></div>';
        }
    }
    
    /**
     * Render podcast full player widget (Shikwasa-based, always visible, no drawer)
     */
    private static function renderPodcastPlayerFull($widget, $configData) {
        try {
            $title = $widget['title'] ?? 'Podcast Player';
            $rssFeedUrl = $configData['rss_feed_url'] ?? '';
            $widgetId = isset($widget['id']) ? (int)$widget['id'] : 0;
            
            if (empty($rssFeedUrl)) {
                return '<div class="widget-item widget-podcast-full"><div class="widget-content"><div class="widget-title">' . htmlspecialchars($title) . '</div><div class="widget-note" style="color: #dc3545;">RSS Feed URL is required</div></div></div>';
            }
            
            // Validate widgetId
            if ($widgetId <= 0) {
                return '<div class="widget-item widget-podcast-full"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Invalid widget ID</div></div></div>';
            }
        
            $containerId = 'shikwasa-podcast-full-' . $widgetId;
            $playerContainerId = 'shikwasa-player-full-' . $widgetId;
            $playlistId = 'podcast-playlist-full-' . $widgetId;
            
            $html = '<div class="widget-item widget-podcast-full" id="widget-podcast-full-' . $widgetId . '">';
            $html .= '<div class="widget-content">';
            
            // Podcast Header (cover + info)
            $html .= '<div class="podcast-header-full">';
            $html .= '<img class="podcast-cover-full" id="podcast-cover-full-' . $widgetId . '" src="" alt="Podcast Cover" style="display: none;">';
            $html .= '<div class="podcast-header-info">';
            $html .= '<h3 class="podcast-title-full" id="podcast-title-full-' . $widgetId . '">' . htmlspecialchars($title) . '</h3>';
            $html .= '<p class="episode-title-full" id="episode-title-full-' . $widgetId . '">Loading...</p>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Full Shikwasa Player (always visible)
            $html .= '<div id="' . htmlspecialchars($playerContainerId) . '" class="shikwasa-podcast-container-full" data-rss-url="' . htmlspecialchars($rssFeedUrl) . '"></div>';
            
            // Playlist (always visible)
            $html .= '<div id="' . htmlspecialchars($playlistId) . '" class="podcast-playlist-full"></div>';
            
            $html .= '</div>';
            $html .= '</div>';
            
            // Add Shikwasa initialization script
            $html .= '<script>
        (function() {
            const widgetId = ' . (int)$widgetId . ';
            const containerId = ' . json_encode($playerContainerId) . ';
            const playlistId = ' . json_encode($playlistId) . ';
            const rssUrl = ' . json_encode($rssFeedUrl) . ';
            
            let playerInstance = null;
            let episodes = [];
            let currentEpisodeIndex = 0;
            let feedData = null;
            
            // Load Shikwasa library if not already loaded
            if (!window.Shikwasa) {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = "https://cdn.jsdelivr.net/npm/shikwasa@2/dist/shikwasa.min.css";
                document.head.appendChild(link);
                
                const script = document.createElement("script");
                script.src = "https://cdn.jsdelivr.net/npm/shikwasa@2/dist/shikwasa.min.js";
                script.onload = function() {
                    fetchAndParseRSS();
                };
                document.head.appendChild(script);
            } else {
                fetchAndParseRSS();
            }
            
            function fetchAndParseRSS() {
                fetch("https://api.rss2json.com/v1/api.json?rss_url=" + encodeURIComponent(rssUrl))
                    .then(response => response.json())
                    .then(data => {
                        feedData = data;
                        if (data.status === "ok" && data.items && data.items.length > 0) {
                            // Parse episodes from RSS feed
                            episodes = data.items.map(item => {
                                let audioUrl = null;
                                
                                // Method 1: Check enclosure (standard RSS)
                                if (item.enclosure && item.enclosure.link) {
                                    audioUrl = item.enclosure.link;
                                } else if (item.enclosure && item.enclosure.url) {
                                    audioUrl = item.enclosure.url;
                                }
                                
                                // Method 2: Check media:content (iTunes RSS)
                                if (!audioUrl && item.media && item.media.content) {
                                    if (Array.isArray(item.media.content)) {
                                        const audioContent = item.media.content.find(c => c.$.type && c.$.type.startsWith("audio/"));
                                        if (audioContent && audioContent.$.url) {
                                            audioUrl = audioContent.$.url;
                                        }
                                    } else if (item.media.content.$.url) {
                                        audioUrl = item.media.content.$.url;
                                    }
                                }
                                
                                // Method 3: Check for links in description/content
                                if (!audioUrl && item.description) {
                                    const match = item.description.match(/href=["\']([^"\']+\.(mp3|m4a|ogg|wav))["\']/i);
                                    if (match) {
                                        audioUrl = match[1];
                                    }
                                }
                                
                                return {
                                    title: item.title || "Untitled Episode",
                                    audio: audioUrl,
                                    cover: data.feed?.image || item.thumbnail || item.enclosure?.image || "",
                                    description: item.description || item.content || "",
                                    pubDate: item.pubDate || ""
                                };
                            }).filter(ep => ep.audio);
                            
                            if (episodes.length > 0) {
                                updateHeaderView();
                                initializePlayer();
                                renderPlaylist();
                            } else {
                                showError("No playable episodes found in RSS feed.");
                            }
                        } else {
                            showError("Failed to load RSS feed. Please check your feed URL.");
                        }
                    })
                    .catch(error => {
                        console.error("RSS fetch error:", error);
                        showError("Error loading podcast feed. Please try again later.");
                    });
            }
            
            function updateHeaderView() {
                if (episodes.length === 0) return;
                
                const firstEpisode = episodes[0];
                const coverEl = document.getElementById("podcast-cover-full-" + widgetId);
                const podcastTitleEl = document.getElementById("podcast-title-full-" + widgetId);
                const episodeTitleEl = document.getElementById("episode-title-full-" + widgetId);
                
                // Update cover image
                if (feedData.feed?.image || firstEpisode.cover) {
                    const coverUrl = feedData.feed?.image || firstEpisode.cover;
                    coverEl.src = coverUrl;
                    coverEl.style.display = "block";
                }
                
                // Update titles
                if (feedData.feed?.title && podcastTitleEl) {
                    podcastTitleEl.textContent = feedData.feed.title;
                }
                if (episodeTitleEl) {
                    episodeTitleEl.textContent = firstEpisode.title;
                }
            }
            
            function initializePlayer() {
                if (episodes.length === 0) return;
                
                const container = document.getElementById(containerId);
                if (!container) return;
                
                // Get theme color from CSS variable
                const primaryColor = getComputedStyle(document.documentElement).getPropertyValue("--primary-color") || 
                                    getComputedStyle(document.documentElement).getPropertyValue("--accent-color") || 
                                    "#0066ff";
                
                try {
                    playerInstance = new Shikwasa.Player({
                        container: container,
                        audio: episodes[currentEpisodeIndex],
                        playlist: episodes.length > 1 ? episodes.slice(1) : [],
                        themeColor: primaryColor.trim(),
                        theme: "auto"
                    });
                    
                    // Apply additional styling
                    setTimeout(() => {
                        const playerEl = container.querySelector(".shk-player");
                        if (playerEl) {
                            playerEl.style.fontFamily = "inherit";
                            playerEl.style.color = getComputedStyle(document.documentElement).getPropertyValue("--text-color") || "inherit";
                        }
                    }, 100);
                } catch (error) {
                    console.error("Failed to initialize Shikwasa player:", error);
                    showError("Failed to load podcast player.");
                }
            }
            
            function renderPlaylist() {
                const playlistEl = document.getElementById(playlistId);
                if (!playlistEl || episodes.length === 0) return;
                
                // Sanitize HTML to prevent XSS
                const sanitize = (str) => {
                    const div = document.createElement("div");
                    div.textContent = str;
                    return div.innerHTML;
                };
                
                playlistEl.innerHTML = "<h4 class=\\"playlist-title-full\\">Episodes</h4><ul class=\\"episode-list-full\\">" +
                    episodes.map((ep, index) => {
                        const title = sanitize(ep.title);
                        const description = ep.description ? sanitize(ep.description.substring(0, 150) + "...") : "";
                        const cover = ep.cover ? sanitize(ep.cover) : "";
                        const activeClass = index === currentEpisodeIndex ? "active" : "";
                        return "<li class=\\"episode-item-full " + activeClass + "\\" data-index=\\"" + index + "\\">" +
                            (cover ? "<img src=\\"" + cover + "\\" alt=\\"" + title + "\\" class=\\"episode-thumbnail-full\\">" : "") +
                            "<div class=\\"episode-info-full\\">" +
                            "<div class=\\"episode-name-full\\">" + title + "</div>" +
                            (description ? "<div class=\\"episode-desc-full\\">" + description + "</div>" : "") +
                            "</div>" +
                            "</li>";
                    }).join("") + "</ul>";
                
                // Add click handlers to playlist items
                playlistEl.querySelectorAll(".episode-item-full").forEach(item => {
                    item.addEventListener("click", () => {
                        const index = parseInt(item.getAttribute("data-index"));
                        playEpisode(index);
                    });
                });
            }
            
            function playEpisode(index) {
                if (index < 0 || index >= episodes.length) return;
                
                currentEpisodeIndex = index;
                
                // Pause current audio if playing
                const container = document.getElementById(containerId);
                if (container) {
                    const audio = container.querySelector("audio");
                    if (audio) {
                        audio.pause();
                    }
                    container.innerHTML = "";
                }
                
                // Reinitialize player with new episode
                setTimeout(() => {
                    initializePlayer();
                    renderPlaylist();
                }, 100);
            }
            
            function showError(message) {
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = "<div class=\\"podcast-error\\">" + message + "</div>";
                }
            }
        })();
        </script>';
        
            return $html;
        } catch (Exception $e) {
            error_log("Podcast full player render error: " . $e->getMessage());
            return '<div class="widget-item widget-podcast-full"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Error loading podcast player. Please check your configuration.</div></div></div>';
        }
    }
    
    /**
     * Render PodNBio Player - Custom compact podcast widget
     * Ultra-compact design (100-120px height) with bottom sheet drawer
     */
    private static function renderPodcastPlayerCustom($widget, $configData) {
        try {
            $title = $widget['title'] ?? 'Podcast Player';
            $rssFeedUrl = $configData['rss_feed_url'] ?? '';
            $widgetId = isset($widget['id']) ? (int)$widget['id'] : 0;
            
            if (empty($rssFeedUrl)) {
                return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-title">' . htmlspecialchars($title) . '</div><div class="widget-note" style="color: #dc3545;">RSS Feed URL is required</div></div></div>';
            }
            
            if ($widgetId <= 0) {
                return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Invalid widget ID</div></div></div>';
            }
        
            $containerId = 'podnbio-player-' . $widgetId;
            $playerId = 'podnbio-audio-' . $widgetId;
            $drawerId = 'podnbio-drawer-' . $widgetId;
            
            $html = '<div class="widget-item widget-podcast-custom" id="' . htmlspecialchars($containerId) . '">';
            $html .= '<div class="widget-content">';
            
            // Compact Player View (~100px height)
            $html .= '<div class="podcast-compact-player">';
            $html .= '<img class="podcast-cover-compact" id="podcast-cover-' . $widgetId . '" src="" alt="Podcast Cover" style="display: none;">';
            $html .= '<div class="podcast-info-compact">';
            $html .= '<div class="episode-title-compact" id="episode-title-' . $widgetId . '">Loading...</div>';
            $html .= '<div class="podcast-controls-compact">';
            $html .= '<button class="skip-back-btn" id="skip-back-' . $widgetId . '" aria-label="Skip back 15 seconds" title="Skip back 15s"><i class="fas fa-backward"></i> <span class="skip-label">15s</span></button>';
            $html .= '<button class="play-pause-btn" id="play-pause-' . $widgetId . '" aria-label="Play/Pause"><i class="fas fa-play"></i></button>';
            $html .= '<button class="skip-forward-btn" id="skip-forward-' . $widgetId . '" aria-label="Skip forward 15 seconds" title="Skip forward 15s"><span class="skip-label">15s</span> <i class="fas fa-forward"></i></button>';
            $html .= '<button class="expand-drawer-btn" id="expand-drawer-' . $widgetId . '" aria-label="More info" title="More info"><i class="fas fa-chevron-up"></i></button>';
            $html .= '</div>';
            $html .= '<div class="progress-container">';
            $html .= '<div class="progress-bar" id="progress-bar-' . $widgetId . '"></div>';
            $html .= '<span class="time-display" id="time-display-' . $widgetId . '">0:00 / 0:00</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Audio element (hidden)
            $html .= '<audio id="' . htmlspecialchars($playerId) . '" preload="metadata"></audio>';
            
            // Bottom Sheet Drawer (initially visible, auto-hides after 3s)
            $html .= '<div class="podcast-bottom-sheet" id="' . htmlspecialchars($drawerId) . '">';
            $html .= '<div class="drawer-backdrop" id="drawer-backdrop-' . $widgetId . '"></div>';
            $html .= '<div class="drawer-content-wrapper">';
            $html .= '<div class="drawer-drag-handle"></div>';
            $html .= '<div class="drawer-tabs">';
            $html .= '<button class="tab-btn active" data-tab="shownotes" id="tab-shownotes-' . $widgetId . '">Show Notes</button>';
            $html .= '<button class="tab-btn" data-tab="chapters" id="tab-chapters-' . $widgetId . '">Chapters</button>';
            $html .= '<button class="tab-btn" data-tab="episodes" id="tab-episodes-' . $widgetId . '">More Episodes</button>';
            $html .= '</div>';
            $html .= '<div class="drawer-panels">';
            $html .= '<div class="tab-panel active" id="shownotes-panel-' . $widgetId . '"></div>';
            $html .= '<div class="tab-panel" id="chapters-panel-' . $widgetId . '"></div>';
            $html .= '<div class="tab-panel" id="episodes-panel-' . $widgetId . '"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Inline JavaScript (HTML5 Audio + Vanilla JS)
            $html .= self::getPodNBioPlayerInlineScript($widgetId, $containerId, $playerId, $drawerId, $rssFeedUrl);
        
            return $html;
        } catch (Exception $e) {
            error_log("PodNBio Player render error: " . $e->getMessage());
            return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Error loading podcast player. Please check your configuration.</div></div></div>';
        }
    }
    
    /**
     * Get inline JavaScript for PodNBio Player
     * Full HTML5 Audio + Vanilla JS implementation
     */
    private static function getPodNBioPlayerInlineScript($widgetId, $containerId, $playerId, $drawerId, $rssUrl) {
        // Escape variables for JavaScript
        $jsWidgetId = (int)$widgetId;
        $jsContainerId = json_encode($containerId);
        $jsPlayerId = json_encode($playerId);
        $jsDrawerId = json_encode($drawerId);
        $jsRssUrl = json_encode($rssUrl);
        
        return '<script>
(function() {
    const widgetId = ' . $jsWidgetId . ';
    const containerId = ' . $jsContainerId . ';
    const playerId = ' . $jsPlayerId . ';
    const drawerId = ' . $jsDrawerId . ';
    const rssUrl = ' . $jsRssUrl . ';
    
    let audio = null;
    let episodes = [];
    let chapters = [];
    let currentEpisodeIndex = 0;
    let feedData = null;
    let autoCollapseTimer = null;
    let hasUserInteracted = false;
    
    function initAudio() {
        audio = document.getElementById(playerId);
        if (!audio) return;
        audio.addEventListener("play", () => updatePlayButton(true));
        audio.addEventListener("pause", () => updatePlayButton(false));
        audio.addEventListener("timeupdate", updateProgress);
        audio.addEventListener("loadedmetadata", updateDuration);
        audio.addEventListener("ended", () => updatePlayButton(false));
    }
    
    function formatTime(seconds) {
        if (!isFinite(seconds) || isNaN(seconds)) return "0:00";
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ":" + (secs < 10 ? "0" : "") + secs;
    }
    
    function updatePlayButton(isPlaying) {
        const btn = document.getElementById("play-pause-" + widgetId);
        if (!btn) return;
        const icon = btn.querySelector("i");
        if (icon) icon.className = isPlaying ? "fas fa-pause" : "fas fa-play";
    }
    
    function updateProgress() {
        if (!audio || !audio.duration) return;
        const progress = (audio.currentTime / audio.duration) * 100;
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        if (progressBar) {
            progressBar.style.setProperty("--progress-width", progress + "%");
        }
        const timeDisplay = document.getElementById("time-display-" + widgetId);
        if (timeDisplay) timeDisplay.textContent = formatTime(audio.currentTime) + " / " + formatTime(audio.duration);
        updateCurrentChapter();
    }
    
    function updateDuration() {
        if (!audio) return;
        const timeDisplay = document.getElementById("time-display-" + widgetId);
        if (timeDisplay && audio.duration) timeDisplay.textContent = formatTime(audio.currentTime) + " / " + formatTime(audio.duration);
    }
    
    function updateCurrentChapter() {
        if (!audio || chapters.length === 0) return;
        const currentTime = audio.currentTime;
        let activeChapter = null;
        for (let i = chapters.length - 1; i >= 0; i--) {
            if (chapters[i].start <= currentTime) {
                activeChapter = chapters[i];
                break;
            }
        }
        const chaptersPanel = document.getElementById("chapters-panel-" + widgetId);
        if (chaptersPanel) {
            chaptersPanel.querySelectorAll(".chapter-item").forEach((item, index) => {
                item.classList.toggle("active", chapters[index] === activeChapter);
            });
        }
    }
    
    function togglePlayPause() {
        if (!audio) return;
        audio.paused ? audio.play() : audio.pause();
    }
    
    function skipBackward() {
        if (!audio) return;
        audio.currentTime = Math.max(0, audio.currentTime - 15);
    }
    
    function skipForward() {
        if (!audio || !audio.duration) return;
        audio.currentTime = Math.min(audio.duration, audio.currentTime + 15);
    }
    
    function openDrawer() {
        const drawer = document.getElementById(drawerId);
        if (!drawer) return;
        
        // Use requestAnimationFrame for smoother animation
        requestAnimationFrame(() => {
            drawer.classList.remove("hidden");
        });
        
        hasUserInteracted = true;
        if (autoCollapseTimer) {
            clearTimeout(autoCollapseTimer);
            autoCollapseTimer = null;
        }
    }
    
    function closeDrawer() {
        const drawer = document.getElementById(drawerId);
        if (!drawer) return;
        
        drawer.classList.add("hidden");
    }
    
    function switchTab(tabName) {
        document.querySelectorAll("#" + drawerId + " .tab-btn").forEach(btn => btn.classList.remove("active"));
        const activeBtn = document.getElementById("tab-" + tabName + "-" + widgetId);
        if (activeBtn) activeBtn.classList.add("active");
        document.querySelectorAll("#" + drawerId + " .tab-panel").forEach(panel => panel.classList.remove("active"));
        const activePanel = document.getElementById(tabName + "-panel-" + widgetId);
        if (activePanel) activePanel.classList.add("active");
    }
    
    function jumpToChapter(chapterIndex) {
        if (!audio || !chapters[chapterIndex]) return;
        audio.currentTime = chapters[chapterIndex].start;
        if (audio.paused) audio.play();
    }
    
    function loadEpisode(index) {
        if (index < 0 || index >= episodes.length) return;
        currentEpisodeIndex = index;
        const episode = episodes[index];
        if (!audio || !episode.audio) return;
        audio.pause();
        audio.src = episode.audio;
        audio.load();
        const titleEl = document.getElementById("episode-title-" + widgetId);
        if (titleEl) titleEl.textContent = episode.title;
        const coverEl = document.getElementById("podcast-cover-" + widgetId);
        if (coverEl && episode.cover) {
            coverEl.src = episode.cover;
            coverEl.style.display = "block";
        }
        updateShowNotes(episode);
        parseChapters(episode);
    }
    
    function updateShowNotes(episode) {
        const panel = document.getElementById("shownotes-panel-" + widgetId);
        if (!panel) return;
        const sanitize = (html) => {
            const div = document.createElement("div");
            div.textContent = html;
            return div.innerHTML;
        };
        let html = "";
        if (episode.description) {
            const temp = document.createElement("div");
            temp.innerHTML = episode.description;
            const textContent = temp.textContent || temp.innerText || "";
            html = "<div class=\"show-notes-content\">" + sanitize(textContent) + "</div>";
        } else {
            html = "<div class=\"show-notes-content\">No show notes available.</div>";
        }
        panel.innerHTML = html;
    }
    
    function parseChapters(episode) {
        chapters = [];
        if (episode.chapters && Array.isArray(episode.chapters)) {
            chapters = episode.chapters.map(ch => ({
                title: ch.title || "Untitled",
                start: parseFloat(ch.start) || 0
            })).sort((a, b) => a.start - b.start);
        }
        renderChapters();
    }
    
    function renderChapters() {
        const panel = document.getElementById("chapters-panel-" + widgetId);
        if (!panel) return;
        if (chapters.length === 0) {
            panel.innerHTML = "<div class=\"chapters-empty\">No chapters available for this episode.</div>";
            return;
        }
        let html = "<ul class=\"chapters-list\">";
        chapters.forEach((chapter, index) => {
            const timeStr = formatTime(chapter.start);
            html += "<li class=\"chapter-item\" data-index=\"" + index + "\">";
            html += "<span class=\"chapter-time\">" + timeStr + "</span>";
            html += "<span class=\"chapter-title\">" + escapeHtml(chapter.title) + "</span>";
            html += "</li>";
        });
        html += "</ul>";
        panel.innerHTML = html;
        panel.querySelectorAll(".chapter-item").forEach((item, index) => {
            item.addEventListener("click", () => jumpToChapter(index));
        });
    }
    
    function renderEpisodes() {
        const panel = document.getElementById("episodes-panel-" + widgetId);
        if (!panel || episodes.length === 0) return;
        let html = "<ul class=\"episodes-list\">";
        episodes.forEach((episode, index) => {
            const activeClass = index === currentEpisodeIndex ? "active" : "";
            html += "<li class=\"episode-item " + activeClass + "\" data-index=\"" + index + "\">";
            if (episode.cover) {
                html += "<img src=\"" + escapeHtml(episode.cover) + "\" alt=\"" + escapeHtml(episode.title) + "\" class=\"episode-thumbnail\">";
            }
            html += "<div class=\"episode-info\">";
            html += "<div class=\"episode-name\">" + escapeHtml(episode.title) + "</div>";
            if (episode.description) {
                const desc = escapeHtml(episode.description).substring(0, 100);
                html += "<div class=\"episode-desc\">" + desc + "...</div>";
            }
            html += "</div>";
            html += "</li>";
        });
        html += "</ul>";
        panel.innerHTML = html;
        panel.querySelectorAll(".episode-item").forEach((item) => {
            item.addEventListener("click", () => {
                const index = parseInt(item.getAttribute("data-index"));
                loadEpisode(index);
                closeDrawer();
            });
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }
    
    function fetchAndParseRSS() {
        fetch("https://api.rss2json.com/v1/api.json?rss_url=" + encodeURIComponent(rssUrl))
            .then(response => response.json())
            .then(data => {
                feedData = data;
                if (data.status === "ok" && data.items && data.items.length > 0) {
                    episodes = data.items.map(item => {
                        let audioUrl = null;
                        if (item.enclosure && item.enclosure.link) {
                            audioUrl = item.enclosure.link;
                        } else if (item.enclosure && item.enclosure.url) {
                            audioUrl = item.enclosure.url;
                        }
                        if (!audioUrl && item.media && item.media.content) {
                            if (Array.isArray(item.media.content)) {
                                const audioContent = item.media.content.find(c => c.$ && c.$.type && c.$.type.startsWith("audio/"));
                                if (audioContent && audioContent.$.url) audioUrl = audioContent.$.url;
                            } else if (item.media.content.$ && item.media.content.$.url) {
                                audioUrl = item.media.content.$.url;
                            }
                        }
                        return {
                            title: item.title || "Untitled Episode",
                            audio: audioUrl,
                            cover: data.feed?.image || item.thumbnail || "",
                            description: item.description || item.content || "",
                            pubDate: item.pubDate || "",
                            chapters: []
                        };
                    }).filter(ep => ep.audio);
                    
                    if (episodes.length > 0) {
                        loadEpisode(0);
                        const coverEl = document.getElementById("podcast-cover-" + widgetId);
                        if (coverEl && feedData.feed?.image) {
                            coverEl.src = feedData.feed.image;
                            coverEl.style.display = "block";
                        }
                        renderEpisodes();
                        autoCollapseTimer = setTimeout(() => {
                            if (!hasUserInteracted) closeDrawer();
                        }, 3000);
                    } else {
                        showError("No playable episodes found in RSS feed.");
                    }
                } else {
                    showError("Failed to load RSS feed. Please check your feed URL.");
                }
            })
            .catch(error => {
                console.error("RSS fetch error:", error);
                showError("Error loading podcast feed. Please try again later.");
            });
    }
    
    function showError(message) {
        const titleEl = document.getElementById("episode-title-" + widgetId);
        if (titleEl) {
            titleEl.textContent = message;
            titleEl.style.color = "#dc3545";
        }
    }
    
    initAudio();
    document.getElementById("play-pause-" + widgetId)?.addEventListener("click", togglePlayPause);
    document.getElementById("skip-back-" + widgetId)?.addEventListener("click", skipBackward);
    document.getElementById("skip-forward-" + widgetId)?.addEventListener("click", skipForward);
            document.getElementById("expand-drawer-" + widgetId)?.addEventListener("click", () => {
                const drawer = document.getElementById(drawerId);
                if (drawer && drawer.classList.contains("hidden")) {
                    openDrawer();
                } else {
                    closeDrawer();
                }
            });
            
            // Close drawer when clicking drag handle
            document.querySelector("#" + drawerId + " .drawer-drag-handle")?.addEventListener("click", closeDrawer);
    document.querySelectorAll("#" + drawerId + " .tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            switchTab(btn.getAttribute("data-tab"));
            hasUserInteracted = true;
        });
    });
    fetchAndParseRSS();
})();
</script>';
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

