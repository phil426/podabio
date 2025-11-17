<?php
/**
 * Admin Panel - Subscription Management
 * Podn.Bio - Manage subscriptions
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/index.php'; // For requireAdmin function

requireAdmin();

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$subscriptionId = (int)($_GET['id'] ?? $_POST['subscription_id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    switch ($action) {
        case 'cancel_subscription':
            if ($subscriptionId) {
                executeQuery("UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$subscriptionId]);
                $message = 'Subscription cancelled successfully';
            }
            break;
            
        case 'activate_subscription':
            if ($subscriptionId) {
                executeQuery("UPDATE subscriptions SET status = 'active', updated_at = NOW() WHERE id = ?", [$subscriptionId]);
                $message = 'Subscription activated successfully';
            }
            break;
            
        case 'verify_venmo':
            if ($subscriptionId) {
                // Activate pending Venmo subscription
                executeQuery("UPDATE subscriptions SET status = 'active', updated_at = NOW() WHERE id = ? AND status = 'pending'", [$subscriptionId]);
                $message = 'Venmo payment verified and subscription activated';
            }
            break;
    }
    
    if ($message || $error) {
        redirect('/admin/subscriptions.php' . ($subscriptionId ? '?id=' . $subscriptionId : '') . ($message ? '&success=' . urlencode($message) : '') . ($error ? '&error=' . urlencode($error) : ''));
        exit;
    }
}

// Get subscriptions list
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$filter = sanitizeInput($_GET['filter'] ?? 'all');
$whereClause = '';
$params = [];

if ($filter === 'active') {
    $whereClause = "WHERE s.status = 'active'";
} elseif ($filter === 'pending') {
    $whereClause = "WHERE s.status = 'pending'";
} elseif ($filter === 'cancelled') {
    $whereClause = "WHERE s.status = 'cancelled'";
}

$totalSubscriptions = (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions s JOIN users u ON s.user_id = u.id $whereClause", $params)['count'];
$totalPages = ceil($totalSubscriptions / $limit);

$subscriptions = fetchAll(
    "SELECT s.*, u.email 
     FROM subscriptions s 
     JOIN users u ON s.user_id = u.id 
     $whereClause 
     ORDER BY s.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

// Subscription stats
$stats = [
    'total' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions")['count'],
    'active' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")['count'],
    'pending' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'pending'")['count'],
    'cancelled' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'cancelled'")['count'],
    'premium' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE plan_type = 'premium' AND status = 'active'")['count'],
    'pro' => (int)fetchOne("SELECT COUNT(*) as count FROM subscriptions WHERE plan_type = 'pro' AND status = 'active'")['count'],
];

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - Admin - <?php echo h(APP_NAME); ?></title>
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tabs a {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            background: #f3f4f6;
            color: #374151;
            transition: all 0.3s;
        }
        
        .filter-tabs a:hover {
            background: #e5e7eb;
        }
        
        .filter-tabs a.active {
            background: #667eea;
            color: white;
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
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-error {
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
                <a href="/admin/">Dashboard</a>
                <a href="/admin/users.php">Users</a>
                <a href="/admin/pages.php">Pages</a>
                <a href="/admin/subscriptions.php" class="active">Subscriptions</a>
                <a href="/admin/analytics.php">Analytics</a>
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php">Support</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/admin/react-admin.php">PodaBio Studio</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <h2 style="margin-bottom: 2rem; color: #1f2937;">Subscription Management</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo h($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo h($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total</h3>
                <div class="value"><?php echo number_format($stats['total']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Active</h3>
                <div class="value"><?php echo number_format($stats['active']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Cancelled</h3>
                <div class="value"><?php echo number_format($stats['cancelled']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Premium</h3>
                <div class="value"><?php echo number_format($stats['premium']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pro</h3>
                <div class="value"><?php echo number_format($stats['pro']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="filter-tabs">
                <a href="/admin/subscriptions.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="/admin/subscriptions.php?filter=active" class="<?php echo $filter === 'active' ? 'active' : ''; ?>">Active</a>
                <a href="/admin/subscriptions.php?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="/admin/subscriptions.php?filter=cancelled" class="<?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Started</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?php echo $sub['id']; ?></td>
                            <td><?php echo h($sub['email']); ?></td>
                            <td><?php echo ucfirst($sub['plan_type']); ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'active' => 'badge-success',
                                    'pending' => 'badge-warning',
                                    'cancelled' => 'badge-danger',
                                    'expired' => 'badge-danger'
                                ];
                                $statusColor = $statusColors[$sub['status']] ?? 'badge-warning';
                                ?>
                                <span class="badge <?php echo $statusColor; ?>"><?php echo ucfirst($sub['status']); ?></span>
                            </td>
                            <td><?php echo $sub['payment_method'] ? ucfirst($sub['payment_method']) : 'N/A'; ?></td>
                            <td><?php echo h(formatDate($sub['started_at'] ?? $sub['created_at'], 'M j, Y')); ?></td>
                            <td><?php echo $sub['expires_at'] ? h(formatDate($sub['expires_at'], 'M j, Y')) : 'Never'; ?></td>
                            <td>
                                <?php if ($sub['status'] === 'pending' && $sub['payment_method'] === 'venmo'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="verify_venmo">
                                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-primary">Verify Payment</button>
                                    </form>
                                <?php elseif ($sub['status'] === 'active'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="cancel_subscription">
                                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-secondary">Cancel</button>
                                    </form>
                                <?php elseif ($sub['status'] === 'cancelled'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="activate_subscription">
                                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-primary">Reactivate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; justify-content: center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="/admin/subscriptions.php?page=<?php echo $i; ?><?php echo $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>" 
                           style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; <?php echo $i == $page ? 'background: #667eea; color: white; border-color: #667eea;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

