<?php
/**
 * Stripe Payment Processor Class
 * PodaBio - Handles Stripe payment processing and subscriptions
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Load Stripe SDK (via Composer autoloader if available)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback: try to load Stripe directly if manually installed
    if (file_exists(__DIR__ . '/../vendor/stripe/stripe-php/init.php')) {
        require_once __DIR__ . '/../vendor/stripe/stripe-php/init.php';
    } else {
        error_log('Stripe SDK not found. Please install via: composer require stripe/stripe-php');
    }
}

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;

class StripeProcessor {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
        
        // Initialize Stripe API key
        if (!empty(STRIPE_SECRET_KEY)) {
            Stripe::setApiKey(STRIPE_SECRET_KEY);
        }
    }
    
    /**
     * Create or retrieve Stripe customer
     * @param int $userId
     * @param string $email
     * @param string|null $name
     * @return \Stripe\Customer|null
     */
    public function createCustomer($userId, $email, $name = null) {
        if (empty(STRIPE_SECRET_KEY)) {
            error_log('Stripe secret key not configured');
            return null;
        }
        
        try {
            $customer = Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => [
                    'user_id' => $userId,
                    'app' => APP_NAME
                ]
            ]);
            
            // Store customer ID in database
            $subscription = fetchOne(
                "SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );
            
            if ($subscription) {
                executeQuery(
                    "UPDATE subscriptions SET stripe_customer_id = ? WHERE id = ?",
                    [$customer->id, $subscription['id']]
                );
            }
            
            return $customer;
        } catch (ApiErrorException $e) {
            error_log('Stripe customer creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get existing Stripe customer or create new one
     * @param int $userId
     * @param string $email
     * @param string|null $name
     * @return \Stripe\Customer|null
     */
    public function getOrCreateCustomer($userId, $email, $name = null) {
        // Check if customer ID exists in database
        $subscription = fetchOne(
            "SELECT stripe_customer_id FROM subscriptions WHERE user_id = ? AND stripe_customer_id IS NOT NULL ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        if ($subscription && !empty($subscription['stripe_customer_id'])) {
            try {
                return Customer::retrieve($subscription['stripe_customer_id']);
            } catch (ApiErrorException $e) {
                error_log('Failed to retrieve Stripe customer: ' . $e->getMessage());
                // Customer may have been deleted, create new one
            }
        }
        
        return $this->createCustomer($userId, $email, $name);
    }
    
    /**
     * Create Stripe Checkout session
     * @param int $userId
     * @param string $planType 'pro'
     * @param string $billingInterval 'month' or 'year'
     * @param int|null $trialDays Number of trial days (default: TRIAL_PERIOD_DAYS)
     * @return array ['success' => bool, 'session_url' => string|null, 'error' => string|null]
     */
    public function createCheckoutSession($userId, $planType, $billingInterval = 'month', $trialDays = null) {
        if (empty(STRIPE_SECRET_KEY)) {
            return ['success' => false, 'session_url' => null, 'error' => 'Stripe not configured'];
        }
        
        if ($planType !== 'pro') {
            return ['success' => false, 'session_url' => null, 'error' => 'Invalid plan type'];
        }
        
        $trialDays = $trialDays ?? TRIAL_PERIOD_DAYS;
        $priceId = $billingInterval === 'year' ? STRIPE_PRO_ANNUAL_PRICE_ID : STRIPE_PRO_MONTHLY_PRICE_ID;
        
        if (empty($priceId)) {
            return ['success' => false, 'session_url' => null, 'error' => 'Stripe price ID not configured'];
        }
        
        // Get or create customer
        $user = fetchOne("SELECT id, email, name FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'session_url' => null, 'error' => 'User not found'];
        }
        
        $customer = $this->getOrCreateCustomer($userId, $user['email'], $user['name'] ?? null);
        if (!$customer) {
            return ['success' => false, 'session_url' => null, 'error' => 'Failed to create customer'];
        }
        
        try {
            $sessionParams = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $userId,
                        'plan_type' => $planType,
                        'billing_interval' => $billingInterval
                    ]
                ],
                'success_url' => PAYMENT_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => PAYMENT_CANCEL_URL,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_type' => $planType,
                    'billing_interval' => $billingInterval
                ]
            ];
            
            // Add trial period if specified
            if ($trialDays > 0) {
                $sessionParams['subscription_data']['trial_period_days'] = $trialDays;
            }
            
            $session = Session::create($sessionParams);
            
            return [
                'success' => true,
                'session_url' => $session->url,
                'session_id' => $session->id,
                'error' => null
            ];
        } catch (ApiErrorException $e) {
            error_log('Stripe checkout session creation failed: ' . $e->getMessage());
            return ['success' => false, 'session_url' => null, 'error' => 'Failed to create checkout session'];
        }
    }
    
    /**
     * Retrieve checkout session details
     * @param string $sessionId
     * @return \Stripe\Checkout\Session|null
     */
    public function getCheckoutSession($sessionId) {
        if (empty(STRIPE_SECRET_KEY)) {
            return null;
        }
        
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            error_log('Failed to retrieve checkout session: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get subscription details
     * @param string $subscriptionId
     * @return \Stripe\Subscription|null
     */
    public function getSubscription($subscriptionId) {
        if (empty(STRIPE_SECRET_KEY)) {
            return null;
        }
        
        try {
            return Subscription::retrieve($subscriptionId);
        } catch (ApiErrorException $e) {
            error_log('Failed to retrieve subscription: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cancel subscription
     * @param string $subscriptionId
     * @return bool
     */
    public function cancelSubscription($subscriptionId) {
        if (empty(STRIPE_SECRET_KEY)) {
            return false;
        }
        
        try {
            $subscription = Subscription::retrieve($subscriptionId);
            $subscription->cancel();
            return true;
        } catch (ApiErrorException $e) {
            error_log('Failed to cancel subscription: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update subscription (change plan/interval)
     * @param string $subscriptionId
     * @param string $newPriceId
     * @return bool
     */
    public function updateSubscription($subscriptionId, $newPriceId) {
        if (empty(STRIPE_SECRET_KEY)) {
            return false;
        }
        
        try {
            $subscription = Subscription::retrieve($subscriptionId);
            
            // Update the subscription with new price
            Subscription::update($subscriptionId, [
                'items' => [[
                    'id' => $subscription->items->data[0]->id,
                    'price' => $newPriceId,
                ]],
                'proration_behavior' => 'always_invoice'
            ]);
            
            return true;
        } catch (ApiErrorException $e) {
            error_log('Failed to update subscription: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify webhook signature
     * @param string $payload Raw request body
     * @param string $signature Stripe signature header
     * @return \Stripe\Event|null
     */
    public function verifyWebhookSignature($payload, $signature) {
        if (empty(STRIPE_WEBHOOK_SECRET)) {
            error_log('Stripe webhook secret not configured');
            return null;
        }
        
        try {
            return Webhook::constructEvent($payload, $signature, STRIPE_WEBHOOK_SECRET);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('Webhook signature verification failed: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            error_log('Webhook verification error: ' . $e->getMessage());
            return null;
        }
    }
}

