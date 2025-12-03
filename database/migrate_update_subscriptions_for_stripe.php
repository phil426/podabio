<?php
/**
 * Migration: Add Stripe-specific columns to subscriptions table
 * 
 * Adds columns for Stripe integration:
 *   - stripe_customer_id
 *   - stripe_subscription_id
 *   - stripe_price_id
 *   - billing_interval (month/year)
 *   - trial_ends_at
 *   - is_trial
 * 
 * Also updates payment_method enum to include 'stripe'.
 * 
 * Safe to run multiple times – checks for column existence before altering.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

// Check existing columns
try {
    $existingColumnsStmt = $pdo->query("SHOW COLUMNS FROM subscriptions");
    $existingColumns = $existingColumnsStmt ? $existingColumnsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $existingColumns = array_map('strtolower', $existingColumns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect subscriptions table: " . $e->getMessage() . "\n";
    exit(1);
}

$addedAny = false;

// Stripe-specific columns to add
$columnsToAdd = [
    'stripe_customer_id' => 'VARCHAR(255) NULL',
    'stripe_subscription_id' => 'VARCHAR(255) NULL',
    'stripe_price_id' => 'VARCHAR(255) NULL',
    'billing_interval' => "ENUM('month', 'year') NULL DEFAULT 'month'",
    'trial_ends_at' => 'DATETIME NULL',
    'is_trial' => 'BOOLEAN NOT NULL DEFAULT FALSE'
];

foreach ($columnsToAdd as $column => $definition) {
    if (!in_array(strtolower($column), $existingColumns, true)) {
        try {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN `$column` $definition");
            echo "✅ Added column `$column` to subscriptions table.\n";
            $addedAny = true;
        } catch (PDOException $e) {
            echo "❌ Failed to add column `$column`: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️  Column `$column` already exists – skipping.\n";
    }
}

// Add indexes for Stripe IDs if they don't exist
$indexesToAdd = [
    'idx_stripe_customer_id' => 'stripe_customer_id',
    'idx_stripe_subscription_id' => 'stripe_subscription_id'
];

foreach ($indexesToAdd as $indexName => $column) {
    try {
        // Check if index exists
        $indexCheck = $pdo->query("SHOW INDEXES FROM subscriptions WHERE Key_name = '$indexName'");
        if ($indexCheck && $indexCheck->rowCount() == 0) {
            $pdo->exec("CREATE INDEX `$indexName` ON subscriptions (`$column`)");
            echo "✅ Added index `$indexName` on `$column`.\n";
            $addedAny = true;
        } else {
            echo "ℹ️  Index `$indexName` already exists – skipping.\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            echo "❌ Failed to add index `$indexName`: " . $e->getMessage() . "\n";
        } else {
            echo "ℹ️  Index `$indexName` already exists – skipping.\n";
        }
    }
}

// Update payment_method enum to include 'stripe'
// Note: MySQL requires recreating the column to modify ENUM
try {
    // Check current enum values
    $columnInfo = $pdo->query("SHOW COLUMNS FROM subscriptions WHERE Field = 'payment_method'")->fetch(PDO::FETCH_ASSOC);
    if ($columnInfo) {
        $currentType = $columnInfo['Type'];
        if (strpos($currentType, 'stripe') === false) {
            // Extract current enum values and add 'stripe'
            preg_match("/ENUM\((.*)\)/", $currentType, $matches);
            if ($matches) {
                $enumValues = $matches[1];
                $newEnumValues = $enumValues . ",'stripe'";
                $pdo->exec("ALTER TABLE subscriptions MODIFY COLUMN payment_method ENUM($newEnumValues) NULL");
                echo "✅ Updated payment_method enum to include 'stripe'.\n";
                $addedAny = true;
            }
        } else {
            echo "ℹ️  payment_method enum already includes 'stripe' – skipping.\n";
        }
    }
} catch (PDOException $e) {
    echo "⚠️  Could not update payment_method enum (may need manual update): " . $e->getMessage() . "\n";
}

if (!$addedAny) {
    echo "✅ All Stripe columns and indexes already exist. No changes made.\n";
} else {
    echo "🎉 Stripe subscription columns are now present.\n";
}

