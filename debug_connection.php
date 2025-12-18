<?php
header('Content-Type: text/plain');

echo "Debug Connectivity Script\n";
echo "=========================\n\n";

// 1. Check allow_url_fopen
echo "1. Checking allow_url_fopen:\n";
$allowUrlFopen = ini_get('allow_url_fopen');
echo "   allow_url_fopen = " . ($allowUrlFopen ? 'On' : 'Off') . "\n\n";

// 2. Test file_get_contents
echo "2. Testing file_get_contents to iTunes API:\n";
$url = 'https://itunes.apple.com/search?term=test&media=podcast&limit=1';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'PodaBio/1.0',
        'ignore_errors' => true // Fetch content even on failure status
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
    echo "   First 100 chars: " . substr($response, 0, 100) . "...\n";
}
echo "\n";

// 3. Test cURL
echo "3. Testing cURL to iTunes API:\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PodaBio/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $start = microtime(true);
    $curlResponse = curl_exec($ch);
    $end = microtime(true);
    $duration = round($end - $start, 4);

    if ($curlResponse === false) {
        echo "   [FAILED] cURL error: " . curl_error($ch) . "\n";
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo "   [SUCCESS] HTTP $httpCode. Received " . strlen($curlResponse) . " bytes in $duration seconds.\n";
    }
    curl_close($ch);
} else {
    echo "   [SKIPPED] cURL not available.\n";
}
echo "\n";
