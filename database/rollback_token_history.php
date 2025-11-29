<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

try {
    $pdo->exec("DROP TABLE IF EXISTS page_token_history");
    echo "Dropped page_token_history table." . PHP_EOL;
} catch (PDOException $e) {
    echo "Failed to drop page_token_history table: " . $e->getMessage() . PHP_EOL;
    throw $e;
}

