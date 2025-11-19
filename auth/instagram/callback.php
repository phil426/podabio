<?php
/**
 * Instagram OAuth Callback
 * PodaBio
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../config/instagram.php';

if (!isLoggedIn()) {
    redirect('/login.php?error=' . urlencode('Please log in to connect Instagram'));
    exit;
}

$error = '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$errorParam = $_GET['error'] ?? '';
$errorDescription = $_GET['error_description'] ?? '';

// Check for Instagram OAuth errors
if (!empty($errorParam)) {
    $error = 'Instagram authorization failed: ' . $errorParam;
    if (!empty($errorDescription)) {
        $error .= ' - ' . $errorDescription;
    }
    error_log("Instagram OAuth error: $errorParam - $errorDescription");
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

// Check if Instagram is configured
if (empty(INSTAGRAM_APP_ID) || empty(INSTAGRAM_APP_SECRET)) {
    $error = 'Instagram integration is not configured. Please contact support.';
    error_log('Instagram OAuth: App ID or Secret not configured');
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

// Verify state token for CSRF protection
if (empty($state)) {
    $error = 'Missing authorization state. Please try again.';
    error_log('Instagram OAuth: No state parameter received');
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

if (!isset($_SESSION['instagram_oauth_state'])) {
    $error = 'Session expired. Please try again.';
    error_log('Instagram OAuth: Session state not found. Session ID: ' . session_id());
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

if ($state !== $_SESSION['instagram_oauth_state']) {
    $error = 'Invalid authorization request. Please try again.';
    error_log('Instagram OAuth: State mismatch. Expected: ' . $_SESSION['instagram_oauth_state'] . ', Got: ' . $state);
    unset($_SESSION['instagram_oauth_state']);
    unset($_SESSION['instagram_oauth_mode']);
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

// Get OAuth mode
$mode = $_SESSION['instagram_oauth_mode'] ?? 'link';
$currentUserId = getUserId();

// Clear OAuth state
unset($_SESSION['instagram_oauth_state']);
unset($_SESSION['instagram_oauth_mode']);

if (empty($code)) {
    $error = 'Authorization failed. No code received.';
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

// Exchange code for short-lived access token
$tokenData = getInstagramAccessToken($code);

if (!$tokenData) {
    $error = 'Failed to get access token. Please check your Instagram app configuration.';
    error_log('Instagram OAuth: getInstagramAccessToken returned null');
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

if (!isset($tokenData['access_token'])) {
    $errorMsg = $tokenData['error_message'] ?? $tokenData['error'] ?? 'Unknown error';
    $error = 'Failed to get access token: ' . (is_string($errorMsg) ? $errorMsg : json_encode($errorMsg));
    error_log('Instagram OAuth: Token exchange failed - ' . json_encode($tokenData));
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

$shortLivedToken = $tokenData['access_token'];
$userId = $tokenData['user_id'] ?? null;

// Exchange short-lived token for long-lived token (valid for 60 days)
$longLivedData = getInstagramLongLivedToken($shortLivedToken);

if (!$longLivedData) {
    $error = 'Failed to exchange for long-lived token. Please try again.';
    error_log('Instagram OAuth: getInstagramLongLivedToken returned null');
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

if (!isset($longLivedData['access_token'])) {
    $errorMsg = $longLivedData['error_message'] ?? $longLivedData['error'] ?? 'Unknown error';
    $error = 'Failed to get long-lived token: ' . (is_string($errorMsg) ? $errorMsg : json_encode($errorMsg));
    error_log('Instagram OAuth: Long-lived token exchange failed - ' . json_encode($longLivedData));
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

$longLivedToken = $longLivedData['access_token'];
$expiresIn = $longLivedData['expires_in'] ?? 5184000; // Default 60 days in seconds
$expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

// Get user info from Instagram
$userInfo = getInstagramUserInfo($longLivedToken);

if (!$userInfo || !isset($userInfo['id'])) {
    $error = 'Failed to get user information. Please try again.';
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
    exit;
}

$instagramUserId = $userInfo['id'];
$instagramUsername = $userInfo['username'] ?? '';

// Save Instagram connection to user account
try {
    executeQuery(
        "UPDATE users SET 
            instagram_user_id = ?,
            instagram_access_token = ?,
            instagram_token_expires_at = ?
        WHERE id = ?",
        [$instagramUserId, $longLivedToken, $expiresAt, $currentUserId]
    );
    
    $successMsg = 'Instagram account connected successfully!';
    redirect('/admin/userdashboard.php#/integrations?success=' . urlencode($successMsg));
} catch (PDOException $e) {
    error_log("Instagram connection failed: " . $e->getMessage());
    $error = 'Failed to save Instagram connection. Please try again.';
    redirect('/admin/userdashboard.php#/integrations?error=' . urlencode($error));
}

