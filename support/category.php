<?php
/**
 * Support Category View
 * PodaBio - Display articles in a category
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$slug = sanitizeInput($_GET['slug'] ?? '');

if (empty($slug)) {
    redirect('/support/');
    exit;
}

$category = fetchOne("SELECT * FROM support_categories WHERE slug = ?", [$slug]);

if (!$category) {
    http_response_code(404);
    die('Category not found');
}

$articles = fetchAll(
    "SELECT * FROM support_articles 
     WHERE category_id = ? AND published = 1 
     ORDER BY display_order ASC, created_at DESC",
    [$category['id']]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($category['name']); ?> - Support - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
        .header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 2rem; }
        .nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #667eea; text-decoration: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; color: #1f2937; }
        .page-header p { color: #6b7280; font-size: 1.1rem; }
        .articles-list { list-style: none; }
        .articles-list li { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .articles-list a { text-decoration: none; color: inherit; display: block; }
        .articles-list h3 { font-size: 1.25rem; margin-bottom: 0.5rem; color: #1f2937; }
        .articles-list p { color: #6b7280; }
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
        <div class="page-header">
            <h1><?php echo h($category['name']); ?></h1>
            <p><?php echo h($category['description'] ?? ''); ?></p>
        </div>
        
        <ul class="articles-list">
            <?php foreach ($articles as $article): ?>
                <li>
                    <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                        <h3><?php echo h($article['title']); ?></h3>
                        <p><?php echo h(truncate($article['content'], 150)); ?></p>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>

