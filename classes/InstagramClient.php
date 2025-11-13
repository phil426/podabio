<?php
/**
 * Instagram API Client
 * Handles interactions with Instagram Basic Display API
 * 
 * Documentation: https://developers.facebook.com/docs/instagram-basic-display-api
 */

class InstagramClient {
    
    private $appId;
    private $appSecret;
    private $accessToken;
    private $apiBaseUrl = 'https://graph.instagram.com';
    
    /**
     * Initialize Instagram client
     * @param string $appId Instagram App ID
     * @param string $appSecret Instagram App Secret
     * @param string $accessToken User access token (long-lived)
     */
    public function __construct($appId = null, $appSecret = null, $accessToken = null) {
        // Load from config if not provided
        if ($appId === null) {
            $appId = defined('INSTAGRAM_APP_ID') ? INSTAGRAM_APP_ID : '';
        }
        if ($appSecret === null) {
            $appSecret = defined('INSTAGRAM_APP_SECRET') ? INSTAGRAM_APP_SECRET : '';
        }
        if ($accessToken === null) {
            $accessToken = defined('INSTAGRAM_ACCESS_TOKEN') ? INSTAGRAM_ACCESS_TOKEN : '';
        }
        
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }
    
    /**
     * Make a request to Instagram API
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array Response data
     */
    private function request($endpoint, $params = []) {
        if (empty($this->accessToken)) {
            return ['error' => ['message' => 'Instagram access token not configured']];
        }
        
        $url = $this->apiBaseUrl . '/' . ltrim($endpoint, '/');
        $params['access_token'] = $this->accessToken;
        $params['fields'] = $params['fields'] ?? 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp';
        
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
     * Get user's media (posts)
     * @param int $limit Number of posts to fetch (max 100)
     * @return array List of media items
     */
    public function getUserMedia($limit = 12) {
        $params = [
            'limit' => min($limit, 100),
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username'
        ];
        
        $response = $this->request('/me/media', $params);
        
        if (isset($response['error'])) {
            error_log('Instagram API error: ' . json_encode($response['error']));
            return [];
        }
        
        return $response['data'] ?? [];
    }
    
    /**
     * Get a specific media item by ID
     * @param string $mediaId Media ID
     * @return array|null Media item or null if not found
     */
    public function getMedia($mediaId) {
        $response = $this->request('/' . $mediaId, [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username'
        ]);
        
        if (isset($response['error'])) {
            error_log('Instagram API error: ' . json_encode($response['error']));
            return null;
        }
        
        return $response;
    }
    
    /**
     * Get user profile information
     * @return array|null User profile or null if not found
     */
    public function getUserProfile() {
        $response = $this->request('/me', [
            'fields' => 'id,username,account_type'
        ]);
        
        if (isset($response['error'])) {
            error_log('Instagram API error: ' . json_encode($response['error']));
            return null;
        }
        
        return $response;
    }
    
    /**
     * Format timestamp for display
     * @param string $timestamp ISO 8601 timestamp
     * @return string Formatted date
     */
    public static function formatTimestamp($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        try {
            $date = new DateTime($timestamp);
            $now = new DateTime();
            $diff = $now->diff($date);
            
            if ($diff->days === 0) {
                if ($diff->h > 0) {
                    return $diff->h . 'h ago';
                } elseif ($diff->i > 0) {
                    return $diff->i . 'm ago';
                } else {
                    return 'Just now';
                }
            } elseif ($diff->days === 1) {
                return 'Yesterday';
            } elseif ($diff->days < 7) {
                return $diff->days . 'd ago';
            } else {
                return $date->format('M j, Y');
            }
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Truncate caption text
     * @param string $caption Caption text
     * @param int $maxLength Maximum length
     * @return string Truncated caption
     */
    public static function truncateCaption($caption, $maxLength = 150) {
        if (empty($caption)) {
            return '';
        }
        
        if (strlen($caption) <= $maxLength) {
            return $caption;
        }
        
        return substr($caption, 0, $maxLength) . '...';
    }
}







