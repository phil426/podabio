<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$migrations = [
    "ALTER TABLE pages ADD COLUMN profile_image_shape ENUM('circle','rounded','square') NOT NULL DEFAULT 'circle' AFTER profile_image",
    "ALTER TABLE pages ADD COLUMN profile_image_shadow ENUM('none','subtle','strong') NOT NULL DEFAULT 'subtle' AFTER profile_image_shape",
    "ALTER TABLE pages ADD COLUMN profile_image_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium' AFTER profile_image_shadow",
    "ALTER TABLE pages ADD COLUMN profile_image_border ENUM('none','thin','thick') NOT NULL DEFAULT 'none' AFTER profile_image_size",
    "ALTER TABLE pages ADD COLUMN name_alignment ENUM('left','center','right') NOT NULL DEFAULT 'center' AFTER profile_image_border",
    "ALTER TABLE pages ADD COLUMN name_text_size ENUM('large','xlarge','xxlarge') NOT NULL DEFAULT 'large' AFTER name_alignment",
    "ALTER TABLE pages ADD COLUMN bio_alignment ENUM('left','center','right') NOT NULL DEFAULT 'center' AFTER name_text_size",
    "ALTER TABLE pages ADD COLUMN bio_text_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium' AFTER bio_alignment"
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

