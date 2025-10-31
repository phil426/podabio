<?php
/**
 * Admin Panel - User Management
 * Podn.Bio - Manage users
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
$userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    switch ($action) {
        case 'delete_user':
            if ($userId) {
                // Delete user (cascade will handle related records)
                executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
                $message = 'User deleted successfully';
            }
            break;
            
        case 'verify_email':
            if ($userId) {
                executeQuery("UPDATE users SET email_verified = 1 WHERE id = ?", [$userId]);
                $message = 'Email verified successfully';
            }
            break;
            
        case 'unverify_email':
            if ($userId) {
                executeQuery("UPDATE users SET email_verified = 0 WHERE id = ?", [$userId]);
                $message = 'Email verification removed';
            }
            break;
    }
    
    if ($message || $error) {
        redirect('/admin/users.php' . ($userId ? '?id=' . $userId : '') . ($message ? '&success=' . urlencode($message) : '') . ($error ? '&error=' . urlencode($error) : ''));
        exit;
    }
}

// Get users list or single user
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$search = sanitizeInput($_GET['search'] ?? '');
$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE email LIKE ?";
    $params[] = '%' . $search . '%';
}

$totalUsers = (int)fetchOne("SELECT COUNT(*) as count FROM users $whereClause", $params)['count'];
$totalPages = ceil($totalUsers / $limit);

$users = fetchAll("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);

// Get single user details if ID provided
$userDetails = null;
if ($userId) {
    $userDetails = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if ($userDetails) {
        $userPages = fetchAll("SELECT * FROM pages WHERE user_id = ?", [$userId]);
        $userSubscriptions = fetchAll("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin - <?php echo h(APP_NAME); ?></title>
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
        
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
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
        
        .pagination {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
            justify-content: center;
        }
        
        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
        }
        
        .pagination a:hover {
            background: #f9fafb;
        }
        
        .pagination a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
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
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .detail-item label {
            font-size: 0.875rem;
            color: #6b7280;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .detail-item .value {
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <h1>Admin Panel - <?php echo h(APP_NAME); ?></h1>
            <nav class="admin-nav">
                <a href="/admin/">Dashboard</a>
                <a href="/admin/users.php" class="active">Users</a>
                <a href="/admin/pages.php">Pages</a>
                <a href="/admin/subscriptions.php">Subscriptions</a>
                <a href="/admin/analytics.php">Analytics</a>
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php">Support</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/dashboard.php">User Dashboard</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <h2 style="margin-bottom: 2rem; color: #1f2937;">User Management</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo h($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo h($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if ($userDetails): ?>
            <!-- User Details View -->
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>User Details</h2>
                    <a href="/admin/users.php" class="btn btn-primary">Back to List</a>
                </div>
                
                <div class="user-details">
                    <div class="detail-item">
                        <label>User ID</label>
                        <div class="value"><?php echo $userDetails['id']; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email</label>
                        <div class="value"><?php echo h($userDetails['email']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Email Verified</label>
                        <div class="value">
                            <?php if ($userDetails['email_verified']): ?>
                                <span class="badge badge-success">Yes</span>
                            <?php else: ?>
                                <span class="badge badge-warning">No</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Has Password</label>
                        <div class="value"><?php echo $userDetails['password_hash'] ? 'Yes' : 'No'; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Has Google Account</label>
                        <div class="value"><?php echo $userDetails['google_id'] ? 'Yes' : 'No'; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Created At</label>
                        <div class="value"><?php echo h(formatDate($userDetails['created_at'], 'F j, Y g:i A')); ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Actions</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <?php if (!$userDetails['email_verified']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="verify_email">
                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                <button type="submit" class="btn btn-primary">Verify Email</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="unverify_email">
                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                <button type="submit" class="btn btn-secondary">Unverify Email</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                            <button type="submit" class="btn btn-danger">Delete User</button>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($userPages)): ?>
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem;">User Pages</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Podcast Name</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userPages as $page): ?>
                                    <tr>
                                        <td><?php echo h($page['username']); ?></td>
                                        <td><?php echo h($page['podcast_name'] ?? 'N/A'); ?></td>
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
                <?php endif; ?>
                
                <?php if (!empty($userSubscriptions)): ?>
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem;">User Subscriptions</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Started</th>
                                    <th>Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userSubscriptions as $sub): ?>
                                    <tr>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Users List View -->
            <div class="section">
                <div class="search-box">
                    <form method="GET">
                        <input type="text" name="search" placeholder="Search by email..." value="<?php echo h($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search): ?>
                            <a href="/admin/users.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
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
                        <?php foreach ($users as $user): ?>
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
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="/admin/users.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

