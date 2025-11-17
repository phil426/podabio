# Payment Integration Setup Guide

## Overview

Payment integration is implemented for PayPal and Venmo subscriptions. The system supports:
- **Free Plan**: Basic features (default)
- **Premium Plan**: $4.99/month
- **Pro Plan**: $9.99/month

## Configuration Required

### 1. PayPal Setup

1. Create a PayPal Business Account at https://www.paypal.com/business
2. Go to PayPal Developer Dashboard: https://developer.paypal.com/
3. Create a new app (Sandbox for testing, Production for live)
4. Copy the Client ID and Client Secret
5. Update `config/payments.php`:

```php
define('PAYPAL_CLIENT_ID', 'your-client-id-here');
define('PAYPAL_CLIENT_SECRET', 'your-client-secret-here');
define('PAYPAL_MODE', 'sandbox'); // Change to 'live' for production
```

### 2. PayPal Webhook Setup

1. In PayPal Developer Dashboard, go to your app settings
2. Add webhook URL: `https://your-domain.com/api/payment/webhook.php`
3. Copy the Webhook ID
4. Update `config/payments.php`:

```php
define('PAYPAL_WEBHOOK_ID', 'your-webhook-id-here');
```

**Note**: For local development, use a tool like ngrok to expose your local server for webhook testing.

### 3. Venmo Setup

Venmo payments are processed manually through PayPal Business Account:
1. Set up your PayPal Business Account to receive Venmo payments
2. Update `config/payments.php`:

```php
define('VENMO_BUSINESS_USERNAME', 'your-venmo-username');
define('VENMO_BUSINESS_EMAIL', 'your-paypal-business-email@example.com');
```

**Note**: Venmo payments require manual verification. Users send payment, and you activate their subscription manually (or via admin panel in future).

## Features Implemented

✅ PayPal payment processing (API v2)
✅ Venmo payment workflow (manual verification)
✅ Subscription upgrade/downgrade
✅ Payment webhook handling
✅ Subscription status tracking
✅ Feature access control based on plans
✅ Dashboard subscription management

## Plan Features

### Free Plan
- Basic links
- Basic themes

### Premium Plan ($4.99/month)
- Basic links
- Basic themes
- Custom colors & fonts
- Basic analytics
- Email subscription integration

### Pro Plan ($9.99/month)
- Everything in Premium
- Custom domain support
- Affiliate link management
- Advanced analytics
- 24/7 Priority Support

## Testing

### PayPal Sandbox Testing
1. Set `PAYPAL_MODE` to `'sandbox'`
2. Use PayPal sandbox test accounts
3. Test checkout flow at `/payment/checkout.php?plan=premium`

### Production Checklist
- [ ] Change `PAYPAL_MODE` to `'live'`
- [ ] Update PayPal Client ID/Secret to production values
- [ ] Configure production webhook URL
- [ ] Test payment flow with small amount
- [ ] Verify webhook receives events
- [ ] Set up email notifications for payments
- [ ] Configure Venmo business account details

## Next Steps (Future Enhancements)

- Automatic subscription renewals
- Email notifications for payment events
- Admin panel for manual Venmo verification
- Subscription cancellation flow
- Refund processing
- Invoice generation
- Payment history view

