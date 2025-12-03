<?php
/**
 * Account Subscription API
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Subscription.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();
$subscriptionClass = new Subscription();
$activeSubscription = $subscriptionClass->getActive($userId);

// Check if user is root admin
$isRootAdmin = $subscriptionClass->isRootAdmin($userId);

if (!$activeSubscription) {
    echo json_encode([
        'success' => true,
        'data' => [
            'plan_type' => 'free',
            'status' => 'active',
            'payment_method' => null,
            'expires_at' => null,
            'is_trial' => false,
            'trial_ends_at' => null,
            'billing_interval' => null,
            'is_root_admin' => $isRootAdmin,
            'invoices' => []
        ]
    ]);
    exit;
}

$invoices = [];

try {
    $invoiceRows = fetchAll('SELECT * FROM subscription_invoices WHERE subscription_id = ? ORDER BY issued_at DESC LIMIT 12', [$activeSubscription['id']]);
    foreach ($invoiceRows as $invoice) {
        $invoices[] = [
            'id' => (string) $invoice['id'],
            'amount' => (float) $invoice['amount'],
            'currency' => $invoice['currency'] ?? 'usd',
            'status' => $invoice['status'] ?? 'paid',
            'issued_at' => $invoice['issued_at'],
            'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null
        ];
    }
} catch (PDOException $e) {
    // If invoices table is missing, ignore silently for now
}

// Determine status
$status = 'active';
if (!empty($activeSubscription['is_trial'])) {
    $status = 'trial';
} elseif (!empty($activeSubscription['status'])) {
    $status = $activeSubscription['status'];
}

echo json_encode([
    'success' => true,
    'data' => [
        'plan_type' => $activeSubscription['plan_type'] ?? 'free',
        'status' => $status,
        'payment_method' => $activeSubscription['payment_method'] ?? null,
        'expires_at' => $activeSubscription['expires_at'] ?? null,
        'is_trial' => !empty($activeSubscription['is_trial']),
        'trial_ends_at' => $activeSubscription['trial_ends_at'] ?? null,
        'billing_interval' => $activeSubscription['billing_interval'] ?? null,
        'is_root_admin' => $isRootAdmin,
        'invoices' => $invoices
    ]
]);

