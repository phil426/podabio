<?php
/**
 * Account security actions API.
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';

header('Content-Type: application/json');

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?? '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request payload']);
    exit;
}

$action = $payload['action'] ?? '';
$userId = getUserId();
$user = new User();

switch ($action) {
    case 'unlink_google':
        $result = $user->unlinkGoogleAccount($userId);
        echo json_encode([
            'success' => $result['success'],
            'error' => $result['success'] ? null : ($result['error'] ?? 'Unable to unlink Google account.')
        ]);
        exit;

    case 'remove_password':
        $result = $user->removePassword($userId);
        echo json_encode([
            'success' => $result['success'],
            'error' => $result['success'] ? null : ($result['error'] ?? 'Unable to remove password.')
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unsupported security action']);
        exit;
}

