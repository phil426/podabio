<?php
/**
 * OAuth Configuration
 * Podn.Bio
 */

// Google OAuth 2.0 Settings
// Developer Account: phil@redwoodempiremedia.com
// Project: My First Project (fine-glow-467202-u1)
// OAuth Client: Podn.Bio Web Client
// Redirect URI: https://getphily.com/auth/google/callback.php
// Created: October 30, 2025
// Status: Configured and ready to use

define('GOOGLE_CLIENT_ID', '1059272027103-ic3mvq2p7guag9ektq8b982lov5fah7j.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '[REDACTED]');
define('GOOGLE_REDIRECT_URI', APP_URL . '/auth/google/callback.php');

// Google OAuth endpoints
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

/**
 * Get Google OAuth authorization URL
 * @param string $mode 'login' or 'link' - determines the OAuth flow
 * @return string
 */
function getGoogleAuthUrl($mode = 'login') {
    // Ensure session is started (should already be started, but check anyway)
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/../includes/session.php';
    }
    
    // Generate state token for CSRF protection and flow tracking
    $stateToken = generateToken(32);
    $_SESSION['oauth_state'] = $stateToken;
    $_SESSION['oauth_mode'] = $mode; // Store mode in session
    
    // Ensure session data is written (but don't close the session)
    // The session will be automatically saved when the script ends
    
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
        'state' => $stateToken
    ];
    
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 * @param string $code
 * @return array|null
 */
function getGoogleAccessToken($code) {
    $data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(GOOGLE_TOKEN_URL, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Get user info from Google
 * @param string $accessToken
 * @return array|null
 */
function getGoogleUserInfo($accessToken) {
    $url = GOOGLE_USERINFO_URL . '?access_token=' . $accessToken;
    
    $options = [
        'http' => [
            'header' => "Accept: application/json\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

