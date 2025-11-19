<?php
/**
 * Email Subscription Class
 * PodaBio - Handles email list subscriptions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

class EmailSubscription {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Subscribe email to page's list
     * @param int $pageId
     * @param string $email
     * @return array ['success' => bool, 'subscription_id' => int|null, 'error' => string|null]
     */
    public function subscribe($pageId, $email) {
        // Validate email
        if (!isValidEmail($email)) {
            return ['success' => false, 'subscription_id' => null, 'error' => 'Invalid email address'];
        }
        
        // Get page email service configuration
        $page = fetchOne("SELECT email_service_provider, email_service_api_key, email_list_id, email_double_optin FROM pages WHERE id = ?", [$pageId]);
        
        if (!$page || empty($page['email_service_provider'])) {
            return ['success' => false, 'subscription_id' => null, 'error' => 'Email service not configured for this page'];
        }
        
        // Check if already subscribed
        $existing = fetchOne(
            "SELECT id, status FROM email_subscriptions WHERE page_id = ? AND email = ?",
            [$pageId, $email]
        );
        
        if ($existing) {
            if ($existing['status'] === 'confirmed') {
                return ['success' => false, 'subscription_id' => null, 'error' => 'Email already subscribed'];
            }
            // If pending, allow resubscription attempt
        }
        
        // Rate limiting check
        $ipAddress = getClientIP();
        if (!checkRateLimit('email_subscribe_' . $ipAddress, 5, 300)) { // 5 per 5 minutes
            return ['success' => false, 'subscription_id' => null, 'error' => 'Too many subscription attempts. Please try again later.'];
        }
        
        try {
            // Subscribe to email service (if configured)
            $status = 'pending';
            $listId = $page['email_list_id'] ?? null;
            
            // If double opt-in is enabled, status stays pending until confirmation
            // Otherwise, mark as confirmed if API call succeeds
            if (!$page['email_double_optin']) {
                $apiResult = $this->subscribeToService(
                    $page['email_service_provider'],
                    $page['email_service_api_key'],
                    $listId,
                    $email
                );
                
                if ($apiResult['success']) {
                    $status = 'confirmed';
                } else {
                    // Still save subscription, but marked as pending
                    // User can retry later
                }
            }
            
            // Save to database
            $subscriptionId = null;
            if ($existing) {
                // Update existing
                executeQuery(
                    "UPDATE email_subscriptions SET status = ?, email_service = ?, list_id = ?, ip_address = ?, created_at = NOW() WHERE id = ?",
                    [$status, $page['email_service_provider'], $listId, $ipAddress, $existing['id']]
                );
                $subscriptionId = $existing['id'];
            } else {
                // Create new
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_subscriptions (page_id, email, email_service, list_id, status, ip_address)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$pageId, $email, $page['email_service_provider'], $listId, $status, $ipAddress]);
                $subscriptionId = $this->pdo->lastInsertId();
            }
            
            // Track subscription in analytics
            require_once __DIR__ . '/Analytics.php';
            $analytics = new Analytics();
            $analytics->trackEmailSubscribe($pageId);
            
            // Send confirmation email if double opt-in
            if ($page['email_double_optin'] && $status === 'pending') {
                // TODO: Send confirmation email
                // This would require email service setup
            }
            
            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'status' => $status,
                'requires_confirmation' => $page['email_double_optin'] && $status === 'pending',
                'error' => null
            ];
        } catch (PDOException $e) {
            error_log("Email subscription failed: " . $e->getMessage());
            return ['success' => false, 'subscription_id' => null, 'error' => 'Failed to subscribe. Please try again.'];
        }
    }
    
    /**
     * Subscribe email to external service
     * @param string $service
     * @param string $apiKey
     * @param string $listId
     * @param string $email
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function subscribeToService($service, $apiKey, $listId, $email) {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key not configured'];
        }
        
        // Basic implementation - can be expanded with full API integrations
        // For now, just return success (actual API calls would go here)
        // This allows the system to work without requiring API setup during development
        
        switch ($service) {
            case 'mailchimp':
                // TODO: Implement Mailchimp API
                return ['success' => true, 'error' => null];
                
            case 'constant_contact':
                // TODO: Implement Constant Contact API
                return ['success' => true, 'error' => null];
                
            case 'convertkit':
                // TODO: Implement ConvertKit API
                return ['success' => true, 'error' => null];
                
            case 'aweber':
                // TODO: Implement AWeber API
                return ['success' => true, 'error' => null];
                
            case 'mailerlite':
                // TODO: Implement MailerLite API
                return ['success' => true, 'error' => null];
                
            case 'sendinblue':
                // TODO: Implement SendinBlue/Brevo API
                return ['success' => true, 'error' => null];
                
            default:
                return ['success' => false, 'error' => 'Unknown email service'];
        }
    }
    
    /**
     * Confirm email subscription
     * @param string $token
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function confirm($token) {
        // Note: confirmation_token field would need to be added to schema if double opt-in is fully implemented
        $subscription = fetchOne(
            "SELECT * FROM email_subscriptions WHERE id = ? AND status = 'pending'",
            [$token]
        );
        
        if (!$subscription) {
            return ['success' => false, 'error' => 'Invalid or expired confirmation token'];
        }
        
        // Get page configuration
        $page = fetchOne(
            "SELECT email_service_provider, email_service_api_key, email_list_id FROM pages WHERE id = ?",
            [$subscription['page_id']]
        );
        
        // Subscribe to service now that email is confirmed
        if ($page) {
            $apiResult = $this->subscribeToService(
                $page['email_service_provider'],
                $page['email_service_api_key'],
                $page['email_list_id'],
                $subscription['email']
            );
        }
        
        // Update status to confirmed
        executeQuery(
            "UPDATE email_subscriptions SET status = 'confirmed', confirmed_at = NOW(), confirmation_token = NULL WHERE id = ?",
            [$subscription['id']]
        );
        
        return ['success' => true, 'error' => null];
    }
    
    /**
     * Get subscription count for page
     * @param int $pageId
     * @return int
     */
    public function getCount($pageId) {
        $result = fetchOne(
            "SELECT COUNT(*) as count FROM email_subscriptions WHERE page_id = ? AND status = 'confirmed'",
            [$pageId]
        );
        return (int)($result['count'] ?? 0);
    }
}

