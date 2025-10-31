<?php
/**
 * Blog Post View
 * Podn.Bio - Display blog post
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$slug = sanitizeInput($_GET['slug'] ?? '');

if (empty($slug)) {
    http_response_code(404);
    die('Post not found');
}

$post = fetchOne(
    "SELECT p.*, c.name as category_name, c.slug as category_slug 
     FROM blog_posts p 
     LEFT JOIN blog_categories c ON p.category_id = c.id 
     WHERE p.slug = ? AND p.published = 1",
    [$slug]
);

if (!$post) {
    http_response_code(404);
    die('Post not found');
}

// Increment view count
executeQuery("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?", [$post['id']]);

// Get related posts
$relatedPosts = fetchAll(
    "SELECT * FROM blog_posts 
     WHERE category_id = ? AND id != ? AND published = 1 
     ORDER BY created_at DESC 
     LIMIT 5",
    [$post['category_id'], $post['id']]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($post['title']); ?> - Blog - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
        .header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 2rem; }
        .nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #667eea; text-decoration: none; }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .post { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .post-header { margin-bottom: 2rem; }
        .post-breadcrumb { color: #6b7280; margin-bottom: 1rem; }
        .post-breadcrumb a { color: #667eea; text-decoration: none; }
        .post h1 { font-size: 2.5rem; margin-bottom: 1rem; color: #1f2937; }
        .post-meta { color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem; }
        .post-content { font-size: 1.1rem; line-height: 1.8; color: #374151; }
        .post-content h2 { margin-top: 2rem; margin-bottom: 1rem; color: #1f2937; }
        .post-content p { margin-bottom: 1rem; }
        .related { margin-top: 3rem; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <a href="/blog/">← Back to Blog</a>
        </nav>
    </header>
    
    <div class="container">
        <article class="post">
            <div class="post-header">
                <div class="post-breadcrumb">
                    <a href="/blog/">Blog</a>
                    <?php if ($post['category_name']): ?>
                        → <a href="/blog/?category=<?php echo h($post['category_slug']); ?>"><?php echo h($post['category_name']); ?></a>
                    <?php endif; ?>
                </div>
                <h1><?php echo h($post['title']); ?></h1>
                <div class="post-meta">
                    <?php if ($post['category_name']): ?>
                        <span><?php echo h($post['category_name']); ?></span> • 
                    <?php endif; ?>
                    <span><?php echo h(formatDate($post['created_at'], 'F j, Y')); ?></span>
                </div>
            </div>
            
            <?php if ($post['featured_image']): ?>
                <img src="<?php echo h($post['featured_image']); ?>" alt="<?php echo h($post['title']); ?>" style="width: 100%; border-radius: 8px; margin-bottom: 2rem;">
            <?php endif; ?>
            
            <div class="post-content">
                <?php echo nl2br(h($post['content'])); ?>
            </div>
        </article>
        
        <?php if (!empty($relatedPosts)): ?>
            <div class="related">
                <h2 style="margin-bottom: 1.5rem;">Related Posts</h2>
                <ul style="list-style: none;">
                    <?php foreach ($relatedPosts as $related): ?>
                        <li style="margin-bottom: 1rem;">
                            <a href="/blog/post.php?slug=<?php echo h($related['slug']); ?>" style="color: #667eea; text-decoration: none; font-weight: 500;">
                                <?php echo h($related['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

