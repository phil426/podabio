<?php
/**
 * Migration: Add category and tag columns to themes table
 *
 * Adds JSON columns for theme organization:
 *   - categories (JSON array of category strings)
 *   - tags (JSON array of tag strings)
 *
 * Safe to run multiple times – it checks for column existence before altering.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$columnsToAdd = [
    'categories' => 'JSON NULL',
    'tags' => 'JSON NULL'
];

try {
    $existingColumnsStmt = $pdo->query("SHOW COLUMNS FROM themes");
    $existingColumns = $existingColumnsStmt ? $existingColumnsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $existingColumns = array_map('strtolower', $existingColumns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect themes table: " . $e->getMessage() . "\n";
    exit(1);
}

$addedAny = false;

foreach ($columnsToAdd as $column => $definition) {
    if (!in_array(strtolower($column), $existingColumns, true)) {
        try {
            $pdo->exec("ALTER TABLE themes ADD COLUMN `$column` $definition");
            echo "✅ Added column `$column` to themes table.\n";
            $addedAny = true;
        } catch (PDOException $e) {
            echo "❌ Failed to add column `$column`: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "ℹ️  Column `$column` already exists – skipping.\n";
    }
}

if (!$addedAny) {
    echo "✅ All category/tag columns already exist. No changes made.\n";
} else {
    echo "🎉 Theme category and tag columns are now present.\n";
}

