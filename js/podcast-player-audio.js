// Audio Player Controller - Namespaced for podcast drawer

class PodcastAudioPlayer {
    constructor(drawerContainer) {
        this.drawerContainer = drawerContainer;
        // Use querySelector('audio') to find the audio element regardless of ID suffix
        this.audio = drawerContainer ? drawerContainer.querySelector('audio') : null;
        console.log('PodcastAudioPlayer: Audio element found:', this.audio ? this.audio.id : 'None');
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
        this.playbackSpeed = parseFloat(PodcastStorage.get('podcast_playbackSpeed', 1.0));
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
        const savedPosition = PodcastStorage.get(`podcast_episode_${episode.guid}_position`, 0);
        if (savedPosition > 5) { // Only resume if more than 5 seconds
            this.audio.currentTime = savedPosition;
        }

        if (autoPlay) {
            this.play().catch(err => {
                console.error('Auto-play failed:', err);
                // Auto-play might be blocked, that's okay
            });
        }
    }

    /**
     * Play audio
     */
    async play() {
        try {
            await this.audio.play();
            console.log('PodcastAudioPlayer: Playback started');
            this.savePosition();
        } catch (error) {
            console.error('PodcastAudioPlayer: Play failed:', error);
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
        if (!this.audio.src) {
            console.warn('PodcastAudioPlayer: Toggle play ignored, no source loaded');
            return;
        }
        if (this.audio.paused) {
            console.log('PodcastAudioPlayer: Toggling -> Play');
            this.play();
        } else {
            console.log('PodcastAudioPlayer: Toggling -> Pause');
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
        PodcastStorage.set('podcast_playbackSpeed', speed);
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
                // Dispatch custom event for sleep timer ended
                this.audio.dispatchEvent(new CustomEvent('sleeptimerend'));
            } else {
                // Dispatch custom event for timer update
                this.audio.dispatchEvent(new CustomEvent('sleeptimerupdate', { detail: { remaining } }));
            }
        }, 1000);

        // Initial update
        const initialRemaining = minutes === -1 ? null : minutes * 60;
        this.audio.dispatchEvent(new CustomEvent('sleeptimerupdate', { detail: { remaining: initialRemaining } }));
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
            PodcastStorage.set(`podcast_episode_${this.currentEpisode.guid}_position`, this.audio.currentTime);
        }
    }

    /**
     * Load saved position
     */
    loadSavedPosition() {
        if (this.currentEpisode) {
            const savedPosition = PodcastStorage.get(`podcast_episode_${this.currentEpisode.guid}_position`, 0);
            if (savedPosition > 0) {
                this.audio.currentTime = savedPosition;
            }
        }
    }









    // Event handlers
    onPlay() {
        // Managed by event listeners
    }

    onPause() {
        this.savePosition();
    }

    onEnded() {
        this.savePosition();
        // Could auto-play next episode here
    }

    onTimeUpdate() {
        this.savePosition();
    }

    onLoadedMetadata() {
        // Managed by event listeners
    }

    onError(error) {
        console.error('Audio error:', error);
        // Error event will bubble up from audio element
    }

    onWaiting() {
        // Show loading state if needed
    }

    onCanPlay() {
        // Hide loading state if needed
    }
}

