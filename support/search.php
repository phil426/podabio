<?php
/**
 * Support Search
 * Podn.Bio - Search support articles
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$query = sanitizeInput($_GET['q'] ?? '');

$results = [];
if (!empty($query)) {
    $results = fetchAll(
        "SELECT a.*, c.name as category_name, c.slug as category_slug 
         FROM support_articles a 
         LEFT JOIN support_categories c ON a.category_id = c.id 
         WHERE a.published = 1 
         AND (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)
         ORDER BY a.created_at DESC",
        ['%' . $query . '%', '%' . $query . '%', '%' . $query . '%']
    );
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Support - <?php echo h(APP_NAME); ?></title>
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
        .search-box { margin-bottom: 2rem; }
        .search-box form { display: flex; max-width: 600px; }
        .search-box input { flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px 0 0 8px; font-size: 1rem; }
        .search-box button { padding: 1rem 2rem; background: #667eea; color: white; border: none; border-radius: 0 8px 8px 0; font-weight: 600; cursor: pointer; }
        .results-count { color: #6b7280; margin-bottom: 2rem; }
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
        <div class="search-box">
            <form method="GET">
                <input type="text" name="q" placeholder="Search for help..." value="<?php echo h($query); ?>" required>
                <button type="submit">Search</button>
            </form>
        </div>
        
        <?php if (!empty($query)): ?>
            <div class="results-count">
                Found <?php echo count($results); ?> result(s) for "<?php echo h($query); ?>"
            </div>
            
            <?php if (empty($results)): ?>
                <p>No results found. Try different keywords or <a href="/support/">browse all articles</a>.</p>
            <?php else: ?>
                <ul class="articles-list">
                    <?php foreach ($results as $article): ?>
                        <li>
                            <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                                <h3><?php echo h($article['title']); ?></h3>
                                <p><?php echo h(truncate($article['content'], 150)); ?></p>
                                <?php if ($article['category_name']): ?>
                                    <small style="color: #9ca3af;"><?php echo h($article['category_name']); ?></small>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

