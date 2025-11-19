<?php
/**
 * Payment Webhook Handler
 * PodaBio - Handles PayPal webhook events
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/payments.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/PaymentProcessor.php';
require_once __DIR__ . '/../../classes/Subscription.php';

// Get raw request body
$rawBody = file_get_contents('php://input');
$headers = getallheaders();

// Log webhook for debugging
error_log("PayPal Webhook received: " . print_r($headers, true));
error_log("Webhook body: " . $rawBody);

// Verify webhook (in production, implement full PayPal webhook verification)
$processor = new PaymentProcessor();
// $verified = $processor->verifyPayPalWebhook($headers, $rawBody);

// Parse webhook event
$event = json_decode($rawBody, true);

if (!$event || !isset($event['event_type'])) {
    http_response_code(400);
    exit('Invalid webhook data');
}

// Handle different event types
switch ($event['event_type']) {
    case 'PAYMENT.CAPTURE.COMPLETED':
        // Payment was captured successfully
        $resource = $event['resource'] ?? [];
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if ($orderId) {
            $subscription = new Subscription();
            $sub = $subscription->getByPaymentId($orderId);
            
            if ($sub && $sub['status'] === 'pending') {
                // Activate subscription
                executeQuery(
                    "UPDATE subscriptions SET status = 'active', payment_id = ?, updated_at = NOW() WHERE id = ?",
                    [$resource['id'], $sub['id']]
                );
                
                // TODO: Send confirmation email
            }
        }
        break;
        
    case 'PAYMENT.CAPTURE.DENIED':
    case 'PAYMENT.CAPTURE.REFUNDED':
        // Payment failed or was refunded
        $resource = $event['resource'] ?? [];
        $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        
        if ($orderId) {
            $subscription = new Subscription();
            $sub = $subscription->getByPaymentId($orderId);
            
            if ($sub) {
                executeQuery(
                    "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
                    [$sub['id']]
                );
            }
        }
        break;
}

http_response_code(200);
echo 'OK';

