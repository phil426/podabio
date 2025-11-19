<?php
/**
 * Support Knowledge Base - Index
 * PodaBio - Support articles listing
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Get all categories with article counts
$categories = fetchAll(
    "SELECT c.*, COUNT(a.id) as article_count 
     FROM support_categories c 
     LEFT JOIN support_articles a ON c.id = a.category_id AND a.published = 1
     GROUP BY c.id 
     ORDER BY c.display_order ASC, c.name ASC"
);

// Get featured/recent articles
$recentArticles = fetchAll(
    "SELECT a.*, c.name as category_name, c.slug as category_slug 
     FROM support_articles a 
     LEFT JOIN support_categories c ON a.category_id = c.id 
     WHERE a.published = 1 
     ORDER BY a.created_at DESC 
     LIMIT 10"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - <?php echo h(APP_NAME); ?></title>
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
            line-height: 1.6;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.25rem;
            opacity: 0.95;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }
        
        .search-box {
            margin-bottom: 3rem;
        }
        
        .search-box form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-box input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px 0 0 8px;
            font-size: 1rem;
        }
        
        .search-box button {
            padding: 1rem 2rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            font-weight: 600;
            cursor: pointer;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 4rem;
        }
        
        .category-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .category-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .category-card p {
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .category-card .count {
            color: #667eea;
            font-weight: 600;
        }
        
        .articles-section {
            margin-top: 4rem;
        }
        
        .articles-section h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #1f2937;
        }
        
        .articles-list {
            list-style: none;
        }
        
        .articles-list li {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .articles-list li:hover {
            transform: translateX(5px);
        }
        
        .articles-list a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .articles-list h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .articles-list p {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .article-meta {
            font-size: 0.875rem;
            color: #9ca3af;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 2rem 2rem;
            margin-top: 4rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #374151;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <ul class="nav-links">
                <li><a href="/features.php">Features</a></li>
                <li><a href="/pricing.php">Pricing</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/support/">Support</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="page-header">
        <h1>Support Center</h1>
        <p>Find answers to common questions and learn how to use <?php echo h(APP_NAME); ?></p>
    </div>
    
    <div class="container">
        <div class="search-box">
            <form method="GET" action="/support/search.php">
                <input type="text" name="q" placeholder="Search for help..." required>
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="/support/category.php?slug=<?php echo h($category['slug']); ?>" class="category-card">
                    <h3><?php echo h($category['name']); ?></h3>
                    <p><?php echo h($category['description'] ?? ''); ?></p>
                    <span class="count"><?php echo $category['article_count']; ?> articles</span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($recentArticles)): ?>
            <div class="articles-section">
                <h2>Recent Articles</h2>
                <ul class="articles-list">
                    <?php foreach ($recentArticles as $article): ?>
                        <li>
                            <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                                <h3><?php echo h($article['title']); ?></h3>
                                <p><?php echo h(truncate($article['content'], 150)); ?></p>
                                <div class="article-meta">
                                    <?php if ($article['category_name']): ?>
                                        <span><?php echo h($article['category_name']); ?></span> • 
                                    <?php endif; ?>
                                    <span><?php echo h(formatDate($article['created_at'], 'M j, Y')); ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4><?php echo h(APP_NAME); ?></h4>
                <p>The link-in-bio platform built for podcasters.</p>
            </div>
            
            <div class="footer-section">
                <h4>Product</h4>
                <ul>
                    <li><a href="/features.php">Features</a></li>
                    <li><a href="/pricing.php">Pricing</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/blog/">Blog</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

