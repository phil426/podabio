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

// If it's an excluded path or empty, let it fall through to normal handling
if ($isExcluded || empty($path)) {
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















