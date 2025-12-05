<?php
/**
 * Support Article Single Page
 * PodaBio - Display individual support/help article
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/SupportArticle.php';
require_once __DIR__ . '/classes/SupportCategory.php';

$appName = defined('APP_NAME') ? APP_NAME : 'PodaBio';
$appUrl = defined('APP_URL') ? APP_URL : 'https://poda.bio';

// Get article by slug
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /support.php');
    exit;
}

$article = SupportArticle::getBySlug($slug, true);

if (!$article) {
    http_response_code(404);
    $pageTitle = "Article Not Found - " . $appName;
    $notFound = true;
} else {
    // Increment view count
    SupportArticle::incrementViewCount($article['id']);
    $pageTitle = h($article['title']) . " - Help Center - " . $appName;
    $notFound = false;
}

// Get related articles from same category
$relatedArticles = [];
if (!$notFound && !empty($article['category_id'])) {
    $categoryArticles = SupportArticle::getPublished($article['category_id'], 6, 0);
    $relatedArticles = array_filter($categoryArticles, function($a) use ($article) {
        return $a['id'] !== $article['id'];
    });
    $relatedArticles = array_slice($relatedArticles, 0, 3);
}

// Get categories for sidebar
$categories = SupportCategory::getWithArticles();

// Simple Markdown-like parsing
function parseMarkdown($text) {
    // Headers
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // Bold and Italic
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    
    // Links
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
    
    // Code blocks
    $text = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $text);
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    
    // Lists
    $text = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.+<\/li>)/s', '<ul>$1</ul>', $text);
    $text = preg_replace('/<\/ul>\s*<ul>/', '', $text);
    
    // Paragraphs
    $text = '<p>' . preg_replace('/\n\n+/', '</p><p>', trim($text)) . '</p>';
    
    // Clean up
    $text = str_replace('<p></p>', '', $text);
    $text = preg_replace('/<p>(<h[123]>)/s', '$1', $text);
    $text = preg_replace('/(<\/h[123]>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<ul>)/s', '$1', $text);
    $text = preg_replace('/(<\/ul>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<pre>)/s', '$1', $text);
    $text = preg_replace('/(<\/pre>)<\/p>/s', '$1', $text);
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php if (!$notFound): ?>
    <meta name="description" content="<?= h(substr(strip_tags($article['content']), 0, 160)) ?>">
    <link rel="canonical" href="<?= h($appUrl) ?>/support-article.php?slug=<?= h($article['slug']) ?>">
    <?php endif; ?>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-elevated: #1a1a24;
            --text-primary: #ffffff;
            --text-secondary: #a0a0b0;
            --text-tertiary: #6b6b7a;
            --accent-primary: #8b5cf6;
            --accent-secondary: #a78bfa;
            --border-color: #2a2a3a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Space Grotesk', system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.7;
            min-height: 100vh;
        }
        
        a {
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 0;
        }
        
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .logo:hover {
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 24px;
        }
        
        .nav-links a {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .nav-links a:hover {
            color: var(--text-primary);
            text-decoration: none;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            padding: 20px 0;
            font-size: 14px;
            color: var(--text-tertiary);
        }
        
        .breadcrumb a {
            color: var(--text-secondary);
        }
        
        .breadcrumb span {
            margin: 0 8px;
        }
        
        /* Content */
        .content {
            padding: 20px 0 60px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 40px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
        }
        
        /* Article */
        .article-header {
            margin-bottom: 32px;
        }
        
        .article-header h1 {
            font-size: 36px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 16px;
        }
        
        .article-meta {
            display: flex;
            gap: 16px;
            color: var(--text-tertiary);
            font-size: 14px;
        }
        
        .category-badge {
            padding: 4px 12px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
            border-radius: 6px;
        }
        
        .article-content {
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
        }
        
        .article-content h1,
        .article-content h2,
        .article-content h3 {
            margin: 32px 0 16px;
            color: var(--text-primary);
        }
        
        .article-content h1:first-child,
        .article-content h2:first-child,
        .article-content h3:first-child {
            margin-top: 0;
        }
        
        .article-content h2 {
            font-size: 24px;
        }
        
        .article-content h3 {
            font-size: 20px;
        }
        
        .article-content p {
            margin-bottom: 16px;
            color: var(--text-secondary);
        }
        
        .article-content ul,
        .article-content ol {
            margin: 16px 0;
            padding-left: 24px;
            color: var(--text-secondary);
        }
        
        .article-content li {
            margin-bottom: 8px;
        }
        
        .article-content code {
            padding: 2px 6px;
            background: var(--bg-secondary);
            border-radius: 4px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.9em;
            color: var(--accent-secondary);
        }
        
        .article-content pre {
            margin: 16px 0;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .article-content pre code {
            padding: 0;
            background: none;
            color: var(--text-primary);
        }
        
        .article-content strong {
            color: var(--text-primary);
        }
        
        /* Sidebar */
        .sidebar h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        
        .sidebar-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-list li {
            margin-bottom: 8px;
        }
        
        .category-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: var(--bg-secondary);
            border-radius: 6px;
            color: var(--text-primary);
            transition: all 0.2s;
        }
        
        .category-list a:hover {
            background: rgba(139, 92, 246, 0.1);
            text-decoration: none;
        }
        
        .category-count {
            font-size: 12px;
            color: var(--text-tertiary);
        }
        
        .related-list {
            list-style: none;
        }
        
        .related-list li {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .related-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .related-list a {
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .related-list a:hover {
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        /* Not Found */
        .not-found {
            text-align: center;
            padding: 80px 20px;
        }
        
        .not-found h1 {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .not-found p {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .back-button {
            display: inline-block;
            padding: 12px 24px;
            background: var(--accent-primary);
            color: white;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .back-button:hover {
            opacity: 0.9;
            text-decoration: none;
        }
        
        /* Footer */
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 40px 0;
            text-align: center;
        }
        
        .footer p {
            color: var(--text-tertiary);
            font-size: 14px;
        }
        
        .footer-links {
            margin-top: 16px;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            margin: 0 12px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="/" class="logo"><?= h($appName) ?></a>
            <nav class="nav-links">
                <a href="/">Home</a>
                <a href="/support.php">Help Center</a>
                <a href="/login.php">Login</a>
            </nav>
        </div>
    </header>
    
    <?php if ($notFound): ?>
        <main class="not-found">
            <h1>404</h1>
            <p>Sorry, we couldn't find that article.</p>
            <a href="/support.php" class="back-button">Browse Help Center</a>
        </main>
    <?php else: ?>
        <div class="breadcrumb">
            <div class="container">
                <a href="/support.php">Help Center</a>
                <?php if (!empty($article['category_name'])): ?>
                    <span>›</span>
                    <a href="/support.php?category=<?= h($article['category_slug']) ?>"><?= h($article['category_name']) ?></a>
                <?php endif; ?>
                <span>›</span>
                <span style="color: var(--text-primary);"><?= h($article['title']) ?></span>
            </div>
        </div>
        
        <main class="content">
            <div class="container">
                <div class="content-grid">
                    <article>
                        <div class="article-header">
                            <h1><?= h($article['title']) ?></h1>
                            <div class="article-meta">
                                <?php if (!empty($article['category_name'])): ?>
                                    <span class="category-badge"><?= h($article['category_name']) ?></span>
                                <?php endif; ?>
                                <span>Last updated <?= date('M j, Y', strtotime($article['updated_at'])) ?></span>
                                <span><?= h($article['view_count']) ?> views</span>
                            </div>
                        </div>
                        
                        <div class="article-content">
                            <?= parseMarkdown($article['content']) ?>
                        </div>
                    </article>
                    
                    <aside class="sidebar">
                        <?php if (!empty($relatedArticles)): ?>
                            <div class="sidebar-card">
                                <h3>Related Articles</h3>
                                <ul class="related-list">
                                    <?php foreach ($relatedArticles as $related): ?>
                                        <li>
                                            <a href="support-article.php?slug=<?= h($related['slug']) ?>">
                                                <?= h($related['title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sidebar-card">
                            <h3>Categories</h3>
                            <ul class="category-list">
                                <?php foreach ($categories as $cat): ?>
                                    <li>
                                        <a href="/support.php?category=<?= h($cat['slug']) ?>">
                                            <?= h($cat['name']) ?>
                                            <span class="category-count"><?= h($cat['article_count']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    <?php endif; ?>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= h($appName) ?>. All rights reserved.</p>
            <div class="footer-links">
                <a href="/privacy.php">Privacy Policy</a>
                <a href="/terms.php">Terms of Service</a>
                <a href="mailto:support@poda.bio">Contact</a>
            </div>
        </div>
    </footer>
</body>
</html>


