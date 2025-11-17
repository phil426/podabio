<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$migrations = [
    "ALTER TABLE pages DROP COLUMN scheduled_publish_at",
    "ALTER TABLE pages DROP COLUMN published_at",
    "ALTER TABLE pages DROP COLUMN publish_status"
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo 'Executed: ' . $sql . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'check that column/key exists') !== false || strpos(strtolower($e->getMessage()), 'unknown column') !== false) {
            echo 'Skipped (column already removed): ' . $sql . PHP_EOL;
        } else {
            throw $e;
        }
    }
}

