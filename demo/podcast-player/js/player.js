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
        // Update full player modal via app
        if (window.app) {
            window.app.updateFullPlayerModal();
        }
        
        this.updatePlayButton();
        this.updateSpeedUI();
    }

    /**
     * Update play button state
     */
    updatePlayButton() {
        const isPlaying = this.isPlaying();
        const playButtons = document.querySelectorAll('.play-pause-large-now, .modal-play-pause, #nav-play-btn');
        
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
        const speedValue = document.getElementById('speed-display');
        if (speedValue) {
            speedValue.textContent = `${this.playbackSpeed}x`;
        }
    }

    /**
     * Update timer UI
     */
    updateTimerUI(secondsRemaining) {
        const timerStatus = document.getElementById('timer-display');
        if (timerStatus) {
            if (secondsRemaining === null) {
                timerStatus.textContent = 'Off';
            } else {
                const minutes = Math.floor(secondsRemaining / 60);
                timerStatus.textContent = minutes > 0 ? `${minutes}m` : 'End';
            }
        }
    }

    /**
     * Update progress bar
     */
    updateProgress() {
        if (!this.audio.duration || this.isDragging) return;
        
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        
        // Update Now Playing progress
        const progressFill = document.getElementById('progress-fill-now-playing');
        const progressScrubber = document.getElementById('progress-scrubber-now-playing');
        if (progressFill) {
            progressFill.style.width = `${progress}%`;
        }
        if (progressScrubber) {
            progressScrubber.style.left = `${progress}%`;
        }
        
        // Update modal progress
        const modalProgressFill = document.getElementById('modal-progress-fill');
        const modalProgressScrubber = document.getElementById('modal-progress-scrubber');
        if (modalProgressFill) {
            modalProgressFill.style.width = `${progress}%`;
        }
        if (modalProgressScrubber) {
            modalProgressScrubber.style.left = `${progress}%`;
        }
        
        // Update time displays
        const currentTimeEl = document.getElementById('current-time-display');
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(this.audio.currentTime);
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
        this.updateProgress();
        this.savePosition();
        
        // Update time displays
        const currentTimeEl = document.getElementById('current-time-display');
        const remainingTimeEl = document.getElementById('remaining-time-display');
        const totalTimeEl = document.getElementById('total-time-display');
        const modalCurrentTime = document.getElementById('modal-current-time');
        const modalTotalTime = document.getElementById('modal-total-time');
        
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(this.audio.currentTime || 0);
        }
        
        if (remainingTimeEl && this.audio.duration) {
            const remaining = this.audio.duration - (this.audio.currentTime || 0);
            remainingTimeEl.textContent = '-' + formatTime(remaining);
        }
        
        if (totalTimeEl && this.audio.duration) {
            totalTimeEl.textContent = formatTime(this.audio.duration);
        }
        
        if (modalCurrentTime) {
            modalCurrentTime.textContent = formatTime(this.audio.currentTime || 0);
        }
        if (modalTotalTime && this.audio.duration) {
            modalTotalTime.textContent = formatTime(this.audio.duration);
        }
        
        // Update chapters if available
        if (window.app && window.app.updateActiveChapter) {
            window.app.updateActiveChapter(this.getCurrentTime());
        }
    }

    onLoadedMetadata() {
        const totalTimeEl = document.getElementById('total-time-display');
        const modalTotalTime = document.getElementById('modal-total-time');
        if (totalTimeEl && this.audio.duration) {
            totalTimeEl.textContent = formatTime(this.audio.duration);
        }
        if (modalTotalTime && this.audio.duration) {
            modalTotalTime.textContent = formatTime(this.audio.duration);
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
     * Show compact player bar (no-op - compact player removed)
     */
    showCompactPlayer() {
        // Compact player removed in tabbed layout redesign
    }

    /**
     * Hide compact player bar (no-op - compact player removed)
     */
    hideCompactPlayer() {
        // Compact player removed in tabbed layout redesign
    }
}

