<?php
/**
 * Authentication Helper
 * PodaBio
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Require user to be logged in
 * Redirects to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $currentUrl = currentUrl();
        redirect('/login.php?redirect=' . urlencode($currentUrl));
    }
    
    // FORCE Lefty - Always set admin_panel to 'lefty' and redirect old admin pages
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $oldAdminPages = [
        '/admin/index.php',
        '/admin/classic.php',
        '/admin/select-panel.php',
        '/admin/analytics.php',
        '/admin/pages.php',
        '/admin/blog.php',
        '/admin/users.php',
        '/admin/settings.php',
        '/admin/support.php',
        '/admin/subscriptions.php'
    ];
    
    // If accessing any old admin page, force redirect to Lefty
    foreach ($oldAdminPages as $oldPage) {
        if (strpos($requestUri, $oldPage) !== false) {
            $_SESSION['admin_panel'] = 'lefty';
            redirect('/admin/userdashboard.php');
            exit;
        }
    }
    
    // Always ensure admin_panel is set to 'lefty'
    $_SESSION['admin_panel'] = 'lefty';
}

/**
 * Require email verification
 * Redirects to verification page if not verified
 */
function requireEmailVerification() {
    requireAuth();
    
    $user = getCurrentUser();
    if (!$user || !$user['email_verified']) {
        redirect('/verify-email-required.php');
    }
}

/**
 * Check if user can unlink Google account (has password)
 * @param int $userId
 * @return bool
 */
function canUnlinkGoogle($userId) {
    $user = getCurrentUser();
    if (!$user || $user['id'] != $userId) {
        return false;
    }
    
    return !empty($user['password_hash']) && !empty($user['google_id']);
}

/**
 * Check if user can remove password (has Google linked)
 * @param int $userId
 * @return bool
 */
function canRemovePassword($userId) {
    $user = getCurrentUser();
    if (!$user || $user['id'] != $userId) {
        return false;
    }
    
    return !empty($user['google_id']) && !empty($user['password_hash']);
}

/**
 * Check if user has multiple login methods
 * @param int $userId
 * @return bool
 */
function hasMultipleLoginMethods($userId) {
    $user = getCurrentUser();
    if (!$user || $user['id'] != $userId) {
        return false;
    }
    
    $hasPassword = !empty($user['password_hash']);
    $hasGoogle = !empty($user['google_id']);
    
    return $hasPassword && $hasGoogle;
}

/**
 * Get account login methods
 * @param int $userId
 * @return array Array of login method strings ('password', 'google')
 */
function getAccountLoginMethods($userId) {
    $user = getCurrentUser();
    if (!$user || $user['id'] != $userId) {
        return [];
    }
    
    $methods = [];
    if (!empty($user['password_hash'])) {
        $methods[] = 'password';
    }
    if (!empty($user['google_id'])) {
        $methods[] = 'google';
    }
    
    return $methods;
}


