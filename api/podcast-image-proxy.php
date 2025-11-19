<?php
/**
 * Podcast Image Proxy - Handles CORS and fetches images for podcast player
 */

header('Content-Type: image/jpeg');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get image URL from query parameter
$imageUrl = $_GET['url'] ?? '';

if (empty($imageUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'Image URL is required']);
    exit;
}

// Validate URL
if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}

// Only allow image URLs
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$urlPath = parse_url($imageUrl, PHP_URL_PATH);
$extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image format']);
    exit;
}

// Fetch image
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'user_agent' => 'PodaBio Podcast Player/1.0',
        'follow_location' => true,
        'max_redirects' => 5
    ]
]);

$imageContent = @file_get_contents($imageUrl, false, $context);

if ($imageContent === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch image']);
    exit;
}

// Detect content type from image data
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $imageContent);
finfo_close($finfo);

// Set appropriate content type
if ($mimeType) {
    header('Content-Type: ' . $mimeType);
}

// Return image content
echo $imageContent;

