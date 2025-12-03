<?php
/**
 * Migration: Add root admin flag to users table
 * 
 * Adds is_root_admin column and sets it to true for phil624@gmail.com.
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

// Add is_root_admin column if it doesn't exist
if (!in_array('is_root_admin', $existingColumns, true)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN `is_root_admin` BOOLEAN NOT NULL DEFAULT FALSE");
        echo "✅ Added column `is_root_admin` to users table.\n";
        $addedAny = true;
    } catch (PDOException $e) {
        echo "❌ Failed to add column `is_root_admin`: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "ℹ️  Column `is_root_admin` already exists – skipping.\n";
}

// Add index for root admin queries if it doesn't exist
try {
    $indexCheck = $pdo->query("SHOW INDEXES FROM users WHERE Key_name = 'idx_is_root_admin'");
    if ($indexCheck && $indexCheck->rowCount() == 0) {
        $pdo->exec("CREATE INDEX `idx_is_root_admin` ON users (`is_root_admin`)");
        echo "✅ Added index `idx_is_root_admin` on `is_root_admin`.\n";
        $addedAny = true;
    } else {
        echo "ℹ️  Index `idx_is_root_admin` already exists – skipping.\n";
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
        echo "⚠️  Could not add index (may already exist): " . $e->getMessage() . "\n";
    } else {
        echo "ℹ️  Index `idx_is_root_admin` already exists – skipping.\n";
    }
}

// Set root admin flag for phil624@gmail.com
try {
    $rootAdminEmail = 'phil624@gmail.com';
    $stmt = $pdo->prepare("UPDATE users SET is_root_admin = TRUE WHERE email = ?");
    $stmt->execute([$rootAdminEmail]);
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        echo "✅ Set is_root_admin = TRUE for $rootAdminEmail.\n";
        $addedAny = true;
    } else {
        // Check if user exists
        $userCheck = $pdo->prepare("SELECT id, is_root_admin FROM users WHERE email = ?");
        $userCheck->execute([$rootAdminEmail]);
        $user = $userCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['is_root_admin']) {
                echo "ℹ️  User $rootAdminEmail already has is_root_admin = TRUE – skipping.\n";
            } else {
                echo "⚠️  User $rootAdminEmail exists but update had no effect. Check manually.\n";
            }
        } else {
            echo "⚠️  User $rootAdminEmail not found. Will be set when user is created.\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Failed to set root admin flag: " . $e->getMessage() . "\n";
}

if (!$addedAny) {
    echo "✅ Root admin flag setup already complete. No changes made.\n";
} else {
    echo "🎉 Root admin flag is now configured.\n";
}

