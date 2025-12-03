<?php
/**
 * Payment Processing API
 * PodaBio - Handles Stripe checkout for subscription payments
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/payments.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../classes/StripeProcessor.php';
require_once __DIR__ . '/../../classes/Subscription.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$planType = sanitizeInput($_POST['plan'] ?? '');
$billingInterval = sanitizeInput($_POST['billing_interval'] ?? 'month');
$trialDays = isset($_POST['trial_days']) ? (int)$_POST['trial_days'] : null;

// Only allow 'pro' plan (premium removed)
if ($planType !== 'pro') {
    echo json_encode(['success' => false, 'error' => 'Invalid plan. Only Pro plan is available.']);
    exit;
}

// Validate billing interval
if (!in_array($billingInterval, ['month', 'year'])) {
    $billingInterval = 'month';
}

// Check if user is root admin
$subscriptionClass = new Subscription();
if ($subscriptionClass->isRootAdmin($user['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Root admin already has Pro features. No subscription needed.'
    ]);
    exit;
}

// Create Stripe checkout session
$processor = new StripeProcessor();
$result = $processor->createCheckoutSession($user['id'], $planType, $billingInterval, $trialDays);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'data' => [
            'checkout_url' => $result['session_url'],
            'session_id' => $result['session_id']
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? 'Payment processing failed'
    ]);
}

