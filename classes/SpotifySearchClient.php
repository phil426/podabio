<?php
/**
 * Spotify Web API Client
 * Podn.Bio
 * 
 * Handles searching for podcasts using the Spotify Web API
 * Implements Client Credentials OAuth flow for server-side authentication
 */

require_once __DIR__ . '/../config/podcast-apis.php';

class SpotifySearchClient {
    private $accessToken = null;
    private $tokenExpiry = 0;
    
    /**
     * Get access token using Client Credentials flow
     * @return string|null
     */
    private function getAccessToken() {
        // Check if we have a valid cached token
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        $clientId = getSpotifyClientId();
        $clientSecret = getSpotifyClientSecret();
        
        // Prepare credentials for base64 encoding
        $credentials = base64_encode($clientId . ':' . $clientSecret);
        
        // Request access token
        $ch = curl_init(SPOTIFY_TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Spotify API token request failed: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Spotify API token request failed with HTTP code: " . $httpCode);
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['access_token'])) {
            error_log("Spotify API token response invalid: " . $response);
            return null;
        }
        
        // Cache the token (subtract 60 seconds for safety margin)
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;
        
        return $this->accessToken;
    }
    
    /**
     * Search for a podcast by name
     * @param string $podcastName The name of the podcast to search for
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function searchPodcast($podcastName) {
        if (empty($podcastName)) {
            return ['success' => false, 'data' => null, 'error' => 'Podcast name is required'];
        }
        
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return ['success' => false, 'data' => null, 'error' => 'Failed to authenticate with Spotify API'];
        }
        
        // Clean and encode the search term
        $searchTerm = urlencode(trim($podcastName));
        
        // Build search URL
        $url = SPOTIFY_API_BASE_URL . '/search?q=' . $searchTerm . '&type=show&limit=1';
        
        // Make API request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Spotify API search request failed: " . $error);
            return ['success' => false, 'data' => null, 'error' => 'Failed to connect to Spotify API'];
        }
        
        if ($httpCode !== 200) {
            error_log("Spotify API search request failed with HTTP code: " . $httpCode);
            return ['success' => false, 'data' => null, 'error' => 'Spotify API returned error code: ' . $httpCode];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Spotify API search response invalid JSON");
            return ['success' => false, 'data' => null, 'error' => 'Invalid response from Spotify API'];
        }
        
        // Check if results were found
        if (!isset($data['shows']['items']) || empty($data['shows']['items'])) {
            return ['success' => false, 'data' => null, 'error' => 'Podcast not found on Spotify'];
        }
        
        $show = $data['shows']['items'][0];
        
        // Extract Spotify show ID from URI (format: spotify:show:xxxxx)
        $showId = null;
        if (isset($show['id'])) {
            $showId = $show['id'];
        } elseif (isset($show['uri'])) {
            // Extract ID from URI like "spotify:show:4rOoJ6Egrf8K2IrywzwOMk"
            $parts = explode(':', $show['uri']);
            if (count($parts) === 3 && $parts[0] === 'spotify' && $parts[1] === 'show') {
                $showId = $parts[2];
            }
        }
        
        if (!$showId) {
            return ['success' => false, 'data' => null, 'error' => 'Could not extract show ID from Spotify response'];
        }
        
        // Build Spotify URL
        $spotifyUrl = 'https://open.spotify.com/show/' . $showId;
        
        return [
            'success' => true,
            'data' => [
                'id' => $showId,
                'name' => $show['name'] ?? '',
                'description' => $show['description'] ?? '',
                'url' => $spotifyUrl,
                'publisher' => $show['publisher'] ?? '',
                'images' => $show['images'] ?? []
            ],
            'error' => null
        ];
    }
}

