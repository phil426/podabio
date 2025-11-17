<?php
/**
 * Company Blog - Index
 * Podn.Bio - Blog posts listing
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Get pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get category filter
$categorySlug = sanitizeInput($_GET['category'] ?? '');
$whereClause = "WHERE p.published = 1";
$params = [];

if ($categorySlug) {
    $category = fetchOne("SELECT id FROM blog_categories WHERE slug = ?", [$categorySlug]);
    if ($category) {
        $whereClause .= " AND p.category_id = ?";
        $params[] = $category['id'];
    }
}

// Get total posts
$totalPosts = (int)fetchOne("SELECT COUNT(*) as count FROM blog_posts p $whereClause", $params)['count'];
$totalPages = ceil($totalPosts / $limit);

// Get posts
$posts = fetchAll(
    "SELECT p.*, c.name as category_name, c.slug as category_slug 
     FROM blog_posts p 
     LEFT JOIN blog_categories c ON p.category_id = c.id 
     $whereClause 
     ORDER BY p.created_at DESC 
     LIMIT $limit OFFSET $offset",
    $params
);

// Get categories
$categories = fetchAll("SELECT * FROM blog_categories ORDER BY display_order ASC, name ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
        .header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .nav { max-width: 1200px; margin: 0 auto; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #667eea; text-decoration: none; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 2rem; text-align: center; }
        .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 4rem 2rem; display: grid; grid-template-columns: 1fr 300px; gap: 3rem; }
        .posts-list { list-style: none; }
        .post-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .post-card a { text-decoration: none; color: inherit; }
        .post-card h2 { font-size: 1.75rem; margin-bottom: 1rem; color: #1f2937; }
        .post-meta { color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem; }
        .post-excerpt { color: #374151; margin-bottom: 1rem; }
        .sidebar { }
        .category-list { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .category-list h3 { margin-bottom: 1rem; color: #1f2937; }
        .category-list ul { list-style: none; }
        .category-list li { margin-bottom: 0.5rem; }
        .category-list a { color: #667eea; text-decoration: none; }
        .pagination { display: flex; gap: 0.5rem; justify-content: center; margin-top: 3rem; }
        .pagination a { padding: 0.75rem 1.5rem; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; }
        .pagination a.active { background: #667eea; color: white; border-color: #667eea; }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <a href="/">← Back to Home</a>
        </nav>
    </header>
    
    <div class="page-header">
        <h1><?php echo h(APP_NAME); ?> Blog</h1>
        <p>Latest news, updates, and tips for podcasters</p>
    </div>
    
    <div class="container">
        <main>
            <ul class="posts-list">
                <?php foreach ($posts as $post): ?>
                    <li class="post-card">
                        <a href="/blog/post.php?slug=<?php echo h($post['slug']); ?>">
                            <?php if ($post['category_name']): ?>
                                <div style="color: #667eea; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                    <?php echo h($post['category_name']); ?>
                                </div>
                            <?php endif; ?>
                            <h2><?php echo h($post['title']); ?></h2>
                            <div class="post-meta">
                                <?php echo h(formatDate($post['created_at'], 'F j, Y')); ?>
                            </div>
                            <div class="post-excerpt">
                                <?php echo h(truncate($post['content'], 200)); ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="/blog/?page=<?php echo $i; ?><?php echo $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <aside class="sidebar">
            <div class="category-list">
                <h3>Categories</h3>
                <ul>
                    <li><a href="/blog/">All Posts</a></li>
                    <?php foreach ($categories as $category): ?>
                        <li><a href="/blog/?category=<?php echo h($category['slug']); ?>"><?php echo h($category['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</body>
</html>

