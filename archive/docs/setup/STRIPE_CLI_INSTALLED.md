# ✅ Stripe CLI Installed Successfully!

## Installation Complete

- ✅ Stripe CLI version 1.33.0 installed to `~/.local/bin/stripe`
- ✅ Added to PATH in `~/.zshrc`
- ✅ Verified installation

## Next Steps

### 1. Login to Stripe CLI

Run this command and follow the browser prompt:

```bash
stripe login
```

This will open your browser to authenticate with Stripe.

### 2. Start Webhook Forwarding

Once logged in, start forwarding webhooks to your local server:

```bash
./scripts/start-stripe-webhook.sh
```

Or manually:

```bash
stripe listen --forward-to localhost:8080/api/stripe/webhook
```

### 3. Update Webhook Secret (IMPORTANT!)

When `stripe listen` starts, it will output a webhook signing secret like:

```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx
```

**Update `config/local.php` with this new secret:**

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx'); // Get from stripe listen output
```

⚠️ **Note**: The CLI-generated secret is different from your production webhook secret. You'll need to switch between them when testing locally vs. production.

### 4. Keep It Running

Keep the `stripe listen` process running in a terminal while testing. It will show webhook events in real-time.

## Testing Flow

1. ✅ Stripe CLI installed
2. ⏳ Login: `stripe login`
3. ⏳ Start webhook forwarding: `./scripts/start-stripe-webhook.sh`
4. ⏳ Update webhook secret in `config/local.php`
5. ⏳ Test checkout/trial in your app
6. ⏳ Watch webhook events in Stripe CLI terminal

## Helper Scripts

- `./scripts/start-stripe-webhook.sh` - Start webhook forwarding
- `./scripts/verify-stripe-setup.php` - Verify configuration

## Location

Stripe CLI is installed at: `~/.local/bin/stripe`

If you open a new terminal, it will be available automatically (added to PATH in ~/.zshrc).

