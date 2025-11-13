<?php
/**
 * Account Authentication Methods API
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();
$user = fetchOne('SELECT email, password_hash, google_id FROM users WHERE id = ?', [$userId]);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$hasPassword = !empty($user['password_hash']);
$hasGoogle = !empty($user['google_id']);

require_once __DIR__ . '/../../config/oauth.php';
$linkUrl = getGoogleAuthUrl('link');

echo json_encode([
    'success' => true,
    'data' => [
        'has_password' => $hasPassword,
        'has_google' => $hasGoogle,
        'google_link_url' => $linkUrl
    ]
]);

