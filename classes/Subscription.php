<?php
/**
 * Subscription Class
 * Podn.Bio
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
             AND (expires_at IS NULL OR expires_at > NOW()) 
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
    }
    
    /**
     * Upgrade subscription
     * @param int $userId
     * @param string $planType (premium or pro)
     * @param string $paymentMethod (paypal or venmo)
     * @param string $paymentId Payment transaction ID
     * @return array ['success' => bool, 'subscription_id' => int|null, 'error' => string|null]
     */
    public function upgrade($userId, $planType, $paymentMethod, $paymentId) {
        // Cancel any existing active subscriptions
        $this->cancelActive($userId);
        
        // Calculate expiration (30 days from now for monthly plans)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        try {
            executeQuery(
                "INSERT INTO subscriptions (user_id, plan_type, payment_method, payment_id, status, started_at, expires_at) 
                 VALUES (?, ?, ?, ?, 'active', NOW(), ?)",
                [$userId, $planType, $paymentMethod, $paymentId, $expiresAt]
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
        $subscription = $this->getActive($userId);
        if (!$subscription) {
            return false;
        }
        
        $plan = $subscription['plan_type'];
        
        // Feature access matrix
        $features = [
            'free' => ['basic_links', 'basic_themes'],
            'premium' => ['basic_links', 'basic_themes', 'custom_colors', 'custom_fonts', 'analytics', 'email_subscription'],
            'pro' => ['basic_links', 'basic_themes', 'custom_colors', 'custom_fonts', 'analytics', 'email_subscription', 'custom_domain', 'affiliate_links', 'advanced_analytics']
        ];
        
        return isset($features[$plan]) && in_array($feature, $features[$plan]);
    }
}

