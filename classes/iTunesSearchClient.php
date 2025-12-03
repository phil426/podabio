<?php
/**
 * iTunes Search API Client
 * PodaBio
 * 
 * Handles searching for podcasts using the iTunes Search API
 */

require_once __DIR__ . '/../config/podcast-apis.php';

class iTunesSearchClient {
    
    /**
     * Search for a podcast by name
     * @param string $podcastName The name of the podcast to search for
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function searchPodcast($podcastName) {
        if (empty($podcastName)) {
            return ['success' => false, 'data' => null, 'error' => 'Podcast name is required'];
        }
        
        // Clean and encode the search term
        $searchTerm = urlencode(trim($podcastName));
        
        // Build search URL
        $url = ITUNES_SEARCH_URL . '?term=' . $searchTerm . '&media=podcast&limit=1&entity=podcast';
        
        // Fetch results
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => APP_NAME . '/' . APP_VERSION,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return ['success' => false, 'data' => null, 'error' => 'Failed to fetch from iTunes API'];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'data' => null, 'error' => 'Invalid JSON response from iTunes API'];
        }
        
        // Check if results were found
        if (!isset($data['results']) || empty($data['results'])) {
            return ['success' => false, 'data' => null, 'error' => 'Podcast not found on iTunes'];
        }
        
        $result = $data['results'][0];
        
        // Extract Apple Podcasts ID from collectionId or trackId
        $podcastId = $result['collectionId'] ?? $result['trackId'] ?? null;
        
        if (!$podcastId) {
            return ['success' => false, 'data' => null, 'error' => 'Could not extract podcast ID from iTunes response'];
        }
        
        // Build Apple Podcasts URL
        $applePodcastsUrl = 'https://podcasts.apple.com/podcast/id' . $podcastId;
        
        return [
            'success' => true,
            'data' => [
                'id' => $podcastId,
                'name' => $result['collectionName'] ?? $result['trackName'] ?? '',
                'artist' => $result['artistName'] ?? '',
                'url' => $applePodcastsUrl,
                'feed_url' => $result['feedUrl'] ?? null,
                'artwork_url' => $result['artworkUrl600'] ?? $result['artworkUrl100'] ?? null
            ],
            'error' => null
        ];
    }
    
    /**
     * Search for multiple podcasts by name
     * @param string $podcastName The name of the podcast to search for
     * @param int $limit Maximum number of results to return (default: 10)
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function searchPodcasts($podcastName, $limit = 10) {
        if (empty($podcastName)) {
            return ['success' => false, 'data' => null, 'error' => 'Podcast name is required'];
        }
        
        // Clean and encode the search term
        $searchTerm = urlencode(trim($podcastName));
        
        // Build search URL
        $url = ITUNES_SEARCH_URL . '?term=' . $searchTerm . '&media=podcast&limit=' . intval($limit) . '&entity=podcast';
        
        // Fetch results
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => APP_NAME . '/' . APP_VERSION,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return ['success' => false, 'data' => null, 'error' => 'Failed to fetch from iTunes API'];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'data' => null, 'error' => 'Invalid JSON response from iTunes API'];
        }
        
        // Check if results were found
        if (!isset($data['results']) || empty($data['results'])) {
            return ['success' => true, 'data' => [], 'error' => null];
        }
        
        // Process all results
        $results = [];
        foreach ($data['results'] as $result) {
            $podcastId = $result['collectionId'] ?? $result['trackId'] ?? null;
            
            if ($podcastId) {
                $applePodcastsUrl = 'https://podcasts.apple.com/podcast/id' . $podcastId;
                
                $results[] = [
                    'id' => $podcastId,
                    'name' => $result['collectionName'] ?? $result['trackName'] ?? '',
                    'artist' => $result['artistName'] ?? '',
                    'url' => $applePodcastsUrl,
                    'feed_url' => $result['feedUrl'] ?? null,
                    'artwork_url' => $result['artworkUrl600'] ?? $result['artworkUrl100'] ?? null
                ];
            }
        }
        
        return [
            'success' => true,
            'data' => $results,
            'error' => null
        ];
    }
}

