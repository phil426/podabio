// Main Application Controller - Namespaced for podcast drawer

class PodcastPlayerApp {
    constructor(config, drawerContainer) {
        // Config object with: rssFeedUrl, rssProxyUrl, imageProxyUrl, platformLinks, reviewLinks, socialIcons
        this.config = config || {};
        this.drawerContainer = drawerContainer || document.querySelector('.podcast-top-drawer');
        if (!this.drawerContainer) {
            console.error('PodcastPlayerApp: drawerContainer not found');
            return;
        }
        
        this.rssParser = new PodcastRSSParser(this.config.rssProxyUrl || '/api/rss-proxy.php');
        this.player = new PodcastAudioPlayer(this.drawerContainer);
        this.podcastData = null;
        this.currentEpisode = null;
        this.activeChapter = null;
        this.currentTab = 'now-playing';
        
        // Make player and app accessible globally for event handlers (namespaced)
        window.podcastPlayerApp = this;
        window.podcastPlayer = this.player;
        
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
        const tabButtons = this.drawerContainer.querySelectorAll('.tab-button');
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
        this.drawerContainer.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        // Update panels
        this.drawerContainer.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `${tabName}-panel`);
        });
    }

    /**
     * Load RSS feed
     */
    async loadFeed() {
        const loadingSkeleton = this.drawerContainer.querySelector('#loading-skeleton');
        const episodesList = this.drawerContainer.querySelector('#episodes-list');
        const errorState = this.drawerContainer.querySelector('#error-state');
        
        try {
            // Show loading state
            if (loadingSkeleton) loadingSkeleton.style.display = 'block';
            if (episodesList) episodesList.style.display = 'none';
            if (errorState) errorState.style.display = 'none';
            
            // Check cache first
            const cached = PodcastStorage.get('podcast_data');
            const cacheTime = PodcastStorage.get('podcast_data_time', 0);
            const now = Date.now();
            const cacheTTL = this.config.cacheTTL || 3600000; // Default 1 hour
            
            if (cached && (now - cacheTime) < cacheTTL) {
                this.podcastData = cached;
                this.renderPodcastData();
                if (loadingSkeleton) loadingSkeleton.style.display = 'none';
                return;
            }
            
            // Fetch and parse RSS
            if (!this.config.rssFeedUrl) {
                throw new Error('RSS feed URL not provided');
            }
            this.podcastData = await this.rssParser.parseFeed(this.config.rssFeedUrl);
            
            // Cache the data
            PodcastStorage.set('podcast_data', this.podcastData);
            PodcastStorage.set('podcast_data_time', now);
            
            this.renderPodcastData();
            
        } catch (error) {
            console.error('Failed to load feed:', error);
            
            // Show error state
            if (loadingSkeleton) loadingSkeleton.style.display = 'none';
            if (episodesList) episodesList.style.display = 'none';
            if (errorState) errorState.style.display = 'block';
            
            this.showToast('Failed to load podcast feed', 'error');
        }
    }

    /**
     * Render podcast data
     */
    renderPodcastData() {
        if (!this.podcastData) return;
        
        // Extract dominant color for theme
        if (this.podcastData.coverImage) {
            getDominantColor(getProxiedImageUrl(this.podcastData.coverImage, this.config.imageProxyUrl), (color) => {
                if (color && this.drawerContainer) {
                    this.drawerContainer.style.setProperty('--podcast-primary-color', color);
                }
            });
        }
        
        // Render episodes
        this.renderEpisodeList();
        
        // Hide loading, show episodes
        const loadingSkeleton = this.drawerContainer.querySelector('#loading-skeleton');
        const episodesList = this.drawerContainer.querySelector('#episodes-list');
        
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
        
        const episodesList = this.drawerContainer.querySelector('#episodes-list');
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
            src: getProxiedImageUrl(episode.artwork || this.podcastData.coverImage || '', this.config.imageProxyUrl),
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
        const artworkContainer = this.drawerContainer.querySelector('#now-playing-artwork-container');
        const placeholder = this.drawerContainer.querySelector('#artwork-placeholder');
        const artwork = this.drawerContainer.querySelector('#now-playing-artwork');
        
        // Check if podcast is set
        const hasPodcastData = this.podcastData && (this.podcastData.name || this.podcastData.title);
        
        if (!this.currentEpisode || !hasPodcastData) {
            // Show generic placeholder until podcast is set
            if (artwork) artwork.style.display = 'none';
            if (placeholder) placeholder.style.display = 'flex';
            return;
        }
        
        // Only show artwork if podcast is set and we have an episode
        const episodeArtwork = this.currentEpisode.artwork || this.podcastData.coverImage || '';
        
        // Update artwork container - show generic placeholder until podcast is set
        if (artworkContainer) {
            const containerImg = artworkContainer.querySelector('.episode-artwork-large');
            const containerPlaceholder = artworkContainer.querySelector('.artwork-placeholder');
            
            if (episodeArtwork) {
                // Show actual artwork when podcast is set and artwork is available
                if (containerImg) {
                    containerImg.src = getProxiedImageUrl(episodeArtwork, this.config.imageProxyUrl);
                    containerImg.style.display = 'block';
                }
                if (containerPlaceholder) {
                    containerPlaceholder.style.display = 'none';
                }
            } else {
                // Show generic placeholder if no artwork available
                if (containerImg) containerImg.style.display = 'none';
                if (containerPlaceholder) containerPlaceholder.style.display = 'flex';
            }
        }
    }

    /**
     * Update episode list to show active episode
     */
    updateEpisodeListActive() {
        const cards = this.drawerContainer.querySelectorAll('.episode-card');
        cards.forEach(card => {
            card.classList.remove('active');
        });
    }

    /**
     * Render show notes
     */
    renderShowNotes() {
        if (!this.currentEpisode) {
            const content = this.drawerContainer.querySelector('#shownotes-content');
            if (content) {
                content.innerHTML = '<p class="empty-message">No episode selected</p>';
            }
            return;
        }
        
        const content = this.drawerContainer.querySelector('#shownotes-content');
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
            const content = this.drawerContainer.querySelector('#chapters-list');
            if (content) {
                content.innerHTML = '<div class="empty-state">No chapters available</div>';
            }
            return;
        }
        
        const content = this.drawerContainer.querySelector('#chapters-list');
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
                    src: getProxiedImageUrl(chapter.imageUrl, this.config.imageProxyUrl),
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
        
        const content = this.drawerContainer.querySelector('#chapters-list');
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
        const playPauseBtn = this.drawerContainer.querySelector('#play-pause-large-now');
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => this.player.togglePlayPause());
        }
        
        const skipBackBtn = this.drawerContainer.querySelector('#skip-back-large');
        if (skipBackBtn) {
            skipBackBtn.addEventListener('click', () => this.player.skipBackward(10));
        }
        
        const skipForwardBtn = this.drawerContainer.querySelector('#skip-forward-large');
        if (skipForwardBtn) {
            skipForwardBtn.addEventListener('click', () => this.player.skipForward(45));
        }
        
        // Progress bar scrubbing
        const progressBar = this.drawerContainer.querySelector('#progress-bar-now-playing');
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
        const speedBtn = this.drawerContainer.querySelector('#speed-control-btn');
        if (speedBtn) {
            speedBtn.addEventListener('click', () => this.toggleSpeedSelector());
        }
        
        const timerBtn = this.drawerContainer.querySelector('#timer-control-btn');
        if (timerBtn) {
            timerBtn.addEventListener('click', () => this.toggleTimerSelector());
        }
        
        const shareBtn = this.drawerContainer.querySelector('#share-control-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => this.handleShare());
        }
        
        // Retry button
        const retryButton = this.drawerContainer.querySelector('#retry-button');
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
            const btn = this.drawerContainer.querySelector('#play-pause-large-now');
            if (btn) {
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fas fa-pause';
            }
        });
        
        this.player.audio.addEventListener('pause', () => {
            const btn = this.drawerContainer.querySelector('#play-pause-large-now');
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
        const fill = this.drawerContainer.querySelector('#progress-fill-now-playing');
        const scrubber = this.drawerContainer.querySelector('#progress-scrubber-now-playing');
        
        if (fill) fill.style.width = percent + '%';
        if (scrubber) scrubber.style.left = percent + '%';
        
        this.updateTimeDisplays();
    }

    /**
     * Update time displays
     */
    updateTimeDisplays() {
        const audio = this.player.audio;
        if (!audio.duration) return;

        const currentTimeDisplay = this.drawerContainer.querySelector('#current-time-display');
        const remainingTimeDisplay = this.drawerContainer.querySelector('#remaining-time-display');

        if (currentTimeDisplay) currentTimeDisplay.textContent = formatTime(audio.currentTime || 0);
        if (remainingTimeDisplay) {
            const remaining = audio.duration - (audio.currentTime || 0);
            remainingTimeDisplay.textContent = '-' + formatTime(remaining);
        }
    }

    /**
     * Initialize speed selector
     */
    initSpeedSelector() {
        const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
        const speedOptions = this.drawerContainer.querySelector('#speed-options-inline');
        
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
        const display = this.drawerContainer.querySelector('#speed-display');
        if (display) {
            display.textContent = this.player.playbackSpeed + 'x';
        }
    }

    toggleSpeedSelector() {
        const selector = this.drawerContainer.querySelector('#inline-speed-selector');
        const timerSelector = this.drawerContainer.querySelector('#inline-timer-selector');
        
        // Close timer selector if open
        if (timerSelector && timerSelector.style.display !== 'none') {
            timerSelector.style.display = 'none';
        }
        
        if (selector) {
            const isVisible = selector.style.display !== 'none';
            selector.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Update active option
                this.drawerContainer.querySelectorAll('.speed-option-inline').forEach(opt => {
                    opt.classList.toggle('active', parseFloat(opt.dataset.speed) === this.player.playbackSpeed);
                });
            }
        }
    }

    /**
     * Initialize timer selector
     */
    initTimerSelector() {
        const timerOptions = this.drawerContainer.querySelector('#timer-options-inline');
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
        const display = this.drawerContainer.querySelector('#timer-display');
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
        const selector = this.drawerContainer.querySelector('#inline-timer-selector');
        const speedSelector = this.drawerContainer.querySelector('#inline-speed-selector');
        
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
        
        const sharePlatforms = this.drawerContainer.querySelector('#share-platforms');
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
                this.showToast('Link copied!', 'success');
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
                this.showToast('Link copied!', 'success');
            }).catch(() => {
                this.showToast('Unable to share', 'error');
            });
        }
    }
    
    /**
     * Show toast notification (scoped to drawer)
     */
    showToast(message, type = 'info') {
        showToast(message, type, this.drawerContainer);
    }

    /**
     * Initialize follow section
     * Updated to display social icons to the right of "Listen On" using flexbox layout
     * Email subscription section is hidden (TODO: Integrate email subscription feature at a later date)
     */
    initFollowSection() {
        const followContent = this.drawerContainer.querySelector('#follow-content');
        if (!followContent) return;
        
        let html = '';
        
        // Listen On section with Social Icons layout
        const hasPlatformLinks = this.config.platformLinks && Object.keys(this.config.platformLinks).length > 0;
        const hasSocialIcons = this.config.socialIcons && this.config.socialIcons.length > 0;
        
        if (hasPlatformLinks || hasSocialIcons) {
            html += '<div class="follow-section listen-on-section">';
            html += '<div class="section-header">Listen On</div>';
            html += '<div class="platform-buttons-container">';
            
            // Platform buttons
            if (hasPlatformLinks) {
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
            }
            
            html += '</div>'; // Close platform-buttons-container
            
            // Social Icons to the right
            if (hasSocialIcons) {
                html += '<div class="social-icons-container">';
                this.config.socialIcons.forEach(icon => {
                    // Map platform names to Font Awesome icons
                    const platformIcons = {
                        'facebook' => 'fab fa-facebook',
                        'twitter' => 'fab fa-twitter',
                        'instagram' => 'fab fa-instagram',
                        'linkedin' => 'fab fa-linkedin',
                        'youtube' => 'fab fa-youtube',
                        'tiktok' => 'fab fa-tiktok',
                        'snapchat' => 'fab fa-snapchat',
                        'pinterest' => 'fab fa-pinterest',
                        'reddit' => 'fab fa-reddit',
                        'discord' => 'fab fa-discord',
                        'twitch' => 'fab fa-twitch',
                        'github' => 'fab fa-github',
                        'apple_podcasts' => 'fas fa-podcast',
                        'spotify' => 'fab fa-spotify',
                        'youtube_music' => 'fab fa-youtube',
                        'iheart_radio' => 'fas fa-heart',
                        'amazon_music' => 'fab fa-amazon'
                    };
                    
                    const iconClass = platformIcons[icon.platform_name] || 'fas fa-link';
                    html += `
                        <a href="${icon.url}" target="_blank" rel="noopener noreferrer" class="social-icon" title="${icon.platform_name}">
                            <i class="${iconClass}"></i>
                        </a>
                    `;
                });
                html += '</div>'; // Close social-icons-container
            }
            
            html += '</div>'; // Close listen-on-section
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
        
        // Email signup - HIDDEN FOR NOW
        // TODO: Integrate email subscription feature at a later date
        // html += `
        //     <div class="follow-section">
        //         <div class="section-header">Email Updates</div>
        //         <div class="email-form">
        //             <input type="email" class="email-input" id="email-input" placeholder="Enter your email">
        //             <button class="email-submit" id="email-submit">Subscribe</button>
        //             <div id="email-success" class="success-message" style="display: none;">You're subscribed!</div>
        //         </div>
        //     </div>
        // `;
        
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
        const rssCopyBtn = this.drawerContainer.querySelector('#rss-copy-button');
        if (rssCopyBtn) {
            rssCopyBtn.addEventListener('click', () => {
                const rssLink = this.config.rssFeedUrl;
                if (navigator.clipboard && rssLink) {
                    navigator.clipboard.writeText(rssLink).then(() => {
                        this.showToast('RSS link copied!', 'success');
                    }).catch(err => {
                        console.error('Failed to copy RSS link: ', err);
                        this.showToast('Failed to copy RSS link.', 'error');
                    });
                }
            });
        }
    }



}

// Export class for use in page.php
// Usage: new PodcastPlayerApp(config, drawerContainer)
// where config = { rssFeedUrl, rssProxyUrl, imageProxyUrl, platformLinks, reviewLinks, socialIcons }
