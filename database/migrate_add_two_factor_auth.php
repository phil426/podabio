<?php
/**
 * Migration: Add Two-Factor Authentication (2FA) columns to users table
 * 
 * Adds columns for TOTP and Email-based 2FA support.
 * 
 * Safe to run multiple times – checks for column existence before altering.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

// Check existing columns
try {
    $existingColumnsStmt = $pdo->query("SHOW COLUMNS FROM users");
    $existingColumns = $existingColumnsStmt ? $existingColumnsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $existingColumns = array_map('strtolower', $existingColumns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect users table: " . $e->getMessage() . "\n";
    exit(1);
}

$addedAny = false;

// Add two_factor_enabled column
if (!in_array('two_factor_enabled', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE");
        echo "✅ Added column `two_factor_enabled` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_enabled`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_enabled` already exists – skipping.\n";
}

// Add two_factor_method column
if (!in_array('two_factor_method', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_method` ENUM('totp', 'email', 'both') DEFAULT NULL");
        echo "✅ Added column `two_factor_method` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_method`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_method` already exists – skipping.\n";
}

// Add two_factor_secret column (for TOTP)
if (!in_array('two_factor_secret', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_secret` VARCHAR(255) NULL COMMENT 'TOTP secret (encrypted)'");
        echo "✅ Added column `two_factor_secret` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_secret`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_secret` already exists – skipping.\n";
}

// Add two_factor_backup_codes column
if (!in_array('two_factor_backup_codes', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_backup_codes` JSON NULL COMMENT 'One-time backup codes'");
        echo "✅ Added column `two_factor_backup_codes` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_backup_codes`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_backup_codes` already exists – skipping.\n";
}

// Add two_factor_email_code column (temporary storage)
if (!in_array('two_factor_email_code', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_email_code` VARCHAR(10) NULL COMMENT 'Temporary email code'");
        echo "✅ Added column `two_factor_email_code` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_email_code`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_email_code` already exists – skipping.\n";
}

// Add two_factor_email_code_expires column
if (!in_array('two_factor_email_code_expires', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `two_factor_email_code_expires` DATETIME NULL");
        echo "✅ Added column `two_factor_email_code_expires` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `two_factor_email_code_expires`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `two_factor_email_code_expires` already exists – skipping.\n";
}

// Add index for faster lookups
try {
    $indexCheck = $pdo->query("SHOW INDEXES FROM users WHERE Key_name = 'idx_two_factor_enabled'");
    if ($indexCheck && $indexCheck->rowCount() == 0) {
        $pdo->exec("CREATE INDEX `idx_two_factor_enabled` ON users (`two_factor_enabled`)");
        echo "✅ Added index `idx_two_factor_enabled` on `two_factor_enabled`.\n";
        $addedAny = true;
    } else {
        echo "ℹ️  Index `idx_two_factor_enabled` already exists – skipping.\n";
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
        echo "⚠️  Could not add index (may already exist): " . $e->getMessage() . "\n";
    } else {
        echo "ℹ️  Index `idx_two_factor_enabled` already exists – skipping.\n";
    }
}

if (!$addedAny) {
    echo "✅ Two-factor authentication columns already exist. No changes made.\n";
} else {
    echo "🎉 Two-factor authentication columns are now configured.\n";
}

