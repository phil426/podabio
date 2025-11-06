<?php
/**
 * Email Subscription API Endpoint
 * Handles email list subscriptions from public pages
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/EmailSubscription.php';

// Set JSON response header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pageId = (int)($_POST['page_id'] ?? 0);
$email = sanitizeInput($_POST['email'] ?? '');

if (empty($pageId) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Page ID and email are required']);
    exit;
}

// Verify page exists and is active
$page = new Page();
$pageData = $page->getByUserId(fetchOne("SELECT user_id FROM pages WHERE id = ?", [$pageId])['user_id'] ?? 0);

if (!$pageData || $pageData['id'] != $pageId) {
    $pageData = fetchOne("SELECT * FROM pages WHERE id = ? AND is_active = 1", [$pageId]);
}

if (!$pageData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Page not found']);
    exit;
}

// Check if email service is configured
if (empty($pageData['email_service_provider'])) {
    echo json_encode(['success' => false, 'error' => 'Email subscription is not configured for this page']);
    exit;
}

// Subscribe email
$emailSub = new EmailSubscription();
$result = $emailSub->subscribe($pageId, $email);

if ($result['success']) {
    if ($result['requires_confirmation']) {
        echo json_encode([
            'success' => true,
            'message' => 'Please check your email to confirm your subscription.',
            'requires_confirmation' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully subscribed! Thank you.',
            'requires_confirmation' => false
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode($result);
}

