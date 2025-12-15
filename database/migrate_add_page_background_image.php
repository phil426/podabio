<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$migrations = [
    "ALTER TABLE pages ADD COLUMN page_background_image_url TEXT DEFAULT NULL AFTER page_background",
    "ALTER TABLE pages ADD COLUMN page_background_image_overlay VARCHAR(50) DEFAULT 'rgba(0,0,0,0.4)' AFTER page_background_image_url",
    "ALTER TABLE pages ADD COLUMN page_background_image_focal_x VARCHAR(10) DEFAULT '50%' AFTER page_background_image_overlay",
    "ALTER TABLE pages ADD COLUMN page_background_image_focal_y VARCHAR(10) DEFAULT '50%' AFTER page_background_image_focal_x",
    "ALTER TABLE pages ADD COLUMN page_background_image_scale DECIMAL(3,2) DEFAULT 1.00 AFTER page_background_image_focal_y",
    "ALTER TABLE pages ADD COLUMN page_background_image_blur VARCHAR(10) DEFAULT '0px' AFTER page_background_image_scale"
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
