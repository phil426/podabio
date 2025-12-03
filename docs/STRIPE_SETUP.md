# Stripe Payment Integration Setup Guide

This guide walks you through setting up Stripe payment processing for PodaBio's Free and Pro subscription plans.

## Overview

- **Plans**: Free and Pro only (premium plan removed)
- **Pricing**: 
  - Pro Monthly: $4.99/month
  - Pro Annual: $53.89/year (10% discount)
- **Trial**: 14-day free trial with payment method collected upfront
- **Payment Processor**: Stripe only (PayPal/Venmo removed)

## Prerequisites

1. Stripe account (sign up at https://stripe.com)
2. PHP 8.0+ with cURL extension
3. Stripe PHP SDK installed

## Step 1: Install Stripe PHP SDK

### Option A: Using Composer (Recommended)

If your project uses Composer:

```bash
composer require stripe/stripe-php
```

This will install the Stripe SDK in the `vendor/` directory and create/update `composer.json`.

### Option B: Manual Installation

If you're not using Composer, download the Stripe PHP SDK:

1. Download the latest release from: https://github.com/stripe/stripe-php/releases
2. Extract to `vendor/stripe/stripe-php/`
3. Ensure the SDK can be autoloaded

The `StripeProcessor` class will automatically detect and load the SDK from either location.

## Step 2: Create Stripe Account and Get API Keys

1. **Sign up** for a Stripe account at https://stripe.com
2. **Complete** your account setup (business details, bank account, etc.)
3. **Get API Keys**:
   - Go to: https://dashboard.stripe.com/apikeys
   - Copy your **Publishable key** (starts with `pk_test_` or `pk_live_`)
   - Copy your **Secret key** (starts with `sk_test_` or `sk_live_`)
   - **Important**: Use test keys (`pk_test_` / `sk_test_`) for development

## Step 3: Configure Stripe API Keys

1. **Create or update** `config/local.php` (this file is gitignored):

```php
<?php
// Stripe Configuration
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
define('STRIPE_WEBHOOK_SECRET', ''); // Set after webhook setup
define('STRIPE_MODE', 'test'); // 'test' or 'live'

// Stripe Price IDs (set after creating products/prices)
define('STRIPE_PRO_MONTHLY_PRICE_ID', ''); // Set in Step 4
define('STRIPE_PRO_ANNUAL_PRICE_ID', ''); // Set in Step 4
```

2. **Or update** `config/payments.php` directly (not recommended for production):

Edit the constants in `config/payments.php` with your Stripe keys.

## Step 4: Create Products and Prices in Stripe Dashboard

1. **Go to Stripe Dashboard** → Products: https://dashboard.stripe.com/products

2. **Create Pro Monthly Product**:
   - Click "Add product"
   - Name: `PodaBio Pro Monthly`
   - Description: `Pro plan subscription - Monthly billing`
   - Pricing model: `Standard pricing`
   - Price: `$4.99`
   - Billing period: `Monthly`
   - Click "Save product"
   - **Copy the Price ID** (starts with `price_`) → This is `STRIPE_PRO_MONTHLY_PRICE_ID`

3. **Create Pro Annual Product**:
   - Click "Add product"
   - Name: `PodaBio Pro Annual`
   - Description: `Pro plan subscription - Annual billing (10% discount)`
   - Pricing model: `Standard pricing`
   - Price: `$53.89`
   - Billing period: `Yearly`
   - Click "Save product"
   - **Copy the Price ID** (starts with `price_`) → This is `STRIPE_PRO_ANNUAL_PRICE_ID`

4. **Update Configuration** with the Price IDs:

In `config/local.php` or `config/payments.php`:

```php
define('STRIPE_PRO_MONTHLY_PRICE_ID', 'price_xxxxxxxxxxxxx');
define('STRIPE_PRO_ANNUAL_PRICE_ID', 'price_xxxxxxxxxxxxx');
```

## Step 5: Set Up Webhook Endpoint

Webhooks allow Stripe to notify your application about subscription events (trials starting, payments, cancellations, etc.).

### 5.1 Configure Webhook in Stripe Dashboard

1. **Go to Stripe Dashboard** → Developers → Webhooks: https://dashboard.stripe.com/webhooks

2. **Click "Add endpoint"**

3. **Endpoint URL**: 
   ```
   https://yourdomain.com/api/stripe/webhook
   ```
   Replace `yourdomain.com` with your actual domain (e.g., `poda.bio`)

4. **Select events to listen to**:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `customer.subscription.trial_will_end`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`

5. **Click "Add endpoint"**

6. **Copy the Signing secret** (starts with `whsec_`)
   - This will be shown after creating the endpoint
   - Click "Reveal" to see it
   - Add it to your config:

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx');
```

### 5.2 Test Webhook Locally (Optional)

For local development, use Stripe CLI:

1. **Install Stripe CLI**: https://stripe.com/docs/stripe-cli

2. **Login**:
   ```bash
   stripe login
   ```

3. **Forward webhooks** to local server:
   ```bash
   stripe listen --forward-to localhost:8080/api/stripe/webhook
   ```

4. **Copy the webhook signing secret** shown in the terminal and use it in your local config

## Step 6: Run Database Migrations

Run the migration scripts to set up the database:

```bash
# 1. Add Stripe fields to subscriptions table
php database/migrate_update_subscriptions_for_stripe.php

# 2. Add root admin flag to users table
php database/migrate_add_root_admin_flag.php

# 3. Simplify plans (verify setup)
php database/migrate_simplify_plans.php
```

## Step 7: Set Root Admin

The root admin account (`phil624@gmail.com`) automatically has Pro features without needing a subscription.

Ensure the user exists and the migration has set the `is_root_admin` flag:

```sql
SELECT id, email, is_root_admin FROM users WHERE email = 'phil624@gmail.com';
```

If needed, manually set:

```sql
UPDATE users SET is_root_admin = TRUE WHERE email = 'phil624@gmail.com';
```

## Step 8: Test the Integration

### 8.1 Test Mode

1. **Ensure** `STRIPE_MODE = 'test'` in your config
2. **Use test API keys** (`pk_test_...` and `sk_test_...`)
3. **Use Stripe test card numbers**:
   - Success: `4242 4242 4242 4242`
   - Decline: `4000 0000 0000 0002`
   - Use any future expiry date and any CVC

### 8.2 Test Scenarios

1. **Start Free Trial**:
   - Navigate to billing/subscription page
   - Click "Start 14-Day Free Trial"
   - Complete checkout with test card
   - Verify subscription is created with trial status

2. **Purchase Pro Subscription**:
   - Navigate to billing/subscription page
   - Select Pro plan (monthly or annual)
   - Complete checkout with test card
   - Verify subscription is activated

3. **Webhook Events**:
   - Check webhook logs in Stripe Dashboard
   - Verify events are received and processed
   - Check database for subscription records

## Step 9: Go Live

When ready for production:

1. **Switch to live mode**:
   ```php
   define('STRIPE_MODE', 'live');
   ```

2. **Update API keys** with live keys:
   - Replace `pk_test_...` with `pk_live_...`
   - Replace `sk_test_...` with `sk_live_...`

3. **Create webhook endpoint** for production:
   - Use production URL: `https://poda.bio/api/stripe/webhook`
   - Copy the live webhook signing secret
   - Update `STRIPE_WEBHOOK_SECRET`

4. **Verify production webhook**:
   - Send a test event from Stripe Dashboard
   - Check webhook logs to ensure events are received

## Configuration Reference

### Required Constants (in `config/payments.php` or `config/local.php`)

```php
// Stripe API Keys
STRIPE_SECRET_KEY          // Secret API key (starts with sk_test_ or sk_live_)
STRIPE_PUBLISHABLE_KEY     // Publishable key (starts with pk_test_ or pk_live_)
STRIPE_WEBHOOK_SECRET      // Webhook signing secret (starts with whsec_)
STRIPE_MODE                // 'test' or 'live'

// Stripe Price IDs
STRIPE_PRO_MONTHLY_PRICE_ID  // Price ID for monthly Pro plan
STRIPE_PRO_ANNUAL_PRICE_ID   // Price ID for annual Pro plan

// Pricing
PLAN_PRO_MONTHLY_PRICE     // 4.99
PLAN_PRO_ANNUAL_PRICE      // 53.89
TRIAL_PERIOD_DAYS          // 14
```

## Troubleshooting

### Webhook Not Receiving Events

1. **Check endpoint URL** is correct and accessible
2. **Verify webhook secret** is correct
3. **Check server logs** for webhook errors
4. **Use Stripe CLI** to test locally: `stripe listen --forward-to localhost:8080/api/stripe/webhook`

### Subscription Not Activating

1. **Check webhook logs** in Stripe Dashboard
2. **Verify webhook events** are being received (`checkout.session.completed`)
3. **Check database** for subscription records
4. **Review server error logs** for processing errors

### Trial Not Starting

1. **Verify** `trial_period_days` is set correctly (14 days)
2. **Check** webhook is processing `checkout.session.completed` event
3. **Verify** subscription record has `is_trial = TRUE` and `trial_ends_at` set

## Security Best Practices

1. **Never commit** API keys to version control
2. **Use environment variables** or `config/local.php` (gitignored) for secrets
3. **Verify webhook signatures** (already implemented in `StripeProcessor`)
4. **Use HTTPS** for all webhook endpoints
5. **Rotate keys** periodically in production

## Support

For Stripe-specific issues:
- Stripe Documentation: https://stripe.com/docs
- Stripe Support: https://support.stripe.com

For PodaBio integration issues:
- Check server error logs
- Review webhook event logs in Stripe Dashboard
- Verify database migrations have been run

