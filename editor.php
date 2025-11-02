<?php
/**
 * Page Editor
 * Podn.Bio - Edit page content, widgets, and settings
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Subscription.php';
require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/includes/theme-helpers.php';
require_once __DIR__ . '/classes/WidgetStyleManager.php';
require_once __DIR__ . '/config/oauth.php';

// Require authentication
requireAuth();

$user = getCurrentUser();
$userId = $user['id'];

// Handle account management actions
$error = '';
$success = '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $userObj = new User();
        
        switch ($action) {
            case 'unlink_google':
                $result = $userObj->unlinkGoogleAccount($userId);
                if ($result['success']) {
                    $success = 'Google account unlinked successfully.';
                    redirect('/editor.php?tab=account&success=' . urlencode($success));
                    exit;
                } else {
                    $error = $result['error'];
                }
                break;
                
            case 'remove_password':
                $result = $userObj->removePassword($userId);
                if ($result['success']) {
                    $success = 'Password removed successfully. You can now only log in with Google.';
                    redirect('/editor.php?tab=account&success=' . urlencode($success));
                    exit;
                } else {
                    $error = $result['error'];
                }
                break;
                
            case 'create_page':
                $username = sanitizeInput($_POST['username'] ?? '');
                if (empty($username)) {
                    $error = 'Username is required';
                } else {
                    $pageObj = new Page();
                    $result = $pageObj->create($userId, $username);
                    
                    if ($result['success']) {
                        redirect('/editor.php?success=' . urlencode('Page created successfully!'));
                        exit;
                    } else {
                        $error = $result['error'];
                    }
                }
                break;
                
            default:
                // Other actions handled by existing editor code
                break;
        }
    }
}

// Get account status
$userObj = new User();
$accountStatus = $userObj->getAccountStatus($userId);
$hasPassword = $accountStatus['has_password'];
$hasGoogle = $accountStatus['has_google'];
$methods = $accountStatus['methods'];

// Check for success/error messages from redirects
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
// Handle linked parameter for backward compatibility
// Only set if success is not already set to prevent duplicates
if (isset($_GET['linked']) && $_GET['linked'] == '1' && empty($success)) {
    $success = 'Google account linked successfully!';
}

// Get user's page
$pageClass = new Page();
$page = $pageClass->getByUserId($userId);

// If no page exists, we'll show the page creation form instead of redirecting
$pageId = null;
$links = [];
$socialIcons = [];

if ($page) {
    $pageId = $page['id'];
    $links = $pageClass->getAllLinks($pageId);
    $socialIcons = $pageClass->getSocialIcons($pageId);
}

// Get themes using Theme class
$themeClass = new Theme();
$themes = $themeClass->getAllThemes(true);
$userThemes = $page ? $themeClass->getUserThemes($userId) : [];
$allThemes = array_merge($themes, $userThemes); // Combine system and user themes

// Get current theme configuration
$widgetStyles = $page ? getWidgetStyles($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : WidgetStyleManager::getDefaults();
$pageBackground = $page ? getPageBackground($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : '#ffffff';
$spatialEffect = $page ? getSpatialEffect($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : 'none';

// Get page and widget fonts separately
$pageFonts = $page ? getPageFonts($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : ['page_primary_font' => 'Inter', 'page_secondary_font' => 'Inter'];
$widgetFonts = $page ? getWidgetFonts($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : ['widget_primary_font' => 'Inter', 'widget_secondary_font' => 'Inter'];

// Get widget background and border color
$widgetBackground = $page ? getWidgetBackground($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : '#ffffff';
$widgetBorderColor = $page ? getWidgetBorderColor($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : '#000000';

// Legacy font support - map to new structure if needed
$legacyFonts = $page ? getThemeFonts($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null) : ['page_primary_font' => 'Inter', 'page_secondary_font' => 'Inter'];
if (!isset($pageFonts['page_primary_font'])) {
    $pageFonts['page_primary_font'] = $legacyFonts['heading'] ?? $legacyFonts['page_primary_font'] ?? 'Inter';
    $pageFonts['page_secondary_font'] = $legacyFonts['body'] ?? $legacyFonts['page_secondary_font'] ?? 'Inter';
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Page - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            overflow-x: hidden;
        }
        
        .editor-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        
        /* Left Sidebar Navigation */
        .sidebar {
            width: 200px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .sidebar-logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0066ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 0.5rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border-left: 3px solid transparent;
            font-size: 0.875rem;
        }
        
        .nav-item:hover {
            background: #f9fafb;
            color: #0066ff;
        }
        
        .nav-item.active {
            background: #f0f7ff;
            color: #0066ff;
            border-left-color: #0066ff;
            font-weight: 600;
        }
        
        .nav-item i {
            width: 18px;
            margin-right: 0.6rem;
            font-size: 1rem;
        }
        
        .sidebar-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .user-profile:hover {
            background: #f9fafb;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #0066ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .user-info {
            flex: 1;
            font-size: 0.75rem;
        }
        
        .user-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.75rem;
        }
        
        .user-email {
            color: #6b7280;
            font-size: 0.7rem;
        }
        
        /* Center Editor Area */
        .editor-main {
            flex: 1;
            margin-left: 200px;
            margin-right: 0;
            background: #ffffff;
            overflow-y: auto;
            height: 100vh;
        }
        
        .editor-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .editor-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .editor-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        .widgets-list {
            list-style: none;
            padding: 0;
        }
        .widget-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
            transition: all 0.2s;
            position: relative;
        }
        .widget-item:hover {
            background: #f0f0f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .widget-item.dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        .widget-item.drag-over {
            border-top: 3px solid #0066ff;
        }
        .widget-item::before {
            content: '☰';
            margin-right: 10px;
            color: #999;
            font-size: 18px;
        }
        .widget-info {
            flex: 1;
        }
        .widget-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .widget-url {
            color: #666;
            font-size: 14px;
            word-break: break-all;
        }
        .widget-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-primary {
            background: #0066ff;
            color: white;
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        /* Theme Cards */
        .theme-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .theme-card {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .theme-card:hover {
            border-color: #9ca3af;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .theme-card.theme-selected {
            border-color: #0066ff;
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }
        
        .theme-card-swatch {
            width: 100%;
            height: 100px;
            position: relative;
            border-radius: 10px 10px 0 0;
        }
        
        .theme-card-body {
            padding: 0.75rem;
            background: #ffffff;
            cursor: pointer;
        }
        
        .theme-card-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .theme-card-font-preview {
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .theme-card-footer {
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f9f9f9;
            border-top: 1px solid #e5e7eb;
        }
        
        .theme-widget-settings-btn:hover {
            background: #0066ff;
            border-color: #0066ff;
            color: white;
        }
        
        .theme-card input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            margin: 0;
            accent-color: #0066ff;
        }
        
        .theme-card input[type="radio"]:checked {
            accent-color: #0066ff;
        }
        
        @media (max-width: 768px) {
            .theme-cards-container {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.75rem;
            }
            
            .theme-card-swatch {
                height: 80px;
            }
        }
        
        /* Toast Notification System */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        
        .toast {
            min-width: 320px;
            max-width: 420px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            pointer-events: auto;
            transform: translateX(calc(100% + 40px));
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast.success {
            border-left: 4px solid #10b981;
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }
        
        .toast.error {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
        }
        
        .toast.info {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        }
        
        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 14px;
        }
        
        .toast.success .toast-icon {
            background: #10b981;
            color: #ffffff;
        }
        
        .toast.error .toast-icon {
            background: #ef4444;
            color: #ffffff;
        }
        
        .toast.info .toast-icon {
            background: #3b82f6;
            color: #ffffff;
        }
        
        .toast-content {
            flex: 1;
            min-width: 0;
        }
        
        .toast-message {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
        }
        
        .toast-close {
            flex-shrink: 0;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
            font-size: 18px;
            transition: color 0.2s;
            opacity: 0.7;
        }
        
        .toast-close:hover {
            color: #374151;
            opacity: 1;
        }
        
        /* Legacy alert styles (for backward compatibility) */
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Professional Bottom Drawer Slider */
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.25s;
            /* Removed backdrop-filter to prevent blur */
        }
        
        .drawer-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.12);
            z-index: 2001;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .drawer.active {
            transform: translateY(0);
        }
        
        /* Drawer drag handle */
        .drawer-handle {
            width: 40px;
            height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 12px auto 0;
            cursor: grab;
        }
        
        .drawer-handle:active {
            cursor: grabbing;
        }
        
        .drawer-content {
            flex: 1;
            overflow-y: auto;
            padding: 0 1.25rem 1.25rem;
            -webkit-overflow-scrolling: touch;
        }
        
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            flex-shrink: 0;
        }
        
        .drawer-header h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            letter-spacing: -0.01em;
        }
        
        .drawer-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
            transition: all 0.2s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }
        
        .drawer-close:hover {
            color: #111827;
            background: #f3f4f6;
        }
        
        /* Compact form groups in drawer */
        .drawer .form-group {
            margin-bottom: 1rem;
        }
        
        .drawer .form-group:last-child {
            margin-bottom: 0;
        }
        
        .drawer .form-group label {
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
            display: block;
        }
        
        .drawer .form-group input,
        .drawer .form-group select,
        .drawer .form-group textarea {
            font-size: 0.875rem;
            padding: 0.625rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #ffffff;
        }
        
        .drawer .form-group input:focus,
        .drawer .form-group select:focus,
        .drawer .form-group textarea:focus {
            outline: none;
            border-color: #0066ff;
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }
        
        .drawer .form-group textarea {
            min-height: 70px;
            resize: vertical;
            font-family: inherit;
        }
        
        .drawer-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1rem 1.25rem;
            border-top: 1px solid #f3f4f6;
            flex-shrink: 0;
            background: #ffffff;
        }
        
        .widget-item {
            position: relative;
            overflow: visible;
            z-index: 1;
            margin-bottom: 10px;
        }
        
        .widget-item.has-drawer {
            z-index: 10;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        
        .modal {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1);
        }
        
        /* Widget Gallery Styles */
        .widget-gallery-modal {
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .widget-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 0.5rem 0;
        }
        
        .widget-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 200px;
        }
        
        .widget-card:hover {
            border-color: #0066ff;
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 102, 255, 0.15);
        }
        
        .widget-card.coming-soon {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .widget-card.coming-soon:hover {
            transform: none;
            border-color: #e0e0e0;
        }
        
        .widget-card-thumbnail {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #0066ff;
        }
        
        .widget-card-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .widget-card-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .widget-card-description {
            font-size: 0.875rem;
            color: #666;
            line-height: 1.4;
            flex-grow: 1;
        }
        
        .widget-card-badge {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            background: #f0f0f0;
            border-radius: 4px;
            color: #666;
        }
        
        .category-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #ddd;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            color: #666;
        }
        
        .category-btn:hover {
            border-color: #0066ff;
            color: #0066ff;
        }
        
        .category-btn.active {
            background: #0066ff;
            border-color: #0066ff;
            color: #fff;
        }
        
        .gallery-controls {
            position: sticky;
            top: 0;
            background: #fff;
            padding: 1rem 0;
            z-index: 10;
            border-bottom: 1px solid #eee;
            margin: -1rem -1.5rem 1.5rem -1.5rem;
            padding: 1rem 1.5rem;
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #111827;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            padding: 0;
            border: none;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
            font-size: 1.25rem;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .modal-content {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        
        .widget-item.editing {
            border: 2px solid #0066ff;
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }
        
        .widget-item.new {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .editor-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="editor-layout">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="/editor.php" class="sidebar-logo">
                    <i class="fas fa-podcast"></i>
                    <?php echo h(APP_NAME); ?>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <?php if ($page): ?>
                    <a href="javascript:void(0)" class="nav-item active" onclick="showSection('widgets', this)">
                        <i class="fas fa-puzzle-piece"></i>
                        <span>Widgets</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('social-icons', this)">
                        <i class="fas fa-share-alt"></i>
                        <span>Social Icons</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('settings', this)">
                        <i class="fas fa-cog"></i>
                        <span>Page Settings</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('account', this)">
                        <i class="fas fa-user"></i>
                        <span>Account</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('appearance', this)">
                        <i class="fas fa-palette"></i>
                        <span>Appearance</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('email', this)">
                        <i class="fas fa-envelope"></i>
                        <span>Email Subscription</span>
                    </a>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile" onclick="showSection('account', document.querySelector('.nav-item[onclick*=\"account\"]'))">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['email'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo h($page['username'] ?? 'New User'); ?></div>
                        <div class="user-email"><?php echo h($user['email']); ?></div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                </div>
            </div>
        </aside>
        
        <!-- Center Editor -->
        <main class="editor-main">
            <div class="editor-content">
                <div class="editor-header">
                    <h1><?php echo $page ? 'Edit Your Page' : 'Create Your Page'; ?></h1>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <?php if ($page): ?>
                            <a href="/<?php echo h($page['username']); ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> View Page
                            </a>
                        <?php endif; ?>
                        <a href="/logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
        
        <!-- Toast Container -->
        <div id="toast-container" class="toast-container"></div>
        
        <!-- Legacy alerts (will be converted to toasts via JavaScript) -->
        <?php if ($success): ?>
            <div class="alert alert-success" data-toast="true" data-type="success" style="display: none;"><?php echo h($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" data-toast="true" data-type="error" style="display: none;"><?php echo h($error); ?></div>
        <?php endif; ?>
        
        <div id="message" style="display:none;"></div>
        
                <?php if (!$page): ?>
                    <!-- No Page - Show Creation Form -->
                    <div style="max-width: 600px; margin: 40px auto; padding: 2rem; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">
                        <h2 style="margin-top: 0;">Create Your First Page</h2>
                        <p style="color: #666; margin-bottom: 1.5rem;">Get started by creating your link-in-bio page. Choose a unique username that will be part of your page URL.</p>
                        
                        <form method="POST" action="/editor.php">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                            <input type="hidden" name="action" value="create_page">
                            
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Choose a username:</label>
                                <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_-]{3,30}" 
                                       placeholder="your-username" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box;">
                                <small style="display: block; margin-top: 0.5rem; color: #666;">
                                    3-30 characters, letters, numbers, underscores, and hyphens only. 
                                    Your page will be available at: <strong><?php echo h(APP_URL); ?>/<span id="username-preview">your-username</span></strong>
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 16px;">Create Page</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Widgets Tab -->
                    <div id="tab-widgets" class="tab-content active">
            <h2>Manage Widgets</h2>
            <p style="margin-bottom: 20px; color: #666;">Add widgets to your page from the widget gallery. Widgets can display links, podcast players, social feeds, videos, and more.</p>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="showAddWidgetForm()">Add Widget</button>
            </div>
            
            <ul id="widgets-list" class="widgets-list">
                <?php 
                // Get widgets (try new method first, fallback to links for compatibility)
                $widgets = [];
                if ($page && method_exists($pageClass, 'getWidgets')) {
                    $widgets = $pageClass->getWidgets($page['id']);
                } elseif (!empty($links)) {
                    // Fallback: convert links to widget format
                    $widgets = array_map(function($link) {
                        $config = [
                            'url' => $link['url'] ?? '',
                            'thumbnail_image' => $link['thumbnail_image'] ?? null,
                            'icon' => $link['icon'] ?? null,
                            'disclosure_text' => $link['disclosure_text'] ?? null
                        ];
                        return [
                            'id' => $link['id'],
                            'widget_type' => $link['type'] ?? 'custom_link',
                            'title' => $link['title'],
                            'config_data' => $config
                        ];
                    }, $links);
                }
                ?>
                <?php if (empty($widgets)): ?>
                    <li>No widgets yet. Click "Add Widget" to browse the widget gallery and add content to your page.</li>
                <?php else: ?>
                    <?php foreach ($widgets as $widget): 
                        $configData = is_string($widget['config_data'] ?? '') 
                            ? json_decode($widget['config_data'], true) 
                            : ($widget['config_data'] ?? []);
                        $widgetType = $widget['widget_type'] ?? 'custom_link';
                        $displayInfo = $configData['url'] ?? $widgetType;
                    ?>
                        <li class="widget-item" data-widget-id="<?php echo $widget['id']; ?>">
                            <div class="widget-info">
                                <div class="widget-title">
                                    <?php echo h($widget['title']); ?>
                                    <span style="font-size: 0.75rem; color: #999; font-weight: normal; margin-left: 0.5rem;">
                                        (<?php echo h($widgetType); ?>)
                                    </span>
                                </div>
                                <div class="widget-url"><?php echo h($displayInfo); ?></div>
                            </div>
                            <div class="widget-actions">
                                <button class="btn btn-secondary btn-small" onclick="editWidget(<?php echo $widget['id']; ?>, this)">Edit</button>
                                <button class="btn btn-danger btn-small" onclick="deleteWidget(<?php echo $widget['id']; ?>)">Delete</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Social Icons Tab -->
        <div id="tab-social-icons" class="tab-content">
            <h2>Social Icons</h2>
            <p style="margin-bottom: 20px; color: #666;">Add links to your social media profiles and platforms.</p>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="showAddDirectoryForm()">Add Social Icon</button>
            </div>
            
            <ul id="directories-list" class="widgets-list">
                <?php if (empty($socialIcons)): ?>
                    <li>No social icons yet. Click "Add Social Icon" to get started.</li>
                <?php else: ?>
                    <?php foreach ($socialIcons as $icon): ?>
                        <li class="widget-item" data-directory-id="<?php echo $icon['id']; ?>">
                            <div class="widget-info">
                                <div class="widget-title"><?php echo h($icon['platform_name']); ?></div>
                                <div class="widget-url"><?php echo h($icon['url']); ?></div>
                            </div>
                            <div class="widget-actions">
                                <button class="btn btn-danger btn-small" onclick="deleteDirectory(<?php echo $icon['id']; ?>)">Delete</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Settings Tab -->
        <div id="tab-settings" class="tab-content">
            <h2>Page Settings</h2>
            <form id="page-settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo h($page['username']); ?>" required>
                    <small>Your page URL: <?php echo h(APP_URL); ?>/<span id="username-preview"><?php echo h($page['username']); ?></span></small>
                </div>
                
                <div class="form-group">
                    <label for="podcast_name">Page Name</label>
                    <input type="text" id="podcast_name" name="podcast_name" value="<?php echo h($page['podcast_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="podcast_description">Description</label>
                    <textarea id="podcast_description" name="podcast_description" rows="4"><?php echo h($page['podcast_description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Profile Image Upload -->
                <div class="form-group">
                    <label>Profile Image</label>
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <?php if ($page['profile_image']): ?>
                                <img id="profile-preview-settings" src="<?php echo h($page['profile_image']); ?>" alt="Profile" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                            <?php else: ?>
                                <div id="profile-preview-settings" style="width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No image</div>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" id="profile-image-input-settings" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-bottom: 10px; width: 100%;">
                            <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                <?php if ($page['profile_image']): ?>
                                    <button type="button" class="btn btn-danger btn-small" onclick="removeImage('profile', 'settings')">Remove</button>
                                <?php endif; ?>
                            </div>
                            <small style="display: block; color: #666;">Select an image to automatically upload. Recommended: 400x400px, square image. Max 5MB</small>
                        </div>
                    </div>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <div class="form-group">
                    <label for="custom_domain">Custom Domain</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <input type="text" 
                               id="custom_domain" 
                               name="custom_domain" 
                               value="<?php echo h($page['custom_domain'] ?? ''); ?>" 
                               placeholder="example.com"
                               style="flex: 1; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               pattern="[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.[a-zA-Z]{2,}"
                               title="Enter a valid domain (e.g., example.com)">
                        <button type="button" 
                                class="btn btn-secondary" 
                                onclick="verifyDomain()"
                                id="verify-domain-btn">
                            Verify DNS
                        </button>
                    </div>
                    <small style="display: block; margin-top: 0.5rem; color: #666;">
                        Enter your custom domain (e.g., mypodcast.com). DNS must point to our server.
                    </small>
                    <div id="domain-verification-status" style="margin-top: 0.5rem; display: none;"></div>
                    
                    <!-- DNS Instructions -->
                    <div id="dns-instructions" style="margin-top: 1rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd; display: none;">
                        <h4 style="margin-top: 0;">DNS Configuration Instructions</h4>
                        <p style="margin-bottom: 0.5rem;"><strong>To connect your custom domain:</strong></p>
                        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
                            <li>Add an <strong>A record</strong> in your DNS settings:</li>
                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                <li><strong>Type:</strong> A</li>
                                <li><strong>Host:</strong> @ (or leave blank for root domain)</li>
                                <li><strong>Value:</strong> <?php echo h(SERVER_IP); ?></li>
                                <li><strong>TTL:</strong> 3600 (or default)</li>
                            </ul>
                            <li>If you want to use www subdomain, add a CNAME record:</li>
                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                <li><strong>Type:</strong> CNAME</li>
                                <li><strong>Host:</strong> www</li>
                                <li><strong>Value:</strong> example.com (your domain)</li>
                            </ul>
                            <li>Wait for DNS propagation (can take up to 48 hours)</li>
                            <li>Click "Verify DNS" to check if configuration is correct</li>
                        </ol>
                        <p style="margin-top: 1rem; margin-bottom: 0; font-size: 0.9rem; color: #666;">
                            <strong>Note:</strong> DNS changes may take time to propagate. If verification fails, wait a few hours and try again.
                        </p>
                    </div>
                    <button type="button" 
                            class="btn btn-link" 
                            onclick="toggleDNSInstructions()"
                            style="margin-top: 0.5rem; padding: 0; text-decoration: underline; color: var(--primary-color);">
                        <span id="instructions-toggle-text">Show</span> DNS Instructions
                    </button>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
        
        <!-- Account Tab -->
        <div id="tab-account" class="tab-content">
            <h2>Account Settings</h2>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Account Information</h3>
                <p><strong>Email:</strong> <?php echo h($user['email']); ?></p>
                <p><strong>Account Created:</strong> <?php echo formatDate($user['created_at'], 'F j, Y'); ?></p>
                <?php if ($user['email_verified']): ?>
                    <p style="color: #059669;"><strong>✓ Email Verified</strong></p>
                <?php else: ?>
                    <p style="color: #dc2626;"><strong>⚠ Email Not Verified</strong></p>
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Login Methods</h3>
                <p>Your account can be accessed using:</p>
                <ul class="login-methods" style="list-style: none; padding: 0; margin: 1rem 0;">
                    <?php if ($hasPassword): ?>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem;">
                            <strong style="min-width: 150px;">Email/Password</strong>
                            <?php if ($hasGoogle): ?>
                                <form method="POST" action="/editor.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove your password? You will only be able to log in with Google.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                    <input type="hidden" name="action" value="remove_password">
                                    <button type="submit" class="btn btn-small btn-secondary">Remove Password</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($hasGoogle): ?>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem;">
                            <strong style="min-width: 150px;">Google OAuth</strong>
                            <?php if ($hasPassword): ?>
                                <form method="POST" action="/editor.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to unlink your Google account? You will only be able to log in with email/password.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                    <input type="hidden" name="action" value="unlink_google">
                                    <button type="submit" class="btn btn-small btn-secondary">Unlink Google</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php else: ?>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem;">
                            <strong style="min-width: 150px;">Google OAuth</strong> - Not linked
                            <?php 
                            $googleLinkUrl = getGoogleAuthUrl('link');
                            ?>
                            <a href="<?php echo h($googleLinkUrl); ?>" class="btn btn-small btn-primary">Link Google Account</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php if (empty($methods)): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem;">
                        <strong>Warning:</strong> You must have at least one login method. Please link a Google account or set a password.
                    </div>
                <?php elseif (count($methods) === 1): ?>
                    <div class="alert alert-info" style="margin-top: 1rem;">
                        <strong>Info:</strong> You currently have only one login method. Consider adding another for account recovery.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Subscription</h3>
                <?php
                $subscription = new Subscription();
                $activeSubscription = $subscription->getActive($userId);
                ?>
                <?php if ($activeSubscription): ?>
                    <p><strong>Current Plan:</strong> <span style="text-transform: capitalize;"><?php echo h($activeSubscription['plan_type']); ?></span></p>
                    <?php if ($activeSubscription['expires_at']): ?>
                        <p><strong>Expires:</strong> <?php echo h(formatDate($activeSubscription['expires_at'], 'F j, Y')); ?></p>
                    <?php else: ?>
                        <p><strong>Status:</strong> Active (no expiration)</p>
                    <?php endif; ?>
                    <?php if ($activeSubscription['payment_method']): ?>
                        <p><strong>Payment Method:</strong> <span style="text-transform: capitalize;"><?php echo h($activeSubscription['payment_method']); ?></span></p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 1rem;">
                        <?php if ($activeSubscription['plan_type'] === 'free'): ?>
                            <a href="/payment/checkout.php?plan=premium" class="btn btn-primary">Upgrade to Premium</a>
                            <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                        <?php elseif ($activeSubscription['plan_type'] === 'premium'): ?>
                            <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                            <button type="button" class="btn btn-secondary" onclick="alert('To cancel your subscription, please contact support.')">Cancel Subscription</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" onclick="alert('To cancel your subscription, please contact support.')">Cancel Subscription</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No active subscription. Start with a free plan!</p>
                    <div style="margin-top: 1rem;">
                        <a href="/payment/checkout.php?plan=premium" class="btn btn-primary">Upgrade to Premium</a>
                        <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Your Page</h3>
                <p><strong>Username:</strong> <?php echo h($page['username']); ?></p>
                <p><strong>Page URL:</strong> <a href="/<?php echo h($page['username']); ?>" target="_blank"><?php echo h(APP_URL); ?>/<?php echo h($page['username']); ?></a></p>
                <div style="margin-top: 1rem;">
                    <a href="/<?php echo h($page['username']); ?>" target="_blank" class="btn btn-primary">View Page</a>
                </div>
            </div>
        </div>
        
        <!-- Appearance Tab -->
        <div id="tab-appearance" class="tab-content">
            <h2>Appearance</h2>
            <form id="appearance-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label style="display: block; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Theme</label>
                    <div class="theme-cards-container">
                        <!-- Theme Cards -->
                        <?php foreach ($allThemes as $theme): 
                            $themeColors = parseThemeJson($theme['colors'], []);
                            $primaryColor = $themeColors['primary'] ?? '#000000';
                            $secondaryColor = $themeColors['secondary'] ?? '#ffffff';
                            $accentColor = $themeColors['accent'] ?? '#0066ff';
                            $isSelected = ($page['theme_id'] == $theme['id']);
                            
                            // Get theme fonts for preview
                            $themeFonts = parseThemeJson($theme['fonts'] ?? '{}', []);
                            $themePagePrimaryFont = $theme['page_primary_font'] ?? $themeFonts['heading'] ?? 'Inter';
                            $themePageSecondaryFont = $theme['page_secondary_font'] ?? $themeFonts['body'] ?? 'Inter';
                            $themeWidgetPrimaryFont = $theme['widget_primary_font'] ?? $themePagePrimaryFont;
                            $themeWidgetSecondaryFont = $theme['widget_secondary_font'] ?? $themePageSecondaryFont;
                        ?>
                        <div class="theme-card <?php echo $isSelected ? 'theme-selected' : ''; ?>" data-theme-id="<?php echo $theme['id']; ?>">
                            <div class="theme-card-swatch" style="background: linear-gradient(135deg, <?php echo h($primaryColor); ?> 0%, <?php echo h($accentColor); ?> 100%);" onclick="selectTheme(<?php echo $theme['id']; ?>)">
                            </div>
                            <div class="theme-card-body" onclick="selectTheme(<?php echo $theme['id']; ?>)">
                                <div class="theme-card-name"><?php echo h($theme['name']); ?></div>
                                <!-- Font Preview -->
                                <div class="theme-card-font-preview">
                                    <div style="font-family: '<?php echo h($themePagePrimaryFont); ?>', sans-serif; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">Page: Sample</div>
                                    <div style="font-family: '<?php echo h($themeWidgetPrimaryFont); ?>', sans-serif; font-size: 0.75rem; font-weight: 600; color: #666;">Widget: Sample</div>
                                </div>
                            </div>
                            <div class="theme-card-footer">
                                <input type="radio" name="theme_id" value="<?php echo $theme['id']; ?>" id="theme-<?php echo $theme['id']; ?>" <?php echo $isSelected ? 'checked' : ''; ?> onchange="handleThemeChange()" onclick="event.stopPropagation();">
                                <button type="button" class="theme-widget-settings-btn" onclick="event.stopPropagation(); showWidgetSettingsDrawer(<?php echo $theme['id']; ?>)" title="View Widget Settings" style="background: none; border: 1px solid #ddd; border-radius: 6px; padding: 0.375rem 0.5rem; cursor: pointer; color: #666; font-size: 0.75rem; transition: all 0.2s;">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="display: block; margin-top: 1rem; color: #666;">Select a theme to automatically apply colors and fonts. You can modify individual elements after selection.</small>
                </div>
                
                <div class="form-group">
                    <label for="layout_option">Layout</label>
                    <select id="layout_option" name="layout_option">
                        <option value="layout1" <?php echo ($page['layout_option'] == 'layout1') ? 'selected' : ''; ?>>Layout 1</option>
                        <option value="layout2" <?php echo ($page['layout_option'] == 'layout2') ? 'selected' : ''; ?>>Layout 2</option>
                    </select>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- ========== PAGE STYLING SECTION ========== -->
                <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.5rem; font-weight: 700;">Page Styling</h2>
                <small style="display: block; margin-bottom: 1.5rem; color: #666;">Customize the overall page appearance including background, colors, and typography.</small>
                
                <!-- Page Background -->
                <h3 style="margin-top: 0;">Page Background</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Set a solid color or create a custom gradient background for your page.</small>
                
                <div class="form-group">
                    <label>Background Type</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="bg-type-btn <?php echo !isGradient($pageBackground) ? 'active' : ''; ?>" data-type="solid" onclick="switchBackgroundType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($pageBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($pageBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($pageBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="bg-type-btn <?php echo isGradient($pageBackground) ? 'active' : ''; ?>" data-type="gradient" onclick="switchBackgroundType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($pageBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($pageBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($pageBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="bg-solid-option" class="bg-option" style="<?php echo isGradient($pageBackground) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo isGradient($pageBackground) ? '#ffffff' : h($pageBackground); ?>; flex-shrink: 0;" id="page-bg-swatch"></div>
                            <input type="color" id="page_background_color" value="<?php echo isGradient($pageBackground) ? '#ffffff' : h($pageBackground); ?>" style="width: 100px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updatePageBackground()">
                            <input type="text" id="page_background_color_hex" value="<?php echo isGradient($pageBackground) ? '#ffffff' : h($pageBackground); ?>" placeholder="#ffffff" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updatePageBackgroundFromHex()">
                        </div>
                        <input type="hidden" id="page_background" name="page_background" value="<?php echo h($pageBackground); ?>">
                    </div>
                    
                    <!-- Gradient Option -->
                    <div id="bg-gradient-option" class="bg-option" style="<?php echo !isGradient($pageBackground) ? 'display: none;' : ''; ?>">
                        <div id="gradient-builder" style="padding: 1rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                            <?php
                            // Parse gradient if it exists
                            $gradientParsed = parseGradientOrColor($pageBackground);
                            $gradStart = '#0066ff';
                            $gradEnd = '#ff00ff';
                            $gradDir = '135deg';
                            if ($gradientParsed && $gradientParsed['type'] === 'gradient') {
                                // Extract colors and direction from gradient
                                preg_match('/(\d+)deg/', $gradientParsed['params'], $dirMatch);
                                preg_match('/#[0-9a-fA-F]{6}/', $gradientParsed['params'], $startMatch);
                                preg_match('/#[0-9a-fA-F]{6}(?=\s|,|\))/', $gradientParsed['params'], $endMatch);
                                if ($dirMatch) $gradDir = $dirMatch[1] . 'deg';
                                if ($startMatch) $gradStart = $startMatch[0];
                                if ($endMatch) $gradEnd = $endMatch[0];
                            }
                            ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Start Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($gradStart); ?>; flex-shrink: 0;" id="gradient-start-swatch"></div>
                                        <input type="color" id="gradient_start_color" value="<?php echo h($gradStart); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateGradient()">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($gradEnd); ?>; flex-shrink: 0;" id="gradient-end-swatch"></div>
                                        <input type="color" id="gradient_end_color" value="<?php echo h($gradEnd); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateGradient()">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Direction</label>
                                <select id="gradient_direction" onchange="updateGradient()" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;">
                                    <option value="135deg" <?php echo $gradDir === '135deg' ? 'selected' : ''; ?>>Diagonal (135°)</option>
                                    <option value="90deg" <?php echo $gradDir === '90deg' ? 'selected' : ''; ?>>Vertical (90°)</option>
                                    <option value="0deg" <?php echo $gradDir === '0deg' ? 'selected' : ''; ?>>Horizontal (0°)</option>
                                    <option value="45deg" <?php echo $gradDir === '45deg' ? 'selected' : ''; ?>>Diagonal (45°)</option>
                                    <option value="180deg" <?php echo $gradDir === '180deg' ? 'selected' : ''; ?>>Vertical Reverse (180°)</option>
                                </select>
                            </div>
                            <div style="height: 60px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($pageBackground); ?>;" id="gradient-preview"></div>
                        </div>
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- Page Colors -->
                <h3 style="margin-top: 0;">Page Colors</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Customize colors to override theme colors or create your own color scheme.</small>
                
                <?php
                // Get theme colors with fallbacks using Theme class
                $colors = getThemeColors($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null);
                $customPrimary = $colors['primary'];
                $customSecondary = $colors['secondary'];
                $customAccent = $colors['accent'];
                ?>
                
                <div class="form-group">
                    <label for="custom_primary_color">Primary Color</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($customPrimary); ?>; flex-shrink: 0;" id="primary-color-swatch"></div>
                        <input type="color" id="custom_primary_color" name="custom_primary_color" value="<?php echo h($customPrimary); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateColorSwatch('primary', this.value)">
                        <input type="text" id="custom_primary_color_hex" value="<?php echo h($customPrimary); ?>" placeholder="#000000" style="flex: 1; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('primary', this.value)">
                        <small style="margin-left: 10px; color: #666;">Text and borders</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="custom_secondary_color">Secondary Color</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($customSecondary); ?>; flex-shrink: 0;" id="secondary-color-swatch"></div>
                        <input type="color" id="custom_secondary_color" name="custom_secondary_color" value="<?php echo h($customSecondary); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateColorSwatch('secondary', this.value)">
                        <input type="text" id="custom_secondary_color_hex" value="<?php echo h($customSecondary); ?>" placeholder="#ffffff" style="flex: 1; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('secondary', this.value)">
                        <small style="margin-left: 10px; color: #666;">Background</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="custom_accent_color">Accent Color</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($customAccent); ?>; flex-shrink: 0;" id="accent-color-swatch"></div>
                        <input type="color" id="custom_accent_color" name="custom_accent_color" value="<?php echo h($customAccent); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateColorSwatch('accent', this.value)">
                        <input type="text" id="custom_accent_color_hex" value="<?php echo h($customAccent); ?>" placeholder="#0066ff" style="flex: 1; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('accent', this.value)">
                        <small style="margin-left: 10px; color: #666;">Highlights and links</small>
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- Page Fonts -->
                <h3 style="margin-top: 0;">Page Fonts</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Choose fonts for page headings and body text. Popular Google Fonts are included.</small>
                
                <?php
                // Get Google Fonts list from helper function
                $googleFonts = getGoogleFontsList();
                $pagePrimaryFont = $pageFonts['page_primary_font'] ?? 'Inter';
                $pageSecondaryFont = $pageFonts['page_secondary_font'] ?? 'Inter';
                ?>
                
                <div class="form-group">
                    <label for="page_primary_font">Page Primary Font</label>
                    <select id="page_primary_font" name="page_primary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($pagePrimaryFont == $fontValue) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for page titles and headings</small>
                </div>
                
                <div class="form-group">
                    <label for="page_secondary_font">Page Secondary Font</label>
                    <select id="page_secondary_font" name="page_secondary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($pageSecondaryFont == $fontValue) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for page body text and descriptions</small>
                </div>
                
                <!-- Page Font Preview -->
                <div style="margin-top: 1.5rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                    <h4 style="margin-top: 0;">Page Font Preview</h4>
                    <h3 id="page-font-preview-heading" style="font-family: '<?php echo h($pagePrimaryFont); ?>', sans-serif; margin: 0.5rem 0;">Sample Page Heading Text</h3>
                    <p id="page-font-preview-body" style="font-family: '<?php echo h($pageSecondaryFont); ?>', sans-serif; margin: 0.5rem 0; color: #666;">This is a preview of how your page body text will look with the selected font.</p>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- ========== WIDGET STYLING SECTION ========== -->
                <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.5rem; font-weight: 700;">Widget Styling</h2>
                <small style="display: block; margin-bottom: 1.5rem; color: #666;">Customize the appearance of widgets (links, podcast player, etc.) independently from page styling.</small>
                
                <!-- Widget Background -->
                <h3 style="margin-top: 0;">Widget Background</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Set a solid color or create a custom gradient background for widgets.</small>
                
                <div class="form-group">
                    <label>Background Type</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="widget-bg-type-btn <?php echo !isGradient($widgetBackground) ? 'active' : ''; ?>" data-type="solid" onclick="switchWidgetBackgroundType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($widgetBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($widgetBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($widgetBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="widget-bg-type-btn <?php echo isGradient($widgetBackground) ? 'active' : ''; ?>" data-type="gradient" onclick="switchWidgetBackgroundType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($widgetBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($widgetBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($widgetBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="widget-bg-solid-option" class="widget-bg-option" style="<?php echo isGradient($widgetBackground) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo isGradient($widgetBackground) ? '#ffffff' : h($widgetBackground); ?>; flex-shrink: 0;" id="widget-bg-swatch"></div>
                            <input type="color" id="widget_background_color" value="<?php echo isGradient($widgetBackground) ? '#ffffff' : h($widgetBackground); ?>" style="width: 100px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBackground()">
                            <input type="text" id="widget_background_color_hex" value="<?php echo isGradient($widgetBackground) ? '#ffffff' : h($widgetBackground); ?>" placeholder="#ffffff" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateWidgetBackgroundFromHex()">
                        </div>
                        <input type="hidden" id="widget_background" name="widget_background" value="<?php echo h($widgetBackground); ?>">
                    </div>
                    
                    <!-- Gradient Option -->
                    <div id="widget-bg-gradient-option" class="widget-bg-option" style="<?php echo !isGradient($widgetBackground) ? 'display: none;' : ''; ?>">
                        <div id="widget-gradient-builder" style="padding: 1rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                            <?php
                            // Parse gradient if it exists
                            $widgetGradientParsed = parseGradientOrColor($widgetBackground);
                            $widgetGradStart = '#ffffff';
                            $widgetGradEnd = '#f0f0f0';
                            $widgetGradDir = '135deg';
                            if ($widgetGradientParsed && $widgetGradientParsed['type'] === 'gradient') {
                                preg_match('/(\d+)deg/', $widgetGradientParsed['params'], $dirMatch);
                                preg_match('/#[0-9a-fA-F]{6}/', $widgetGradientParsed['params'], $startMatch);
                                preg_match('/#[0-9a-fA-F]{6}(?=\s|,|\))/', $widgetGradientParsed['params'], $endMatch);
                                if ($dirMatch) $widgetGradDir = $dirMatch[1] . 'deg';
                                if ($startMatch) $widgetGradStart = $startMatch[0];
                                if ($endMatch) $widgetGradEnd = $endMatch[0];
                            }
                            ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Start Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetGradStart); ?>; flex-shrink: 0;" id="widget-gradient-start-swatch"></div>
                                        <input type="color" id="widget_gradient_start_color" value="<?php echo h($widgetGradStart); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetGradient()">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetGradEnd); ?>; flex-shrink: 0;" id="widget-gradient-end-swatch"></div>
                                        <input type="color" id="widget_gradient_end_color" value="<?php echo h($widgetGradEnd); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetGradient()">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Direction</label>
                                <select id="widget_gradient_direction" onchange="updateWidgetGradient()" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;">
                                    <option value="135deg" <?php echo $widgetGradDir === '135deg' ? 'selected' : ''; ?>>Diagonal (135°)</option>
                                    <option value="90deg" <?php echo $widgetGradDir === '90deg' ? 'selected' : ''; ?>>Vertical (90°)</option>
                                    <option value="0deg" <?php echo $widgetGradDir === '0deg' ? 'selected' : ''; ?>>Horizontal (0°)</option>
                                    <option value="45deg" <?php echo $widgetGradDir === '45deg' ? 'selected' : ''; ?>>Diagonal (45°)</option>
                                    <option value="180deg" <?php echo $widgetGradDir === '180deg' ? 'selected' : ''; ?>>Vertical Reverse (180°)</option>
                                </select>
                            </div>
                            <div style="height: 60px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetBackground); ?>;" id="widget-gradient-preview"></div>
                        </div>
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- Widget Border Color -->
                <h3 style="margin-top: 0;">Widget Border Color</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Set a solid color or create a custom gradient for widget borders.</small>
                
                <div class="form-group">
                    <label>Border Color Type</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="widget-border-type-btn <?php echo !isGradient($widgetBorderColor) ? 'active' : ''; ?>" data-type="solid" onclick="switchWidgetBorderType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($widgetBorderColor) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($widgetBorderColor) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($widgetBorderColor) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="widget-border-type-btn <?php echo isGradient($widgetBorderColor) ? 'active' : ''; ?>" data-type="gradient" onclick="switchWidgetBorderType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($widgetBorderColor) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($widgetBorderColor) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($widgetBorderColor) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="widget-border-solid-option" class="widget-border-option" style="<?php echo isGradient($widgetBorderColor) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo isGradient($widgetBorderColor) ? '#000000' : h($widgetBorderColor); ?>; flex-shrink: 0;" id="widget-border-swatch"></div>
                            <input type="color" id="widget_border_color_picker" value="<?php echo isGradient($widgetBorderColor) ? '#000000' : h($widgetBorderColor); ?>" style="width: 100px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBorderColor()">
                            <input type="text" id="widget_border_color_hex" value="<?php echo isGradient($widgetBorderColor) ? '#000000' : h($widgetBorderColor); ?>" placeholder="#000000" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateWidgetBorderColorFromHex()">
                        </div>
                        <input type="hidden" id="widget_border_color" name="widget_border_color" value="<?php echo h($widgetBorderColor); ?>">
                    </div>
                    
                    <!-- Gradient Option -->
                    <div id="widget-border-gradient-option" class="widget-border-option" style="<?php echo !isGradient($widgetBorderColor) ? 'display: none;' : ''; ?>">
                        <div id="widget-border-gradient-builder" style="padding: 1rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                            <?php
                            // Parse border gradient if it exists
                            $widgetBorderGradientParsed = parseGradientOrColor($widgetBorderColor);
                            $widgetBorderGradStart = '#000000';
                            $widgetBorderGradEnd = '#333333';
                            $widgetBorderGradDir = '135deg';
                            if ($widgetBorderGradientParsed && $widgetBorderGradientParsed['type'] === 'gradient') {
                                preg_match('/(\d+)deg/', $widgetBorderGradientParsed['params'], $dirMatch);
                                preg_match('/#[0-9a-fA-F]{6}/', $widgetBorderGradientParsed['params'], $startMatch);
                                preg_match('/#[0-9a-fA-F]{6}(?=\s|,|\))/', $widgetBorderGradientParsed['params'], $endMatch);
                                if ($dirMatch) $widgetBorderGradDir = $dirMatch[1] . 'deg';
                                if ($startMatch) $widgetBorderGradStart = $startMatch[0];
                                if ($endMatch) $widgetBorderGradEnd = $endMatch[0];
                            }
                            ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Start Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetBorderGradStart); ?>; flex-shrink: 0;" id="widget-border-gradient-start-swatch"></div>
                                        <input type="color" id="widget_border_gradient_start_color" value="<?php echo h($widgetBorderGradStart); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBorderGradient()">
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <div style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetBorderGradEnd); ?>; flex-shrink: 0;" id="widget-border-gradient-end-swatch"></div>
                                        <input type="color" id="widget_border_gradient_end_color" value="<?php echo h($widgetBorderGradEnd); ?>" style="width: 80px; height: 40px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBorderGradient()">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">Direction</label>
                                <select id="widget_border_gradient_direction" onchange="updateWidgetBorderGradient()" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;">
                                    <option value="135deg" <?php echo $widgetBorderGradDir === '135deg' ? 'selected' : ''; ?>>Diagonal (135°)</option>
                                    <option value="90deg" <?php echo $widgetBorderGradDir === '90deg' ? 'selected' : ''; ?>>Vertical (90°)</option>
                                    <option value="0deg" <?php echo $widgetBorderGradDir === '0deg' ? 'selected' : ''; ?>>Horizontal (0°)</option>
                                    <option value="45deg" <?php echo $widgetBorderGradDir === '45deg' ? 'selected' : ''; ?>>Diagonal (45°)</option>
                                    <option value="180deg" <?php echo $widgetBorderGradDir === '180deg' ? 'selected' : ''; ?>>Vertical Reverse (180°)</option>
                                </select>
                            </div>
                            <div style="height: 60px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetBorderColor); ?>;" id="widget-border-gradient-preview"></div>
                        </div>
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- Widget Fonts -->
                <h3 style="margin-top: 0;">Widget Fonts</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Choose fonts for widget titles and content. Defaults to page fonts if not specified.</small>
                
                <?php
                $widgetPrimaryFont = $widgetFonts['widget_primary_font'] ?? $pagePrimaryFont;
                $widgetSecondaryFont = $widgetFonts['widget_secondary_font'] ?? $pageSecondaryFont;
                ?>
                
                <div class="form-group">
                    <label for="widget_primary_font">Widget Primary Font</label>
                    <select id="widget_primary_font" name="widget_primary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="" <?php echo empty($widgetPrimaryFont) || $widgetPrimaryFont === $pagePrimaryFont ? 'selected' : ''; ?>>Default (Page Primary Font)</option>
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($widgetPrimaryFont == $fontValue && !empty($widgetPrimaryFont)) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for widget titles and headings</small>
                </div>
                
                <div class="form-group">
                    <label for="widget_secondary_font">Widget Secondary Font</label>
                    <select id="widget_secondary_font" name="widget_secondary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="" <?php echo empty($widgetSecondaryFont) || $widgetSecondaryFont === $pageSecondaryFont ? 'selected' : ''; ?>>Default (Page Secondary Font)</option>
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($widgetSecondaryFont == $fontValue && !empty($widgetSecondaryFont)) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for widget body text and descriptions</small>
                </div>
                
                <!-- Widget Font Preview -->
                <div style="margin-top: 1.5rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                    <h4 style="margin-top: 0;">Widget Font Preview</h4>
                    <h3 id="widget-font-preview-heading" style="font-family: '<?php echo h($widgetPrimaryFont ?: $pagePrimaryFont); ?>', sans-serif; margin: 0.5rem 0;">Sample Widget Heading Text</h3>
                    <p id="widget-font-preview-body" style="font-family: '<?php echo h($widgetSecondaryFont ?: $pageSecondaryFont); ?>', sans-serif; margin: 0.5rem 0; color: #666;">This is a preview of how your widget content will look with the selected font.</p>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                
                <!-- Widget Structure -->
                <h3 style="margin-top: 0;">Widget Structure</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Customize the appearance of widgets (links, podcast player, etc.)</small>
                
                <div class="form-group">
                    <label>Border Width</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php 
                        $borderWidths = ['thin' => 'Thin', 'medium' => 'Medium', 'thick' => 'Thick'];
                        $currentBorderWidth = $widgetStyles['border_width'] ?? 'medium';
                        foreach ($borderWidths as $value => $label): 
                            $isSelected = ($currentBorderWidth === $value);
                        ?>
                        <button type="button" class="widget-style-btn <?php echo $isSelected ? 'active' : ''; ?>" data-field="border_width" data-value="<?php echo h($value); ?>" onclick="updateWidgetStyle('border_width', '<?php echo h($value); ?>')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo $isSelected ? '600' : '500'; ?>;"><?php echo h($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="widget_border_width" name="widget_border_width" value="<?php echo h($currentBorderWidth); ?>">
                </div>
                
                <div class="form-group">
                    <label>Border Effect</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="border-effect-btn <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'shadow' ? 'active' : ''; ?>" data-effect="shadow" onclick="switchBorderEffect('shadow')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'shadow' ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'shadow' ? '#0066ff' : 'white'; ?>; color: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'shadow' ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'shadow' ? '600' : '500'; ?>;">Shadow</button>
                        <button type="button" class="border-effect-btn <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'glow' ? 'active' : ''; ?>" data-effect="glow" onclick="switchBorderEffect('glow')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'glow' ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'glow' ? '#0066ff' : 'white'; ?>; color: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'glow' ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo ($widgetStyles['border_effect'] ?? 'shadow') === 'glow' ? '600' : '500'; ?>;">Glow</button>
                    </div>
                    <input type="hidden" id="widget_border_effect" name="widget_border_effect" value="<?php echo h($widgetStyles['border_effect'] ?? 'shadow'); ?>">
                </div>
                
                <!-- Shadow Options -->
                <div id="shadow-options" class="border-effect-options" style="<?php echo ($widgetStyles['border_effect'] ?? 'shadow') !== 'shadow' ? 'display: none;' : ''; ?>">
                    <div class="form-group">
                        <label>Shadow Intensity</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <?php 
                            $shadowIntensities = ['none' => 'None', 'subtle' => 'Subtle', 'pronounced' => 'Pronounced'];
                            $currentShadowIntensity = $widgetStyles['border_shadow_intensity'] ?? 'subtle';
                            foreach ($shadowIntensities as $value => $label): 
                                $isSelected = ($currentShadowIntensity === $value);
                            ?>
                            <button type="button" class="widget-style-btn <?php echo $isSelected ? 'active' : ''; ?>" data-field="border_shadow_intensity" data-value="<?php echo h($value); ?>" onclick="updateWidgetStyle('border_shadow_intensity', '<?php echo h($value); ?>')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo $isSelected ? '600' : '500'; ?>;"><?php echo h($label); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="widget_border_shadow_intensity" name="widget_border_shadow_intensity" value="<?php echo h($currentShadowIntensity); ?>">
                    </div>
                </div>
                
                <!-- Glow Options -->
                <div id="glow-options" class="border-effect-options" style="<?php echo ($widgetStyles['border_effect'] ?? 'shadow') !== 'glow' ? 'display: none;' : ''; ?>">
                    <div class="form-group">
                        <label>Glow Intensity</label>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                            <?php 
                            $glowIntensities = ['none' => 'None', 'subtle' => 'Subtle', 'pronounced' => 'Pronounced'];
                            $currentGlowIntensity = $widgetStyles['border_glow_intensity'] ?? 'none';
                            foreach ($glowIntensities as $value => $label): 
                                $isSelected = ($currentGlowIntensity === $value);
                            ?>
                            <button type="button" class="widget-style-btn <?php echo $isSelected ? 'active' : ''; ?>" data-field="border_glow_intensity" data-value="<?php echo h($value); ?>" onclick="updateWidgetStyle('border_glow_intensity', '<?php echo h($value); ?>')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo $isSelected ? '600' : '500'; ?>;"><?php echo h($label); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="widget_border_glow_intensity" name="widget_border_glow_intensity" value="<?php echo h($currentGlowIntensity); ?>">
                    </div>
                    <div class="form-group">
                        <label for="widget_glow_color">Glow Color</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; background: <?php echo h($widgetStyles['glow_color'] ?? '#ff00ff'); ?>; flex-shrink: 0;" id="glow-color-swatch"></div>
                            <input type="color" id="widget_glow_color" value="<?php echo h($widgetStyles['glow_color'] ?? '#ff00ff'); ?>" style="width: 100px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateGlowColor()">
                            <input type="text" id="widget_glow_color_hex" value="<?php echo h($widgetStyles['glow_color'] ?? '#ff00ff'); ?>" placeholder="#ff00ff" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateGlowColorFromHex()">
                        </div>
                        <input type="hidden" id="widget_glow_color_hidden" name="widget_glow_color_hidden" value="<?php echo h($widgetStyles['glow_color'] ?? '#ff00ff'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Spacing Between Widgets</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php 
                        $spacings = ['tight' => 'Tight', 'comfortable' => 'Comfortable', 'spacious' => 'Spacious'];
                        $currentSpacing = $widgetStyles['spacing'] ?? 'comfortable';
                        foreach ($spacings as $value => $label): 
                            $isSelected = ($currentSpacing === $value);
                        ?>
                        <button type="button" class="widget-style-btn <?php echo $isSelected ? 'active' : ''; ?>" data-field="spacing" data-value="<?php echo h($value); ?>" onclick="updateWidgetStyle('spacing', '<?php echo h($value); ?>')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo $isSelected ? '600' : '500'; ?>;"><?php echo h($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="widget_spacing" name="widget_spacing" value="<?php echo h($currentSpacing); ?>">
                </div>
                
                <div class="form-group">
                    <label>Widget Shape</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php 
                        $shapes = ['square' => 'Square', 'rounded' => 'Rounded', 'round' => 'Round'];
                        $currentShape = $widgetStyles['shape'] ?? 'rounded';
                        foreach ($shapes as $value => $label): 
                            $isSelected = ($currentShape === $value);
                        ?>
                        <button type="button" class="widget-style-btn <?php echo $isSelected ? 'active' : ''; ?>" data-field="shape" data-value="<?php echo h($value); ?>" onclick="updateWidgetStyle('shape', '<?php echo h($value); ?>')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; font-weight: <?php echo $isSelected ? '600' : '500'; ?>;"><?php echo h($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="widget_shape" name="widget_shape" value="<?php echo h($currentShape); ?>">
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <h3 style="margin-top: 0;">Spatial Effect</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Apply a global visual effect to your entire page.</small>
                
                <div class="form-group">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        <?php 
                        $spatialEffects = [
                            'none' => ['label' => 'None', 'icon' => 'fa-square', 'desc' => 'Standard layout'],
                            'glass' => ['label' => 'Glass', 'icon' => 'fa-image', 'desc' => 'Glassmorphism effect'],
                            'depth' => ['label' => 'Depth', 'icon' => 'fa-cube', 'desc' => '3D perspective'],
                            'floating' => ['label' => 'Floating', 'icon' => 'fa-window-maximize', 'desc' => 'Floating container']
                        ];
                        $currentSpatialEffect = $spatialEffect;
                        foreach ($spatialEffects as $value => $info): 
                            $isSelected = ($currentSpatialEffect === $value);
                        ?>
                        <button type="button" class="spatial-effect-btn <?php echo $isSelected ? 'active' : ''; ?>" data-effect="<?php echo h($value); ?>" onclick="updateSpatialEffect('<?php echo h($value); ?>')" style="padding: 1rem; border: 2px solid <?php echo $isSelected ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo $isSelected ? '#0066ff' : 'white'; ?>; color: <?php echo $isSelected ? 'white' : '#666'; ?>; cursor: pointer; text-align: center; transition: all 0.2s;">
                            <i class="fas <?php echo h($info['icon']); ?>" style="display: block; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo h($info['label']); ?></div>
                            <div style="font-size: 0.75rem; opacity: 0.8;"><?php echo h($info['desc']); ?></div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="spatial_effect" name="spatial_effect" value="<?php echo h($currentSpatialEffect); ?>">
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <h3 style="margin-top: 0;">Save as Theme</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Save your current customization as a reusable theme.</small>
                
                <div class="form-group">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="theme_name" placeholder="Enter theme name..." style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" maxlength="100">
                        <button type="button" class="btn btn-secondary" onclick="saveTheme()" style="padding: 0.75rem 1.5rem; background: #0066ff; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">Save Theme</button>
                    </div>
                </div>
                
                <?php if (!empty($userThemes)): ?>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Your Saved Themes</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem;">
                        <?php foreach ($userThemes as $userTheme): 
                            $themeColors = parseThemeJson($userTheme['colors'] ?? '{}', []);
                            $themePrimary = $themeColors['primary'] ?? '#000000';
                            $themeAccent = $themeColors['accent'] ?? '#0066ff';
                        ?>
                        <div style="background: white; border: 2px solid #ddd; border-radius: 8px; padding: 0.75rem; position: relative;">
                            <div style="height: 60px; background: linear-gradient(135deg, <?php echo h($themePrimary); ?> 0%, <?php echo h($themeAccent); ?> 100%); border-radius: 6px; margin-bottom: 0.5rem;"></div>
                            <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;"><?php echo h($userTheme['name']); ?></div>
                            <button type="button" onclick="deleteUserTheme(<?php echo $userTheme['id']; ?>)" style="width: 100%; padding: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">Delete</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">Save Appearance</button>
            </form>
        </div>
        
        <!-- Widget Settings Drawer -->
        <div id="widget-settings-drawer-overlay" class="drawer-overlay" onclick="closeWidgetSettingsDrawer()"></div>
        <div id="widget-settings-drawer" class="drawer">
            <div class="drawer-handle"></div>
            <div class="drawer-content">
                <div class="drawer-header">
                    <h2>Widget Settings Preview</h2>
                    <button class="drawer-close" onclick="closeWidgetSettingsDrawer()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="widget-settings-drawer-body" style="padding: 1.5rem;">
                    <div style="text-align: center; color: #666; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Loading widget settings...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Subscription Tab -->
        <div id="tab-email" class="tab-content">
            <h2>Email Subscription Settings</h2>
            <p style="margin-bottom: 20px; color: #666;">Configure your email service provider to collect email subscriptions from your page visitors.</p>
            <form id="email-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email_service_provider">Email Service Provider</label>
                    <select id="email_service_provider" name="email_service_provider">
                        <option value="">Select Service</option>
                        <?php foreach (EMAIL_SERVICES as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" <?php echo ($page['email_service_provider'] == $key) ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email_service_api_key">API Key</label>
                    <input type="text" id="email_service_api_key" name="email_service_api_key" value="<?php echo h($page['email_service_api_key'] ?? ''); ?>" placeholder="Enter your API key">
                    <small>Your API key from your email service provider</small>
                </div>
                
                <div class="form-group">
                    <label for="email_list_id">List ID</label>
                    <input type="text" id="email_list_id" name="email_list_id" value="<?php echo h($page['email_list_id'] ?? ''); ?>" placeholder="Enter your list/audience ID">
                    <small>The ID of the email list/audience to subscribe users to</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="email_double_optin" name="email_double_optin" value="1" <?php echo ($page['email_double_optin'] ?? 0) ? 'checked' : ''; ?>>
                        Enable double opt-in (require email confirmation)
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Email Settings</button>
            </form>
        </div>
                <?php endif; // End if ($page) ?>
            </div>
        </main>
    </div>
    
    <?php if ($page): ?>
    <!-- Add Directory Modal -->
    <div id="directory-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:30px; border-radius:8px; max-width:500px; width:90%;">
            <h2>Add Social Icon</h2>
            <form id="directory-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="directory_platform">Platform</label>
                    <select id="directory_platform" name="platform_name" required>
                        <option value="">Select Platform</option>
                        <?php
                        $platforms = [
                            // Podcast Platforms
                            'apple_podcasts' => 'Apple Podcasts',
                            'spotify' => 'Spotify',
                            'youtube_music' => 'YouTube Music',
                            'iheart_radio' => 'iHeart Radio',
                            'amazon_music' => 'Amazon Music',
                            // Social Media Platforms
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
                        ];
                        foreach ($platforms as $key => $name):
                        ?>
                            <option value="<?php echo h($key); ?>"><?php echo h($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="directory_url">URL</label>
                    <input type="url" id="directory_url" name="url" required placeholder="https://...">
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeDirectoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Social Icon</button>
                </div>
            </form>
        </div>
    </div>
    
        <!-- Widget Gallery Modal -->
        <?php if ($page): ?>
        <div id="widget-gallery-overlay" class="modal-overlay" onclick="closeWidgetGallery()">
            <div class="modal widget-gallery-modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2>Widget Gallery</h2>
                    <button class="modal-close" onclick="closeWidgetGallery()" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-content">
                    <!-- Search and Filter -->
                    <div class="gallery-controls" style="margin-bottom: 1.5rem;">
                        <div class="search-box" style="margin-bottom: 1rem;">
                            <input type="text" id="gallery-search" placeholder="Search widgets..." style="width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        </div>
                        <div class="category-filters" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="category-btn active" data-category="all" onclick="filterWidgetsByCategory('all')">All</button>
                            <button class="category-btn" data-category="links" onclick="filterWidgetsByCategory('links')">Links</button>
                            <button class="category-btn" data-category="videos" onclick="filterWidgetsByCategory('videos')">Videos</button>
                            <button class="category-btn" data-category="content" onclick="filterWidgetsByCategory('content')">Content</button>
                            <button class="category-btn" data-category="podcast" onclick="filterWidgetsByCategory('podcast')">Podcast</button>
                        </div>
                    </div>
                    
                    <!-- Widget Grid -->
                    <div id="widget-gallery-grid" class="widget-gallery-grid">
                        <!-- Widgets will be loaded here via JavaScript -->
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Loading widgets...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Widget Configuration Modal -->
        <div id="widget-modal-overlay" class="modal-overlay" onclick="closeWidgetModal()">
            <div class="modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 id="modal-title">Configure Widget</h2>
                    <button class="modal-close" onclick="closeWidgetModal()" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-content">
                    <form id="widget-form" onsubmit="event.preventDefault(); return false;">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="action" id="widget-action" value="add">
                        <input type="hidden" name="widget_id" id="widget-id">
                        <input type="hidden" name="widget_type" id="widget-type-hidden">
                        
                        <div id="widget-config-fields">
                            <!-- Dynamic fields will be inserted here -->
                        </div>
                    </form>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeWidgetModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="handleWidgetFormSubmit(document.getElementById('widget-form'))">Add Widget</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    
    <script>
        // Ensure functions are in global scope
        window.csrfToken = '<?php echo h($csrfToken); ?>';
        const csrfToken = window.csrfToken;
        
        window.showSection = function(sectionName, navElement) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            const sectionElement = document.getElementById('tab-' + sectionName);
            if (sectionElement) {
                sectionElement.classList.add('active');
            }
            
            // Add active class to clicked nav item
            if (navElement) {
                navElement.classList.add('active');
            } else {
                // Find the nav item if called programmatically
                document.querySelectorAll('.nav-item').forEach(item => {
                    if (item.getAttribute('onclick') && item.getAttribute('onclick').includes(sectionName)) {
                        item.classList.add('active');
                    }
                });
            }
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', sectionName);
            window.history.pushState({}, '', url);
        };
        
        // Alias for backward compatibility
        window.showTab = function(tabName, evt) {
            const navElement = evt ? evt.currentTarget : null;
            showSection(tabName, navElement);
        };
        // On page load, check for tab parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                // Find and click the appropriate nav item
                const navItem = document.querySelector(`.nav-item[onclick*="${tabParam}"]`);
                if (navItem) {
                    showSection(tabParam, navItem);
                } else {
                    showSection(tabParam, null);
                }
            }
        });
        
        // Widget Gallery Functions
        let allWidgets = [];
        let filteredWidgets = [];
        let currentCategory = 'all';
        let currentSearch = '';
        
        window.showAddWidgetForm = function() {
            openWidgetGallery();
        };
        
        window.openWidgetGallery = function() {
            const overlay = document.getElementById('widget-gallery-overlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                loadWidgetGallery();
            }
        };
        
        window.closeWidgetGallery = function() {
            const overlay = document.getElementById('widget-gallery-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        };
        
        function loadWidgetGallery() {
            const grid = document.getElementById('widget-gallery-grid');
            if (!grid) return;
            
            grid.innerHTML = '<div style="text-align: center; padding: 2rem; color: #666;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i><p>Loading widgets...</p></div>';
            
            // Load available widgets from API
            const formData = new FormData();
            formData.append('action', 'get_available');
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && (data.available_widgets || data.widgets)) {
                    const widgets = data.available_widgets || data.widgets;
                    allWidgets = Object.values(widgets);
                    filteredWidgets = allWidgets;
                    renderWidgetGallery();
                } else {
                    grid.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;"><p>Failed to load widgets. Please try again.</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading widgets:', error);
                grid.innerHTML = '<div style="text-align: center; padding: 2rem; color: #dc3545;"><p>Error loading widgets. Please refresh the page.</p></div>';
            });
        }
        
        function renderWidgetGallery() {
            const grid = document.getElementById('widget-gallery-grid');
            if (!grid) return;
            
            if (filteredWidgets.length === 0) {
                grid.innerHTML = '<div style="text-align: center; padding: 2rem; color: #666;"><p>No widgets found matching your search.</p></div>';
                return;
            }
            
            grid.innerHTML = filteredWidgets.map(widget => {
                const isComingSoon = widget.coming_soon || false;
                const thumbnail = widget.thumbnail || '/assets/widget-thumbnails/default.png';
                
                return `
                    <div class="widget-card ${isComingSoon ? 'coming-soon' : ''}" 
                         onclick="${isComingSoon ? '' : `openWidgetConfig('${widget.widget_id}')`}"
                         data-category="${widget.category}"
                         data-name="${widget.name.toLowerCase()}"
                         data-description="${(widget.description || '').toLowerCase()}">
                        <div class="widget-card-thumbnail">
                            ${thumbnail.startsWith('/') || thumbnail.startsWith('http') 
                                ? `<img src="${thumbnail}" alt="${widget.name}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-puzzle-piece\\'></i>'">` 
                                : `<i class="fas fa-puzzle-piece"></i>`}
                        </div>
                        <div class="widget-card-name">${widget.name}</div>
                        <div class="widget-card-description">${widget.description || ''}</div>
                        ${isComingSoon ? '<div class="widget-card-badge">Coming Soon</div>' : ''}
                    </div>
                `;
            }).join('');
        }
        
        window.filterWidgetsByCategory = function(category) {
            currentCategory = category;
            
            // Update active button
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.category === category) {
                    btn.classList.add('active');
                }
            });
            
            applyFilters();
        };
        
        function applyFilters() {
            filteredWidgets = allWidgets.filter(widget => {
                // Category filter
                if (currentCategory !== 'all' && widget.category !== currentCategory) {
                    return false;
                }
                
                // Search filter
                if (currentSearch) {
                    const searchLower = currentSearch.toLowerCase();
                    const nameMatch = (widget.name || '').toLowerCase().includes(searchLower);
                    const descMatch = (widget.description || '').toLowerCase().includes(searchLower);
                    return nameMatch || descMatch;
                }
                
                return true;
            });
            
            renderWidgetGallery();
        }
        
        // Search input handler
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('gallery-search');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    currentSearch = e.target.value;
                    applyFilters();
                });
            }
        });
        
        window.openWidgetConfig = function(widgetId) {
            // Find widget definition
            const widget = allWidgets.find(w => w.widget_id === widgetId);
            if (!widget) {
                showToast('Widget not found', 'error');
                return;
            }
            
            // Close gallery
            closeWidgetGallery();
            
            // Set widget type
            document.getElementById('widget-type-hidden').value = widgetId;
            document.getElementById('modal-title').textContent = `Add ${widget.name}`;
            document.getElementById('widget-action').value = 'add';
            document.getElementById('widget-id').value = '';
            
            // Generate form fields based on widget definition
            const fieldsContainer = document.getElementById('widget-config-fields');
            fieldsContainer.innerHTML = '';
            
            // For podcast players, add RSS Feed URL first, then Title, Description, and Cover Image
            if (widget.widget_id === 'podcast_player_custom') {
                // RSS Feed URL (first field)
                const rssFieldDef = widget.config_fields.rss_feed_url;
                if (rssFieldDef) {
                    const required = rssFieldDef.required ? ' <span style="color: #dc3545;">*</span>' : '';
                    const helpText = rssFieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${rssFieldDef.help}</small>` : '';
                    fieldsContainer.innerHTML += `
                        <div class="form-group">
                            <label for="widget_config_rss_feed_url">${rssFieldDef.label}${required}</label>
                            <input type="${rssFieldDef.type || 'url'}" id="widget_config_rss_feed_url" name="rss_feed_url" ${rssFieldDef.required ? 'required' : ''} placeholder="${rssFieldDef.placeholder || ''}" value="${rssFieldDef.default || ''}">
                            ${helpText}
                        </div>
                    `;
                }
                
                // Title field (second)
                fieldsContainer.innerHTML += `
                    <div class="form-group">
                        <label for="widget_config_title">Title <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="widget_config_title" name="title" required placeholder="Enter widget title">
                        ${widget.widget_id === 'podcast_player_custom' ? '<small style="color: #666; display: block; margin-top: 0.25rem;">Will be auto-populated from RSS feed</small>' : ''}
                    </div>
                `;
                
                // Description field (third)
                fieldsContainer.innerHTML += `
                    <div class="form-group">
                        <label for="widget_config_description">Description</label>
                        <textarea id="widget_config_description" name="description" rows="3" placeholder="Will be auto-populated from RSS feed"></textarea>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">Description from RSS feed</small>
                    </div>
                `;
                
                // Cover Image field (fourth)
                const thumbnailFieldDef = widget.config_fields.thumbnail_image;
                if (thumbnailFieldDef) {
                    const helpText = thumbnailFieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${thumbnailFieldDef.help}</small>` : '';
                    fieldsContainer.innerHTML += `
                        <div class="form-group">
                            <label for="widget_config_thumbnail_image">${thumbnailFieldDef.label}</label>
                            <input type="${thumbnailFieldDef.type || 'url'}" id="widget_config_thumbnail_image" name="thumbnail_image" placeholder="${thumbnailFieldDef.placeholder || ''}" value="${thumbnailFieldDef.default || ''}">
                            ${helpText}
                        </div>
                    `;
                }
            } else {
                // For non-podcast widgets, add title field first
                fieldsContainer.innerHTML += `
                    <div class="form-group">
                        <label for="widget_config_title">Title <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="widget_config_title" name="title" required placeholder="Enter widget title">
                    </div>
                `;
            }
            
            // Add widget-specific fields (skip RSS feed URL and thumbnail_image for podcast player as they're already added)
            if (widget.config_fields) {
                Object.entries(widget.config_fields).forEach(([fieldName, fieldDef]) => {
                    // Skip RSS feed URL and thumbnail_image for podcast players (already rendered)
                    if (widget.widget_id === 'podcast_player_custom' && (fieldName === 'rss_feed_url' || fieldName === 'thumbnail_image')) {
                        return;
                    }
                    const required = fieldDef.required ? ' <span style="color: #dc3545;">*</span>' : '';
                    const helpText = fieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${fieldDef.help}</small>` : '';
                    
                    if (fieldDef.type === 'textarea') {
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                <textarea id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''} rows="4" placeholder="${fieldDef.placeholder || ''}">${fieldDef.default || ''}</textarea>
                                ${helpText}
                            </div>
                        `;
                    } else if (fieldDef.type === 'select') {
                        const options = (fieldDef.options || []).map(opt => {
                            const value = typeof opt === 'string' ? opt : opt.value;
                            const label = typeof opt === 'string' ? opt : opt.label;
                            return `<option value="${value}">${label}</option>`;
                        }).join('');
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                <select id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''}>
                                    ${options}
                                </select>
                                ${helpText}
                            </div>
                        `;
                    } else if (fieldDef.type === 'checkbox') {
                        const checked = fieldDef.default === true ? ' checked' : '';
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" id="widget_config_${fieldName}" name="${fieldName}" value="1"${checked}>
                                    <span>${fieldDef.label}</span>
                                </label>
                                ${helpText}
                            </div>
                        `;
                    } else {
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                <input type="${fieldDef.type || 'text'}" id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''} placeholder="${fieldDef.placeholder || ''}" value="${fieldDef.default || ''}">
                                ${helpText}
                            </div>
                        `;
                    }
                });
            }
            
            // Update submit button text
            const submitBtn = document.querySelector('#widget-modal-overlay .btn-primary');
            if (submitBtn) {
                submitBtn.textContent = 'Add Widget';
            }
            
            // Open configuration modal
            openWidgetModal();
            
            // Set up RSS feed auto-population for podcast players
            if (widget.widget_id === 'podcast_player_custom') {
                setupRSSAutoPopulate();
            }
        };
        
        function setupRSSAutoPopulate() {
            const rssInput = document.getElementById('widget_config_rss_feed_url');
            const titleInput = document.getElementById('widget_config_title');
            const descInput = document.getElementById('widget_config_description');
            const thumbnailInput = document.getElementById('widget_config_thumbnail_image');
            
            if (!rssInput) return;
            
            let rssFetchTimeout = null;
            
            rssInput.addEventListener('input', function() {
                const rssUrl = this.value.trim();
                
                // Clear existing timeout
                if (rssFetchTimeout) {
                    clearTimeout(rssFetchTimeout);
                }
                
                // Only fetch if URL looks valid and has a protocol
                if (!rssUrl || !rssUrl.startsWith('http://') && !rssUrl.startsWith('https://')) {
                    return;
                }
                
                // Debounce: wait 1 second after user stops typing
                rssFetchTimeout = setTimeout(() => {
                    fetchRSSFeedInfo(rssUrl, titleInput, descInput, thumbnailInput);
                }, 1000);
            });
        }
        
        function fetchRSSFeedInfo(rssUrl, titleInput, descInput, thumbnailInput) {
            if (!rssUrl) return;
            
            // Show loading state
            const loadingIndicator = document.createElement('small');
            loadingIndicator.id = 'rss-loading-indicator';
            loadingIndicator.style.color = '#0066ff';
            loadingIndicator.style.display = 'block';
            loadingIndicator.style.marginTop = '0.25rem';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching RSS feed...';
            
            const rssInput = document.getElementById('widget_config_rss_feed_url');
            if (rssInput && !rssInput.parentElement.querySelector('#rss-loading-indicator')) {
                rssInput.parentElement.appendChild(loadingIndicator);
            }
            
            // Fetch RSS feed via proxy/API
            fetch('https://api.rss2json.com/v1/api.json?rss_url=' + encodeURIComponent(rssUrl))
                .then(response => response.json())
                .then(data => {
                    // Remove loading indicator
                    const indicator = document.getElementById('rss-loading-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    
                    if (data.status === 'ok' && data.feed) {
                        const feed = data.feed;
                        
                        // Populate title
                        if (titleInput && feed.title) {
                            titleInput.value = feed.title;
                            titleInput.style.borderColor = '#28a745';
                            setTimeout(() => {
                                titleInput.style.borderColor = '';
                            }, 2000);
                        }
                        
                        // Populate description
                        if (descInput && feed.description) {
                            // Clean HTML from description
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = feed.description;
                            descInput.value = tempDiv.textContent || tempDiv.innerText || feed.description;
                            descInput.style.borderColor = '#28a745';
                            setTimeout(() => {
                                descInput.style.borderColor = '';
                            }, 2000);
                        }
                        
                        // Populate thumbnail/cover image
                        if (thumbnailInput) {
                            // Try multiple sources for cover image
                            let coverImage = feed.image || '';
                            
                            // If no feed image, try first episode thumbnail
                            if (!coverImage && data.items && data.items.length > 0) {
                                coverImage = data.items[0].thumbnail || data.items[0].enclosure?.image || '';
                            }
                            
                            if (coverImage) {
                                thumbnailInput.value = coverImage;
                                thumbnailInput.style.borderColor = '#28a745';
                                setTimeout(() => {
                                    thumbnailInput.style.borderColor = '';
                                }, 2000);
                                
                                // Show preview if possible
                                showThumbnailPreview(coverImage);
                            }
                        }
                        
                        showToast('RSS feed loaded successfully!', 'success');
                    } else {
                        showToast('Failed to load RSS feed. Please check the URL.', 'error');
                    }
                })
                .catch(error => {
                    console.error('RSS fetch error:', error);
                    const indicator = document.getElementById('rss-loading-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    showToast('Error loading RSS feed. Please try again.', 'error');
                });
        }
        
        function showThumbnailPreview(imageUrl) {
            const thumbnailInput = document.getElementById('widget_config_thumbnail_image');
            if (!thumbnailInput) return;
            
            // Remove existing preview if any
            const existingPreview = thumbnailInput.parentElement.querySelector('.thumbnail-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Create preview
            const preview = document.createElement('div');
            preview.className = 'thumbnail-preview';
            preview.style.marginTop = '0.5rem';
            preview.style.padding = '0.5rem';
            preview.style.background = '#f9f9f9';
            preview.style.borderRadius = '8px';
            preview.style.textAlign = 'center';
            preview.innerHTML = `
                <img src="${imageUrl}" 
                     alt="Cover preview" 
                     style="max-width: 100px; max-height: 100px; border-radius: 8px; object-fit: cover;"
                     onerror="this.parentElement.innerHTML='<small style=\\'color: #666;\\'>Image preview unavailable</small>'">
                <div style="margin-top: 0.25rem; font-size: 0.75rem; color: #666;">Cover image preview</div>
            `;
            
            thumbnailInput.parentElement.appendChild(preview);
        }
        
        window.deleteTempWidget = function(button) {
            const widgetItem = button.closest('.widget-item');
            const widgetId = widgetItem.getAttribute('data-widget-id');
            
            // If it's a temporary (negative) ID, just remove from DOM
            if (widgetId && parseInt(widgetId) < 0) {
                widgetItem.remove();
                
                // Show "no widgets" message if list is empty
                const widgetsList = document.getElementById('widgets-list');
                if (widgetsList && widgetsList.children.length === 0) {
                    widgetsList.innerHTML = '<li>No widgets yet. Click "Add Widget" to browse the widget gallery and add content to your page.</li>';
                }
            } else {
                // It's a real widget, use the delete function
                deleteWidget(widgetId);
            }
        };
        
        // Prevent double submission
        let isSubmittingWidget = false;
        
        window.handleWidgetFormSubmit = function(form) {
            // Prevent double submission
            if (isSubmittingWidget) {
                console.log('Widget submission already in progress - ignoring duplicate call');
                return false;
            }
            
            const action = document.getElementById('widget-action').value;
            const widgetType = document.getElementById('widget-type-hidden').value;
            const widgetId = document.getElementById('widget-id').value;
            const titleInput = document.getElementById('widget_config_title');
            
            if (!titleInput || !titleInput.value.trim()) {
                showToast('Title is required', 'error');
                return false;
            }
            
            if (!widgetType) {
                showToast('Widget type is required', 'error');
                return false;
            }
            
            // Set submitting flag IMMEDIATELY
            isSubmittingWidget = true;
            
            // Disable submit button to prevent double clicks
            const submitBtn = document.querySelector('#widget-modal-overlay .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                submitBtn.onclick = null; // Remove onclick handler to prevent double clicks
            }
            
            // Also disable all form inputs
            const formInputs = document.querySelectorAll('#widget-form input, #widget-form textarea, #widget-form select');
            formInputs.forEach(input => {
                input.disabled = true;
            });
            
            // Build the request
            const requestData = new FormData();
            requestData.append('action', action === 'update' ? 'update' : 'add');
            requestData.append('csrf_token', csrfToken);
            requestData.append('title', titleInput.value.trim());
            requestData.append('widget_type', widgetType);
            
            // Get all config fields from the form
            const configData = {};
            const widget = allWidgets.find(w => w.widget_id === widgetType);
            if (widget && widget.config_fields) {
                Object.keys(widget.config_fields).forEach(fieldName => {
                    const field = document.getElementById(`widget_config_${fieldName}`);
                    if (field) {
                        // Handle different field types
                        if (field.type === 'checkbox') {
                            configData[fieldName] = field.checked ? '1' : '0';
                        } else if (field.tagName === 'SELECT' && field.multiple) {
                            configData[fieldName] = Array.from(field.selectedOptions).map(opt => opt.value);
                        } else {
                            configData[fieldName] = field.value;
                        }
                    }
                });
            }
            
            // For podcast players, also include description if it exists
            if (widgetType === 'podcast_player_custom') {
                const descField = document.getElementById('widget_config_description');
                if (descField && descField.value) {
                    configData['description'] = descField.value.trim();
                }
            }
            
            requestData.append('config_data', JSON.stringify(configData));
            
            if (action === 'update' && widgetId) {
                requestData.append('widget_id', widgetId);
            }
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: requestData
            })
            .then(response => response.json())
            .then(data => {
                // Reset submitting flag
                isSubmittingWidget = false;
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = action === 'update' ? 'Save Changes' : 'Add Widget';
                }
                
                if (data.success) {
                    // Prevent any further actions
                    isSubmittingWidget = true; // Keep it true until reload
                    closeWidgetModal();
                    showToast('Widget saved successfully!', 'success');
                    
                    // Reload page to show new widget in list
                    // Use a slightly longer delay to ensure database commit
                    setTimeout(() => {
                        // Force hard reload to bypass cache
                        window.location.reload(true);
                    }, 1200);
                } else {
                    // Re-enable on error
                    formInputs.forEach(input => {
                        input.disabled = false;
                    });
                    showToast(data.error || 'Failed to save widget', 'error');
                }
            })
            .catch(error => {
                // Reset submitting flag on error
                isSubmittingWidget = false;
                
                // Re-enable form inputs
                formInputs.forEach(input => {
                    input.disabled = false;
                });
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = action === 'update' ? 'Save Changes' : 'Add Widget';
                    submitBtn.onclick = function() { handleWidgetFormSubmit(document.getElementById('widget-form')); };
                }
                
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
            
            return false; // Prevent any default form behavior
        };
        
        
        window.editWidget = function(widgetId, buttonElement) {
            const widgetItem = buttonElement ? buttonElement.closest('.widget-item') : document.querySelector(`[data-widget-id="${widgetId}"]`);
            
            if (!widgetItem) return;
            
            // Fetch widget data from API
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('widget_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.widget) {
                    const widget = data.widget;
                    const configData = typeof widget.config_data === 'string' 
                        ? JSON.parse(widget.config_data) 
                        : (widget.config_data || {});
                    
                    // Find widget definition
                    const widgetDef = allWidgets.find(w => w.widget_id === widget.widget_type);
                    
                    // Set up form
                    document.getElementById('modal-title').textContent = `Edit ${widgetDef ? widgetDef.name : 'Widget'}`;
                    document.getElementById('widget-action').value = 'update';
                    document.getElementById('widget-id').value = widget.id;
                    document.getElementById('widget-type-hidden').value = widget.widget_type;
                    
                    // Generate form fields
                    const fieldsContainer = document.getElementById('widget-config-fields');
                    fieldsContainer.innerHTML = '';
                    
                    // For podcast players, add RSS Feed URL first, then Title, Description, and Cover Image
                    if (widget.widget_type === 'podcast_player_custom' && widgetDef) {
                        // RSS Feed URL (first field)
                        const rssFieldDef = widgetDef.config_fields.rss_feed_url;
                        if (rssFieldDef) {
                            const rssValue = (configData['rss_feed_url'] || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const required = rssFieldDef.required ? ' <span style="color: #dc3545;">*</span>' : '';
                            const helpText = rssFieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${rssFieldDef.help}</small>` : '';
                            fieldsContainer.innerHTML += `
                                <div class="form-group">
                                    <label for="widget_config_rss_feed_url">${rssFieldDef.label}${required}</label>
                                    <input type="${rssFieldDef.type || 'url'}" id="widget_config_rss_feed_url" name="rss_feed_url" ${rssFieldDef.required ? 'required' : ''} placeholder="${rssFieldDef.placeholder || ''}" value="${rssValue}">
                                    ${helpText}
                                </div>
                            `;
                        }
                        
                        // Title field (second)
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_title">Title <span style="color: #dc3545;">*</span></label>
                                <input type="text" id="widget_config_title" name="title" required value="${(widget.title || '').replace(/"/g, '&quot;')}">
                                <small style="color: #666; display: block; margin-top: 0.25rem;">Will be auto-populated from RSS feed</small>
                            </div>
                        `;
                        
                        // Description field (third)
                        const descValue = (configData['description'] || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_description">Description</label>
                                <textarea id="widget_config_description" name="description" rows="3" placeholder="Will be auto-populated from RSS feed">${descValue}</textarea>
                                <small style="color: #666; display: block; margin-top: 0.25rem;">Description from RSS feed</small>
                            </div>
                        `;
                        
                        // Cover Image field (fourth)
                        const thumbnailFieldDef = widgetDef.config_fields.thumbnail_image;
                        if (thumbnailFieldDef) {
                            const thumbnailValue = (configData['thumbnail_image'] || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const helpText = thumbnailFieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${thumbnailFieldDef.help}</small>` : '';
                            fieldsContainer.innerHTML += `
                                <div class="form-group">
                                    <label for="widget_config_thumbnail_image">${thumbnailFieldDef.label}</label>
                                    <input type="${thumbnailFieldDef.type || 'url'}" id="widget_config_thumbnail_image" name="thumbnail_image" placeholder="${thumbnailFieldDef.placeholder || ''}" value="${thumbnailValue}">
                                    ${helpText}
                                </div>
                            `;
                        }
                    } else {
                        // For non-podcast widgets, add title field first
                        fieldsContainer.innerHTML += `
                            <div class="form-group">
                                <label for="widget_config_title">Title <span style="color: #dc3545;">*</span></label>
                                <input type="text" id="widget_config_title" name="title" required value="${(widget.title || '').replace(/"/g, '&quot;')}">
                            </div>
                        `;
                    }
                    
                    // Widget-specific fields (skip RSS feed URL and thumbnail_image for podcast player as they're already added)
                    if (widgetDef && widgetDef.config_fields) {
                        Object.entries(widgetDef.config_fields).forEach(([fieldName, fieldDef]) => {
                            // Skip RSS feed URL and thumbnail_image for podcast players (already rendered)
                            if (widget.widget_type === 'podcast_player_custom' && (fieldName === 'rss_feed_url' || fieldName === 'thumbnail_image')) {
                                return;
                            }
                            const value = configData[fieldName] || '';
                            const safeValue = String(value).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const required = fieldDef.required ? ' <span style="color: #dc3545;">*</span>' : '';
                            const helpText = fieldDef.help ? `<small style="color: #666; display: block; margin-top: 0.25rem;">${fieldDef.help}</small>` : '';
                            
                            if (fieldDef.type === 'textarea') {
                                fieldsContainer.innerHTML += `
                                    <div class="form-group">
                                        <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                        <textarea id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''} rows="4" placeholder="${fieldDef.placeholder || ''}">${safeValue}</textarea>
                                        ${helpText}
                                    </div>
                                `;
                            } else if (fieldDef.type === 'select') {
                                const options = (fieldDef.options || []).map(opt => {
                                    const optValue = typeof opt === 'string' ? opt : opt.value;
                                    const optLabel = typeof opt === 'string' ? opt : opt.label;
                                    const selected = optValue === value ? ' selected' : '';
                                    return `<option value="${optValue}"${selected}>${optLabel}</option>`;
                                }).join('');
                                fieldsContainer.innerHTML += `
                                    <div class="form-group">
                                        <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                        <select id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''}>
                                            ${options}
                                        </select>
                                        ${helpText}
                                    </div>
                                `;
                            } else if (fieldDef.type === 'checkbox') {
                                const checked = value === '1' || value === true || value === 'true' ? ' checked' : '';
                                fieldsContainer.innerHTML += `
                                    <div class="form-group">
                                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                                            <input type="checkbox" id="widget_config_${fieldName}" name="${fieldName}" value="1"${checked}>
                                            <span>${fieldDef.label}</span>
                                        </label>
                                        ${helpText}
                                    </div>
                                `;
                            } else {
                                fieldsContainer.innerHTML += `
                                    <div class="form-group">
                                        <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                        <input type="${fieldDef.type || 'text'}" id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''} placeholder="${fieldDef.placeholder || ''}" value="${safeValue}">
                                        ${helpText}
                                    </div>
                                `;
                            }
                        });
                    }
                    
                    // Update submit button
                    const submitBtn = document.querySelector('#widget-modal-overlay .btn-primary');
                    if (submitBtn) {
                        submitBtn.textContent = 'Save Changes';
                    }
                    
                    openWidgetModal();
                    
                    // Set up RSS feed auto-population for podcast players
                    if (widget.widget_type === 'podcast_player' || widget.widget_type === 'podcast_player_full' || widget.widget_type === 'podcast_player_custom') {
                        setupRSSAutoPopulate();
                    }
                } else {
                    showToast('Failed to load widget data', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading widget:', error);
                showToast('Error loading widget', 'error');
            });
        }
        
        window.openWidgetModal = function() {
            const overlay = document.getElementById('widget-modal-overlay');
            if (overlay) {
                overlay.classList.add('active');
                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';
            }
        }
        
        window.closeWidgetModal = function() {
            const overlay = document.getElementById('widget-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                // Restore body scroll
                document.body.style.overflow = '';
            }
        }
        
        function deleteWidget(widgetId) {
            if (!confirm('Are you sure you want to delete this widget?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('widget_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Widget deleted successfully!', 'success');
                    // Reload after a short delay to show changes
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred', 'error');
            });
        }
        
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) {
                // Fallback if container doesn't exist
                console.warn('Toast container not found');
                return;
            }
            
            // Remove any existing toasts with the same message to prevent duplicates
            const existingToasts = container.querySelectorAll('.toast');
            existingToasts.forEach(toast => {
                const toastMsg = toast.querySelector('.toast-message');
                if (toastMsg && toastMsg.textContent.trim() === message.trim()) {
                    toast.remove();
                }
            });
            
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            // Choose icon based on type
            let icon = 'fa-check-circle';
            if (type === 'error') {
                icon = 'fa-exclamation-circle';
            } else if (type === 'info') {
                icon = 'fa-info-circle';
            } else {
                icon = 'fa-check-circle';
            }
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="toast-content">
                    <p class="toast-message">${message}</p>
                </div>
                <button class="toast-close" onclick="this.closest('.toast').remove()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Auto-dismiss after 6 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 400); // Wait for animation to complete
            }, 6000);
        }
        
        function showMessage(message, type) {
            // Use new toast system
            showToast(message, type);
        }
        
        // Convert legacy alerts to toasts on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Handle URL parameters first (to prevent duplicates from both URL and inline alerts)
            const urlParams = new URLSearchParams(window.location.search);
            const successParam = urlParams.get('success');
            const errorParam = urlParams.get('error');
            const linkedParam = urlParams.get('linked');
            
            let toastShown = false;
            
            // Process success parameter from URL
            if (successParam) {
                toastShown = true;
                setTimeout(() => {
                    showToast(decodeURIComponent(successParam), 'success');
                    // Clean URL - remove both success and linked params if linked=1 exists
                    const paramsToRemove = ['success'];
                    if (linkedParam === '1') {
                        paramsToRemove.push('linked');
                    }
                    const newUrl = window.location.pathname + '?' + Array.from(urlParams.entries())
                        .filter(([key]) => !paramsToRemove.includes(key))
                        .map(([key, val]) => `${key}=${val}`)
                        .join('&');
                    if (newUrl.endsWith('?')) {
                        window.history.replaceState({}, '', window.location.pathname + (urlParams.has('tab') ? '?tab=' + urlParams.get('tab') : ''));
                    } else {
                        window.history.replaceState({}, '', newUrl);
                    }
                }, 300);
            } else if (linkedParam === '1' && !toastShown) {
                // Handle linked parameter separately (only if success wasn't already set)
                toastShown = true;
                setTimeout(() => {
                    showToast('Google account linked successfully!', 'success');
                    // Clean URL
                    const newUrl = window.location.pathname + '?' + Array.from(urlParams.entries())
                        .filter(([key]) => key !== 'linked')
                        .map(([key, val]) => `${key}=${val}`)
                        .join('&');
                    if (newUrl.endsWith('?')) {
                        window.history.replaceState({}, '', window.location.pathname + (urlParams.has('tab') ? '?tab=' + urlParams.get('tab') : ''));
                    } else {
                        window.history.replaceState({}, '', newUrl);
                    }
                }, 300);
            }
            
            // Process error parameter
            if (errorParam) {
                setTimeout(() => {
                    showToast(decodeURIComponent(errorParam), 'error');
                    // Clean URL
                    const newUrl = window.location.pathname + '?' + Array.from(urlParams.entries())
                        .filter(([key]) => key !== 'error')
                        .map(([key, val]) => `${key}=${val}`)
                        .join('&');
                    if (newUrl.endsWith('?')) {
                        window.history.replaceState({}, '', window.location.pathname + (urlParams.has('tab') ? '?tab=' + urlParams.get('tab') : ''));
                    } else {
                        window.history.replaceState({}, '', newUrl);
                    }
                }, 300);
            }
            
            // Convert legacy inline alerts to toasts (only if no URL param toast was shown)
            if (!toastShown) {
                const alerts = document.querySelectorAll('[data-toast="true"]');
                alerts.forEach(alert => {
                    const message = alert.textContent.trim();
                    const type = alert.getAttribute('data-type') || 'success';
                    if (message) {
                        // Small delay to ensure smooth animation
                        setTimeout(() => {
                            showToast(message, type);
                        }, 300);
                    }
                });
            }
        });
        
        // Handle widget form submission
        // Handle main drawer form submission (fallback)
        // Handle drawer form submission
        // Widget form submission is handled directly by button onclick
        // No duplicate event listener needed
        
        // Handle Escape key to close drawer
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('widget-modal-overlay');
                if (overlay && overlay.classList.contains('active')) {
                    closeWidgetModal();
                }
            }
        });
        
        // Ensure drawer is hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            const drawer = document.getElementById('link-drawer');
            const overlay = document.getElementById('drawer-overlay');
            if (drawer) {
                drawer.style.display = 'none';
                drawer.classList.remove('active');
            }
            if (overlay) {
                overlay.classList.remove('active');
            }
        });
        
        // Drag and drop functionality
        let draggedElement = null;
        const widgetsList = document.getElementById('widgets-list');
        
        if (widgetsList) {
            // Make widgets sortable
            Array.from(widgetsList.children).forEach(item => {
                if (item.classList.contains('widget-item')) {
                    item.setAttribute('draggable', 'true');
                    
                    item.addEventListener('dragstart', function(e) {
                        draggedElement = this;
                        this.classList.add('dragging');
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    
                    item.addEventListener('dragend', function() {
                        this.classList.remove('dragging');
                        document.querySelectorAll('.widget-item').forEach(el => {
                            el.classList.remove('drag-over');
                        });
                    });
                    
                    item.addEventListener('dragover', function(e) {
                        if (e.preventDefault) {
                            e.preventDefault();
                        }
                        e.dataTransfer.dropEffect = 'move';
                        if (this !== draggedElement) {
                            this.classList.add('drag-over');
                        }
                        return false;
                    });
                    
                    item.addEventListener('dragleave', function() {
                        this.classList.remove('drag-over');
                    });
                    
                    item.addEventListener('drop', function(e) {
                        if (e.stopPropagation) {
                            e.stopPropagation();
                        }
                        
                        if (draggedElement !== this) {
                            const allItems = Array.from(widgetsList.querySelectorAll('.widget-item'));
                            const draggedIndex = allItems.indexOf(draggedElement);
                            const targetIndex = allItems.indexOf(this);
                            
                            if (draggedIndex < targetIndex) {
                                widgetsList.insertBefore(draggedElement, this.nextSibling);
                            } else {
                                widgetsList.insertBefore(draggedElement, this);
                            }
                            
                            // Save new order
                            saveWidgetOrder();
                        }
                        
                        return false;
                    });
                }
            });
        }
        
        function saveWidgetOrder() {
            if (!widgetsList) {
                console.error('Widgets list not found');
                return;
            }
            
            const items = Array.from(widgetsList.querySelectorAll('.widget-item'));
            if (items.length === 0) {
                console.warn('No widgets found to reorder');
                return;
            }
            
            const widgetOrders = items.map((item, index) => {
                const widgetId = item.getAttribute('data-widget-id');
                if (!widgetId) {
                    console.warn('Widget item missing data-widget-id:', item);
                    return null;
                }
                return {
                    widget_id: parseInt(widgetId),
                    display_order: index + 1
                };
            }).filter(order => order !== null);
            
            if (widgetOrders.length === 0) {
                console.error('No valid widget orders to save');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('widget_orders', JSON.stringify(widgetOrders));
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showMessage('Failed to save widget order', 'error');
                    setTimeout(() => location.reload(), 500);
                }
            })
            .catch(() => {
                showMessage('An error occurred while saving order', 'error');
                setTimeout(() => location.reload(), 500);
            });
        }
        
        // Domain verification function
        function verifyDomain() {
            const domain = document.getElementById('custom_domain').value.trim();
            const statusDiv = document.getElementById('domain-verification-status');
            const verifyBtn = document.getElementById('verify-domain-btn');
            
            if (!domain) {
                showMessage('Please enter a domain first', 'error');
                return;
            }
            
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';
            statusDiv.style.display = 'block';
            statusDiv.innerHTML = '<p style="color: #666;">Verifying DNS configuration...</p>';
            
            const formData = new FormData();
            formData.append('action', 'verify_domain');
            formData.append('domain', domain);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify DNS';
                
                if (data.success) {
                    if (data.verified) {
                        statusDiv.innerHTML = `
                            <div style="padding: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">
                                <strong>✓ Verified!</strong> ${data.message}
                            </div>
                        `;
                    } else {
                        statusDiv.innerHTML = `
                            <div style="padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                                <strong>✗ Not Verified</strong> ${data.message}
                                ${data.records && data.records.length > 0 ? '<p style="margin-top: 0.5rem; font-size: 0.9rem;">Found DNS records: ' + JSON.stringify(data.records) + '</p>' : ''}
                            </div>
                        `;
                    }
                } else {
                    statusDiv.innerHTML = `
                        <div style="padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                            <strong>Error:</strong> ${data.message || 'Failed to verify domain'}
                        </div>
                    `;
                }
            })
            .catch(() => {
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify DNS';
                statusDiv.innerHTML = `
                    <div style="padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; color: #721c24;">
                        <strong>Error:</strong> Failed to verify domain. Please try again.
                    </div>
                `;
            });
        }
        
        // Toggle DNS instructions
        function toggleDNSInstructions() {
            const instructions = document.getElementById('dns-instructions');
            const toggleText = document.getElementById('instructions-toggle-text');
            
            if (instructions.style.display === 'none') {
                instructions.style.display = 'block';
                toggleText.textContent = 'Hide';
            } else {
                instructions.style.display = 'none';
                toggleText.textContent = 'Show';
            }
        }
        
        // Auto-upload profile images when file is selected (Settings tab only)
        const profileImageInputSettings = document.getElementById('profile-image-input-settings');
        
        if (profileImageInputSettings) {
            profileImageInputSettings.addEventListener('change', function(e) {
                if (e.target.files && e.target.files.length > 0) {
                    uploadImage('profile', 'settings');
                }
            });
        }
        
        // Auto-save functionality with debouncing
        let saveTimeouts = {};
        let savingIndicators = {};
        
        function showSavingIndicator(formId, message = 'Saving...') {
            let indicator = savingIndicators[formId];
            if (!indicator) {
                // Create saving indicator element
                indicator = document.createElement('span');
                indicator.className = 'auto-save-indicator';
                indicator.style.cssText = 'margin-left: 10px; font-size: 0.875rem; color: #666; font-style: italic;';
                savingIndicators[formId] = indicator;
                
                // Find save button and append indicator
                const form = document.getElementById(formId);
                if (form) {
                    const saveBtn = form.querySelector('button[type="submit"]');
                    if (saveBtn) {
                        saveBtn.parentNode.appendChild(indicator);
                    }
                }
            }
            indicator.textContent = message;
            indicator.style.display = 'inline';
            return indicator;
        }
        
        function hideSavingIndicator(formId) {
            const indicator = savingIndicators[formId];
            if (indicator) {
                indicator.style.display = 'none';
            }
        }
        
        function autoSaveForm(formId, action, getFormData) {
            // Clear existing timeout
            if (saveTimeouts[formId]) {
                clearTimeout(saveTimeouts[formId]);
            }
            
            // Show saving indicator
            showSavingIndicator(formId, 'Saving...');
            
            // Set new timeout (debounce for 1 second)
            saveTimeouts[formId] = setTimeout(() => {
                const formData = getFormData();
                formData.append('action', action);
                formData.append('csrf_token', csrfToken);
                
                fetch('/api/page.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSavingIndicator(formId, 'Saved');
                        setTimeout(() => hideSavingIndicator(formId), 2000);
                    } else {
                        showSavingIndicator(formId, 'Error saving');
                        setTimeout(() => hideSavingIndicator(formId), 3000);
                        console.error('Auto-save error:', data.error);
                    }
                })
                .catch(error => {
                    showSavingIndicator(formId, 'Error saving');
                    setTimeout(() => hideSavingIndicator(formId), 3000);
                    console.error('Auto-save error:', error);
                });
            }, 1000);
        }
        
        // Handle page settings form with auto-save
        const pageSettingsForm = document.getElementById('page-settings-form');
        if (pageSettingsForm) {
            // Auto-save on input changes
            const settingsFields = ['username', 'podcast_name', 'podcast_description', 'custom_domain'];
            settingsFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        autoSaveForm('page-settings-form', 'update_settings', () => {
                            const formData = new FormData();
                            const username = document.getElementById('username').value;
                            const podcastName = document.getElementById('podcast_name').value;
                            const podcastDesc = document.getElementById('podcast_description').value;
                            const customDomain = document.getElementById('custom_domain').value;
                            
                            if (username) formData.append('username', username);
                            if (podcastName) formData.append('podcast_name', podcastName);
                            if (podcastDesc) formData.append('podcast_description', podcastDesc);
                            if (customDomain) formData.append('custom_domain', customDomain);
                            
                            return formData;
                        });
                    });
                }
            });
            
            // Manual form submission (keep for explicit saves)
            pageSettingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_settings');
            
            showSavingIndicator('page-settings-form', 'Saving...');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Settings saved successfully!', 'success');
                    document.getElementById('username-preview').textContent = document.getElementById('username').value;
                    // Clear verification status if domain changed
                    const statusDiv = document.getElementById('domain-verification-status');
                    if (statusDiv) {
                        statusDiv.style.display = 'none';
                    }
                    hideSavingIndicator('page-settings-form');
                } else {
                    showMessage(data.error || 'Failed to save settings', 'error');
                    hideSavingIndicator('page-settings-form');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
                hideSavingIndicator('page-settings-form');
            });
            });
        }
        
        // Color picker functions
        function updateColorSwatch(type, color) {
            document.getElementById(type + '-color-swatch').style.background = color;
            document.getElementById('custom_' + type + '_color_hex').value = color;
        }
        
        function updateColorFromHex(type, hex) {
            // Validate hex color
            hex = hex.trim();
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                document.getElementById('custom_' + type + '_color').value = hex;
                document.getElementById(type + '-color-swatch').style.background = hex;
            } else {
                alert('Please enter a valid hex color (e.g., #000000)');
            }
        }
        
        // Font preview update
        function updateFontPreview() {
            const headingFontEl = document.getElementById('custom_heading_font');
            const bodyFontEl = document.getElementById('custom_body_font');
            
            // Add null checks
            if (!headingFontEl || !bodyFontEl) {
                console.error('Font preview elements not found');
                return;
            }
            
            const headingFont = headingFontEl.value;
            const bodyFont = bodyFontEl.value;
            
            // Load Google Fonts
            const headingFontUrl = headingFont.replace(/\s+/g, '+');
            const bodyFontUrl = bodyFont.replace(/\s+/g, '+');
            
            // Remove existing font links
            const existingLinks = document.querySelectorAll('link[rel="stylesheet"][href*="fonts.googleapis.com"]');
            existingLinks.forEach(link => {
                if (!link.dataset.editorFonts) {
                    link.remove();
                }
            });
            
            // Add new font links
            const fontLink = document.createElement('link');
            fontLink.rel = 'stylesheet';
            fontLink.dataset.editorFonts = 'true';
            fontLink.href = `https://fonts.googleapis.com/css2?family=${headingFontUrl}:wght@400;600;700&family=${bodyFontUrl}:wght@400;500&display=swap`;
            document.head.appendChild(fontLink);
            
            // Update preview
            const previewHeading = document.getElementById('font-preview-heading');
            const previewBody = document.getElementById('font-preview-body');
            
            if (previewHeading) {
                previewHeading.style.fontFamily = `'${headingFont}', sans-serif`;
            }
            if (previewBody) {
                previewBody.style.fontFamily = `'${bodyFont}', sans-serif`;
            }
        }
        
        // Page Font Preview
        function updatePageFontPreview() {
            const pagePrimaryFont = document.getElementById('page_primary_font')?.value || '';
            const pageSecondaryFont = document.getElementById('page_secondary_font')?.value || '';
            
            if (!pagePrimaryFont && !pageSecondaryFont) return;
            
            // Load Google Fonts
            const primaryFontUrl = pagePrimaryFont.replace(/\s+/g, '+');
            const secondaryFontUrl = pageSecondaryFont.replace(/\s+/g, '+');
            
            // Add font link
            if (pagePrimaryFont || pageSecondaryFont) {
                const fontLink = document.createElement('link');
                fontLink.rel = 'stylesheet';
                fontLink.dataset.editorFonts = 'true';
                fontLink.href = `https://fonts.googleapis.com/css2?family=${primaryFontUrl}:wght@400;600;700&family=${secondaryFontUrl}:wght@400;500&display=swap`;
                document.head.appendChild(fontLink);
            }
            
            // Update preview
            const previewHeading = document.getElementById('page-font-preview-heading');
            const previewBody = document.getElementById('page-font-preview-body');
            
            if (previewHeading && pagePrimaryFont) {
                previewHeading.style.fontFamily = `'${pagePrimaryFont}', sans-serif`;
            }
            if (previewBody && pageSecondaryFont) {
                previewBody.style.fontFamily = `'${pageSecondaryFont}', sans-serif`;
            }
            
            saveAppearanceForm();
        }
        
        // Widget Font Preview
        function updateWidgetFontPreview() {
            const widgetPrimaryFont = document.getElementById('widget_primary_font')?.value || '';
            const widgetSecondaryFont = document.getElementById('widget_secondary_font')?.value || '';
            const pagePrimaryFont = document.getElementById('page_primary_font')?.value || '';
            const pageSecondaryFont = document.getElementById('page_secondary_font')?.value || '';
            
            // Use page fonts as fallback if widget fonts are empty
            const finalPrimaryFont = widgetPrimaryFont || pagePrimaryFont;
            const finalSecondaryFont = widgetSecondaryFont || pageSecondaryFont;
            
            if (!finalPrimaryFont && !finalSecondaryFont) return;
            
            // Load Google Fonts (only if widget fonts are specified)
            if (widgetPrimaryFont || widgetSecondaryFont) {
                const primaryFontUrl = finalPrimaryFont.replace(/\s+/g, '+');
                const secondaryFontUrl = finalSecondaryFont.replace(/\s+/g, '+');
                
                const fontLink = document.createElement('link');
                fontLink.rel = 'stylesheet';
                fontLink.dataset.editorFonts = 'true';
                fontLink.href = `https://fonts.googleapis.com/css2?family=${primaryFontUrl}:wght@400;600;700&family=${secondaryFontUrl}:wght@400;500&display=swap`;
                document.head.appendChild(fontLink);
            }
            
            // Update preview
            const previewHeading = document.getElementById('widget-font-preview-heading');
            const previewBody = document.getElementById('widget-font-preview-body');
            
            if (previewHeading && finalPrimaryFont) {
                previewHeading.style.fontFamily = `'${finalPrimaryFont}', sans-serif`;
            }
            if (previewBody && finalSecondaryFont) {
                previewBody.style.fontFamily = `'${finalSecondaryFont}', sans-serif`;
            }
            
            saveAppearanceForm();
        }
        
        // Select theme from card click
        function selectTheme(themeId) {
            const radio = document.getElementById('theme-' + themeId);
            if (radio) {
                radio.checked = true;
                handleThemeChange();
            }
        }
        
        // Handle theme change
        function handleThemeChange() {
            // Get selected radio button
            const selectedRadio = document.querySelector('input[name="theme_id"]:checked');
            const themeId = selectedRadio ? selectedRadio.value : '';
            
            // Update visual selection
            document.querySelectorAll('.theme-card').forEach(card => {
                card.classList.remove('theme-selected');
            });
            if (selectedRadio) {
                const card = selectedRadio.closest('.theme-card');
                if (card) {
                    card.classList.add('theme-selected');
                }
            }
            
            if (themeId) {
                // Load theme data via AJAX
                fetch('/api/themes.php?id=' + themeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.theme) {
                            // Validate JSON fields before parsing
                            let colors = {};
                            let fonts = {};
                            
                            try {
                                colors = data.theme.colors ? JSON.parse(data.theme.colors) : {};
                            } catch (e) {
                                console.error('Failed to parse theme colors:', e);
                            }
                            
                            try {
                                fonts = data.theme.fonts ? JSON.parse(data.theme.fonts) : {};
                            } catch (e) {
                                console.error('Failed to parse theme fonts:', e);
                            }
                            
                            // Apply theme colors
                            if (colors.primary) {
                                updateColorSwatch('primary', colors.primary);
                                const primaryColorEl = document.getElementById('custom_primary_color');
                                const primaryColorHexEl = document.getElementById('custom_primary_color_hex');
                                if (primaryColorEl) primaryColorEl.value = colors.primary;
                                if (primaryColorHexEl) primaryColorHexEl.value = colors.primary;
                            }
                            if (colors.secondary) {
                                updateColorSwatch('secondary', colors.secondary);
                                const secondaryColorEl = document.getElementById('custom_secondary_color');
                                const secondaryColorHexEl = document.getElementById('custom_secondary_color_hex');
                                if (secondaryColorEl) secondaryColorEl.value = colors.secondary;
                                if (secondaryColorHexEl) secondaryColorHexEl.value = colors.secondary;
                            }
                            if (colors.accent) {
                                updateColorSwatch('accent', colors.accent);
                                const accentColorEl = document.getElementById('custom_accent_color');
                                const accentColorHexEl = document.getElementById('custom_accent_color_hex');
                                if (accentColorEl) accentColorEl.value = colors.accent;
                                if (accentColorHexEl) accentColorHexEl.value = colors.accent;
                            }
                            
                            // Apply theme fonts (legacy support)
                            if (fonts.heading) {
                                const customHeadingFont = document.getElementById('custom_heading_font');
                                if (customHeadingFont) {
                                    customHeadingFont.value = fonts.heading;
                                }
                                const pagePrimaryFont = document.getElementById('page_primary_font');
                                if (pagePrimaryFont) {
                                    pagePrimaryFont.value = fonts.heading;
                                }
                                updateFontPreview();
                                updatePageFontPreview();
                            }
                            if (fonts.body) {
                                const customBodyFont = document.getElementById('custom_body_font');
                                if (customBodyFont) {
                                    customBodyFont.value = fonts.body;
                                }
                                const pageSecondaryFont = document.getElementById('page_secondary_font');
                                if (pageSecondaryFont) {
                                    pageSecondaryFont.value = fonts.body;
                                }
                                updateFontPreview();
                                updatePageFontPreview();
                            }
                            
                            // Apply page fonts from theme (if available)
                            if (data.theme.page_primary_font) {
                                const pagePrimaryFont = document.getElementById('page_primary_font');
                                if (pagePrimaryFont) {
                                    pagePrimaryFont.value = data.theme.page_primary_font;
                                    updatePageFontPreview();
                                }
                            }
                            if (data.theme.page_secondary_font) {
                                const pageSecondaryFont = document.getElementById('page_secondary_font');
                                if (pageSecondaryFont) {
                                    pageSecondaryFont.value = data.theme.page_secondary_font;
                                    updatePageFontPreview();
                                }
                            }
                            
                            // Apply widget fonts from theme (if available)
                            if (data.theme.widget_primary_font) {
                                const widgetPrimaryFont = document.getElementById('widget_primary_font');
                                if (widgetPrimaryFont) {
                                    widgetPrimaryFont.value = data.theme.widget_primary_font;
                                    updateWidgetFontPreview();
                                }
                            }
                            if (data.theme.widget_secondary_font) {
                                const widgetSecondaryFont = document.getElementById('widget_secondary_font');
                                if (widgetSecondaryFont) {
                                    widgetSecondaryFont.value = data.theme.widget_secondary_font;
                                    updateWidgetFontPreview();
                                }
                            }
                            
                            // Apply page background from theme
                            if (data.theme.page_background) {
                                const pageBackground = document.getElementById('page_background');
                                if (pageBackground) {
                                    pageBackground.value = data.theme.page_background;
                                    const isGradient = data.theme.page_background.includes('gradient');
                                    switchBackgroundType(isGradient ? 'gradient' : 'solid');
                                    if (isGradient) {
                                        // Parse and populate gradient fields
                                        const match = data.theme.page_background.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6})\s*0%,\s*(#[0-9a-fA-F]{6})\s*100%\)/);
                                        if (match) {
                                            const direction = match[1] + 'deg';
                                            const startColor = match[2];
                                            const endColor = match[3];
                                            const dirEl = document.getElementById('gradient_direction');
                                            const startEl = document.getElementById('gradient_start_color');
                                            const endEl = document.getElementById('gradient_end_color');
                                            if (dirEl) dirEl.value = direction;
                                            if (startEl) startEl.value = startColor;
                                            if (endEl) endEl.value = endColor;
                                            updateGradient();
                                        }
                                    } else {
                                        // Solid color
                                        const bgColorEl = document.getElementById('page_background_color');
                                        if (bgColorEl) bgColorEl.value = data.theme.page_background;
                                        updatePageBackground();
                                    }
                                }
                            }
                            
                            // Apply widget background from theme
                            if (data.theme.widget_background) {
                                const widgetBackground = document.getElementById('widget_background');
                                if (widgetBackground) {
                                    widgetBackground.value = data.theme.widget_background;
                                    const isGradient = data.theme.widget_background.includes('gradient');
                                    switchWidgetBackgroundType(isGradient ? 'gradient' : 'solid');
                                    if (isGradient) {
                                        // Parse and populate gradient fields
                                        const match = data.theme.widget_background.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6})\s*0%,\s*(#[0-9a-fA-F]{6})\s*100%\)/);
                                        if (match) {
                                            const direction = match[1] + 'deg';
                                            const startColor = match[2];
                                            const endColor = match[3];
                                            const dirEl = document.getElementById('widget_gradient_direction');
                                            const startEl = document.getElementById('widget_gradient_start_color');
                                            const endEl = document.getElementById('widget_gradient_end_color');
                                            if (dirEl) dirEl.value = direction;
                                            if (startEl) startEl.value = startColor;
                                            if (endEl) endEl.value = endColor;
                                            updateWidgetGradient();
                                        }
                                    } else {
                                        // Solid color
                                        const bgColorEl = document.getElementById('widget_background_color');
                                        if (bgColorEl) bgColorEl.value = data.theme.widget_background;
                                        updateWidgetBackground();
                                    }
                                }
                            }
                            
                            // Apply widget border color from theme
                            if (data.theme.widget_border_color) {
                                const widgetBorderColor = document.getElementById('widget_border_color');
                                if (widgetBorderColor) {
                                    widgetBorderColor.value = data.theme.widget_border_color;
                                    const isGradient = data.theme.widget_border_color.includes('gradient');
                                    switchWidgetBorderType(isGradient ? 'gradient' : 'solid');
                                    if (isGradient) {
                                        // Parse and populate gradient fields
                                        const match = data.theme.widget_border_color.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6})\s*0%,\s*(#[0-9a-fA-F]{6})\s*100%\)/);
                                        if (match) {
                                            const direction = match[1] + 'deg';
                                            const startColor = match[2];
                                            const endColor = match[3];
                                            const dirEl = document.getElementById('widget_border_gradient_direction');
                                            const startEl = document.getElementById('widget_border_gradient_start_color');
                                            const endEl = document.getElementById('widget_border_gradient_end_color');
                                            if (dirEl) dirEl.value = direction;
                                            if (startEl) startEl.value = startColor;
                                            if (endEl) endEl.value = endColor;
                                            updateWidgetBorderGradient();
                                        }
                                    } else {
                                        // Solid color
                                        const borderColorEl = document.getElementById('widget_border_color_picker');
                                        if (borderColorEl) borderColorEl.value = data.theme.widget_border_color;
                                        updateWidgetBorderColor();
                                    }
                                }
                            }
                            
                            // Apply widget styles from theme
                            if (data.theme.widget_styles) {
                                try {
                                    const widgetStyles = typeof data.theme.widget_styles === 'string' 
                                        ? JSON.parse(data.theme.widget_styles) 
                                        : data.theme.widget_styles;
                                    
                                    if (widgetStyles.border_width) {
                                        const borderWidth = document.getElementById('widget_border_width');
                                        if (borderWidth) borderWidth.value = widgetStyles.border_width;
                                    }
                                    if (widgetStyles.border_effect) {
                                        const borderEffect = document.getElementById('widget_border_effect');
                                        if (borderEffect) borderEffect.value = widgetStyles.border_effect;
                                    }
                                    if (widgetStyles.border_shadow_intensity) {
                                        const shadowIntensity = document.getElementById('widget_border_shadow_intensity');
                                        if (shadowIntensity) shadowIntensity.value = widgetStyles.border_shadow_intensity;
                                    }
                                    if (widgetStyles.border_glow_intensity) {
                                        const glowIntensity = document.getElementById('widget_border_glow_intensity');
                                        if (glowIntensity) glowIntensity.value = widgetStyles.border_glow_intensity;
                                    }
                                    if (widgetStyles.glow_color) {
                                        const glowColor = document.getElementById('widget_glow_color_hidden');
                                        if (glowColor) glowColor.value = widgetStyles.glow_color;
                                    }
                                    if (widgetStyles.spacing) {
                                        const spacing = document.getElementById('widget_spacing');
                                        if (spacing) spacing.value = widgetStyles.spacing;
                                    }
                                    if (widgetStyles.shape) {
                                        const shape = document.getElementById('widget_shape');
                                        if (shape) shape.value = widgetStyles.shape;
                                    }
                                } catch (e) {
                                    console.error('Failed to parse widget_styles:', e);
                                }
                            }
                            
                            // Apply spatial effect from theme
                            if (data.theme.spatial_effect) {
                                const spatialEffect = document.getElementById('spatial_effect');
                                if (spatialEffect) {
                                    spatialEffect.value = data.theme.spatial_effect;
                                }
                            }
                            
                            // Trigger auto-save
                            saveAppearanceForm();
                        }
                    })
                    .catch((error) => {
                        console.error('Failed to load theme:', error);
                        console.error('Theme ID:', themeId);
                    });
            }
        }
        
        // Update font preview on font change
        // Font preview event listeners
        const customHeadingFont = document.getElementById('custom_heading_font');
        const customBodyFont = document.getElementById('custom_body_font');
        if (customHeadingFont) customHeadingFont.addEventListener('change', updateFontPreview);
        if (customBodyFont) customBodyFont.addEventListener('change', updateFontPreview);
        
        // Page font preview event listeners
        const pagePrimaryFont = document.getElementById('page_primary_font');
        const pageSecondaryFont = document.getElementById('page_secondary_font');
        if (pagePrimaryFont) pagePrimaryFont.addEventListener('change', updatePageFontPreview);
        if (pageSecondaryFont) pageSecondaryFont.addEventListener('change', updatePageFontPreview);
        
        // Widget font preview event listeners
        const widgetPrimaryFont = document.getElementById('widget_primary_font');
        const widgetSecondaryFont = document.getElementById('widget_secondary_font');
        if (widgetPrimaryFont) widgetPrimaryFont.addEventListener('change', updateWidgetFontPreview);
        if (widgetSecondaryFont) widgetSecondaryFont.addEventListener('change', updateWidgetFontPreview);
        
        // Load fonts on page load
        if (customHeadingFont || customBodyFont) updateFontPreview();
        if (pagePrimaryFont || pageSecondaryFont) updatePageFontPreview();
        if (widgetPrimaryFont || widgetSecondaryFont) updateWidgetFontPreview();
        
        // Handle appearance form with auto-save
        const appearanceForm = document.getElementById('appearance-form');
        if (appearanceForm) {
            // Auto-save on changes
            // Note: theme_id is handled separately via handleThemeChange() for radio buttons
            const appearanceFields = ['layout_option', 'custom_primary_color', 'custom_secondary_color', 
                                      'custom_accent_color', 'custom_heading_font', 'custom_body_font',
                                      'page_primary_font', 'page_secondary_font',
                                      'page_background', 'widget_background', 'widget_border_color',
                                      'widget_primary_font', 'widget_secondary_font',
                                      'widget_border_width', 'widget_border_effect',
                                      'widget_border_shadow_intensity', 'widget_border_glow_intensity',
                                      'widget_glow_color_hidden', 'widget_spacing', 'widget_shape', 'spatial_effect'];
            appearanceFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('change', function() {
                        saveAppearanceForm();
                    });
                }
            });
            
            // Also watch hex color inputs
            ['custom_primary_color_hex', 'custom_secondary_color_hex', 'custom_accent_color_hex'].forEach(hexFieldId => {
                const hexField = document.getElementById(hexFieldId);
                if (hexField) {
                    hexField.addEventListener('input', function() {
                        autoSaveForm('appearance-form', 'update_appearance', () => {
                            const formData = new FormData();
                            const selectedRadio = document.querySelector('input[name="theme_id"]:checked');
                            const themeId = selectedRadio ? selectedRadio.value : '';
                            const layout = document.getElementById('layout_option').value;
                            const primaryColor = document.getElementById('custom_primary_color').value;
                            const secondaryColor = document.getElementById('custom_secondary_color').value;
                            const accentColor = document.getElementById('custom_accent_color').value;
                            const headingFont = document.getElementById('custom_heading_font').value;
                            const bodyFont = document.getElementById('custom_body_font').value;
                            
                            formData.append('theme_id', themeId);
                            formData.append('layout_option', layout);
                            formData.append('custom_primary_color', primaryColor);
                            formData.append('custom_secondary_color', secondaryColor);
                            formData.append('custom_accent_color', accentColor);
                            formData.append('custom_heading_font', headingFont);
                            formData.append('custom_body_font', bodyFont);
                            
                            return formData;
                        });
                    });
                }
            });
            
            // Manual form submission (keep for explicit saves)
            appearanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_appearance');
            
            // Include custom colors and fonts
            formData.append('custom_primary_color', document.getElementById('custom_primary_color').value);
            formData.append('custom_secondary_color', document.getElementById('custom_secondary_color').value);
            formData.append('custom_accent_color', document.getElementById('custom_accent_color').value);
            formData.append('custom_heading_font', document.getElementById('custom_heading_font').value);
            formData.append('custom_body_font', document.getElementById('custom_body_font').value);
            
            showSavingIndicator('appearance-form', 'Saving...');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Appearance updated successfully!', 'success');
                    hideSavingIndicator('appearance-form');
                } else {
                    showMessage(data.error || 'Failed to update appearance', 'error');
                    hideSavingIndicator('appearance-form');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
                hideSavingIndicator('appearance-form');
            });
            });
        }
        
        // Handle email settings form with auto-save
        const emailForm = document.getElementById('email-form');
        if (emailForm) {
            // Auto-save on input changes
            const emailFields = emailForm.querySelectorAll('input, select, textarea');
            emailFields.forEach(field => {
                if (field.type !== 'file' && field.type !== 'submit' && field.type !== 'button') {
                    field.addEventListener('input', function() {
                        autoSaveForm('email-form', 'update_email_settings', () => {
                            return new FormData(emailForm);
                        });
                    });
                    field.addEventListener('change', function() {
                        autoSaveForm('email-form', 'update_email_settings', () => {
                            return new FormData(emailForm);
                        });
                    });
                }
            });
            
            // Manual form submission (keep for explicit saves)
            emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_email_settings');
            
            showSavingIndicator('email-form', 'Saving...');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Email settings saved successfully!', 'success');
                    hideSavingIndicator('email-form');
                } else {
                    showMessage(data.error || 'Failed to save email settings', 'error');
                    hideSavingIndicator('email-form');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
                hideSavingIndicator('email-form');
            });
            });
        }
        
        // Image upload functionality
        function uploadImage(type, context = 'appearance') {
            // Only handle profile images
            if (type !== 'profile') {
                console.error('Invalid image type:', type);
                showToast('Invalid image type', 'error');
                return;
            }
            
            const inputId = context === 'settings' ? 'profile-image-input-settings' : 'profile-image-input';
            const previewId = context === 'settings' ? 'profile-preview-settings' : 'profile-preview';
            const input = document.getElementById(inputId);
            
            if (!input) {
                console.error('Input element not found:', inputId);
                showToast('File input element not found', 'error');
                return;
            }
            
            const file = input.files[0];
            
            if (!file) {
                showToast('Please select an image file', 'error');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File size must be less than 5MB', 'error');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showToast('Invalid file type. Please use JPEG, PNG, GIF, or WebP', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', type);
            formData.append('csrf_token', csrfToken);
            
            // Debug logging
            console.log('Uploading image:', {
                type: type,
                context: context,
                fileName: file.name,
                fileSize: file.size,
                fileType: file.type,
                inputId: inputId,
                previewId: previewId
            });
            
            showToast('Uploading image...', 'info');
            
            fetch('/api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Upload response status:', response.status, response.statusText);
                
                // Check if response is OK
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Upload failed - response text:', text);
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.error || 'Upload failed');
                        } catch (e) {
                            if (e instanceof Error && e.message !== 'Upload failed') {
                                throw e;
                            }
                            throw new Error(text || 'Upload failed with status ' + response.status);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Upload response data:', data);
                
                if (data.success) {
                    // Update preview
                    const preview = document.getElementById(previewId);
                    
                    if (!preview) {
                        console.error('Preview element not found:', previewId);
                        console.error('Available preview elements:', document.querySelectorAll('[id*="preview"]'));
                        showToast('Image uploaded but preview could not be updated', 'error');
                        return;
                    }
                    
                    // Use the URL from the response
                    const imageUrl = data.url || data.path;
                    
                    if (!imageUrl) {
                        console.error('No image URL in response:', data);
                        showToast('Image uploaded but no URL returned', 'error');
                        return;
                    }
                    
                    console.log('Updating preview with URL:', imageUrl);
                    
                    // Update or create preview image
                    if (preview && preview.tagName === 'IMG') {
                        // Update existing image - add timestamp to force reload
                        console.log('Updating existing IMG element');
                        preview.src = imageUrl + '?t=' + Date.now();
                    } else if (preview && preview.parentNode) {
                        // Replace div with img
                        console.log('Replacing DIV with IMG element');
                        const img = document.createElement('img');
                        img.id = previewId;
                        img.src = imageUrl;
                        img.alt = 'Profile';
                        img.style.cssText = 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;';
                        preview.parentNode.replaceChild(img, preview);
                        console.log('Preview image replaced successfully');
                    } else {
                        console.error('Preview element or parent not found');
                        console.error('Preview:', preview);
                        console.error('Preview parent:', preview ? preview.parentNode : 'null');
                    }
                    
                    // Show remove button if not visible
                    let buttonContainer;
                    if (context === 'settings') {
                        // In settings context, find the button container div
                        buttonContainer = input.closest('div').querySelector('div[style*="display: flex"]');
                    } else {
                        // In appearance context, find the button container div
                        buttonContainer = input.nextElementSibling;
                    }
                    
                    if (buttonContainer) {
                        // Check if remove button already exists
                        const existingRemoveBtn = buttonContainer.querySelector('.btn-danger');
                        if (!existingRemoveBtn) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-danger btn-small';
                            removeBtn.textContent = 'Remove';
                            removeBtn.onclick = () => removeImage(type, context);
                            buttonContainer.appendChild(removeBtn);
                        }
                    }
                    
                    // Clear input
                    input.value = '';
                    
                    showToast(data.message || 'Image uploaded successfully!', 'success');
                } else {
                    showToast(data.error || 'Failed to upload image', 'error');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                console.error('Error stack:', error.stack);
                showToast(error.message || 'An error occurred while uploading', 'error');
            });
        }
        
        function removeImage(type, context = 'appearance') {
            // Only handle profile images
            if (type !== 'profile') {
                console.error('Invalid image type:', type);
                showToast('Invalid image type', 'error');
                return;
            }
            
            if (!confirm('Are you sure you want to remove the profile image?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'remove_image');
            formData.append('type', 'profile');
            formData.append('csrf_token', csrfToken);
            
            showToast('Removing image...', 'info');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Remove image failed - response:', text);
                        throw new Error(text || 'Failed to remove image');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Remove image response:', data);
                console.log('Response success:', data.success);
                console.log('Response error:', data.error);
                
                if (data.success) {
                    const previewId = context === 'settings' ? 'profile-preview-settings' : 'profile-preview';
                    const preview = document.getElementById(previewId);
                    
                    if (preview) {
                        // Replace img with placeholder div
                        const placeholder = document.createElement('div');
                        placeholder.id = previewId;
                        placeholder.style.cssText = 'width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;';
                        placeholder.textContent = 'No image';
                        preview.parentNode.replaceChild(placeholder, preview);
                    } else {
                        console.error('Preview element not found:', previewId);
                    }
                    
                    // Remove remove button
                    const inputId = context === 'settings' ? 'profile-image-input-settings' : 'profile-image-input';
                    const input = document.getElementById(inputId);
                    
                    if (input) {
                        const buttonContainer = input.nextElementSibling;
                        if (buttonContainer) {
                            const removeBtn = buttonContainer.querySelector('.btn-danger');
                            if (removeBtn) {
                                removeBtn.remove();
                                console.log('Remove button removed successfully');
                            } else {
                                console.warn('Remove button not found in container');
                            }
                        } else {
                            console.warn('Button container not found');
                        }
                    } else {
                        console.error('Input element not found:', inputId);
                    }
                    
                    showToast('Profile image removed successfully', 'success');
                } else {
                    showToast(data.error || 'Failed to remove image', 'error');
                    console.error('Remove image failed:', data.error);
                }
            })
            .catch(error => {
                console.error('Remove image error:', error);
                showToast(error.message || 'An error occurred while removing image', 'error');
            });
        }
        
        // Social Icons Management
        function showAddDirectoryForm() {
            document.getElementById('directory-form').reset();
            document.getElementById('directory-modal').style.display = 'block';
        }
        
        function closeDirectoryModal() {
            document.getElementById('directory-modal').style.display = 'none';
        }
        
        function deleteDirectory(directoryId) {
            if (!confirm('Are you sure you want to delete this social icon?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_directory');
            formData.append('directory_id', directoryId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Social icon deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to delete social icon', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
        }
        
        // Handle directory form submission
        const directoryForm = document.getElementById('directory-form');
        if (directoryForm) {
            directoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_directory');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDirectoryModal();
                    showMessage('Social icon added successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to add social icon', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
            });
        }
        
        // ========== Theme System JavaScript Functions ==========
        
        // Helper function to build appearance form data (must be global for inline handlers)
        function saveAppearanceForm() {
            if (!document.getElementById('appearance-form')) return;
            
            autoSaveForm('appearance-form', 'update_appearance', () => {
                    const formData = new FormData();
                    const selectedRadio = document.querySelector('input[name="theme_id"]:checked');
                    const themeId = selectedRadio ? selectedRadio.value : '';
                    
                    const layoutEl = document.getElementById('layout_option');
                    const primaryColorEl = document.getElementById('custom_primary_color');
                    const secondaryColorEl = document.getElementById('custom_secondary_color');
                    const accentColorEl = document.getElementById('custom_accent_color');
                    
                    if (!layoutEl || !primaryColorEl || !secondaryColorEl || !accentColorEl) {
                        console.error('Required appearance form elements not found');
                        return null;
                    }
                    
                    const layout = layoutEl.value;
                    const primaryColor = primaryColorEl.value;
                    const secondaryColor = secondaryColorEl.value;
                    const accentColor = accentColorEl.value;
                    
                    // Legacy fonts (for backward compatibility)
                    const headingFont = document.getElementById('custom_heading_font');
                    const bodyFont = document.getElementById('custom_body_font');
                    if (headingFont && headingFont.value) formData.append('custom_heading_font', headingFont.value);
                    if (bodyFont && bodyFont.value) formData.append('custom_body_font', bodyFont.value);
                    
                    // Page fonts
                    const pagePrimaryFont = document.getElementById('page_primary_font');
                    const pageSecondaryFont = document.getElementById('page_secondary_font');
                    if (pagePrimaryFont && pagePrimaryFont.value) formData.append('page_primary_font', pagePrimaryFont.value);
                    if (pageSecondaryFont && pageSecondaryFont.value) formData.append('page_secondary_font', pageSecondaryFont.value);
                    
                    // Widget fonts
                    const widgetPrimaryFont = document.getElementById('widget_primary_font');
                    const widgetSecondaryFont = document.getElementById('widget_secondary_font');
                    if (widgetPrimaryFont && widgetPrimaryFont.value) formData.append('widget_primary_font', widgetPrimaryFont.value);
                    if (widgetSecondaryFont && widgetSecondaryFont.value) formData.append('widget_secondary_font', widgetSecondaryFont.value);
                    
                    formData.append('theme_id', themeId);
                    formData.append('layout_option', layout);
                    formData.append('custom_primary_color', primaryColor);
                    formData.append('custom_secondary_color', secondaryColor);
                    formData.append('custom_accent_color', accentColor);
                    
                    // Page background
                    const pageBackground = document.getElementById('page_background');
                    if (pageBackground && pageBackground.value) formData.append('page_background', pageBackground.value);
                    
                    // Widget background
                    const widgetBackground = document.getElementById('widget_background');
                    if (widgetBackground && widgetBackground.value) formData.append('widget_background', widgetBackground.value);
                    
                    // Widget border color
                    const widgetBorderColor = document.getElementById('widget_border_color');
                    if (widgetBorderColor && widgetBorderColor.value) formData.append('widget_border_color', widgetBorderColor.value);
                    
                    const spatialEffect = document.getElementById('spatial_effect');
                    if (spatialEffect && spatialEffect.value) formData.append('spatial_effect', spatialEffect.value);
                    
                    // Widget styles
                    const borderWidthEl = document.getElementById('widget_border_width');
                    const borderEffectEl = document.getElementById('widget_border_effect');
                    const shadowIntensityEl = document.getElementById('widget_border_shadow_intensity');
                    const glowIntensityEl = document.getElementById('widget_border_glow_intensity');
                    const glowColorEl = document.getElementById('widget_glow_color_hidden');
                    const spacingEl = document.getElementById('widget_spacing');
                    const shapeEl = document.getElementById('widget_shape');
                    
                    const widgetStyles = {
                        border_width: borderWidthEl ? borderWidthEl.value : 'medium',
                        border_effect: borderEffectEl ? borderEffectEl.value : 'shadow',
                        border_shadow_intensity: shadowIntensityEl ? shadowIntensityEl.value : 'subtle',
                        border_glow_intensity: glowIntensityEl ? glowIntensityEl.value : 'none',
                        glow_color: glowColorEl ? glowColorEl.value : '#ff00ff',
                        spacing: spacingEl ? spacingEl.value : 'comfortable',
                        shape: shapeEl ? shapeEl.value : 'rounded'
                    };
                    formData.append('widget_styles', JSON.stringify(widgetStyles));
                    
                    return formData;
                });
        }
        
        // Page Background Functions
        function switchBackgroundType(type) {
            const solidOption = document.getElementById('bg-solid-option');
            const gradientOption = document.getElementById('bg-gradient-option');
            const buttons = document.querySelectorAll('.bg-type-btn');
            
            buttons.forEach(btn => {
                if (btn.dataset.type === type) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                    btn.style.fontWeight = '600';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                    btn.style.fontWeight = '500';
                }
            });
            
            if (type === 'solid') {
                solidOption.style.display = '';
                gradientOption.style.display = 'none';
            } else {
                solidOption.style.display = 'none';
                gradientOption.style.display = '';
            }
        }
        
        function updatePageBackground() {
            const colorPicker = document.getElementById('page_background_color');
            const hexInput = document.getElementById('page_background_color_hex');
            const swatch = document.getElementById('page-bg-swatch');
            const hidden = document.getElementById('page_background');
            
            if (!colorPicker || !hexInput || !swatch || !hidden) {
                console.error('Page background elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            swatch.style.background = color;
            hidden.value = color;
            
            saveAppearanceForm();
        }
        
        function updatePageBackgroundFromHex() {
            const hexInput = document.getElementById('page_background_color_hex');
            
            // Add null check
            if (!hexInput) {
                console.error('page_background_color_hex element not found');
                return;
            }
            
            let hex = hexInput.value.trim();
            
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                const colorPicker = document.getElementById('page_background_color');
                const swatch = document.getElementById('page-bg-swatch');
                const hidden = document.getElementById('page_background');
                
                colorPicker.value = hex;
                swatch.style.background = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                saveAppearanceForm();
            } else {
                alert('Please enter a valid hex color (e.g., #ffffff)');
            }
        }
        
        function updateGradient() {
            const startColorEl = document.getElementById('gradient_start_color');
            const endColorEl = document.getElementById('gradient_end_color');
            const directionEl = document.getElementById('gradient_direction');
            const preview = document.getElementById('gradient-preview');
            const hidden = document.getElementById('page_background');
            const startSwatch = document.getElementById('gradient-start-swatch');
            const endSwatch = document.getElementById('gradient-end-swatch');
            
            if (!startColorEl || !endColorEl || !directionEl || !preview || !hidden || !startSwatch || !endSwatch) {
                console.error('Gradient elements not found');
                return;
            }
            
            const startColor = startColorEl.value;
            const endColor = endColorEl.value;
            const direction = directionEl.value;
            
            const gradient = `linear-gradient(${direction}, ${startColor} 0%, ${endColor} 100%)`;
            preview.style.background = gradient;
            hidden.value = gradient;
            startSwatch.style.background = startColor;
            endSwatch.style.background = endColor;
            
            saveAppearanceForm();
        }
        
        // Widget Style Functions
        function updateWidgetStyle(field, value) {
            const hiddenField = document.getElementById('widget_' + field);
            if (hiddenField) {
                hiddenField.value = value;
            }
            
            // Update button states
            const buttons = document.querySelectorAll(`[data-field="${field}"]`);
            buttons.forEach(btn => {
                if (btn.dataset.value === value) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                    btn.style.fontWeight = '600';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                    btn.style.fontWeight = '500';
                }
            });
            
            saveAppearanceForm();
        }
        
        function switchBorderEffect(effect) {
            const shadowOptions = document.getElementById('shadow-options');
            const glowOptions = document.getElementById('glow-options');
            const hidden = document.getElementById('widget_border_effect');
            const buttons = document.querySelectorAll('.border-effect-btn');
            
            hidden.value = effect;
            
            buttons.forEach(btn => {
                if (btn.dataset.effect === effect) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                    btn.style.fontWeight = '600';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                    btn.style.fontWeight = '500';
                }
            });
            
            if (effect === 'shadow') {
                shadowOptions.style.display = '';
                glowOptions.style.display = 'none';
            } else {
                shadowOptions.style.display = 'none';
                glowOptions.style.display = '';
            }
            
            saveAppearanceForm();
        }
        
        function updateGlowColor() {
            const colorPicker = document.getElementById('widget_glow_color');
            const hexInput = document.getElementById('widget_glow_color_hex');
            const swatch = document.getElementById('glow-color-swatch');
            const hidden = document.getElementById('widget_glow_color_hidden');
            
            if (!colorPicker || !hexInput || !swatch || !hidden) {
                console.error('Glow color elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            swatch.style.background = color;
            hidden.value = color;
            
            saveAppearanceForm();
        }
        
        function updateGlowColorFromHex() {
            const hexInput = document.getElementById('widget_glow_color_hex');
            
            if (!hexInput) {
                console.error('widget_glow_color_hex element not found');
                return;
            }
            
            let hex = hexInput.value.trim();
            
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                const colorPicker = document.getElementById('widget_glow_color');
                const swatch = document.getElementById('glow-color-swatch');
                const hidden = document.getElementById('widget_glow_color_hidden');
                
                if (!colorPicker || !swatch || !hidden) {
                    console.error('Glow color elements not found');
                    return;
                }
                
                colorPicker.value = hex;
                swatch.style.background = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                saveAppearanceForm();
            } else {
                alert('Please enter a valid hex color (e.g., #ff00ff)');
            }
        }
        
        function updateSpatialEffect(effect) {
            const hidden = document.getElementById('spatial_effect');
            const buttons = document.querySelectorAll('.spatial-effect-btn');
            
            hidden.value = effect;
            
            buttons.forEach(btn => {
                if (btn.dataset.effect === effect) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                }
            });
            
            saveAppearanceForm();
        }
        
        // Widget Background Functions
        function switchWidgetBackgroundType(type) {
            const solidOption = document.getElementById('widget-bg-solid-option');
            const gradientOption = document.getElementById('widget-bg-gradient-option');
            const buttons = document.querySelectorAll('.widget-bg-type-btn');
            
            buttons.forEach(btn => {
                if (btn.dataset.type === type) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                }
            });
            
            if (type === 'solid') {
                solidOption.style.display = '';
                gradientOption.style.display = 'none';
            } else {
                solidOption.style.display = 'none';
                gradientOption.style.display = '';
            }
        }
        
        function updateWidgetBackground() {
            const colorPicker = document.getElementById('widget_background_color');
            const hexInput = document.getElementById('widget_background_color_hex');
            const swatch = document.getElementById('widget-bg-swatch');
            const hidden = document.getElementById('widget_background');
            
            if (!colorPicker || !hexInput || !swatch || !hidden) {
                console.error('Widget background elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            swatch.style.background = color;
            hidden.value = color;
            
            saveAppearanceForm();
        }
        
        function updateWidgetBackgroundFromHex() {
            const hexInput = document.getElementById('widget_background_color_hex');
            
            // Add null check
            if (!hexInput) {
                console.error('widget_background_color_hex element not found');
                return;
            }
            
            let hex = hexInput.value.trim();
            
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                const colorPicker = document.getElementById('widget_background_color');
                const swatch = document.getElementById('widget-bg-swatch');
                const hidden = document.getElementById('widget_background');
                
                colorPicker.value = hex;
                swatch.style.background = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                saveAppearanceForm();
            } else {
                alert('Please enter a valid hex color (e.g., #ffffff)');
            }
        }
        
        function updateWidgetGradient() {
            const startColorEl = document.getElementById('widget_gradient_start_color');
            const endColorEl = document.getElementById('widget_gradient_end_color');
            const directionEl = document.getElementById('widget_gradient_direction');
            const preview = document.getElementById('widget-gradient-preview');
            const hidden = document.getElementById('widget_background');
            
            if (!startColorEl || !endColorEl || !directionEl || !preview || !hidden) {
                console.error('Widget gradient elements not found');
                return;
            }
            
            const startColor = startColorEl.value;
            const endColor = endColorEl.value;
            const direction = directionEl.value;
            
            const gradient = `linear-gradient(${direction}, ${startColor} 0%, ${endColor} 100%)`;
            preview.style.background = gradient;
            hidden.value = gradient;
            
            // Update swatches
            const startSwatch = document.getElementById('widget-gradient-start-swatch');
            const endSwatch = document.getElementById('widget-gradient-end-swatch');
            if (startSwatch) startSwatch.style.background = startColor;
            if (endSwatch) endSwatch.style.background = endColor;
            
            saveAppearanceForm();
        }
        
        // Widget Border Color Functions
        function switchWidgetBorderType(type) {
            const solidOption = document.getElementById('widget-border-solid-option');
            const gradientOption = document.getElementById('widget-border-gradient-option');
            const buttons = document.querySelectorAll('.widget-border-type-btn');
            
            buttons.forEach(btn => {
                if (btn.dataset.type === type) {
                    btn.classList.add('active');
                    btn.style.borderColor = '#0066ff';
                    btn.style.background = '#0066ff';
                    btn.style.color = 'white';
                } else {
                    btn.classList.remove('active');
                    btn.style.borderColor = '#ddd';
                    btn.style.background = 'white';
                    btn.style.color = '#666';
                }
            });
            
            if (type === 'solid') {
                solidOption.style.display = '';
                gradientOption.style.display = 'none';
            } else {
                solidOption.style.display = 'none';
                gradientOption.style.display = '';
            }
        }
        
        function updateWidgetBorderColor() {
            const colorPicker = document.getElementById('widget_border_color_picker');
            const hexInput = document.getElementById('widget_border_color_hex');
            const swatch = document.getElementById('widget-border-swatch');
            const hidden = document.getElementById('widget_border_color');
            
            if (!colorPicker || !hexInput || !swatch || !hidden) {
                console.error('Widget border color elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            swatch.style.background = color;
            hidden.value = color;
            
            saveAppearanceForm();
        }
        
        function updateWidgetBorderColorFromHex() {
            const hexInput = document.getElementById('widget_border_color_hex');
            
            // Add null check
            if (!hexInput) {
                console.error('widget_border_color_hex element not found');
                return;
            }
            
            let hex = hexInput.value.trim();
            
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                const colorPicker = document.getElementById('widget_border_color_picker');
                const swatch = document.getElementById('widget-border-swatch');
                const hidden = document.getElementById('widget_border_color');
                
                colorPicker.value = hex;
                swatch.style.background = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                saveAppearanceForm();
            } else {
                alert('Please enter a valid hex color (e.g., #000000)');
            }
        }
        
        function updateWidgetBorderGradient() {
            const startColorEl = document.getElementById('widget_border_gradient_start_color');
            const endColorEl = document.getElementById('widget_border_gradient_end_color');
            const directionEl = document.getElementById('widget_border_gradient_direction');
            const preview = document.getElementById('widget-border-gradient-preview');
            const hidden = document.getElementById('widget_border_color');
            
            if (!startColorEl || !endColorEl || !directionEl || !preview || !hidden) {
                console.error('Widget border gradient elements not found');
                return;
            }
            
            const startColor = startColorEl.value;
            const endColor = endColorEl.value;
            const direction = directionEl.value;
            
            const gradient = `linear-gradient(${direction}, ${startColor} 0%, ${endColor} 100%)`;
            preview.style.background = gradient;
            hidden.value = gradient;
            
            // Update swatches
            const startSwatch = document.getElementById('widget-border-gradient-start-swatch');
            const endSwatch = document.getElementById('widget-border-gradient-end-swatch');
            if (startSwatch) startSwatch.style.background = startColor;
            if (endSwatch) endSwatch.style.background = endColor;
            
            saveAppearanceForm();
        }
        
        // Widget Settings Drawer Functions
        function showWidgetSettingsDrawer(themeId) {
            const drawer = document.getElementById('widget-settings-drawer');
            const overlay = document.getElementById('widget-settings-drawer-overlay');
            const drawerBody = document.getElementById('widget-settings-drawer-body');
            
            if (!drawer || !overlay) return;
            
            // Show loading state
            drawerBody.innerHTML = `
                <div style="text-align: center; color: #666; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Loading widget settings...</p>
                </div>
            `;
            
            // Show drawer
            drawer.style.display = 'block';
            overlay.style.display = 'block';
            setTimeout(() => {
                drawer.classList.add('active');
                overlay.classList.add('active');
            }, 10);
            
            // Load theme data
            fetch('/api/themes.php?id=' + themeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.theme) {
                        renderWidgetSettings(data.theme);
                    } else {
                        drawerBody.innerHTML = '<p style="color: #ef4444;">Failed to load theme settings.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading theme:', error);
                    drawerBody.innerHTML = '<p style="color: #ef4444;">Error loading theme settings.</p>';
                });
        }
        
        function renderWidgetSettings(theme) {
            const drawerBody = document.getElementById('widget-settings-drawer-body');
            
            // Parse theme data
            const colors = theme.colors ? JSON.parse(theme.colors) : {};
            const fonts = theme.fonts ? JSON.parse(theme.fonts) : {};
            const widgetStyles = theme.widget_styles ? JSON.parse(theme.widget_styles) : {};
            
            const pagePrimaryFont = theme.page_primary_font || fonts.heading || 'Inter';
            const pageSecondaryFont = theme.page_secondary_font || fonts.body || 'Inter';
            const widgetPrimaryFont = theme.widget_primary_font || pagePrimaryFont;
            const widgetSecondaryFont = theme.widget_secondary_font || pageSecondaryFont;
            const widgetBackground = theme.widget_background || '#ffffff';
            const widgetBorderColor = theme.widget_border_color || '#000000';
            
            // Build preview HTML
            const html = `
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Sample Widget Preview</h3>
                    <div style="padding: 1rem; background: ${widgetBackground}; border: 2px solid ${widgetBorderColor}; border-radius: 12px; margin-bottom: 1rem;">
                        <div style="font-family: '${widgetPrimaryFont}', sans-serif; font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; color: ${colors.primary || '#000000'};">Widget Title</div>
                        <div style="font-family: '${widgetSecondaryFont}', sans-serif; font-size: 0.875rem; color: ${colors.primary || '#000000'}; opacity: 0.8;">This is a sample widget showing how content will look with this theme's widget settings.</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #666;">Widget Background</h4>
                    <div style="width: 100%; height: 60px; background: ${widgetBackground}; border: 2px solid #ddd; border-radius: 8px;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #666;">Widget Border Color</h4>
                    <div style="width: 100%; height: 60px; background: ${widgetBorderColor}; border: 2px solid #ddd; border-radius: 8px;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #666;">Widget Fonts</h4>
                    <div style="padding: 0.75rem; background: #f9f9f9; border-radius: 8px;">
                        <div style="font-family: '${widgetPrimaryFont}', sans-serif; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Primary: ${widgetPrimaryFont}</div>
                        <div style="font-family: '${widgetSecondaryFont}', sans-serif; font-size: 0.875rem; color: #666;">Secondary: ${widgetSecondaryFont}</div>
                    </div>
                </div>
                
                ${widgetStyles.border_width ? `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #666;">Widget Structure</h4>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="padding: 0.375rem 0.75rem; background: #f0f0f0; border-radius: 6px; font-size: 0.75rem;">Border: ${widgetStyles.border_width}</span>
                        <span style="padding: 0.375rem 0.75rem; background: #f0f0f0; border-radius: 6px; font-size: 0.75rem;">Shape: ${widgetStyles.shape || 'rounded'}</span>
                        <span style="padding: 0.375rem 0.75rem; background: #f0f0f0; border-radius: 6px; font-size: 0.75rem;">Spacing: ${widgetStyles.spacing || 'comfortable'}</span>
                    </div>
                </div>
                ` : ''}
            `;
            
            drawerBody.innerHTML = html;
        }
        
        function closeWidgetSettingsDrawer() {
            const drawer = document.getElementById('widget-settings-drawer');
            const overlay = document.getElementById('widget-settings-drawer-overlay');
            
            if (drawer) {
                drawer.classList.remove('active');
                setTimeout(() => {
                    drawer.style.display = 'none';
                }, 300);
            }
            
            if (overlay) {
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 300);
            }
        }
        
        // Save Theme Function
        function saveTheme() {
            const themeName = document.getElementById('theme_name').value.trim();
            
            if (!themeName) {
                alert('Please enter a theme name');
                return;
            }
            
            if (themeName.length > 100) {
                alert('Theme name must be 100 characters or less');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_theme');
            formData.append('csrf_token', csrfToken);
            formData.append('theme_name', themeName);
            
            // Get current colors
            formData.append('custom_primary_color', document.getElementById('custom_primary_color').value);
            formData.append('custom_secondary_color', document.getElementById('custom_secondary_color').value);
            formData.append('custom_accent_color', document.getElementById('custom_accent_color').value);
            
            // Get current fonts
            formData.append('custom_heading_font', document.getElementById('custom_heading_font').value);
            formData.append('custom_body_font', document.getElementById('custom_body_font').value);
            
            // Get page background
            const pageBackground = document.getElementById('page_background').value;
            if (pageBackground) formData.append('page_background', pageBackground);
            
            // Get spatial effect
            const spatialEffect = document.getElementById('spatial_effect').value;
            if (spatialEffect) formData.append('spatial_effect', spatialEffect);
            
            // Get widget styles
            const widgetStyles = {
                border_width: document.getElementById('widget_border_width').value,
                border_effect: document.getElementById('widget_border_effect').value,
                border_shadow_intensity: document.getElementById('widget_border_shadow_intensity').value,
                border_glow_intensity: document.getElementById('widget_border_glow_intensity').value,
                glow_color: document.getElementById('widget_glow_color_hidden').value,
                spacing: document.getElementById('widget_spacing').value,
                shape: document.getElementById('widget_shape').value
            };
            formData.append('widget_styles', JSON.stringify(widgetStyles));
            
            showMessage('Saving theme...', 'info');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Theme saved successfully!', 'success');
                    document.getElementById('theme_name').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.error || 'Failed to save theme', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
        }
        
        // Delete Theme Function
        function deleteUserTheme(themeId) {
            if (!confirm('Are you sure you want to delete this theme? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_theme');
            formData.append('csrf_token', csrfToken);
            formData.append('theme_id', themeId);
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Theme deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to delete theme', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
        }
        // Close directory modal on outside click
        const directoryModal = document.getElementById('directory-modal');
        if (directoryModal) {
            directoryModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeDirectoryModal();
                }
            });
        }
    <?php else: ?>
        // Update username preview as user types for page creation form
        document.getElementById('username').addEventListener('input', function(e) {
            const preview = document.getElementById('username-preview');
            if (preview) {
                preview.textContent = e.target.value || 'your-username';
            }
        });
    <?php endif; ?>
    </script>
</body>
</html>

