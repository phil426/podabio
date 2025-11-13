<?php
/**
 * Admin Panel - Support Articles Management
 * Podn.Bio - Manage support/knowledge base articles
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
$articleId = (int)($_GET['id'] ?? $_POST['article_id'] ?? 0);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    switch ($action) {
        case 'create_article':
        case 'update_article':
            $title = sanitizeInput($_POST['title'] ?? '');
            $slug = sanitizeInput($_POST['slug'] ?? '');
            $content = $_POST['content'] ?? '';
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $published = isset($_POST['published']) ? 1 : 0;
            $tags = sanitizeInput($_POST['tags'] ?? '');
            
            if (empty($title) || empty($slug)) {
                $error = 'Title and slug are required';
            } else {
                if ($action === 'create_article') {
                    executeQuery(
                        "INSERT INTO support_articles (title, slug, content, category_id, published, tags) 
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [$title, $slug, $content, $categoryId, $published, $tags]
                    );
                    $message = 'Article created successfully';
                } else {
                    executeQuery(
                        "UPDATE support_articles SET title = ?, slug = ?, content = ?, category_id = ?, published = ?, tags = ? WHERE id = ?",
                        [$title, $slug, $content, $categoryId, $published, $tags, $articleId]
                    );
                    $message = 'Article updated successfully';
                }
                redirect('/admin/support.php' . ($articleId ? '?id=' . $articleId : '') . ($message ? '&success=' . urlencode($message) : ''));
                exit;
            }
            break;
            
        case 'delete_article':
            if ($articleId) {
                executeQuery("DELETE FROM support_articles WHERE id = ?", [$articleId]);
                $message = 'Article deleted';
                redirect('/admin/support.php?success=' . urlencode($message));
                exit;
            }
            break;
    }
}

// Get articles
$articles = fetchAll(
    "SELECT a.*, c.name as category_name 
     FROM support_articles a 
     LEFT JOIN support_categories c ON a.category_id = c.id 
     ORDER BY a.created_at DESC"
);

// Get single article for editing
$article = null;
if ($articleId) {
    $article = fetchOne("SELECT * FROM support_articles WHERE id = ?", [$articleId]);
}

// Get categories
$categories = fetchAll("SELECT * FROM support_categories ORDER BY name ASC");

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Management - Admin - <?php echo h(APP_NAME); ?></title>
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
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php" class="active">Support</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/admin/react-admin.php">PodaBio Studio</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="color: #1f2937;">Support Articles Management</h2>
            <a href="/admin/support.php?action=create" class="btn btn-primary">Create New Article</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 1.5rem;">
                <?php echo h($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'create' || ($action === 'edit' && $article)): ?>
            <!-- Create/Edit Form -->
            <div class="section">
                <h2><?php echo $action === 'create' ? 'Create New Article' : 'Edit Article'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_article' : 'update_article'; ?>">
                    <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
                    
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" value="<?php echo h($article['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Slug (URL-friendly)</label>
                        <input type="text" name="slug" value="<?php echo h($article['slug'] ?? ''); ?>" required pattern="[a-z0-9-]+">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($article && $article['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" required><?php echo h($article['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Tags (comma-separated)</label>
                        <input type="text" name="tags" value="<?php echo h($article['tags'] ?? ''); ?>" placeholder="tag1, tag2, tag3">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="published" value="1" <?php echo ($article && $article['published']) ? 'checked' : ''; ?>>
                            Published
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Article</button>
                    <a href="/admin/support.php" class="btn" style="background: #6b7280; color: white;">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <!-- Articles List -->
            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $art): ?>
                            <tr>
                                <td><?php echo h($art['title']); ?></td>
                                <td><?php echo h($art['category_name'] ?? 'None'); ?></td>
                                <td>
                                    <?php if ($art['published']): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($art['view_count'] ?? 0); ?></td>
                                <td>
                                    <a href="/admin/support.php?action=edit&id=<?php echo $art['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                    <a href="/support/article.php?slug=<?php echo h($art['slug']); ?>" target="_blank" class="btn btn-small" style="background: #6b7280; color: white;">View</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this article?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete_article">
                                        <input type="hidden" name="article_id" value="<?php echo $art['id']; ?>">
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

