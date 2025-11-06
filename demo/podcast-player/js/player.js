// Audio Player Controller

class AudioPlayer {
    constructor() {
        this.audio = document.getElementById('audio-player');
        this.currentEpisode = null;
        this.playbackSpeed = parseFloat(Storage.get('playbackSpeed', 1.0));
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
        const savedPosition = Storage.get(`episode_${episode.guid}_position`, 0);
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
    skipBackward(seconds = 15) {
        const newTime = Math.max(0, this.audio.currentTime - seconds);
        this.seekTo(newTime);
    }

    /**
     * Skip forward
     */
    skipForward(seconds = 30) {
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
        Storage.set('playbackSpeed', speed);
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
                showToast('Sleep timer ended', 'info');
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
            Storage.set(`episode_${this.currentEpisode.guid}_position`, this.audio.currentTime);
        }
    }

    /**
     * Load saved position
     */
    loadSavedPosition() {
        if (this.currentEpisode) {
            const savedPosition = Storage.get(`episode_${this.currentEpisode.guid}_position`, 0);
            if (savedPosition > 0) {
                this.audio.currentTime = savedPosition;
            }
        }
    }

    /**
     * Update UI elements
     */
    updateUI() {
        // Update compact player
        const compactTitle = document.getElementById('compact-title');
        const compactArtist = document.getElementById('compact-artist');
        const compactArtwork = document.getElementById('compact-artwork');
        
        if (this.currentEpisode) {
            if (compactTitle) compactTitle.textContent = this.currentEpisode.title;
            if (compactArtist) compactArtist.textContent = this.currentEpisode.title; // Use podcast name from global state
            if (compactArtwork && this.currentEpisode.artwork) {
                compactArtwork.src = this.currentEpisode.artwork;
                compactArtwork.style.display = 'block';
            }
        }
        
        // Update full player
        const playerTitle = document.getElementById('player-episode-title');
        const playerArtwork = document.getElementById('player-artwork-large');
        
        if (this.currentEpisode) {
            if (playerTitle) playerTitle.textContent = this.currentEpisode.title;
            if (playerArtwork && this.currentEpisode.artwork) {
                playerArtwork.src = this.currentEpisode.artwork;
            }
        }
        
        this.updatePlayButton();
        this.updateSpeedUI();
    }

    /**
     * Update play button state
     */
    updatePlayButton() {
        const isPlaying = this.isPlaying();
        const playButtons = document.querySelectorAll('.play-button, .play-pause-large');
        
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
        const speedValue = document.getElementById('speed-value');
        if (speedValue) {
            speedValue.textContent = `${this.playbackSpeed}x`;
        }
    }

    /**
     * Update timer UI
     */
    updateTimerUI(secondsRemaining) {
        const timerStatus = document.getElementById('timer-status');
        if (timerStatus) {
            if (secondsRemaining === null) {
                timerStatus.textContent = 'Off';
            } else {
                const minutes = Math.floor(secondsRemaining / 60);
                timerStatus.textContent = minutes > 0 ? `${minutes} min` : 'End';
            }
        }
    }

    /**
     * Update progress bar
     */
    updateProgress() {
        if (!this.audio.duration || this.isDragging) return;
        
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        
        // Update compact progress
        const compactProgressFill = document.getElementById('compact-progress-fill');
        if (compactProgressFill) {
            compactProgressFill.style.width = `${progress}%`;
        }
        
        // Update full player progress
        const progressFillLarge = document.getElementById('progress-fill-large');
        const progressScrubber = document.getElementById('progress-scrubber');
        if (progressFillLarge) {
            progressFillLarge.style.width = `${progress}%`;
        }
        if (progressScrubber) {
            progressScrubber.style.left = `${progress}%`;
        }
        
        // Update time displays
        const currentTimeEl = document.getElementById('current-time');
        const compactTimeEl = document.querySelector('.player-title + .player-artist');
        
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(this.audio.currentTime);
        }
    }

    // Event handlers
    onPlay() {
        this.updatePlayButton();
        this.showCompactPlayer();
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
        this.updateProgress();
        this.savePosition();
        
        // Update chapters if available
        if (window.app && window.app.updateActiveChapter) {
            window.app.updateActiveChapter(this.getCurrentTime());
        }
    }

    onLoadedMetadata() {
        const totalTimeEl = document.getElementById('total-time');
        if (totalTimeEl && this.audio.duration) {
            totalTimeEl.textContent = formatTime(this.audio.duration);
        }
        this.updateProgress();
    }

    onError(error) {
        console.error('Audio error:', error);
        showToast('Error playing audio', 'error');
    }

    onWaiting() {
        // Show loading state if needed
    }

    onCanPlay() {
        // Hide loading state if needed
    }

    /**
     * Show compact player bar
     */
    showCompactPlayer() {
        const compactPlayer = document.getElementById('compact-player-bar');
        if (compactPlayer) {
            compactPlayer.style.display = 'flex';
        }
    }

    /**
     * Hide compact player bar
     */
    hideCompactPlayer() {
        const compactPlayer = document.getElementById('compact-player-bar');
        if (compactPlayer) {
            compactPlayer.style.display = 'none';
        }
    }
}

