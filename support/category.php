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
    <meta name="description" content="<?php echo h($category['description'] ?? ''); ?>">
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
        .support-category-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .support-category-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            margin-bottom: 2rem;
            font-family: var(--poda-font-body);
            transition: all 0.3s;
        }
        
        .support-category-back-link:hover {
            color: var(--poda-accent-signal-green-hover);
            transform: translateX(-3px);
        }
        
        .support-category-header {
            margin-bottom: 3rem;
        }
        
        .support-category-header h1 {
            font-family: var(--poda-font-heading);
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            color: var(--poda-text-primary);
        }
        
        .support-category-header p {
            color: var(--poda-text-secondary);
            font-size: 1.1rem;
            font-family: var(--poda-font-body);
        }
        
        .support-category-articles-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .support-category-article-item {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .support-category-article-item:hover {
            border-color: var(--poda-accent-signal-green);
            transform: translateX(5px);
            box-shadow: 0 4px 16px rgba(0, 255, 127, 0.1);
        }
        
        .support-category-article-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .support-category-article-item h3 {
            font-family: var(--poda-font-heading);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--poda-text-primary);
        }
        
        .support-category-article-item p {
            color: var(--poda-text-secondary);
            font-family: var(--poda-font-body);
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .support-category-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- React Marketing Navigation Mount Point -->
    <div id="marketing-nav-root"></div>
    
    <div class="support-category-container" style="padding-top: 2rem;">
        <a href="/support/" class="support-category-back-link">← Back to Support</a>
        
        <div class="support-category-header">
            <h1><?php echo h($category['name']); ?></h1>
            <p><?php echo h($category['description'] ?? ''); ?></p>
        </div>
        
        <ul class="support-category-articles-list">
            <?php foreach ($articles as $article): ?>
                <li class="support-category-article-item">
                    <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                        <h3><?php echo h($article['title']); ?></h3>
                        <p><?php echo h(truncate($article['content'], 150)); ?></p>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
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
