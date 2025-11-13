<?php
/**
 * Admin Panel - Page Management
 * Podn.Bio - Manage user pages
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
$pageId = (int)($_GET['id'] ?? $_POST['page_id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    switch ($action) {
        case 'activate_page':
            if ($pageId) {
                executeQuery("UPDATE pages SET is_active = 1 WHERE id = ?", [$pageId]);
                $message = 'Page activated successfully';
            }
            break;
            
        case 'deactivate_page':
            if ($pageId) {
                executeQuery("UPDATE pages SET is_active = 0 WHERE id = ?", [$pageId]);
                $message = 'Page deactivated successfully';
            }
            break;
            
        case 'delete_page':
            if ($pageId) {
                executeQuery("DELETE FROM pages WHERE id = ?", [$pageId]);
                $message = 'Page deleted successfully';
                redirect('/admin/pages.php?success=' . urlencode($message));
                exit;
            }
            break;
    }
    
    if ($message || $error) {
        redirect('/admin/pages.php' . ($pageId ? '?id=' . $pageId : '') . ($message ? '&success=' . urlencode($message) : '') . ($error ? '&error=' . urlencode($error) : ''));
        exit;
    }
}

// Get pages list or single page
$pageNum = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($pageNum - 1) * $limit;

$search = sanitizeInput($_GET['search'] ?? '');
$whereClause = "WHERE 1=1";
$params = [];

if ($search) {
    $whereClause .= " AND (p.username LIKE ? OR p.podcast_name LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$totalPages = (int)fetchOne("SELECT COUNT(*) as count FROM pages p JOIN users u ON p.user_id = u.id $whereClause", $params)['count'];
$totalPagesNum = ceil($totalPages / $limit);

$pages = fetchAll(
    "SELECT p.*, u.email 
     FROM pages p 
     JOIN users u ON p.user_id = u.id 
     $whereClause 
     ORDER BY p.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

// Get single page details if ID provided
$pageDetails = null;
if ($pageId) {
    $pageDetails = fetchOne(
        "SELECT p.*, u.email 
         FROM pages p 
         JOIN users u ON p.user_id = u.id 
         WHERE p.id = ?",
        [$pageId]
    );
    if ($pageDetails) {
        $pageLinks = fetchAll("SELECT * FROM links WHERE page_id = ? ORDER BY display_order ASC", [$pageId]);
        $pageEpisodes = fetchAll("SELECT * FROM episodes WHERE page_id = ? ORDER BY pub_date DESC LIMIT 10", [$pageId]);
        $pageDirectories = fetchAll("SELECT * FROM social_icons WHERE page_id = ?", [$pageId]);
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Management - Admin - <?php echo h(APP_NAME); ?></title>
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
        
        .page-details {
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
            word-break: break-all;
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
                <a href="/admin/pages.php" class="active">Pages</a>
                <a href="/admin/subscriptions.php">Subscriptions</a>
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
        <h2 style="margin-bottom: 2rem; color: #1f2937;">Page Management</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo h($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo h($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if ($pageDetails): ?>
            <!-- Page Details View -->
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Page Details</h2>
                    <div style="display: flex; gap: 1rem;">
                        <a href="/<?php echo h($pageDetails['username']); ?>" target="_blank" class="btn btn-primary">View Page</a>
                        <a href="/admin/pages.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
                
                <div class="page-details">
                    <div class="detail-item">
                        <label>Page ID</label>
                        <div class="value"><?php echo $pageDetails['id']; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Username</label>
                        <div class="value"><?php echo h($pageDetails['username']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Owner Email</label>
                        <div class="value"><?php echo h($pageDetails['email']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Podcast Name</label>
                        <div class="value"><?php echo h($pageDetails['podcast_name'] ?? 'N/A'); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Custom Domain</label>
                        <div class="value"><?php echo h($pageDetails['custom_domain'] ?? 'None'); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Status</label>
                        <div class="value">
                            <?php if ($pageDetails['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <label>Created At</label>
                        <div class="value"><?php echo h(formatDate($pageDetails['created_at'], 'F j, Y g:i A')); ?></div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Actions</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <?php if ($pageDetails['is_active']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="deactivate_page">
                                <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
                                <button type="submit" class="btn btn-secondary">Deactivate</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="activate_page">
                                <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
                                <button type="submit" class="btn btn-primary">Activate</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete_page">
                            <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
                            <button type="submit" class="btn btn-danger">Delete Page</button>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($pageLinks)): ?>
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem;">Links (<?php echo count($pageLinks); ?>)</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>URL</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pageLinks as $link): ?>
                                    <tr>
                                        <td><?php echo h($link['title']); ?></td>
                                        <td><?php echo h($link['type']); ?></td>
                                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;"><?php echo h($link['url']); ?></td>
                                        <td><?php echo $link['display_order']; ?></td>
                                        <td>
                                            <?php if ($link['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($pageEpisodes)): ?>
                    <div style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 1rem;">Recent Episodes (<?php echo count($pageEpisodes); ?>)</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Published</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pageEpisodes as $episode): ?>
                                    <tr>
                                        <td><?php echo h($episode['title']); ?></td>
                                        <td><?php echo h(formatDate($episode['pub_date'] ?? $episode['created_at'], 'M j, Y')); ?></td>
                                        <td>
                                            <?php 
                                            if ($episode['duration']) {
                                                $hours = floor($episode['duration'] / 3600);
                                                $minutes = floor(($episode['duration'] % 3600) / 60);
                                                if ($hours > 0) {
                                                    echo sprintf('%d:%02d:%02d', $hours, $minutes, $episode['duration'] % 60);
                                                } else {
                                                    echo sprintf('%d:%02d', $minutes, $episode['duration'] % 60);
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Pages List View -->
            <div class="section">
                <div class="search-box">
                    <form method="GET">
                        <input type="text" name="search" placeholder="Search by username, podcast name, or email..." value="<?php echo h($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search): ?>
                            <a href="/admin/pages.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Podcast Name</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo $page['id']; ?></td>
                                <td><?php echo h($page['username']); ?></td>
                                <td><?php echo h($page['podcast_name'] ?? 'N/A'); ?></td>
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
                                    <a href="/<?php echo h($page['username']); ?>" target="_blank" class="btn btn-small btn-secondary">Visit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPagesNum > 1): ?>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; justify-content: center;">
                        <?php for ($i = 1; $i <= $totalPagesNum; $i++): ?>
                            <a href="/admin/pages.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; <?php echo $i == $pageNum ? 'background: #667eea; color: white; border-color: #667eea;' : ''; ?>">
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

