<?php
/**
 * RSS Parser Class
 * Podn.Bio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class RSSParser {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Parse RSS feed URL
     * @param string $feedUrl
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function parseFeed($feedUrl) {
        // Validate URL
        if (!isValidUrl($feedUrl)) {
            return ['success' => false, 'data' => null, 'error' => 'Invalid RSS feed URL'];
        }
        
        // Fetch RSS feed
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => APP_NAME . '/' . APP_VERSION,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);
        
        $feedContent = @file_get_contents($feedUrl, false, $context);
        
        if ($feedContent === false) {
            return ['success' => false, 'data' => null, 'error' => 'Failed to fetch RSS feed'];
        }
        
        // Parse XML
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($feedContent);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return ['success' => false, 'data' => null, 'error' => 'Invalid RSS feed format'];
        }
        
        // Parse RSS or Atom feed
        $feedData = $this->parseXML($xml);
        
        if (!$feedData) {
            return ['success' => false, 'data' => null, 'error' => 'Could not parse RSS feed data'];
        }
        
        return ['success' => true, 'data' => $feedData, 'error' => null];
    }
    
    /**
     * Parse XML feed (RSS or Atom)
     * @param SimpleXMLElement $xml
     * @return array|null
     */
    private function parseXML($xml) {
        $data = [
            'title' => '',
            'description' => '',
            'cover_image' => '',
            'episodes' => []
        ];
        
        // Check if RSS 2.0
        if (isset($xml->channel)) {
            $channel = $xml->channel;
            
            // Get podcast info
            $data['title'] = (string)($channel->title ?? '');
            $data['description'] = (string)($channel->description ?? '');
            
            // Get cover image - try multiple methods
            $coverImage = '';
            
            // Method 1: Standard RSS image->url
            if (isset($channel->image->url)) {
                $coverImage = (string)$channel->image->url;
            }
            
            // Method 2: iTunes namespace image href - use proper namespace URI
            if (empty($coverImage)) {
                $itunesNs = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
                $itunesImage = $channel->children($itunesNs)->image;
                if ($itunesImage && isset($itunesImage['href'])) {
                    $coverImage = (string)$itunesImage['href'];
                } elseif ($itunesImage && isset($itunesImage->attributes()->href)) {
                    $coverImage = (string)$itunesImage->attributes()->href;
                }
            }
            
            // Method 3: Try direct itunes:image access (fallback)
            if (empty($coverImage) && isset($channel->{'itunes:image'})) {
                $itunesImage = $channel->{'itunes:image'};
                if (isset($itunesImage['href'])) {
                    $coverImage = (string)$itunesImage['href'];
                } elseif (isset($itunesImage->attributes()->href)) {
                    $coverImage = (string)$itunesImage->attributes()->href;
                }
            }
            
            // Method 4: Check for image tag with url attribute
            if (empty($coverImage) && isset($channel->image)) {
                $imageElement = $channel->image;
                if (isset($imageElement->url)) {
                    $coverImage = (string)$imageElement->url;
                } elseif (isset($imageElement['href'])) {
                    $coverImage = (string)$imageElement['href'];
                }
            }
            
            // Method 5: Check for media:thumbnail or media:content with image
            if (empty($coverImage)) {
                $namespaces = $xml->getNamespaces(true);
                foreach ($namespaces as $prefix => $uri) {
                    if (strpos($uri, 'media') !== false || $prefix === 'media') {
                        $mediaImage = $channel->children($uri)->thumbnail;
                        if (isset($mediaImage['url'])) {
                            $coverImage = (string)$mediaImage['url'];
                            break;
                        }
                    }
                }
            }
            
            if (!empty($coverImage)) {
                $data['cover_image'] = $coverImage;
            }
            
            // Parse episodes
            if (isset($channel->item)) {
                foreach ($channel->item as $item) {
                    $episode = $this->parseEpisode($item);
                    if ($episode) {
                        $data['episodes'][] = $episode;
                    }
                }
            }
        }
        // Check if Atom feed
        elseif (isset($xml->entry)) {
            $data['title'] = (string)($xml->title ?? '');
            $data['description'] = (string)($xml->subtitle ?? '');
            
            // Parse episodes (entries in Atom)
            foreach ($xml->entry as $entry) {
                $episode = $this->parseAtomEntry($entry);
                if ($episode) {
                    $data['episodes'][] = $episode;
                }
            }
        } else {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Parse RSS item/episode
     * @param SimpleXMLElement $item
     * @return array|null
     */
    private function parseEpisode($item) {
        $episode = [
            'title' => (string)($item->title ?? ''),
            'description' => (string)($item->description ?? ''),
            'pub_date' => null,
            'audio_url' => '',
            'duration' => null,
            'guid' => (string)($item->guid ?? ''),
            'episode_number' => null
        ];
        
        // Get publication date
        if (isset($item->pubDate)) {
            $pubDate = strtotime((string)$item->pubDate);
            if ($pubDate !== false) {
                $episode['pub_date'] = date('Y-m-d H:i:s', $pubDate);
            }
        }
        
        // Get audio URL
        if (isset($item->enclosure['url'])) {
            $episode['audio_url'] = (string)$item->enclosure['url'];
        } elseif (isset($item->link)) {
            $episode['audio_url'] = (string)$item->link;
        }
        
        // Get duration (iTunes namespace)
        if (isset($item->{'itunes:duration'})) {
            $duration = (string)$item->{'itunes:duration'};
            $episode['duration'] = $this->parseDuration($duration);
        }
        
        // Get episode number (iTunes)
        if (isset($item->{'itunes:episode'})) {
            $episode['episode_number'] = (int)$item->{'itunes:episode'};
        }
        
        // Skip if no title
        if (empty($episode['title'])) {
            return null;
        }
        
        return $episode;
    }
    
    /**
     * Parse Atom entry
     * @param SimpleXMLElement $entry
     * @return array|null
     */
    private function parseAtomEntry($entry) {
        $episode = [
            'title' => (string)($entry->title ?? ''),
            'description' => (string)($entry->summary ?? ''),
            'pub_date' => null,
            'audio_url' => '',
            'duration' => null,
            'guid' => (string)($entry->id ?? ''),
            'episode_number' => null
        ];
        
        // Get publication date
        if (isset($entry->published)) {
            $pubDate = strtotime((string)$entry->published);
            if ($pubDate !== false) {
                $episode['pub_date'] = date('Y-m-d H:i:s', $pubDate);
            }
        } elseif (isset($entry->updated)) {
            $pubDate = strtotime((string)$entry->updated);
            if ($pubDate !== false) {
                $episode['pub_date'] = date('Y-m-d H:i:s', $pubDate);
            }
        }
        
        // Get audio URL (link with type audio)
        foreach ($entry->link as $link) {
            $type = (string)($link['type'] ?? '');
            if (strpos($type, 'audio') !== false || strpos($type, 'video') !== false) {
                $episode['audio_url'] = (string)($link['href'] ?? '');
                break;
            }
        }
        
        if (empty($episode['title'])) {
            return null;
        }
        
        return $episode;
    }
    
    /**
     * Parse duration string to seconds
     * @param string $duration (e.g., "01:23:45" or "83:45")
     * @return int|null
     */
    private function parseDuration($duration) {
        $duration = trim($duration);
        $parts = explode(':', $duration);
        
        if (count($parts) === 3) {
            // HH:MM:SS
            return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
        } elseif (count($parts) === 2) {
            // MM:SS
            return (int)$parts[0] * 60 + (int)$parts[1];
        } elseif (count($parts) === 1) {
            // SS
            return (int)$parts[0];
        }
        
        return null;
    }
    
    /**
     * Save parsed feed data to page
     * @param int $pageId
     * @param array $feedData
     * @return bool
     */
    public function saveToPage($pageId, $feedData) {
        try {
            $this->pdo->beginTransaction();
            
            // Only save cover image URL - do NOT update podcast_name or podcast_description
            // RSS feed data should only be used for the podcast player, not the main page
            if (!empty($feedData['cover_image'])) {
                executeQuery(
                    "UPDATE pages SET cover_image_url = ? WHERE id = ?",
                    [
                        $feedData['cover_image'],
                        $pageId
                    ]
                );
            }
            
            // Delete existing episodes
            executeQuery("DELETE FROM episodes WHERE page_id = ?", [$pageId]);
            
            // Insert new episodes
            if (!empty($feedData['episodes'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO episodes (page_id, title, description, pub_date, audio_url, duration, episode_number, guid)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($feedData['episodes'] as $episode) {
                    $stmt->execute([
                        $pageId,
                        $episode['title'],
                        $episode['description'],
                        $episode['pub_date'],
                        $episode['audio_url'],
                        $episode['duration'],
                        $episode['episode_number'],
                        $episode['guid']
                    ]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to save RSS data to page: " . $e->getMessage());
            return false;
        }
    }
}


