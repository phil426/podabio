<?php
/**
 * PHP Built-in Server Router
 * Routes username-based URLs to page.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $uri ? trim($uri, '/') : '';

// If it's a file that exists, serve it directly
if ($path && file_exists(__DIR__ . '/' . $path) && !is_dir(__DIR__ . '/' . $path)) {
    return false; // Serve the file
}

// Proxy Vite assets in development
// Proxy Vite assets - simplified for troubleshooting
// We assume we are in dev mode if this code is active.
$viteHost = getenv('VITE_DEV_HOST') ?: '10.0.0.86';
$vitePort = 5174;

// Check if this path looks like a frontend asset
// Includes: src/, node_modules/, @vite, @react-refresh, @id, internal Vite paths
if (preg_match('/^(@.+|src\/|node_modules\/|admin-ui\/src\/)/', $path)) {
    $url = "http://{$viteHost}:{$vitePort}/" . $path;
    
    // Pass query parameters
    if (!empty($_SERVER['QUERY_STRING'])) {
        $url .= '?' . $_SERVER['QUERY_STRING'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Important: preserve content type
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    // Only output if Vite returns success (200), otherwise let PHP handle it (404)
    if ($httpCode === 200) {
        header("Content-Type: $contentType");
        header("Access-Control-Allow-Origin: *");
        echo $response;
        exit;
    }
    curl_close($ch);
}

// Exclude known paths that should be handled by their own files
$excludedPaths = [
    'admin',
    'api',
    'auth',
    'blog',
    'payment',
    'support',
    'demo',
    'index.php',
    'login.php',
    'signup.php',
    'editor.php',
    'about.php',
    'features.php',
    'pricing.php',
    'studio-docs',
    'studio-docs.php',
    'forgot-password.php',
    'reset-password.php',
    'verify-email.php',
    'oauth.php',
    'callback.php',
    'logout.php',
    'click.php',
    'favicon.php',
    'fontawesome.php',
    'demo-themes.php',
    'feature-comparison.html',
    'page-preview.php'
];

// Check if path starts with any excluded prefix
$isExcluded = false;
foreach ($excludedPaths as $excluded) {
    if ($path === $excluded || strpos($path, $excluded . '/') === 0) {
        $isExcluded = true;
        break;
    }
}

// If path is empty (root), serve index.php
if (empty($path) || $path === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require __DIR__ . '/index.php';
    exit;
}

// If it's an excluded path, let it fall through to normal handling
if ($isExcluded) {
    return false;
}

// Check if it's page-preview with username
if (preg_match('/^page-preview\.php\/([a-zA-Z0-9_-]{3,30})$/', $path, $matches)) {
    $_GET['username'] = $matches[1];
    require __DIR__ . '/page-preview.php';
    return true;
}

// Check if it looks like a username (alphanumeric, underscore, dash, 3-30 chars)
if (preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $path)) {
    // Route to page.php with username parameter
    $_GET['username'] = $path;
    require __DIR__ . '/page.php';
    return true;
}

// Default: let PHP handle it (will show 404 if file doesn't exist)
return false;















