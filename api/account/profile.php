<?php
/**
 * Account Profile API
 * Returns basic account details for the Studio top bar.
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Subscription.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();
$userClass = new User();
$user = $userClass->getById($userId);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$subscriptionClass = new Subscription();
$activeSubscription = $subscriptionClass->getActive($userId);

echo json_encode([
    'success' => true,
    'data' => [
        'email' => $user['email'],
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? ''),
        'plan' => $activeSubscription['plan_type'] ?? 'free',
        'avatar_url' => $user['avatar_url'] ?? null
    ]
]);

