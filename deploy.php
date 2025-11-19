<?php
/**
 * Web-Based Deployment Script
 * PodaBio - Deploy code from GitHub via browser
 * 
 * SECURITY: This script requires admin authentication and CSRF token
 * DELETE THIS FILE after deployment or restrict access via .htaccess
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security.php';

// Check if user is admin
function isAdmin() {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Check if user email is admin
    $adminEmails = ['phil@redwoodempiremedia.com', 'cursor@poda.bio', 'phil624@gmail.com'];
    return in_array(strtolower($user['email']), array_map('strtolower', $adminEmails));
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

requireAdmin();

$csrfToken = generateCSRFToken();
$output = [];
$success = false;
$error = null;

// Handle deployment request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please refresh the page and try again.';
    } else {
        $projectDir = __DIR__;
        
        // Step 1: Git Pull
        $output[] = "📦 Step 1: Pulling latest code from GitHub...";
        $gitPull = shell_exec("cd {$projectDir} && git pull origin main 2>&1");
        
        if ($gitPull === null) {
            $error = "Failed to execute git pull. Check server permissions.";
        } else {
            $output[] = $gitPull;
            
            // Check if git pull was successful
            if (strpos($gitPull, 'Already up to date') !== false || 
                strpos($gitPull, 'Updating') !== false || 
                strpos($gitPull, 'Fast-forward') !== false) {
                $output[] = "✅ Code updated successfully";
                
                // Step 2: Check database migration
                $output[] = "";
                $output[] = "🗄️  Step 2: Checking database migration status...";
                
                try {
                    $pdo = getDB();
                    $tableExists = $pdo->query("SHOW TABLES LIKE 'podcast_directories'")->rowCount() > 0;
                    $newTableExists = $pdo->query("SHOW TABLES LIKE 'social_icons'")->rowCount() > 0;
                    
                    if ($newTableExists) {
                        $output[] = "✅ Migration already completed - social_icons table exists";
                    } elseif ($tableExists) {
                        $output[] = "📋 podcast_directories table found, running migration...";
                        
                        // Run migration
                        $pdo->beginTransaction();
                        try {
                            $pdo->exec('RENAME TABLE podcast_directories TO social_icons');
                            $pdo->exec("ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages'");
                            $pdo->commit();
                            
                            $count = $pdo->query('SELECT COUNT(*) as count FROM social_icons')->fetch()['count'];
                            $output[] = "✅ Migration completed successfully! Migrated {$count} records.";
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            throw $e;
                        }
                    } else {
                        $output[] = "⚠️  Neither table exists. Migration may not be needed.";
                    }
                    
                    $success = true;
                } catch (Exception $e) {
                    $error = "Migration failed: " . $e->getMessage();
                }
            } else {
                $error = "Git pull may have failed. Check the output above.";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy - PodaBio</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .warning strong {
            display: block;
            margin-bottom: 5px;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            margin: 20px 0;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            margin-top: 15px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .info {
            color: #0c5460;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Deploy to Production (poda.bio)</h1>
        
        <div class="warning">
            <strong>⚠️ Security Warning</strong>
            This script has admin-level access. Delete this file after deployment or restrict access via .htaccess.
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                ✅ Deployment Complete!
            </div>
            <div class="info">
                <strong>Next steps:</strong><br>
                1. Test PHP backend: <a href="https://poda.bio/index.php" target="_blank">https://poda.bio/index.php</a><br>
                2. Test admin panel: <a href="https://poda.bio/admin/userdashboard.php" target="_blank">https://poda.bio/admin/userdashboard.php</a><br>
                3. Verify React app loads (check browser console)<br>
                4. Test database connectivity<br>
                5. Test file uploads<br>
                6. <strong>Delete this deploy.php file for security</strong>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($output)): ?>
            <div class="output"><?php echo htmlspecialchars(implode("\n", $output)); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <button type="submit" class="btn" <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) ? 'disabled' : ''; ?>>
                <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) ? 'Deployment Complete' : 'Deploy from GitHub'; ?>
            </button>
        </form>
        
        <div class="info" style="margin-top: 30px;">
            <strong>What this does:</strong><br>
            1. Pulls latest code from GitHub (main branch)<br>
            2. Checks and runs database migrations if needed<br>
            3. Verifies deployment success
        </div>
    </div>
</body>
</html>

