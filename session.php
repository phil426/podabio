<?php
/**
 * Session Management
 * PodaBio
 */

// Load constants if not already loaded
if (!defined('SESSION_LIFETIME')) {
    require_once __DIR__ . '/../config/constants.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Use 'Lax' for SameSite to allow OAuth redirects to work properly
    // 'Strict' blocks cookies on cross-site redirects (like OAuth)
    // Set cookie path to root so it works across all subdirectories
    session_start([
        'cookie_lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600,
        'cookie_httponly' => true,
        'cookie_secure' => false, // Set to true in production with HTTPS
        'cookie_samesite' => 'Lax', // Changed from 'Strict' to allow OAuth redirects
        'cookie_path' => '/', // Ensure cookie is available site-wide
        'name' => 'PHPSESSID' // Explicit session name
    ]);
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerateSession() {
    session_regenerate_id(true);
}

/**
 * Destroy session
 */
function destroySession() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

