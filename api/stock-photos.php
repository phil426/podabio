<?php
/**
 * Stock Photos API Proxy
 * PodaBio
 * 
 * Proxies requests to Unsplash and Pexels APIs and handles
 * downloading selected images to the local media library.
 */

// Load core configuration
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/local.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Helper to send JSON response
function send_json_response($success, $data = [], $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(array_merge(
        ['success' => $success],
        $data,
        $error ? ['error' => $error] : []
    ));
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    send_json_response(false, [], 'Unauthorized', 401);
}

// Handle GET requests (Search)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $provider = $_GET['provider'] ?? '';
    $query = $_GET['query'] ?? '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $per_page = 20;

    if (empty($query)) {
        send_json_response(false, [], 'Search query is required', 400);
    }

    if ($provider === 'unsplash') {
        if (!defined('UNSPLASH_ACCESS_KEY')) {
            send_json_response(false, [], 'Unsplash API key not configured', 501);
        }

        $url = 'https://api.unsplash.com/search/photos?' . http_build_query([
            'query' => $query,
            'page' => $page,
            'per_page' => $per_page,
            'client_id' => UNSPLASH_ACCESS_KEY
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            send_json_response(false, [], 'Failed to fetch from Unsplash', 502);
        }

        $data = json_decode($response, true);

        // Transform to common format
        $results = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'url' => $item['urls']['regular'],
                'thumbnail' => $item['urls']['small'],
                'author' => $item['user']['name'],
                'author_url' => $item['user']['links']['html'],
                'provider' => 'unsplash'
            ];
        }, $data['results']);

        send_json_response(true, [
            'results' => $results,
            'total_pages' => $data['total_pages']
        ]);

    } elseif ($provider === 'pexels') {
        if (!defined('PEXELS_API_KEY')) {
            send_json_response(false, [], 'Pexels API key not configured', 501);
        }

        $url = 'https://api.pexels.com/v1/search?' . http_build_query([
            'query' => $query,
            'page' => $page,
            'per_page' => $per_page
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . PEXELS_API_KEY
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            send_json_response(false, [], 'Failed to fetch from Pexels', 502);
        }

        $data = json_decode($response, true);

        // Transform to common format
        $results = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'url' => $item['src']['large2x'], // High quality for background
                'thumbnail' => $item['src']['medium'],
                'author' => $item['photographer'],
                'author_url' => $item['photographer_url'],
                'provider' => 'pexels'
            ];
        }, $data['photos']);

        // Pexels doesn't return total pages directly, calculate it
        $total_pages = ceil($data['total_results'] / $per_page);

        send_json_response(true, [
            'results' => $results,
            'total_pages' => $total_pages
        ]);

    } elseif ($provider === 'all') {
        // Aggregated search (Unsplash + Pexels) using curl_multi
        $mh = curl_multi_init();
        $channels = [];
        $results = [];

        // Prepare Unsplash Request
        if (defined('UNSPLASH_ACCESS_KEY')) {
            $url = 'https://api.unsplash.com/search/photos?' . http_build_query([
                'query' => $query,
                'page' => $page,
                'per_page' => $per_page,
                'client_id' => UNSPLASH_ACCESS_KEY
            ]);
            $ch_unsplash = curl_init();
            curl_setopt($ch_unsplash, CURLOPT_URL, $url);
            curl_setopt($ch_unsplash, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch_unsplash);
            $channels['unsplash'] = $ch_unsplash;
        }

        // Prepare Pexels Request
        if (defined('PEXELS_API_KEY')) {
            $url = 'https://api.pexels.com/v1/search?' . http_build_query([
                'query' => $query,
                'page' => $page,
                'per_page' => $per_page
            ]);
            $ch_pexels = curl_init();
            curl_setopt($ch_pexels, CURLOPT_URL, $url);
            curl_setopt($ch_pexels, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_pexels, CURLOPT_HTTPHEADER, ['Authorization: ' . PEXELS_API_KEY]);
            curl_multi_add_handle($mh, $ch_pexels);
            $channels['pexels'] = $ch_pexels;
        }

        // Execute handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Process Unsplash Results
        if (isset($channels['unsplash'])) {
            $response = curl_multi_getcontent($channels['unsplash']);
            $data = json_decode($response, true);
            if (isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    $results[] = [
                        'id' => $item['id'],
                        'url' => $item['urls']['regular'],
                        'thumbnail' => $item['urls']['small'],
                        'author' => $item['user']['name'],
                        'author_url' => $item['user']['links']['html'],
                        'provider' => 'unsplash'
                    ];
                }
            }
            curl_multi_remove_handle($mh, $channels['unsplash']);
            curl_close($channels['unsplash']);
        }

        // Process Pexels Results
        if (isset($channels['pexels'])) {
            $response = curl_multi_getcontent($channels['pexels']);
            $data = json_decode($response, true);
            if (isset($data['photos'])) {
                foreach ($data['photos'] as $item) {
                    $results[] = [
                        'id' => $item['id'],
                        'url' => $item['src']['large2x'],
                        'thumbnail' => $item['src']['medium'],
                        'author' => $item['photographer'],
                        'author_url' => $item['photographer_url'],
                        'provider' => 'pexels'
                    ];
                }
            }
            curl_multi_remove_handle($mh, $channels['pexels']);
            curl_close($channels['pexels']);
        }

        curl_multi_close($mh);

        // Shuffle results to mix them up nicely
        shuffle($results);

        send_json_response(true, [
            'results' => $results,
            'total_pages' => 99 // Arbitrary high number for infinite scroll, hard to calc exact merged pages
        ]);

    } else {
        send_json_response(false, [], 'Invalid provider', 400);
    }
}

// Handle POST requests (Import/Download)
// Handle POST requests (Import/Download)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Load MediaLibrary class
    require_once __DIR__ . '/../classes/MediaLibrary.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $image_url = $input['url'] ?? '';
    $provider = $input['provider'] ?? 'external';
    $author = $input['author'] ?? 'Unknown';

    if (empty($image_url)) {
        send_json_response(false, [], 'Image URL is required', 400);
    }

    $mediaLib = new MediaLibrary();

    // Generate a filename prefix based on provider
    // We don't pass a specific filename, letting MediaLibrary generate a secure one, 
    // but default generation uses timestamp. 
    // MediaLibrary::downloadAndSaveImage handles downloading, validation, saving, and DB insertion.

    $result = $mediaLib->downloadAndSaveImage($image_url, $_SESSION['user_id']);

    if (!$result['success']) {
        send_json_response(false, [], $result['error'] ?? 'Failed to download image', 500);
    }

    // Success! Fetch the full media item to return to frontend
    $media_item = $mediaLib->getMediaItem($result['media_id'], $_SESSION['user_id']);

    if (!$media_item) {
        // Should not happen if save was success
        send_json_response(false, [], 'Image saved but failed to retrieve details', 500);
    }

    // Add stock photo metadata if needed (optional, as user_media doesn't usually store provider/author)
    // If we wanted to store provider/author, we'd need extra columns or a JSON field in user_media.
    // For now, we just return it so the UI might use it effectively (though UI re-fetches from DB usually).
    $media_item['provider'] = $provider;
    $media_item['author'] = $author;

    send_json_response(true, ['media' => $media_item]);
}
