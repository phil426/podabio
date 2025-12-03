<?php
/**
 * Stripe Setup Verification Script
 * Checks if Stripe is properly configured
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';

if (file_exists(__DIR__ . '/../config/local.php')) {
    require_once __DIR__ . '/../config/local.php';
}

echo "🔍 Verifying Stripe Configuration...\n\n";

$issues = [];
$warnings = [];

// Check Stripe API keys
if (empty(STRIPE_SECRET_KEY)) {
    $issues[] = "❌ STRIPE_SECRET_KEY is not set";
} else {
    if (strpos(STRIPE_SECRET_KEY, 'sk_test_') === 0) {
        echo "✅ Stripe Secret Key (Test Mode): " . substr(STRIPE_SECRET_KEY, 0, 12) . "...\n";
    } elseif (strpos(STRIPE_SECRET_KEY, 'sk_live_') === 0) {
        echo "✅ Stripe Secret Key (Live Mode): " . substr(STRIPE_SECRET_KEY, 0, 12) . "...\n";
    } else {
        $issues[] = "❌ STRIPE_SECRET_KEY format appears invalid";
    }
}

if (empty(STRIPE_PUBLISHABLE_KEY)) {
    $issues[] = "❌ STRIPE_PUBLISHABLE_KEY is not set";
} else {
    if (strpos(STRIPE_PUBLISHABLE_KEY, 'pk_test_') === 0) {
        echo "✅ Stripe Publishable Key (Test Mode): " . substr(STRIPE_PUBLISHABLE_KEY, 0, 12) . "...\n";
    } elseif (strpos(STRIPE_PUBLISHABLE_KEY, 'pk_live_') === 0) {
        echo "✅ Stripe Publishable Key (Live Mode): " . substr(STRIPE_PUBLISHABLE_KEY, 0, 12) . "...\n";
    } else {
        $issues[] = "❌ STRIPE_PUBLISHABLE_KEY format appears invalid";
    }
}

if (empty(STRIPE_WEBHOOK_SECRET)) {
    $warnings[] = "⚠️  STRIPE_WEBHOOK_SECRET is not set (required for webhook verification)";
} else {
    echo "✅ Stripe Webhook Secret: " . substr(STRIPE_WEBHOOK_SECRET, 0, 10) . "...\n";
}

// Check mode
echo "✅ Stripe Mode: " . STRIPE_MODE . "\n";

// Check price IDs
if (empty(STRIPE_PRO_MONTHLY_PRICE_ID)) {
    $issues[] = "❌ STRIPE_PRO_MONTHLY_PRICE_ID is not set (create product in Stripe Dashboard)";
} else {
    echo "✅ Monthly Price ID: " . STRIPE_PRO_MONTHLY_PRICE_ID . "\n";
}

if (empty(STRIPE_PRO_ANNUAL_PRICE_ID)) {
    $issues[] = "❌ STRIPE_PRO_ANNUAL_PRICE_ID is not set (create product in Stripe Dashboard)";
} else {
    echo "✅ Annual Price ID: " . STRIPE_PRO_ANNUAL_PRICE_ID . "\n";
}

// Check Stripe SDK
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "✅ Stripe SDK installed (vendor/autoload.php exists)\n";
} else {
    $issues[] = "❌ Stripe SDK not found. Run: composer install";
}

// Check pricing constants
echo "✅ Pro Monthly Price: $" . PLAN_PRO_MONTHLY_PRICE . "/month\n";
echo "✅ Pro Annual Price: $" . PLAN_PRO_ANNUAL_PRICE . "/year\n";
echo "✅ Trial Period: " . TRIAL_PERIOD_DAYS . " days\n";

echo "\n";

if (count($issues) > 0) {
    echo "🚨 Issues Found:\n";
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
    echo "\n";
}

if (count($issues) === 0 && count($warnings) === 0) {
    echo "🎉 Stripe configuration looks good!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test the trial signup flow in the admin panel\n";
    echo "2. Set up webhook endpoint in Stripe Dashboard\n";
    echo "3. Test subscription purchase\n";
} elseif (count($issues) === 0) {
    echo "✅ Configuration is functional, but check warnings above.\n";
} else {
    echo "❌ Please fix the issues above before testing.\n";
    echo "\n";
    echo "Quick setup:\n";
    echo "1. Copy config/local.php.example to config/local.php\n";
    echo "2. Add your Stripe API keys from https://dashboard.stripe.com/apikeys\n";
    echo "3. Create products in Stripe Dashboard and add Price IDs\n";
    echo "4. See STRIPE_QUICK_SETUP.md for detailed instructions\n";
}

echo "\n";

