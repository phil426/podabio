<?php
/**
 * Podcast Player Template
 * 
 * Renders the podcast player drawer content.
 * 
 * Expected variables:
 * @var array $page
 * @var string $idSuffix
 * @var bool $enablePreviewMode
 * @var string $drawerStyles Optional inline styles for the drawer container
 * @var bool $showCloseButton Optional flag to show/hide the close button (default: true)
 */
$idSuffix = $idSuffix ?? '';
$drawerStyles = $drawerStyles ?? '';
$showCloseButton = $showCloseButton ?? true;
?>
<div class="podcast-top-drawer" id="podcast-top-drawer<?php echo $idSuffix; ?>" style="<?php echo $drawerStyles; ?>">
    <!-- Tab Navigation -->
    <nav class="tab-navigation" id="tab-navigation<?php echo $idSuffix; ?>">
        <button class="tab-button active" data-tab="now-playing" id="tab-now-playing<?php echo $idSuffix; ?>">Now
            Playing</button>
        <button class="tab-button" data-tab="follow" id="tab-follow<?php echo $idSuffix; ?>">Follow</button>
        <button class="tab-button" data-tab="details" id="tab-details<?php echo $idSuffix; ?>">Details</button>
        <button class="tab-button" data-tab="episodes" id="tab-episodes<?php echo $idSuffix; ?>">Episodes</button>
    </nav>

    <!-- Tab Content Container -->
    <div class="tab-content-container" id="tab-content-container<?php echo $idSuffix; ?>" <?php if (!empty($drawerStyles))
           echo 'style="flex: 1; overflow-y: auto;"'; ?>>
        <!-- Now Playing Tab -->
        <div class="tab-panel active" id="now-playing-panel<?php echo $idSuffix; ?>">
            <div class="now-playing-content">
                <!-- Full Width Cover Artwork -->
                <div class="episode-artwork-fullwidth" id="now-playing-artwork-container<?php echo $idSuffix; ?>">
                    <?php if (!empty($page['cover_image_url'])): ?>
                        <img class="episode-artwork-large" id="now-playing-artwork<?php echo $idSuffix; ?>"
                            src="<?php echo h(normalizeImageUrl($page['cover_image_url'])); ?>" alt="Podcast Cover">
                        <div class="artwork-placeholder" id="artwork-placeholder<?php echo $idSuffix; ?>"
                            style="display: none;">
                            <i class="fas fa-music"></i>
                        </div>
                    <?php else: ?>
                        <img class="episode-artwork-large" id="now-playing-artwork<?php echo $idSuffix; ?>" src=""
                            alt="Episode Artwork" style="display: none;">
                        <div class="artwork-placeholder" id="artwork-placeholder<?php echo $idSuffix; ?>">
                            <i class="fas fa-music"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Progress Section Overlay -->
                    <div class="progress-section-large" id="progress-section-now-playing<?php echo $idSuffix; ?>">
                        <div class="time-display">
                            <span id="current-time-display<?php echo $idSuffix; ?>">0:00</span>
                            <span id="remaining-time-display<?php echo $idSuffix; ?>">-0:00</span>
                        </div>
                        <div class="progress-bar-now-playing" id="progress-bar-now-playing<?php echo $idSuffix; ?>">
                            <div class="progress-fill-now-playing"
                                id="progress-fill-now-playing<?php echo $idSuffix; ?>"></div>
                            <div class="progress-scrubber-now-playing"
                                id="progress-scrubber-now-playing<?php echo $idSuffix; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Player Controls -->
                <div class="player-controls-section">
                    <div class="primary-controls">
                        <button class="control-button-large skip-back-large"
                            id="skip-back-large<?php echo $idSuffix; ?>" aria-label="Skip back 10 seconds">
                            <span class="skip-label-large">10</span>
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="control-button-large play-pause-large-now"
                            id="play-pause-large-now<?php echo $idSuffix; ?>" aria-label="Play/Pause">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="control-button-large skip-forward-large"
                            id="skip-forward-large<?php echo $idSuffix; ?>" aria-label="Skip forward 45 seconds">
                            <span class="skip-label-large">45</span>
                            <i class="fas fa-forward"></i>
                        </button>
                    </div>

                    <div class="secondary-controls-bar">
                        <button class="secondary-control-btn speed-control-btn"
                            id="speed-control-btn<?php echo $idSuffix; ?>" aria-label="Playback Speed">
                            <span id="speed-display<?php echo $idSuffix; ?>">1x</span>
                        </button>
                        <button class="secondary-control-btn timer-control-btn"
                            id="timer-control-btn<?php echo $idSuffix; ?>" aria-label="Sleep Timer">
                            <i class="fas fa-moon"></i>
                            <span id="timer-display<?php echo $idSuffix; ?>">Off</span>
                        </button>
                        <button class="secondary-control-btn share-control-btn"
                            id="share-control-btn<?php echo $idSuffix; ?>" aria-label="Share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- Follow Tab -->
        <div class="tab-panel" id="follow-panel<?php echo $idSuffix; ?>">
            <div class="follow-tab-content">
                <div id="follow-content<?php echo $idSuffix; ?>"></div>
            </div>
        </div>

        <!-- Details Tab -->
        <div class="tab-panel" id="details-panel<?php echo $idSuffix; ?>">
            <div class="details-tab-content">
                <div class="details-section-modern" id="shownotes-section<?php echo $idSuffix; ?>">
                    <div class="section-header-modern" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-file-alt section-icon"></i>
                            <h2 class="section-title-modern">Show Notes</h2>
                        </div>
                        <div class="text-size-controls" id="text-size-controls<?php echo $idSuffix; ?>">
                            <button class="text-size-btn" data-size="small" aria-label="Small text">A</button>
                            <button class="text-size-btn active" data-size="medium" aria-label="Medium text">A</button>
                            <button class="text-size-btn" data-size="large" aria-label="Large text">A</button>
                        </div>
                    </div>
                    <div class="shownotes-content-modern" id="shownotes-content<?php echo $idSuffix; ?>">
                        <div class="empty-state-modern">
                            <i class="fas fa-info-circle"></i>
                            <p>No episode selected</p>
                        </div>
                    </div>
                </div>

                <div class="details-section-modern" id="chapters-section<?php echo $idSuffix; ?>">
                    <div class="section-header-modern">
                        <i class="fas fa-list-ul section-icon"></i>
                        <h2 class="section-title-modern">Chapters</h2>
                    </div>
                    <div class="chapters-list-modern" id="chapters-list<?php echo $idSuffix; ?>">
                        <div class="empty-state-modern">
                            <i class="fas fa-list"></i>
                            <p>No chapters available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Episodes Tab -->
        <div class="tab-panel" id="episodes-panel<?php echo $idSuffix; ?>">
            <div class="episodes-tab-content">
                <div class="episodes-header" id="episodes-header<?php echo $idSuffix; ?>" style="display: none;">
                    <h2 class="episodes-title">All Episodes</h2>
                    <span class="episodes-count" id="episodes-count<?php echo $idSuffix; ?>"></span>
                </div>

                <div class="loading-skeleton-modern" id="loading-skeleton<?php echo $idSuffix; ?>">
                    <div class="skeleton-item-modern">
                        <div class="skeleton-artwork"></div>
                        <div class="skeleton-text">
                            <div class="skeleton-line skeleton-line-title"></div>
                            <div class="skeleton-line skeleton-line-meta"></div>
                        </div>
                    </div>
                    <div class="skeleton-item-modern">
                        <div class="skeleton-artwork"></div>
                        <div class="skeleton-text">
                            <div class="skeleton-line skeleton-line-title"></div>
                            <div class="skeleton-line skeleton-line-meta"></div>
                        </div>
                    </div>
                    <div class="skeleton-item-modern">
                        <div class="skeleton-artwork"></div>
                        <div class="skeleton-text">
                            <div class="skeleton-line skeleton-line-title"></div>
                            <div class="skeleton-line skeleton-line-meta"></div>
                        </div>
                    </div>
                    <div class="skeleton-item-modern">
                        <div class="skeleton-artwork"></div>
                        <div class="skeleton-text">
                            <div class="skeleton-line skeleton-line-title"></div>
                            <div class="skeleton-line skeleton-line-meta"></div>
                        </div>
                    </div>
                </div>

                <div class="episodes-list-modern" id="episodes-list<?php echo $idSuffix; ?>" style="display: none;">
                </div>

                <div class="error-state-modern" id="error-state<?php echo $idSuffix; ?>" style="display: none;">
                    <div class="error-icon-wrapper">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="error-title">Failed to load episodes</h3>
                    <p class="error-message">Please check your connection and try again.</p>
                    <button class="retry-button-modern" id="retry-button<?php echo $idSuffix; ?>">
                        <i class="fas fa-redo"></i>
                        <span>Retry</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast<?php echo $idSuffix; ?>" style="display: none;">
        <span id="toast-message<?php echo $idSuffix; ?>"></span>
    </div>

    <audio id="podcast-audio-player<?php echo $idSuffix; ?>" preload="metadata"></audio>

    <?php if ($showCloseButton): ?>
    <div class="podcast-global-close-footer">
        <button class="floating-close-btn" id="close-player-btn<?php echo $idSuffix; ?>" aria-label="Close Player">
            <i class="fas fa-chevron-down"></i>
            <span>Close</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Speed Modal (Global) -->
    <div class="podcast-modal-overlay" id="speed-modal-overlay<?php echo $idSuffix; ?>" style="display: none;">
        <div class="podcast-modal-container">
            <div class="podcast-modal-content">
                <h3 class="podcast-modal-title">Playback Speed</h3>
                <div class="podcast-modal-options" id="speed-options-modal<?php echo $idSuffix; ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Timer Modal (Global) -->
    <div class="podcast-modal-overlay" id="timer-modal-overlay<?php echo $idSuffix; ?>" style="display: none;">
        <div class="podcast-modal-container">
            <div class="podcast-modal-content">
                <h3 class="podcast-modal-title">Sleep Timer</h3>
                <div class="podcast-modal-options" id="timer-options-modal<?php echo $idSuffix; ?>">
                </div>
            </div>
        </div>
    </div>
</div>