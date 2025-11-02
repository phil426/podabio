<?php
/**
 * Widget Features Deployment Script
 * Visit: https://getphily.com/deploy_widgets.php?deploy=1
 * 
 * SECURITY: Delete this file after deployment!
 */

// Simple security check
if (!isset($_GET['deploy']) || $_GET['deploy'] !== '1') {
    die("
    <html>
    <head><title>Widget Features Deployment</title></head>
    <body style='font-family: Arial; padding: 40px; max-width: 600px; margin: 0 auto;'>
        <h1>🚀 Widget Features Deployment</h1>
        <p>This script will:</p>
        <ul>
            <li>Pull latest code from GitHub</li>
            <li>Show deployment summary</li>
        </ul>
        <p><strong>⚠️ After deployment, delete this file for security!</strong></p>
        <a href='?deploy=1' style='background: #0066ff; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px;'>
            Deploy Now
        </a>
    </body>
    </html>
    ");
}

set_time_limit(300);
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
        .info { color: #ff0; border-left-color: #ff0; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Widget Features Deployment</h1>
    <pre>
<?php
echo "Starting deployment...\n";
echo str_repeat("=", 60) . "\n\n";

// Step 1: Pull from Git
echo "[1/2] Pulling latest code from GitHub...\n";
$projectDir = __DIR__;
chdir($projectDir);

$gitPull = shell_exec('git pull origin main 2>&1');
echo $gitPull . "\n";

if (strpos($gitPull, 'Already up to date') !== false || strpos($gitPull, 'Updating') !== false || strpos($gitPull, 'Fast-forward') !== false) {
    echo "\n✅ Code updated successfully\n\n";
    
    echo "[2/2] Deployment Summary:\n";
    echo str_repeat("-", 60) . "\n";
    echo "Files deployed:\n";
    echo "  ✓ editor.php (widget features, preview, analytics)\n";
    echo "  ✓ page.php (featured widgets rendering)\n";
    echo "  ✓ classes/Page.php (widget methods)\n";
    echo "  ✓ classes/WidgetRenderer.php (blog widgets)\n";
    echo "  ✓ classes/WidgetRegistry.php (new widgets)\n";
    echo "  ✓ classes/Analytics.php (widget analytics)\n";
    echo "  ✓ api/widgets.php (featured widget support)\n";
    echo "  ✓ api/analytics.php (analytics API)\n";
    echo "  ✓ api/blog_categories.php (blog widget support)\n";
    echo "  ✓ click.php (widget click tracking)\n";
    echo "  ✓ database/migrate_add_featured_widgets.php\n";
    echo "  ✓ database_backup_widgets.php\n";
    echo "\n";
    
    echo "Next Steps:\n";
    echo "  1. Run database migration:\n";
    echo "     https://getphily.com/database/migrate_add_featured_widgets.php\n";
    echo "  2. Delete this file for security: deploy_widgets.php\n";
    echo "  3. Test features in editor\n";
    echo "\n";
    echo str_repeat("=", 60) . "\n";
    echo "✅ Deployment Complete!\n";
    
} else {
    echo "\n❌ Git pull may have failed. Please check the output above.\n";
    echo "\nYou can also manually pull via SSH:\n";
    echo "  cd /home/u810635266/domains/getphily.com/public_html\n";
    echo "  git pull origin main\n";
}
?>
    </pre>
</body>
</html>

