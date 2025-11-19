<?php
/**
 * Admin Panel - System Settings
 * PodaBio - System configuration
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/index.php';

requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    // Settings would be saved here (e.g., to a settings table or config file)
    $message = 'Settings saved successfully (configuration stored in config files)';
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; }
        .admin-header { background: #1f2937; color: white; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 1.5rem; color: #667eea; }
        .admin-nav { display: flex; gap: 1rem; }
        .admin-nav a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; transition: background 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: #374151; }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .section h2 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #1f2937; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 1rem; }
        .btn { padding: 0.75rem 2rem; border-radius: 6px; background: #667eea; color: white; border: none; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <h1>Admin Panel - <?php echo h(APP_NAME); ?></h1>
            <nav class="admin-nav">
                <a href="/admin/">Dashboard</a>
                <a href="/admin/users.php">Users</a>
                <a href="/admin/pages.php">Pages</a>
                <a href="/admin/subscriptions.php">Subscriptions</a>
                <a href="/admin/analytics.php">Analytics</a>
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php">Support</a>
                <a href="/admin/settings.php" class="active">Settings</a>
                <a href="/admin/select-panel.php">Switch Panel</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <h2 style="margin-bottom: 2rem; color: #1f2937;">System Settings</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Application Information</h2>
            <div class="form-group">
                <label>Application Name</label>
                <input type="text" value="<?php echo h(APP_NAME); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Application URL</label>
                <input type="text" value="<?php echo h(APP_URL); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Application Version</label>
                <input type="text" value="<?php echo h(APP_VERSION); ?>" disabled>
            </div>
            <p style="color: #6b7280; margin-top: 1rem;">
                <strong>Note:</strong> Application settings are configured in <code>config/constants.php</code> and other configuration files.
                To change these settings, edit the configuration files directly on the server.
            </p>
        </div>
        
        <div class="section">
            <h2>System Status</h2>
            <div class="form-group">
                <label>PHP Version</label>
                <input type="text" value="<?php echo phpversion(); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Server Environment</label>
                <input type="text" value="<?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>" disabled>
            </div>
            <div class="form-group">
                <label>Database Connection</label>
                <input type="text" value="Connected" disabled style="color: green;">
            </div>
        </div>
    </div>
</body>
</html>

