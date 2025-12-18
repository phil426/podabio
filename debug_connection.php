<?php
// Set headers to text/plain to see errors easily
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Debug Connectivity Script with Imports\n";
echo "======================================\n\n";

try {
    echo "1. Including config/constants.php... ";
    require_once __DIR__ . '/config/constants.php';
    echo "OK\n";

    echo "2. Including config/database.php... ";
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        echo "OK\n";
    } else {
        echo "MISSING (expected on first deploy)\n";
    }

    echo "3. Including includes/session.php... ";
    require_once __DIR__ . '/includes/session.php';
    echo "OK\n";

    // Skip auth.php as it redirects
    // echo "4. Including includes/auth.php... ";
    // require_once __DIR__ . '/includes/auth.php'; 

    echo "5. Including classes/iTunesSearchClient.php... ";
    require_once __DIR__ . '/classes/iTunesSearchClient.php';
    echo "OK\n";

    echo "6. Testing iTunesSearchClient instantiation... ";
    $client = new iTunesSearchClient();
    echo "OK\n";

    echo "7. Testing searchPodcasts()...\n";
    $result = $client->searchPodcasts('buy the bay', 5);

    echo "   Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    if ($result['error']) {
        echo "   Error: " . $result['error'] . "\n";
    }
    echo "   Count: " . count($result['data'] ?? []) . "\n";

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
