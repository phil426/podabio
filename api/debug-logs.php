<?php
/**
 * Debug Logs Endpoint
 * Shows recent PHP error_log entries related to theme updates
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireAuth();

// Try to read from common log locations
$logFiles = [
    '/usr/local/var/log/php-fpm.log',
    '/var/log/php_errors.log',
    '/var/log/php-fpm/error.log',
    sys_get_temp_dir() . '/php_errors.log'
];

$logs = [];
$foundLogs = false;

foreach ($logFiles as $logFile) {
    if (file_exists($logFile) && is_readable($logFile)) {
        $foundLogs = true;
        $lines = file($logFile);
        if ($lines) {
            // Get last 500 lines
            $recentLines = array_slice($lines, -500);
            
            // Filter for theme-related logs
            foreach ($recentLines as $line) {
                if (stripos($line, 'THEME') !== false || 
                    stripos($line, 'typography') !== false || 
                    stripos($line, 'SAVE THEME') !== false) {
                    $logs[] = trim($line);
                }
            }
        }
        break; // Use first found log file
    }
}

if (!$foundLogs) {
    // Try to get from syslog
    $logs[] = "No log files found. PHP error_log may be going to syslog or stderr.";
    $logs[] = "Check your PHP configuration: php -i | grep error_log";
}

// Also check database for recent theme updates
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Theme.php';

$themeClass = new Theme();
$userId = getUserId();

$recentThemes = fetchAll(
    "SELECT id, name, typography_tokens, updated_at 
     FROM themes 
     WHERE user_id = ? 
     ORDER BY updated_at DESC 
     LIMIT 5",
    [$userId]
);

$themeData = [];
foreach ($recentThemes as $theme) {
    $tt = null;
    if (!empty($theme['typography_tokens'])) {
        $tt = is_string($theme['typography_tokens']) 
            ? json_decode($theme['typography_tokens'], true) 
            : $theme['typography_tokens'];
    }
    
    $themeData[] = [
        'id' => $theme['id'],
        'name' => $theme['name'],
        'updated_at' => $theme['updated_at'],
        'has_color' => isset($tt['color']),
        'heading_color' => $tt['color']['heading'] ?? null,
        'body_color' => $tt['color']['body'] ?? null
    ];
}

echo json_encode([
    'success' => true,
    'log_entries' => array_slice($logs, -100), // Last 100 matching lines
    'recent_themes' => $themeData,
    'log_file_found' => $foundLogs
], JSON_PRETTY_PRINT);

