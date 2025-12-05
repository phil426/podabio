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
        
        switch ($service) {
            case 'mailchimp':
                return $this->subscribeToMailchimp($apiKey, $listId, $email);
                
            case 'convertkit':
                return $this->subscribeToConvertKit($apiKey, $listId, $email);
                
            case 'mailerlite':
                return $this->subscribeToMailerLite($apiKey, $listId, $email);
                
            case 'constant_contact':
                // Constant Contact requires OAuth2 - not implementing at this time
                error_log("Constant Contact integration requires OAuth2 setup");
                return ['success' => false, 'error' => 'Constant Contact requires OAuth2 - contact support for setup'];
                
            case 'aweber':
                // AWeber requires OAuth - not implementing at this time
                error_log("AWeber integration requires OAuth setup");
                return ['success' => false, 'error' => 'AWeber requires OAuth - contact support for setup'];
                
            case 'sendinblue':
            case 'brevo':
                return $this->subscribeToBrevo($apiKey, $listId, $email);
                
            default:
                return ['success' => false, 'error' => 'Unknown email service'];
        }
    }
    
    /**
     * Subscribe to Mailchimp list
     * @param string $apiKey Mailchimp API key (includes datacenter suffix like -us6)
     * @param string $listId Mailchimp list/audience ID
     * @param string $email Subscriber email
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function subscribeToMailchimp($apiKey, $listId, $email) {
        // Extract datacenter from API key (e.g., "abc123-us6" -> "us6")
        $keyParts = explode('-', $apiKey);
        $dc = end($keyParts);
        
        if (empty($dc) || $dc === $apiKey) {
            return ['success' => false, 'error' => 'Invalid Mailchimp API key format'];
        }
        
        if (empty($listId)) {
            return ['success' => false, 'error' => 'Mailchimp list ID not configured'];
        }
        
        $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members";
        
        $data = [
            'email_address' => $email,
            'status' => 'subscribed' // Use 'pending' for double opt-in
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode('anystring:' . $apiKey)
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Mailchimp cURL error: " . $curlError);
            return ['success' => false, 'error' => 'Failed to connect to Mailchimp'];
        }
        
        $result = json_decode($response, true);
        
        // Success: 200 (existing subscribed) or 201 (newly subscribed)
        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'error' => null];
        }
        
        // Already subscribed (member exists)
        if ($httpCode === 400 && isset($result['title']) && strpos($result['title'], 'Member Exists') !== false) {
            // Update existing member status to subscribed
            $subscriberHash = md5(strtolower($email));
            $updateUrl = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";
            
            $ch2 = curl_init($updateUrl);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => json_encode(['status' => 'subscribed']),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode('anystring:' . $apiKey)
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $updateResponse = curl_exec($ch2);
            $updateHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);
            
            if ($updateHttpCode === 200) {
                return ['success' => true, 'error' => null];
            }
            
            return ['success' => true, 'error' => null]; // Already subscribed is still success
        }
        
        // Error
        $errorMsg = $result['detail'] ?? $result['title'] ?? 'Unknown Mailchimp error';
        error_log("Mailchimp error: HTTP {$httpCode} - " . $errorMsg);
        return ['success' => false, 'error' => 'Mailchimp: ' . $errorMsg];
    }
    
    /**
     * Subscribe to ConvertKit form
     * @param string $apiKey ConvertKit API key
     * @param string $formId ConvertKit form ID
     * @param string $email Subscriber email
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function subscribeToConvertKit($apiKey, $formId, $email) {
        if (empty($formId)) {
            return ['success' => false, 'error' => 'ConvertKit form ID not configured'];
        }
        
        $url = "https://api.convertkit.com/v3/forms/{$formId}/subscribe";
        
        $data = [
            'api_key' => $apiKey,
            'email' => $email
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8'
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("ConvertKit cURL error: " . $curlError);
            return ['success' => false, 'error' => 'Failed to connect to ConvertKit'];
        }
        
        $result = json_decode($response, true);
        
        // Success: 200
        if ($httpCode === 200 && isset($result['subscription'])) {
            return ['success' => true, 'error' => null];
        }
        
        // Error
        $errorMsg = $result['error'] ?? $result['message'] ?? 'Unknown ConvertKit error';
        error_log("ConvertKit error: HTTP {$httpCode} - " . $errorMsg);
        return ['success' => false, 'error' => 'ConvertKit: ' . $errorMsg];
    }
    
    /**
     * Subscribe to MailerLite group
     * @param string $apiKey MailerLite API key
     * @param string $groupId MailerLite group ID
     * @param string $email Subscriber email
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function subscribeToMailerLite($apiKey, $groupId, $email) {
        // MailerLite API v2
        $url = "https://connect.mailerlite.com/api/subscribers";
        
        $data = [
            'email' => $email
        ];
        
        // Add to group if specified
        if (!empty($groupId)) {
            $data['groups'] = [$groupId];
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("MailerLite cURL error: " . $curlError);
            return ['success' => false, 'error' => 'Failed to connect to MailerLite'];
        }
        
        $result = json_decode($response, true);
        
        // Success: 200 (existing) or 201 (new)
        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'error' => null];
        }
        
        // Error
        $errorMsg = $result['message'] ?? 'Unknown MailerLite error';
        error_log("MailerLite error: HTTP {$httpCode} - " . $errorMsg);
        return ['success' => false, 'error' => 'MailerLite: ' . $errorMsg];
    }
    
    /**
     * Subscribe to Brevo (formerly SendinBlue) list
     * @param string $apiKey Brevo API key
     * @param string $listId Brevo list ID
     * @param string $email Subscriber email
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function subscribeToBrevo($apiKey, $listId, $email) {
        $url = "https://api.brevo.com/v3/contacts";
        
        $data = [
            'email' => $email,
            'updateEnabled' => true
        ];
        
        // Add to list if specified
        if (!empty($listId)) {
            $data['listIds'] = [(int)$listId];
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Brevo cURL error: " . $curlError);
            return ['success' => false, 'error' => 'Failed to connect to Brevo'];
        }
        
        $result = json_decode($response, true);
        
        // Success: 201 (created) or 204 (updated)
        if ($httpCode === 201 || $httpCode === 204 || $httpCode === 200) {
            return ['success' => true, 'error' => null];
        }
        
        // Error
        $errorMsg = $result['message'] ?? 'Unknown Brevo error';
        error_log("Brevo error: HTTP {$httpCode} - " . $errorMsg);
        return ['success' => false, 'error' => 'Brevo: ' . $errorMsg];
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

