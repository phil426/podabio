<?php
/**
 * Podlinks API Endpoint
 * PodaBio
 * 
 * Generates podcast platform links by searching APIs and populating social icons
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/RSSParser.php';
require_once __DIR__ . '/../classes/iTunesSearchClient.php';
require_once __DIR__ . '/../classes/SpotifySearchClient.php';
require_once __DIR__ . '/../classes/PodcastLinkBuilder.php';
require_once __DIR__ . '/../classes/APIResponse.php';

// Check authentication
requireAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action !== 'generate_podlinks') {
    http_response_code(400);
    echo APIResponse::error('Invalid action');
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];
$page = new Page();
$userPage = $page->getByUserId($userId);

if (!$userPage) {
    http_response_code(404);
    echo APIResponse::error('Page not found');
    exit;
}

$pageId = $userPage['id'];

// Get RSS feed URL
$rssFeedUrl = $userPage['rss_feed_url'] ?? null;

if (empty($rssFeedUrl)) {
    echo APIResponse::error('RSS feed URL is not set. Please add an RSS feed URL first.');
    exit;
}

// Parse RSS feed to extract podcast name
$rssParser = new RSSParser();
$feedResult = $rssParser->parseFeed($rssFeedUrl);

if (!$feedResult['success'] || empty($feedResult['data'])) {
    echo APIResponse::error('Failed to parse RSS feed: ' . ($feedResult['error'] ?? 'Unknown error'));
    exit;
}

$podcastName = $feedResult['data']['title'] ?? '';

if (empty($podcastName)) {
    echo APIResponse::error('Could not extract podcast name from RSS feed');
    exit;
}

// Initialize API clients
$itunesClient = new iTunesSearchClient();
$spotifyClient = new SpotifySearchClient();
$linkBuilder = new PodcastLinkBuilder();

$results = [];
$platformStatus = [];

// Search iTunes API
$itunesResult = $itunesClient->searchPodcast($podcastName);
$applePodcastsId = null;

if ($itunesResult['success'] && !empty($itunesResult['data'])) {
    $applePodcastsId = $itunesResult['data']['id'];
    $applePodcastsUrl = $itunesResult['data']['url'];
    
    // Create/update Apple Podcasts icon
    $iconResult = $page->getOrCreateSocialIcon($pageId, 'apple_podcasts', $applePodcastsUrl);
    $platformStatus['apple_podcasts'] = [
        'found' => true,
        'url' => $applePodcastsUrl,
        'skipped' => $iconResult['skipped'] ?? false
    ];
} else {
    $platformStatus['apple_podcasts'] = [
        'found' => false,
        'error' => $itunesResult['error'] ?? 'Not found',
        'url' => null
    ];
}

// Search Spotify API
$spotifyResult = $spotifyClient->searchPodcast($podcastName);

if ($spotifyResult['success'] && !empty($spotifyResult['data'])) {
    $spotifyUrl = $spotifyResult['data']['url'];
    
    // Create/update Spotify icon
    $iconResult = $page->getOrCreateSocialIcon($pageId, 'spotify', $spotifyUrl);
    $platformStatus['spotify'] = [
        'found' => true,
        'url' => $spotifyUrl,
        'skipped' => $iconResult['skipped'] ?? false
    ];
} else {
    $platformStatus['spotify'] = [
        'found' => false,
        'error' => $spotifyResult['error'] ?? 'Not found',
        'url' => null
    ];
}

// Build URLs for other platforms using Apple Podcasts ID
if ($applePodcastsId) {
    $platformUrls = $linkBuilder->buildPlatformUrls($applePodcastsId);
    
    // Remove apple_podcasts from the list (already handled above)
    unset($platformUrls['apple_podcasts']);
    
    foreach ($platformUrls as $platformName => $platformUrl) {
        // Create/update icon for this platform
        $iconResult = $page->getOrCreateSocialIcon($pageId, $platformName, $platformUrl);
        $platformStatus[$platformName] = [
            'found' => true,
            'url' => $platformUrl,
            'skipped' => $iconResult['skipped'] ?? false
        ];
    }
} else {
    // If no Apple Podcasts ID, mark dependent platforms as not found
    $dependentPlatforms = ['amazon_music', 'youtube_music', 'pocket_casts', 'castro', 'overcast'];
    
    foreach ($dependentPlatforms as $platformName) {
        $platformStatus[$platformName] = [
            'found' => false,
            'error' => 'Not found (requires Apple Podcasts ID)',
            'url' => null
        ];
    }
}

// Return results
echo APIResponse::success([
    'podcast_name' => $podcastName,
    'platforms' => $platformStatus
], 'Podlinks generated successfully');

