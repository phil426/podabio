<?php
/**
 * Payment Processing API
 * Podn.Bio - Handles payment initiation
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/payments.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../classes/PaymentProcessor.php';
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
$paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');

if (!in_array($planType, ['premium', 'pro'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid plan']);
    exit;
}

if (!in_array($paymentMethod, ['paypal', 'venmo'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment method']);
    exit;
}

$processor = new PaymentProcessor();

if ($paymentMethod === 'paypal') {
    // Get plan price
    $price = $planType === 'premium' ? PLAN_PREMIUM_PRICE : PLAN_PRO_PRICE;
    
    // Create PayPal payment
    $result = $processor->createPayPalPayment($user['id'], $planType, $price);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'redirect_url' => $result['payment_url']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Payment processing failed'
        ]);
    }
} else if ($paymentMethod === 'venmo') {
    // Process Venmo payment (creates pending subscription)
    $result = $processor->processVenmoPayment($user['id'], $planType);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'redirect_url' => '/payment/pending.php?plan=' . $planType
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Payment processing failed'
        ]);
    }
}

