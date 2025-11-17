<?php
/**
 * Payment Processor Class
 * Podn.Bio - Handles PayPal and Venmo payments
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class PaymentProcessor {
    
    /**
     * Create PayPal payment
     * @param int $userId
     * @param string $planType
     * @param float $amount
     * @return array ['success' => bool, 'payment_url' => string|null, 'error' => string|null]
     */
    public function createPayPalPayment($userId, $planType, $amount) {
        if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_CLIENT_SECRET)) {
            return ['success' => false, 'payment_url' => null, 'error' => 'PayPal not configured'];
        }
        
        // Get access token
        $accessToken = $this->getPayPalAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'payment_url' => null, 'error' => 'Failed to get PayPal access token'];
        }
        
        // Create payment order
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'subscription_' . $userId . '_' . time(),
                'description' => ucfirst($planType) . ' Plan Subscription - ' . APP_NAME,
                'amount' => [
                    'currency_code' => PAYMENT_CURRENCY,
                    'value' => number_format($amount, 2, '.', '')
                ]
            ]],
            'application_context' => [
                'brand_name' => APP_NAME,
                'return_url' => PAYMENT_SUCCESS_URL . '?user_id=' . $userId . '&plan=' . $planType,
                'cancel_url' => PAYMENT_CANCEL_URL . '?user_id=' . $userId
            ]
        ];
        
        $ch = curl_init(PAYPAL_API_URL . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            error_log("PayPal order creation failed: " . $response);
            return ['success' => false, 'payment_url' => null, 'error' => 'Failed to create PayPal order'];
        }
        
        $order = json_decode($response, true);
        
        // Store pending subscription
        require_once __DIR__ . '/Subscription.php';
        $subscription = new Subscription();
        $subscription->upgrade($userId, $planType, 'paypal', $order['id']);
        
        // Find approval URL
        $approveUrl = null;
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }
        
        if (!$approveUrl) {
            return ['success' => false, 'payment_url' => null, 'error' => 'Payment approval URL not found'];
        }
        
        return ['success' => true, 'payment_url' => $approveUrl, 'error' => null];
    }
    
    /**
     * Capture PayPal payment
     * @param string $orderId
     * @return array ['success' => bool, 'transaction_id' => string|null, 'error' => string|null]
     */
    public function capturePayPalPayment($orderId) {
        $accessToken = $this->getPayPalAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'transaction_id' => null, 'error' => 'Failed to get access token'];
        }
        
        $ch = curl_init(PAYPAL_API_URL . '/v2/checkout/orders/' . $orderId . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            error_log("PayPal capture failed: " . $response);
            return ['success' => false, 'transaction_id' => null, 'error' => 'Payment capture failed'];
        }
        
        $capture = json_decode($response, true);
        
        // Get transaction ID from capture
        $transactionId = null;
        if (isset($capture['purchase_units'][0]['payments']['captures'][0]['id'])) {
            $transactionId = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
        }
        
        return ['success' => true, 'transaction_id' => $transactionId, 'error' => null];
    }
    
    /**
     * Get PayPal access token
     * @return string|null
     */
    private function getPayPalAccessToken() {
        $ch = curl_init(PAYPAL_API_URL . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("PayPal token request failed: " . $response);
            return null;
        }
        
        $tokenData = json_decode($response, true);
        return $tokenData['access_token'] ?? null;
    }
    
    /**
     * Process Venmo payment (manual confirmation via PayPal Business)
     * Note: Venmo payments are typically sent manually to a PayPal Business account
     * This creates a pending subscription that requires manual verification
     * @param int $userId
     * @param string $planType
     * @param string $venmoTransactionId User-provided Venmo transaction reference
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function processVenmoPayment($userId, $planType, $venmoTransactionId = null) {
        require_once __DIR__ . '/Subscription.php';
        $subscription = new Subscription();
        
        // Create pending subscription - will be activated after manual verification
        $result = $subscription->upgrade($userId, $planType, 'venmo', $venmoTransactionId ?? 'pending_' . $userId . '_' . time());
        
        if ($result['success']) {
            // Update status to pending for manual verification
            executeQuery(
                "UPDATE subscriptions SET status = 'pending' WHERE id = ?",
                [$result['subscription_id']]
            );
            
            // TODO: Send notification email to admin for manual verification
            return ['success' => true, 'error' => null];
        }
        
        return ['success' => false, 'error' => $result['error']];
    }
    
    /**
     * Verify PayPal webhook signature
     * @param string $headersRaw
     * @param string $body
     * @return bool
     */
    public function verifyPayPalWebhook($headersRaw, $body) {
        // Extract headers
        $headers = [];
        foreach (explode("\n", $headersRaw) as $header) {
            if (strpos($header, ':') !== false) {
                list($key, $value) = explode(':', $header, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        // Verify webhook signature (simplified - full implementation requires PayPal webhook verification)
        // In production, use PayPal's webhook verification API
        return true; // Placeholder - implement full verification
    }
}

