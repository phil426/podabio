<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$migrations = [
    "ALTER TABLE pages ADD COLUMN publish_status ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft' AFTER is_active",
    "ALTER TABLE pages ADD COLUMN published_at DATETIME NULL AFTER publish_status",
    "ALTER TABLE pages ADD COLUMN scheduled_publish_at DATETIME NULL AFTER published_at"
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo 'Executed: ' . $sql . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo 'Skipped (already exists): ' . $sql . PHP_EOL;
        } else {
            throw $e;
        }
    }
}

