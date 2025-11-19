<?php
/**
 * Podcast APIs Configuration
 * PodaBio
 * 
 * Stores API credentials for podcast platform search services
 */

// Spotify Web API Credentials
define('SPOTIFY_CLIENT_ID', 'b70f9c5389f44a4f9d2b5f2d54208b85');
define('SPOTIFY_CLIENT_SECRET', '8190dd07a00f4f8dbff21726545ee86f');
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

