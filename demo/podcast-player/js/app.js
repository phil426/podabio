// Main Application Controller

class PodcastApp {
    constructor() {
        this.config = CONFIG;
        this.rssParser = new RSSParser(this.config);
        this.player = new AudioPlayer();
        this.podcastData = null;
        this.currentEpisode = null;
        this.activeChapter = null;
        
        // Make player and app accessible globally for event handlers
        window.app = this;
        window.player = this.player;
        
        this.init();
    }

    async init() {
        // Initialize UI event listeners
        this.initEventListeners();
        
        // Initialize drawer tabs
        this.initDrawerTabs();
        
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
        
        // Update header
        const podcastCover = document.getElementById('podcast-cover');
        const podcastName = document.getElementById('podcast-name');
        const podcastDescription = document.getElementById('podcast-description');
        
        if (podcastCover && this.podcastData.coverImage) {
            podcastCover.src = this.podcastData.coverImage;
            podcastCover.style.display = 'block';
            
            // Extract dominant color for theme
            getDominantColor(this.podcastData.coverImage, (color) => {
                if (color) {
                    document.documentElement.style.setProperty('--primary-color', color);
                }
            });
        }
        
        if (podcastName) podcastName.textContent = this.podcastData.title || 'Podcast';
        if (podcastDescription) {
            podcastDescription.textContent = this.podcastData.description || '';
            // Add expand functionality
            this.setupDescriptionExpand(podcastDescription);
        }
        
        // Render episodes
        this.renderEpisodeList();
        
        // Hide loading, show episodes
        const loadingSkeleton = document.getElementById('loading-skeleton');
        const episodesList = document.getElementById('episodes-list');
        
        if (loadingSkeleton) loadingSkeleton.style.display = 'none';
        if (episodesList) episodesList.style.display = 'block';
    }

    /**
     * Setup description expand functionality
     */
    setupDescriptionExpand(element) {
        // Check if content is truncated
        if (element.scrollHeight > element.clientHeight) {
            const moreLink = document.createElement('a');
            moreLink.textContent = 'more...';
            moreLink.style.cssText = 'color: rgba(255,255,255,0.9); text-decoration: underline; cursor: pointer;';
            moreLink.onclick = (e) => {
                e.preventDefault();
                element.classList.add('expanded');
                moreLink.remove();
            };
            element.appendChild(document.createTextNode(' '));
            element.appendChild(moreLink);
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
            src: episode.artwork || this.podcastData.coverImage || '',
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
        });
        
        return card;
    }

    /**
     * Load episode into player
     */
    loadEpisode(episode) {
        this.currentEpisode = episode;
        this.player.loadEpisode(episode, true);
        
        // Update full player UI
        this.updateFullPlayerUI();
        
        // Open full player modal
        this.openPlayerModal();
        
        // Render show notes, chapters, etc.
        this.renderShowNotes();
        this.renderChapters();
        this.renderEpisodesPanel();
    }

    /**
     * Update full player UI
     */
    updateFullPlayerUI() {
        if (!this.currentEpisode) return;
        
        const playerTitle = document.getElementById('player-episode-title');
        const playerPodcastName = document.getElementById('player-podcast-name');
        const playerArtwork = document.getElementById('player-artwork-large');
        const durationBadge = document.getElementById('duration-badge');
        
        if (playerTitle) playerTitle.textContent = this.currentEpisode.title;
        if (playerPodcastName) playerPodcastName.textContent = this.podcastData.title || '';
        if (playerArtwork) {
            playerArtwork.src = this.currentEpisode.artwork || this.podcastData.coverImage || '';
        }
        if (durationBadge && this.currentEpisode.duration) {
            durationBadge.textContent = formatTime(this.currentEpisode.duration);
        }
    }

    /**
     * Render show notes
     */
    renderShowNotes() {
        if (!this.currentEpisode) return;
        
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
        if (!this.currentEpisode) return;
        
        const content = document.getElementById('chapters-content');
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
                    src: chapter.imageUrl,
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
        
        const content = document.getElementById('chapters-content');
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
     * Render episodes panel (compact list in drawer)
     */
    renderEpisodesPanel() {
        if (!this.podcastData || !this.podcastData.episodes) return;
        
        const content = document.getElementById('episodes-panel-content');
        if (!content) return;
        
        content.innerHTML = '';
        
        this.podcastData.episodes.forEach((episode) => {
            const item = createElement('div', {
                className: 'episode-panel-item' + (episode.guid === this.currentEpisode?.guid ? ' active' : '')
            });
            
            const artwork = createElement('img', {
                className: 'episode-panel-artwork',
                src: episode.artwork || this.podcastData.coverImage || '',
                alt: episode.title
            });
            
            const info = createElement('div', { className: 'episode-panel-info' });
            const title = createElement('div', { className: 'episode-panel-title' }, episode.title);
            const meta = createElement('div', { className: 'episode-panel-meta' });
            
            const duration = episode.duration ? formatTime(episode.duration) : '';
            const date = episode.pubDate ? formatDate(episode.pubDate) : '';
            meta.textContent = [duration, date].filter(Boolean).join(' · ');
            
            info.appendChild(title);
            info.appendChild(meta);
            
            item.appendChild(artwork);
            item.appendChild(info);
            
            item.addEventListener('click', () => {
                this.loadEpisode(episode);
            });
            
            content.appendChild(item);
        });
    }

    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Compact player controls
        const compactPlayBtn = document.getElementById('compact-play-button');
        if (compactPlayBtn) {
            compactPlayBtn.addEventListener('click', () => this.player.togglePlayPause());
        }
        
        const expandBtn = document.getElementById('expand-button');
        if (expandBtn) {
            expandBtn.addEventListener('click', () => this.openPlayerModal());
        }
        
        // Full player controls
        const playPauseLarge = document.getElementById('play-pause-large');
        if (playPauseLarge) {
            playPauseLarge.addEventListener('click', () => this.player.togglePlayPause());
        }
        
        const skipBackBtn = document.getElementById('skip-back-button');
        if (skipBackBtn) {
            skipBackBtn.addEventListener('click', () => this.player.skipBackward(15));
        }
        
        const skipForwardBtn = document.getElementById('skip-forward-button');
        if (skipForwardBtn) {
            skipForwardBtn.addEventListener('click', () => this.player.skipForward(30));
        }
        
        // Progress bar scrubbing
        const progressBarLarge = document.getElementById('progress-bar-large');
        if (progressBarLarge) {
            progressBarLarge.addEventListener('click', (e) => {
                if (this.player.audio.duration) {
                    const rect = progressBarLarge.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const time = percent * this.player.audio.duration;
                    this.player.seekTo(time);
                }
            });
        }
        
        // Compact progress bar
        const compactProgressBar = document.getElementById('compact-progress-bar');
        if (compactProgressBar) {
            compactProgressBar.addEventListener('click', (e) => {
                if (this.player.audio.duration) {
                    const rect = compactProgressBar.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const time = percent * this.player.audio.duration;
                    this.player.seekTo(time);
                }
            });
        }
        
        // Player modal close
        const modalBackdrop = document.getElementById('modal-backdrop');
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', () => this.closePlayerModal());
        }
        
        // Retry button
        const retryButton = document.getElementById('retry-button');
        if (retryButton) {
            retryButton.addEventListener('click', () => this.loadFeed());
        }
        
        // Follow button
        const followButton = document.getElementById('follow-button');
        if (followButton) {
            followButton.addEventListener('click', () => {
                followButton.classList.toggle('following');
                const icon = followButton.querySelector('i');
                const text = followButton.querySelector('span');
                if (followButton.classList.contains('following')) {
                    if (icon) icon.className = 'fas fa-check';
                    if (text) text.textContent = 'Following';
                    showToast('You\'re now following this podcast', 'success');
                } else {
                    if (icon) icon.className = 'fas fa-plus';
                    if (text) text.textContent = 'Follow';
                }
            });
        }
        
        // Swipe down to close modal
        this.initModalSwipe();
    }

    /**
     * Initialize drawer tabs
     */
    initDrawerTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.dataset.tab;
                this.switchTab(tabName);
            });
        });
    }

    /**
     * Switch drawer tab
     */
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        // Update panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.toggle('active', panel.id === `panel-${tabName}`);
        });
    }

    /**
     * Open player modal
     */
    openPlayerModal() {
        const modal = document.getElementById('player-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Switch to show notes tab by default
            this.switchTab('shownotes');
        }
    }

    /**
     * Close player modal
     */
    closePlayerModal() {
        const modal = document.getElementById('player-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    /**
     * Initialize modal swipe gesture
     */
    initModalSwipe() {
        const modalContent = document.getElementById('modal-content');
        if (!modalContent) return;
        
        let startY = 0;
        let currentY = 0;
        let isDragging = false;
        
        const handleStart = (e) => {
            startY = e.touches ? e.touches[0].clientY : e.clientY;
            isDragging = true;
        };
        
        const handleMove = (e) => {
            if (!isDragging) return;
            currentY = e.touches ? e.touches[0].clientY : e.clientY;
            const deltaY = currentY - startY;
            
            if (deltaY > 0) {
                modalContent.style.transform = `translateY(${deltaY}px)`;
            }
        };
        
        const handleEnd = () => {
            if (!isDragging) return;
            isDragging = false;
            
            const deltaY = currentY - startY;
            if (deltaY > 100) {
                this.closePlayerModal();
            }
            
            modalContent.style.transform = '';
        };
        
        modalContent.addEventListener('touchstart', handleStart);
        modalContent.addEventListener('touchmove', handleMove);
        modalContent.addEventListener('touchend', handleEnd);
        modalContent.addEventListener('mousedown', handleStart);
        modalContent.addEventListener('mousemove', handleMove);
        modalContent.addEventListener('mouseup', handleEnd);
    }

    /**
     * Initialize speed selector
     */
    initSpeedSelector() {
        const speedButton = document.getElementById('speed-button');
        if (speedButton) {
            speedButton.addEventListener('click', () => {
                this.openSpeedModal();
            });
        }
        
        const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
        const speedOptions = document.getElementById('speed-options');
        
        if (speedOptions) {
            speeds.forEach(speed => {
                const option = createElement('button', {
                    className: `speed-option ${speed === this.player.playbackSpeed ? 'active' : ''}`,
                    dataset: { speed: speed }
                }, `${speed}x`);
                
                option.addEventListener('click', () => {
                    this.player.setPlaybackSpeed(speed);
                    this.closeSpeedModal();
                });
                
                speedOptions.appendChild(option);
            });
        }
        
        const speedBackdrop = document.getElementById('speed-backdrop');
        const speedModal = document.getElementById('speed-modal');
        if (speedBackdrop && speedModal) {
            speedBackdrop.addEventListener('click', () => this.closeSpeedModal());
        }
    }

    openSpeedModal() {
        const modal = document.getElementById('speed-modal');
        if (modal) {
            modal.style.display = 'flex';
            // Update active option
            document.querySelectorAll('.speed-option').forEach(opt => {
                opt.classList.toggle('active', parseFloat(opt.dataset.speed) === this.player.playbackSpeed);
            });
        }
    }

    closeSpeedModal() {
        const modal = document.getElementById('speed-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Initialize timer selector
     */
    initTimerSelector() {
        const timerButton = document.getElementById('timer-button');
        if (timerButton) {
            timerButton.addEventListener('click', () => {
                this.openTimerModal();
            });
        }
        
        const timerOptions = document.getElementById('timer-options');
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
                    className: 'timer-option',
                    dataset: { minutes: time.value }
                }, time.label);
                
                option.addEventListener('click', () => {
                    this.player.setSleepTimer(time.value);
                    this.closeTimerModal();
                });
                
                timerOptions.appendChild(option);
            });
        }
        
        const timerBackdrop = document.getElementById('timer-backdrop');
        const timerModal = document.getElementById('timer-modal');
        if (timerBackdrop && timerModal) {
            timerBackdrop.addEventListener('click', () => this.closeTimerModal());
        }
    }

    openTimerModal() {
        const modal = document.getElementById('timer-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    closeTimerModal() {
        const modal = document.getElementById('timer-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Initialize sharing
     */
    initSharing() {
        const shareButton = document.getElementById('share-button');
        if (shareButton) {
            shareButton.addEventListener('click', () => {
                this.openShareDrawer();
            });
        }
        
        const shareCancel = document.getElementById('share-cancel');
        const shareBackdrop = document.getElementById('share-backdrop');
        if (shareCancel) {
            shareCancel.addEventListener('click', () => this.closeShareDrawer());
        }
        if (shareBackdrop) {
            shareBackdrop.addEventListener('click', () => this.closeShareDrawer());
        }
        
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
        const podcastName = this.podcastData.title;
        const shareText = `Check out "${episodeTitle}" from ${podcastName}`;
        const shareUrl = window.location.href;
        
        if (platform.action === 'copy') {
            navigator.clipboard.writeText(shareUrl).then(() => {
                showToast('Link copied!', 'success');
                this.closeShareDrawer();
            });
        } else if (navigator.share && (platform.name === 'Twitter' || platform.name === 'Facebook' || platform.name === 'WhatsApp')) {
            // Use Web Share API if available
            navigator.share({
                title: episodeTitle,
                text: shareText,
                url: shareUrl
            }).then(() => {
                this.closeShareDrawer();
            });
        } else {
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
            this.closeShareDrawer();
        }
    }

    openShareDrawer() {
        const drawer = document.getElementById('share-drawer');
        if (drawer) {
            drawer.style.display = 'block';
        }
    }

    closeShareDrawer() {
        const drawer = document.getElementById('share-drawer');
        if (drawer) {
            drawer.style.display = 'none';
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

