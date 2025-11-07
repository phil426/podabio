// Audio Player Controller - Namespaced for podcast drawer

class PodcastAudioPlayer {
    constructor(drawerContainer) {
        this.drawerContainer = drawerContainer;
        this.audio = drawerContainer ? drawerContainer.querySelector('#podcast-audio-player') : null;
        if (!this.audio) {
            // Create audio element if it doesn't exist
            this.audio = document.createElement('audio');
            this.audio.id = 'podcast-audio-player';
            this.audio.preload = 'metadata';
            if (drawerContainer) {
                drawerContainer.appendChild(this.audio);
            } else {
                document.body.appendChild(this.audio);
            }
        }
        
        this.currentEpisode = null;
        this.playbackSpeed = parseFloat(Storage.get('podcast_playbackSpeed', 1.0));
        this.sleepTimer = null;
        this.sleepTimerEndTime = null;
        this.isDragging = false;
        
        this.init();
    }

    init() {
        if (!this.audio) return;
        
        this.audio.playbackRate = this.playbackSpeed;
        
        // Event listeners
        this.audio.addEventListener('play', () => this.onPlay());
        this.audio.addEventListener('pause', () => this.onPause());
        this.audio.addEventListener('ended', () => this.onEnded());
        this.audio.addEventListener('timeupdate', () => this.onTimeUpdate());
        this.audio.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        this.audio.addEventListener('error', (e) => this.onError(e));
        this.audio.addEventListener('waiting', () => this.onWaiting());
        this.audio.addEventListener('canplay', () => this.onCanPlay());
        
        // Load saved position
        this.loadSavedPosition();
    }

    /**
     * Load episode
     */
    loadEpisode(episode, autoPlay = false) {
        if (!episode || !episode.audioUrl) {
            console.error('Invalid episode:', episode);
            return;
        }

        this.currentEpisode = episode;
        this.audio.src = episode.audioUrl;
        
        // Try to resume from saved position
        const savedPosition = Storage.get(`podcast_episode_${episode.guid}_position`, 0);
        if (savedPosition > 5) { // Only resume if more than 5 seconds
            this.audio.currentTime = savedPosition;
        }
        
        if (autoPlay) {
            this.play().catch(err => {
                console.error('Auto-play failed:', err);
                // Auto-play might be blocked, that's okay
            });
        }
        
        this.updateUI();
    }

    /**
     * Play audio
     */
    async play() {
        try {
            await this.audio.play();
            this.savePosition();
        } catch (error) {
            console.error('Play failed:', error);
            throw error;
        }
    }

    /**
     * Pause audio
     */
    pause() {
        this.audio.pause();
        this.savePosition();
    }

    /**
     * Toggle play/pause
     */
    togglePlayPause() {
        if (this.audio.paused) {
            this.play();
        } else {
            this.pause();
        }
    }

    /**
     * Seek to position (in seconds)
     */
    seekTo(seconds) {
        if (!this.audio.duration) return;
        
        const clampedTime = Math.max(0, Math.min(this.audio.duration, seconds));
        this.audio.currentTime = clampedTime;
        this.savePosition();
    }

    /**
     * Skip backward
     */
    skipBackward(seconds = 10) {
        const newTime = Math.max(0, this.audio.currentTime - seconds);
        this.seekTo(newTime);
    }

    /**
     * Skip forward
     */
    skipForward(seconds = 45) {
        if (!this.audio.duration) return;
        
        const newTime = Math.min(this.audio.duration, this.audio.currentTime + seconds);
        this.seekTo(newTime);
    }

    /**
     * Set playback speed
     */
    setPlaybackSpeed(speed) {
        this.playbackSpeed = speed;
        this.audio.playbackRate = speed;
        Storage.set('podcast_playbackSpeed', speed);
        this.updateSpeedUI();
    }

    /**
     * Set sleep timer
     */
    setSleepTimer(minutes) {
        this.clearSleepTimer();
        
        if (minutes === 0) {
            return; // Cancel timer
        }
        
        // If minutes is -1, set to end of episode
        if (minutes === -1) {
            if (this.audio.duration) {
                const remaining = this.audio.duration - this.audio.currentTime;
                this.sleepTimerEndTime = Date.now() + (remaining * 1000);
            } else {
                return; // Can't set to end if duration unknown
            }
        } else {
            this.sleepTimerEndTime = Date.now() + (minutes * 60 * 1000);
        }
        
        // Check every second
        this.sleepTimer = setInterval(() => {
            const remaining = Math.max(0, Math.floor((this.sleepTimerEndTime - Date.now()) / 1000));
            
            if (remaining === 0) {
                this.pause();
                this.clearSleepTimer();
                if (window.podcastPlayerApp) {
                    window.podcastPlayerApp.showToast('Sleep timer ended', 'info');
                }
            } else {
                this.updateTimerUI(remaining);
            }
        }, 1000);
        
        this.updateTimerUI(minutes === -1 ? null : minutes * 60);
    }

    /**
     * Clear sleep timer
     */
    clearSleepTimer() {
        if (this.sleepTimer) {
            clearInterval(this.sleepTimer);
            this.sleepTimer = null;
            this.sleepTimerEndTime = null;
        }
        this.updateTimerUI(null);
    }

    /**
     * Get current time
     */
    getCurrentTime() {
        return this.audio.currentTime || 0;
    }

    /**
     * Get duration
     */
    getDuration() {
        return this.audio.duration || 0;
    }

    /**
     * Check if playing
     */
    isPlaying() {
        return !this.audio.paused;
    }

    /**
     * Save current position
     */
    savePosition() {
        if (this.currentEpisode && this.audio.currentTime) {
            Storage.set(`podcast_episode_${this.currentEpisode.guid}_position`, this.audio.currentTime);
        }
    }

    /**
     * Load saved position
     */
    loadSavedPosition() {
        if (this.currentEpisode) {
            const savedPosition = Storage.get(`podcast_episode_${this.currentEpisode.guid}_position`, 0);
            if (savedPosition > 0) {
                this.audio.currentTime = savedPosition;
            }
        }
    }

    /**
     * Update UI elements
     */
    updateUI() {
        this.updatePlayButton();
        this.updateSpeedUI();
    }

    /**
     * Update play button state
     */
    updatePlayButton() {
        if (!this.drawerContainer) return;
        
        const isPlaying = this.isPlaying();
        const playButtons = this.drawerContainer.querySelectorAll('.play-pause-large-now');
        
        playButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
            }
        });
    }

    /**
     * Update speed UI
     */
    updateSpeedUI() {
        if (!this.drawerContainer) return;
        
        const speedValue = this.drawerContainer.querySelector('#speed-display');
        if (speedValue) {
            speedValue.textContent = `${this.playbackSpeed}x`;
        }
    }

    /**
     * Update timer UI
     */
    updateTimerUI(secondsRemaining) {
        if (!this.drawerContainer) return;
        
        const timerStatus = this.drawerContainer.querySelector('#timer-display');
        if (timerStatus) {
            if (secondsRemaining === null) {
                timerStatus.textContent = 'Off';
            } else {
                const minutes = Math.floor(secondsRemaining / 60);
                timerStatus.textContent = minutes > 0 ? `${minutes}m` : 'End';
            }
        }
    }

    // Event handlers
    onPlay() {
        this.updatePlayButton();
    }

    onPause() {
        this.updatePlayButton();
        this.savePosition();
    }

    onEnded() {
        this.updatePlayButton();
        this.savePosition();
        // Could auto-play next episode here
    }

    onTimeUpdate() {
        // Progress and time displays are now handled by app.js's updateProgress and updateTimeDisplays
        if (window.podcastPlayerApp && window.podcastPlayerApp.updateProgress) {
            window.podcastPlayerApp.updateProgress();
        }
        this.savePosition();

        // Update chapters if available
        if (window.podcastPlayerApp && window.podcastPlayerApp.updateActiveChapter) {
            window.podcastPlayerApp.updateActiveChapter(this.getCurrentTime());
        }
    }

    onLoadedMetadata() {
        this.updateProgress();
    }

    onError(error) {
        console.error('Audio error:', error);
        if (window.podcastPlayerApp) {
            window.podcastPlayerApp.showToast('Error playing audio', 'error');
        }
    }

    onWaiting() {
        // Show loading state if needed
    }

    onCanPlay() {
        // Hide loading state if needed
    }
}

