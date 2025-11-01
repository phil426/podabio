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
        $minimalId = 'podcast-minimal-' . $widgetId;
        $drawerId = 'podcast-drawer-' . $widgetId;
        $playerContainerId = 'shikwasa-player-container-' . $widgetId;
        $playlistId = 'podcast-playlist-' . $widgetId;
        
        $html = '<div class="widget-item widget-podcast" id="widget-podcast-' . $widgetId . '">';
        $html .= '<div class="widget-content">';
        
        // Minimal Collapsed View
        $html .= '<div id="' . htmlspecialchars($minimalId) . '" class="podcast-widget-minimal">';
        $html .= '<img class="podcast-cover" id="podcast-cover-' . $widgetId . '" src="" alt="Podcast Cover" style="display: none;">';
        $html .= '<div class="podcast-info">';
        $html .= '<div class="podcast-title" id="podcast-title-' . $widgetId . '">' . htmlspecialchars($title) . '</div>';
        $html .= '<div class="episode-title" id="episode-title-' . $widgetId . '">Loading...</div>';
        $html .= '<div class="minimal-controls">';
        $html .= '<button class="minimal-play-pause" id="minimal-play-pause-' . $widgetId . '" aria-label="Play/Pause">';
        $html .= '<i class="fas fa-play"></i>';
        $html .= '</button>';
        $html .= '<div class="minimal-progress">';
        $html .= '<div class="minimal-progress-bar" id="minimal-progress-bar-' . $widgetId . '"></div>';
        $html .= '</div>';
        $html .= '<div class="minimal-time" id="minimal-time-' . $widgetId . '">0:00</div>';
        $html .= '<button class="minimal-expand" id="minimal-expand-' . $widgetId . '" aria-label="Expand Player">';
        $html .= '<i class="fas fa-chevron-up"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Expanded Drawer
        $html .= '<div id="' . htmlspecialchars($drawerId) . '" class="podcast-widget-drawer hidden">';
        $html .= '<div class="drawer-header">';
        $html .= '<button class="drawer-close" id="drawer-close-' . $widgetId . '" aria-label="Close Player">';
        $html .= '<i class="fas fa-times"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div id="' . htmlspecialchars($playerContainerId) . '" class="shikwasa-podcast-container" data-rss-url="' . htmlspecialchars($rssFeedUrl) . '"></div>';
        $html .= '<div id="' . htmlspecialchars($playlistId) . '" class="drawer-playlist"></div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Add Shikwasa initialization script
        $html .= '<script>
        (function() {
            const widgetId = ' . (int)$widgetId . ';
            const minimalId = ' . json_encode($minimalId) . ';
            const drawerId = ' . json_encode($drawerId) . ';
            const containerId = ' . json_encode($playerContainerId) . ';
            const playlistId = ' . json_encode($playlistId) . ';
            const rssUrl = ' . json_encode($rssFeedUrl) . ';
            
            let playerInstance = null;
            let episodes = [];
            let currentEpisodeIndex = 0;
            let feedData = null;
            let drawerOpening = false;
            let drawerClosing = false;
            
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
                                updateMinimalView();
                                setupEventListeners();
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
            
            function updateMinimalView() {
                if (episodes.length === 0) return;
                
                const firstEpisode = episodes[0];
                const coverEl = document.getElementById("podcast-cover-" + widgetId);
                const podcastTitleEl = document.getElementById("podcast-title-" + widgetId);
                const episodeTitleEl = document.getElementById("episode-title-" + widgetId);
                
                // Update cover image
                if (feedData.feed?.image || firstEpisode.cover) {
                    const coverUrl = feedData.feed?.image || firstEpisode.cover;
                    coverEl.src = coverUrl;
                    coverEl.style.display = "block";
                }
                
                // Update titles
                if (feedData.feed?.title) {
                    podcastTitleEl.textContent = feedData.feed.title;
                }
                episodeTitleEl.textContent = firstEpisode.title;
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
                    
                    // Setup player event listeners
                    setupPlayerListeners();
                    
                    // Render playlist
                    renderPlaylist();
                    
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
            
            function setupPlayerListeners() {
                // Sync minimal controls with player state
                // Wait a bit for Shikwasa to fully initialize
                setTimeout(() => {
                    if (!playerInstance) return;
                    
                    // Try to get audio element from player
                    const container = document.getElementById(containerId);
                    const audio = container ? container.querySelector("audio") : null;
                    
                    if (!audio) {
                        // Try alternative method - Shikwasa might store audio differently
                        const playerEl = container ? container.querySelector(".shk-player") : null;
                        if (playerEl) {
                            // Find audio element within player
                            const audioEl = playerEl.querySelector("audio");
                            if (audioEl) {
                                attachAudioListeners(audioEl);
                            }
                        }
                        return;
                    }
                    
                    attachAudioListeners(audio);
                }, 200);
            }
            
            function attachAudioListeners(audio) {
                audio.addEventListener("play", () => {
                    updateMinimalPlayButton(true);
                });
                
                audio.addEventListener("pause", () => {
                    updateMinimalPlayButton(false);
                });
                
                audio.addEventListener("timeupdate", () => {
                    updateMinimalProgress();
                });
                
                audio.addEventListener("loadedmetadata", () => {
                    updateMinimalProgress();
                });
            }
            
            function updateMinimalPlayButton(isPlaying) {
                const btn = document.getElementById("minimal-play-pause-" + widgetId);
                if (btn) {
                    const icon = btn.querySelector("i");
                    if (icon) {
                        icon.className = isPlaying ? "fas fa-pause" : "fas fa-play";
                    }
                }
            }
            
            function updateMinimalProgress() {
                const container = document.getElementById(containerId);
                if (!container) return;
                
                const audio = container.querySelector("audio");
                if (!audio) return;
                
                const progress = (audio.currentTime / audio.duration) * 100 || 0;
                const currentTime = formatTime(audio.currentTime || 0);
                
                const progressBar = document.getElementById("minimal-progress-bar-" + widgetId);
                const timeEl = document.getElementById("minimal-time-" + widgetId);
                
                if (progressBar) {
                    progressBar.style.width = progress + "%";
                }
                if (timeEl) {
                    timeEl.textContent = currentTime;
                }
            }
            
            function formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return mins + ":" + (secs < 10 ? "0" : "") + secs;
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
                
                playlistEl.innerHTML = "<h3 class=\\"playlist-title\\">Episodes</h3><ul class=\\"playlist-list\\">" +
                    episodes.map((ep, index) => {
                        const title = sanitize(ep.title);
                        const description = ep.description ? sanitize(ep.description.substring(0, 100) + "...") : "";
                        const cover = ep.cover ? sanitize(ep.cover) : "";
                        const activeClass = index === currentEpisodeIndex ? "active" : "";
                        return "<li class=\\"playlist-item " + activeClass + "\\" data-index=\\"" + index + "\\">" +
                            (cover ? "<img src=\\"" + cover + "\\" alt=\\"" + title + "\\" class=\\"playlist-thumbnail\\">" : "") +
                            "<div class=\\"playlist-info\\">" +
                            "<div class=\\"playlist-episode-title\\">" + title + "</div>" +
                            (description ? "<div class=\\"playlist-episode-description\\">" + description + "</div>" : "") +
                            "</div>" +
                            "</li>";
                    }).join("") + "</ul>";
                
                // Add click handlers to playlist items
                playlistEl.querySelectorAll(".playlist-item").forEach(item => {
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
            
            function setupEventListeners() {
                // Expand button
                const expandBtn = document.getElementById("minimal-expand-" + widgetId);
                if (expandBtn) {
                    expandBtn.addEventListener("click", openDrawer);
                }
                
                // Close button
                const closeBtn = document.getElementById("drawer-close-" + widgetId);
                if (closeBtn) {
                    closeBtn.addEventListener("click", closeDrawer);
                }
                
                // Play/Pause button
                const playPauseBtn = document.getElementById("minimal-play-pause-" + widgetId);
                if (playPauseBtn) {
                    playPauseBtn.addEventListener("click", togglePlayPause);
                }
                
                // Keyboard controls
                document.addEventListener("keydown", function(e) {
                    const drawer = document.getElementById(drawerId);
                    if (!drawer || drawer.classList.contains("hidden")) return;
                    
                    if (e.key === "Escape") {
                        closeDrawer();
                    }
                });
            }
            
            function togglePlayPause() {
                const container = document.getElementById(containerId);
                const audio = container ? container.querySelector("audio") : null;
                
                // Only toggle if player is already initialized
                // Don't auto-open drawer - user must click expand button
                if (!audio || !playerInstance) {
                    return;
                }
                
                if (audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            }
            
            function openDrawer() {
                const drawer = document.getElementById(drawerId);
                if (!drawer || drawerOpening || drawerClosing) return;
                
                // Prevent multiple simultaneous opens
                if (!drawer.classList.contains("hidden")) return;
                
                drawerOpening = true;
                
                // Remove hidden class to trigger animation
                drawer.classList.remove("hidden");
                
                // Initialize player if not already done
                if (!playerInstance) {
                    initializePlayer();
                }
                
                // Prevent body scroll when drawer is open
                document.body.style.overflow = "hidden";
                
                // Reset flag after animation completes
                setTimeout(() => {
                    drawerOpening = false;
                }, 350);
            }
            
            function closeDrawer() {
                const drawer = document.getElementById(drawerId);
                if (!drawer || drawerOpening || drawerClosing) return;
                
                // Prevent multiple simultaneous closes
                if (drawer.classList.contains("hidden")) return;
                
                drawerClosing = true;
                
                // Add hidden class to trigger animation
                drawer.classList.add("hidden");
                
                // Restore body scroll
                document.body.style.overflow = "";
                
                // Reset flag after animation completes
                setTimeout(() => {
                    drawerClosing = false;
                }, 350);
            }
            
            function showError(message) {
                const minimalEl = document.getElementById(minimalId);
                if (minimalEl) {
                    minimalEl.innerHTML = `<div class="podcast-error">${message}</div>`;
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

