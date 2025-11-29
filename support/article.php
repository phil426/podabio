<?php
/**
 * Support Article View
 * PodaBio - Display support article
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
    <meta name="description" content="<?php echo h(truncate($article['content'], 160)); ?>">
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
        .support-article-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .support-article-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            margin-bottom: 2rem;
            font-family: var(--poda-font-body);
            transition: all 0.3s;
        }
        
        .support-article-back-link:hover {
            color: var(--poda-accent-signal-green-hover);
            transform: translateX(-3px);
        }
        
        .support-article {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .support-article-breadcrumb {
            color: var(--poda-text-secondary);
            margin-bottom: 1.5rem;
            font-family: var(--poda-font-body);
            font-size: 0.9rem;
        }
        
        .support-article-breadcrumb a {
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .support-article-breadcrumb a:hover {
            color: var(--poda-accent-signal-green-hover);
        }
        
        .support-article h1 {
            font-family: var(--poda-font-heading);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .support-article-meta {
            color: var(--poda-text-secondary);
            font-family: var(--poda-font-body);
            font-size: 0.9rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--poda-border-subtle);
        }
        
        .support-article-content {
            font-family: var(--poda-font-body);
            font-size: 1rem;
            line-height: 1.8;
            color: var(--poda-text-primary);
        }
        
        .support-article-content h2 {
            font-family: var(--poda-font-heading);
            font-size: 1.75rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .support-article-content h3 {
            font-family: var(--poda-font-heading);
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .support-article-content p {
            margin-bottom: 1.25rem;
        }
        
        .support-article-content ul,
        .support-article-content ol {
            margin-left: 2rem;
            margin-bottom: 1.25rem;
        }
        
        .support-article-content li {
            margin-bottom: 0.5rem;
        }
        
        .support-article-content a {
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s;
        }
        
        .support-article-content a:hover {
            border-bottom-color: var(--poda-accent-signal-green);
        }
        
        .support-article-tags {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--poda-border-subtle);
        }
        
        .support-related-articles {
            margin-top: 3rem;
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .support-related-articles h2 {
            font-family: var(--poda-font-heading);
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            color: var(--poda-text-primary);
        }
        
        .support-related-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .support-related-item {
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .support-related-item:hover {
            background: var(--poda-bg-primary);
        }
        
        .support-related-item a {
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            font-weight: 500;
            font-family: var(--poda-font-body);
            transition: color 0.3s;
        }
        
        .support-related-item a:hover {
            color: var(--poda-accent-signal-green-hover);
        }
        
        @media (max-width: 768px) {
            .support-article {
                padding: 2rem;
            }
            
            .support-article h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- React Marketing Navigation Mount Point -->
    <div id="marketing-nav-root"></div>
    
    <div class="support-article-container" style="padding-top: 2rem;">
        <a href="/support/" class="support-article-back-link">← Back to Support</a>
        
        <article class="support-article">
            <div class="support-article-breadcrumb">
                <a href="/support/">Support</a>
                <?php if ($article['category_name']): ?>
                    → <a href="/support/category.php?slug=<?php echo h($article['category_slug']); ?>"><?php echo h($article['category_name']); ?></a>
                <?php endif; ?>
            </div>
            
            <h1><?php echo h($article['title']); ?></h1>
            
            <div class="support-article-meta">
                <?php if ($article['category_name']): ?>
                    <span><?php echo h($article['category_name']); ?></span> • 
                <?php endif; ?>
                <span>Updated <?php echo h(formatDate($article['updated_at'] ?? $article['created_at'], 'F j, Y')); ?></span>
            </div>
            
            <div class="support-article-content">
                <?php echo nl2br(h($article['content'])); ?>
            </div>
            
            <?php if (!empty($article['tags'])): ?>
                <div class="support-article-tags">
                    <strong style="color: var(--poda-text-primary);">Tags:</strong> 
                    <span style="color: var(--poda-text-secondary);"><?php echo h($article['tags']); ?></span>
                </div>
            <?php endif; ?>
        </article>
        
        <?php if (!empty($relatedArticles)): ?>
            <div class="support-related-articles">
                <h2>Related Articles</h2>
                <ul class="support-related-list">
                    <?php foreach ($relatedArticles as $related): ?>
                        <li class="support-related-item">
                            <a href="/support/article.php?slug=<?php echo h($related['slug']); ?>">
                                <?php echo h($related['title']); ?>
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
