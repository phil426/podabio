# Stripe Quick Setup Checklist

## ✅ Step 1: Get Your Stripe API Keys

1. Go to https://dashboard.stripe.com (sign up if needed)
2. Click "Developers" → "API keys"
3. Copy your **Publishable key** (starts with `pk_test_...`)
4. Copy your **Secret key** (starts with `sk_test_...`)

## ✅ Step 2: Configure API Keys

Edit `config/local.php` (create if it doesn't exist) and add:

```php
<?php
// Stripe Configuration
define('STRIPE_SECRET_KEY', 'sk_test_...'); // Your Stripe secret key from dashboard
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...'); // Your Stripe publishable key from dashboard
define('STRIPE_MODE', 'test'); // Change to 'live' for production
```

Or add them directly to `config/payments.php` (not recommended for production).

## ✅ Step 3: Create Products in Stripe Dashboard

### Create Monthly Product:
1. Go to https://dashboard.stripe.com/products
2. Click "Add product"
3. **Name**: `PodaBio Pro Monthly`
4. **Description**: `Pro plan subscription - Monthly billing`
5. **Pricing**: 
   - Price: `$4.99`
   - Billing period: `Monthly`
6. Click "Save product"
7. **Copy the Price ID** (starts with `price_...`)

### Create Annual Product:
1. Click "Add product" again
2. **Name**: `PodaBio Pro Annual`
3. **Description**: `Pro plan subscription - Annual billing (10% discount)`
4. **Pricing**:
   - Price: `$53.89`
   - Billing period: `Yearly`
5. Click "Save product"
6. **Copy the Price ID** (starts with `price_...`)

### Add Price IDs to Config:

Update `config/local.php` or `config/payments.php`:

```php
define('STRIPE_PRO_MONTHLY_PRICE_ID', 'price_...'); // Your monthly price ID

define('STRIPE_PRO_ANNUAL_PRICE_ID', 'price_...'); // Your annual price ID
```

## ✅ Step 4: Set Up Webhook

1. Go to https://dashboard.stripe.com/webhooks
2. Click "Add endpoint"
3. **Endpoint URL**: `https://yourdomain.com/api/stripe/webhook`
   - Replace `yourdomain.com` with your actual domain
   - For local testing, use Stripe CLI (see below)
4. **Events to send**:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `customer.subscription.trial_will_end`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Click "Add endpoint"
6. **Copy the Signing secret** (starts with `whsec_...`)
7. Add to config:

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_...'); // Your webhook signing secret from Stripe
```

### Local Testing with Stripe CLI:

For local development, use Stripe CLI:

```bash
# Install Stripe CLI (if not installed)
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8080/api/stripe/webhook
```

Copy the webhook signing secret shown in the terminal and use it in your local config.

## ✅ Step 5: Test

1. Navigate to Account → Billing in the admin panel
2. Click "Start 14-Day Free Trial"
3. Use test card: `4242 4242 4242 4242`
4. Any future expiry date (e.g., 12/25)
5. Any CVC (e.g., 123)
6. Complete checkout
7. Verify subscription is created with trial status

## 🚨 Common Issues

**Issue**: "Stripe SDK not found"
- **Solution**: Run `composer install` in project root

**Issue**: "Stripe not configured"
- **Solution**: Add API keys to `config/local.php` or `config/payments.php`

**Issue**: "Price ID not configured"
- **Solution**: Create products in Stripe Dashboard and add Price IDs to config

**Issue**: Webhook not receiving events
- **Solution**: Check webhook URL is correct and accessible, verify webhook secret

## 📝 Configuration File Location

- **Development**: `config/local.php` (gitignored - safe for secrets)
- **Production**: `config/payments.php` or environment variables

## 🎉 Ready to Test!

Once you've completed these steps, you're ready to test the Stripe integration!
