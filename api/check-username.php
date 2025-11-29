<?php
/**
 * Check Username Availability API
 * PodaBio
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';

header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get username from query parameter
$username = sanitizeInput($_GET['username'] ?? '');

// Validate username format
if (empty($username)) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'error' => 'Username is required'
    ]);
    exit;
}

// Validate username format (3-30 chars, alphanumeric, underscore, hyphen)
if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
    echo json_encode([
        'success' => true,
        'available' => false,
        'error' => 'Username must be 3-30 characters and contain only letters, numbers, underscores, and hyphens'
    ]);
    exit;
}

// Check availability
$page = new Page();
$isAvailable = $page->isUsernameAvailable($username);

echo json_encode([
    'success' => true,
    'available' => $isAvailable,
    'username' => $username,
    'message' => $isAvailable ? 'Username is available' : 'Username is already taken'
]);

