<?php
/**
 * Support / Help Center
 * PodaBio - Public-facing help documentation
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/SupportArticle.php';
require_once __DIR__ . '/classes/SupportCategory.php';

$appName = defined('APP_NAME') ? APP_NAME : 'PodaBio';
$appUrl = defined('APP_URL') ? APP_URL : 'https://poda.bio';

// Get categories and articles
$categories = SupportCategory::getWithArticles();
$popularArticles = SupportArticle::getPopular(5);
$recentArticles = SupportArticle::getRecent(5);

// Handle search
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchResults = [];
if (!empty($searchQuery)) {
    $searchResults = SupportArticle::search($searchQuery, true);
}

// Handle category filter
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$categoryArticles = [];
$selectedCategoryData = null;

if ($selectedCategory) {
    $selectedCategoryData = SupportCategory::getBySlug($selectedCategory);
    if ($selectedCategoryData) {
        $categoryArticles = SupportArticle::getPublished($selectedCategoryData['id'], 100, 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - <?= h($appName) ?></title>
    <meta name="description" content="Get help with <?= h($appName) ?>. Browse our documentation, FAQs, and support articles.">
    <link rel="canonical" href="<?= h($appUrl) ?>/support.php">
    
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
            --border-hover: #3a3a4a;
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
            line-height: 1.6;
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
        
        /* Hero */
        .hero {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            padding: 60px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .hero p {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        /* Search */
        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            font-family: inherit;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--accent-primary);
        }
        
        .search-input::placeholder {
            color: var(--text-tertiary);
        }
        
        /* Content */
        .content {
            padding: 48px 0;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
        
        .category-list {
            list-style: none;
            margin-bottom: 32px;
        }
        
        .category-list li {
            margin-bottom: 8px;
        }
        
        .category-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            transition: all 0.2s;
        }
        
        .category-list a:hover,
        .category-list a.active {
            border-color: var(--accent-primary);
            text-decoration: none;
        }
        
        .category-list a.active {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .category-count {
            font-size: 12px;
            color: var(--text-tertiary);
            background: var(--bg-secondary);
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        /* Main Content */
        .main-content h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .article-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .article-card {
            display: block;
            padding: 20px 24px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.2s;
        }
        
        .article-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .article-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .article-card p {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .article-meta {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-tertiary);
        }
        
        .category-badge {
            padding: 2px 8px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
            border-radius: 4px;
        }
        
        /* Search Results */
        .search-results-header {
            margin-bottom: 24px;
        }
        
        .search-results-header h2 {
            margin-bottom: 8px;
        }
        
        .search-results-header p {
            color: var(--text-secondary);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .no-results h3 {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        /* Popular/Recent Sections */
        .section-divider {
            margin: 32px 0;
            border: 0;
            border-top: 1px solid var(--border-color);
        }
        
        .compact-list {
            list-style: none;
        }
        
        .compact-list li {
            border-bottom: 1px solid var(--border-color);
        }
        
        .compact-list li:last-child {
            border-bottom: none;
        }
        
        .compact-list a {
            display: block;
            padding: 12px 0;
            color: var(--text-primary);
        }
        
        .compact-list a:hover {
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        /* Footer */
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 40px 0;
            margin-top: 60px;
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
                <a href="/blog.php">Blog</a>
                <a href="/login.php">Login</a>
            </nav>
        </div>
    </header>
    
    <section class="hero">
        <div class="container">
            <h1>How can we help?</h1>
            <p>Search our knowledge base or browse categories below</p>
            <form class="search-form" action="" method="get">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search for articles..."
                    value="<?= h($searchQuery) ?>"
                    autocomplete="off"
                >
            </form>
        </div>
    </section>
    
    <main class="content">
        <div class="container">
            <?php if (!empty($searchQuery)): ?>
                <!-- Search Results -->
                <div class="search-results-header">
                    <h2>Search Results</h2>
                    <p><?= count($searchResults) ?> results for "<?= h($searchQuery) ?>"</p>
                </div>
                
                <?php if (empty($searchResults)): ?>
                    <div class="no-results">
                        <h3>No results found</h3>
                        <p>Try different keywords or browse our categories</p>
                    </div>
                <?php else: ?>
                    <div class="article-list">
                        <?php foreach ($searchResults as $article): ?>
                            <a href="support-article.php?slug=<?= h($article['slug']) ?>" class="article-card">
                                <h3><?= h($article['title']) ?></h3>
                                <p><?= h(substr(strip_tags($article['content']), 0, 150)) ?>...</p>
                                <div class="article-meta">
                                    <?php if (!empty($article['category_name'])): ?>
                                        <span class="category-badge"><?= h($article['category_name']) ?></span>
                                    <?php endif; ?>
                                    <span><?= h($article['view_count']) ?> views</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($selectedCategoryData): ?>
                <!-- Category Articles -->
                <div class="content-grid">
                    <aside class="sidebar">
                        <h3>Categories</h3>
                        <ul class="category-list">
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="?category=<?= h($cat['slug']) ?>" class="<?= $cat['slug'] === $selectedCategory ? 'active' : '' ?>">
                                        <?= h($cat['name']) ?>
                                        <span class="category-count"><?= h($cat['article_count']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    
                    <div class="main-content">
                        <h2><?= h($selectedCategoryData['name']) ?></h2>
                        <?php if (!empty($selectedCategoryData['description'])): ?>
                            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                                <?= h($selectedCategoryData['description']) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="article-list">
                            <?php foreach ($categoryArticles as $article): ?>
                                <a href="support-article.php?slug=<?= h($article['slug']) ?>" class="article-card">
                                    <h3><?= h($article['title']) ?></h3>
                                    <p><?= h(substr(strip_tags($article['content']), 0, 150)) ?>...</p>
                                    <div class="article-meta">
                                        <span><?= h($article['view_count']) ?> views</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Default View -->
                <div class="content-grid">
                    <aside class="sidebar">
                        <h3>Categories</h3>
                        <ul class="category-list">
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="?category=<?= h($cat['slug']) ?>">
                                        <?= h($cat['name']) ?>
                                        <span class="category-count"><?= h($cat['article_count']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h3>Popular Articles</h3>
                        <ul class="compact-list">
                            <?php foreach ($popularArticles as $article): ?>
                                <li>
                                    <a href="support-article.php?slug=<?= h($article['slug']) ?>">
                                        <?= h($article['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                    
                    <div class="main-content">
                        <h2>Recent Articles</h2>
                        <div class="article-list">
                            <?php foreach ($recentArticles as $article): ?>
                                <a href="support-article.php?slug=<?= h($article['slug']) ?>" class="article-card">
                                    <h3><?= h($article['title']) ?></h3>
                                    <p><?= h(substr(strip_tags($article['content']), 0, 150)) ?>...</p>
                                    <div class="article-meta">
                                        <?php if (!empty($article['category_name'])): ?>
                                            <span class="category-badge"><?= h($article['category_name']) ?></span>
                                        <?php endif; ?>
                                        <span><?= h($article['view_count']) ?> views</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (empty($recentArticles)): ?>
                            <div class="no-results">
                                <h3>No articles yet</h3>
                                <p>Check back soon for helpful documentation</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
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


