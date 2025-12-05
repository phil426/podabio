<?php
/**
 * Blog Listing Page
 * PodaBio - Marketing blog for content
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/BlogPost.php';
require_once __DIR__ . '/classes/BlogCategory.php';

$appName = defined('APP_NAME') ? APP_NAME : 'PodaBio';
$appUrl = defined('APP_URL') ? APP_URL : 'https://poda.bio';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get categories
$categories = BlogCategory::getWithPosts();

// Handle category filter
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$categoryId = null;
$selectedCategoryData = null;

if ($selectedCategory) {
    $selectedCategoryData = BlogCategory::getBySlug($selectedCategory);
    if ($selectedCategoryData) {
        $categoryId = $selectedCategoryData['id'];
    }
}

// Get posts
$posts = BlogPost::getPublished($categoryId, $perPage, $offset);
$totalPosts = BlogPost::count(true);
$totalPages = ceil($totalPosts / $perPage);

// Handle search
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchResults = [];
if (!empty($searchQuery)) {
    $searchResults = BlogPost::search($searchQuery, true);
}

// Get recent posts for sidebar
$recentPosts = BlogPost::getRecent(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $selectedCategoryData ? h($selectedCategoryData['name']) . ' - ' : '' ?>Blog - <?= h($appName) ?></title>
    <meta name="description" content="Tips, guides, and insights for podcasters. Learn how to grow your audience and monetize your content.">
    <link rel="canonical" href="<?= h($appUrl) ?>/blog.php">
    
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
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, transparent 60%);
            padding: 60px 0;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .hero p {
            font-size: 20px;
            color: var(--text-secondary);
            max-width: 600px;
        }
        
        /* Search */
        .search-bar {
            margin-top: 32px;
            max-width: 500px;
        }
        
        .search-input {
            width: 100%;
            padding: 14px 20px;
            font-size: 16px;
            font-family: inherit;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            outline: none;
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
            grid-template-columns: 1fr 300px;
            gap: 48px;
        }
        
        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Category Pills */
        .category-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }
        
        .category-pill {
            padding: 8px 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .category-pill:hover,
        .category-pill.active {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--accent-primary);
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        /* Post Grid */
        .post-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .post-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            text-decoration: none;
        }
        
        .post-image {
            height: 180px;
            background: var(--bg-secondary);
            overflow: hidden;
        }
        
        .post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .post-image-placeholder {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-elevated) 100%);
            font-size: 48px;
            color: var(--text-tertiary);
        }
        
        .post-content {
            padding: 24px;
        }
        
        .post-content h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .post-excerpt {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--text-tertiary);
        }
        
        .category-badge {
            padding: 4px 10px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Featured Post */
        .featured-post {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 0;
        }
        
        @media (max-width: 700px) {
            .featured-post {
                grid-template-columns: 1fr;
            }
        }
        
        .featured-post .post-image {
            height: 100%;
            min-height: 280px;
        }
        
        .featured-post .post-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px;
        }
        
        .featured-post h2 {
            font-size: 28px;
            -webkit-line-clamp: 3;
        }
        
        .featured-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--accent-primary);
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
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
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .recent-list {
            list-style: none;
        }
        
        .recent-list li {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .recent-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .recent-list a {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .recent-list a:hover {
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        .recent-date {
            display: block;
            font-size: 12px;
            color: var(--text-tertiary);
            margin-top: 4px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 48px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .pagination a:hover {
            border-color: var(--accent-primary);
            color: var(--accent-secondary);
            text-decoration: none;
        }
        
        .pagination .current {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        /* Search Results */
        .search-header {
            margin-bottom: 24px;
        }
        
        .search-header h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .search-header p {
            color: var(--text-secondary);
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
        
        /* Newsletter CTA */
        .newsletter-cta {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }
        
        .newsletter-cta h4 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .newsletter-cta p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        
        .newsletter-cta .cta-button {
            display: inline-block;
            padding: 10px 24px;
            background: var(--accent-primary);
            color: white;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .newsletter-cta .cta-button:hover {
            opacity: 0.9;
            text-decoration: none;
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
    
    <section class="hero">
        <div class="container">
            <h1><?= $selectedCategoryData ? h($selectedCategoryData['name']) : 'Blog' ?></h1>
            <p>
                <?= $selectedCategoryData 
                    ? h($selectedCategoryData['description'] ?: 'Browse articles in this category') 
                    : 'Tips, guides, and insights to help you grow your podcast and build your audience.' 
                ?>
            </p>
            <form class="search-bar" action="" method="get">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search articles..."
                    value="<?= h($searchQuery) ?>"
                >
            </form>
        </div>
    </section>
    
    <main class="content">
        <div class="container">
            <?php if (!empty($searchQuery)): ?>
                <!-- Search Results -->
                <div class="search-header">
                    <h2>Search Results</h2>
                    <p><?= count($searchResults) ?> results for "<?= h($searchQuery) ?>"</p>
                </div>
                
                <?php if (empty($searchResults)): ?>
                    <div class="empty-state">
                        <h3>No results found</h3>
                        <p>Try different keywords or browse categories below</p>
                    </div>
                <?php else: ?>
                    <div class="post-grid">
                        <?php foreach ($searchResults as $post): ?>
                            <a href="blog-post.php?slug=<?= h($post['slug']) ?>" class="post-card">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <div class="post-image">
                                        <img src="<?= h($post['featured_image']) ?>" alt="">
                                    </div>
                                <?php else: ?>
                                    <div class="post-image-placeholder">📝</div>
                                <?php endif; ?>
                                <div class="post-content">
                                    <h2><?= h($post['title']) ?></h2>
                                    <?php if (!empty($post['excerpt'])): ?>
                                        <p class="post-excerpt"><?= h($post['excerpt']) ?></p>
                                    <?php endif; ?>
                                    <div class="post-meta">
                                        <?php if (!empty($post['category_name'])): ?>
                                            <span class="category-badge"><?= h($post['category_name']) ?></span>
                                        <?php endif; ?>
                                        <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="content-grid">
                    <div>
                        <!-- Category Pills -->
                        <?php if (!empty($categories)): ?>
                            <div class="category-pills">
                                <a href="/blog.php" class="category-pill <?= !$selectedCategory ? 'active' : '' ?>">All</a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="?category=<?= h($cat['slug']) ?>" class="category-pill <?= $selectedCategory === $cat['slug'] ? 'active' : '' ?>">
                                        <?= h($cat['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($posts)): ?>
                            <div class="empty-state">
                                <h3>No posts yet</h3>
                                <p>Check back soon for new content!</p>
                            </div>
                        <?php else: ?>
                            <div class="post-grid">
                                <?php foreach ($posts as $index => $post): ?>
                                    <a href="blog-post.php?slug=<?= h($post['slug']) ?>" class="post-card <?= $index === 0 && $page === 1 && !$selectedCategory ? 'featured-post' : '' ?>">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <div class="post-image">
                                                <img src="<?= h($post['featured_image']) ?>" alt="">
                                            </div>
                                        <?php else: ?>
                                            <div class="post-image-placeholder">📝</div>
                                        <?php endif; ?>
                                        <div class="post-content">
                                            <?php if ($index === 0 && $page === 1 && !$selectedCategory): ?>
                                                <span class="featured-badge">Featured</span>
                                            <?php endif; ?>
                                            <h2><?= h($post['title']) ?></h2>
                                            <?php if (!empty($post['excerpt'])): ?>
                                                <p class="post-excerpt"><?= h($post['excerpt']) ?></p>
                                            <?php endif; ?>
                                            <div class="post-meta">
                                                <?php if (!empty($post['category_name'])): ?>
                                                    <span class="category-badge"><?= h($post['category_name']) ?></span>
                                                <?php endif; ?>
                                                <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?><?= $selectedCategory ? '&category=' . h($selectedCategory) : '' ?>">← Prev</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <?php if ($i === $page): ?>
                                            <span class="current"><?= $i ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?= $i ?><?= $selectedCategory ? '&category=' . h($selectedCategory) : '' ?>"><?= $i ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?= $page + 1 ?><?= $selectedCategory ? '&category=' . h($selectedCategory) : '' ?>">Next →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <aside class="sidebar">
                        <div class="newsletter-cta">
                            <h4>Ready to grow?</h4>
                            <p>Create your free PodaBio page today</p>
                            <a href="/signup.php" class="cta-button">Get Started</a>
                        </div>
                        
                        <?php if (!empty($recentPosts)): ?>
                            <div class="sidebar-card">
                                <h3>Recent Posts</h3>
                                <ul class="recent-list">
                                    <?php foreach ($recentPosts as $recent): ?>
                                        <li>
                                            <a href="blog-post.php?slug=<?= h($recent['slug']) ?>">
                                                <?= h($recent['title']) ?>
                                                <span class="recent-date"><?= date('M j, Y', strtotime($recent['created_at'])) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($categories)): ?>
                            <div class="sidebar-card">
                                <h3>Categories</h3>
                                <ul class="recent-list">
                                    <?php foreach ($categories as $cat): ?>
                                        <li>
                                            <a href="?category=<?= h($cat['slug']) ?>">
                                                <?= h($cat['name']) ?>
                                                <span class="recent-date"><?= h($cat['post_count']) ?> posts</span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </aside>
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


