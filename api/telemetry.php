<?php
/**
 * Lightweight telemetry endpoint for PodaBio Studio.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$event = isset($payload['event']) ? trim((string) $payload['event']) : '';

if ($event === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing event name']);
    exit;
}

$metadata = [];

if (isset($payload['metadata']) && is_array($payload['metadata'])) {
    $metadata = $payload['metadata'];
}

$userId = getUserId();
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// For now we log to the PHP error log. This can be swapped for DB storage later.
error_log(sprintf(
    '[telemetry] user=%d event=%s ip=%s metadata=%s',
    $userId,
    $event,
    $ipAddress,
    json_encode($metadata)
));

echo json_encode(['success' => true]);

