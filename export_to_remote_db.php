<?php
/**
 * Export local database to remote Hostinger MySQL server
 * This script reads from local database and writes to remote database
 */

// Local database config
$local_host = '127.0.0.1';
$local_db = 'podnbio_dev';
$local_user = 'podnbio';
$local_pass = 'podnbio_local_pass';

// Remote database config (Hostinger)
$remote_host = 'srv775.hstgr.io';
$remote_db = 'u925957603_podabio';
$remote_user = 'u925957603_pab';
$remote_pass = '[REDACTED]';

try {
    // Connect to local database
    echo "Connecting to local database...\n";
    $local_pdo = new PDO(
        "mysql:host={$local_host};dbname={$local_db};charset=utf8mb4",
        $local_user,
        $local_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connected to local database\n\n";

    // Connect to remote database
    echo "Connecting to remote database...\n";
    $remote_pdo = new PDO(
        "mysql:host={$remote_host};dbname={$remote_db};charset=utf8mb4",
        $remote_user,
        $remote_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connected to remote database\n\n";

    // Disable foreign key checks on remote
    $remote_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $remote_pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

    // Get all tables from local database
    echo "Fetching table list from local database...\n";
    $tables = $local_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables\n\n";

    foreach ($tables as $table) {
        echo "Processing table: {$table}...\n";
        
        // Get table structure
        $create_table = $local_pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $create_sql = $create_table['Create Table'];
        
        // Drop table if exists on remote
        $remote_pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        
        // Create table on remote
        $remote_pdo->exec($create_sql);
        echo "  ✓ Created table structure\n";
        
        // Get all data from local table
        $rows = $local_pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            // Get column names
            $columns = array_keys($rows[0]);
            $column_list = '`' . implode('`, `', $columns) . '`';
            $placeholders = ':' . implode(', :', $columns);
            
            // Prepare insert statement
            $insert_sql = "INSERT INTO `{$table}` ({$column_list}) VALUES ({$placeholders})";
            $stmt = $remote_pdo->prepare($insert_sql);
            
            // Insert rows in batches
            $batch_size = 100;
            $total_rows = count($rows);
            $inserted = 0;
            
            $remote_pdo->beginTransaction();
            foreach ($rows as $row) {
                $stmt->execute($row);
                $inserted++;
                
                if ($inserted % $batch_size == 0) {
                    $remote_pdo->commit();
                    $remote_pdo->beginTransaction();
                    echo "  → Inserted {$inserted}/{$total_rows} rows...\r";
                }
            }
            $remote_pdo->commit();
            echo "  ✓ Inserted {$total_rows} rows\n";
        } else {
            echo "  ✓ Table is empty\n";
        }
        
        echo "\n";
    }

    // Re-enable foreign key checks
    $remote_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "✓ Database export completed successfully!\n";
    echo "All tables have been copied from local to remote database.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

