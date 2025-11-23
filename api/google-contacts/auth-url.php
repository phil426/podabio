<?php
/**
 * Google Contacts OAuth - Get Authorization URL
 * Returns the OAuth URL for Google Contacts import
 */

// Suppress any warnings/errors that might output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start(); // Start output buffering to catch any unexpected output

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/oauth.php';

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Generate state token for CSRF protection
$stateToken = generateToken(32);
$_SESSION['google_contacts_oauth_state'] = $stateToken;

// Build OAuth URL with contacts scope
// Use getCurrentBaseUrl() to automatically detect localhost vs production
$redirectUri = getCurrentBaseUrl() . '/api/google-contacts/callback.php';
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/contacts.readonly',
    'access_type' => 'online',
    'prompt' => 'select_account consent',
    'state' => $stateToken
];

$authUrl = GOOGLE_AUTH_URL . '?' . http_build_query($params);

// End output buffering and send JSON
ob_end_clean();
echo json_encode(['authUrl' => $authUrl]);
exit;

