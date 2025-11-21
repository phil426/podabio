<?php
/**
 * Migration: Add token columns to themes table
 *
 * Adds JSON columns for the new theme token architecture:
 *   - color_tokens
 *   - typography_tokens
 *   - spacing_tokens
 *   - shape_tokens
 *   - motion_tokens
 *   - iconography_tokens
 * and a layout_density column.
 *
 * Safe to run multiple times – it checks for column existence before altering.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

$columnsToAdd = [
    'color_tokens' => 'JSON NULL',
    'typography_tokens' => 'JSON NULL',
    'spacing_tokens' => 'JSON NULL',
    'shape_tokens' => 'JSON NULL',
    'motion_tokens' => 'JSON NULL',
    'iconography_tokens' => 'JSON NULL',
    'layout_density' => "VARCHAR(32) NULL DEFAULT 'comfortable'"
];

try {
    $existingColumnsStmt = $pdo->query("SHOW COLUMNS FROM themes");
    $existingColumns = $existingColumnsStmt ? $existingColumnsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $existingColumns = array_map('strtolower', $existingColumns);
} catch (PDOException $e) {
    echo "❌ Failed to inspect themes table: " . $e->getMessage() . "\n";
    return;
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
        }
    } else {
        echo "ℹ️  Column `$column` already exists – skipping.\n";
    }
}

if (!$addedAny) {
    echo "✅ All token columns already exist. No changes made.\n";
} else {
    echo "🎉 Theme token columns are now present.\n";
}
