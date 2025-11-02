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
                
            case 'podcast_player_custom':
                return self::renderPodcastPlayerCustom($widget, $configData);
                
            case 'email_subscription':
                return self::renderEmailSubscription($widget, $configData);
                
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
        
        // Always render the widget if it has a title, even if URL is empty
        // This ensures all widgets show up on the page
        if (!$title || (trim($title) === '')) {
            return '';
        }
        
        $pageId = $widget['page_id'] ?? 0;
        $widgetId = $widget['id'] ?? 0;
        
        // Use URL if available, otherwise use # as placeholder
        $clickUrl = $url ? "/click.php?link_id={$widgetId}&page_id={$pageId}" : "#";
        
        // Determine if this is a simple link (no thumbnail) vs full-width link
        $isSimpleLink = !$thumbnail;
        $widgetClass = 'widget-item' . ($isSimpleLink ? ' widget-link-simple' : '');
        
        $html = '<a href="' . htmlspecialchars($clickUrl) . '" class="' . $widgetClass . '" target="_blank" rel="noopener noreferrer">';
        
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
     * Render PodNBio Player - Custom compact podcast widget
     * Ultra-compact design (100-120px height) with bottom sheet drawer
     */
    private static function renderPodcastPlayerCustom($widget, $configData) {
        try {
            $title = $widget['title'] ?? 'Podcast Player';
            $rssFeedUrl = $configData['rss_feed_url'] ?? '';
            $widgetId = isset($widget['id']) ? (int)$widget['id'] : 0;
            $pageId = isset($widget['page_id']) ? (int)$widget['page_id'] : 0;
            $widgetType = $widget['widget_type'] ?? 'unknown';
            
            // Debug: Log widget type (can be removed after verification)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PodNBio Widget Debug - Type: {$widgetType}, ID: {$widgetId}, PageID: {$pageId}");
            }
            
            if (empty($rssFeedUrl)) {
                return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-title">' . htmlspecialchars($title) . '</div><div class="widget-note" style="color: #dc3545;">RSS Feed URL is required</div></div></div>';
            }
            
            if ($widgetId <= 0) {
                return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">Invalid widget ID</div></div></div>';
            }
        
            // Get social icons for this page
            require_once __DIR__ . '/Page.php';
            $pageClass = new Page();
            $socialIcons = [];
            if ($pageId > 0) {
                try {
                    $socialIcons = $pageClass->getSocialIcons($pageId);
                } catch (Exception $e) {
                    error_log("Error fetching social icons: " . $e->getMessage());
                    $socialIcons = [];
                }
            }
            
            $containerId = 'podnbio-player-' . $widgetId;
            $playerId = 'podnbio-audio-' . $widgetId;
            $drawerId = 'podnbio-drawer-' . $widgetId;
            
            $html = '<div class="widget-item widget-podcast-custom" id="' . htmlspecialchars($containerId) . '">';
            $html .= '<div class="widget-content">';
            
            // Horizontal Card Layout
            $html .= '<div class="podcast-compact-player">';
            $html .= '<div class="podcast-header-compact">';
            $html .= '<i class="fas fa-rss rss-icon" title="RSS Feed"></i>';
            $html .= '</div>';
            $html .= '<div class="podcast-main-content">';
            $html .= '<img class="podcast-cover-compact" id="podcast-cover-' . $widgetId . '" src="" alt="Podcast Cover" style="display: none;">';
            $html .= '<div class="podcast-info-compact">';
            $html .= '<div class="podcast-title-compact" id="podcast-title-' . $widgetId . '">Loading...</div>';
            $html .= '<div class="episode-title-compact" id="episode-title-' . $widgetId . '">Loading episode...</div>';
            $html .= '<div class="podcast-controls-compact">';
            $html .= '<button class="skip-back-btn" id="skip-back-' . $widgetId . '" aria-label="Skip back 15 seconds" title="Skip back 15s"><span class="skip-label">15</span><i class="fas fa-backward"></i></button>';
            $html .= '<button class="play-pause-btn" id="play-pause-' . $widgetId . '" aria-label="Play/Pause"><i class="fas fa-play"></i></button>';
            $html .= '<button class="skip-forward-btn" id="skip-forward-' . $widgetId . '" aria-label="Skip forward 30 seconds" title="Skip forward 30s"><span class="skip-label">30</span><i class="fas fa-forward"></i></button>';
            $html .= '</div>';
            $html .= '<div class="progress-container">';
            $html .= '<span class="current-time" id="current-time-' . $widgetId . '">0:00</span>';
            $html .= '<div class="progress-bar-wrapper" id="progress-wrapper-' . $widgetId . '">';
            $html .= '<div class="progress-bar" id="progress-bar-' . $widgetId . '">';
            $html .= '<div class="progress-fill" id="progress-fill-' . $widgetId . '"></div>';
            $html .= '<div class="progress-scrubber" id="progress-scrubber-' . $widgetId . '"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<span class="total-time" id="total-time-' . $widgetId . '">0:00</span>';
            $html .= '<button class="volume-btn" id="volume-btn-' . $widgetId . '" aria-label="Volume control" title="Volume"><i class="fas fa-volume-up"></i></button>';
            $html .= '<button class="expand-drawer-btn" id="expand-drawer-' . $widgetId . '" aria-label="Toggle drawer" title="Toggle drawer"><i class="fas fa-chevron-down drawer-icon-toggle"></i></button>';
            $html .= '</div>'; // Close progress-container
            $html .= '</div>'; // Close podcast-info-compact
            $html .= '</div>'; // Close podcast-main-content
            
            // Bottom Sheet Drawer (initially hidden)
            $html .= '<div class="podcast-bottom-sheet hidden" id="' . htmlspecialchars($drawerId) . '">';
            $html .= '<div class="drawer-backdrop" id="drawer-backdrop-' . $widgetId . '"></div>';
            $html .= '<div class="drawer-content-wrapper">';
            $html .= '<div class="drawer-drag-handle"></div>';
            $html .= '<div class="drawer-tabs">';
            $html .= '<button class="tab-btn active" data-tab="shownotes" id="tab-shownotes-' . $widgetId . '">Show Notes</button>';
            $html .= '<button class="tab-btn" data-tab="chapters" id="tab-chapters-' . $widgetId . '">Chapters</button>';
            $html .= '<button class="tab-btn" data-tab="episodes" id="tab-episodes-' . $widgetId . '">More Episodes</button>';
            $html .= '<button class="tab-btn" data-tab="follow" id="tab-follow-' . $widgetId . '">Follow</button>';
            $html .= '</div>';
            $html .= '<div class="drawer-panels">';
            $html .= '<div class="tab-panel active" id="shownotes-panel-' . $widgetId . '"></div>';
            $html .= '<div class="tab-panel" id="chapters-panel-' . $widgetId . '"></div>';
            $html .= '<div class="tab-panel" id="episodes-panel-' . $widgetId . '"></div>';
            $html .= '<div class="tab-panel" id="follow-panel-' . $widgetId . '"></div>';
            $html .= '</div>'; // Close drawer-panels
            $html .= '</div>'; // Close drawer-content-wrapper
            $html .= '</div>'; // Close podcast-bottom-sheet
            
            $html .= '</div>'; // Close podcast-compact-player
            $html .= '</div>'; // Close widget-content
            
            // Audio element (hidden)
            $html .= '<audio id="' . htmlspecialchars($playerId) . '" preload="metadata"></audio>';
            
            $html .= '</div>'; // Close widget-item widget-podcast-custom
            
            // Inline JavaScript (HTML5 Audio + Vanilla JS)
            $html .= self::getPodNBioPlayerInlineScript($widgetId, $containerId, $playerId, $drawerId, $rssFeedUrl, $socialIcons);
        
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
    private static function getPodNBioPlayerInlineScript($widgetId, $containerId, $playerId, $drawerId, $rssUrl, $socialIcons = []) {
        // Escape variables for JavaScript
        $jsWidgetId = (int)$widgetId;
        $jsContainerId = json_encode($containerId);
        $jsPlayerId = json_encode($playerId);
        $jsDrawerId = json_encode($drawerId);
        $jsRssUrl = json_encode($rssUrl);
        $jsSocialIcons = json_encode($socialIcons);
        
        return '<script>
(function() {
    const widgetId = ' . $jsWidgetId . ';
    const containerId = ' . $jsContainerId . ';
    const playerId = ' . $jsPlayerId . ';
    const drawerId = ' . $jsDrawerId . ';
    const rssUrl = ' . $jsRssUrl . ';
    const socialIcons = ' . $jsSocialIcons . ';
    
    let audio = null;
    let episodes = [];
    let chapters = [];
    let currentEpisodeIndex = 0;
    let feedData = null;
    let autoCollapseTimer = null;
    let hasUserInteracted = false;
    let isDragging = false;
    
    function initAudio() {
        audio = document.getElementById(playerId);
        if (!audio) return;
        
        // Ensure audio volume is set immediately (default might be 0 or muted)
        audio.volume = 1.0;
        audio.muted = false;
        
        audio.addEventListener("play", () => {
            updatePlayButton(true);
        });
        audio.addEventListener("pause", () => {
            updatePlayButton(false);
        });
        audio.addEventListener("timeupdate", updateProgress);
        audio.addEventListener("loadedmetadata", updateDuration);
        audio.addEventListener("ended", () => {
            updatePlayButton(false);
        });
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
        if (!audio) return;
        // Do not update progress while dragging (to avoid conflict)
        if (isDragging) return;
        
        const progress = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        if (progressBar) {
            progressBar.style.setProperty("--progress-width", progress + "%");
        }
        const currentTimeEl = document.getElementById("current-time-" + widgetId);
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
        const totalTimeEl = document.getElementById("total-time-" + widgetId);
        if (totalTimeEl && audio.duration) {
            totalTimeEl.textContent = formatTime(audio.duration);
        }
        updateCurrentChapter();
    }
    
    function updateDuration() {
        if (!audio) return;
        const totalTimeEl = document.getElementById("total-time-" + widgetId);
        if (totalTimeEl && audio.duration) {
            totalTimeEl.textContent = formatTime(audio.duration);
        }
        const currentTimeEl = document.getElementById("current-time-" + widgetId);
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
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
        
        // Ensure audio has a source
        if (!audio.src || audio.src === "") {
            console.warn("Audio element has no source");
            return;
        }
        
        if (audio.paused) {
            audio.play().catch(e => {
                console.error("Play failed:", e);
            });
        } else {
            audio.pause();
        }
    }
    
    function skipBackward() {
        if (!audio) return;
        audio.currentTime = Math.max(0, audio.currentTime - 15);
    }
    
    function skipForward() {
        if (!audio || !audio.duration) return;
        audio.currentTime = Math.min(audio.duration, audio.currentTime + 30);
    }
    
    function seekToPosition(event) {
        if (!audio || !audio.duration || isDragging) return;
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        if (!progressBar) return;
        
        // Do not seek if clicking on the scrubber itself
        if (event.target && event.target.id === "progress-scrubber-" + widgetId) {
            return;
        }
        
        const rect = progressBar.getBoundingClientRect();
        const clickX = event.clientX - rect.left;
        const percent = Math.max(0, Math.min(1, clickX / rect.width));
        audio.currentTime = percent * audio.duration;
    }
    
    function startDrag(event) {
        if (!audio || !audio.duration) return;
        isDragging = true;
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        const scrubber = document.getElementById("progress-scrubber-" + widgetId);
        if (!progressBar || !scrubber) return;
        
        // Stop event propagation to prevent progress bar click
        if (event.stopPropagation) event.stopPropagation();
        
        scrubber.classList.add("dragging");
        
        // If dragging from scrubber, use its position; otherwise calculate from mouse/touch
        let clickX;
        if (event.target && event.target.id === "progress-scrubber-" + widgetId) {
            // Dragging from scrubber - use current position
            const rect = progressBar.getBoundingClientRect();
            const scrubberLeft = scrubber.getBoundingClientRect().left;
            clickX = scrubberLeft - rect.left + 8; // Add half scrubber width
        } else {
            // Dragging from progress bar area
            const rect = progressBar.getBoundingClientRect();
            clickX = (event.clientX || (event.touches && event.touches[0].clientX)) - rect.left;
        }
        
        const percent = Math.max(0, Math.min(1, clickX / progressBar.offsetWidth));
        audio.currentTime = percent * audio.duration;
        
        if (event.preventDefault) event.preventDefault();
    }
    
    function doDrag(event) {
        if (!isDragging || !audio || !audio.duration) return;
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        const progressWrapper = document.getElementById("progress-wrapper-" + widgetId);
        if (!progressBar || !progressWrapper) return;
        
        // Use progress bar rect for accurate calculation
        const rect = progressBar.getBoundingClientRect();
        const dragX = (event.clientX || (event.touches && event.touches[0].clientX)) - rect.left;
        const percent = Math.max(0, Math.min(1, dragX / rect.width));
        const newTime = percent * audio.duration;
        
        // Update audio time
        audio.currentTime = newTime;
        
        // Manually update progress bar and time display while dragging
        const progressBarEl = document.getElementById("progress-bar-" + widgetId);
        if (progressBarEl) {
            progressBarEl.style.setProperty("--progress-width", percent * 100 + "%");
        }
        const currentTimeEl = document.getElementById("current-time-" + widgetId);
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(newTime);
        }
        
        if (event.preventDefault) event.preventDefault();
    }
    
    function stopDrag(event) {
        if (!isDragging) return;
        isDragging = false;
        const scrubber = document.getElementById("progress-scrubber-" + widgetId);
        if (scrubber) {
            scrubber.classList.remove("dragging");
        }
    }
    
    function openDrawer() {
        const drawer = document.getElementById(drawerId);
        if (!drawer) return;
        
        // Remove hidden class to trigger expansion animation (height transition)
        drawer.classList.remove("hidden");
        
        // Update toggle button state
        const toggleBtn = document.getElementById("expand-drawer-" + widgetId);
        if (toggleBtn) {
            toggleBtn.classList.add("active");
        }
        
        hasUserInteracted = true;
        if (autoCollapseTimer) {
            clearTimeout(autoCollapseTimer);
            autoCollapseTimer = null;
        }
    }
    
    function closeDrawer() {
        const drawer = document.getElementById(drawerId);
        if (!drawer) return;
        
        // Add hidden class to trigger collapse animation (height transition)
        drawer.classList.add("hidden");
        
        // Update toggle button state
        const toggleBtn = document.getElementById("expand-drawer-" + widgetId);
        if (toggleBtn) {
            toggleBtn.classList.remove("active");
        }
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
        audio.volume = 1.0;
        audio.muted = false;
        audio.load();
        
        const podcastTitleEl = document.getElementById("podcast-title-" + widgetId);
        if (podcastTitleEl && feedData && feedData.feed) {
            podcastTitleEl.textContent = feedData.feed.title || "Podcast";
        }
        const episodeTitleEl = document.getElementById("episode-title-" + widgetId);
        if (episodeTitleEl) episodeTitleEl.textContent = episode.title;
        const coverEl = document.getElementById("podcast-cover-" + widgetId);
        if (coverEl && episode.cover) {
            coverEl.src = episode.cover;
            coverEl.style.display = "block";
        }
        updateShowNotes(episode);
        parseChapters(episode);
        
        // Ensure progress controls are set up after audio loads
        audio.addEventListener("loadedmetadata", () => {
            setupProgressControls();
        }, { once: true });
    }
    
    function updateShowNotes(episode) {
        const panel = document.getElementById("shownotes-panel-" + widgetId);
        if (!panel) return;
        
        // Sanitize HTML - allow safe tags only (similar to PHP sanitizeHtml)
        function sanitizeHtml(html) {
            if (!html) return "";
            const temp = document.createElement("div");
            temp.innerHTML = html;
            
            // List of allowed safe HTML tags
            const allowedTags = ["p", "br", "strong", "em", "u", "a", "ul", "ol", "li", "h1", "h2", "h3", "h4", "h5", "h6", "blockquote", "code", "pre"];
            
            // Remove all tags except allowed ones
            const walker = document.createTreeWalker(
                temp,
                NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            const nodesToRemove = [];
            let node;
            while (node = walker.nextNode()) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    const tagName = node.tagName.toLowerCase();
                    if (!allowedTags.includes(tagName)) {
                        nodesToRemove.push(node);
                    } else if (tagName === "a") {
                        // Sanitize links - remove javascript: and data: URLs
                        const href = node.getAttribute("href");
                        if (href && (href.toLowerCase().startsWith("javascript:") || href.toLowerCase().startsWith("data:"))) {
                            node.setAttribute("href", "#");
                        }
                    }
                }
            }
            
            nodesToRemove.forEach(n => {
                const parent = n.parentNode;
                while (n.firstChild) {
                    parent.insertBefore(n.firstChild, n);
                }
                parent.removeChild(n);
            });
            
            return temp.innerHTML;
        }
        
        let html = "";
        if (episode.description) {
            html = "<div class=\"show-notes-content\">" + sanitizeHtml(episode.description) + "</div>";
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
                // Strip HTML tags for episode list description preview
                const temp = document.createElement("div");
                temp.innerHTML = episode.description;
                const textContent = temp.textContent || temp.innerText || "";
                const desc = textContent.substring(0, 100);
                html += "<div class=\"episode-desc\">" + escapeHtml(desc) + "...</div>";
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
    
    function getPlatformIcon(platformName) {
        const platform = platformName.toLowerCase();
        const iconMap = {
            "facebook": "fab fa-facebook",
            "twitter": "fab fa-twitter",
            "instagram": "fab fa-instagram",
            "youtube": "fab fa-youtube",
            "tiktok": "fab fa-tiktok",
            "spotify": "fab fa-spotify",
            "apple": "fab fa-apple",
            "apple podcasts": "fab fa-podcast",
            "linkedin": "fab fa-linkedin",
            "pinterest": "fab fa-pinterest",
            "snapchat": "fab fa-snapchat",
            "reddit": "fab fa-reddit",
            "discord": "fab fa-discord",
            "twitch": "fab fa-twitch",
            "patreon": "fab fa-patreon",
            "email": "fas fa-envelope",
            "website": "fas fa-globe",
            "website url": "fas fa-link"
        };
        return iconMap[platform] || "fas fa-link";
    }
    
    function renderFollow() {
        const panel = document.getElementById("follow-panel-" + widgetId);
        if (!panel) return;
        
        if (!socialIcons || socialIcons.length === 0) {
            panel.innerHTML = "<div class=\"follow-empty\">No social links available.</div>";
            return;
        }
        
        let html = "<div class=\"follow-buttons\">";
        socialIcons.forEach((icon) => {
            const platformName = icon.platform_name || icon.platformName || "Link";
            const url = icon.url || "";
            const iconClass = icon.icon || getPlatformIcon(platformName);
            
            html += "<a href=\"" + escapeHtml(url) + "\" class=\"follow-button\" target=\"_blank\" rel=\"noopener noreferrer\">";
            html += "<i class=\"" + escapeHtml(iconClass) + "\"></i>";
            html += "<span class=\"follow-button-label\">" + escapeHtml(platformName) + "</span>";
            html += "</a>";
        });
        html += "</div>";
        panel.innerHTML = html;
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
                        
                        // Setup progress controls after episode loads
                        setTimeout(() => {
                            setupProgressControls();
                        }, 100);
                        
                        // Drawer starts closed (user can open it with toggle button)
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
    
    // Setup progress bar and scrubber interaction after elements exist
    let controlsSetup = false;
    function setupProgressControls() {
        const progressBar = document.getElementById("progress-bar-" + widgetId);
        const scrubber = document.getElementById("progress-scrubber-" + widgetId);
        const progressWrapper = document.getElementById("progress-wrapper-" + widgetId);
        
        if (!progressBar || !scrubber || controlsSetup) return;
        controlsSetup = true;
        
        // Progress bar click to seek
        progressBar.addEventListener("click", seekToPosition);
        
        // Also allow clicking on wrapper
        if (progressWrapper) {
            progressWrapper.addEventListener("click", (e) => {
                // Only seek if not clicking on scrubber
                if (e.target.id !== "progress-scrubber-" + widgetId && 
                    !e.target.closest("#progress-scrubber-" + widgetId)) {
                    seekToPosition(e);
                }
            });
        }
        
        // Scrubber drag functionality
        scrubber.addEventListener("mousedown", (e) => {
            e.stopPropagation();
            startDrag(e);
        });
        
        scrubber.addEventListener("touchstart", (e) => {
            e.preventDefault();
            e.stopPropagation();
            startDrag(e.touches[0]);
        });
    }
    
    // Global mouse/touch move and up for dragging
    document.addEventListener("mousemove", doDrag);
    document.addEventListener("mouseup", stopDrag);
    document.addEventListener("touchmove", (e) => {
        if (isDragging) {
            e.preventDefault();
            doDrag(e.touches[0]);
        }
    });
    document.addEventListener("touchend", stopDrag);
    
    // Toggle drawer button
    const toggleBtn = document.getElementById("expand-drawer-" + widgetId);
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            const drawer = document.getElementById(drawerId);
            if (drawer && drawer.classList.contains("hidden")) {
                openDrawer();
                toggleBtn.classList.add("active");
            } else {
                closeDrawer();
                toggleBtn.classList.remove("active");
            }
        });
    }
    
    // Close drawer when clicking drag handle
    document.querySelector("#" + drawerId + " .drawer-drag-handle")?.addEventListener("click", () => {
        closeDrawer();
        const toggleBtn = document.getElementById("expand-drawer-" + widgetId);
        if (toggleBtn) toggleBtn.classList.remove("active");
    });
    document.querySelectorAll("#" + drawerId + " .tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const tabName = btn.getAttribute("data-tab");
            switchTab(tabName);
            hasUserInteracted = true;
            
            // Render follow tab when opened
            if (tabName === "follow") {
                renderFollow();
            }
        });
    });
    
    // Initial render of follow tab
    renderFollow();
    
    fetchAndParseRSS();
})();
</script>';
    }
    
    /**
     * Render email subscription widget
     */
    private static function renderEmailSubscription($widget, $configData) {
        $pageId = $widget['page_id'] ?? 0;
        
        // Get page to check email service configuration
        require_once __DIR__ . '/Page.php';
        $pageClass = new Page();
        $page = $pageClass->get($pageId);
        
        if (!$page || empty($page['email_service_provider'])) {
            return ''; // Don't render if email service not configured
        }
        
        $html = '<button onclick="openEmailDrawer()" class="widget-item" style="cursor: pointer; text-align: left;">';
        $html .= '<div class="widget-content">';
        $html .= '<div class="widget-title">📧 Subscribe to Email List</div>';
        $html .= '</div>';
        $html .= '</button>';
        
        return $html;
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

