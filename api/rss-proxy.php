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

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $rssUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'PodaBio Podcast Player/1.0');
// Disable SSL verification if needed (try to avoid, but helpful for some misconfigured feeds)
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$feedContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($feedContent === false) {
    http_response_code(500);
    // Return actual error for debugging
    echo json_encode(['error' => 'Failed to fetch RSS feed: ' . $curlError]);
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => "Remote server returned error: $httpCode"]);
    exit;
}

// Check if content is empty
if (empty($feedContent)) {
    http_response_code(500);
    echo json_encode(['error' => 'Empty response from RSS feed']);
    exit;
}

// Return RSS feed content
echo $feedContent;
