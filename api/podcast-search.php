<?php
/**
 * Podcast Search API Endpoint
 * PodaBio
 * 
 * Searches for podcasts using the iTunes API
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/iTunesSearchClient.php';
require_once __DIR__ . '/../classes/APIResponse.php';

// Check authentication
requireAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action !== 'search_podcasts') {
    http_response_code(400);
    echo APIResponse::error('Invalid action');
    exit;
}

$searchQuery = $_GET['query'] ?? $_POST['query'] ?? '';

if (empty($searchQuery)) {
    echo APIResponse::error('Search query is required');
    exit;
}

// Initialize iTunes client
$itunesClient = new iTunesSearchClient();

// Search for podcasts
$searchResult = $itunesClient->searchPodcasts($searchQuery, 10);

if (!$searchResult['success']) {
    echo APIResponse::error($searchResult['error'] ?? 'Failed to search podcasts');
    exit;
}

// Return results
echo APIResponse::success([
    'results' => $searchResult['data'] ?? []
], 'Search completed successfully');

