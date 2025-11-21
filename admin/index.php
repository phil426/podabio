<?php
/**
 * Admin Panel - Dashboard (DEPRECATED)
 * PodaBio - Forces logout and redirects to login
 * 
 * This file is kept for backward compatibility but forces users to log out and log back in.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Force logout - clear all session data
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirect to login with message
redirect('/login.php?message=' . urlencode('The old admin panel has been retired. Please log in to access the new dashboard.'));
exit;

