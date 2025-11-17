<?php
/**
 * Account page management API.
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Page.php';

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

if ($action !== 'create_page') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported page action']);
    exit;
}

$username = isset($payload['username']) ? trim((string) $payload['username']) : '';

if ($username === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username is required']);
    exit;
}

$page = new Page();
$result = $page->create(getUserId(), $username);

echo json_encode([
    'success' => $result['success'],
    'error' => $result['success'] ? null : ($result['error'] ?? 'Unable to create page'),
    'data' => [
        'page_id' => $result['page_id'] ?? null
    ]
]);

