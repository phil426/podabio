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

if (!feature_flag('admin_new_experience')) {
    redirect('/editor.php');
    exit;
}

// Initialize SPA components
$manifestLoader = new ViteManifestLoader();
$bootstrap = new SPABootstrap();

// Get script and CSS sources
$scriptSrc = $manifestLoader->getScriptSrc();
$cssHref = $manifestLoader->getCssHref();
$isDev = $manifestLoader->isDevMode();
$devScripts = $isDev ? $manifestLoader->getDevModeScripts() : [];

// Prepare template data
$title = 'PodaBio User Dashboard';
$windowGlobals = $bootstrap->generateWindowGlobals();

// Include template
require __DIR__ . '/../templates/spa-bootstrap.php';

