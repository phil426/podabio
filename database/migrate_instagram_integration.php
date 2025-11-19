<?php
/**
 * Migration: Add Instagram integration columns to users table
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$columnsToAdd = [
    'instagram_user_id' => 'VARCHAR(255) NULL',
    'instagram_access_token' => 'TEXT NULL',
    'instagram_token_expires_at' => 'DATETIME NULL'
];

try {
    $existingColumnsStmt = $pdo->query("SHOW COLUMNS FROM users");
    $existingColumns = $existingColumnsStmt ? $existingColumnsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $existingColumns = array_map('strtolower', $existingColumns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect users table: " . $e->getMessage() . "\n";
    exit(1);
}

$addedAny = false;

foreach ($columnsToAdd as $column => $definition) {
    if (!in_array(strtolower($column), $existingColumns, true)) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
            echo "✅ Added column `$column` to users table.\n";
            $addedAny = true;
        } catch (PDOException $e) {
            echo "❌ Failed to add column `$column`: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️  Column `$column` already exists – skipping.\n";
    }
}

if (!$addedAny) {
    echo "✅ All Instagram columns already exist. No changes made.\n";
} else {
    echo "🎉 Instagram integration columns are now present.\n";
}

















