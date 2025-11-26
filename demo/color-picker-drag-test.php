<?php
/**
 * Color Picker Drag Test Entry Point
 * Simple test page for debugging color picker dragging issues
 * 
 * Access at: http://localhost:8080/demo/color-picker-drag-test.php
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

// Initialize SPA components
$manifestLoader = new ViteManifestLoader();
$bootstrap = new SPABootstrap();

// Get script and CSS sources
$scriptSrc = $manifestLoader->getScriptSrc();
$cssHref = $manifestLoader->getCssHref();
$isDev = $manifestLoader->isDevMode();
$devScripts = $isDev ? $manifestLoader->getDevModeScripts() : [];

// CRITICAL: If no script source is available, something is wrong
if (empty($scriptSrc)) {
    die('Unable to load React app. Please check Vite dev server or build.');
}

// Prepare template data
$title = 'Color Picker Drag Test';
$windowGlobals = $bootstrap->generateWindowGlobals();

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Include template with a redirect script to ensure React Router matches the route
?>
<script>
  // After React app loads, ensure we're on the correct route
  window.addEventListener('DOMContentLoaded', function() {
    // Small delay to let React Router initialize
    setTimeout(function() {
      const currentPath = window.location.pathname;
      // If we're on the PHP file path, React Router should handle it, but if not, redirect
      if (currentPath === '/demo/color-picker-drag-test.php' && window.location.hash === '') {
        // React Router should already handle this, but just in case
        console.log('Test page loaded at:', currentPath);
      }
    }, 100);
  });
</script>
<?php
require __DIR__ . '/../templates/spa-bootstrap.php';

