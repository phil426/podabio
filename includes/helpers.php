<?php
/**
 * Helper Functions
 * Podn.Bio
 */

/**
 * Sanitize output for HTML
 * @param string $string
 * @return string
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Check token expiry
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 * @param string $url
 * @return bool
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Format date
 * @param string|DateTime $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Time ago format
 * @param string|DateTime $date
 * @return string
 */
function timeAgo($date) {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'just now';
    }
}

/**
 * Redirect to URL
 * @param string $url
 * @param int $code
 */
function redirect($url, $code = 302) {
    header("Location: $url", true, $code);
    exit;
}

/**
 * Get current URL
 * @return string
 */
function currentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 * @return string
 */
function baseUrl() {
    return APP_URL;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get logged in user ID
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get logged in user
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user === null) {
        $user = fetchOne("SELECT * FROM users WHERE id = ?", [getUserId()]);
    }
    return $user;
}

/**
 * Check if user has active subscription
 * @param string $plan Minimum plan required
 * @return bool
 */
function hasSubscription($plan = PLAN_FREE) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $subscription = fetchOne(
        "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    if (!$subscription) {
        return $plan === PLAN_FREE;
    }
    
    $planHierarchy = [PLAN_FREE => 0, PLAN_PREMIUM => 1, PLAN_PRO => 2];
    $userPlan = $planHierarchy[$subscription['plan_type']] ?? 0;
    $requiredPlan = $planHierarchy[$plan] ?? 0;
    
    return $userPlan >= $requiredPlan;
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Slugify string
 * @param string $string
 * @return string
 */
function slugify($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate string
 * @param string $string
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Send email
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $fromEmail
 * @param string $fromName
 * @return bool
 */
function sendEmail($to, $subject, $message, $fromEmail = null, $fromName = null) {
    $fromEmail = $fromEmail ?? 'noreply@' . parse_url(APP_URL, PHP_URL_HOST);
    $fromName = $fromName ?? APP_NAME;
    
    // Headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email using PHP mail() function
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Send verification email
 * @param string $email
 * @param string $token
 * @return bool
 */
function sendVerificationEmail($email, $token) {
    $verificationUrl = APP_URL . '/verify-email.php?token=' . urlencode($token);
    
    $subject = 'Verify Your ' . APP_NAME . ' Account';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #f9fafb; padding: 30px; }
            .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . h(APP_NAME) . '</h1>
            </div>
            <div class="content">
                <h2>Verify Your Email Address</h2>
                <p>Thank you for signing up! Please verify your email address by clicking the button below:</p>
                <p style="text-align: center;">
                    <a href="' . h($verificationUrl) . '" class="button">Verify Email Address</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #667eea;">' . h($verificationUrl) . '</p>
                <p>This verification link will expire in 24 hours.</p>
                <p>If you did not create an account, please ignore this email.</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . h(APP_NAME) . '. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($email, $subject, $message);
}

/**
 * Get Font Awesome icon HTML
 * Requires Font Awesome to be loaded in the page
 * @param string $icon Icon name (e.g., 'user', 'home', 'cog', 'envelope')
 * @param string $style Icon style: 'solid' (default), 'regular', 'brands'
 * @param string $class Additional CSS classes
 * @return string HTML for icon
 */
function fa_icon($icon, $style = 'solid', $class = '') {
    $styleClass = 'fa-' . $style;
    $iconClass = 'fa-' . $icon;
    $classes = trim($styleClass . ' ' . $iconClass . ' ' . $class);
    return '<i class="' . h($classes) . '" aria-hidden="true"></i>';
}

/**
 * Safe JSON parsing for theme data with error handling
 * @param string $json JSON string to parse
 * @param mixed $default Default value if parsing fails
 * @return mixed Parsed data or default value
 */
function parseThemeJson($json, $default = []) {
    if (empty($json)) {
        return $default;
    }
    
    // If already an array, return as-is
    if (is_array($json)) {
        return $json;
    }
    
    // Try to decode JSON
    $decoded = json_decode($json, true);
    
    // Check for JSON errors
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Return default on error
    return $default;
}

/**
 * Get theme colors for a page
 * Wrapper function for Theme class
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Colors array with primary, secondary, accent keys
 */
function getThemeColors($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getThemeColors($page, $theme);
}

/**
 * Get theme fonts for a page
 * Wrapper function for Theme class
 * @param array $page Page data array
 * @param array|null $theme Optional theme data array
 * @return array Fonts array with heading, body keys
 */
function getThemeFonts($page, $theme = null) {
    require_once __DIR__ . '/../classes/Theme.php';
    $themeObj = new Theme();
    return $themeObj->getThemeFonts($page, $theme);
}

/**
 * Get available Google Fonts list
 * @return array Associative array of font values => display names
 */
function getGoogleFontsList() {
    return [
        'Inter' => 'Inter',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Montserrat' => 'Montserrat',
        'Poppins' => 'Poppins',
        'Raleway' => 'Raleway',
        'Source Sans Pro' => 'Source Sans Pro',
        'Playfair Display' => 'Playfair Display',
        'Merriweather' => 'Merriweather',
        'Nunito' => 'Nunito',
        'Oswald' => 'Oswald',
        'PT Sans' => 'PT Sans',
        'Ubuntu' => 'Ubuntu',
        'Crimson Text' => 'Crimson Text',
        'Roboto Condensed' => 'Roboto Condensed',
        'Lora' => 'Lora',
        'Noto Sans' => 'Noto Sans',
        'PT Serif' => 'PT Serif',
        'Work Sans' => 'Work Sans',
        'Fira Sans' => 'Fira Sans',
        'Bebas Neue' => 'Bebas Neue',
        'Dancing Script' => 'Dancing Script',
        'Pacifico' => 'Pacifico',
        'Anton' => 'Anton',
        'Barlow' => 'Barlow',
        'Comfortaa' => 'Comfortaa',
        'Exo 2' => 'Exo 2',
        'Indie Flower' => 'Indie Flower',
        'Josefin Sans' => 'Josefin Sans',
        'Libre Baskerville' => 'Libre Baskerville',
        'Mukta' => 'Mukta',
        'Noto Serif' => 'Noto Serif',
        'Quicksand' => 'Quicksand',
        'Rubik' => 'Rubik',
        'Titillium Web' => 'Titillium Web',
        'Varela Round' => 'Varela Round',
        'Arvo' => 'Arvo',
        'Bitter' => 'Bitter',
        'Cabin' => 'Cabin',
        'Dosis' => 'Dosis',
        'EB Garamond' => 'EB Garamond',
        'Fjalla One' => 'Fjalla One',
        'Hind' => 'Hind',
        'Inconsolata' => 'Inconsolata',
        'Karla' => 'Karla',
        'Libre Franklin' => 'Libre Franklin',
        'Maven Pro' => 'Maven Pro',
        'Nunito Sans' => 'Nunito Sans',
        'Orbitron' => 'Orbitron',
        'Rajdhani' => 'Rajdhani'
    ];
}

