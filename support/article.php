<?php
/**
 * Support Article View
 * Podn.Bio - Display support article
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$slug = sanitizeInput($_GET['slug'] ?? '');

if (empty($slug)) {
    http_response_code(404);
    die('Article not found');
}

$article = fetchOne(
    "SELECT a.*, c.name as category_name, c.slug as category_slug 
     FROM support_articles a 
     LEFT JOIN support_categories c ON a.category_id = c.id 
     WHERE a.slug = ? AND a.published = 1",
    [$slug]
);

if (!$article) {
    http_response_code(404);
    die('Article not found');
}

// Increment view count
executeQuery("UPDATE support_articles SET view_count = view_count + 1 WHERE id = ?", [$article['id']]);

// Get related articles in same category
$relatedArticles = fetchAll(
    "SELECT * FROM support_articles 
     WHERE category_id = ? AND id != ? AND published = 1 
     ORDER BY created_at DESC 
     LIMIT 5",
    [$article['category_id'], $article['id']]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($article['title']); ?> - Support - <?php echo h(APP_NAME); ?></title>
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
        .article { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .article-header { margin-bottom: 2rem; }
        .article-breadcrumb { color: #6b7280; margin-bottom: 1rem; }
        .article-breadcrumb a { color: #667eea; text-decoration: none; }
        .article h1 { font-size: 2.5rem; margin-bottom: 1rem; color: #1f2937; }
        .article-meta { color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem; }
        .article-content { font-size: 1.1rem; line-height: 1.8; color: #374151; }
        .article-content h2 { margin-top: 2rem; margin-bottom: 1rem; color: #1f2937; }
        .article-content p { margin-bottom: 1rem; }
        .article-content ul, .article-content ol { margin-left: 2rem; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin-top: 2rem; }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <a href="/support/">← Back to Support</a>
        </nav>
    </header>
    
    <div class="container">
        <article class="article">
            <div class="article-header">
                <div class="article-breadcrumb">
                    <a href="/support/">Support</a>
                    <?php if ($article['category_name']): ?>
                        → <a href="/support/category.php?slug=<?php echo h($article['category_slug']); ?>"><?php echo h($article['category_name']); ?></a>
                    <?php endif; ?>
                </div>
                <h1><?php echo h($article['title']); ?></h1>
                <div class="article-meta">
                    <?php if ($article['category_name']): ?>
                        <span><?php echo h($article['category_name']); ?></span> • 
                    <?php endif; ?>
                    <span>Updated <?php echo h(formatDate($article['updated_at'] ?? $article['created_at'], 'F j, Y')); ?></span>
                </div>
            </div>
            
            <div class="article-content">
                <?php echo nl2br(h($article['content'])); ?>
            </div>
            
            <?php if (!empty($article['tags'])): ?>
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <strong>Tags:</strong> <?php echo h($article['tags']); ?>
                </div>
            <?php endif; ?>
        </article>
        
        <?php if (!empty($relatedArticles)): ?>
            <div style="margin-top: 3rem; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2 style="margin-bottom: 1.5rem;">Related Articles</h2>
                <ul style="list-style: none;">
                    <?php foreach ($relatedArticles as $related): ?>
                        <li style="margin-bottom: 1rem;">
                            <a href="/support/article.php?slug=<?php echo h($related['slug']); ?>" style="color: #667eea; text-decoration: none; font-weight: 500;">
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

