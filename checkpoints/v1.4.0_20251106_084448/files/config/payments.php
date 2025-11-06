<?php
/**
 * Payment Configuration
 * Podn.Bio - PayPal and Venmo Integration
 */

// Payment gateway settings
define('PAYMENT_ENABLED', true);

// PayPal Settings
define('PAYPAL_CLIENT_ID', ''); // Set via environment or config
define('PAYPAL_CLIENT_SECRET', ''); // Set via environment or config
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'
define('PAYPAL_WEBHOOK_ID', ''); // For webhook verification

// Venmo Settings
// Note: Venmo payments are processed through PayPal Business Account
// Users send payments to a PayPal Business account using Venmo
define('VENMO_BUSINESS_USERNAME', ''); // PayPal Business username for Venmo
define('VENMO_BUSINESS_EMAIL', ''); // PayPal Business email for receiving Venmo payments

// Subscription Pricing (monthly)
define('PLAN_PREMIUM_PRICE', 4.99); // $4.99/month
define('PLAN_PRO_PRICE', 9.99); // $9.99/month

// Currency
define('PAYMENT_CURRENCY', 'USD');

// Return URLs
define('PAYMENT_SUCCESS_URL', APP_URL . '/payment/success');
define('PAYMENT_CANCEL_URL', APP_URL . '/payment/cancel');
define('PAYMENT_WEBHOOK_URL', APP_URL . '/api/payment/webhook');

// PayPal API URLs
define('PAYPAL_API_URL', PAYPAL_MODE === 'sandbox' 
    ? 'https://api.sandbox.paypal.com' 
    : 'https://api.paypal.com');

define('PAYPAL_SANDBOX_URL', 'https://www.sandbox.paypal.com');
define('PAYPAL_LIVE_URL', 'https://www.paypal.com');

