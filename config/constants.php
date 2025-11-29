<?php
/**
 * Application Constants
 * Podn.Bio
 */

// Application settings
define('APP_NAME', 'Podn.Bio');
define('APP_VERSION', '2.1.0');
define('APP_URL', 'https://getphily.com'); // Change to podn.bio for production

// Server configuration
define('SERVER_IP', '156.67.73.201'); // Hostinger server IP

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Upload directories
define('UPLOAD_PROFILES', UPLOAD_PATH . '/profiles');
define('UPLOAD_BACKGROUNDS', UPLOAD_PATH . '/backgrounds');
define('UPLOAD_THUMBNAILS', UPLOAD_PATH . '/thumbnails');
define('UPLOAD_BLOG', UPLOAD_PATH . '/blog');
define('UPLOAD_MEDIA', UPLOAD_PATH . '/media'); // User media library

// URL paths
define('PUBLIC_URL', APP_URL);
define('UPLOAD_URL', APP_URL . '/uploads');
define('ASSETS_URL', APP_URL . '/assets');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('VERIFICATION_TOKEN_EXPIRY', 86400); // 24 hours
define('RESET_TOKEN_EXPIRY', 3600); // 1 hour

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Image dimensions
define('PROFILE_IMAGE_WIDTH', 400);
define('PROFILE_IMAGE_HEIGHT', 400);
define('THUMBNAIL_WIDTH', 400);
define('THUMBNAIL_HEIGHT', 400);
define('BACKGROUND_IMAGE_MAX_WIDTH', 1920);
define('BACKGROUND_IMAGE_MAX_HEIGHT', 1080);

// Pagination
define('ITEMS_PER_PAGE', 20);
define('EPISODES_PER_PAGE', 10);
define('MEDIA_PER_PAGE', 24);

// Social media platforms
define('SOCIAL_PLATFORMS', [
    'facebook' => 'Facebook',
    'twitter' => 'Twitter / X',
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
    'snapchat' => 'Snapchat',
    'pinterest' => 'Pinterest',
    'reddit' => 'Reddit',
    'discord' => 'Discord',
    'twitch' => 'Twitch',
    'github' => 'GitHub',
    'behance' => 'Behance',
    'dribbble' => 'Dribbble',
    'medium' => 'Medium'
]);

// Podcast platforms (for social icons)
define('PODCAST_PLATFORMS', [
    'apple_podcasts' => 'Apple Podcasts',
    'spotify' => 'Spotify',
    'youtube_music' => 'YouTube Music',
    'iheart_radio' => 'iHeart Radio',
    'amazon_music' => 'Amazon Music'
]);

// Email service providers
define('EMAIL_SERVICES', [
    'mailchimp' => 'Mailchimp',
    'constant_contact' => 'Constant Contact',
    'convertkit' => 'ConvertKit',
    'aweber' => 'AWeber',
    'mailerlite' => 'MailerLite',
    'sendinblue' => 'SendinBlue/Brevo'
]);

// Subscription plans
define('PLAN_FREE', 'free');
define('PLAN_PREMIUM', 'premium');
define('PLAN_PRO', 'pro');

// Theme defaults
define('THEME_DEFAULT_PRIMARY_COLOR', '#000000');
define('THEME_DEFAULT_SECONDARY_COLOR', '#ffffff');
define('THEME_DEFAULT_ACCENT_COLOR', '#0066ff');
define('THEME_DEFAULT_FONT', 'Inter');

// Timezone
date_default_timezone_set('UTC');

// Load local overrides if they exist (for local development)
// This file is gitignored and allows machine-specific configuration
if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}

// PHP Configuration Notes
// - Recommended PHP version: 8.3
// - Minimum PHP version: 8.0
// - See PHP_CONFIGURATION.md for detailed configuration recommendations

