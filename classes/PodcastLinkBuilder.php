<?php
/**
 * Podcast Link Builder
 * Podn.Bio
 * 
 * Builds platform-specific URLs for podcast platforms using Apple Podcasts ID
 */

class PodcastLinkBuilder {
    
    /**
     * Build platform URLs from Apple Podcasts ID
     * @param string|int $applePodcastsId The Apple Podcasts ID
     * @return array Associative array of platform names to URLs
     */
    public function buildPlatformUrls($applePodcastsId) {
        if (empty($applePodcastsId)) {
            return [];
        }
        
        $id = (string)$applePodcastsId;
        
        return [
            'apple_podcasts' => 'https://podcasts.apple.com/podcast/id' . $id,
            'amazon_music' => 'https://music.amazon.com/podcasts/' . $id,
            'youtube_music' => 'https://music.youtube.com/channel/' . $id, // Note: This may not work for all podcasts
            'pocket_casts' => 'https://pca.st/itunes/' . $id,
            'castro' => 'https://castro.fm/itunes/' . $id,
            'overcast' => 'https://overcast.fm/itunes/' . $id
        ];
    }
    
    /**
     * Get platform display names
     * @return array Associative array of platform keys to display names
     */
    public function getPlatformNames() {
        return [
            'apple_podcasts' => 'Apple Podcasts',
            'spotify' => 'Spotify',
            'amazon_music' => 'Amazon Music',
            'youtube_music' => 'YouTube Music',
            'pocket_casts' => 'Pocket Casts',
            'castro' => 'Castro',
            'overcast' => 'Overcast'
        ];
    }
}

