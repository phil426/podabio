<?php
/**
 * Migration: Add Footer Fields
 * Adds footer_copyright, footer_privacy_link, and footer_terms_link columns to pages table
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$migrations = [
    "ALTER TABLE pages ADD COLUMN footer_copyright VARCHAR(100) NULL",
    "ALTER TABLE pages ADD COLUMN footer_privacy_link VARCHAR(500) NULL",
    "ALTER TABLE pages ADD COLUMN footer_terms_link VARCHAR(500) NULL"
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

echo PHP_EOL . 'Migration completed!' . PHP_EOL;

