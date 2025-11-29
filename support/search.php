<?php
/**
 * Support Search
 * PodaBio - Search support articles
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
    <meta name="description" content="Search support articles">
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
        .support-search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .support-search-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            margin-bottom: 2rem;
            font-family: var(--poda-font-body);
            transition: all 0.3s;
        }
        
        .support-search-back-link:hover {
            color: var(--poda-accent-signal-green-hover);
            transform: translateX(-3px);
        }
        
        .support-search-box {
            max-width: 700px;
            margin: 0 auto 3rem;
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
        
        .support-search-results-count {
            color: var(--poda-text-secondary);
            margin-bottom: 2rem;
            font-family: var(--poda-font-body);
        }
        
        .support-search-articles-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .support-search-article-item {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .support-search-article-item:hover {
            border-color: var(--poda-accent-signal-green);
            transform: translateX(5px);
            box-shadow: 0 4px 16px rgba(0, 255, 127, 0.1);
        }
        
        .support-search-article-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .support-search-article-item h3 {
            font-family: var(--poda-font-heading);
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--poda-text-primary);
        }
        
        .support-search-article-item p {
            color: var(--poda-text-secondary);
            font-family: var(--poda-font-body);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .support-search-article-category {
            font-size: 0.85rem;
            color: var(--poda-text-muted);
            font-family: var(--poda-font-body);
        }
        
        .support-search-no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--poda-text-secondary);
        }
        
        .support-search-no-results p {
            font-family: var(--poda-font-body);
            margin-bottom: 1rem;
        }
        
        .support-search-no-results a {
            color: var(--poda-accent-signal-green);
            text-decoration: none;
            font-family: var(--poda-font-body);
        }
        
        .support-search-no-results a:hover {
            color: var(--poda-accent-signal-green-hover);
        }
    </style>
</head>
<body>
    <!-- React Marketing Navigation Mount Point -->
    <div id="marketing-nav-root"></div>
    
    <div class="support-search-container" style="padding-top: 2rem;">
        <a href="/support/" class="support-search-back-link">← Back to Support</a>
        
        <div class="support-search-box">
            <form method="GET" action="/support/search.php" class="support-search-form">
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Search for help..." 
                    value="<?php echo h($query); ?>"
                    class="support-search-input"
                    required
                />
                <button type="submit" class="support-search-btn">Search</button>
            </form>
        </div>
        
        <?php if (!empty($query)): ?>
            <div class="support-search-results-count">
                Found <?php echo count($results); ?> result(s) for "<?php echo h($query); ?>"
            </div>
            
            <?php if (empty($results)): ?>
                <div class="support-search-no-results">
                    <p>No results found. Try different keywords or <a href="/support/">browse all articles</a>.</p>
                </div>
            <?php else: ?>
                <ul class="support-search-articles-list">
                    <?php foreach ($results as $article): ?>
                        <li class="support-search-article-item">
                            <a href="/support/article.php?slug=<?php echo h($article['slug']); ?>">
                                <h3><?php echo h($article['title']); ?></h3>
                                <p><?php echo h(truncate($article['content'], 150)); ?></p>
                                <?php if ($article['category_name']): ?>
                                    <span class="support-search-article-category"><?php echo h($article['category_name']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
