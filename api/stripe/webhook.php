<?php
/**
 * Stripe Webhook Handler
 * PodaBio - Handles Stripe webhook events for subscriptions
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/payments.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/StripeProcessor.php';
require_once __DIR__ . '/../../classes/Subscription.php';

// Get raw request body and signature
$payload = @file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($signature)) {
    http_response_code(400);
    error_log('Stripe webhook: Missing payload or signature');
    exit('Missing payload or signature');
}

// Verify webhook signature
$processor = new StripeProcessor();
$event = $processor->verifyWebhookSignature($payload, $signature);

if (!$event) {
    http_response_code(400);
    error_log('Stripe webhook: Signature verification failed');
    exit('Invalid signature');
}

$subscriptionClass = new Subscription();
$eventType = $event->type;
$eventData = $event->data->object;

// Log webhook for debugging
error_log("Stripe webhook received: {$eventType} - Event ID: {$event->id}");

// Implement idempotency: Check if we've processed this event before
$eventId = $event->id;
$processedEvent = fetchOne(
    "SELECT id FROM stripe_webhook_events WHERE event_id = ?",
    [$eventId]
);

if ($processedEvent) {
    // Event already processed, return success
    http_response_code(200);
    echo json_encode(['received' => true, 'message' => 'Event already processed']);
    exit;
}

// Store event ID for idempotency (create table if needed)
try {
    $pdo = getDB();
    // Create table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stripe_webhook_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(255) UNIQUE NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_id (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    executeQuery(
        "INSERT INTO stripe_webhook_events (event_id, event_type) VALUES (?, ?)",
        [$eventId, $eventType]
    );
} catch (PDOException $e) {
    error_log("Failed to store webhook event ID: " . $e->getMessage());
    // Continue processing even if storing event ID fails
}

try {
    switch ($eventType) {
        case 'checkout.session.completed':
            // Checkout session completed - activate subscription or trial
            $session = $eventData;
            
            if ($session->mode === 'subscription' && isset($session->subscription)) {
                $stripeSubscriptionId = $session->subscription;
                $userId = $session->metadata->user_id ?? null;
                $planType = $session->metadata->plan_type ?? 'pro';
                $billingInterval = $session->metadata->billing_interval ?? 'month';
                
                if (!$userId) {
                    error_log("Stripe webhook: checkout.session.completed missing user_id");
                    break;
                }
                
                // Get Stripe subscription details
                $stripeSubscription = $processor->getSubscription($stripeSubscriptionId);
                if (!$stripeSubscription) {
                    error_log("Stripe webhook: Failed to retrieve subscription {$stripeSubscriptionId}");
                    break;
                }
                
                // Check if subscription already exists
                $existingSub = $subscriptionClass->getByStripeSubscriptionId($stripeSubscriptionId);
                
                if (!$existingSub) {
                    // Create new subscription
                    $stripeData = [
                        'customer_id' => $session->customer,
                        'subscription_id' => $stripeSubscriptionId,
                        'price_id' => $stripeSubscription->items->data[0]->price->id ?? null,
                        'billing_interval' => $billingInterval
                    ];
                    
                    // Check if in trial period
                    if ($stripeSubscription->status === 'trialing' && isset($stripeSubscription->trial_end)) {
                        $stripeData['trial_ends_at'] = date('Y-m-d H:i:s', $stripeSubscription->trial_end);
                        $stripeData['is_trial'] = true;
                        
                        $result = $subscriptionClass->startTrial($userId, $planType, $stripeData);
                    } else {
                        $result = $subscriptionClass->upgrade($userId, $planType, 'stripe', null, $stripeData);
                    }
                    
                    if ($result['success']) {
                        error_log("Stripe webhook: Subscription created for user {$userId}");
                    } else {
                        error_log("Stripe webhook: Failed to create subscription: " . $result['error']);
                    }
                } else {
                    // Update existing subscription
                    $subscriptionClass->syncWithStripe($stripeSubscriptionId, [
                        'status' => $stripeSubscription->status,
                        'trial_end' => $stripeSubscription->trial_end ?? null,
                        'current_period_end' => $stripeSubscription->current_period_end ?? null
                    ]);
                }
            }
            break;
            
        case 'customer.subscription.created':
            // New subscription created (may already be handled by checkout.session.completed)
            $stripeSubscription = $eventData;
            $stripeSubscriptionId = $stripeSubscription->id;
            
            // Sync subscription data
            $subscriptionClass->syncWithStripe($stripeSubscriptionId, [
                'status' => $stripeSubscription->status,
                'trial_end' => $stripeSubscription->trial_end ?? null,
                'current_period_end' => $stripeSubscription->current_period_end ?? null
            ]);
            break;
            
        case 'customer.subscription.updated':
            // Subscription updated (plan change, billing interval change, etc.)
            $stripeSubscription = $eventData;
            $stripeSubscriptionId = $stripeSubscription->id;
            
            // Update subscription data
            $subscriptionClass->syncWithStripe($stripeSubscriptionId, [
                'status' => $stripeSubscription->status,
                'trial_end' => $stripeSubscription->trial_end ?? null,
                'current_period_end' => $stripeSubscription->current_period_end ?? null
            ]);
            
            // Update price_id and billing_interval if changed
            if (isset($stripeSubscription->items->data[0]->price->id)) {
                $newPriceId = $stripeSubscription->items->data[0]->price->id;
                $billingInterval = $stripeSubscription->items->data[0]->price->recurring->interval ?? 'month';
                
                executeQuery(
                    "UPDATE subscriptions 
                     SET stripe_price_id = ?, billing_interval = ?, updated_at = NOW()
                     WHERE stripe_subscription_id = ?",
                    [$newPriceId, $billingInterval, $stripeSubscriptionId]
                );
            }
            break;
            
        case 'customer.subscription.deleted':
            // Subscription cancelled
            $stripeSubscription = $eventData;
            $stripeSubscriptionId = $stripeSubscription->id;
            
            $subscription = $subscriptionClass->getByStripeSubscriptionId($stripeSubscriptionId);
            if ($subscription) {
                executeQuery(
                    "UPDATE subscriptions 
                     SET status = 'cancelled', updated_at = NOW()
                     WHERE stripe_subscription_id = ?",
                    [$stripeSubscriptionId]
                );
                error_log("Stripe webhook: Subscription {$stripeSubscriptionId} cancelled");
            }
            break;
            
        case 'invoice.payment_succeeded':
            // Successful payment/renewal
            $invoice = $eventData;
            
            if (isset($invoice->subscription)) {
                $stripeSubscriptionId = $invoice->subscription;
                
                // Update subscription expiration
                $stripeSubscription = $processor->getSubscription($stripeSubscriptionId);
                if ($stripeSubscription && isset($stripeSubscription->current_period_end)) {
                    $expiresAt = date('Y-m-d H:i:s', $stripeSubscription->current_period_end);
                    
                    executeQuery(
                        "UPDATE subscriptions 
                         SET expires_at = ?, updated_at = NOW()
                         WHERE stripe_subscription_id = ?",
                        [$expiresAt, $stripeSubscriptionId]
                    );
                    
                    // If was in trial, mark as no longer trial
                    executeQuery(
                        "UPDATE subscriptions 
                         SET is_trial = FALSE, updated_at = NOW()
                         WHERE stripe_subscription_id = ? AND is_trial = TRUE",
                        [$stripeSubscriptionId]
                    );
                    
                    error_log("Stripe webhook: Subscription {$stripeSubscriptionId} payment succeeded, expires at {$expiresAt}");
                }
            }
            break;
            
        case 'invoice.payment_failed':
            // Payment failed
            $invoice = $eventData;
            
            if (isset($invoice->subscription)) {
                $stripeSubscriptionId = $invoice->subscription;
                
                // Mark subscription as past_due
                executeQuery(
                    "UPDATE subscriptions 
                     SET status = 'past_due', updated_at = NOW()
                     WHERE stripe_subscription_id = ?",
                    [$stripeSubscriptionId]
                );
                
                error_log("Stripe webhook: Payment failed for subscription {$stripeSubscriptionId}");
                // TODO: Send notification email to user
            }
            break;
            
        case 'customer.subscription.trial_will_end':
            // Trial ending soon (3 days before)
            $stripeSubscription = $eventData;
            $stripeSubscriptionId = $stripeSubscription->id;
            
            error_log("Stripe webhook: Trial ending soon for subscription {$stripeSubscriptionId}");
            // TODO: Send notification email to user
            break;
            
        default:
            // Unhandled event type
            error_log("Stripe webhook: Unhandled event type: {$eventType}");
            break;
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
    
} catch (Exception $e) {
    error_log("Stripe webhook error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return 200 to prevent Stripe from retrying immediately
    // We'll handle the error and retry logic separately if needed
    http_response_code(200);
    echo json_encode(['received' => true, 'error' => 'Processing error logged']);
}

