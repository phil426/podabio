<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

try {
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS page_token_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id INT UNSIGNED NOT NULL,
            overrides JSON NOT NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_page_created_at (page_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
    echo "Created page_token_history table." . PHP_EOL;
} catch (PDOException $e) {
    echo "Failed to create page_token_history table: " . $e->getMessage() . PHP_EOL;
    throw $e;
}

