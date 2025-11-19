<?php
/**
 * Podcast APIs Configuration
 * PodaBio
 * 
 * Stores API credentials for podcast platform search services
 */

// Spotify Web API Credentials
define('SPOTIFY_CLIENT_ID', '[REDACTED]');
define('SPOTIFY_CLIENT_SECRET', '[REDACTED]');
define('SPOTIFY_REDIRECT_URI', 'https://poda.bio/callback');

// Spotify API Endpoints
define('SPOTIFY_TOKEN_URL', 'https://accounts.spotify.com/api/token');
define('SPOTIFY_API_BASE_URL', 'https://api.spotify.com/v1');

// iTunes Search API (no credentials needed)
define('ITUNES_SEARCH_URL', 'https://itunes.apple.com/search');

/**
 * Get Spotify Client ID
 * @return string
 */
function getSpotifyClientId() {
    return SPOTIFY_CLIENT_ID;
}

/**
 * Get Spotify Client Secret
 * @return string
 */
function getSpotifyClientSecret() {
    return SPOTIFY_CLIENT_SECRET;
}

/**
 * Get Spotify Redirect URI
 * @return string
 */
function getSpotifyRedirectUri() {
    return SPOTIFY_REDIRECT_URI;
}

