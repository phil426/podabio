<?php
/**
 * Meta (Facebook/Instagram/Threads) App Configuration
 * PodaBio
 * 
 * Meta App Dashboard: https://developers.facebook.com/apps/
 * App Name: PodaBio
 * App ID: 738310402631107
 * 
 * Last Updated: December 2024
 * 
 * IMPORTANT NOTES:
 * - The Threads App Secret is masked in the Meta Dashboard
 *   To retrieve it: Go to Meta App Dashboard > App Settings > Basic > Threads app secret > Show
 *   Then update THREADS_APP_SECRET below
 * 
 * - Privacy Policy and Terms URLs are correctly configured:
 *   Privacy: https://www.poda.bio/privacy.php
 *   Terms: https://www.poda.bio/terms.php
 */

// ============================================================================
// FACEBOOK APP CONFIGURATION
// ============================================================================

// Facebook App ID
define('FACEBOOK_APP_ID', '738310402631107');

// Facebook App Secret
// WARNING: Keep this secret secure. Do not commit to public repositories.
define('FACEBOOK_APP_SECRET', '[REDACTED]');

// App Display Name
define('FACEBOOK_APP_NAME', 'PodaBio');

// App Namespace
define('FACEBOOK_APP_NAMESPACE', 'podabio');

// App Domains (comma-separated if multiple)
define('FACEBOOK_APP_DOMAINS', 'www.poda.bio');

// Contact Email
define('FACEBOOK_CONTACT_EMAIL', 'phil@redwoodempiremedia.com');

// Privacy Policy URL
define('FACEBOOK_PRIVACY_POLICY_URL', 'https://www.poda.bio/privacy.php');

// Terms of Service URL
define('FACEBOOK_TERMS_URL', 'https://www.poda.bio/terms.php');

// App Category
define('FACEBOOK_APP_CATEGORY', 'Utility & productivity');

// ============================================================================
// INSTAGRAM CONFIGURATION
// ============================================================================
// Note: Instagram uses the same App ID and App Secret as Facebook
// Instagram Basic Display API uses the Facebook App credentials

define('INSTAGRAM_APP_ID', FACEBOOK_APP_ID);
define('INSTAGRAM_APP_SECRET', FACEBOOK_APP_SECRET);

// Instagram OAuth Redirect URI
define('INSTAGRAM_REDIRECT_URI', APP_URL . '/auth/instagram/callback.php');

// Instagram API endpoints
define('INSTAGRAM_AUTH_URL', 'https://api.instagram.com/oauth/authorize');
define('INSTAGRAM_TOKEN_URL', 'https://api.instagram.com/oauth/access_token');
define('INSTAGRAM_GRAPH_API_URL', 'https://graph.instagram.com');

// ============================================================================
// THREADS CONFIGURATION
// ============================================================================

// Threads App ID
define('THREADS_APP_ID', '1561760304877775');

// Threads App Secret
// NOTE: This is masked in the dashboard. You'll need to retrieve it or reset it.
// To view: Go to Meta App Dashboard > App Settings > Basic > Threads app secret > Show
define('THREADS_APP_SECRET', ''); // TODO: Set this value from Meta Dashboard

// Threads Display Name
define('THREADS_DISPLAY_NAME', 'PodaBio');

// ============================================================================
// FACEBOOK LOGIN CONFIGURATION
// ============================================================================

// Facebook Login Redirect URI
define('FACEBOOK_LOGIN_REDIRECT_URI', APP_URL . '/auth/facebook/callback.php');

// Facebook OAuth Scopes
define('FACEBOOK_LOGIN_SCOPES', 'email,public_profile');

// Facebook Graph API Version
define('FACEBOOK_GRAPH_API_VERSION', 'v18.0');

// Facebook Graph API Base URL
define('FACEBOOK_GRAPH_API_URL', 'https://graph.facebook.com/' . FACEBOOK_GRAPH_API_VERSION);

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get Facebook OAuth authorization URL
 * @param string $mode 'login' or 'link' - determines the OAuth flow
 * @return string
 */
function getFacebookAuthUrl($mode = 'login') {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/../includes/session.php';
    }
    
    // Generate state token for CSRF protection
    $stateToken = generateToken(32);
    $_SESSION['facebook_oauth_state'] = $stateToken;
    $_SESSION['facebook_oauth_mode'] = $mode;
    
    $params = [
        'client_id' => FACEBOOK_APP_ID,
        'redirect_uri' => FACEBOOK_LOGIN_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => FACEBOOK_LOGIN_SCOPES,
        'state' => $stateToken
    ];
    
    return 'https://www.facebook.com/' . FACEBOOK_GRAPH_API_VERSION . '/dialog/oauth?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 * @param string $code
 * @return array|null
 */
function getFacebookAccessToken($code) {
    if (empty(FACEBOOK_APP_ID) || empty(FACEBOOK_APP_SECRET)) {
        error_log('Facebook OAuth: App ID or Secret not configured');
        return ['error' => 'Facebook not configured'];
    }
    
    $data = [
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_LOGIN_REDIRECT_URI,
        'code' => $code
    ];
    
    $url = FACEBOOK_GRAPH_API_URL . '/oauth/access_token?' . http_build_query($data);
    
    $options = [
        'http' => [
            'header' => "Accept: application/json\r\n",
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log('Facebook OAuth: HTTP request failed');
        return ['error' => 'HTTP request failed'];
    }
    
    $decoded = json_decode($response, true);
    
    if (isset($decoded['error'])) {
        error_log('Facebook OAuth API error: ' . json_encode($decoded));
        return $decoded;
    }
    
    return $decoded;
}

/**
 * Get user info from Facebook
 * @param string $accessToken
 * @return array|null
 */
function getFacebookUserInfo($accessToken) {
    $url = FACEBOOK_GRAPH_API_URL . '/me?' . http_build_query([
        'fields' => 'id,name,email,picture',
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
    
    // Load helpers for getCurrentBaseUrl()
    if (!function_exists('getCurrentBaseUrl')) {
        require_once __DIR__ . '/../includes/helpers.php';
    }
    
    // Generate state token for CSRF protection
    $stateToken = generateToken(32);
    $_SESSION['instagram_oauth_state'] = $stateToken;
    $_SESSION['instagram_oauth_mode'] = $mode;
    
    // Use dynamic redirect URI based on current host (for dev/prod flexibility)
    $redirectUri = getCurrentBaseUrl() . '/auth/instagram/callback.php';
    
    $scopes = 'user_profile,user_media';
    
    $params = [
        'client_id' => INSTAGRAM_APP_ID,
        'redirect_uri' => $redirectUri,
        'scope' => $scopes,
        'response_type' => 'code',
        'state' => $stateToken
    ];
    
    return INSTAGRAM_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for Instagram access token
 * @param string $code
 * @return array|null
 */
function getInstagramAccessToken($code) {
    if (empty(INSTAGRAM_APP_ID) || empty(INSTAGRAM_APP_SECRET)) {
        error_log('Instagram OAuth: App ID or Secret not configured');
        return ['error' => 'Instagram not configured'];
    }
    
    $data = [
        'client_id' => INSTAGRAM_APP_ID,
        'client_secret' => INSTAGRAM_APP_SECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => INSTAGRAM_REDIRECT_URI,
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
    $response = @file_get_contents(INSTAGRAM_TOKEN_URL, false, $context);
    
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
    $url = INSTAGRAM_GRAPH_API_URL . '/access_token?' . http_build_query([
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
    $url = INSTAGRAM_GRAPH_API_URL . '/me?' . http_build_query([
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

