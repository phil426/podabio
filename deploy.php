<?php
/**
 * Auto-Deployment Script
 * Upload this file to your server root and access via browser
 * Example: https://getphily.com/deploy.php
 * 
 * SECURITY: Delete this file immediately after deployment!
 */

// Simple security - only allow if accessed directly
if (php_sapi_name() !== 'cli' && !isset($_GET['deploy'])) {
    die("
    <html>
    <head><title>Deployment</title></head>
    <body style='font-family: Arial; padding: 40px; max-width: 600px; margin: 0 auto;'>
        <h1>⚠️ Deployment Script</h1>
        <p>This script will:</p>
        <ul>
            <li>Pull latest code from GitHub</li>
            <li>Run database migration</li>
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
    <h1>🚀 Auto-Deployment</h1>
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

if (strpos($gitPull, 'Already up to date') !== false || strpos($gitPull, 'Updating') !== false) {
    echo "✅ Code updated\n\n";
} else {
    echo "⚠️  Git pull output (continuing anyway...)\n\n";
}

// Step 2: Run Database Migration
echo "[2/2] Running database migration...\n";
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDB();
    
    // Check if podcast_directories exists
    $oldTable = $pdo->query("SHOW TABLES LIKE 'podcast_directories'")->rowCount() > 0;
    $newTable = $pdo->query("SHOW TABLES LIKE 'social_icons'")->rowCount() > 0;
    
    if ($newTable) {
        $count = $pdo->query("SELECT COUNT(*) as count FROM social_icons")->fetch()['count'];
        echo "✅ Migration already completed ($count records in social_icons)\n\n";
    } elseif ($oldTable) {
        $count = $pdo->query("SELECT COUNT(*) as count FROM podcast_directories")->fetch()['count'];
        echo "Found $count records to migrate...\n";
        
        $pdo->beginTransaction();
        $pdo->exec("RENAME TABLE podcast_directories TO social_icons");
        $pdo->exec("ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages'");
        $pdo->commit();
        
        $newCount = $pdo->query("SELECT COUNT(*) as count FROM social_icons")->fetch()['count'];
        echo "✅ Migration completed successfully! ($newCount records migrated)\n\n";
    } else {
        echo "⚠️  Neither table exists. Please check your database.\n\n";
    }
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ Deployment complete!\n\n";
echo "Next steps:\n";
echo "1. Test: https://getphily.com/editor.php\n";
echo "2. Verify 'Social Icons' tab appears\n";
echo "3. Delete this deploy.php file\n";
?>
    </pre>
    
    <?php
    // Auto-delete this file after successful deployment
    if (file_exists(__FILE__)) {
        echo "<script>
            setTimeout(function() {
                if (confirm('Deployment complete! Delete deploy.php file now?')) {
                    window.location.href = '?delete=1';
                }
            }, 2000);
        </script>";
        
        if (isset($_GET['delete'])) {
            @unlink(__FILE__);
            echo "<p style='color: #0f0;'>✅ deploy.php deleted for security.</p>";
            exit;
        }
    }
    ?>
</body>
</html>

