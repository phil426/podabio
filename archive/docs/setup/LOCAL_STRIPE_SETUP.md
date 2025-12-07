# Stripe CLI Setup for Local Development

## Current Status

- ✅ Stripe configuration complete
- ❌ Stripe CLI not installed (needed for local webhook testing)

## Why You Need Stripe CLI

Since you're running **locally** (`localhost:8080`), Stripe can't send webhooks directly to your machine. Stripe CLI creates a tunnel that forwards webhooks from Stripe to your local server.

## Installation

### Option 1: Quick Install Script (Recommended)

I've created an install script. Run:

```bash
./scripts/install-stripe-cli.sh
```

### Option 2: Manual Installation

1. **Download from GitHub:**
   - Go to: https://github.com/stripe/stripe-cli/releases/latest
   - Download: `stripe_X.X.X_macos_arm64.tar.gz` (for Apple Silicon)
   - Or: `stripe_X.X.X_macos_x86_64.tar.gz` (for Intel)

2. **Install:**
   ```bash
   tar -xzf stripe_*.tar.gz
   sudo mv stripe /usr/local/bin/
   ```

3. **Verify:**
   ```bash
   stripe --version
   ```

### Option 3: Homebrew (if Command Line Tools are updated)

```bash
brew tap stripe/stripe-cli
brew install stripe/stripe-cli/stripe
```

## Setup Steps

### 1. Login to Stripe CLI

```bash
stripe login
```

This opens your browser to authenticate with Stripe.

### 2. Start Webhook Forwarding

Run the helper script:

```bash
./scripts/start-stripe-webhook.sh
```

Or manually:

```bash
stripe listen --forward-to localhost:8080/api/stripe/webhook
```

### 3. Update Webhook Secret (IMPORTANT!)

When `stripe listen` starts, it will display a webhook signing secret like:

```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx
```

**You MUST update `config/local.php` with this new secret:**

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx'); // Get from stripe listen output
```

⚠️ **Note**: The CLI-generated secret is different from your production webhook secret. You'll need to switch between them when testing locally vs. production.

### 4. Keep It Running

Keep the `stripe listen` process running while testing. It will show webhook events in real-time.

## Testing Flow

1. ✅ Start PHP server: `php -S localhost:8080 router.php`
2. ✅ Start Stripe CLI: `./scripts/start-stripe-webhook.sh`
3. ✅ Update webhook secret in `config/local.php`
4. ✅ Test checkout/trial in your app
5. ✅ Watch webhook events in Stripe CLI terminal

## Current Configuration

- **Local Server**: `http://localhost:8080`
- **Webhook Endpoint**: `http://localhost:8080/api/stripe/webhook`
- **PHP Port**: 8080

## Troubleshooting

**"stripe: command not found"**
- Stripe CLI isn't installed or not in PATH
- Try: `which stripe` to check

**"Not logged in to Stripe CLI"**
- Run: `stripe login`

**Webhooks not being received**
- Make sure `stripe listen` is running
- Check webhook secret matches the one from `stripe listen`
- Verify PHP server is running on port 8080
