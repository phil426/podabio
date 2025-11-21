<?php
/**
 * Google OAuth Callback
 * PodaBio
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Page.php';
require_once __DIR__ . '/../../config/oauth.php';

$error = '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

// Verify state token for CSRF protection
if (empty($state) || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    $error = 'Invalid authorization request. Please try again.';
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_mode']);
    redirect('/login.php?error=' . urlencode($error));
}

// Get OAuth mode (login or link)
$mode = $_SESSION['oauth_mode'] ?? 'login';
$isLoggedIn = isLoggedIn();
$currentUserId = getUserId();

// Clear OAuth state
unset($_SESSION['oauth_state']);
unset($_SESSION['oauth_mode']);

if (empty($code)) {
    $error = 'Authorization failed. No code received.';
    redirect($mode === 'link' && $isLoggedIn ? '/admin/userdashboard.php#/account/profile?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

// Exchange code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    $error = 'Failed to get access token. Please try again.';
    redirect($mode === 'link' && $isLoggedIn ? '/admin/userdashboard.php#/account/profile?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

// Get user info from Google
$userInfo = getGoogleUserInfo($tokenData['access_token']);

if (!$userInfo || !isset($userInfo['id'])) {
    $error = 'Failed to get user information. Please try again.';
    redirect($mode === 'link' && $isLoggedIn ? '/admin/userdashboard.php#/account/profile?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

$googleId = $userInfo['id'];
$email = $userInfo['email'];
$user = new User();

// Handle link flow (user is logged in and wants to link Google account)
if ($mode === 'link' && $isLoggedIn) {
    $linkResult = $user->linkGoogleAccount($currentUserId, $googleId, $email);
    
    if ($linkResult['success']) {
        // Check if user has a page - if yes, go to editor, otherwise dashboard
        $successMsg = 'Google account linked successfully!';
        $redirectTo = '/admin/userdashboard.php#/account/profile?success=' . urlencode($successMsg);
        redirect($redirectTo);
    } else {
        // Check if user has a page - if yes, go to editor, otherwise dashboard
        $redirectTo = '/admin/userdashboard.php#/account/profile?error=' . urlencode($linkResult['error']);
        redirect($redirectTo);
    }
    exit;
}

// Handle login flow
// Check if user exists with Google ID
$existingUser = fetchOne("SELECT * FROM users WHERE google_id = ?", [$googleId]);

if ($existingUser) {
    // User with Google ID exists, log them in
    $result = $user->loginWithGoogle(
        $googleId,
        $email,
        [
            'name' => $userInfo['name'] ?? '',
            'picture' => $userInfo['picture'] ?? ''
        ]
    );
    
    if ($result['success']) {
        // Redirect directly to Lefty
        $_SESSION['admin_panel'] = 'lefty';
        redirect('/admin/userdashboard.php');
    } else {
        redirect('/login.php?error=' . urlencode($result['error']));
    }
    exit;
}

// Check if email exists with password (but no Google ID)
$existingEmail = fetchOne("SELECT * FROM users WHERE email = ? AND google_id IS NULL", [$email]);

if ($existingEmail) {
    // Email exists with password but no Google account linked
    // Store Google data in session and redirect to password verification
    $_SESSION['pending_google_link'] = [
        'google_id' => $googleId,
        'email' => $email,
        'user_id' => $existingEmail['id'],
        'name' => $userInfo['name'] ?? '',
        'picture' => $userInfo['picture'] ?? ''
    ];
    
    redirect('/verify-google-link.php');
    exit;
}

// No existing account, create new user with Google
$result = $user->loginWithGoogle(
    $googleId,
    $email,
    [
        'name' => $userInfo['name'] ?? '',
        'picture' => $userInfo['picture'] ?? ''
    ]
);

if ($result['success']) {
    // Redirect directly to Lefty
    $_SESSION['admin_panel'] = 'lefty';
    redirect('/admin/userdashboard.php');
} else {
    redirect('/login.php?error=' . urlencode($result['error']));
}


