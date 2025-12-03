<?php
/**
 * Start Pro Trial API
 * PodaBio - Handles 14-day free trial signup with payment method collection
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

$userId = $user['id'];

// Check if user is root admin (already has Pro features)
$subscriptionClass = new Subscription();
if ($subscriptionClass->isRootAdmin($userId)) {
    echo json_encode([
        'success' => false,
        'error' => 'Root admin already has Pro features. No trial needed.'
    ]);
    exit;
}

// Check if user already has an active Pro subscription
$activeSubscription = $subscriptionClass->getActive($userId);
if ($activeSubscription && $activeSubscription['plan_type'] === 'pro') {
    echo json_encode([
        'success' => false,
        'error' => 'You already have an active Pro subscription.'
    ]);
    exit;
}

// Check if user is already in a trial
if ($subscriptionClass->checkTrialStatus($userId)) {
    echo json_encode([
        'success' => false,
        'error' => 'You are already in a trial period.'
    ]);
    exit;
}

// Get billing interval (default to monthly)
$billingInterval = sanitizeInput($_POST['billing_interval'] ?? 'month');
if (!in_array($billingInterval, ['month', 'year'])) {
    $billingInterval = 'month';
}

// Create Stripe checkout session with 14-day trial
$processor = new StripeProcessor();
$result = $processor->createCheckoutSession($userId, 'pro', $billingInterval, TRIAL_PERIOD_DAYS);

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
        'error' => $result['error'] ?? 'Failed to create trial checkout session'
    ]);
}

