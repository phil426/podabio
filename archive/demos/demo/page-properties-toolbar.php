<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/feature-flags.php';

requireAuth();

$manifestPath = __DIR__ . '/../admin-ui/dist/.vite/manifest.json';
$scriptSrc = null;
$cssHref = null;

if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (isset($manifest['index.html'])) {
        $entry = $manifest['index.html'];
        $scriptSrc = '/admin-ui/dist/' . $entry['file'];
        if (!empty($entry['css'])) {
            $cssHref = '/admin-ui/dist/' . $entry['css'][0];
        }
    }
}

// Check if Vite dev server is running
$isDev = !$scriptSrc;
if (!$isDev) {
    $devServerRunning = @fsockopen('localhost', 5174, $errno, $errstr, 0.1);
    if ($devServerRunning) {
        fclose($devServerRunning);
        $isDev = true;
        $scriptSrc = null;
    }
}
if ($isDev) {
    $scriptSrc = 'http://localhost:5174/src/main.tsx';
}

$csrfToken = generateCSRFToken();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Properties Toolbar Demo - PodaBio</title>
    <?php if ($cssHref): ?>
        <link rel="stylesheet" href="<?php echo h($cssHref); ?>">
    <?php endif; ?>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .fallback {
            display: grid;
            place-items: center;
            min-height: 100vh;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <script>
        window.__CSRF_TOKEN__ = '<?php echo h($csrfToken); ?>';
        window.__APP_URL__ = '<?php echo h(APP_URL); ?>';
        window.__FEATURES__ = <?php echo json_encode([
            'account_workspace' => feature_flag('admin_account_workspace', true),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>
    <div id="root">
        <div class="fallback">
            <p>Loading demo…</p>
        </div>
    </div>
    <?php if ($isDev): ?>
        <script type="module">
            import RefreshRuntime from "http://localhost:5174/@react-refresh";
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>
        <script type="module" src="http://localhost:5174/@vite/client"></script>
    <?php endif; ?>
    <script type="module" src="<?php echo h($scriptSrc); ?>"></script>
</body>
</html>

