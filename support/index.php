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
    <meta name="description" content="Find answers to common questions and learn how to use <?php echo h(APP_NAME); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/../css/marketing-dark.css'); ?>">
    <?php
    // Load React marketing navigation component
    require_once __DIR__ . '/../config/spa-config.php';
    
    $viteDevServerRunning = isViteDevServerRunning();
    $isDev = $viteDevServerRunning || isSPADevMode();
    
    if ($isDev) {
        // Development: Load React Refresh and Vite client first, then the component
        $refreshUrl = getDevServerRefreshUrl();
        $viteClientUrl = getDevServerViteClientUrl();
        ?>
        <script type="module">
            import RefreshRuntime from "<?php echo htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8'); ?>";
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>
        <script type="module" src="<?php echo htmlspecialchars($viteClientUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <script type="module" src="http://localhost:5174/src/marketing-nav.tsx"></script>
        <script type="module" src="http://localhost:5174/src/marketing-icons.tsx"></script>
        <?php
    } else {
        // Production: Load from built files
        $manifestPath = __DIR__ . '/../admin-ui/dist/.vite/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['src/marketing-nav.tsx'])) {
                $entry = $manifest['src/marketing-nav.tsx'];
                if (isset($entry['file'])) {
                    echo '<script type="module" src="/admin-ui/dist/' . htmlspecialchars($entry['file']) . '"></script>';
                }
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/admin-ui/dist/' . htmlspecialchars($cssFile) . '">';
                    }
                }
            }
            if (isset($manifest['src/marketing-icons.tsx'])) {
                $entry = $manifest['src/marketing-icons.tsx'];
                if (isset($entry['file'])) {
                    echo '<script type="module" src="/admin-ui/dist/' . htmlspecialchars($entry['file']) . '"></script>';
                }
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/admin-ui/dist/' . htmlspecialchars($cssFile) . '">';
                    }
                }
            }
        }
    }
    ?>
    <style>
        /* Support Page Specific Styles */
        .support-hero {
            padding: 6rem 2rem 4rem;
            text-align: center;
            background: var(--poda-bg-primary);
        }
        
        .support-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .support-hero p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
        }
        
        .support-search-box {
            max-width: 700px;
            margin: 0 auto 4rem;
        }
        
        .support-search-form {
            display: flex;
            gap: 0;
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 0.5rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .support-search-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--poda-text-primary);
            font-family: var(--poda-font-body);
            font-size: 1rem;
            padding: 0.75rem 1rem;
        }
        
        .support-search-input::placeholder {
            color: var(--poda-text-muted);
        }
        
        .support-search-btn {
            background: var(--poda-accent-signal-green);
            color: var(--poda-bg-primary);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-family: var(--poda-font-body);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .support-search-btn:hover {
            background: var(--poda-accent-signal-green-hover);
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.4);
        }
        
        .support-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 4rem;
        }
        
        .support-category-card {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .support-category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--poda-accent-signal-green);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .support-category-card:hover {
            transform: translateY(-4px);
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 8px 32px rgba(0, 255, 127, 0.2);
        }
        
        .support-category-card:hover::before {
            transform: scaleX(1);
        }
        
        .support-category-card h3 {
            font-family: var(--poda-font-heading);
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--poda-text-primary);
        }
        
        .support-category-card p {
            color: var(--poda-text-secondary);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .support-category-count {
            color: var(--poda-accent-signal-green);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .support-articles-section {
            margin-top: 4rem;
        }
        
        .support-articles-section h2 {
            font-family: var(--poda-font-heading);
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--poda-text-primary);
        }
        
        .support-articles-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .support-article-item {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .support-article-item:hover {
            border-color: var(--poda-accent-signal-green);
            transform: translateX(5px);
            box-shadow: 0 4px 16px rgba(0, 255, 127, 0.1);
        }
        
        .support-article-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .support-article-item h3 {
            font-family: var(--poda-font-heading);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--poda-text-primary);
        }
        
        .support-article-item p {
            color: var(--poda-text-secondary);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        .support-article-meta {
            font-size: 0.85rem;
            color: var(--poda-text-muted);
        }
        
        @media (max-width: 768px) {
            .support-hero h1 {
                font-size: 2.5rem;
            }
            
            .support-categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- React Marketing Navigation Mount Point -->
    <div id="marketing-nav-root"></div>
    
    <!-- Support Hero Section -->
    <section class="support-hero">
        <h1>Support Center</h1>
        <p>Find answers to common questions and learn how to use <?php echo h(APP_NAME); ?></p>
    </section>
    
    <div class="support-container">
        <!-- Search Box -->
        <div class="support-search-box">
            <form method="GET" action="/support/search.php" class="support-search-form">
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Search for help..." 
                    class="support-search-input"
                    required
                />
                <button type="submit" class="support-search-btn">Search</button>
            </form>
        </div>
        
        <!-- Categories Grid -->
        <div class="support-categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="/support/category.php?slug=<?php echo h($category['slug']); ?>" class="support-category-card">
                    <h3><?php echo h($category['name']); ?></h3>
                    <p><?php echo h($category['description'] ?? ''); ?></p>
                    <span class="support-category-count"><?php echo $category['article_count']; ?> articles</span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Articles -->
        <?php if (!empty($recentArticles)): ?>
            <div class="support-articles-section">
                <h2>Recent Articles</h2>
                <ul class="support-articles-list">
                    <?php foreach ($recentArticles as $article): ?>
                        <li class="support-article-item">
                            <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                                <h3><?php echo h($article['title']); ?></h3>
                                <p><?php echo h(truncate($article['content'], 150)); ?></p>
                                <div class="support-article-meta">
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
    
    <!-- Footer (same as marketing pages) -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4><?php echo h(APP_NAME); ?></h4>
                <p>The link-in-bio platform built for podcasters.</p>
            </div>
            
            <div class="footer-section">
                <h4>Product</h4>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="#about">About</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#privacy" onclick="if(typeof openDrawer === 'function') { openDrawer('privacy'); return false; }">Privacy</a></li>
                    <li><a href="#terms" onclick="if(typeof openDrawer === 'function') { openDrawer('terms'); return false; }">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
