# Payment Integration Setup Guide

## Overview

Payment integration is implemented using Stripe for subscription payments. The system supports:
- **Free Plan**: Basic features (default)
- **Pro Plan**: $4.99/month or $53.89/year (10% discount)

## Features

- Stripe Checkout for secure payment processing
- 14-day free trial (payment method collected upfront, no charge during trial)
- Monthly and annual billing options
- Automatic subscription management via webhooks
- Root admin bypass (phil624@gmail.com has Pro features without subscription)

## Quick Start

1. **Follow the comprehensive setup guide**: See `docs/STRIPE_SETUP.md` for detailed instructions
2. **Install Stripe SDK**: `composer require stripe/stripe-php`
3. **Configure API keys**: Add Stripe keys to `config/local.php` or `config/payments.php`
4. **Create products/prices** in Stripe Dashboard
5. **Set up webhook** endpoint: `https://yourdomain.com/api/stripe/webhook`
6. **Run database migrations**: See Step 6 in `docs/STRIPE_SETUP.md`

## Configuration

### Required Constants

Add to `config/local.php` (recommended) or `config/payments.php`:

```php
// Stripe API Keys
define('STRIPE_SECRET_KEY', 'sk_test_...'); // Get from Stripe Dashboard
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...'); // Get from Stripe Dashboard
define('STRIPE_WEBHOOK_SECRET', 'whsec_...'); // Get after webhook setup
define('STRIPE_MODE', 'test'); // 'test' or 'live'

// Stripe Price IDs (get from Stripe Dashboard after creating products)
define('STRIPE_PRO_MONTHLY_PRICE_ID', 'price_...');
define('STRIPE_PRO_ANNUAL_PRICE_ID', 'price_...');
```

### Pricing Constants

Already defined in `config/payments.php`:
- `PLAN_PRO_MONTHLY_PRICE` = 4.99
- `PLAN_PRO_ANNUAL_PRICE` = 53.89 (10% discount)
- `TRIAL_PERIOD_DAYS` = 14

## Plan Features

### Free Plan
- Basic links
- Basic themes

### Pro Plan ($4.99/month or $53.89/year)
- Everything in Free
- Custom colors & fonts
- Basic analytics
- Email subscription integration
- Custom domain support
- Affiliate link management
- Advanced analytics
- 24/7 Priority Support

*Note: Premium plan features have been consolidated into Pro plan.*

## Trial Program

Users can start a 14-day free trial for the Pro plan:
- Payment method is collected upfront (required by Stripe)
- No charge during the trial period
- Automatically converts to paid subscription after 14 days
- Users can cancel anytime during the trial

## Root Admin

The account `phil624@gmail.com` is configured as root admin and automatically has Pro features without needing a subscription. This is set via the `is_root_admin` flag in the database.

## Testing

### Test Mode

1. Set `STRIPE_MODE = 'test'` in config
2. Use test API keys from Stripe Dashboard
3. Use Stripe test card: `4242 4242 4242 4242`
4. Any future expiry date and any CVC

### Test Scenarios

- Start free trial → Verify trial activation
- Purchase Pro subscription → Verify subscription activation
- Check webhook events → Verify events are processed

## Production Checklist

- [ ] Switch to live mode: `STRIPE_MODE = 'live'`
- [ ] Update API keys with live keys
- [ ] Create webhook endpoint for production URL
- [ ] Update webhook secret with production value
- [ ] Test checkout flow with small amount
- [ ] Verify webhook receives and processes events
- [ ] Test trial activation
- [ ] Test subscription cancellation

## Documentation

For detailed setup instructions, see:
- **`docs/STRIPE_SETUP.md`** - Complete Stripe integration guide
  - API key configuration
  - Product/price creation
  - Webhook setup
  - Testing procedures
  - Troubleshooting

## API Endpoints

- `/api/payment/process.php` - Create Stripe checkout session
- `/api/payment/start-trial.php` - Start 14-day free trial
- `/api/stripe/webhook.php` - Handle Stripe webhook events
- `/payment/success.php` - Payment/trial confirmation page
- `/payment/cancel.php` - Cancelled checkout page

## Database

Required database fields (added via migrations):
- `subscriptions` table: `stripe_customer_id`, `stripe_subscription_id`, `stripe_price_id`, `billing_interval`, `trial_ends_at`, `is_trial`
- `users` table: `is_root_admin`
- `stripe_webhook_events` table: For webhook idempotency

Run migrations:
```bash
php database/migrate_update_subscriptions_for_stripe.php
php database/migrate_add_root_admin_flag.php
php database/migrate_simplify_plans.php
```

## Security

- Webhook signature verification (prevents unauthorized events)
- CSRF protection on payment endpoints
- Idempotent webhook processing (prevents duplicate events)
- Sensitive keys stored in gitignored config files

## Support

- **Stripe Documentation**: https://stripe.com/docs
- **Stripe Support**: https://support.stripe.com
- **Setup Guide**: See `docs/STRIPE_SETUP.md`
