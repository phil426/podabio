<?php
/**
 * Blog Post Single Page
 * PodaBio - Display individual blog post
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/BlogPost.php';
require_once __DIR__ . '/classes/BlogCategory.php';

$appName = defined('APP_NAME') ? APP_NAME : 'PodaBio';
$appUrl = defined('APP_URL') ? APP_URL : 'https://poda.bio';

// Get post by slug
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /blog.php');
    exit;
}

$post = BlogPost::getBySlug($slug, true);

if (!$post) {
    http_response_code(404);
    $pageTitle = "Post Not Found - " . $appName;
    $notFound = true;
} else {
    // Increment view count
    BlogPost::incrementViewCount($post['id']);
    $pageTitle = h($post['title']) . " - Blog - " . $appName;
    $notFound = false;
}

// Get related posts
$relatedPosts = [];
if (!$notFound) {
    $relatedPosts = BlogPost::getRelated($post['id'], 3);
}

// Get recent posts for sidebar
$recentPosts = BlogPost::getRecent(5);

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
    
    // Images
    $text = preg_replace('/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1" class="content-image">', $text);
    
    // Code blocks
    $text = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $text);
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    
    // Blockquotes
    $text = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $text);
    $text = preg_replace('/<\/blockquote>\s*<blockquote>/', '<br>', $text);
    
    // Lists
    $text = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.+<\/li>)/s', '<ul>$1</ul>', $text);
    $text = preg_replace('/<\/ul>\s*<ul>/', '', $text);
    
    // Numbered lists
    $text = preg_replace('/^\d+\. (.+)$/m', '<oli>$1</oli>', $text);
    $text = preg_replace('/(<oli>.+<\/oli>)/s', '<ol>$1</ol>', $text);
    $text = str_replace(['<oli>', '</oli>'], ['<li>', '</li>'], $text);
    $text = preg_replace('/<\/ol>\s*<ol>/', '', $text);
    
    // Horizontal rules
    $text = preg_replace('/^---$/m', '<hr>', $text);
    
    // Paragraphs
    $text = '<p>' . preg_replace('/\n\n+/', '</p><p>', trim($text)) . '</p>';
    
    // Clean up
    $text = str_replace('<p></p>', '', $text);
    $text = preg_replace('/<p>(<h[123]>)/s', '$1', $text);
    $text = preg_replace('/(<\/h[123]>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<ul>)/s', '$1', $text);
    $text = preg_replace('/(<\/ul>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<ol>)/s', '$1', $text);
    $text = preg_replace('/(<\/ol>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<pre>)/s', '$1', $text);
    $text = preg_replace('/(<\/pre>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<blockquote>)/s', '$1', $text);
    $text = preg_replace('/(<\/blockquote>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<hr>)<\/p>/s', '$1', $text);
    $text = preg_replace('/<p>(<img)/s', '$1', $text);
    $text = preg_replace('/(class="content-image">)<\/p>/s', '$1', $text);
    
    return $text;
}

// Format reading time
function estimateReadingTime($text) {
    $wordCount = str_word_count(strip_tags($text));
    $minutes = ceil($wordCount / 200);
    return $minutes . ' min read';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php if (!$notFound): ?>
    <meta name="description" content="<?= h($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160)) ?>">
    <link rel="canonical" href="<?= h($appUrl) ?>/blog-post.php?slug=<?= h($post['slug']) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= h($post['title']) ?>">
    <meta property="og:description" content="<?= h($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160)) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= h($appUrl) ?>/blog-post.php?slug=<?= h($post['slug']) ?>">
    <?php if (!empty($post['featured_image'])): ?>
    <meta property="og:image" content="<?= h($post['featured_image']) ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($post['title']) ?>">
    <meta name="twitter:description" content="<?= h($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160)) ?>">
    <?php endif; ?>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&display=swap" rel="stylesheet">
    
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
        
        /* Hero Image */
        .hero-image {
            width: 100%;
            max-height: 400px;
            overflow: hidden;
        }
        
        .hero-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
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
        
        /* Article */
        .article-header {
            margin-bottom: 40px;
        }
        
        .article-header h1 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .article-header h1 {
                font-size: 32px;
            }
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
            color: var(--text-tertiary);
            font-size: 14px;
        }
        
        .category-badge {
            padding: 6px 14px;
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-secondary);
            border-radius: 6px;
            font-weight: 500;
        }
        
        .meta-divider {
            width: 4px;
            height: 4px;
            background: var(--text-tertiary);
            border-radius: 50%;
        }
        
        /* Article Content */
        .article-content {
            font-family: 'Newsreader', Georgia, serif;
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        .article-content h1,
        .article-content h2,
        .article-content h3 {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            margin: 40px 0 20px;
            color: var(--text-primary);
            line-height: 1.3;
        }
        
        .article-content h1:first-child,
        .article-content h2:first-child,
        .article-content h3:first-child {
            margin-top: 0;
        }
        
        .article-content h2 {
            font-size: 28px;
        }
        
        .article-content h3 {
            font-size: 22px;
        }
        
        .article-content p {
            margin-bottom: 20px;
        }
        
        .article-content ul,
        .article-content ol {
            margin: 20px 0;
            padding-left: 28px;
        }
        
        .article-content li {
            margin-bottom: 10px;
        }
        
        .article-content blockquote {
            margin: 32px 0;
            padding: 24px 32px;
            background: var(--bg-elevated);
            border-left: 4px solid var(--accent-primary);
            border-radius: 0 12px 12px 0;
            font-style: italic;
            color: var(--text-primary);
        }
        
        .article-content code {
            padding: 2px 6px;
            background: var(--bg-elevated);
            border-radius: 4px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.85em;
            color: var(--accent-secondary);
        }
        
        .article-content pre {
            margin: 24px 0;
            padding: 24px;
            background: var(--bg-secondary);
            border-radius: 12px;
            overflow-x: auto;
        }
        
        .article-content pre code {
            padding: 0;
            background: none;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .article-content strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .article-content a {
            color: var(--accent-secondary);
            text-decoration: underline;
            text-decoration-color: rgba(167, 139, 250, 0.3);
            text-underline-offset: 3px;
        }
        
        .article-content a:hover {
            text-decoration-color: var(--accent-secondary);
        }
        
        .article-content hr {
            margin: 48px 0;
            border: none;
            border-top: 1px solid var(--border-color);
        }
        
        .article-content .content-image {
            width: 100%;
            height: auto;
            margin: 32px 0;
            border-radius: 12px;
        }
        
        /* Tags */
        .article-tags {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }
        
        .article-tags h4 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .tag {
            padding: 6px 14px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        /* Share */
        .share-section {
            margin-top: 32px;
            padding: 24px;
            background: var(--bg-elevated);
            border-radius: 12px;
            text-align: center;
        }
        
        .share-section h4 {
            font-size: 14px;
            margin-bottom: 16px;
            color: var(--text-secondary);
        }
        
        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        
        .share-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .share-button:hover {
            border-color: var(--accent-primary);
            text-decoration: none;
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
        
        .related-list,
        .recent-list {
            list-style: none;
        }
        
        .related-list li,
        .recent-list li {
            padding: 14px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .related-list li:last-child,
        .recent-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .related-list a,
        .recent-list a {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .related-list a:hover,
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
        
        /* CTA */
        .cta-card {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(139, 92, 246, 0.3);
            text-align: center;
        }
        
        .cta-card h4 {
            font-size: 18px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .cta-card p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        
        .cta-button {
            display: inline-block;
            padding: 12px 28px;
            background: var(--accent-primary);
            color: white;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .cta-button:hover {
            opacity: 0.9;
            text-decoration: none;
        }
        
        /* Not Found */
        .not-found {
            text-align: center;
            padding: 100px 20px;
        }
        
        .not-found h1 {
            font-size: 72px;
            margin-bottom: 16px;
            color: var(--text-tertiary);
        }
        
        .not-found h2 {
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .not-found p {
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        .back-button {
            display: inline-block;
            padding: 14px 28px;
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
    
    <?php if ($notFound): ?>
        <main class="not-found">
            <h1>404</h1>
            <h2>Post Not Found</h2>
            <p>Sorry, we couldn't find that blog post.</p>
            <a href="/blog.php" class="back-button">Browse All Posts</a>
        </main>
    <?php else: ?>
        <?php if (!empty($post['featured_image'])): ?>
            <div class="hero-image">
                <img src="<?= h($post['featured_image']) ?>" alt="">
            </div>
        <?php endif; ?>
        
        <main class="content">
            <div class="container">
                <div class="content-grid">
                    <article>
                        <div class="article-header">
                            <h1><?= h($post['title']) ?></h1>
                            <div class="article-meta">
                                <?php if (!empty($post['category_name'])): ?>
                                    <a href="/blog.php?category=<?= h($post['category_slug']) ?>" class="category-badge">
                                        <?= h($post['category_name']) ?>
                                    </a>
                                <?php endif; ?>
                                <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
                                <span class="meta-divider"></span>
                                <span><?= estimateReadingTime($post['content']) ?></span>
                                <span class="meta-divider"></span>
                                <span><?= h($post['view_count']) ?> views</span>
                            </div>
                        </div>
                        
                        <div class="article-content">
                            <?= parseMarkdown($post['content']) ?>
                        </div>
                        
                        <?php if (!empty($post['tags'])): ?>
                            <div class="article-tags">
                                <h4>Tags</h4>
                                <div class="tag-list">
                                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                        <span class="tag"><?= h(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="share-section">
                            <h4>Share this post</h4>
                            <div class="share-buttons">
                                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode($appUrl . '/blog-post.php?slug=' . $post['slug']) ?>" target="_blank" class="share-button">
                                    𝕏 Twitter
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($appUrl . '/blog-post.php?slug=' . $post['slug']) ?>" target="_blank" class="share-button">
                                    LinkedIn
                                </a>
                                <button onclick="navigator.clipboard.writeText(window.location.href); this.textContent = 'Copied!';" class="share-button">
                                    Copy Link
                                </button>
                            </div>
                        </div>
                    </article>
                    
                    <aside class="sidebar">
                        <div class="sidebar-card cta-card">
                            <h4>Start Your Podcast Page</h4>
                            <p>Create a beautiful landing page for your show in minutes</p>
                            <a href="/signup.php" class="cta-button">Get Started Free</a>
                        </div>
                        
                        <?php if (!empty($relatedPosts)): ?>
                            <div class="sidebar-card">
                                <h3>Related Posts</h3>
                                <ul class="related-list">
                                    <?php foreach ($relatedPosts as $related): ?>
                                        <li>
                                            <a href="blog-post.php?slug=<?= h($related['slug']) ?>">
                                                <?= h($related['title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sidebar-card">
                            <h3>Recent Posts</h3>
                            <ul class="recent-list">
                                <?php foreach ($recentPosts as $recent): ?>
                                    <?php if ($recent['id'] !== $post['id']): ?>
                                        <li>
                                            <a href="blog-post.php?slug=<?= h($recent['slug']) ?>">
                                                <?= h($recent['title']) ?>
                                                <span class="recent-date"><?= date('M j, Y', strtotime($recent['created_at'])) ?></span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
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


