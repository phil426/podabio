<?php
/**
 * Google OAuth Callback
 * Podn.Bio
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

// Debug: Log session state for troubleshooting (remove in production)
if (empty($state)) {
    error_log("OAuth Callback: No state parameter received");
    redirect('/login.php?error=' . urlencode('Missing authorization state. Please try again.'));
}

// Verify state token for CSRF protection
if (!isset($_SESSION['oauth_state'])) {
    error_log("OAuth Callback: Session oauth_state not found. Session ID: " . session_id());
    error_log("OAuth Callback: Session data: " . print_r($_SESSION, true));
    $error = 'Session expired. Please try logging in again.';
    redirect('/login.php?error=' . urlencode($error));
}

if ($state !== $_SESSION['oauth_state']) {
    error_log("OAuth Callback: State mismatch. Expected: " . $_SESSION['oauth_state'] . ", Got: " . $state);
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
    redirect($mode === 'link' && $isLoggedIn ? '/dashboard.php?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

// Exchange code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    $error = 'Failed to get access token. Please try again.';
    redirect($mode === 'link' && $isLoggedIn ? '/dashboard.php?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

// Get user info from Google
$userInfo = getGoogleUserInfo($tokenData['access_token']);

if (!$userInfo || !isset($userInfo['id'])) {
    $error = 'Failed to get user information. Please try again.';
    redirect($mode === 'link' && $isLoggedIn ? '/dashboard.php?error=' . urlencode($error) : '/login.php?error=' . urlencode($error));
}

$googleId = $userInfo['id'];
$email = $userInfo['email'];
$user = new User();

// Handle link flow (user is logged in and wants to link Google account)
if ($mode === 'link' && $isLoggedIn) {
    $linkResult = $user->linkGoogleAccount($currentUserId, $googleId, $email);
    
    if ($linkResult['success']) {
        // Check if user has a page - if yes, go to editor, otherwise dashboard
        $pageClass = new Page();
        $userPage = $pageClass->getByUserId($currentUserId);
        $successMsg = 'Google account linked successfully!';
        $redirectTo = $userPage ? '/editor.php?tab=account&success=' . urlencode($successMsg) : '/dashboard.php?success=' . urlencode($successMsg);
        redirect($redirectTo);
    } else {
        // Check if user has a page - if yes, go to editor, otherwise dashboard
        $pageClass = new Page();
        $userPage = $pageClass->getByUserId($currentUserId);
        $redirectTo = $userPage ? '/editor.php?tab=account&error=' . urlencode($linkResult['error']) : '/dashboard.php?error=' . urlencode($linkResult['error']);
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
        // Check if user has a page - if yes, go to editor, otherwise dashboard
        $pageClass = new Page();
        $userPage = $pageClass->getByUserId($result['user']['id'] ?? $existingUser['id']);
        redirect($userPage ? '/editor.php' : '/dashboard.php');
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
    // Check if user has a page - if yes, go to editor, otherwise dashboard
    $pageClass = new Page();
    $userPage = $pageClass->getByUserId($result['user']['id'] ?? null);
    redirect($userPage ? '/editor.php' : '/dashboard.php');
} else {
    redirect('/login.php?error=' . urlencode($result['error']));
}

