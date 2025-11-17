<?php
/**
 * CSRF Token Refresh Endpoint
 * Returns a fresh CSRF token for the current session
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Generate a fresh CSRF token
$token = generateCSRFToken();

echo json_encode([
    'success' => true,
    'csrf_token' => $token
]);

