<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

try {
    $pdo->exec("\n        ALTER TABLE pages\n        ADD COLUMN token_overrides JSON NULL AFTER page_secondary_font\n    ");
    echo 'Added token_overrides column to pages table.' . PHP_EOL;
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'token_overrides column already exists.' . PHP_EOL;
    } else {
        throw $e;
    }
}

