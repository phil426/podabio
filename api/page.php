<?php
/**
 * Page API Endpoint
 * Handles page update operations
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/theme-helpers.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/RSSParser.php';
require_once __DIR__ . '/../classes/Theme.php';
require_once __DIR__ . '/../classes/APIResponse.php';
require_once __DIR__ . '/../classes/WidgetStyleManager.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

$user = getCurrentUser();
$userId = $user['id'];

$page = new Page();
$userPage = $page->getByUserId($userId);

if (!$userPage) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Page not found']);
    exit;
}

$pageId = $userPage['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action !== 'get_snapshot') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $defaultTokens = require __DIR__ . '/../config/tokens.php';
    $overrides = [];
    if (!empty($userPage['token_overrides'])) {
        $decodedOverrides = json_decode($userPage['token_overrides'], true);
        if (is_array($decodedOverrides)) {
            $overrides = $decodedOverrides;
        }
    }

    $mergedTokens = array_replace_recursive($defaultTokens, $overrides);

    $widgets = array_map(function ($widget) {
        if (!empty($widget['config_data']) && is_string($widget['config_data'])) {
            $decoded = json_decode($widget['config_data'], true);
            if (is_array($decoded)) {
                $widget['config_data'] = $decoded;
            }
        }
        return $widget;
    }, $page->getAllWidgets($pageId));

    // Get active theme for resolving widget_styles and spatial_effect
    $themeClass = new Theme();
    $activeTheme = null;
    if (!empty($userPage['theme_id'])) {
        $activeTheme = $themeClass->getTheme($userPage['theme_id']);
    }

    // Resolve widget_styles (merged from page + theme using helper function)
    $resolvedWidgetStyles = getWidgetStyles($userPage, $activeTheme);
    
    // Resolve spatial_effect (merged from page + theme using helper function)
    $resolvedSpatialEffect = getSpatialEffect($userPage, $activeTheme);

    $response = [
        'success' => true,
        'page' => [
            'id' => $userPage['id'],
            'username' => $userPage['username'],
            'podcast_name' => $userPage['podcast_name'],
            'podcast_description' => $userPage['podcast_description'],
            'footer_text' => $userPage['footer_text'] ?? null,
            'footer_copyright' => $userPage['footer_copyright'] ?? null,
            'footer_privacy_link' => $userPage['footer_privacy_link'] ?? null,
            'footer_terms_link' => $userPage['footer_terms_link'] ?? null,
            'profile_visible' => isset($userPage['profile_visible']) ? (bool)$userPage['profile_visible'] : true,
            'footer_visible' => isset($userPage['footer_visible']) ? (bool)$userPage['footer_visible'] : true,
            'podcast_player_enabled' => isset($userPage['podcast_player_enabled']) ? (bool)$userPage['podcast_player_enabled'] : false,
            'profile_image_shape' => $userPage['profile_image_shape'] ?? 'circle',
            'profile_image_shadow' => $userPage['profile_image_shadow'] ?? 'subtle',
            'profile_image_size' => $userPage['profile_image_size'] ?? 'medium',
            'profile_image_border' => $userPage['profile_image_border'] ?? 'none',
            'profile_image_radius' => $userPage['profile_image_radius'] ?? null,
            'profile_image_effect' => $userPage['profile_image_effect'] ?? 'none',
            'profile_image_shadow_color' => $userPage['profile_image_shadow_color'] ?? null,
            'profile_image_shadow_intensity' => $userPage['profile_image_shadow_intensity'] ?? null,
            'profile_image_shadow_depth' => $userPage['profile_image_shadow_depth'] ?? null,
            'profile_image_shadow_blur' => $userPage['profile_image_shadow_blur'] ?? null,
            'profile_image_glow_color' => $userPage['profile_image_glow_color'] ?? null,
            'profile_image_glow_width' => $userPage['profile_image_glow_width'] ?? null,
            'profile_image_border_color' => $userPage['profile_image_border_color'] ?? null,
            'profile_image_border_width' => $userPage['profile_image_border_width'] ?? null,
            'profile_image_spacing_top' => $userPage['profile_image_spacing_top'] ?? null,
            'profile_image_spacing_bottom' => $userPage['profile_image_spacing_bottom'] ?? null,
            'name_alignment' => $userPage['name_alignment'] ?? 'center',
            'name_text_size' => $userPage['name_text_size'] ?? 'large',
            'bio_alignment' => $userPage['bio_alignment'] ?? 'center',
            'bio_text_size' => $userPage['bio_text_size'] ?? 'medium',
            'rss_feed_url' => $userPage['rss_feed_url'],
            'cover_image_url' => $userPage['cover_image_url'],
            'theme_id' => $userPage['theme_id'],
            'colors' => json_decode($userPage['colors'] ?? '', true),
            'fonts' => json_decode($userPage['fonts'] ?? '', true),
            'page_background' => $userPage['page_background'],
            'widget_background' => $userPage['widget_background'],
            'widget_border_color' => $userPage['widget_border_color'],
            'page_primary_font' => $userPage['page_primary_font'],
        'page_secondary_font' => $userPage['page_secondary_font'],
        'page_name_effect' => $userPage['page_name_effect'] ?? null,
        'profile_image' => $userPage['profile_image'] ?? null,
            'publish_status' => $userPage['publish_status'] ?? 'draft',
            'published_at' => $userPage['published_at'] ?? null,
            'scheduled_publish_at' => $userPage['scheduled_publish_at'] ?? null,
            // Add resolved widget_styles and spatial_effect (merged from page + theme)
            'widget_styles' => $resolvedWidgetStyles,
            'spatial_effect' => $resolvedSpatialEffect
        ],
        'widgets' => $widgets,
        'social_icons' => $page->getSocialIcons($pageId),
        'tokens' => $mergedTokens,
        'token_overrides' => $overrides
    ];

    echo json_encode($response);
    exit;
}

// Only accept POST requests for mutations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    error_log("API update_appearance CSRF failed. Provided token: " . ($_POST['csrf_token'] ?? 'missing') . " session token: " . ($_SESSION['csrf_token'] ?? 'unset') . " referer: " . ($_SERVER['HTTP_REFERER'] ?? 'unknown'));
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

switch ($action) {
    case 'update_settings':
        $updateData = [];
        
        if (isset($_POST['username'])) {
            $username = sanitizeInput($_POST['username']);
            // Check if username is available (if changed)
            if ($username !== $userPage['username']) {
                if (!$page->isUsernameAvailable($username)) {
                    echo json_encode(['success' => false, 'error' => 'Username already taken']);
                    exit;
                }
            }
            $updateData['username'] = $username;
        }
        
        if (isset($_POST['podcast_name'])) {
            $updateData['podcast_name'] = sanitizeInput($_POST['podcast_name']);
        }
        
        if (isset($_POST['podcast_description'])) {
            $updateData['podcast_description'] = sanitizeInput($_POST['podcast_description']);
        }

        if (isset($_POST['footer_text'])) {
            $updateData['footer_text'] = sanitizeInput($_POST['footer_text']);
        }

        if (isset($_POST['footer_copyright'])) {
            $footerCopyright = trim(sanitizeInput($_POST['footer_copyright']));
            $updateData['footer_copyright'] = !empty($footerCopyright) ? $footerCopyright : null;
        }

        if (isset($_POST['footer_privacy_link'])) {
            $footerPrivacyLink = trim(sanitizeInput($_POST['footer_privacy_link']));
            $updateData['footer_privacy_link'] = !empty($footerPrivacyLink) ? $footerPrivacyLink : null;
        }

        if (isset($_POST['footer_terms_link'])) {
            $footerTermsLink = trim(sanitizeInput($_POST['footer_terms_link']));
            $updateData['footer_terms_link'] = !empty($footerTermsLink) ? $footerTermsLink : null;
        }

        if (isset($_POST['profile_visible'])) {
            $updateData['profile_visible'] = (int)$_POST['profile_visible'];
        }

        if (isset($_POST['footer_visible'])) {
            $updateData['footer_visible'] = (int)$_POST['footer_visible'];
        }
        
        // Debug logging for footer fields
        if (isset($updateData['footer_text']) || isset($updateData['footer_copyright']) || isset($updateData['footer_privacy_link']) || isset($updateData['footer_terms_link']) || isset($updateData['footer_visible'])) {
            error_log("Footer update data: " . json_encode([
                'footer_text' => $updateData['footer_text'] ?? 'not set',
                'footer_copyright' => $updateData['footer_copyright'] ?? 'not set',
                'footer_privacy_link' => $updateData['footer_privacy_link'] ?? 'not set',
                'footer_terms_link' => $updateData['footer_terms_link'] ?? 'not set',
                'footer_visible' => $updateData['footer_visible'] ?? 'not set'
            ]));
        }

        if (isset($_POST['podcast_player_enabled'])) {
            $updateData['podcast_player_enabled'] = (int)$_POST['podcast_player_enabled'];
        }

        if (isset($_POST['profile_image_shape'])) {
            $shape = sanitizeInput($_POST['profile_image_shape']);
            if (in_array($shape, ['circle', 'rounded', 'square'], true)) {
                $updateData['profile_image_shape'] = $shape;
            }
        }

        if (isset($_POST['bio_alignment'])) {
            $alignment = sanitizeInput($_POST['bio_alignment']);
            if (in_array($alignment, ['left', 'center', 'right'], true)) {
                $updateData['bio_alignment'] = $alignment;
            }
        }

        if (isset($_POST['bio_text_size'])) {
            $textSize = sanitizeInput($_POST['bio_text_size']);
            if (in_array($textSize, ['small', 'medium', 'large'], true)) {
                $updateData['bio_text_size'] = $textSize;
            }
        }

        if (isset($_POST['name_alignment'])) {
            $alignment = sanitizeInput($_POST['name_alignment']);
            if (in_array($alignment, ['left', 'center', 'right'], true)) {
                $updateData['name_alignment'] = $alignment;
            }
        }

        if (isset($_POST['name_text_size'])) {
            $textSize = sanitizeInput($_POST['name_text_size']);
            if (in_array($textSize, ['large', 'xlarge', 'xxlarge'], true)) {
                $updateData['name_text_size'] = $textSize;
            }
        }

        if (isset($_POST['profile_image_shadow'])) {
            $shadow = sanitizeInput($_POST['profile_image_shadow']);
            if (in_array($shadow, ['none', 'subtle', 'strong'], true)) {
                $updateData['profile_image_shadow'] = $shadow;
            }
        }

        if (isset($_POST['profile_image_size'])) {
            // Support both old enum format and new numeric format
            $size = sanitizeInput($_POST['profile_image_size']);
            if (in_array($size, ['small', 'medium', 'large'], true)) {
                $updateData['profile_image_size'] = $size;
            } elseif (is_numeric($size) && $size >= 40 && $size <= 200) {
                $updateData['profile_image_size'] = (int)$size;
            }
        }

        if (isset($_POST['profile_image_border'])) {
            // Support both old enum format and new numeric format
            $border = sanitizeInput($_POST['profile_image_border']);
            if (in_array($border, ['none', 'thin', 'thick'], true)) {
                $updateData['profile_image_border'] = $border;
            }
        }

        // New profile image fields (numeric/color values)
        if (isset($_POST['profile_image_radius'])) {
            $radius = filter_var($_POST['profile_image_radius'], FILTER_VALIDATE_INT);
            if ($radius !== false && $radius >= 0 && $radius <= 50) {
                $updateData['profile_image_radius'] = $radius;
            }
        }

        if (isset($_POST['profile_image_effect'])) {
            $effect = sanitizeInput($_POST['profile_image_effect']);
            if (in_array($effect, ['none', 'glow', 'shadow'], true)) {
                $updateData['profile_image_effect'] = $effect;
            }
        }

        if (isset($_POST['profile_image_shadow_color'])) {
            $color = sanitizeInput($_POST['profile_image_shadow_color']);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $updateData['profile_image_shadow_color'] = $color;
            }
        }

        if (isset($_POST['profile_image_shadow_intensity'])) {
            $intensity = filter_var($_POST['profile_image_shadow_intensity'], FILTER_VALIDATE_FLOAT);
            if ($intensity !== false && $intensity >= 0 && $intensity <= 1) {
                $updateData['profile_image_shadow_intensity'] = $intensity;
            }
        }

        if (isset($_POST['profile_image_shadow_depth'])) {
            $depth = filter_var($_POST['profile_image_shadow_depth'], FILTER_VALIDATE_INT);
            if ($depth !== false && $depth >= 0 && $depth <= 20) {
                $updateData['profile_image_shadow_depth'] = $depth;
            }
        }

        if (isset($_POST['profile_image_shadow_blur'])) {
            $blur = filter_var($_POST['profile_image_shadow_blur'], FILTER_VALIDATE_INT);
            if ($blur !== false && $blur >= 0 && $blur <= 50) {
                $updateData['profile_image_shadow_blur'] = $blur;
            }
        }

        if (isset($_POST['profile_image_glow_color'])) {
            $color = sanitizeInput($_POST['profile_image_glow_color']);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $updateData['profile_image_glow_color'] = $color;
            }
        }

        if (isset($_POST['profile_image_glow_width'])) {
            $width = filter_var($_POST['profile_image_glow_width'], FILTER_VALIDATE_INT);
            if ($width !== false && $width >= 0 && $width <= 50) {
                $updateData['profile_image_glow_width'] = $width;
            }
        }

        if (isset($_POST['profile_image_border_color'])) {
            $color = sanitizeInput($_POST['profile_image_border_color']);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $updateData['profile_image_border_color'] = $color;
            }
        }

        if (isset($_POST['profile_image_border_width'])) {
            $width = filter_var($_POST['profile_image_border_width'], FILTER_VALIDATE_FLOAT);
            if ($width !== false && $width >= 0 && $width <= 10) {
                $updateData['profile_image_border_width'] = $width;
            }
        }

        if (isset($_POST['profile_image_spacing_top'])) {
            $spacing = filter_var($_POST['profile_image_spacing_top'], FILTER_VALIDATE_INT);
            if ($spacing !== false && $spacing >= 0 && $spacing <= 100) {
                $updateData['profile_image_spacing_top'] = $spacing;
            }
        }

        if (isset($_POST['profile_image_spacing_bottom'])) {
            $spacing = filter_var($_POST['profile_image_spacing_bottom'], FILTER_VALIDATE_INT);
            if ($spacing !== false && $spacing >= 0 && $spacing <= 100) {
                $updateData['profile_image_spacing_bottom'] = $spacing;
            }
        }
        
        // Handle RSS feed URL
        if (isset($_POST['rss_feed_url'])) {
            $rssFeedUrl = trim(sanitizeInput($_POST['rss_feed_url']));
            
            if (empty($rssFeedUrl)) {
                // Allow removing RSS feed URL
                $updateData['rss_feed_url'] = null;
            } else {
                // Validate URL format
                if (!filter_var($rssFeedUrl, FILTER_VALIDATE_URL)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid RSS feed URL format. Please enter a valid URL.']);
                    exit;
                }
                
                $updateData['rss_feed_url'] = $rssFeedUrl;
                
                // Automatically parse RSS feed and extract podcast metadata
                try {
                    $parser = new RSSParser();
                    $feedResult = $parser->parseFeed($rssFeedUrl);
                    
                    if ($feedResult['success'] && !empty($feedResult['data'])) {
                        $feedData = $feedResult['data'];
                        
                        // Save cover image URL from RSS feed
                        if (!empty($feedData['cover_image'])) {
                            $updateData['cover_image_url'] = $feedData['cover_image'];
                        }
                        
                        // Save podcast name and description for podcast player display
                        if (!empty($feedData['title'])) {
                            $updateData['podcast_name'] = $feedData['title'];
                        }
                        if (!empty($feedData['description'])) {
                            $updateData['podcast_description'] = $feedData['description'];
                        }
                    } else {
                        // Log error but don't fail the update
                        error_log("Failed to parse RSS feed: " . ($feedResult['error'] ?? 'Unknown error'));
                    }
                } catch (Exception $e) {
                    // Log error but don't fail the update - RSS URL will still be saved
                    error_log("Exception while parsing RSS feed: " . $e->getMessage());
                }
            }
        }
        
        // Handle custom domain
        if (isset($_POST['custom_domain'])) {
            $customDomain = trim(sanitizeInput($_POST['custom_domain']));
            
            if (empty($customDomain)) {
                // Allow removing custom domain
                $updateData['custom_domain'] = null;
            } else {
                // Validate domain format
                require_once __DIR__ . '/../classes/DomainVerifier.php';
                $verifier = new DomainVerifier();
                
                if (!$verifier->isValidDomain($customDomain)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid domain format. Please enter a valid domain (e.g., example.com)']);
                    exit;
                }
                
                // Check if domain is already taken by another page
                $existingPage = fetchOne("SELECT id FROM pages WHERE custom_domain = ? AND id != ?", [$customDomain, $pageId]);
                if ($existingPage) {
                    echo json_encode(['success' => false, 'error' => 'This domain is already in use by another page']);
                    exit;
                }
                
                $updateData['custom_domain'] = $customDomain;
            }
        }
        
        $result = $page->update($pageId, $updateData);
        echo json_encode(['success' => $result, 'error' => $result ? null : 'Failed to update settings']);
        break;
        
    case 'verify_domain':
        $domain = sanitizeInput($_POST['domain'] ?? '');
        
        if (empty($domain)) {
            echo json_encode(['success' => false, 'verified' => false, 'message' => 'Domain is required']);
            exit;
        }
        
        require_once __DIR__ . '/../classes/DomainVerifier.php';
        $verifier = new DomainVerifier();
        
        $result = $verifier->verifyDomain($domain);
        echo json_encode([
            'success' => true,
            'verified' => $result['verified'],
            'message' => $result['message'],
            'records' => $result['records']
        ]);
        break;
        
    case 'update_appearance':
        $updateData = [];
        
        if (isset($_POST['theme_id'])) {
            $updateData['theme_id'] = !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : null;
        }
        
        if (isset($_POST['layout_option'])) {
            $updateData['layout_option'] = sanitizeInput($_POST['layout_option']);
        }
        
        // Handle custom colors
        $colors = [];
        if (isset($_POST['custom_primary_color'])) {
            $colors['primary'] = sanitizeInput($_POST['custom_primary_color']);
        }
        if (isset($_POST['custom_secondary_color'])) {
            $colors['secondary'] = sanitizeInput($_POST['custom_secondary_color']);
        }
        if (isset($_POST['custom_accent_color'])) {
            $colors['accent'] = sanitizeInput($_POST['custom_accent_color']);
        }
        if (!empty($colors)) {
            $updateData['colors'] = $colors;
        }
        
        // Handle page fonts (new separate columns)
        if (isset($_POST['page_primary_font'])) {
            $updateData['page_primary_font'] = sanitizeInput($_POST['page_primary_font']);
        }
        if (isset($_POST['page_secondary_font'])) {
            $updateData['page_secondary_font'] = sanitizeInput($_POST['page_secondary_font']);
        }
        
        // Handle legacy custom fonts (for backward compatibility)
        $fonts = [];
        if (isset($_POST['custom_heading_font'])) {
            $fonts['heading'] = sanitizeInput($_POST['custom_heading_font']);
            // Also update page_primary_font if not already set
            if (!isset($updateData['page_primary_font'])) {
                $updateData['page_primary_font'] = $fonts['heading'];
            }
        }
        if (isset($_POST['custom_body_font'])) {
            $fonts['body'] = sanitizeInput($_POST['custom_body_font']);
            // Also update page_secondary_font if not already set
            if (!isset($updateData['page_secondary_font'])) {
                $updateData['page_secondary_font'] = $fonts['body'];
            }
        }
        if (!empty($fonts)) {
            $updateData['fonts'] = $fonts; // Keep for backward compatibility
        }
        
        // Handle widget fonts
        if (isset($_POST['widget_primary_font'])) {
            $updateData['widget_primary_font'] = sanitizeInput($_POST['widget_primary_font']);
        }
        if (isset($_POST['widget_secondary_font'])) {
            $updateData['widget_secondary_font'] = sanitizeInput($_POST['widget_secondary_font']);
        }
        
        // Handle page background
        // Allow null to clear page-level override (so theme value is used)
        if (isset($_POST['page_background'])) {
            $pageBg = $_POST['page_background'];
            if ($pageBg === '' || $pageBg === null || $pageBg === 'null') {
                $updateData['page_background'] = null; // Clear override
            } else {
                $updateData['page_background'] = sanitizeInput($pageBg);
            }
        }
        
        // Handle widget background
        // Allow null to clear page-level override (so theme value is used)
        if (isset($_POST['widget_background'])) {
            $widgetBg = $_POST['widget_background'];
            if ($widgetBg === '' || $widgetBg === null || $widgetBg === 'null') {
                $updateData['widget_background'] = null; // Clear override
            } else {
                $updateData['widget_background'] = sanitizeInput($widgetBg);
            }
        }
        
        // Handle widget border color
        if (isset($_POST['widget_border_color'])) {
            $updateData['widget_border_color'] = sanitizeInput($_POST['widget_border_color']);
        }
        
        // Handle widget styles
        // Allow null to clear page-level override (so theme value is used)
        if (isset($_POST['widget_styles'])) {
            $widgetStylesJson = $_POST['widget_styles'];
            if ($widgetStylesJson === '' || $widgetStylesJson === null || $widgetStylesJson === 'null') {
                $updateData['widget_styles'] = null; // Clear override
            } else {
                if (is_string($widgetStylesJson)) {
                    $widgetStyles = json_decode($widgetStylesJson, true);
                } else {
                    $widgetStyles = $widgetStylesJson;
                }
                
                if (is_array($widgetStyles)) {
                    // Sanitize and merge with defaults
                    $updateData['widget_styles'] = WidgetStyleManager::sanitize($widgetStyles);
                }
            }
        }
        
        // Handle spatial effect
        // Allow null to clear page-level override (so theme value is used)
        if (isset($_POST['spatial_effect'])) {
            $spatialEffect = $_POST['spatial_effect'];
            if ($spatialEffect === '' || $spatialEffect === null || $spatialEffect === 'null') {
                $updateData['spatial_effect'] = null; // Clear override
            } else {
                $spatialEffect = sanitizeInput($spatialEffect);
                $validEffects = ['none', 'tilt'];
                if (in_array($spatialEffect, $validEffects, true)) {
                    $updateData['spatial_effect'] = $spatialEffect;
                }
            }
        }
        
        // Handle page name effect
        if (isset($_POST['page_name_effect'])) {
            $pageNameEffect = sanitizeInput($_POST['page_name_effect']);
            // Valid page-title effects (5 impressive new creations)
            $validEffects = ['', 'none', 'aurora-borealis', 'holographic', 'liquid-neon', 'chrome-metallic', 'energy-pulse'];
            if (in_array($pageNameEffect, $validEffects, true)) {
                // Normalize 'none' and empty string to NULL (no effect)
                // Other effects will be saved as-is for future implementation
                if ($pageNameEffect === '' || $pageNameEffect === 'none') {
                    $updateData['page_name_effect'] = null;
                } else {
                    $updateData['page_name_effect'] = $pageNameEffect;
                }
            } else {
                error_log("API: page_name_effect validation FAILED. Value: " . var_export($pageNameEffect, true) . ", Valid effects: " . implode(', ', $validEffects));
            }
        }
        
        // Handle profile image fields (for theme editor)
        // Profile image size (numeric, 40-200)
        if (isset($_POST['profile_image_size'])) {
            $size = filter_var($_POST['profile_image_size'], FILTER_VALIDATE_INT);
            if ($size !== false && $size >= 40 && $size <= 200) {
                $updateData['profile_image_size'] = $size;
            }
        }
        
        // Profile image radius (0-50)
        if (isset($_POST['profile_image_radius'])) {
            $radius = filter_var($_POST['profile_image_radius'], FILTER_VALIDATE_INT);
            if ($radius !== false && $radius >= 0 && $radius <= 50) {
                $updateData['profile_image_radius'] = $radius;
            }
        }
        
        // Profile image effect
        if (isset($_POST['profile_image_effect'])) {
            $effect = sanitizeInput($_POST['profile_image_effect']);
            if (in_array($effect, ['none', 'glow', 'shadow'], true)) {
                $updateData['profile_image_effect'] = $effect;
            }
        }
        
        // Profile image shadow properties
        if (isset($_POST['profile_image_shadow_color'])) {
            $color = sanitizeInput($_POST['profile_image_shadow_color']);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $updateData['profile_image_shadow_color'] = $color;
            }
        }
        
        if (isset($_POST['profile_image_shadow_intensity'])) {
            $intensity = filter_var($_POST['profile_image_shadow_intensity'], FILTER_VALIDATE_FLOAT);
            if ($intensity !== false && $intensity >= 0 && $intensity <= 1) {
                $updateData['profile_image_shadow_intensity'] = $intensity;
            }
        }
        
        if (isset($_POST['profile_image_shadow_depth'])) {
            $depth = filter_var($_POST['profile_image_shadow_depth'], FILTER_VALIDATE_INT);
            if ($depth !== false && $depth >= 0 && $depth <= 20) {
                $updateData['profile_image_shadow_depth'] = $depth;
            }
        }
        
        if (isset($_POST['profile_image_shadow_blur'])) {
            $blur = filter_var($_POST['profile_image_shadow_blur'], FILTER_VALIDATE_INT);
            if ($blur !== false && $blur >= 0 && $blur <= 50) {
                $updateData['profile_image_shadow_blur'] = $blur;
            }
        }
        
        // Profile image glow properties
        if (isset($_POST['profile_image_glow_color'])) {
            $color = sanitizeInput($_POST['profile_image_glow_color']);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $updateData['profile_image_glow_color'] = $color;
            }
        }
        
        if (isset($_POST['profile_image_glow_width'])) {
            $width = filter_var($_POST['profile_image_glow_width'], FILTER_VALIDATE_INT);
            if ($width !== false && $width >= 0 && $width <= 50) {
                $updateData['profile_image_glow_width'] = $width;
            }
        }
        
        // Profile image border properties
        if (isset($_POST['profile_image_border_color'])) {
            $color = sanitizeInput($_POST['profile_image_border_color']);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $updateData['profile_image_border_color'] = $color;
            }
        }
        
        if (isset($_POST['profile_image_border_width'])) {
            $width = filter_var($_POST['profile_image_border_width'], FILTER_VALIDATE_FLOAT);
            if ($width !== false && $width >= 0 && $width <= 10) {
                $updateData['profile_image_border_width'] = $width;
            }
        }
        
        // Handle profile_image URL
        if (isset($_POST['profile_image'])) {
            $profileImageUrl = trim(sanitizeInput($_POST['profile_image']));
            if (!empty($profileImageUrl)) {
                // Validate URL format
                if (filter_var($profileImageUrl, FILTER_VALIDATE_URL)) {
                    $updateData['profile_image'] = $profileImageUrl;
                }
            } else {
                // Allow clearing profile image
                $updateData['profile_image'] = null;
            }
        }
        
        $result = $page->update($pageId, $updateData);
        if ($result) {
            echo APIResponse::success(null, 'Appearance updated successfully');
        } else {
            echo APIResponse::error('Failed to update appearance');
        }
        break;
        
    case 'update_email_settings':
        $updateData = [];
        
        if (isset($_POST['email_service_provider'])) {
            $updateData['email_service_provider'] = !empty($_POST['email_service_provider']) ? sanitizeInput($_POST['email_service_provider']) : null;
        }
        
        if (isset($_POST['email_service_api_key'])) {
            $updateData['email_service_api_key'] = sanitizeInput($_POST['email_service_api_key']);
        }
        
        if (isset($_POST['email_list_id'])) {
            $updateData['email_list_id'] = sanitizeInput($_POST['email_list_id']);
        }
        
        if (isset($_POST['email_double_optin'])) {
            $updateData['email_double_optin'] = (int)$_POST['email_double_optin'];
        } else {
            $updateData['email_double_optin'] = 0;
        }
        
        $result = $page->update($pageId, $updateData);
        echo json_encode(['success' => $result, 'error' => $result ? null : 'Failed to update email settings']);
        break;
        
    case 'add_directory':
        $platformName = sanitizeInput($_POST['platform_name'] ?? '');
        $url = sanitizeUrl($_POST['url'] ?? '');
        
        if (empty($platformName)) {
            echo json_encode(['success' => false, 'error' => 'Platform name is required']);
            exit;
        }
        
        // Allow empty URL for placeholder icons (will be created with is_active = 0)
        if (empty($url)) {
            $url = ''; // Explicitly set to empty string
        }
        
        $result = $page->addSocialIcon($pageId, $platformName, $url);
        echo json_encode($result);
        break;
        
    case 'update_directory':
        $iconId = (int)($_POST['directory_id'] ?? 0);
        $platformName = sanitizeInput($_POST['platform_name'] ?? '');
        $url = sanitizeUrl($_POST['url'] ?? '');
        
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        if (empty($platformName) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Platform name and URL are required']);
            exit;
        }
        
        // Validate URL format (must be valid http/https URL)
        $isValidUrl = filter_var($url, FILTER_VALIDATE_URL) !== false && 
                      (strpos(strtolower($url), 'http://') === 0 || strpos(strtolower($url), 'https://') === 0);
        
        // If URL is invalid, ensure is_active is set to 0
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;
        if ($isActive === 1 && !$isValidUrl) {
            $isActive = 0; // Cannot be active with invalid URL
        }
        
        $result = $page->updateSocialIcon($iconId, $pageId, $platformName, $url, $isActive);
        echo json_encode($result);
        break;
        
    case 'update_social_icon_visibility':
        $iconId = (int)($_POST['icon_id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : false;
        
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        $result = $page->toggleSocialIconVisibility($iconId, $pageId, $isActive);
        echo json_encode($result);
        break;
        
    case 'delete_directory':
        $iconId = (int)($_POST['directory_id'] ?? 0);
        if (!$iconId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Social icon ID required']);
            exit;
        }
        
        $result = $page->deleteSocialIcon($iconId, $pageId);
        echo json_encode($result);
        break;
        
    case 'reorder_social_icons':
        $iconOrdersJson = $_POST['icon_orders'] ?? '';
        if (empty($iconOrdersJson)) {
            echo json_encode(['success' => false, 'error' => 'Icon orders required']);
            exit;
        }
        
        $iconOrders = json_decode($iconOrdersJson, true);
        if (!is_array($iconOrders)) {
            echo json_encode(['success' => false, 'error' => 'Invalid icon orders format']);
            exit;
        }
        
        $result = $page->reorderSocialIcons($pageId, $iconOrders);
        echo json_encode($result);
        break;

    case 'update_publish_state':
        $publishStatus = $_POST['publish_status'] ?? '';
        $validStatuses = ['draft', 'published', 'scheduled'];

        if (!in_array($publishStatus, $validStatuses, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid publish status.']);
            break;
        }

        $updateData = [
            'publish_status' => $publishStatus
        ];

        if ($publishStatus === 'published') {
            $updateData['published_at'] = date('Y-m-d H:i:s');
            $updateData['scheduled_publish_at'] = null;
        } elseif ($publishStatus === 'scheduled') {
            $scheduledInput = trim($_POST['scheduled_publish_at'] ?? '');
            if ($scheduledInput === '') {
                echo json_encode(['success' => false, 'error' => 'Scheduled publish time is required.']);
                break;
            }

            $timestamp = strtotime($scheduledInput);
            if ($timestamp === false) {
                echo json_encode(['success' => false, 'error' => 'Invalid scheduled date/time.']);
                break;
            }

            if ($timestamp <= time()) {
                echo json_encode(['success' => false, 'error' => 'Scheduled publish time must be in the future.']);
                break;
            }

            $updateData['scheduled_publish_at'] = date('Y-m-d H:i:s', $timestamp);
            $updateData['published_at'] = null;
        } else {
            $updateData['scheduled_publish_at'] = null;
            $updateData['published_at'] = null;
        }

        $updated = $page->update($pageId, $updateData);

        if (!$updated) {
            echo json_encode(['success' => false, 'error' => 'Unable to update publish status.']);
            break;
        }

        $latest = fetchOne("SELECT publish_status, published_at, scheduled_publish_at FROM pages WHERE id = ?", [$pageId]);

        echo json_encode([
            'success' => true,
            'publish_status' => $latest['publish_status'] ?? $publishStatus,
            'published_at' => $latest['published_at'] ?? null,
            'scheduled_publish_at' => $latest['scheduled_publish_at'] ?? null
        ]);
        break;
        
    case 'remove_image':
        $imageType = sanitizeInput($_POST['type'] ?? '');
        
        if ($imageType === 'profile') {
            $updateField = 'profile_image';
        } elseif ($imageType === 'background') {
            $updateField = 'background_image';
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid image type']);
            exit;
        }
        
        // Get old image to delete
        $oldImage = $userPage[$updateField] ?? null;
        
        // Remove image from page
        $updateResult = $page->update($pageId, [$updateField => null]);
        
        if ($updateResult) {
            // Delete old image file
            if ($oldImage && strpos($oldImage, APP_URL) === 0) {
                require_once __DIR__ . '/../classes/ImageHandler.php';
                $imageHandler = new ImageHandler();
                $oldPath = str_replace(APP_URL, '', $oldImage);
                $imageHandler->deleteImage($oldPath);
            }
            
            echo json_encode(['success' => true, 'message' => ucfirst($imageType) . ' image removed successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove image']);
        }
        break;
        
    case 'import_rss':
        $rssUrl = sanitizeUrl($_POST['rss_feed_url'] ?? '');
        
        if (empty($rssUrl)) {
            echo json_encode(['success' => false, 'error' => 'RSS feed URL is required']);
            exit;
        }
        
        // Update page with RSS URL
        $page->update($pageId, ['rss_feed_url' => $rssUrl]);
        
        // Parse and import RSS feed
        $parser = new RSSParser();
        $feedResult = $parser->parseFeed($rssUrl);
        
        if (!$feedResult['success']) {
            echo json_encode(['success' => false, 'error' => $feedResult['error']]);
            exit;
        }
        
        // Save to page
        $saveResult = $parser->saveToPage($pageId, $feedResult['data']);
        
        if ($saveResult) {
            $episodeCount = count($feedResult['data']['episodes'] ?? []);
            echo json_encode([
                'success' => true,
                'message' => "RSS feed imported successfully. {$episodeCount} episodes imported.",
                'data' => [
                    'podcast_name' => $feedResult['data']['title'] ?? '',
                    'episode_count' => $episodeCount
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save RSS data']);
        }
        break;
        
    case 'save_theme':
        $themeName = trim(sanitizeInput($_POST['theme_name'] ?? ''));
        
        if (empty($themeName) || strlen($themeName) > 100) {
            echo APIResponse::error('Theme name must be 1-100 characters', 400);
            break;
        }
        
        // Collect current theme configuration
        $themeData = [
            'colors' => [],
            'fonts' => [],
            'page_background' => null,
            'widget_styles' => null,
            'spatial_effect' => 'none'
        ];
        
        // Get current colors
        if (isset($_POST['custom_primary_color'])) {
            $themeData['colors']['primary'] = sanitizeInput($_POST['custom_primary_color']);
        }
        if (isset($_POST['custom_secondary_color'])) {
            $themeData['colors']['secondary'] = sanitizeInput($_POST['custom_secondary_color']);
        }
        if (isset($_POST['custom_accent_color'])) {
            $themeData['colors']['accent'] = sanitizeInput($_POST['custom_accent_color']);
        }
        
        // Get current fonts (legacy support)
        if (isset($_POST['custom_heading_font'])) {
            $themeData['fonts']['heading'] = sanitizeInput($_POST['custom_heading_font']);
        }
        if (isset($_POST['custom_body_font'])) {
            $themeData['fonts']['body'] = sanitizeInput($_POST['custom_body_font']);
        }
        
        // Get page fonts (new separate columns)
        if (isset($_POST['page_primary_font'])) {
            $themeData['page_primary_font'] = sanitizeInput($_POST['page_primary_font']);
        }
        if (isset($_POST['page_secondary_font'])) {
            $themeData['page_secondary_font'] = sanitizeInput($_POST['page_secondary_font']);
        }
        
        // Get widget fonts
        if (isset($_POST['widget_primary_font'])) {
            $themeData['widget_primary_font'] = sanitizeInput($_POST['widget_primary_font']);
        }
        if (isset($_POST['widget_secondary_font'])) {
            $themeData['widget_secondary_font'] = sanitizeInput($_POST['widget_secondary_font']);
        }
        
        // Get page background
        if (isset($_POST['page_background'])) {
            $themeData['page_background'] = sanitizeInput($_POST['page_background']);
        }
        
        // Get widget background
        if (isset($_POST['widget_background'])) {
            $themeData['widget_background'] = sanitizeInput($_POST['widget_background']);
        }
        
        // Get widget border color
        if (isset($_POST['widget_border_color'])) {
            $themeData['widget_border_color'] = sanitizeInput($_POST['widget_border_color']);
        }
        
        // Get widget styles
        if (isset($_POST['widget_styles'])) {
            $widgetStylesJson = $_POST['widget_styles'];
            if (is_string($widgetStylesJson)) {
                $widgetStyles = json_decode($widgetStylesJson, true);
            } else {
                $widgetStyles = $widgetStylesJson;
            }
            
            if (is_array($widgetStyles)) {
                $themeData['widget_styles'] = WidgetStyleManager::sanitize($widgetStyles);
            }
        }
        
        // Get spatial effect
        if (isset($_POST['spatial_effect'])) {
            $spatialEffect = sanitizeInput($_POST['spatial_effect']);
            $validEffects = ['none', 'glass', 'depth', 'floating'];
            if (in_array($spatialEffect, $validEffects, true)) {
                $themeData['spatial_effect'] = $spatialEffect;
            }
        }
        
        // Create theme
        $theme = new Theme();
        $result = $theme->createTheme($userId, $themeName, $themeData);
        
        if ($result['success']) {
            echo APIResponse::success([
                'theme_id' => $result['theme_id'],
                'theme_name' => $themeName
            ], 'Theme saved successfully');
        } else {
            echo APIResponse::error($result['error'] ?? 'Failed to save theme');
        }
        break;
        
    case 'delete_theme':
        $themeId = (int)($_POST['theme_id'] ?? 0);
        
        if (!$themeId) {
            echo APIResponse::error('Theme ID required', 400);
            break;
        }
        
        $theme = new Theme();
        $result = $theme->deleteUserTheme($themeId, $userId);
        
        if ($result) {
            echo APIResponse::success(null, 'Theme deleted successfully');
        } else {
            echo APIResponse::error('Failed to delete theme or theme not found');
        }
        break;
        
    default:
        echo APIResponse::error('Invalid action', 400);
        break;
}

