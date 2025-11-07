// Main Application Controller

class PodcastApp {
    constructor() {
        this.config = CONFIG;
        this.rssParser = new RSSParser(this.config);
        this.player = new AudioPlayer();
        this.podcastData = null;
        this.currentEpisode = null;
        this.activeChapter = null;
        this.currentTab = 'now-playing';
        
        // Make player and app accessible globally for event handlers
        window.app = this;
        window.player = this.player;
        
        this.init();
    }

    async init() {
        // Initialize tab navigation
        this.initTabNavigation();
        
        // Initialize UI event listeners
        this.initEventListeners();
        
        // Initialize speed selector
        this.initSpeedSelector();
        
        // Initialize timer selector
        this.initTimerSelector();
        
        // Initialize sharing
        this.initSharing();
        
        // Initialize follow section
        this.initFollowSection();
        
        // Load RSS feed
        await this.loadFeed();
    }

    /**
     * Initialize tab navigation
     */
    initTabNavigation() {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.dataset.tab;
                this.switchTab(tabName);
            });
        });
    }

    /**
     * Switch tab
     */
    switchTab(tabName) {
        this.currentTab = tabName;
        
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        // Update panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `${tabName}-panel`);
        });
    }

    /**
     * Load RSS feed
     */
    async loadFeed() {
        const loadingSkeleton = document.getElementById('loading-skeleton');
        const episodesList = document.getElementById('episodes-list');
        const errorState = document.getElementById('error-state');
        
        try {
            // Show loading state
            if (loadingSkeleton) loadingSkeleton.style.display = 'block';
            if (episodesList) episodesList.style.display = 'none';
            if (errorState) errorState.style.display = 'none';
            
            // Check cache first
            const cached = Storage.get('podcast_data');
            const cacheTime = Storage.get('podcast_data_time', 0);
            const now = Date.now();
            
            if (cached && (now - cacheTime) < this.config.cacheTTL) {
                this.podcastData = cached;
                this.renderPodcastData();
                if (loadingSkeleton) loadingSkeleton.style.display = 'none';
                return;
            }
            
            // Fetch and parse RSS
            this.podcastData = await this.rssParser.parseFeed(this.config.rssFeedUrl);
            
            // Cache the data
            Storage.set('podcast_data', this.podcastData);
            Storage.set('podcast_data_time', now);
            
            this.renderPodcastData();
            
        } catch (error) {
            console.error('Failed to load feed:', error);
            
            // Show error state
            if (loadingSkeleton) loadingSkeleton.style.display = 'none';
            if (episodesList) episodesList.style.display = 'none';
            if (errorState) errorState.style.display = 'block';
            
            showToast('Failed to load podcast feed', 'error');
        }
    }

    /**
     * Render podcast data
     */
    renderPodcastData() {
        if (!this.podcastData) return;
        
        // Extract dominant color for theme
        if (this.podcastData.coverImage) {
            getDominantColor(getProxiedImageUrl(this.podcastData.coverImage), (color) => {
                if (color) {
                    document.documentElement.style.setProperty('--primary-color', color);
                }
            });
        }
        
        // Render episodes
        this.renderEpisodeList();
        
        // Hide loading, show episodes
        const loadingSkeleton = document.getElementById('loading-skeleton');
        const episodesList = document.getElementById('episodes-list');
        
        if (loadingSkeleton) loadingSkeleton.style.display = 'none';
        if (episodesList) episodesList.style.display = 'block';
        
        // Auto-load the most recent episode (first in list)
        if (this.podcastData.episodes && this.podcastData.episodes.length > 0) {
            const mostRecentEpisode = this.podcastData.episodes[0];
            // Load but don't auto-play (let user click play)
            this.selectEpisode(mostRecentEpisode, false);
        }
    }

    /**
     * Render episode list
     */
    renderEpisodeList() {
        if (!this.podcastData || !this.podcastData.episodes) return;
        
        const episodesList = document.getElementById('episodes-list');
        if (!episodesList) return;
        
        episodesList.innerHTML = '';
        
        this.podcastData.episodes.forEach((episode, index) => {
            const card = this.createEpisodeCard(episode, index);
            episodesList.appendChild(card);
        });
    }

    /**
     * Create episode card element
     */
    createEpisodeCard(episode, index) {
        const card = createElement('div', { className: 'episode-card' });
        
        const artwork = createElement('img', {
            className: 'episode-artwork',
            src: getProxiedImageUrl(episode.artwork || this.podcastData.coverImage || ''),
            alt: episode.title
        });
        
        const info = createElement('div', { className: 'episode-info' });
        const title = createElement('div', { className: 'episode-title' }, episode.title);
        const meta = createElement('div', { className: 'episode-meta' });
        
        const duration = episode.duration ? formatTime(episode.duration) : '';
        const date = episode.pubDate ? formatDate(episode.pubDate) : '';
        
        if (duration) {
            meta.appendChild(document.createTextNode(duration));
        }
        if (duration && date) {
            meta.appendChild(document.createTextNode(' · '));
        }
        if (date) {
            meta.appendChild(document.createTextNode(date));
        }
        
        const chevron = createElement('i', { className: 'fas fa-chevron-right chevron' });
        
        info.appendChild(title);
        info.appendChild(meta);
        
        card.appendChild(artwork);
        card.appendChild(info);
        card.appendChild(chevron);
        
        card.addEventListener('click', () => {
            this.loadEpisode(episode);
            // Switch to Now Playing tab
            this.switchTab('now-playing');
        });
        
        return card;
    }

    /**
     * Load episode into player
     */
    loadEpisode(episode) {
        this.currentEpisode = episode;
        this.player.loadEpisode(episode, true);
        
        // Update Now Playing UI
        this.updateNowPlayingUI();
        
        // Switch to Now Playing tab
        this.switchTab('now-playing');
        
        // Render show notes, chapters, etc.
        this.renderShowNotes();
        this.renderChapters();
        
        // Update episode list to show active episode
        this.updateEpisodeListActive();
    }

    /**
     * Select episode (alias for loadEpisode, used by modal)
     */
    selectEpisode(episode, autoPlay = false) {
        this.currentEpisode = episode;
        this.player.loadEpisode(episode, autoPlay);
        
        // Update Now Playing UI
        this.updateNowPlayingUI();
        
        // Render show notes, chapters, etc.
        this.renderShowNotes();
        this.renderChapters();
        
        // Update episode list to show active episode
        this.updateEpisodeListActive();
    }

    /**
     * Update Now Playing UI
     */
    updateNowPlayingUI() {
        if (!this.currentEpisode) {
            // Show empty state
            const artwork = document.getElementById('now-playing-artwork');
            const placeholder = document.getElementById('artwork-placeholder');
            const title = document.getElementById('now-playing-title');
            const podcastName = document.getElementById('now-playing-podcast');
            const durationBadge = document.getElementById('duration-badge-large');
            
            if (artwork) artwork.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
            if (title) title.textContent = 'Select an episode to play';
            if (podcastName) podcastName.textContent = '';
            if (durationBadge) durationBadge.style.display = 'none';
            return;
        }
        
        const artwork = document.getElementById('now-playing-artwork');
        const placeholder = document.getElementById('artwork-placeholder');
        const title = document.getElementById('now-playing-title');
        const podcastName = document.getElementById('now-playing-podcast');
        const durationBadge = document.getElementById('duration-badge-large');
        
        const episodeArtwork = this.currentEpisode.artwork || this.podcastData.coverImage || '';
        
        if (artwork && episodeArtwork) {
            artwork.src = getProxiedImageUrl(episodeArtwork);
            artwork.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        } else {
            if (artwork) artwork.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
        }
        
        // Update full-width artwork container
        const artworkContainer = document.getElementById('now-playing-artwork-container');
        if (artworkContainer && episodeArtwork) {
            const containerImg = artworkContainer.querySelector('.episode-artwork-large');
            if (containerImg) {
                containerImg.src = getProxiedImageUrl(episodeArtwork);
                containerImg.style.display = 'block';
            }
            const containerPlaceholder = artworkContainer.querySelector('.artwork-placeholder');
            if (containerPlaceholder) {
                containerPlaceholder.style.display = 'none';
            }
        } else if (artworkContainer) {
            const containerImg = artworkContainer.querySelector('.episode-artwork-large');
            if (containerImg) containerImg.style.display = 'none';
            const containerPlaceholder = artworkContainer.querySelector('.artwork-placeholder');
            if (containerPlaceholder) containerPlaceholder.style.display = 'flex';
        }
        
        if (title) title.textContent = this.currentEpisode.title;
        if (podcastName) podcastName.textContent = this.podcastData.name || this.podcastData.title || '';
        if (durationBadge && this.currentEpisode.duration) {
            durationBadge.textContent = formatTime(this.currentEpisode.duration);
            durationBadge.style.display = 'inline-block';
        } else if (durationBadge) {
            durationBadge.style.display = 'none';
        }
    }

    /**
     * Update episode list to show active episode
     */
    updateEpisodeListActive() {
        const cards = document.querySelectorAll('.episode-card');
        cards.forEach(card => {
            card.classList.remove('active');
        });
    }

    /**
     * Render show notes
     */
    renderShowNotes() {
        if (!this.currentEpisode) {
            const content = document.getElementById('shownotes-content');
            if (content) {
                content.innerHTML = '<p class="empty-message">No episode selected</p>';
            }
            return;
        }
        
        const content = document.getElementById('shownotes-content');
        if (!content) return;
        
        let html = this.currentEpisode.description || '<p>No show notes available</p>';
        
        // Process timestamp links
        html = html.replace(/\[(\d{1,2}):(\d{2})\]/g, (match, mins, secs) => {
            const seconds = parseInt(mins) * 60 + parseInt(secs);
            return `<span class="timestamp-link" data-time="${seconds}">${mins}:${secs}</span>`;
        });
        
        // Process timestamp links in anchor tags
        html = html.replace(/<a[^>]*href=["']#t=(\d+)["'][^>]*>(.*?)<\/a>/gi, (match, seconds, text) => {
            return `<span class="timestamp-link" data-time="${seconds}">${text}</span>`;
        });
        
        content.innerHTML = html;
        
        // Add click handlers to timestamp links
        content.querySelectorAll('.timestamp-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const time = parseInt(link.dataset.time);
                this.player.seekTo(time);
            });
        });
    }

    /**
     * Render chapters
     */
    renderChapters() {
        if (!this.currentEpisode) {
            const content = document.getElementById('chapters-list');
            if (content) {
                content.innerHTML = '<div class="empty-state">No chapters available</div>';
            }
            return;
        }
        
        const content = document.getElementById('chapters-list');
        if (!content) return;
        
        if (!this.currentEpisode.chapters || this.currentEpisode.chapters.length === 0) {
            content.innerHTML = '<div class="empty-state">No chapters available</div>';
            return;
        }
        content.innerHTML = '';
        
        this.currentEpisode.chapters.forEach((chapter, index) => {
            const item = createElement('div', {
                className: 'chapter-item',
                dataset: { index: index, time: chapter.startTime }
            });
            
            if (chapter.imageUrl) {
                const img = createElement('img', {
                    className: 'chapter-image',
                    src: getProxiedImageUrl(chapter.imageUrl),
                    alt: chapter.title
                });
                item.appendChild(img);
            }
            
            const info = createElement('div', { className: 'chapter-info' });
            const title = createElement('div', { className: 'chapter-title' }, chapter.title);
            const time = createElement('div', { className: 'chapter-time' }, formatTime(chapter.startTime));
            
            info.appendChild(title);
            info.appendChild(time);
            item.appendChild(info);
            
            const chevron = createElement('i', { className: 'fas fa-chevron-right chevron' });
            item.appendChild(chevron);
            
            item.addEventListener('click', () => {
                this.player.seekTo(chapter.startTime);
            });
            
            content.appendChild(item);
        });
    }

    /**
     * Update active chapter based on current time
     */
    updateActiveChapter(currentTime) {
        if (!this.currentEpisode || !this.currentEpisode.chapters) return;
        
        const content = document.getElementById('chapters-list');
        if (!content) return;
        
        // Find active chapter
        let activeChapter = null;
        for (let i = this.currentEpisode.chapters.length - 1; i >= 0; i--) {
            if (this.currentEpisode.chapters[i].startTime <= currentTime) {
                activeChapter = i;
                break;
            }
        }
        
        // Update UI
        const items = content.querySelectorAll('.chapter-item');
        items.forEach((item, index) => {
            item.classList.toggle('active', index === activeChapter);
        });
        
        // Auto-scroll to active chapter
        if (activeChapter !== null && activeChapter !== this.activeChapter) {
            this.activeChapter = activeChapter;
            const activeItem = items[activeChapter];
            if (activeItem) {
                scrollToElement(activeItem, 100);
            }
        }
    }

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Now Playing controls
        const playPauseBtn = document.getElementById('play-pause-large-now');
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => this.player.togglePlayPause());
        }
        
        const skipBackBtn = document.getElementById('skip-back-large');
        if (skipBackBtn) {
            skipBackBtn.addEventListener('click', () => this.player.skipBackward(15));
        }
        
        const skipForwardBtn = document.getElementById('skip-forward-large');
        if (skipForwardBtn) {
            skipForwardBtn.addEventListener('click', () => this.player.skipForward(30));
        }
        
        // Progress bar scrubbing
        const progressBar = document.getElementById('progress-bar-now-playing');
        if (progressBar) {
            progressBar.addEventListener('click', (e) => {
                if (this.player.audio.duration) {
                    const rect = progressBar.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const time = percent * this.player.audio.duration;
                    this.player.seekTo(time);
                }
            });
        }
        
        // Secondary controls
        const speedBtn = document.getElementById('speed-control-btn');
        if (speedBtn) {
            speedBtn.addEventListener('click', () => this.toggleSpeedSelector());
        }
        
        const timerBtn = document.getElementById('timer-control-btn');
        if (timerBtn) {
            timerBtn.addEventListener('click', () => this.toggleTimerSelector());
        }
        
        const shareBtn = document.getElementById('share-control-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => this.handleShare());
        }
        
        // Retry button
        const retryButton = document.getElementById('retry-button');
        if (retryButton) {
            retryButton.addEventListener('click', () => this.loadFeed());
        }
        
        // Listen to player events for UI updates
        this.setupPlayerEventListeners();
    }

    /**
     * Setup player event listeners for UI updates
     */
    setupPlayerEventListeners() {
        // Update play/pause button
        this.player.audio.addEventListener('play', () => {
            const btn = document.getElementById('play-pause-large-now');
            if (btn) {
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fas fa-pause';
            }
        });
        
        this.player.audio.addEventListener('pause', () => {
            const btn = document.getElementById('play-pause-large-now');
            if (btn) {
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fas fa-play';
            }
        });
        
        // Update progress
        this.player.audio.addEventListener('timeupdate', () => {
            this.updateProgress();
            // Always update active chapter (now in now-playing tab)
            this.updateActiveChapter(this.player.audio.currentTime);
        });
        
        // Update time displays
        this.player.audio.addEventListener('loadedmetadata', () => {
            this.updateTimeDisplays();
        });
    }

    /**
     * Update progress bar
     */
    updateProgress() {
        const audio = this.player.audio;
        if (!audio.duration) return;
        
        const percent = (audio.currentTime / audio.duration) * 100;
        const fill = document.getElementById('progress-fill-now-playing');
        const scrubber = document.getElementById('progress-scrubber-now-playing');
        
        if (fill) fill.style.width = percent + '%';
        if (scrubber) scrubber.style.left = percent + '%';
        
        // Update time displays
        const currentTimeDisplay = document.getElementById('current-time-display');
        const totalTimeDisplay = document.getElementById('total-time-display');
        
        if (currentTimeDisplay) currentTimeDisplay.textContent = formatTime(audio.currentTime);
        if (totalTimeDisplay) totalTimeDisplay.textContent = formatTime(audio.duration);
    }

    /**
     * Update time displays
     */
    updateTimeDisplays() {
        const audio = this.player.audio;
        const currentTimeDisplay = document.getElementById('current-time-display');
        const totalTimeDisplay = document.getElementById('total-time-display');
        
        if (currentTimeDisplay) currentTimeDisplay.textContent = formatTime(audio.currentTime || 0);
        if (totalTimeDisplay) totalTimeDisplay.textContent = formatTime(audio.duration || 0);
    }

    /**
     * Initialize speed selector
     */
    initSpeedSelector() {
        const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
        const speedOptions = document.getElementById('speed-options-inline');
        
        if (speedOptions) {
            speeds.forEach(speed => {
                const option = createElement('button', {
                    className: `speed-option-inline ${speed === this.player.playbackSpeed ? 'active' : ''}`,
                    dataset: { speed: speed }
                }, `${speed}x`);
                
                option.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.player.setPlaybackSpeed(speed);
                    this.updateSpeedDisplay();
                    this.toggleSpeedSelector();
                });
                
                speedOptions.appendChild(option);
            });
        }
    }

    updateSpeedDisplay() {
        const display = document.getElementById('speed-display');
        if (display) {
            display.textContent = this.player.playbackSpeed + 'x';
        }
    }

    toggleSpeedSelector() {
        const selector = document.getElementById('inline-speed-selector');
        const timerSelector = document.getElementById('inline-timer-selector');
        
        // Close timer selector if open
        if (timerSelector && timerSelector.style.display !== 'none') {
            timerSelector.style.display = 'none';
        }
        
        if (selector) {
            const isVisible = selector.style.display !== 'none';
            selector.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Update active option
                document.querySelectorAll('.speed-option-inline').forEach(opt => {
                    opt.classList.toggle('active', parseFloat(opt.dataset.speed) === this.player.playbackSpeed);
                });
            }
        }
    }

    /**
     * Initialize timer selector
     */
    initTimerSelector() {
        const timerOptions = document.getElementById('timer-options-inline');
        const times = [
            { label: '15 minutes', value: 15 },
            { label: '30 minutes', value: 30 },
            { label: '45 minutes', value: 45 },
            { label: '60 minutes', value: 60 },
            { label: 'End of episode', value: -1 }
        ];
        
        if (timerOptions) {
            times.forEach(time => {
                const option = createElement('button', {
                    className: 'timer-option-inline',
                    dataset: { minutes: time.value }
                }, time.label);
                
                option.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.player.setSleepTimer(time.value);
                    this.updateTimerDisplay();
                    this.toggleTimerSelector();
                });
                
                timerOptions.appendChild(option);
            });
        }
    }

    updateTimerDisplay() {
        const display = document.getElementById('timer-display');
        if (display) {
            if (this.player.sleepTimerEndTime) {
                const remaining = Math.ceil((this.player.sleepTimerEndTime - Date.now()) / 1000 / 60);
                display.textContent = remaining + 'm';
            } else {
                display.textContent = 'Off';
            }
        }
    }

    toggleTimerSelector() {
        const selector = document.getElementById('inline-timer-selector');
        const speedSelector = document.getElementById('inline-speed-selector');
        
        // Close speed selector if open
        if (speedSelector && speedSelector.style.display !== 'none') {
            speedSelector.style.display = 'none';
        }
        
        if (selector) {
            const isVisible = selector.style.display !== 'none';
            selector.style.display = isVisible ? 'none' : 'block';
        }
    }

    /**
     * Initialize sharing
     */
    initSharing() {
        // Sharing is now handled inline via handleShare()
        
        this.setupSharePlatforms();
    }

    setupSharePlatforms() {
        const platforms = [
            { name: 'Twitter', icon: 'fab fa-twitter', url: 'https://twitter.com/intent/tweet' },
            { name: 'Facebook', icon: 'fab fa-facebook', url: 'https://www.facebook.com/sharer/sharer.php' },
            { name: 'WhatsApp', icon: 'fab fa-whatsapp', url: 'https://wa.me/' },
            { name: 'Email', icon: 'fas fa-envelope', url: 'mailto:' },
            { name: 'Copy Link', icon: 'fas fa-link', action: 'copy' }
        ];
        
        const sharePlatforms = document.getElementById('share-platforms');
        if (!sharePlatforms) return;
        
        sharePlatforms.innerHTML = '';
        
        platforms.forEach(platform => {
            const platformEl = createElement('div', { className: 'share-platform' });
            const icon = createElement('i', { className: platform.icon });
            const label = createElement('span', {}, platform.name);
            
            platformEl.appendChild(icon);
            platformEl.appendChild(label);
            
            platformEl.addEventListener('click', () => {
                this.handleShare(platform);
            });
            
            sharePlatforms.appendChild(platformEl);
        });
    }

    handleShare(platform) {
        if (!this.currentEpisode) return;
        
        const episodeTitle = this.currentEpisode.title;
        const podcastName = this.podcastData?.title || this.podcastData?.name || 'Podcast';
        const shareText = `Check out "${episodeTitle}" from ${podcastName}`;
        const shareUrl = this.currentEpisode.enclosure?.url || window.location.href;
        
        // Use Web Share API if available
        if (navigator.share) {
            navigator.share({
                title: episodeTitle,
                text: shareText,
                url: shareUrl
            }).catch(() => {
                // User cancelled or error occurred
            });
        } else if (platform && platform.action === 'copy') {
            navigator.clipboard.writeText(shareUrl).then(() => {
                showToast('Link copied!', 'success');
            });
        } else if (platform) {
            // Open share URL
            let url = platform.url;
            if (platform.name === 'Twitter') {
                url += `?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`;
            } else if (platform.name === 'Facebook') {
                url += `?u=${encodeURIComponent(shareUrl)}`;
            } else if (platform.name === 'WhatsApp') {
                url += `?text=${encodeURIComponent(shareText + ' ' + shareUrl)}`;
            } else if (platform.name === 'Email') {
                url += `?subject=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareUrl)}`;
            }
            
            window.open(url, '_blank');
        } else {
            // Fallback: try to copy to clipboard
            navigator.clipboard.writeText(shareUrl).then(() => {
                showToast('Link copied!', 'success');
            }).catch(() => {
                showToast('Unable to share', 'error');
            });
        }
    }

    /**
     * Initialize follow section
     */
    initFollowSection() {
        const followContent = document.getElementById('follow-content');
        if (!followContent) return;
        
        let html = '';
        
        // Listen On platforms
        if (this.config.platformLinks) {
            html += '<div class="follow-section"><div class="section-header">Listen On</div>';
            
            const platforms = [
                { key: 'apple', name: 'Apple Podcasts', icon: 'fab fa-apple' },
                { key: 'spotify', name: 'Spotify', icon: 'fab fa-spotify' },
                { key: 'google', name: 'Google Podcasts', icon: 'fab fa-google' }
            ];
            
            platforms.forEach(platform => {
                const url = this.config.platformLinks[platform.key];
                if (url) {
                    html += `
                        <a href="${url}" target="_blank" class="platform-button">
                            <div class="platform-icon"><i class="${platform.icon}"></i></div>
                            <div class="platform-name">${platform.name}</div>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                    `;
                }
            });
            
            html += '</div>';
        }
        
        // RSS Feed
        html += `
            <div class="follow-section">
                <div class="section-header">Subscribe via RSS</div>
                <button class="rss-button" id="rss-copy-button">
                    <i class="fas fa-rss"></i>
                    <span>Copy RSS Link</span>
                </button>
            </div>
        `;
        
        // Email signup
        html += `
            <div class="follow-section">
                <div class="section-header">Email Updates</div>
                <div class="email-form">
                    <input type="email" class="email-input" id="email-input" placeholder="Enter your email">
                    <button class="email-submit" id="email-submit">Subscribe</button>
                    <div id="email-success" class="success-message" style="display: none;">You're subscribed!</div>
                </div>
            </div>
        `;
        
        // Review section
        html += `
            <div class="review-section">
                <div class="review-header">Love the show? Leave a review!</div>
                <div class="review-subtext">Your feedback helps us grow</div>
                <div class="review-buttons">
        `;
        
        if (this.config.reviewLinks?.apple) {
            html += `<a href="${this.config.reviewLinks.apple}" target="_blank" class="review-button apple">Apple Podcasts</a>`;
        }
        if (this.config.reviewLinks?.spotify) {
            html += `<a href="${this.config.reviewLinks.spotify}" target="_blank" class="review-button spotify">Spotify</a>`;
        }
        if (this.config.reviewLinks?.google) {
            html += `<a href="${this.config.reviewLinks.google}" target="_blank" class="review-button google">Google Podcasts</a>`;
        }
        
        html += '</div></div>';
        
        followContent.innerHTML = html;
        
        // RSS copy button
        const rssCopyBtn = document.getElementById('rss-copy-button');
        if (rssCopyBtn) {
            rssCopyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(this.config.rssFeedUrl).then(() => {
                    showToast('RSS link copied!', 'success');
                });
            });
        }
        
        // Email submit
        const emailSubmit = document.getElementById('email-submit');
        const emailInput = document.getElementById('email-input');
        const emailSuccess = document.getElementById('email-success');
        if (emailSubmit && emailInput) {
            emailSubmit.addEventListener('click', () => {
                const email = emailInput.value.trim();
                if (email && email.includes('@')) {
                    // In a real app, this would send to a server
                    showToast('Email subscribed!', 'success');
                    if (emailSuccess) emailSuccess.style.display = 'block';
                    emailInput.value = '';
                } else {
                    showToast('Please enter a valid email', 'error');
                }
            });
        }
    }



}

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PodcastApp();
    });
} else {
    new PodcastApp();
}
