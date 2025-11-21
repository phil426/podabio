<?php
/**
 * User Dashboard Entry Point
 * PodaBio - React-based admin dashboard (Lefty)
 * 
 * This file serves as the entry point for the user dashboard SPA.
 * It handles authentication, loads the Vite build manifest (production)
 * or connects to the Vite dev server (development), and bootstraps
 * the React application with necessary window globals.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/spa-config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/feature-flags.php';
require_once __DIR__ . '/../classes/ViteManifestLoader.php';
require_once __DIR__ . '/../classes/SPABootstrap.php';

requireAuth();

// Lefty is now the only admin panel - always use it
$_SESSION['admin_panel'] = 'lefty';

// REMOVED: admin_new_experience feature flag check - editor.php is archived, Lefty is the only admin panel

// Initialize SPA components
$manifestLoader = new ViteManifestLoader();
$bootstrap = new SPABootstrap();

// Get script and CSS sources
$scriptSrc = $manifestLoader->getScriptSrc();
$cssHref = $manifestLoader->getCssHref();
$isDev = $manifestLoader->isDevMode();
$devScripts = $isDev ? $manifestLoader->getDevModeScripts() : [];

// CRITICAL: If no script source is available, something is wrong - redirect to login
if (empty($scriptSrc)) {
    // Force logout and redirect
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    redirect('/login.php?message=' . urlencode('Unable to load admin dashboard. Please log in again.'));
    exit;
}

// WARNING: If dev server is not running and we're using production build, 
// the production build might be stale. In development, always run Vite dev server.
if (!$isDev && !$manifestLoader->isDevServerRunning()) {
    // Check if production build exists and is recent (within last hour)
    $manifestPath = getSPAManifestPath();
    if (file_exists($manifestPath)) {
        $manifestAge = time() - filemtime($manifestPath);
        // If manifest is older than 1 hour, warn (but don't block - might be intentional in production)
        if ($manifestAge > 3600) {
            error_log("WARNING: Using stale production build. Manifest is " . round($manifestAge / 3600, 1) . " hours old. Run 'npm run build' in admin-ui/ to update.");
        }
    }
}

// Prepare template data
$title = 'PodaBio User Dashboard';
$windowGlobals = $bootstrap->generateWindowGlobals();

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Include template
require __DIR__ . '/../templates/spa-bootstrap.php';

