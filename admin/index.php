<?php
/**
 * Admin Panel - Dashboard
 * PodaBio - Admin main dashboard
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

// Check if user is admin
function isAdmin() {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Check if user email is admin (or use is_admin field if it exists)
    $adminEmails = ['phil@redwoodempiremedia.com', 'cursor@poda.bio', 'phil624@gmail.com']; // Add admin emails here
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

// Get dashboard stats
$stats = [
    'total_users' => (int)fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_pages' => (int)fetchOne("SELECT COUNT(*) as count FROM pages")['count'],
    'active_pages' => (int)fetchOne("SELECT COUNT(*) as count FROM pages WHERE is_active = 1")['count'],
    'total_links' => (int)fetchOne("SELECT COUNT(*) as count FROM links")['count'] ?? 0,
    'total_subscriptions' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")['count'] ?? 0,
    'total_page_views' => (int)fetchOne("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'view'")['count'] ?? 0,
    'total_link_clicks' => (int)fetchOne("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'click'")['count'] ?? 0,
];

// Recent activity
$recentUsers = fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$recentPages = fetchAll("SELECT p.*, u.email FROM pages p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10");

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .admin-header {
            background: #1f2937;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            color: #667eea;
        }
        
        .admin-nav {
            display: flex;
            gap: 1rem;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: #374151;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table th {
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
        }
        
        table tr:hover {
            background: #f9fafb;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <h1>Admin Panel - <?php echo h(APP_NAME); ?></h1>
            <nav class="admin-nav">
                <a href="/admin/" class="active">Dashboard</a>
                <a href="/admin/users.php">Users</a>
                <a href="/admin/pages.php">Pages</a>
                <a href="/admin/subscriptions.php">Subscriptions</a>
                <a href="/admin/analytics.php">Analytics</a>
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php">Support</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/admin/select-panel.php">Switch Panel</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <h2 style="margin-bottom: 2rem; color: #1f2937;">Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?php echo number_format($stats['total_users']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Pages</h3>
                <div class="value"><?php echo number_format($stats['total_pages']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Active Pages</h3>
                <div class="value"><?php echo number_format($stats['active_pages']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Links</h3>
                <div class="value"><?php echo number_format($stats['total_links']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Active Subscriptions</h3>
                <div class="value"><?php echo number_format($stats['total_subscriptions']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Page Views</h3>
                <div class="value"><?php echo number_format($stats['total_page_views']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Link Clicks</h3>
                <div class="value"><?php echo number_format($stats['total_link_clicks']); ?></div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem;">
            <div class="section">
                <h2>Recent Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Verified</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo h($user['email']); ?></td>
                                <td>
                                    <?php if ($user['email_verified']): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h(formatDate($user['created_at'], 'M j, Y')); ?></td>
                                <td>
                                    <a href="/admin/users.php?id=<?php echo $user['id']; ?>" class="btn btn-small btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Recent Pages</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPages as $page): ?>
                            <tr>
                                <td><?php echo h($page['username']); ?></td>
                                <td><?php echo h($page['email']); ?></td>
                                <td>
                                    <?php if ($page['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h(formatDate($page['created_at'], 'M j, Y')); ?></td>
                                <td>
                                    <a href="/admin/pages.php?id=<?php echo $page['id']; ?>" class="btn btn-small btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

