<?php
/**
 * Giphy API Client
 * Handles interactions with Giphy API
 * 
 * Documentation: https://developers.giphy.com/docs/api/
 */

class GiphyClient {
    
    private $apiKey;
    private $apiBaseUrl = 'https://api.giphy.com/v1';
    
    /**
     * Initialize Giphy client
     * @param string $apiKey Giphy API key
     */
    public function __construct($apiKey = null) {
        // Load from config if not provided
        if ($apiKey === null) {
            $apiKey = defined('GIPHY_API_KEY') ? GIPHY_API_KEY : '';
        }
        
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make a request to Giphy API
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     */
    private function request($endpoint, $params = []) {
        if (empty($this->apiKey)) {
            return ['error' => ['message' => 'Giphy API key not configured']];
        }
        
        $url = $this->apiBaseUrl . '/' . ltrim($endpoint, '/');
        $params['api_key'] = $this->apiKey;
        
        $fullUrl = $url . '?' . http_build_query($params);
        
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => ['message' => 'CURL error: ' . $error]];
        }
        
        if ($httpCode !== 200) {
            return ['error' => ['message' => "HTTP {$httpCode} error"]];
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => ['message' => 'Invalid JSON response']];
        }
        
        return $decoded;
    }
    
    /**
     * Search for GIFs
     * @param string $query Search query
     * @param int $limit Number of results (max 50)
     * @param string $rating Content rating (g, pg, pg-13, r)
     * @param string $lang Language code (default: en)
     * @return array List of GIFs
     */
    public function search($query, $limit = 25, $rating = 'g', $lang = 'en') {
        $params = [
            'q' => $query,
            'limit' => min($limit, 50),
            'rating' => $rating,
            'lang' => $lang
        ];
        
        $response = $this->request('gifs/search', $params);
        
        if (isset($response['error'])) {
            error_log('Giphy API error: ' . json_encode($response['error']));
            return [];
        }
        
        return $response['data'] ?? [];
    }
    
    /**
     * Get trending GIFs
     * @param int $limit Number of results (max 50)
     * @param string $rating Content rating (g, pg, pg-13, r)
     * @return array List of GIFs
     */
    public function getTrending($limit = 25, $rating = 'g') {
        $params = [
            'limit' => min($limit, 50),
            'rating' => $rating
        ];
        
        $response = $this->request('gifs/trending', $params);
        
        if (isset($response['error'])) {
            error_log('Giphy API error: ' . json_encode($response['error']));
            return [];
        }
        
        return $response['data'] ?? [];
    }
    
    /**
     * Get a random GIF
     * @param string $tag Optional tag to filter by
     * @param string $rating Content rating (g, pg, pg-13, r)
     * @return array|null GIF data or null if not found
     */
    public function getRandom($tag = null, $rating = 'g') {
        $params = [
            'rating' => $rating
        ];
        
        if ($tag) {
            $params['tag'] = $tag;
        }
        
        $response = $this->request('gifs/random', $params);
        
        if (isset($response['error'])) {
            error_log('Giphy API error: ' . json_encode($response['error']));
            return null;
        }
        
        return $response['data'] ?? null;
    }
    
    /**
     * Get a GIF by ID
     * @param string $gifId GIF ID
     * @return array|null GIF data or null if not found
     */
    public function getById($gifId) {
        $response = $this->request('gifs/' . $gifId);
        
        if (isset($response['error'])) {
            error_log('Giphy API error: ' . json_encode($response['error']));
            return null;
        }
        
        return $response['data'] ?? null;
    }
    
    /**
     * Get the best rendition URL for a GIF
     * @param array $images Images object from Giphy API response
     * @param string $preferredSize Preferred size (downsized, fixed_height, fixed_width, original)
     * @return string|null Image URL or null
     */
    public static function getBestRendition($images, $preferredSize = 'downsized') {
        if (empty($images) || !is_array($images)) {
            return null;
        }
        
        // Try preferred size first
        if (isset($images[$preferredSize]) && isset($images[$preferredSize]['url'])) {
            return $images[$preferredSize]['url'];
        }
        
        // Fallback order: downsized, fixed_height, fixed_width, original
        $fallbackOrder = ['downsized', 'fixed_height', 'fixed_width', 'original'];
        
        foreach ($fallbackOrder as $size) {
            if (isset($images[$size]) && isset($images[$size]['url'])) {
                return $images[$size]['url'];
            }
        }
        
        return null;
    }
    
    /**
     * Get the best rendition URL for still/preview image
     * @param array $images Images object from Giphy API response
     * @return string|null Image URL or null
     */
    public static function getPreviewImage($images) {
        if (empty($images) || !is_array($images)) {
            return null;
        }
        
        // Try fixed_height_small_still first (good preview size)
        if (isset($images['fixed_height_small_still']) && isset($images['fixed_height_small_still']['url'])) {
            return $images['fixed_height_small_still']['url'];
        }
        
        // Fallback to other still images
        $fallbackOrder = ['fixed_height_still', 'fixed_width_still', 'original_still', 'downsized_still'];
        
        foreach ($fallbackOrder as $size) {
            if (isset($images[$size]) && isset($images[$size]['url'])) {
                return $images[$size]['url'];
            }
        }
        
        return null;
    }
}






















