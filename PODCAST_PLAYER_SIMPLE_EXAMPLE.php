<?php
/**
 * SIMPLE PODCAST PLAYER - NO ANIMATIONS, NO DRAWER
 * This shows what a regular static player would look like
 * Always visible, no expand/collapse functionality
 */

// Example HTML structure for a simple, static player:
?>

<!-- Simple Static Player Widget -->
<div class="widget-item widget-podcast-simple">
    <div class="widget-content">
        <div class="podcast-header">
            <img class="podcast-cover-static" src="[PODCAST_COVER_IMAGE]" alt="Podcast Cover">
            <div class="podcast-header-info">
                <h3 class="podcast-title-static">Podcast Name</h3>
                <p class="episode-title-static">Current Episode Title</p>
            </div>
        </div>
        
        <!-- Shikwasa Player Container (always visible) -->
        <div class="shikwasa-podcast-container-static">
            <!-- Shikwasa player renders here automatically -->
        </div>
        
        <!-- Episode List (always visible, no drawer) -->
        <div class="podcast-playlist-static">
            <h4>Episodes</h4>
            <ul class="episode-list">
                <li class="episode-item">Episode 1</li>
                <li class="episode-item">Episode 2</li>
                <li class="episode-item active">Episode 3 (Current)</li>
            </ul>
        </div>
    </div>
</div>

<!-- CSS for Simple Static Player -->
<style>
/* Simple Static Podcast Player - No Animations */
.widget-podcast-simple {
    /* Inherits standard widget styling */
    padding: 1.5rem;
}

.podcast-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: flex-start;
}

.podcast-cover-static {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
}

.podcast-header-info {
    flex: 1;
}

.podcast-title-static {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}

.episode-title-static {
    font-size: 1rem;
    color: var(--text-color);
    opacity: 0.8;
    margin: 0;
}

.shikwasa-podcast-container-static {
    margin: 1rem 0;
    width: 100%;
}

/* Episode List - Always Visible */
.podcast-playlist-static {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.podcast-playlist-static h4 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: var(--text-color);
}

.episode-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.episode-item {
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 0.5rem;
    border: 1px solid transparent;
    transition: background-color 0.2s;
}

.episode-item:hover {
    background: rgba(0, 0, 0, 0.05);
}

.episode-item.active {
    background: rgba(0, 102, 255, 0.1);
    border-color: var(--primary-color);
}
</style>

