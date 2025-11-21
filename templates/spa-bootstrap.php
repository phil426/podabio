<?php
/**
 * SPA Bootstrap Template
 * HTML template for React admin dashboard entry point
 * PodaBio
 * 
 * @var string $title Page title
 * @var string|null $cssHref Production CSS stylesheet href (optional)
 * @var string $windowGlobals JavaScript code for window globals
 * @var bool $isDev Whether running in development mode
 * @var array $devScripts Array of dev mode script tag HTML strings
 * @var string|null $scriptSrc Entry script source URL
 */

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo h($title ?? 'PodaBio User Dashboard'); ?></title>
    <?php if (!empty($cssHref)): ?>
        <link rel="stylesheet" href="<?php echo h($cssHref); ?>">
    <?php endif; ?>
    <!-- SPA Loader CSS (fallback styles) -->
    <link rel="stylesheet" href="/css/spa-loader.css?v=<?php echo filemtime(__DIR__ . '/../css/spa-loader.css'); ?>">
</head>
<body>
    <?php echo $windowGlobals ?? ''; ?>
    <script>
        // Clear any old admin panel references from client storage
        (function() {
            try {
                // Clear localStorage
                if (typeof localStorage !== 'undefined') {
                    const keys = Object.keys(localStorage);
                    keys.forEach(key => {
                        if (key.toLowerCase().includes('admin') || key.toLowerCase().includes('panel') || key.toLowerCase().includes('editor')) {
                            localStorage.removeItem(key);
                        }
                    });
                }
                // Clear sessionStorage
                if (typeof sessionStorage !== 'undefined') {
                    const keys = Object.keys(sessionStorage);
                    keys.forEach(key => {
                        if (key.toLowerCase().includes('admin') || key.toLowerCase().includes('panel') || key.toLowerCase().includes('editor')) {
                            sessionStorage.removeItem(key);
                        }
                    });
                }
                // Prevent any redirects to old admin pages
                if (typeof window !== 'undefined' && window.location) {
                    const oldAdminPaths = ['/admin/index.php', '/admin/classic.php', '/admin/select-panel.php', '/editor.php', '/admin/analytics.php', '/admin/pages.php', '/admin/blog.php', '/admin/users.php', '/admin/settings.php', '/admin/support.php', '/admin/subscriptions.php'];
                    const currentPath = window.location.pathname;
                    if (oldAdminPaths.some(path => currentPath.includes(path))) {
                        window.location.replace('/admin/userdashboard.php');
                    }
                }
            } catch (e) {
                console.warn('Error clearing storage:', e);
            }
        })();
    </script>
    <div id="root">
        <div class="fallback">
            <p>Loading the new admin experience…</p>
        </div>
    </div>
    <?php if (!empty($isDev) && !empty($devScripts)): ?>
        <?php foreach ($devScripts as $script): ?>
            <?php echo $script; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($scriptSrc)): ?>
        <script type="module" src="<?php echo h($scriptSrc); ?>"></script>
    <?php endif; ?>
</body>
</html>

