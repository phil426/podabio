<?php
/**
 * RSS Proxy - Handles CORS and fetches RSS feeds for podcast player
 */

header('Content-Type: application/xml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get RSS URL from query parameter
$rssUrl = $_GET['url'] ?? '';

if (empty($rssUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'RSS URL is required']);
    exit;
}

// Validate URL
if (!filter_var($rssUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

// Fetch RSS feed
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'user_agent' => 'Podn.Bio Podcast Player/1.0',
        'follow_location' => true,
        'max_redirects' => 5
    ]
]);

$feedContent = @file_get_contents($rssUrl, false, $context);

if ($feedContent === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch RSS feed']);
    exit;
}

// Return RSS feed content
echo $feedContent;

