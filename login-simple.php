<?php
/**
 * Simplified Login Page - Fallback version
 * This version loads more defensively to prevent 500 errors
 */

// Set error handling FIRST
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to show error page
function showErrorPage($message = 'An error occurred. Please try again later.') {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - PodaBio</title>
        <style>
            body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #1a1a1a; color: #fff; }
            .error-container { text-align: center; padding: 2rem; max-width: 500px; }
            h1 { color: #ff4444; }
            p { color: #ccc; line-height: 1.6; }
            a { color: #00ff7f; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>System Error</h1>
            <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a href="/">Return to Home</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Try to load files with individual error checking
$errors = [];

// Load constants
if (!file_exists(__DIR__ . '/config/constants.php')) {
    showErrorPage('Configuration file not found.');
}
require_once __DIR__ . '/config/constants.php';

// Load session
if (!file_exists(__DIR__ . '/includes/session.php')) {
    showErrorPage('Session file not found.');
}
require_once __DIR__ . '/includes/session.php';

// Load helpers
if (!file_exists(__DIR__ . '/includes/helpers.php')) {
    showErrorPage('Helper file not found.');
}
require_once __DIR__ . '/includes/helpers.php';

// Load User class
if (!file_exists(__DIR__ . '/classes/User.php')) {
    showErrorPage('User class not found.');
}
require_once __DIR__ . '/classes/User.php';

// Load TwoFactorAuth class
if (!file_exists(__DIR__ . '/classes/TwoFactorAuth.php')) {
    showErrorPage('TwoFactorAuth class not found.');
}
require_once __DIR__ . '/classes/TwoFactorAuth.php';

// OAuth config is optional
$oauthAvailable = false;
if (file_exists(__DIR__ . '/config/oauth.php')) {
    require_once __DIR__ . '/config/oauth.php';
    $oauthAvailable = function_exists('getGoogleAuthUrl');
}

// If we get here, all required files loaded successfully
// Now load the rest of login.php logic...

