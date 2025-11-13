<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/feature-flags.php';

requireAuth();

if (!feature_flag('admin_new_experience')) {
    redirect('/editor.php');
    exit;
}

$accountWorkspaceEnabled = feature_flag('admin_account_workspace', true);

$manifestPath = __DIR__ . '/../admin-ui/dist/manifest.json';
$scriptSrc = null;
$cssHref = null;

if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (isset($manifest['src/main.tsx'])) {
        $entry = $manifest['src/main.tsx'];
        $scriptSrc = '/admin-ui/dist/' . $entry['file'];
        if (!empty($entry['css'])) {
            $cssHref = '/admin-ui/dist/' . $entry['css'][0];
        }
    }
}

$isDev = !$scriptSrc;
if ($isDev) {
    $scriptSrc = 'http://localhost:5174/src/main.tsx';
}

$csrfToken = generateCSRFToken();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PodaBio Admin Preview</title>
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
            'account_workspace' => $accountWorkspaceEnabled,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>
    <div id="root">
        <div class="fallback">
            <p>Loading the new admin experience…</p>
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

