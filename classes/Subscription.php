<?php
/**
 * Subscription Class
 * PodaBio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Subscription {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Create default free subscription for new user
     * @param int $userId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function createDefault($userId) {
        // Check if user already has a subscription
        $existing = $this->getActive($userId);
        if ($existing) {
            return ['success' => true, 'error' => null];
        }
        
        try {
            executeQuery(
                "INSERT INTO subscriptions (user_id, plan_type, status, started_at) 
                 VALUES (?, 'free', 'active', NOW())",
                [$userId]
            );
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            // Ignore duplicate entry errors
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => true, 'error' => null];
            }
            error_log("Default subscription creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create subscription'];
        }
    }
    
    /**
     * Get active subscription for user
     * @param int $userId
     * @return array|null
     */
    public function getActive($userId) {
        return fetchOne(
            "SELECT * FROM subscriptions 
             WHERE user_id = ? AND status = 'active' 
             AND (expires_at IS NULL OR expires_at > NOW() OR (is_trial = TRUE AND trial_ends_at > NOW()))
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
    }
    
    /**
     * Upgrade subscription (for Stripe and legacy payment methods)
     * @param int $userId
     * @param string $planType ('pro' only - premium plan removed)
     * @param string $paymentMethod ('stripe', 'paypal', or 'venmo')
     * @param string|null $paymentId Payment transaction ID
     * @param array $stripeData Optional Stripe data (customer_id, subscription_id, price_id, billing_interval)
     * @return array ['success' => bool, 'subscription_id' => int|null, 'error' => string|null]
     */
    public function upgrade($userId, $planType, $paymentMethod, $paymentId = null, $stripeData = []) {
        // Only allow 'pro' plan (premium removed)
        if ($planType !== 'pro') {
            return ['success' => false, 'subscription_id' => null, 'error' => 'Invalid plan type'];
        }
        
        // Cancel any existing active subscriptions
        $this->cancelActive($userId);
        
        // Calculate expiration based on billing interval
        $billingInterval = $stripeData['billing_interval'] ?? 'month';
        $daysToAdd = $billingInterval === 'year' ? 365 : 30;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$daysToAdd} days"));
        
        try {
            // Build query with Stripe fields if provided
            $fields = ['user_id', 'plan_type', 'payment_method', 'status', 'started_at'];
            $values = [$userId, $planType, $paymentMethod, 'active'];
            
            if ($paymentId) {
                $fields[] = 'payment_id';
                $values[] = $paymentId;
            }
            
            if (!empty($stripeData['customer_id'])) {
                $fields[] = 'stripe_customer_id';
                $values[] = $stripeData['customer_id'];
            }
            
            if (!empty($stripeData['subscription_id'])) {
                $fields[] = 'stripe_subscription_id';
                $values[] = $stripeData['subscription_id'];
            }
            
            if (!empty($stripeData['price_id'])) {
                $fields[] = 'stripe_price_id';
                $values[] = $stripeData['price_id'];
            }
            
            if (!empty($stripeData['billing_interval'])) {
                $fields[] = 'billing_interval';
                $values[] = $stripeData['billing_interval'];
            }
            
            // Handle trial
            if (!empty($stripeData['trial_ends_at'])) {
                $fields[] = 'trial_ends_at';
                $fields[] = 'is_trial';
                $values[] = $stripeData['trial_ends_at'];
                $values[] = true;
            }
            
            $fields[] = 'expires_at';
            $values[] = $expiresAt;
            
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $fieldList = implode(', ', $fields);
            
            executeQuery(
                "INSERT INTO subscriptions ({$fieldList}) 
                 VALUES ({$placeholders})",
                $values
            );
            
            $subscriptionId = $this->pdo->lastInsertId();
            return ['success' => true, 'subscription_id' => $subscriptionId, 'error' => null];
        } catch (PDOException $e) {
            error_log("Subscription upgrade failed: " . $e->getMessage());
            return ['success' => false, 'subscription_id' => null, 'error' => 'Failed to upgrade subscription'];
        }
    }
    
    /**
     * Cancel active subscriptions for user
     * @param int $userId
     * @return bool
     */
    public function cancelActive($userId) {
        try {
            executeQuery(
                "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() 
                 WHERE user_id = ? AND status = 'active'",
                [$userId]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Subscription cancellation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Renew subscription (extend expiration)
     * @param int $subscriptionId
     * @param string $paymentId
     * @return bool
     */
    public function renew($subscriptionId, $paymentId) {
        try {
            $subscription = fetchOne("SELECT * FROM subscriptions WHERE id = ?", [$subscriptionId]);
            if (!$subscription) {
                return false;
            }
            
            // Extend by 30 days
            $newExpiresAt = date('Y-m-d H:i:s', strtotime($subscription['expires_at'] . ' +30 days'));
            
            executeQuery(
                "UPDATE subscriptions 
                 SET expires_at = ?, payment_id = ?, updated_at = NOW() 
                 WHERE id = ?",
                [$newExpiresAt, $paymentId, $subscriptionId]
            );
            return true;
        } catch (PDOException $e) {
            error_log("Subscription renewal failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription by payment ID
     * @param string $paymentId
     * @return array|null
     */
    public function getByPaymentId($paymentId) {
        return fetchOne(
            "SELECT * FROM subscriptions WHERE payment_id = ?",
            [$paymentId]
        );
    }
    
    /**
     * Check if user has plan feature access
     * @param int $userId
     * @param string $feature Feature name (e.g., 'custom_domain', 'analytics', 'custom_fonts')
     * @return bool
     */
    public function hasFeatureAccess($userId, $feature) {
        // Root admin always has Pro features
        if ($this->isRootAdmin($userId)) {
            // Root admin gets all Pro features
            $proFeatures = [
                'basic_links', 'basic_themes', 'custom_colors', 'custom_fonts', 
                'analytics', 'email_subscription', 'custom_domain', 
                'affiliate_links', 'advanced_analytics'
            ];
            return in_array($feature, $proFeatures);
        }
        
        $subscription = $this->getActive($userId);
        if (!$subscription) {
            return false;
        }
        
        $plan = $subscription['plan_type'];
        
        // Check if user is in trial period (trial = Pro features)
        if ($this->checkTrialStatus($userId)) {
            $plan = 'pro';
        }
        
        // Feature access matrix (premium features consolidated into pro)
        $features = [
            'free' => ['basic_links', 'basic_themes'],
            'pro' => [
                'basic_links', 'basic_themes', 'custom_colors', 'custom_fonts', 
                'analytics', 'email_subscription', 'custom_domain', 
                'affiliate_links', 'advanced_analytics'
            ]
        ];
        
        return isset($features[$plan]) && in_array($feature, $features[$plan]);
    }
    
    /**
     * Check if user is root admin
     * @param int $userId
     * @return bool
     */
    public function isRootAdmin($userId) {
        $user = fetchOne(
            "SELECT email, is_root_admin FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return false;
        }
        
        // Check is_root_admin flag or email match
        return ($user['is_root_admin'] ?? false) || ($user['email'] === 'phil624@gmail.com');
    }
    
    /**
     * Start 14-day free trial for Pro plan
     * @param int $userId
     * @param string $planType (should be 'pro')
     * @param array $stripeData Stripe subscription data
     * @return array ['success' => bool, 'subscription_id' => int|null, 'error' => string|null]
     */
    public function startTrial($userId, $planType = 'pro', $stripeData = []) {
        if ($planType !== 'pro') {
            return ['success' => false, 'subscription_id' => null, 'error' => 'Trial only available for Pro plan'];
        }
        
        // Calculate trial end date (14 days from now)
        $trialEndsAt = date('Y-m-d H:i:s', strtotime('+14 days'));
        
        $stripeData['trial_ends_at'] = $trialEndsAt;
        $stripeData['is_trial'] = true;
        
        return $this->upgrade($userId, $planType, 'stripe', null, $stripeData);
    }
    
    /**
     * Check if user is currently in trial period
     * @param int $userId
     * @return bool
     */
    public function checkTrialStatus($userId) {
        $subscription = fetchOne(
            "SELECT is_trial, trial_ends_at FROM subscriptions 
             WHERE user_id = ? AND status = 'active' 
             AND is_trial = TRUE 
             AND trial_ends_at > NOW()
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        return $subscription !== null;
    }
    
    /**
     * Get subscription by Stripe subscription ID
     * @param string $stripeSubscriptionId
     * @return array|null
     */
    public function getByStripeSubscriptionId($stripeSubscriptionId) {
        return fetchOne(
            "SELECT * FROM subscriptions WHERE stripe_subscription_id = ?",
            [$stripeSubscriptionId]
        );
    }
    
    /**
     * Sync subscription data from Stripe webhook
     * @param string $stripeSubscriptionId
     * @param array $stripeSubscriptionData Stripe subscription object data
     * @return bool
     */
    public function syncWithStripe($stripeSubscriptionId, $stripeSubscriptionData) {
        $subscription = $this->getByStripeSubscriptionId($stripeSubscriptionId);
        
        if (!$subscription) {
            error_log("Subscription not found for Stripe ID: " . $stripeSubscriptionId);
            return false;
        }
        
        try {
            // Update subscription status based on Stripe status
            $stripeStatus = $stripeSubscriptionData['status'] ?? null;
            $dbStatus = 'active';
            
            if ($stripeStatus === 'canceled' || $stripeStatus === 'unpaid') {
                $dbStatus = 'cancelled';
            } elseif ($stripeStatus === 'past_due') {
                $dbStatus = 'past_due';
            }
            
            // Update trial status
            $isTrial = false;
            $trialEndsAt = null;
            
            if (isset($stripeSubscriptionData['trial_end']) && $stripeSubscriptionData['trial_end'] > time()) {
                $isTrial = true;
                $trialEndsAt = date('Y-m-d H:i:s', $stripeSubscriptionData['trial_end']);
            }
            
            // Update current period end as expiration
            $expiresAt = null;
            if (isset($stripeSubscriptionData['current_period_end'])) {
                $expiresAt = date('Y-m-d H:i:s', $stripeSubscriptionData['current_period_end']);
            }
            
            executeQuery(
                "UPDATE subscriptions 
                 SET status = ?, 
                     is_trial = ?, 
                     trial_ends_at = ?, 
                     expires_at = COALESCE(?, expires_at),
                     updated_at = NOW()
                 WHERE stripe_subscription_id = ?",
                [$dbStatus, $isTrial ? 1 : 0, $trialEndsAt, $expiresAt, $stripeSubscriptionId]
            );
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to sync subscription with Stripe: " . $e->getMessage());
            return false;
        }
    }
}


