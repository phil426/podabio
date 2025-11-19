<?php
/**
 * Admin Panel - Blog Management
 * PodaBio - Manage blog posts
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/index.php';

requireAdmin();

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$postId = (int)($_GET['id'] ?? $_POST['post_id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    switch ($action) {
        case 'create_post':
        case 'update_post':
            $title = sanitizeInput($_POST['title'] ?? '');
            $slug = sanitizeInput($_POST['slug'] ?? '');
            $content = $_POST['content'] ?? '';
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                    $published = isset($_POST['published']) ? 1 : 0;
                    $featuredImage = sanitizeInput($_POST['featured_image'] ?? '');
                    $currentUser = getCurrentUser();
                    $authorId = $currentUser['id'] ?? 1; // Default to admin user ID if not found
                    
                    if (empty($title) || empty($slug)) {
                        $error = 'Title and slug are required';
                    } else {
                        if ($action === 'create_post') {
                            executeQuery(
                                "INSERT INTO blog_posts (title, slug, content, category_id, author_id, published, featured_image) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                                [$title, $slug, $content, $categoryId, $authorId, $published, $featuredImage]
                            );
                            $message = 'Blog post created successfully';
                        } else {
                            executeQuery(
                                "UPDATE blog_posts SET title = ?, slug = ?, content = ?, category_id = ?, published = ?, featured_image = ? WHERE id = ?",
                                [$title, $slug, $content, $categoryId, $published, $featuredImage, $postId]
                            );
                            $message = 'Blog post updated successfully';
                        }
                redirect('/admin/blog.php' . ($postId ? '?id=' . $postId : '') . ($message ? '&success=' . urlencode($message) : ''));
                exit;
            }
            break;
            
        case 'delete_post':
            if ($postId) {
                executeQuery("DELETE FROM blog_posts WHERE id = ?", [$postId]);
                $message = 'Post deleted successfully';
                redirect('/admin/blog.php?success=' . urlencode($message));
                exit;
            }
            break;
    }
}

// Get posts list
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$totalPosts = (int)fetchOne("SELECT COUNT(*) as count FROM blog_posts")['count'];
$totalPages = ceil($totalPosts / $limit);

$posts = fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM blog_posts p 
     LEFT JOIN blog_categories c ON p.category_id = c.id 
     ORDER BY p.created_at DESC 
     LIMIT $limit OFFSET $offset"
);

// Get single post for editing
$post = null;
if ($postId) {
    $post = fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
}

// Get categories
$categories = fetchAll("SELECT * FROM blog_categories ORDER BY name ASC");

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Admin - <?php echo h(APP_NAME); ?></title>
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
        .form-group textarea { min-height: 300px; font-family: inherit; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        table th { font-weight: 600; background: #f9fafb; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.875rem; display: inline-block; border: none; cursor: pointer; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
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
                <a href="/admin/blog.php" class="active">Blog</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/admin/select-panel.php">Switch Panel</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="color: #1f2937;">Blog Management</h2>
            <a href="/admin/blog.php?action=create" class="btn btn-primary">Create New Post</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 1.5rem;">
                <?php echo h($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'create' || ($action === 'edit' && $post)): ?>
            <!-- Create/Edit Form -->
            <div class="section">
                <h2><?php echo $action === 'create' ? 'Create New Post' : 'Edit Post'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_post' : 'update_post'; ?>">
                    <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                    
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" value="<?php echo h($post['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Slug (URL-friendly)</label>
                        <input type="text" name="slug" value="<?php echo h($post['slug'] ?? ''); ?>" required pattern="[a-z0-9-]+">
                        <small style="color: #6b7280;">e.g., my-blog-post-title</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($post && $post['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" required><?php echo h($post['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Featured Image URL</label>
                        <input type="url" name="featured_image" value="<?php echo h($post['featured_image'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="published" value="1" <?php echo ($post && $post['published']) ? 'checked' : ''; ?>>
                            Published
                        </label>
                    </div>
                    
                    
                    <button type="submit" class="btn btn-primary">Save Post</button>
                    <a href="/admin/blog.php" class="btn" style="background: #6b7280; color: white;">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <!-- Posts List -->
            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td><?php echo h($p['title']); ?></td>
                                <td><?php echo h($p['category_name'] ?? 'None'); ?></td>
                                <td>
                                    <?php if ($p['published']): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h(formatDate($p['created_at'], 'M j, Y')); ?></td>
                                <td><?php echo number_format($p['view_count'] ?? 0); ?></td>
                                <td>
                                    <a href="/admin/blog.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                    <a href="/blog/post.php?slug=<?php echo h($p['slug']); ?>" target="_blank" class="btn btn-small" style="background: #6b7280; color: white;">View</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this post?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

