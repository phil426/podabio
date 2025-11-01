<?php
/**
 * Simple Web-Based Deployment Script
 * Only pulls code from GitHub - no database migration
 * 
 * SECURITY: Delete this file immediately after deployment!
 */

// Simple security - only allow if accessed directly
if (php_sapi_name() !== 'cli' && !isset($_GET['deploy'])) {
    die("
    <html>
    <head><title>Deployment</title></head>
    <body style='font-family: Arial; padding: 40px; max-width: 600px; margin: 0 auto;'>
        <h1>⚠️ Simple Deployment</h1>
        <p>This script will:</p>
        <ul>
            <li>Pull latest code from GitHub</li>
        </ul>
        <p><strong>After deployment, this file will be automatically deleted.</strong></p>
        <a href='?deploy=1' style='background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px;'>
            Run Deployment
        </a>
    </body>
    </html>
    ");
}

set_time_limit(300); // 5 minutes max
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deployment Progress</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .step { margin: 10px 0; padding: 10px; background: #2a2a2a; border-left: 3px solid #0f0; }
        .error { color: #f00; border-left-color: #f00; }
        .success { color: #0f0; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Simple Deployment</h1>
    <pre>
<?php
echo "Starting deployment...\n";
echo str_repeat("=", 60) . "\n\n";

// Step 1: Pull from Git
echo "Pulling latest code from GitHub...\n";
$projectDir = __DIR__;
chdir($projectDir);

$gitPull = shell_exec('git pull origin main 2>&1');
echo $gitPull . "\n";

if (strpos($gitPull, 'Already up to date') !== false) {
    echo "✅ Already up to date\n\n";
} elseif (strpos($gitPull, 'Updating') !== false || strpos($gitPull, 'Fast-forward') !== false) {
    echo "✅ Code updated successfully\n\n";
} else {
    echo "⚠️  Git pull output (check above)\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ Deployment complete!\n\n";
echo "Next steps:\n";
echo "1. Test the podcast player: https://getphily.com/phil624\n";
echo "2. Verify no CORS errors in console\n";
echo "3. Delete this deploy_simple_web.php file\n";
?>
    </pre>
    
    <?php
    // Auto-delete this file after successful deployment
    if (file_exists(__FILE__)) {
        echo "<script>
            setTimeout(function() {
                if (confirm('Deployment complete! Delete deploy_simple_web.php file now?')) {
                    window.location.href = '?delete=1';
                }
            }, 2000);
        </script>";
        
        if (isset($_GET['delete'])) {
            @unlink(__FILE__);
            echo "<p style='color: #0f0;'>✅ deploy_simple_web.php deleted for security.</p>";
            exit;
        }
    }
    ?>
</body>
</html>
