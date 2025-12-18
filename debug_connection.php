<?php
header('Content-Type: text/plain');

echo "Debug Connectivity Script (Exact Match)\n";
echo "=======================================\n\n";

// exact URL structure from iTunesSearchClient
$searchTerm = urlencode('buy the bay'); // using the term from the screenshot
$limit = 10;
$url = 'https://itunes.apple.com/search?term=' . $searchTerm . '&media=podcast&limit=' . intval($limit) . '&entity=podcast';

echo "URL: " . $url . "\n\n";

// 1. Test file_get_contents
echo "1. Testing file_get_contents:\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'PodaBio/1.0',
        'follow_location' => true,
        'max_redirects' => 5
    ]
]);

$start = microtime(true);
$response = @file_get_contents($url, false, $context);
$end = microtime(true);
$duration = round($end - $start, 4);

if ($response === false) {
    echo "   [FAILED] file_get_contents returned false.\n";
    $error = error_get_last();
    if ($error) {
        echo "   Error: " . $error['message'] . "\n";
    }
} else {
    echo "   [SUCCESS] Received " . strlen($response) . " bytes in $duration seconds.\n";
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "   [FAILED] Invalid JSON: " . json_last_error_msg() . "\n";
    } else {
        echo "   [SUCCESS] Valid JSON. Result count: " . ($data['resultCount'] ?? 'unknown') . "\n";
    }
}
echo "\n";
