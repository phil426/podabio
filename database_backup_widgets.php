<?php
/**
 * Database Backup Script for Widget Improvements
 * Backs up widgets and analytics_events tables before migration
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$backupDir = __DIR__ . '/database_backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$timestamp = date('Ymd_His');
$backupFile = $backupDir . '/widgets_backup_' . $timestamp . '.sql';

try {
    $pdo = getDB();
    $output = "-- Database Backup: Widgets Table\n";
    $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Branch: feature/widget-improvements\n\n";
    
    // Backup widgets table structure
    $output .= "-- Widgets Table Structure\n";
    $createTable = $pdo->query("SHOW CREATE TABLE widgets")->fetch();
    $output .= "DROP TABLE IF EXISTS `widgets_backup`;\n";
    $output .= $createTable['Create Table'] . ";\n\n";
    
    // Backup widgets table data
    $output .= "-- Widgets Table Data\n";
    $widgets = $pdo->query("SELECT * FROM widgets")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($widgets)) {
        foreach ($widgets as $widget) {
            $values = array_map(function($value) use ($pdo) {
                return $value === null ? 'NULL' : $pdo->quote($value);
            }, $widget);
            $output .= "INSERT INTO `widgets_backup` VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    $output .= "\n";
    
    // Backup analytics_events structure (check if table exists)
    $tables = $pdo->query("SHOW TABLES LIKE 'analytics_events'")->fetchAll();
    if (!empty($tables)) {
        $output .= "-- Analytics Events Table Structure\n";
        $createTable = $pdo->query("SHOW CREATE TABLE analytics_events")->fetch();
        $output .= "DROP TABLE IF EXISTS `analytics_events_backup`;\n";
        $output .= $createTable['Create Table'] . ";\n\n";
        
        $output .= "-- Analytics Events Table Data (first 1000 rows)\n";
        $events = $pdo->query("SELECT * FROM analytics_events LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($events)) {
            foreach ($events as $event) {
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $event);
                $output .= "INSERT INTO `analytics_events_backup` VALUES (" . implode(', ', $values) . ");\n";
            }
        }
        $output .= "\n";
    }
    
    // Write backup file
    file_put_contents($backupFile, $output);
    
    // Create rollback script
    $rollbackFile = $backupDir . '/rollback_widgets_' . $timestamp . '.sql';
    $rollback = "-- Rollback Script for Widget Featured Fields Migration\n";
    $rollback .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $rollback .= "ALTER TABLE widgets \n";
    $rollback .= "DROP COLUMN IF EXISTS featured_effect,\n";
    $rollback .= "DROP COLUMN IF EXISTS is_featured;\n";
    file_put_contents($rollbackFile, $rollback);
    
    echo "✓ Backup completed successfully!\n";
    echo "Backup file: $backupFile\n";
    echo "File size: " . filesize($backupFile) . " bytes\n";
    echo "Rollback file: $rollbackFile\n";
    echo "\n";
    echo "Widgets backed up: " . count($widgets) . " records\n";
    
} catch (Exception $e) {
    echo "✗ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

