<?php
/**
 * Instagram Configuration
 * Podn.Bio
 * 
 * To set up Instagram integration:
 * 1. Go to https://developers.facebook.com/apps/
 * 2. Create a new app or use existing
 * 3. Add "Instagram Basic Display" product
 * 4. Configure OAuth redirect URIs
 * 5. Get your App ID and App Secret
 * 6. Generate a long-lived access token (valid for 60 days)
 * 7. Update the values below
 * 
 * Documentation: https://developers.facebook.com/docs/instagram-basic-display-api
 */

// Instagram App ID (from Facebook Developer Console)
define('INSTAGRAM_APP_ID', '');

// Instagram App Secret (from Facebook Developer Console)
define('INSTAGRAM_APP_SECRET', '');

// Long-lived access token (generated via OAuth flow)
// Note: Long-lived tokens expire after 60 days. You'll need to refresh them.
define('INSTAGRAM_ACCESS_TOKEN', '');

/**
 * Get Instagram OAuth authorization URL
 * @param string $mode 'link' - determines the OAuth flow
 * @return string
 */
function getInstagramAuthUrl($mode = 'link') {
    // Check if Instagram is configured
    if (empty(INSTAGRAM_APP_ID) || empty(INSTAGRAM_APP_SECRET)) {
        error_log('Instagram OAuth: App ID or Secret not configured');
        return '';
    }
    
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/../includes/session.php';
    }
    
    // Generate state token for CSRF protection
    $stateToken = generateToken(32);
    $_SESSION['instagram_oauth_state'] = $stateToken;
    $_SESSION['instagram_oauth_mode'] = $mode;
    
    $redirectUri = APP_URL . '/auth/instagram/callback.php';
    $scopes = 'user_profile,user_media';
    
    $params = [
        'client_id' => INSTAGRAM_APP_ID,
        'redirect_uri' => $redirectUri,
        'scope' => $scopes,
        'response_type' => 'code',
        'state' => $stateToken
    ];
    
    return 'https://api.instagram.com/oauth/authorize?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 * @param string $code
 * @return array|null
 */
function getInstagramAccessToken($code) {
    if (empty(INSTAGRAM_APP_ID) || empty(INSTAGRAM_APP_SECRET)) {
        error_log('Instagram OAuth: App ID or Secret not configured');
        return ['error' => 'Instagram not configured'];
    }
    
    $redirectUri = APP_URL . '/auth/instagram/callback.php';
    
    $data = [
        'client_id' => INSTAGRAM_APP_ID,
        'client_secret' => INSTAGRAM_APP_SECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents('https://api.instagram.com/oauth/access_token', false, $context);
    
    if ($response === false) {
        $lastError = error_get_last();
        error_log('Instagram OAuth: HTTP request failed - ' . ($lastError['message'] ?? 'Unknown error'));
        return ['error' => 'HTTP request failed'];
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Instagram OAuth: JSON decode error - ' . json_last_error_msg() . ' Response: ' . substr($response, 0, 500));
        return ['error' => 'Invalid response from Instagram'];
    }
    
    // Check for error in response
    if (isset($decoded['error'])) {
        error_log('Instagram OAuth API error: ' . json_encode($decoded));
    }
    
    return $decoded;
}

/**
 * Exchange short-lived token for long-lived token
 * @param string $shortLivedToken
 * @return array|null
 */
function getInstagramLongLivedToken($shortLivedToken) {
    $url = 'https://graph.instagram.com/access_token?' . http_build_query([
        'grant_type' => 'ig_exchange_token',
        'client_secret' => INSTAGRAM_APP_SECRET,
        'access_token' => $shortLivedToken
    ]);
    
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

/**
 * Get user info from Instagram
 * @param string $accessToken
 * @return array|null
 */
function getInstagramUserInfo($accessToken) {
    $url = 'https://graph.instagram.com/me?' . http_build_query([
        'fields' => 'id,username',
        'access_token' => $accessToken
    ]);
    
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






