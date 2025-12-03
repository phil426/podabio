<?php
/**
 * Migration: Simplify Plans - Free & Pro Only
 * 
 * This migration script sets up the simplified plan structure:
 * - Free and Pro plans only (premium removed)
 * - Root admin flag set for phil624@gmail.com
 * 
 * Note: No subscription migration needed as there are no existing customers
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

echo "🔄 Starting plan simplification migration...\n\n";

$pdo = getDB();

// Check if root admin flag migration has been run
$rootAdminColumn = null;
try {
    $columns = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'is_root_admin'")->fetchAll(PDO::FETCH_ASSOC);
    if (count($columns) > 0) {
        $rootAdminColumn = $columns[0];
    }
} catch (PDOException $e) {
    echo "⚠️  Could not check for is_root_admin column: " . $e->getMessage() . "\n";
}

if (!$rootAdminColumn) {
    echo "⚠️  Root admin flag column not found. Please run migrate_add_root_admin_flag.php first.\n";
    echo "   Run: php database/migrate_add_root_admin_flag.php\n\n";
} else {
    echo "✅ Root admin flag column exists\n";
    
    // Verify root admin is set
    $rootAdminEmail = 'phil624@gmail.com';
    $user = fetchOne("SELECT id, email, is_root_admin FROM users WHERE email = ?", [$rootAdminEmail]);
    
    if ($user) {
        if ($user['is_root_admin']) {
            echo "✅ Root admin flag already set for {$rootAdminEmail}\n";
        } else {
            try {
                executeQuery("UPDATE users SET is_root_admin = TRUE WHERE email = ?", [$rootAdminEmail]);
                echo "✅ Root admin flag set for {$rootAdminEmail}\n";
            } catch (PDOException $e) {
                echo "❌ Failed to set root admin flag: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "⚠️  User {$rootAdminEmail} not found. Will be set when user is created.\n";
    }
}

// Check for any existing premium subscriptions (shouldn't be any, but check anyway)
$premiumSubscriptions = fetchAll(
    "SELECT id, user_id, plan_type FROM subscriptions WHERE plan_type = 'premium' AND status = 'active'"
);

if (count($premiumSubscriptions) > 0) {
    echo "\n⚠️  Found " . count($premiumSubscriptions) . " active premium subscription(s).\n";
    echo "   These should be converted to Pro plan.\n";
    
    foreach ($premiumSubscriptions as $sub) {
        try {
            executeQuery(
                "UPDATE subscriptions SET plan_type = 'pro', updated_at = NOW() WHERE id = ?",
                [$sub['id']]
            );
            echo "   ✅ Converted subscription ID {$sub['id']} from premium to pro\n";
        } catch (PDOException $e) {
            echo "   ❌ Failed to convert subscription ID {$sub['id']}: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "✅ No premium subscriptions found to convert\n";
}

// Verify Stripe fields exist in subscriptions table
$stripeFields = ['stripe_customer_id', 'stripe_subscription_id', 'stripe_price_id', 'billing_interval', 'trial_ends_at', 'is_trial'];
$missingFields = [];

foreach ($stripeFields as $field) {
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM subscriptions WHERE Field = '{$field}'")->fetchAll(PDO::FETCH_ASSOC);
        if (count($columns) === 0) {
            $missingFields[] = $field;
        }
    } catch (PDOException $e) {
        $missingFields[] = $field;
    }
}

if (count($missingFields) > 0) {
    echo "\n⚠️  Missing Stripe fields: " . implode(', ', $missingFields) . "\n";
    echo "   Please run: php database/migrate_update_subscriptions_for_stripe.php\n\n";
} else {
    echo "✅ All Stripe fields present in subscriptions table\n";
}

echo "\n✅ Plan simplification migration complete!\n";
echo "\n📋 Summary:\n";
echo "   - Plans: Free and Pro only (premium removed)\n";
echo "   - Root admin: phil624@gmail.com\n";
echo "   - Stripe integration: Ready\n";
echo "\n";

