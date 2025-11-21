<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$sql = "ALTER TABLE pages ADD COLUMN footer_text TEXT NULL AFTER podcast_description";

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


