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
$userThemeCount = count($userThemes);

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

// Generate page URL for preview
$pageUrl = $page ? (APP_URL . '/' . $page['username']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Page - <?php echo h(APP_NAME); ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%230066ff'/><text x='50' y='70' font-size='60' font-weight='bold' text-anchor='middle' fill='white' font-family='Arial, sans-serif'>P</text></svg>">
    <link rel="alternate icon" href="/favicon.php">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/croppie@2.6.5/croppie.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 12.5%, #f093fb 25%, #4facfe 37.5%, #00f2fe 50%, #87ceeb 62.5%, #ffd700 75%, #ffb366 87.5%, #ffa07a 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            overflow-x: hidden;
            overflow-y: hidden; /* Prevent body from extending beyond viewport */
            position: relative;
            height: 100vh; /* Constrain to exactly viewport height */
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: drift 25s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            animation: pulseGlow 8s ease-in-out infinite;
            z-index: 0;
        }
        
        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(360deg); }
        }
        
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        /* Top Navbar */
        .editor-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 1000;
            
        }
        
        .navbar-logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0066ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .navbar-logo:hover {
            color: #0052cc;
        }
        
        .navbar-center {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.75rem;
        }
        
        .navbar-url-field {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
        }
        
        .navbar-url-field span {
            color: #6b7280;
            font-weight: 500;
        }
        
        .navbar-copy-btn {
            background: none;
            border: none;
            color: #0066ff;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .navbar-copy-btn:hover {
            background: #e0f2fe;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
        }
        
        .editor-layout {
            display: flex;
            align-items: stretch; /* Ensure flex children fill parent height */
            height: calc(100vh - 64px);
            width: 100vw;
            position: relative;
            z-index: 1;
            margin-top: 64px; /* Account for navbar */
            overflow: visible; /* Allow children to handle scrolling */
        }
        
        /* Left Sidebar Navigation */
        .sidebar {
            width: 200px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(229, 231, 235, 0.8);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: fixed;
            left: 0;
            top: 64px; /* Below navbar */
            height: calc(100vh - 64px); /* Account for navbar */
            z-index: 100;
            
        }
        
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: none; /* Hide since logo is now in navbar */
        }
        
        /* Hide mobile-specific sidebar elements on desktop */
        .sidebar-mobile-url,
        .sidebar-mobile-user {
            display: none;
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
            font-size: 0.875rem;
        }
        
        .nav-item:hover {
            background: #f9fafb;
            color: #0066ff;
        }
        
        .nav-item.active {
            background: #ffffff;
            color: #0066ff;
            font-weight: 600;
        }
        
        .nav-item i {
            width: 18px;
            margin-right: 0.6rem;
            font-size: 1rem;
        }
        
        /* Three Column Layout: Sidebar | Editor Content | Preview */
        /* Note: .editor-layout styles are defined above at line 307 */
        
        /* Center Editor Area - Middle Column */
        .editor-main {
            flex: 1;
            margin-left: 200px;
            margin-right: 0;
            background: #f3f4f6;
            min-height: calc(100vh - 64px); /* Account for navbar - use min-height to cover rounding issues */
            height: 100%; /* Fill parent flex container */
            position: relative;
            z-index: 1;
            overflow: visible; /* Allow content to extend for scrolling */
            min-width: 0; /* Allow flex shrinking */
        }
        
        .editor-content {
            width: calc(100% + 240px); /* Extend width to include preview panel space */
            min-height: 100%;
            padding: 1rem;
            padding-right: calc(240px + 0rem + 6px); /* Space for preview (240px) + content padding (0rem) + scrollbar (6px) */
            position: relative;
            overflow-y: scroll; /* Enable scrolling */
            overflow-x: hidden;
            height: 100%;
            max-height: calc(100vh - 64px); /* Constrain to viewport */
            box-sizing: border-box;
            margin-right: -240px; /* Negative margin to extend scrollbar area beyond preview */
            font-weight: 300;
        }
        
        /* Ensure headers keep their specified font weights (override the 300) */
        .editor-content h1 {
            font-weight: 700;
        }
        
        .editor-content h2,
        .editor-content h3,
        .editor-content h4,
        .editor-content h5,
        .editor-content h6 {
            font-weight: 600;
        }
        
        /* Scrollbar uses browser default styling */
        
        /* Live Preview Panel - Third Column */
        #live-preview-panel {
            width: 240px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #f3f4f6; /* Match second column background */
            border-left: 1px solid #e5e7eb;
            overflow: hidden;
            transition: width 0.3s ease, opacity 0.3s ease;
            min-height: calc(100vh - 64px); /* Account for navbar - use min-height to cover rounding issues */
            height: 100%; /* Fill parent flex container */
            
            max-height: calc(100vh - 64px);
        }
        
        #live-preview-panel.collapsed {
            width: 0;
            opacity: 0;
            border-left: none;
            overflow: hidden;
            pointer-events: none;
        }
        
        #live-preview-panel.collapsed .preview-content {
            display: none;
        }
        
        /* Expand button when collapsed */
        .preview-expand-btn {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 100;
            
            transition: all 0.2s;
        }
        
        .preview-expand-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: #0066ff;
        }
        
        #live-preview-panel .preview-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
            flex-shrink: 0;
        }
        
        #live-preview-panel .preview-header h3 {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }
        
        #live-preview-panel .preview-content {
            flex: 1;
            min-height: 0; /* Allow flex shrinking */
            overflow: hidden;
            background: #f3f4f6;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 40px;
        }
        
        #live-preview-panel .preview-refresh-btn {
            margin: 1rem 1rem 0.5rem 1rem; /* Reduced bottom margin */
            padding: 0.625rem 1rem;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0; /* Prevent button compression */
        }
        
        #live-preview-panel .preview-refresh-btn:hover {
            background: #0052cc;
            transform: translateY(-1px);
            
        }
        
        /* Wrapper to constrain scaled container's layout width */
        #live-preview-panel .preview-iframe-wrapper {
            width: 195px; /* Matches scaled visual width (390px * 0.5) */
            height: 422px; /* Matches scaled visual height (844px * 0.5) */
            overflow: hidden;
            position: relative;
        }
        
        #live-preview-panel .preview-iframe-container {
            width: 390px;
            height: 844px;
            background: white;
            
            border-radius: 24px;
            overflow: hidden;
            transform: scale(0.5);
            transform-origin: top left;
            margin: 0;
            position: relative;
        }
        
        #live-preview-panel #preview-iframe {
            width: 390px;
            height: 844px;
            border: none;
            display: block;
        }
        
        .editor-header {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            transform: translateZ(0);
            will-change: transform;
        }
        
        .editor-header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .editor-content h2 {
            font-size: 1.1rem;
            font-weight: 600;
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
        
        /* Widget Utility Styles - Used in accordion headers */
        
        /* Widget Visibility Toggle */
        .widget-visibility-toggle {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 0.5rem;
            position: relative;
        }
        
        .widget-visibility-toggle input[type="checkbox"] {
            width: 40px;
            height: 20px;
            appearance: none;
            background: #cbd5e1;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .widget-visibility-toggle input[type="checkbox"]:checked {
            background: #0066ff;
        }
        
        .widget-visibility-toggle input[type="checkbox"]::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            top: 2px;
            left: 2px;
            transition: transform 0.2s;
        }
        
        .widget-visibility-toggle input[type="checkbox"]:checked::before {
            transform: translateX(20px);
        }
        
        .widget-featured-toggle {
            display: flex;
            align-items: center;
            margin-right: 0.5rem;
            position: relative;
            padding: 0.25rem;
        }
        
        .widget-featured-toggle .fa-star {
            transition: all 0.2s ease;
        }
        
        .widget-featured-toggle .fa-star.featured-active {
            color: #ffd700 !important;
            filter: drop-shadow(0 2px 4px rgba(255, 215, 0, 0.4));
            animation: featuredPulse 2s ease-in-out infinite;
        }
        
        .widget-featured-toggle:hover .fa-star {
            transform: scale(1.2);
            color: #ffd700;
        }
        
        @keyframes featuredPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Accordion section with inactive state */
        .accordion-section.inactive {
            opacity: 0.6;
        }
        
        .accordion-section.inactive .accordion-header {
            background: #e5e7eb;
        }
        
        /* Form styles for accordion content */
        .accordion-content .form-group {
            margin-bottom: 0.75rem;
        }
        
        .accordion-content .form-group:last-child {
            margin-bottom: 0;
        }
        
        .accordion-content .form-group label {
            display: block;
            margin-bottom: 0.375rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .accordion-content .form-group input,
        .accordion-content .form-group textarea,
        .accordion-content .form-group select {
            width: 100%;
            padding: 0.5rem 0.625rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.875rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
            
        }
        
        .accordion-content .form-group input:focus,
        .accordion-content .form-group textarea:focus,
        .accordion-content .form-group select:focus {
            outline: none;
            border-color: #0066ff;
        }
        
        .accordion-content .form-group small {
            display: block;
            margin-top: 0.375rem;
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .widget-accordion-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .widget-accordion-actions .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .widget-save-indicator {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .widget-save-indicator.saving {
            color: #0066ff;
        }
        
        .widget-save-indicator.saved {
            color: #059669;
        }
        
        /* ========================================
           DRAG AND DROP STYLES MODULE
           Can be extracted to: /css/editor-drag-drop.css
           ======================================== */
        .drag-handle {
            cursor: grab;
            color: #9ca3af;
            font-size: 1rem;
            margin-right: 0.75rem;
            transition: color 0.2s;
        }
        
        .drag-handle:hover {
            color: #6b7280;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .draggable-mirror {
            opacity: 0.8;
            background: white;
            border: 2px dashed #0066ff;
            border-radius: 12px;
            
            cursor: grabbing;
        }
        
        .draggable-source--is-dragging {
            opacity: 0.3;
            border: 2px dashed #cbd5e1;
        }
        
        .draggable--over {
            background-color: #f0f9ff;
            border-color: #0066ff;
        }
        
        /* Legacy widget-item styles for backward compatibility */
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
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
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
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .theme-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
            
            position: relative;
        }
        
        .theme-card:hover {
            border-color: #9ca3af;
            
        }
        
        .theme-card.theme-selected {
            border-color: #0066ff;
            border-width: 2px;
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
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999; /* Highest z-index - appears in front of everything */
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        
        .toast {
            border-radius: 8px;
            
            padding: 1rem 1.5rem;
            pointer-events: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
        }
        
        .toast.success {
            background: #10b981;
            color: #ffffff;
        }
        
        .toast.error {
            background: #ef4444;
            color: #ffffff;
        }
        
        .toast.info {
            background: #3b82f6;
            color: #ffffff;
        }
        
        .toast-message {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
            text-align: center;
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
            display: none;
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
            border-radius: 8px 8px 0 0;
            
            z-index: 2001;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 85vh;
            overflow: hidden;
            display: none;
            flex-direction: column;
        }
        
        .drawer.active {
            display: flex;
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
            padding: 0 1rem 1rem;
            -webkit-overflow-scrolling: touch;
        }
        
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            flex-shrink: 0;
        }
        
        .drawer-header h2 {
            margin: 0;
            font-size: 0.9rem;
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
            transition: border-color 0.2s;
            background: #ffffff;
        }
        
        .drawer .form-group input:focus,
        .drawer .form-group select:focus,
        .drawer .form-group textarea:focus {
            outline: none;
            border-color: #0066ff;
            
        }
        
        .drawer .form-group textarea {
            min-height: 70px;
            resize: vertical;
            font-family: inherit;
        }
        
        .drawer-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            padding: 0.75rem 1rem;
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
            border-radius: 4px;
            
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
            padding: 0.5rem 0.875rem;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 4px;
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
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .modal-header h2 {
            font-size: 1.125rem;
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
            padding: 1rem;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-actions {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        
        .widget-item.editing {
            border: 2px solid #0066ff;
            
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
        
        /* Mobile Hamburger Menu */
        .mobile-menu-toggle {
            display: none; /* Hidden by default, shown only on mobile */
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            cursor: pointer;
            
            transition: all 0.2s;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        
        .mobile-menu-toggle:hover {
            background: #f9fafb;
            
        }
        
        .mobile-menu-toggle i {
            font-size: 1.25rem;
            color: #111827;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .editor-main {
                margin-left: 0;
            }
            
            /* Hide preview panel on mobile */
            #live-preview-panel {
                display: none;
            }
            
            /* Show mobile-specific sidebar elements on mobile */
            .sidebar-mobile-url,
            .sidebar-mobile-user {
                display: block;
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
            }
            
            /* Hide desktop navbar elements on mobile */
            .navbar-center,
            .navbar-right {
                display: none !important;
            }
            
            /* Show hamburger menu on mobile */
            .mobile-menu-toggle {
                display: flex !important;
            }
            
            /* Reorder navbar items for mobile: logo left, hamburger right */
            .editor-navbar {
                justify-content: space-between;
            }
            
            .navbar-logo {
                order: 1;
            }
            
            .mobile-menu-toggle {
                order: 2;
                margin-right: 0;
            }
        }
        
        /* Accordion Styles for Appearance Section */
        .accordion-section {
            margin-bottom: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            
        }
        
        .accordion-header {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f9fafb;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
            transition: all 0.2s;
            text-align: left;
        }
        
        .accordion-header:hover {
            background: #f3f4f6;
        }
        
        .accordion-header i.fas {
            font-size: 1.1rem;
            color: #0066ff;
            width: 20px;
            text-align: center;
        }
        
        .accordion-header .accordion-icon {
            margin-left: auto;
            font-size: 0.875rem;
            transition: transform 0.3s linear;
            flex-shrink: 0;
        }
        
        /* Ensure controls in accordion header are positioned correctly */
        .accordion-header .widget-visibility-toggle,
        .accordion-header .widget-featured-toggle {
            flex-shrink: 0;
        }
        
        .accordion-section.expanded .accordion-icon {
            transform: rotate(180deg);
        }
        
        .accordion-content {
            display: none;
            opacity: 0;
            transition: none !important;
        }
        
        .accordion-content > * {
            padding: 1rem;
        }
        
        .accordion-section.expanded .accordion-content {
            display: block;
            opacity: 1;
            animation: fadeIn 0.15s linear forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Reset Button Styles */
        .reset-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: auto;
        }
        
        .reset-btn:hover {
            background: #f3f4f6;
            color: #0066ff;
        }
        
        /* Compact Button Groups */
        .button-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .button-group button {
            flex: 1;
            padding: 0.5rem 0.625rem;
            font-size: 0.875rem;
        }
        
        /* Inline Font Preview */
        .font-preview {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        /* Segmented Control Style */
        .segmented-control {
            display: flex;
            gap: 0;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            background: white;
            
        }
        
        .segmented-control button {
            flex: 1;
            padding: 0.5rem 0.625rem;
            border: none;
            border-right: 1px solid #e5e7eb;
            background: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .segmented-control button:last-child {
            border-right: none;
        }
        
        .segmented-control button.active {
            background: #0066ff;
            color: white;
        }
        
        .segmented-control button:not(.active) {
            color: #666;
        }
        
        .segmented-control button:not(.active):hover {
            background: #f9fafb;
        }
        
        /* User Menu Dropdown */
        .user-menu {
            position: relative;
            display: inline-block;
            transform: translateZ(0);
            will-change: transform;
            isolation: isolate;
            z-index: 10001;
        }
        
        .user-menu-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .user-menu-button:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .user-menu-button.active {
            background: #f0f7ff;
            border-color: #0066ff;
        }
        
        .user-menu-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0066ff 0%, #00aaff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        .user-menu-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }
        
        .user-menu-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
        
        .user-menu-email {
            color: #6b7280;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
        
        .user-menu-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            width: 240px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            overflow: hidden;
        }
        
        .user-menu-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-menu-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .user-menu-header-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .user-menu-header-email {
            color: #6b7280;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .user-menu-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .user-menu-status.verified {
            color: #059669;
            border-color: #10b981;
            background: #ecfdf5;
        }
        
        .user-menu-status.unverified {
            color: #dc2626;
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 0.875rem;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
        }
        
        .user-menu-item:hover {
            background: #f9fafb;
        }
        
        .user-menu-item i {
            width: 16px;
            color: #6b7280;
        }
        
        .user-menu-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 0.5rem 0;
        }
        
        .user-menu-icon {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        @media (max-width: 768px) {
            .editor-header {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .editor-header > div {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .user-menu {
                width: 100%;
            }
            
            .user-menu-button {
                width: 100%;
                justify-content: center;
            }
            
            .user-menu-dropdown {
                right: 0;
                left: 0;
                width: 100%;
            }
        }
        
        /* Crop Modal Styles */
        .crop-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .crop-modal-overlay.active {
            display: flex;
        }
        
        .crop-modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            
        }
        
        .crop-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .crop-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .crop-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
        }
        
        .crop-modal-close:hover {
            color: #111827;
        }
        
        .crop-modal-body {
            margin-bottom: 1.5rem;
        }
        
        #croppie-container {
            width: 100%;
            height: 400px;
        }
        
        .crop-modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .crop-modal-actions button {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .crop-modal-cancel {
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
        }
        
        .crop-modal-cancel:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .crop-modal-apply {
            background: #0066ff;
            border: 2px solid #0066ff;
            color: white;
        }
        
        .crop-modal-apply:hover {
            background: #0052cc;
            border-color: #0052cc;
        }
        
        @media (max-width: 640px) {
            .crop-modal {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            #croppie-container {
                height: 300px;
            }
        }
        
        /* Blog Section */
        .blog-section {
            padding-top: 2rem;
        }
        
        .blog-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .blog-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .blog-header p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .blog-placeholder {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 2px dashed rgba(229, 231, 235, 0.8);
            border-radius: 12px;
            padding: 4rem 2rem;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .blog-placeholder-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            
        }
        
        .blog-placeholder h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.75rem;
        }
        
        .blog-placeholder p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }
        
        /* Support Section */
        .support-section {
            padding-top: 2rem;
        }
        
        .support-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .support-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .support-header p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .support-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .support-card:hover {
            transform: translateY(-4px);
            
            border-color: #0066ff;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .support-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            
        }
        
        .support-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .support-card p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }
        
        @media (max-width: 640px) {
            .support-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .support-card {
                padding: 1.5rem 1rem;
            }
            
            .support-icon {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="editor-navbar">
        <!-- Mobile Menu Toggle (only visible on mobile) -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Logo -->
        <a href="/editor.php" class="navbar-logo">
            <i class="fas fa-podcast"></i>
            <?php echo h(APP_NAME); ?>
        </a>
        
        <!-- URL Field and Copy Button -->
        <div class="navbar-center">
            <?php if ($page && !empty($pageUrl)): ?>
            <div class="navbar-url-field">
                <span><?php echo h($pageUrl); ?></span>
                <button 
                    type="button" 
                    onclick="copyPageUrl()" 
                    id="copy-url-btn"
                    class="navbar-copy-btn"
                    title="Copy page URL">
                    <i class="fas fa-copy" id="copy-url-icon"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- User Menu -->
        <div class="navbar-right">
            <div class="user-menu">
                <button type="button" class="user-menu-button" onclick="toggleUserMenu()" id="user-menu-button">
                    <div class="user-menu-avatar">
                        <?php if ($page && !empty($page['profile_image'])): ?>
                            <img src="<?php echo h($page['profile_image']); ?>" alt="<?php echo h($page['username'] ?? 'User'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['email'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-menu-info">
                        <div class="user-menu-name"><?php echo h($page['username'] ?? 'New User'); ?></div>
                        <div class="user-menu-email"><?php echo h($user['email']); ?></div>
                    </div>
                    <i class="fas fa-chevron-down user-menu-icon"></i>
                </button>
                
                <div class="user-menu-dropdown" id="user-menu-dropdown">
                    <div class="user-menu-header">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <div class="user-menu-avatar" style="width: 48px; height: 48px;">
                                <?php if ($page && !empty($page['profile_image'])): ?>
                                    <img src="<?php echo h($page['profile_image']); ?>" alt="<?php echo h($page['username'] ?? 'User'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['email'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <div class="user-menu-header-name"><?php echo h($page['username'] ?? 'New User'); ?></div>
                                <div class="user-menu-header-email"><?php echo h($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="user-menu-status <?php echo $user['email_verified'] ? 'verified' : 'unverified'; ?>">
                            <i class="fas <?php echo $user['email_verified'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                        </div>
                    </div>
                    
                    <a href="javascript:void(0)" class="user-menu-item" onclick="showSection('account', document.querySelector('.nav-item[onclick*=\"account\"]')); closeUserMenu();">
                        <i class="fas fa-user"></i>
                        <span>Account Settings</span>
                    </a>
                    
                    <?php if ($page): ?>
                    <a href="/<?php echo h($page['username']); ?>" target="_blank" class="user-menu-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Page</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="user-menu-divider"></div>
                    
                    <a href="/logout.php" class="user-menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>
    
    <div class="editor-layout">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <!-- Mobile URL Field -->
            <?php if ($page && !empty($pageUrl)): ?>
            <div class="sidebar-mobile-url">
                <div class="sidebar-mobile-url-field">
                    <span><?php echo h($pageUrl); ?></span>
                    <button 
                        type="button" 
                        onclick="copyPageUrl()" 
                        class="sidebar-mobile-copy-btn"
                        title="Copy page URL">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Mobile User Menu -->
            <div class="sidebar-mobile-user">
                <div class="user-menu">
                    <button type="button" class="user-menu-button" onclick="toggleUserMenu(); toggleMobileMenu();" id="user-menu-button-mobile" style="width: 100%; justify-content: flex-start;">
                        <div class="user-menu-avatar">
                            <?php if ($page && !empty($page['profile_image'])): ?>
                                <img src="<?php echo h($page['profile_image']); ?>" alt="<?php echo h($page['username'] ?? 'User'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($user['email'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-menu-info">
                            <div class="user-menu-name"><?php echo h($page['username'] ?? 'New User'); ?></div>
                            <div class="user-menu-email"><?php echo h($user['email']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down user-menu-icon" style="margin-left: auto;"></i>
                    </button>
                    
                    <div class="user-menu-dropdown" id="user-menu-dropdown-mobile">
                        <div class="user-menu-header">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div class="user-menu-avatar" style="width: 48px; height: 48px;">
                                    <?php if ($page && !empty($page['profile_image'])): ?>
                                        <img src="<?php echo h($page['profile_image']); ?>" alt="<?php echo h($page['username'] ?? 'User'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['email'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <div class="user-menu-header-name"><?php echo h($page['username'] ?? 'New User'); ?></div>
                                    <div class="user-menu-header-email"><?php echo h($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="user-menu-status <?php echo $user['email_verified'] ? 'verified' : 'unverified'; ?>">
                                <i class="fas <?php echo $user['email_verified'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                            </div>
                        </div>
                        
                        <a href="javascript:void(0)" class="user-menu-item" onclick="showSection('account', document.querySelector('.nav-item[onclick*=\"account\"]')); closeUserMenu(); toggleMobileMenu();">
                            <i class="fas fa-user"></i>
                            <span>Account Settings</span>
                        </a>
                        
                        <?php if ($page): ?>
                        <a href="/<?php echo h($page['username']); ?>" target="_blank" class="user-menu-item">
                            <i class="fas fa-external-link-alt"></i>
                            <span>View Page</span>
                        </a>
                        <?php endif; ?>
                        
                        <div class="user-menu-divider"></div>
                        
                        <a href="/logout.php" class="user-menu-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
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
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('blog', this)">
                        <i class="fas fa-blog"></i>
                        <span>Blog</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('support', this)">
                        <i class="fas fa-life-ring"></i>
                        <span>Support</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('analytics', this)">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Center Editor - Middle Column -->
        <main class="editor-main">
                <div class="editor-content">
                <div class="editor-header">
                    <h1 style="margin: 0;"><?php echo $page ? 'Edit Your Page' : 'Create Your Page'; ?></h1>
                </div>
        
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
            
            <div id="widgets-list" class="widgets-list">
                <?php 
                // Helper function to get widget type icon
                function getWidgetTypeIcon($widgetType) {
                    $icons = [
                        'custom_link' => 'fa-link',
                        'youtube_video' => 'fa-youtube',
                        'text_html' => 'fa-text',
                        'image' => 'fa-image',
                        'podcast_player_custom' => 'fa-podcast',
                        'rss_feed' => 'fa-rss',
                        'video' => 'fa-video',
                        'email' => 'fa-envelope',
                        'calendar' => 'fa-calendar',
                        'social' => 'fa-share-alt'
                    ];
                    return $icons[$widgetType] ?? 'fa-puzzle-piece';
                }
                
                // Get widgets (try new method first, fallback to links for compatibility)
                // Use getAllWidgets() to include inactive widgets in editor
                $widgets = [];
                if ($page && method_exists($pageClass, 'getAllWidgets')) {
                    $widgets = $pageClass->getAllWidgets($page['id']);
                } elseif ($page && method_exists($pageClass, 'getWidgets')) {
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
                    <div style="padding: 2rem; text-align: center; color: #666;">
                        No widgets yet. Click "Add Widget" to browse the widget gallery and add content to your page.
                    </div>
                <?php else: ?>
                    <?php foreach ($widgets as $widget): 
                        $configData = is_string($widget['config_data'] ?? '') 
                            ? json_decode($widget['config_data'], true) 
                            : ($widget['config_data'] ?? []);
                        $widgetType = $widget['widget_type'] ?? 'custom_link';
                        $widgetIcon = getWidgetTypeIcon($widgetType);
                    ?>
                        <div class="accordion-section <?php echo !($widget['is_active'] ?? 1) ? 'inactive' : ''; ?>" data-widget-id="<?php echo $widget['id']; ?>" id="widget-<?php echo $widget['id']; ?>">
                            <button type="button" class="accordion-header" onclick="toggleAccordion('widget-<?php echo $widget['id']; ?>')">
                                <i class="fas fa-grip-vertical drag-handle" onclick="event.stopPropagation();"></i>
                                <i class="fas <?php echo $widgetIcon; ?>"></i>
                                <span style="flex: 1; text-align: left;">
                                    <div style="font-weight: 600; color: #111827;"><?php echo h($widget['title']); ?></div>
                                    <div style="font-size: 0.875rem; color: #6b7280; font-weight: normal;"><?php echo h($widgetType); ?></div>
                                </span>
                                <div class="widget-visibility-toggle" onclick="event.stopPropagation();">
                                    <input type="checkbox" 
                                           id="widget-visibility-<?php echo $widget['id']; ?>" 
                                           <?php echo ($widget['is_active'] ?? 1) ? 'checked' : ''; ?>
                                           onchange="toggleWidgetVisibility(<?php echo $widget['id']; ?>, this.checked)"
                                           title="<?php echo ($widget['is_active'] ?? 1) ? 'Visible' : 'Hidden'; ?>">
                                </div>
                                <?php 
                                $isFeatured = !empty($widget['is_featured']);
                                $featuredEffect = $widget['featured_effect'] ?? '';
                                ?>
                                <div class="widget-featured-toggle" onclick="event.stopPropagation(); toggleFeaturedWidget(<?php echo $widget['id']; ?>, <?php echo $isFeatured ? 'true' : 'false'; ?>)" title="<?php echo $isFeatured ? 'Featured Widget - ' . h($featuredEffect) : 'Make Featured Widget'; ?>">
                                    <i class="fas fa-star <?php echo $isFeatured ? 'featured-active' : ''; ?>" style="color: <?php echo $isFeatured ? '#ffd700' : '#9ca3af'; ?>; font-size: 1.1rem; cursor: pointer; transition: all 0.2s;"></i>
                                </div>
                                <i class="fas fa-chevron-down accordion-icon"></i>
                            </button>
                            <div class="accordion-content" id="widget-content-<?php echo $widget['id']; ?>">
                                <div style="text-align: center; color: #666;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 1rem;"></i>
                                    <p>Loading widget settings...</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                    <?php foreach ($socialIcons as $icon): 
                        // Get platform-specific icon
                        $platformName = $icon['platform_name'] ?? '';
                        $platformIcon = 'fas fa-share-alt'; // Default fallback
                        
                        // Map platform names to Font Awesome icons
                        $iconMap = [
                            // Podcast Platforms
                            'apple_podcasts' => 'fas fa-podcast',
                            'spotify' => 'fab fa-spotify',
                            'youtube_music' => 'fab fa-youtube',
                            'iheart_radio' => 'fas fa-heart',
                            'amazon_music' => 'fab fa-amazon',
                            // Social Media Platforms
                            'facebook' => 'fab fa-facebook',
                            'twitter' => 'fab fa-twitter',
                            'instagram' => 'fab fa-instagram',
                            'linkedin' => 'fab fa-linkedin',
                            'youtube' => 'fab fa-youtube',
                            'tiktok' => 'fab fa-tiktok',
                            'snapchat' => 'fab fa-snapchat',
                            'pinterest' => 'fab fa-pinterest',
                            'reddit' => 'fab fa-reddit',
                            'discord' => 'fab fa-discord',
                            'twitch' => 'fab fa-twitch',
                            'github' => 'fab fa-github',
                            'behance' => 'fab fa-behance',
                            'dribbble' => 'fab fa-dribbble',
                            'medium' => 'fab fa-medium'
                        ];
                        
                        if (!empty($iconMap[$platformName])) {
                            $platformIcon = $iconMap[$platformName];
                        }
                    ?>
                        <li class="accordion-section" data-directory-id="<?php echo $icon['id']; ?>" id="social-icon-<?php echo $icon['id']; ?>">
                            <button type="button" class="accordion-header" onclick="toggleAccordion('social-icon-<?php echo $icon['id']; ?>')">
                                <i class="fas fa-grip-vertical drag-handle" onclick="event.stopPropagation();"></i>
                                <i class="<?php echo $platformIcon; ?>"></i>
                                <span style="flex: 1; text-align: left;">
                                    <div style="font-weight: 600; color: #111827;"><?php echo h($icon['platform_name']); ?></div>
                                    <div style="font-size: 0.875rem; color: #6b7280; font-weight: normal;"><?php echo h($icon['url']); ?></div>
                                </span>
                                <i class="fas fa-chevron-down accordion-icon"></i>
                            </button>
                            <div class="accordion-content" id="social-icon-content-<?php echo $icon['id']; ?>">
                                    <form class="social-icon-edit-form" data-directory-id="<?php echo $icon['id']; ?>" onsubmit="saveSocialIcon(event, <?php echo $icon['id']; ?>)">
                                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                        
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label for="social-icon-platform-<?php echo $icon['id']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Platform</label>
                                            <select id="social-icon-platform-<?php echo $icon['id']; ?>" name="platform_name" required style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                                                <option value="">Select Platform</option>
                                                <?php
                                                $platforms = [
                                                    'apple_podcasts' => 'Apple Podcasts',
                                                    'spotify' => 'Spotify',
                                                    'youtube_music' => 'YouTube Music',
                                                    'iheart_radio' => 'iHeart Radio',
                                                    'amazon_music' => 'Amazon Music',
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
                                                    <option value="<?php echo h($key); ?>" <?php echo ($icon['platform_name'] === $key) ? 'selected' : ''; ?>><?php echo h($name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 1.5rem;">
                                            <label for="social-icon-url-<?php echo $icon['id']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">URL</label>
                                            <input type="url" id="social-icon-url-<?php echo $icon['id']; ?>" name="url" value="<?php echo h($icon['url']); ?>" required placeholder="https://..." style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                                        </div>
                                        
                                        <div class="widget-accordion-actions">
                                            <button type="button" class="btn btn-danger" onclick="deleteDirectory(<?php echo $icon['id']; ?>)">Delete</button>
                                            <button type="submit" class="btn btn-secondary">Save</button>
                                        </div>
                                    </form>
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
            <h2 style="margin-bottom: 1.5rem;">Appearance</h2>
            <form id="appearance-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <!-- Theme Accordion Section -->
                <div class="accordion-section" id="theme-selection">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('theme-selection')">
                        <i class="fas fa-palette"></i>
                        <span>Theme</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                        <div class="form-group">
                            <div class="theme-cards-container">
                                <!-- Theme Cards -->
                                <?php foreach ($allThemes as $theme): 
                                    $themeColors = parseThemeJson($theme['colors'], []);
                                    $primaryColor = $themeColors['primary'] ?? '#000000';
                                    $secondaryColor = $themeColors['secondary'] ?? '#ffffff';
                                    $accentColor = $themeColors['accent'] ?? '#0066ff';
                                    $isSelected = ($page['theme_id'] == $theme['id']);
                                    
                                    // Get theme's actual background, fallback to gradient from colors
                                    $themeBackground = !empty($theme['page_background']) 
                                        ? $theme['page_background'] 
                                        : "linear-gradient(135deg, {$primaryColor} 0%, {$accentColor} 100%)";
                                    
                                    // Get theme fonts for preview
                                    $themeFonts = parseThemeJson($theme['fonts'] ?? '{}', []);
                                    $themePagePrimaryFont = $theme['page_primary_font'] ?? $themeFonts['heading'] ?? 'Inter';
                                    $themePageSecondaryFont = $theme['page_secondary_font'] ?? $themeFonts['body'] ?? 'Inter';
                                    $themeWidgetPrimaryFont = $theme['widget_primary_font'] ?? $themePagePrimaryFont;
                                    $themeWidgetSecondaryFont = $theme['widget_secondary_font'] ?? $themePageSecondaryFont;
                                ?>
                                <div class="theme-card <?php echo $isSelected ? 'theme-selected' : ''; ?>" data-theme-id="<?php echo $theme['id']; ?>">
                                    <div class="theme-card-swatch" style="background: <?php echo h($themeBackground); ?>;" onclick="selectTheme(<?php echo $theme['id']; ?>)">
                                    </div>
                                    <div class="theme-card-body" onclick="selectTheme(<?php echo $theme['id']; ?>)">
                                        <div class="theme-card-name" style="font-family: '<?php echo h($themePagePrimaryFont); ?>', sans-serif;"><?php echo h($theme['name']); ?></div>
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
                    </div>
                </div>
                
                <!-- ========== FEATURED WIDGET EFFECT SECTION (ACCORDION) ========== -->
                <div class="accordion-section" id="featured-widget-effect">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('featured-widget-effect')">
                        <i class="fas fa-star" style="color: #ffd700;"></i>
                        <span>Featured Widget Effect</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                        <div class="form-group">
                            <label for="featured-effect-selector" style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Effect</label>
                            <?php
                            // Find currently featured widget
                            $featuredWidget = null;
                            $currentEffect = '';
                            if (!empty($widgets)) {
                                foreach ($widgets as $w) {
                                    if (!empty($w['is_featured'])) {
                                        $featuredWidget = $w;
                                        // Default to 'jiggle' if no effect is set
                                        $currentEffect = $w['featured_effect'] ?? 'jiggle';
                                        break;
                                    }
                                }
                            }
                            ?>
                            <select id="featured-effect-selector" 
                                    onchange="applyFeaturedEffect(this.value)"
                                    style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-size: 1rem;"
                                    <?php echo !$featuredWidget ? 'disabled' : ''; ?>>
                                <option value="">None</option>
                                <option value="jiggle" <?php echo ($currentEffect === 'jiggle' || (!$currentEffect && $featuredWidget)) ? 'selected' : ''; ?>>Jiggle 🎯</option>
                                <option value="burn" <?php echo $currentEffect === 'burn' ? 'selected' : ''; ?>>Burn 🔥</option>
                                <option value="rotating-glow" <?php echo $currentEffect === 'rotating-glow' ? 'selected' : ''; ?>>Rotating Glow 💫</option>
                                <option value="blink" <?php echo $currentEffect === 'blink' ? 'selected' : ''; ?>>Blink 👁️</option>
                                <option value="pulse" <?php echo $currentEffect === 'pulse' ? 'selected' : ''; ?>>Pulse 💓</option>
                                <option value="shake" <?php echo $currentEffect === 'shake' ? 'selected' : ''; ?>>Shake 📳</option>
                                <option value="sparkles" <?php echo $currentEffect === 'sparkles' ? 'selected' : ''; ?>>Sparkles ✨</option>
                            </select>
                            <small style="display: block; margin-top: 0.75rem; color: #666;">
                                <?php if ($featuredWidget): ?>
                                    Apply a special effect to your featured widget to make it stand out. Movement effects (Jiggle, Shake, Pulse, Rotating Glow) animate at random intervals.
                                <?php else: ?>
                                    First, mark a widget as featured using the star icon in the Widgets section.
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- ========== PAGE BACKGROUND SECTION (ACCORDION) ========== -->
                <div class="accordion-section" id="page-background">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('page-background')">
                        <i class="fas fa-fill-drip"></i>
                        <span>Page Background</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <div class="form-group">
                    <label>Background</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="bg-type-btn <?php echo !isGradient($pageBackground) ? 'active' : ''; ?>" data-type="solid" onclick="switchBackgroundType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($pageBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($pageBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($pageBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="bg-type-btn <?php echo isGradient($pageBackground) ? 'active' : ''; ?>" data-type="gradient" onclick="switchBackgroundType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($pageBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($pageBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($pageBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="bg-solid-option" class="bg-option" style="<?php echo isGradient($pageBackground) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="color" id="page_background_color" value="<?php echo isGradient($pageBackground) ? '#ffffff' : h($pageBackground); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updatePageBackground()">
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
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="color" id="gradient_start_color" value="<?php echo h($gradStart); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateGradient()">
                                        <button type="button" onclick="flipGradientColors('page')" title="Flip Colors" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#0066ff'; this.style.color='#0066ff';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd'; this.style.color='#666';">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <input type="color" id="gradient_end_color" value="<?php echo h($gradEnd); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateGradient()">
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
                    </div>
                </div>
                
                <!-- Page Colors -->
                <div class="accordion-section" id="page-colors">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('page-colors')">
                        <i class="fas fa-palette"></i>
                        <span>Page Colors</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <?php
                // Get theme colors with fallbacks using Theme class
                $colors = getThemeColors($page, $page['theme_id'] ? $themeClass->getTheme($page['theme_id']) : null);
                $customPrimary = $colors['primary'];
                $customSecondary = $colors['secondary'];
                $customAccent = $colors['accent'];
                ?>
                
                <div class="form-group">
                    <label for="custom_primary_color">Text and Borders</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" id="custom_primary_color" name="custom_primary_color" value="<?php echo h($customPrimary); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateColorSwatch('primary', this.value)">
                        <input type="text" id="custom_primary_color_hex" value="<?php echo h($customPrimary); ?>" placeholder="#000000" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('primary', this.value)">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="custom_secondary_color">Element Background</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" id="custom_secondary_color" name="custom_secondary_color" value="<?php echo h($customSecondary); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateColorSwatch('secondary', this.value)">
                        <input type="text" id="custom_secondary_color_hex" value="<?php echo h($customSecondary); ?>" placeholder="#ffffff" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('secondary', this.value)">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="custom_accent_color">Highlights and Links</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" id="custom_accent_color" name="custom_accent_color" value="<?php echo h($customAccent); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateColorSwatch('accent', this.value)">
                        <input type="text" id="custom_accent_color_hex" value="<?php echo h($customAccent); ?>" placeholder="#0066ff" style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" onchange="updateColorFromHex('accent', this.value)">
                    </div>
                </div>
                    </div>
                </div>
                
                <!-- Page Fonts -->
                <div class="accordion-section" id="page-fonts">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('page-fonts')">
                        <i class="fas fa-font"></i>
                        <span>Page Fonts</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <?php
                // Get Google Fonts list from helper function
                $googleFonts = getGoogleFontsList();
                $pagePrimaryFont = $pageFonts['page_primary_font'] ?? 'Inter';
                $pageSecondaryFont = $pageFonts['page_secondary_font'] ?? 'Inter';
                ?>
                
                <div class="form-group">
                    <label for="page_primary_font">Titles and Headings Font</label>
                    <select id="page_primary_font" name="page_primary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $category => $fonts): ?>
                            <optgroup label="<?php echo h($category); ?>">
                                <?php foreach ($fonts as $fontValue => $fontName): ?>
                                    <option value="<?php echo h($fontValue); ?>" <?php echo ($pagePrimaryFont == $fontValue) ? 'selected' : ''; ?>>
                                        <?php echo h($fontName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <div id="inline-page-primary-preview" class="font-preview" style="font-family: '<?php echo h($pagePrimaryFont); ?>', sans-serif; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px; font-size: 0.875rem; color: #374151; border: 1px solid #e5e7eb;">Sample Page Heading Text</div>
                </div>
                
                <div class="form-group">
                    <label for="page_secondary_font">Page Body Text and Description Font</label>
                    <select id="page_secondary_font" name="page_secondary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $category => $fonts): ?>
                            <optgroup label="<?php echo h($category); ?>">
                                <?php foreach ($fonts as $fontValue => $fontName): ?>
                                    <option value="<?php echo h($fontValue); ?>" <?php echo ($pageSecondaryFont == $fontValue) ? 'selected' : ''; ?>>
                                        <?php echo h($fontName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <div id="inline-page-secondary-preview" class="font-preview" style="font-family: '<?php echo h($pageSecondaryFont); ?>', sans-serif; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px; font-size: 0.875rem; color: #374151; border: 1px solid #e5e7eb;">This is a preview of how your page body text will look with the selected font.</div>
                </div>
                    </div>
                </div>
                
                <!-- Widget Background -->
                <div class="accordion-section" id="widget-background">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('widget-background')">
                        <i class="fas fa-square"></i>
                        <span>Widget Background</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <div class="form-group">
                    <label>Background Type</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="widget-bg-type-btn <?php echo !isGradient($widgetBackground) ? 'active' : ''; ?>" data-type="solid" onclick="switchWidgetBackgroundType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($widgetBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($widgetBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($widgetBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="widget-bg-type-btn <?php echo isGradient($widgetBackground) ? 'active' : ''; ?>" data-type="gradient" onclick="switchWidgetBackgroundType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($widgetBackground) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($widgetBackground) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($widgetBackground) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="widget-bg-solid-option" class="widget-bg-option" style="<?php echo isGradient($widgetBackground) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="color" id="widget_background_color" value="<?php echo isGradient($widgetBackground) ? '#ffffff' : h($widgetBackground); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateWidgetBackground()">
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
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="color" id="widget_gradient_start_color" value="<?php echo h($widgetGradStart); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetGradient()">
                                        <button type="button" onclick="flipGradientColors('widget')" title="Flip Colors" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#0066ff'; this.style.color='#0066ff';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd'; this.style.color='#666';">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <input type="color" id="widget_gradient_end_color" value="<?php echo h($widgetGradEnd); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetGradient()">
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
                    </div>
                </div>
                
                <!-- Widget Border -->
                <div class="accordion-section" id="widget-border">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('widget-border')">
                        <i class="fas fa-border-all"></i>
                        <span>Widget Border</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <div class="form-group">
                    <label>Border Color Type</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <button type="button" class="widget-border-type-btn <?php echo !isGradient($widgetBorderColor) ? 'active' : ''; ?>" data-type="solid" onclick="switchWidgetBorderType('solid')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo !isGradient($widgetBorderColor) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo !isGradient($widgetBorderColor) ? '#0066ff' : 'white'; ?>; color: <?php echo !isGradient($widgetBorderColor) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Solid Color</button>
                        <button type="button" class="widget-border-type-btn <?php echo isGradient($widgetBorderColor) ? 'active' : ''; ?>" data-type="gradient" onclick="switchWidgetBorderType('gradient')" style="flex: 1; padding: 0.75rem; border: 2px solid <?php echo isGradient($widgetBorderColor) ? '#0066ff' : '#ddd'; ?>; border-radius: 8px; background: <?php echo isGradient($widgetBorderColor) ? '#0066ff' : 'white'; ?>; color: <?php echo isGradient($widgetBorderColor) ? 'white' : '#666'; ?>; cursor: pointer; font-weight: 600;">Gradient</button>
                    </div>
                    
                    <!-- Solid Color Option -->
                    <div id="widget-border-solid-option" class="widget-border-option" style="<?php echo isGradient($widgetBorderColor) ? 'display: none;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="color" id="widget_border_color_picker" value="<?php echo isGradient($widgetBorderColor) ? '#000000' : h($widgetBorderColor); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateWidgetBorderColor()">
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
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="color" id="widget_border_gradient_start_color" value="<?php echo h($widgetBorderGradStart); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBorderGradient()">
                                        <button type="button" onclick="flipGradientColors('border')" title="Flip Colors" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 8px; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#0066ff'; this.style.color='#0066ff';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd'; this.style.color='#666';">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600;">End Color</label>
                                    <input type="color" id="widget_border_gradient_end_color" value="<?php echo h($widgetBorderGradEnd); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;" onchange="updateWidgetBorderGradient()">
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
                    </div>
                </div>
                
                <!-- Widget Fonts -->
                <div class="accordion-section" id="widget-fonts">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('widget-fonts')">
                        <i class="fas fa-text-height"></i>
                        <span>Widget Fonts</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <?php
                $widgetPrimaryFont = $widgetFonts['widget_primary_font'] ?? $pagePrimaryFont;
                $widgetSecondaryFont = $widgetFonts['widget_secondary_font'] ?? $pageSecondaryFont;
                ?>
                
                <div class="form-group">
                    <label for="widget_primary_font">Widget Primary Font</label>
                    <select id="widget_primary_font" name="widget_primary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="" <?php echo empty($widgetPrimaryFont) || $widgetPrimaryFont === $pagePrimaryFont ? 'selected' : ''; ?>>Default (Page Primary Font)</option>
                        <?php foreach ($googleFonts as $category => $fonts): ?>
                            <optgroup label="<?php echo h($category); ?>">
                                <?php foreach ($fonts as $fontValue => $fontName): ?>
                                    <option value="<?php echo h($fontValue); ?>" <?php echo ($widgetPrimaryFont == $fontValue && !empty($widgetPrimaryFont)) ? 'selected' : ''; ?>>
                                        <?php echo h($fontName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for widget titles and headings</small>
                </div>
                
                <div class="form-group">
                    <label for="widget_secondary_font">Widget Secondary Font</label>
                    <select id="widget_secondary_font" name="widget_secondary_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="" <?php echo empty($widgetSecondaryFont) || $widgetSecondaryFont === $pageSecondaryFont ? 'selected' : ''; ?>>Default (Page Secondary Font)</option>
                        <?php foreach ($googleFonts as $category => $fonts): ?>
                            <optgroup label="<?php echo h($category); ?>">
                                <?php foreach ($fonts as $fontValue => $fontName): ?>
                                    <option value="<?php echo h($fontValue); ?>" <?php echo ($widgetSecondaryFont == $fontValue && !empty($widgetSecondaryFont)) ? 'selected' : ''; ?>>
                                        <?php echo h($fontName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for widget body text and descriptions</small>
                </div>
                    </div>
                </div>
                
                <!-- Widget Structure -->
                <div class="accordion-section" id="widget-structure">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('widget-structure')">
                        <i class="fas fa-cube"></i>
                        <span>Widget Structure</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
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
                            <input type="color" id="widget_glow_color" value="<?php echo h($widgetStyles['glow_color'] ?? '#ff00ff'); ?>" style="width: 50px; height: 50px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; flex-shrink: 0;" onchange="updateGlowColor()">
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
                    </div>
                </div>
                
                <!-- Spatial Effects -->
                <div class="accordion-section" id="spatial-effects">
                    <button type="button" class="accordion-header" onclick="toggleAccordion('spatial-effects')">
                        <i class="fas fa-layer-group"></i>
                        <span>Spatial Effects</span>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </button>
                    <div class="accordion-content">
                <div class="form-group">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        <?php 
                        $spatialEffects = [
                            'none' => ['label' => 'None', 'icon' => 'fa-square', 'desc' => 'Standard layout'],
                            'glass' => ['label' => 'Glass', 'icon' => 'fa-image', 'desc' => 'Glassmorphism effect'],
                            'depth' => ['label' => 'Depth', 'icon' => 'fa-cube', 'desc' => '3D perspective'],
                            'floating' => ['label' => 'Floating', 'icon' => 'fa-window-maximize', 'desc' => 'Floating container'],
                            'tilt' => ['label' => 'Tilt', 'icon' => 'fa-mobile-screen-button', 'desc' => 'Device tilt parallax']
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
                    </div>
                </div>
                
                <h3 style="margin-top: 0;">Save as Theme</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Save your current customization as a reusable theme.</small>
                
                <?php if ($userThemeCount >= 3): ?>
                <div style="padding: 1rem; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin-bottom: 1rem;">
                    <strong style="display: block; margin-bottom: 0.5rem; color: #856404;">Theme Limit Reached</strong>
                    <p style="margin: 0; color: #856404; font-size: 0.9rem;">You've reached the maximum of 3 custom themes. Please delete one before creating a new theme.</p>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="theme_name" placeholder="Enter theme name..." style="flex: 1; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;" maxlength="100">
                        <button type="button" class="btn btn-secondary" onclick="saveTheme()" style="padding: 0.75rem 1.5rem; background: #0066ff; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">Save Theme</button>
                    </div>
                </div>
                <?php endif; ?>
                
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
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <div class="form-group">
                    <label for="layout_option">Layout</label>
                    <select id="layout_option" name="layout_option">
                        <option value="layout1" <?php echo ($page['layout_option'] == 'layout1') ? 'selected' : ''; ?>>Layout 1</option>
                        <option value="layout2" <?php echo ($page['layout_option'] == 'layout2') ? 'selected' : ''; ?>>Layout 2</option>
                    </select>
                    <small style="display: block; margin-top: 0.5rem; color: #666;">Additional layout options coming soon.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Appearance</button>
            </form>
        </div>
        
        <!-- Widget Settings Drawer -->
        <div id="widget-settings-drawer-overlay" class="drawer-overlay" onclick="closeWidgetSettingsDrawer()" style="display: none;"></div>
        <div id="widget-settings-drawer" class="drawer" style="display: none;">
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
        
        <!-- Image Crop Modal -->
        <div id="crop-modal-overlay" class="crop-modal-overlay">
            <div class="crop-modal">
                <div class="crop-modal-header">
                    <h3>Crop Profile Image</h3>
                    <button type="button" class="crop-modal-close" onclick="closeCropModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="crop-modal-body">
                    <div id="croppie-container"></div>
                </div>
                <div class="crop-modal-actions">
                    <button type="button" class="crop-modal-cancel" onclick="closeCropModal()">Cancel</button>
                    <button type="button" class="crop-modal-apply" onclick="applyCrop()">Apply Crop</button>
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
        
        <!-- Blog Tab -->
        <div id="tab-blog" class="tab-content">
            <div class="blog-section">
                <div class="blog-header">
                    <h2><i class="fas fa-blog"></i> Blog Management</h2>
                    <p>Manage your blog posts and content</p>
                </div>
                
                <div style="max-width: 800px; margin: 0 auto; padding: 2rem;">
                    <div style="background: white; border-radius: 8px; padding: 2rem;  margin-bottom: 2rem;">
                        <h3 style="margin-top: 0; margin-bottom: 1rem; color: #111827;">Blog Features</h3>
                        <p style="color: #6b7280; margin-bottom: 1.5rem;">Use blog widgets on your page to display blog content. Available widgets:</p>
                        
                        <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #0066ff;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #111827;"><i class="fas fa-list"></i> Latest Posts</h4>
                                <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">Display your most recent blog posts</p>
                            </div>
                            
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #0066ff;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #111827;"><i class="fas fa-filter"></i> Category Filter</h4>
                                <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">Allow visitors to filter posts by category</p>
                            </div>
                            
                            <div style="padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #0066ff;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #111827;"><i class="fas fa-link"></i> Related Posts</h4>
                                <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">Show related blog posts based on current post</p>
                            </div>
                        </div>
                        
                        <div style="padding: 1.5rem; background: #e0f2fe; border-radius: 8px; border: 1px solid #0066ff;">
                            <p style="margin: 0; color: #111827; font-weight: 600; margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> How to use blog widgets:</p>
                            <ol style="margin: 0; padding-left: 1.5rem; color: #374151;">
                                <li>Go to the <strong>Widgets</strong> section</li>
                                <li>Click <strong>"Add Widget"</strong></li>
                                <li>Search for "blog" to find blog widgets</li>
                                <li>Add and configure the blog widget you want</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="/admin/blog.php" style="display: inline-block; padding: 0.75rem 2rem; background: #0066ff; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#0052cc'" onmouseout="this.style.background='#0066ff'">
                            <i class="fas fa-edit"></i> Manage Blog Posts
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Tab -->
        <div id="tab-analytics" class="tab-content">
            <h2>Widget Analytics</h2>
            <p style="margin-bottom: 20px; color: #666;">View performance metrics for your widgets.</p>
            
            <div style="margin-bottom: 1.5rem;">
                <label for="analytics-period" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Time Period</label>
                <select id="analytics-period" onchange="loadWidgetAnalytics()" style="width: 200px; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;">
                    <option value="day">Last 24 Hours</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>Last 30 Days</option>
                    <option value="all">All Time</option>
                </select>
            </div>
            
            <div id="analytics-loading" style="display: none; text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <p>Loading analytics...</p>
            </div>
            
            <div id="analytics-content" style="display: none;">
                <div id="analytics-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <!-- Summary cards will be inserted here -->
                </div>
                
                <div id="analytics-table-container">
                    <table id="widget-analytics-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Widget</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Type</th>
                                <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Clicks</th>
                                <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Views</th>
                                <th style="padding: 0.75rem; text-align: right; font-weight: 600;">CTR</th>
                            </tr>
                        </thead>
                        <tbody id="analytics-table-body">
                            <!-- Widget data will be inserted here -->
                        </tbody>
                    </table>
                    
                    <div id="analytics-empty" style="display: none; text-align: center; padding: 3rem; color: #999;">
                        <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No analytics data available for this period.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Support Tab -->
        <div id="tab-support" class="tab-content">
            <div class="support-section">
                <div class="support-header">
                    <h2><i class="fas fa-life-ring"></i> Support & Help</h2>
                    <p>Get assistance, view documentation, or contact our support team</p>
                </div>
                
                <div class="support-grid">
                    <a href="/docs" class="support-card" target="_blank">
                        <div class="support-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Documentation</h3>
                        <p>Browse guides and tutorials</p>
                    </a>
                    
                    <a href="/faq" class="support-card" target="_blank">
                        <div class="support-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3>FAQ</h3>
                        <p>Find answers to common questions</p>
                    </a>
                    
                    <a href="mailto:support@<?php echo parse_url(APP_URL, PHP_URL_HOST); ?>" class="support-card">
                        <div class="support-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Contact Support</h3>
                        <p>Email us for personalized help</p>
                    </a>
                    
                    <a href="https://github.com/phil426/podn-bio" class="support-card" target="_blank" rel="noopener noreferrer">
                        <div class="support-icon">
                            <i class="fab fa-github"></i>
                        </div>
                        <h3>GitHub</h3>
                        <p>View source code and contribute</p>
                    </a>
                </div>
            </div>
                </div>
                <?php endif; // End if ($page) ?>
            </div>
        </main>
        
        <!-- Live Preview Panel - Third Column -->
        <div id="live-preview-panel">
            <div class="preview-content">
                <div class="preview-iframe-wrapper">
                    <div class="preview-iframe-container">
                        <iframe id="preview-iframe" src="<?php echo h($pageUrl ?? ''); ?>"></iframe>
                    </div>
                </div>
                <button type="button" class="preview-refresh-btn" onclick="refreshPreview()" title="Refresh Preview">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <!-- Expand Preview Button (shown when collapsed) -->
        <button id="preview-expand-btn" onclick="togglePreview()" class="preview-expand-btn" style="display: none;" title="Expand Preview">
            <i class="fas fa-chevron-left" style="font-size: 1rem; color: #0066ff;"></i>
        </button>
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
    
    <!-- Croppie Image Cropper -->
    <script src="https://unpkg.com/croppie@2.6.5/croppie.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <!-- Draggable Library for Drag-and-Drop Sorting -->
    <script src="https://cdn.jsdelivr.net/npm/@shopify/draggable@1.0.0-beta.12/lib/draggable.bundle.js"></script>
    
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
            
            // Accordion content visibility is handled by .expanded class, no action needed here
            
            // Auto-load analytics if analytics tab is opened
            if (sectionName === 'analytics' && typeof loadWidgetAnalytics === 'function') {
                loadWidgetAnalytics();
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
        
        // Accordion Functions for Appearance Section
        window.toggleAccordion = function(sectionId) {
            const section = document.getElementById(sectionId);
            if (!section) return;
            
            const isExpanded = section.classList.contains('expanded');
            
            // Toggle the expanded state
            if (isExpanded) {
                section.classList.remove('expanded');
                localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
            } else {
                section.classList.add('expanded');
                localStorage.setItem(`accordion_${sectionId}`, 'expanded');
            }
        };
        
        // Initialize accordion states from localStorage
        function initializeAccordions() {
            const sections = ['theme-selection', 'page-background', 'page-colors', 'page-fonts', 'widget-background', 
                           'widget-border', 'widget-fonts', 'widget-structure', 'spatial-effects'];
            
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (!section) return;
                
                const savedState = localStorage.getItem(`accordion_${sectionId}`);
                
                // Default to collapsed for most sections, expanded for first one
                if (savedState === null) {
                    // First time: collapse all except first
                    if (sectionId === 'page-background') {
                        section.classList.add('expanded');
                        localStorage.setItem(`accordion_${sectionId}`, 'expanded');
                    } else {
                        section.classList.remove('expanded');
                        localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
                    }
                } else if (savedState === 'expanded') {
                    section.classList.add('expanded');
                } else {
                    section.classList.remove('expanded');
                }
            });
        }
        
        // User Menu Dropdown Functions
        window.toggleUserMenu = function() {
            const dropdown = document.getElementById('user-menu-dropdown');
            const button = document.getElementById('user-menu-button');
            const dropdownMobile = document.getElementById('user-menu-dropdown-mobile');
            const buttonMobile = document.getElementById('user-menu-button-mobile');
            
            // Handle desktop menu
            if (dropdown && button) {
                const isOpen = dropdown.classList.contains('show');
                if (isOpen) {
                    dropdown.classList.remove('show');
                    button.classList.remove('active');
                } else {
                    dropdown.classList.add('show');
                    button.classList.add('active');
                }
            }
            
            // Handle mobile menu
            if (dropdownMobile && buttonMobile) {
                const isOpen = dropdownMobile.classList.contains('show');
                if (isOpen) {
                    dropdownMobile.classList.remove('show');
                    buttonMobile.classList.remove('active');
                } else {
                    dropdownMobile.classList.add('show');
                    buttonMobile.classList.add('active');
                }
            }
        };
        
        window.closeUserMenu = function() {
            const dropdown = document.getElementById('user-menu-dropdown');
            const button = document.getElementById('user-menu-button');
            const dropdownMobile = document.getElementById('user-menu-dropdown-mobile');
            const buttonMobile = document.getElementById('user-menu-button-mobile');
            
            if (dropdown) dropdown.classList.remove('show');
            if (button) button.classList.remove('active');
            if (dropdownMobile) dropdownMobile.classList.remove('show');
            if (buttonMobile) buttonMobile.classList.remove('active');
        };
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenus = document.querySelectorAll('.user-menu');
            const dropdown = document.getElementById('user-menu-dropdown');
            const dropdownMobile = document.getElementById('user-menu-dropdown-mobile');
            
            let shouldClose = true;
            userMenus.forEach(userMenu => {
                if (userMenu.contains(event.target)) {
                    shouldClose = false;
                }
            });
            
            if (shouldClose) {
                if (dropdown) dropdown.classList.remove('show');
                if (dropdownMobile) dropdownMobile.classList.remove('show');
                document.querySelectorAll('.user-menu-button').forEach(btn => btn.classList.remove('active'));
            }
            
            // Close widget accordions when clicking outside (using accordion-section pattern)
            const widgetSections = document.querySelectorAll('#widgets-list .accordion-section');
            widgetSections.forEach(section => {
                if (section.classList.contains('expanded') && !section.contains(event.target)) {
                    const sectionId = section.id;
                    if (sectionId) {
                        section.classList.remove('expanded');
                        localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
                    }
                }
            });
            
            // Close social icon accordions when clicking outside
            const socialSections = document.querySelectorAll('#directories-list .accordion-section');
            socialSections.forEach(section => {
                if (section.classList.contains('expanded') && !section.contains(event.target)) {
                    const sectionId = section.id;
                    if (sectionId) {
                        section.classList.remove('expanded');
                        localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
                    }
                }
            });
        });
        
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('open');
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (sidebar && toggle && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        });
        
        // Toggle Featured Widget from header button
        window.toggleFeaturedWidget = function(widgetId, currentlyFeatured) {
            const newFeaturedState = !currentlyFeatured;
            
            // Update star icon immediately for visual feedback
            const starIcon = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle .fa-star`);
            const featuredToggle = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle`);
            
            if (starIcon && featuredToggle) {
                if (newFeaturedState) {
                    starIcon.classList.add('featured-active');
                    starIcon.style.color = '#ffd700';
                    featuredToggle.title = 'Featured Widget - Select effect below';
                } else {
                    starIcon.classList.remove('featured-active');
                    starIcon.style.color = '#9ca3af';
                    featuredToggle.title = 'Make Featured Widget';
                }
            }
            
            // If enabling featured, unfeature other widgets
            if (newFeaturedState) {
                unfeatureOtherWidgets(widgetId);
            }
            
            // Save the change (don't expand dropdown)
            saveFeaturedWidgetState(widgetId, newFeaturedState);
        };
        
        // Save featured widget state
        function saveFeaturedWidgetState(widgetId, isFeatured) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('widget_id', widgetId);
            formData.append('is_featured', isFeatured ? '1' : '0');
            formData.append('csrf_token', csrfToken);
            
            // Get current featured effect if unfeaturing, clear it
            if (!isFeatured) {
                formData.append('featured_effect', '');
            } else {
                // If featuring, keep existing effect or default to 'jiggle'
                const effectSelect = document.getElementById(`widget-inline-featured_effect-${widgetId}`);
                if (effectSelect) {
                    formData.append('featured_effect', effectSelect.value || 'jiggle');
                } else {
                    // Effect selector not loaded yet, default to 'jiggle'
                    formData.append('featured_effect', 'jiggle');
                }
            }
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update effect group visibility in dropdown if open
                    const effectGroup = document.getElementById(`featured-effect-group-${widgetId}`);
                    if (effectGroup) {
                        effectGroup.style.display = isFeatured ? 'block' : 'none';
                    }
                    
                    showToast(isFeatured ? 'Widget marked as featured!' : 'Widget unfeatured', 'success');
                    
                    // Update Featured Effect selector
                    if (typeof updateFeaturedEffectSelector === 'function') {
                        updateFeaturedEffectSelector();
                    }
                } else {
                    showToast('Failed to update featured status: ' + (data.error || 'Unknown error'), 'error');
                    // Revert star icon
                    const starIcon = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle .fa-star`);
                    const featuredToggle = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle`);
                    if (starIcon && featuredToggle) {
                        starIcon.classList.toggle('featured-active');
                        starIcon.style.color = isFeatured ? '#9ca3af' : '#ffd700';
                        featuredToggle.title = isFeatured ? 'Make Featured Widget' : 'Featured Widget';
                    }
                }
            })
            .catch(error => {
                console.error('Error updating featured status:', error);
                showToast('Error updating featured status', 'error');
            });
        }
        
        // Apply Featured Widget Effect from Manage Widgets dropdown
        window.applyFeaturedEffect = function(effect) {
            // Find the currently featured widget
            const featuredStar = document.querySelector('.widget-featured-toggle .fa-star.featured-active');
            if (!featuredStar) {
                showToast('No widget is currently featured', 'error');
                const selector = document.getElementById('featured-effect-selector');
                if (selector) selector.value = '';
                return;
            }
            
            const widgetItem = featuredStar.closest('.accordion-section');
            if (!widgetItem) {
                showToast('Could not find featured widget', 'error');
                return;
            }
            
            const widgetId = parseInt(widgetItem.getAttribute('data-widget-id'));
            if (!widgetId) {
                showToast('Invalid widget ID', 'error');
                return;
            }
            
            // Save the effect (default to 'jiggle' if empty)
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('widget_id', widgetId);
            formData.append('is_featured', '1');
            formData.append('featured_effect', effect || 'jiggle');
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(effect ? 'Featured effect applied!' : 'Featured effect removed', 'success');
                    // Update dropdown in widget settings if open (default to 'jiggle' if empty)
                    const effectSelect = document.getElementById(`widget-inline-featured_effect-${widgetId}`);
                    if (effectSelect) {
                        effectSelect.value = effect || 'jiggle';
                    }
                } else {
                    showToast('Failed to apply effect: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error applying featured effect:', error);
                showToast('Error applying featured effect', 'error');
            });
        };
        
        // Update Featured Effect Selector dropdown
        function updateFeaturedEffectSelector() {
            const selector = document.getElementById('featured-effect-selector');
            if (!selector) return;
            
            // Find currently featured widget
            const featuredStar = document.querySelector('.widget-featured-toggle .fa-star.featured-active');
            if (!featuredStar) {
                selector.disabled = true;
                selector.value = '';
                return;
            }
            
            selector.disabled = false;
            
            // Get current effect from the featured widget
            const widgetItem = featuredStar.closest('.accordion-section');
            if (widgetItem) {
                const widgetId = widgetItem.getAttribute('data-widget-id');
                // Fetch current widget to get effect
                fetch('/api/widgets.php?action=get&widget_id=' + widgetId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.widget) {
                            const effect = data.widget.featured_effect || '';
                            selector.value = effect;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching featured widget effect:', error);
                    });
            }
        }
        
        // Handle Featured Widget Toggle (for dropdown checkbox)
        window.handleFeaturedToggle = function(widgetId, isFeatured) {
            const effectGroup = document.getElementById('featured-effect-group-' + widgetId);
            if (effectGroup) {
                effectGroup.style.display = isFeatured ? 'block' : 'none';
            }
            
            // Update header star icon
            const starIcon = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle .fa-star`);
            const featuredToggle = document.querySelector(`#widget-accordion-${widgetId} .widget-featured-toggle`);
            if (starIcon && featuredToggle) {
                if (isFeatured) {
                    starIcon.classList.add('featured-active');
                    starIcon.style.color = '#ffd700';
                } else {
                    starIcon.classList.remove('featured-active');
                    starIcon.style.color = '#9ca3af';
                }
            }
            
            // If enabling featured, unfeature other widgets
            if (isFeatured) {
                unfeatureOtherWidgets(widgetId);
            }
            
            // Update Featured Effect selector
            updateFeaturedEffectSelector();
        };
        
        // Unfeature all other widgets (only one featured at a time)
        function unfeatureOtherWidgets(exceptWidgetId) {
            // Update header star icons for all widgets except the one being featured
            document.querySelectorAll('.widget-featured-toggle').forEach(toggle => {
                const widgetItem = toggle.closest('.accordion-section');
                if (widgetItem) {
                    const currentWidgetId = widgetItem.getAttribute('data-widget-id');
                    if (currentWidgetId && parseInt(currentWidgetId) !== parseInt(exceptWidgetId)) {
                        const starIcon = toggle.querySelector('.fa-star');
                        if (starIcon) {
                            starIcon.classList.remove('featured-active');
                            starIcon.style.color = '#9ca3af';
                            toggle.title = 'Make Featured Widget';
                        }
                        
                        // Also unfeature in database
                        saveFeaturedWidgetState(parseInt(currentWidgetId), false);
                    }
                }
            });
            
            // Update checkboxes in dropdowns if they exist
            const allFeaturedCheckboxes = document.querySelectorAll('input[name="is_featured"]');
            allFeaturedCheckboxes.forEach(checkbox => {
                const widgetItem = checkbox.closest('.accordion-section');
                if (widgetItem) {
                    const currentWidgetId = widgetItem.getAttribute('data-widget-id');
                    if (currentWidgetId && parseInt(currentWidgetId) !== parseInt(exceptWidgetId)) {
                        checkbox.checked = false;
                    }
                }
            });
        }
        
        // Live Preview System
        let previewUpdateTimeout = null;
        
        // Copy page URL to clipboard
        window.copyPageUrl = function() {
            const pageUrl = '<?php echo isset($pageUrl) ? h($pageUrl) : ''; ?>';
            if (!pageUrl) return;
            
            // Use Clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(pageUrl).then(() => {
                    showCopySuccess();
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    fallbackCopy(pageUrl);
                });
            } else {
                // Fallback for older browsers
                fallbackCopy(pageUrl);
            }
        };
        
        function showCopySuccess() {
            const btn = document.getElementById('copy-url-btn');
            const icon = document.getElementById('copy-url-icon');
            if (btn && icon) {
                const originalIcon = icon.className;
                icon.className = 'fas fa-check';
                btn.style.color = '#10b981';
                setTimeout(() => {
                    icon.className = originalIcon;
                    btn.style.color = '#0066ff';
                }, 2000);
            }
            showToast('Page URL copied to clipboard!', 'success');
        }
        
        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                console.error('Fallback copy failed:', err);
                showToast('Failed to copy URL. Please copy manually.', 'error');
            }
            document.body.removeChild(textarea);
        }
        
        // Toggle preview panel (collapse/expand)
        window.togglePreview = function() {
            const panel = document.getElementById('live-preview-panel');
            const expandBtn = document.getElementById('preview-expand-btn');
            
            if (!panel) return;
            
            // Toggle collapsed class
            panel.classList.toggle('collapsed');
            
            // Show/hide expand button
            if (expandBtn) {
                if (panel.classList.contains('collapsed')) {
                    expandBtn.style.display = 'flex';
                } else {
                    expandBtn.style.display = 'none';
                }
            }
            
            // Load preview if not loaded when expanding
            if (!panel.classList.contains('collapsed')) {
                const iframe = document.getElementById('preview-iframe');
                if (iframe) {
                    const pageUrl = '<?php echo isset($pageUrl) ? h($pageUrl) : ''; ?>';
                    if (pageUrl && (iframe.src === 'about:blank' || !iframe.src || iframe.src.indexOf('<?php echo h(APP_URL); ?>') === -1)) {
                        iframe.src = pageUrl;
                    }
                }
            }
        };
        
        // Auto-refresh preview when settings change
        function refreshPreview() {
            if (previewUpdateTimeout) {
                clearTimeout(previewUpdateTimeout);
            }
            
            previewUpdateTimeout = setTimeout(() => {
                const panel = document.getElementById('live-preview-panel');
                const iframe = document.getElementById('preview-iframe');
                
                // Only refresh if panel is visible (not collapsed) and iframe exists
                if (panel && !panel.classList.contains('collapsed') && iframe) {
                    // Reload iframe with timestamp to bypass cache
                    const currentSrc = iframe.src.split('?')[0];
                    iframe.src = currentSrc + '?preview=' + Date.now();
                }
            }, 1500); // Debounce: wait 1.5 seconds after last change
        }
        
        // Widget Visibility Toggle
        function toggleWidgetVisibility(widgetId, isVisible) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('widget_id', widgetId);
            formData.append('is_active', isVisible ? 1 : 0);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/widgets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok before parsing JSON
                if (!response.ok) {
                    return response.text().then(text => {
                        // Try to extract error message from HTML or use status
                        const errorMsg = text.includes('<title>') ? `HTTP ${response.status}` : text.substring(0, 200);
                        throw new Error(`HTTP ${response.status}: ${errorMsg}`);
                    });
                }
                // Check content type before parsing
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        throw new Error('Response is not JSON. Received: ' + text.substring(0, 200));
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    const widgetItem = document.querySelector(`.accordion-section[data-widget-id="${widgetId}"]`);
                    if (widgetItem) {
                        if (isVisible) {
                            widgetItem.classList.remove('inactive');
                        } else {
                            widgetItem.classList.add('inactive');
                        }
                    }
                    showToast(isVisible ? 'Widget is now visible' : 'Widget is now hidden', 'success');
                } else {
                    showToast('Failed to update widget visibility: ' + (data.error || 'Unknown error'), 'error');
                    // Revert checkbox
                    const checkbox = document.getElementById(`widget-visibility-${widgetId}`);
                    if (checkbox) {
                        checkbox.checked = !isVisible;
                    }
                }
            })
            .catch(error => {
                console.error('Error toggling widget visibility:', error);
                const errorMsg = error.message || 'Unknown error';
                showToast('Error updating widget visibility: ' + errorMsg, 'error');
                // Revert checkbox
                const checkbox = document.getElementById(`widget-visibility-${widgetId}`);
                if (checkbox) {
                    checkbox.checked = !isVisible;
                }
            });
        }
        
        // Listen for appearance changes to refresh preview
        document.addEventListener('DOMContentLoaded', function() {
            const appearanceForm = document.getElementById('appearance-form');
            if (appearanceForm) {
                appearanceForm.addEventListener('input', refreshPreview);
                appearanceForm.addEventListener('change', refreshPreview);
            }
            
            // Listen for widget changes
            document.addEventListener('click', function(e) {
                if (e.target.matches('.btn-primary') && e.target.closest('.accordion-content')) {
                    refreshPreview();
                }
            });
        });
        
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
            
            // Initialize accordion states
            initializeAccordions();
            
            // Preload widget definitions for accordion settings
            loadWidgetDefinitions();
            
            // Initialize widget accordion states
            initializeWidgetAccordions();
            
            // Initialize drag-and-drop for widgets and social icons
            if (typeof EditorDragDrop !== 'undefined') {
                EditorDragDrop.init();
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
        });
        
        function initializeWidgetAccordions() {
            // Initialize widget accordions using the same pattern as Appearance tab
            const widgetSections = document.querySelectorAll('#widgets-list .accordion-section');
            
            widgetSections.forEach(section => {
                const sectionId = section.id;
                if (!sectionId || !sectionId.startsWith('widget-')) return;
                
                const widgetId = section.getAttribute('data-widget-id');
                if (!widgetId) return;
                
                // Pre-load widget settings
                loadWidgetSettingsInline(parseInt(widgetId));
                
                // Restore accordion state from localStorage
                const savedState = localStorage.getItem(`accordion_${sectionId}`);
                if (savedState === 'expanded') {
                    section.classList.add('expanded');
                } else {
                    section.classList.remove('expanded');
                    localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
                }
            });
            
            // Initialize social icon accordions
            const socialSections = document.querySelectorAll('#directories-list .accordion-section');
            socialSections.forEach(section => {
                const sectionId = section.id;
                if (!sectionId || !sectionId.startsWith('social-icon-')) return;
                
                // Restore accordion state from localStorage
                const savedState = localStorage.getItem(`accordion_${sectionId}`);
                if (savedState === 'expanded') {
                    section.classList.add('expanded');
                } else {
                    section.classList.remove('expanded');
                    localStorage.setItem(`accordion_${sectionId}`, 'collapsed');
                }
            });
        }
        
        // Widget Gallery Functions
        let allWidgets = [];
        let filteredWidgets = [];
        let currentCategory = 'all';
        let currentSearch = '';
        
        // Load widget definitions early
        function loadWidgetDefinitions() {
            if (allWidgets.length > 0) return; // Already loaded
            
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
                    allWidgets = Array.isArray(widgets) ? widgets : Object.values(widgets);
                }
            })
            .catch(error => {
                console.error('Error loading widget definitions:', error);
            });
        }
        
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
            
            // Handle featured widget fields
            const featuredCheckbox = document.getElementById('widget_config_is_featured');
            if (featuredCheckbox) {
                requestData.append('is_featured', featuredCheckbox.checked ? '1' : '0');
                
                const featuredEffectSelect = document.getElementById('widget_config_featured_effect');
                if (featuredEffectSelect) {
                    requestData.append('featured_effect', featuredEffectSelect.value || '');
                }
            }
            
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
            // Try to find widget item - check for accordion first, then legacy
            let widgetItem = null;
            if (buttonElement) {
                widgetItem = buttonElement.closest('.accordion-section') || buttonElement.closest('.widget-item');
            } else {
                widgetItem = document.querySelector(`.accordion-section[data-widget-id="${widgetId}"]`) || 
                           document.querySelector(`.widget-item[data-widget-id="${widgetId}"]`);
            }
            
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
                            
                            // Special handling for thumbnail_image on custom_link widgets - show upload interface
                            if (fieldName === 'thumbnail_image' && widget.widget_type === 'custom_link') {
                                const thumbnailValue = configData['thumbnail_image'] || '';
                                const thumbnailId = `widget-thumbnail-${widgetId}`;
                                fieldsContainer.innerHTML += `
                                    <div class="form-group">
                                        <label>Thumbnail Image</label>
                                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                                            <div style="flex-shrink: 0;">
                                                ${thumbnailValue ? `
                                                    <img id="${thumbnailId}-preview" src="${thumbnailValue.replace(/"/g, '&quot;')}" alt="Thumbnail" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                                                ` : `
                                                    <div id="${thumbnailId}-preview" style="width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No image</div>
                                                `}
                                            </div>
                                            <div style="flex: 1;">
                                                <input type="file" id="${thumbnailId}-input" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-bottom: 10px; width: 100%;">
                                                <input type="hidden" id="widget_config_thumbnail_image" name="thumbnail_image" value="${thumbnailValue.replace(/"/g, '&quot;')}">
                                                <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                                    <button type="button" class="btn btn-primary btn-small" onclick="uploadWidgetThumbnail(${widgetId})">Upload & Crop</button>
                                                    ${thumbnailValue ? `<button type="button" class="btn btn-danger btn-small" onclick="removeWidgetThumbnail(${widgetId})">Remove</button>` : ''}
                                                </div>
                                                <small style="display: block; color: #666;">Select an image to upload and crop. Recommended: 400x400px, square image. Max 5MB</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
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
                                // Handle dynamic category options for blog widgets
                                if (fieldName === 'category_id' && (widget.widget_type === 'blog_latest_posts' || widget.widget_type === 'blog_category_filter')) {
                                    // Load categories via AJAX
                                    fieldsContainer.innerHTML += `
                                        <div class="form-group">
                                            <label for="widget_config_${fieldName}">${fieldDef.label}${required}</label>
                                            <select id="widget_config_${fieldName}" name="${fieldName}" ${fieldDef.required ? 'required' : ''}>
                                                <option value="">Loading categories...</option>
                                            </select>
                                            ${helpText}
                                        </div>
                                    `;
                                    
                                    // Load categories
                                    fetch('/api/blog_categories.php')
                                        .then(response => response.json())
                                        .then(data => {
                                            const select = document.getElementById(`widget_config_${fieldName}`);
                                            if (select && data.success && data.categories) {
                                                let optionsHtml = '<option value="">All Categories</option>';
                                                data.categories.forEach(cat => {
                                                    const selected = value == cat.id ? ' selected' : '';
                                                    const catName = String(cat.name).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                                    optionsHtml += `<option value="${cat.id}"${selected}>${catName}</option>`;
                                                });
                                                select.innerHTML = optionsHtml;
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error loading categories:', error);
                                            const select = document.getElementById(`widget_config_${fieldName}`);
                                            if (select) {
                                                select.innerHTML = '<option value="">Error loading categories</option>';
                                            }
                                        });
                                } else {
                                    // Regular select with static options
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
                                }
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
                    
                    // Add Featured Widget section (after all config fields)
                    if (widgetDef && widget.widget_id) {
                        const isFeatured = widget.is_featured || 0;
                        const featuredEffect = widget.featured_effect || '';
                        fieldsContainer.innerHTML += `
                            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e5e7eb;">
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.75rem; font-weight: 600;">
                                    <input type="checkbox" id="widget_config_is_featured" name="is_featured" value="1" ${isFeatured ? 'checked' : ''} onchange="handleFeaturedToggle(${widgetId}, this.checked)">
                                    <span>Featured Widget</span>
                                </label>
                                <small style="color: #666; display: block; margin-top: 0.25rem;">Highlight this widget with special effects to draw attention</small>
                            </div>
                            <div class="form-group" id="featured-effect-group" style="display: ${isFeatured ? 'block' : 'none'};">
                                <label for="widget_config_featured_effect">Featured Effect</label>
                                <select id="widget_config_featured_effect" name="featured_effect">
                                    <option value="">Select an effect...</option>
                                    <option value="jiggle" ${featuredEffect === 'jiggle' ? 'selected' : ''}>Jiggle 🎯</option>
                                    <option value="burn" ${featuredEffect === 'burn' ? 'selected' : ''}>Burn 🔥</option>
                                    <option value="rotating-glow" ${featuredEffect === 'rotating-glow' ? 'selected' : ''}>Rotating Glow 💫</option>
                                    <option value="blink" ${featuredEffect === 'blink' ? 'selected' : ''}>Blink 👁️</option>
                                    <option value="pulse" ${featuredEffect === 'pulse' ? 'selected' : ''}>Pulse 💓</option>
                                    <option value="shake" ${featuredEffect === 'shake' ? 'selected' : ''}>Shake 📳</option>
                                    <option value="sparkles" ${featuredEffect === 'sparkles' ? 'selected' : ''}>Sparkles ✨</option>
                                </select>
                                <small style="color: #666; display: block; margin-top: 0.25rem;">Choose a special effect for this featured widget</small>
                            </div>
                        `;
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
                    // Remove widget accordion from DOM
                    const widgetAccordion = document.getElementById(`widget-${widgetId}`);
                    if (widgetAccordion) {
                        widgetAccordion.remove();
                    }
                    // Check if widgets list is empty and show message
                    const widgetsList = document.getElementById('widgets-list');
                    if (widgetsList && widgetsList.querySelectorAll('.accordion-section').length === 0) {
                        widgetsList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #666;">No widgets yet. Click "Add Widget" to browse the widget gallery and add content to your page.</div>';
                    }
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred', 'error');
            });
        }
        
        // Widget accordions now use the same toggleAccordion() function as Appearance tab
        
        window.loadWidgetSettingsInline = function(widgetId) {
            const contentDiv = document.getElementById(`widget-content-${widgetId}`);
            console.log('loadWidgetSettingsInline called for widgetId:', widgetId, 'contentDiv:', contentDiv);
            if (!contentDiv) {
                console.error('contentDiv not found for widgetId:', widgetId);
                return;
            }
            console.log('contentDiv found, current innerHTML length:', contentDiv.innerHTML.length);
            
            // Ensure widget definitions are loaded first
            function ensureWidgetDefinitionsLoaded() {
                return new Promise((resolve, reject) => {
                    // If already loaded, resolve immediately
                    if (allWidgets.length > 0) {
                        resolve();
                        return;
                    }
                    
                    // Load widget definitions
                    const loadFormData = new FormData();
                    loadFormData.append('action', 'get_available');
                    loadFormData.append('csrf_token', csrfToken);
                    
                    fetch('/api/widgets.php', {
                        method: 'POST',
                        body: loadFormData
                    })
                    .then(response => {
                        return response.text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (!response.ok) {
                                    // If we got JSON, use its error message
                                    throw new Error(data.error || 'HTTP ' + response.status);
                                }
                                return data;
                            } catch (e) {
                                if (e.message && !e.message.includes('JSON')) {
                                    throw e; // Re-throw if it's our error with message
                                }
                                console.error('Invalid JSON response:', text);
                                if (!response.ok) {
                                    throw new Error('HTTP ' + response.status + ': ' + (text.substring(0, 100) || 'Unknown error'));
                                }
                                throw new Error('Invalid JSON response');
                            }
                        });
                    })
                    .then(widgetData => {
                        console.log('Widget definitions API response:', widgetData);
                        if (widgetData.success && (widgetData.available_widgets || widgetData.widgets)) {
                            const widgets = widgetData.available_widgets || widgetData.widgets;
                            console.log('Raw widgets data:', widgets, 'Type:', typeof widgets, 'Is array:', Array.isArray(widgets));
                            // Handle both array and object formats
                            if (Array.isArray(widgets)) {
                                allWidgets = widgets;
                            } else if (typeof widgets === 'object' && widgets !== null) {
                                // Convert object to array
                                allWidgets = Object.values(widgets);
                            } else {
                                console.error('Invalid widget format:', widgets);
                                reject(new Error('Invalid widget format'));
                                return;
                            }
                            console.log('Widget definitions loaded:', allWidgets.length, 'widgets:', allWidgets.map(w => w.widget_id || w.name));
                            resolve();
                        } else {
                            console.error('Failed to load widgets:', widgetData);
                            reject(new Error('Failed to load widget definitions: ' + (widgetData.error || 'Unknown error')));
                        }
                    })
                    .catch(error => {
                        console.error('Error loading widget definitions:', error);
                        let errorMessage = error.message || 'Unknown error';
                        
                        // Handle CSRF token errors specially
                        if (errorMessage.includes('CSRF') || errorMessage.includes('403')) {
                            errorMessage = 'Session expired. Please refresh the page and try again.';
                        }
                        
                        reject(new Error(errorMessage));
                    });
                });
            }
            
            // Main loading function
            function loadSettings() {
                // First ensure widget definitions are loaded
                ensureWidgetDefinitionsLoaded()
                    .then(() => {
                        // Now fetch the widget data
                        const formData = new FormData();
                        formData.append('action', 'get');
                        formData.append('widget_id', widgetId);
                        formData.append('csrf_token', csrfToken);
                        
                        return fetch('/api/widgets.php', {
                            method: 'POST',
                            body: formData
                        });
                    })
                    .then(response => {
                        return response.text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (!response.ok) {
                                    // If we got JSON, use its error message
                                    throw new Error(data.error || 'HTTP ' + response.status);
                                }
                                return data;
                            } catch (e) {
                                if (e.message && !e.message.includes('JSON')) {
                                    throw e; // Re-throw if it's our error with message
                                }
                                console.error('Invalid JSON response:', text);
                                if (!response.ok) {
                                    throw new Error('HTTP ' + response.status + ': ' + (text.substring(0, 100) || 'Unknown error'));
                                }
                                throw new Error('Invalid JSON response');
                            }
                        });
                    })
                    .then(data => {
                        console.log('Widget data API response:', data);
                        if (data.success && data.widget) {
                            const widget = data.widget;
                            console.log('Widget loaded:', widget);
                            const configData = typeof widget.config_data === 'string' 
                                ? JSON.parse(widget.config_data) 
                                : (widget.config_data || {});
                            
                            console.log('Looking for widget type:', widget.widget_type);
                            console.log('Available widgets:', allWidgets.map(w => ({ id: w.widget_id, name: w.name })));
                            
                            // Find widget definition
                            const widgetDef = allWidgets.find(w => w.widget_id === widget.widget_type);
                            
                            if (!widgetDef) {
                                console.error('Widget definition not found for type:', widget.widget_type);
                                console.log('Available widget types:', allWidgets.map(w => w.widget_id));
                                contentDiv.innerHTML = '<div class="widget-content-inner"><p style="color: #dc3545;">Error: Widget type "' + widget.widget_type + '" not found</p></div>';
                                return;
                            }
                            
                            console.log('Widget definition found:', widgetDef);
                            console.log('About to call renderWidgetSettings, contentDiv exists:', !!contentDiv, 'contentDiv:', contentDiv);
                            if (!contentDiv) {
                                console.error('contentDiv is null before renderWidgetSettings call!');
                                return;
                            }
                            console.log('Calling renderWidgetSettings now...');
                            console.log('renderWidgetSettingsInline function exists:', typeof renderWidgetSettingsInline === 'function');
                            try {
                                renderWidgetSettingsInline(widget, configData, widgetDef, widgetId, contentDiv);
                                console.log('renderWidgetSettingsInline call completed without error');
                            } catch (error) {
                                console.error('Error calling renderWidgetSettingsInline:', error);
                                console.error('Error stack:', error.stack);
                                contentDiv.innerHTML = '<div class="widget-content-inner"><p style="color: #dc3545;">Error: ' + error.message + '</p></div>';
                            }
                        } else {
                            console.error('Failed to load widget:', data);
                            let errorMessage = data.error || 'Unknown error';
                            if (errorMessage.includes('CSRF') || errorMessage.includes('403')) {
                                errorMessage = 'Session expired. Please refresh the page and try again.';
                                setTimeout(() => {
                                    if (confirm('Your session may have expired. Would you like to refresh the page?')) {
                                        window.location.reload();
                                    }
                                }, 1000);
                            }
                            contentDiv.innerHTML = '<div class="widget-content-inner"><p style="color: #dc3545; padding: 1rem; background: #fee; border-radius: 8px;">Error loading widget: ' + errorMessage + '</p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading widget settings:', error);
                        let errorMessage = error.message || 'Unknown error';
                        
                        // Handle CSRF token errors specially
                        if (errorMessage.includes('CSRF') || errorMessage.includes('403')) {
                            errorMessage = 'Session expired. Please refresh the page and try again.';
                            // Suggest page refresh after a delay
                            setTimeout(() => {
                                if (confirm('Your session may have expired. Would you like to refresh the page?')) {
                                    window.location.reload();
                                }
                            }, 1000);
                        }
                        
                        contentDiv.innerHTML = '<div class="widget-content-inner"><p style="color: #dc3545; padding: 1rem; background: #fee; border-radius: 8px;">Error loading widget settings: ' + errorMessage + '</p></div>';
                    });
            }
            
            // Load settings
            loadSettings();
        };
        
        window.renderWidgetSettingsInline = function(widget, configData, widgetDef, widgetId, contentDiv) {
            console.log('=== renderWidgetSettingsInline START ===');
            console.log('renderWidgetSettingsInline called with:', { 
                widgetId, 
                widgetType: widget.widget_type,
                widgetTitle: widget.title,
                hasConfigFields: !!widgetDef.config_fields,
                contentDivExists: !!contentDiv,
                widgetData: widget
            });
            
            // Store full widget data for featured widget fields
            const widgetData = widget;
            
            if (!contentDiv) {
                console.error('contentDiv is null or undefined!');
                return;
            }
            
            try {
                // Generate form HTML - wrap in widget-content-inner to maintain structure
                let formHTML = '<div class="widget-content-inner"><form class="widget-settings-form" data-widget-id="' + widgetId + '">';
                
                // Add title field
                formHTML += `
                <div class="form-group">
                    <label for="widget-inline-title-${widgetId}">Title <span style="color: #dc3545;">*</span></label>
                    <input type="text" id="widget-inline-title-${widgetId}" 
                           name="title" 
                           value="${(widget.title || '').replace(/"/g, '&quot;')}" 
                           required 
                           onchange="saveWidgetSettingsInline(${widgetId})"
                           class="widget-setting-input">
                </div>
            `;
            
            // Add widget-specific fields
            if (widgetDef.config_fields) {
                Object.entries(widgetDef.config_fields).forEach(([fieldName, fieldDef]) => {
                    // Skip disclosure_text field (removed)
                    if (fieldName === 'disclosure_text') {
                        return;
                    }
                    
                    // Declare fieldHTML at the start of each iteration
                    let fieldHTML = '';
                    
                    // Special handling for thumbnail_image - show upload interface
                    // Check both widget_id and widget_type for compatibility
                    const isCustomLink = (widgetDef.widget_id === 'custom_link') || (widget.widget_type === 'custom_link');
                    if (fieldName === 'thumbnail_image' && isCustomLink) {
                        const thumbnailValue = configData[fieldName] || '';
                        const thumbnailId = `widget-thumbnail-${widgetId}`;
                        const thumbnailInputId = `${thumbnailId}-input`;
                        const thumbnailPreviewId = `${thumbnailId}-preview`;
                        
                        fieldHTML = `
                            <div class="form-group">
                                <label for="${thumbnailInputId}">${fieldDef.label}${fieldDef.required ? ' <span style="color: #dc3545;">*</span>' : ''}</label>
                                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                    <div style="width: 120px; height: 120px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f9fafb; flex-shrink: 0; overflow: hidden;">
                                        <div id="${thumbnailPreviewId}" style="width: 100%; height: 100%;">
                                            ${thumbnailValue ? `
                                                <img src="${thumbnailValue.replace(/"/g, '&quot;')}" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                                            ` : `
                                                <div style="text-align: center; color: #9ca3af; padding: 1rem;">
                                                    <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                                    <small style="font-size: 0.75rem;">No image</small>
                                                </div>
                                            `}
                                        </div>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="file" id="${thumbnailInputId}" accept="image/jpeg,image/png,image/gif,image/webp" onchange="uploadWidgetThumbnail(${widgetId})" style="margin-bottom: 10px; width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px;">
                                        <input type="hidden" class="widget-setting-input" id="widget-inline-thumbnail_image-${widgetId}" name="thumbnail_image" value="${thumbnailValue.replace(/"/g, '&quot;')}">
                                        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                            ${thumbnailValue ? `<button type="button" class="btn btn-danger btn-small" onclick="removeWidgetThumbnail(${widgetId})" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; font-weight: 600;">Remove</button>` : ''}
                                        </div>
                                        <small style="display: block; color: #666;">Select an image to automatically upload and crop. Recommended: 400x400px, square image. Max 5MB</small>
                                    </div>
                                </div>
                            </div>
                        `;
                        formHTML += fieldHTML;
                        return; // Skip default rendering
                    }
                    
                    const value = configData[fieldName] || '';
                    const safeValue = String(value).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    const required = fieldDef.required ? ' <span style="color: #dc3545;">*</span>' : '';
                    const helpText = fieldDef.help ? `<small>${fieldDef.help}</small>` : '';
                    
                    const fieldId = `widget-inline-${fieldName}-${widgetId}`;
                    
                    if (fieldDef.type === 'textarea') {
                        fieldHTML = `
                            <div class="form-group">
                                <label for="${fieldId}">${fieldDef.label}${required}</label>
                                <textarea id="${fieldId}" 
                                          name="${fieldName}" 
                                          ${fieldDef.required ? 'required' : ''}
                                          rows="${fieldDef.rows || 4}"
                                          onchange="saveWidgetSettingsInline(${widgetId})"
                                          class="widget-setting-input">${safeValue}</textarea>
                                ${helpText}
                            </div>
                        `;
                    } else if (fieldDef.type === 'checkbox') {
                        const checked = value ? 'checked' : '';
                        fieldHTML = `
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" 
                                           id="${fieldId}" 
                                           name="${fieldName}" 
                                           ${checked}
                                           onchange="saveWidgetSettingsInline(${widgetId})"
                                           class="widget-setting-input">
                                    <span>${fieldDef.label}${required}</span>
                                </label>
                                ${helpText}
                            </div>
                        `;
                    } else if (fieldDef.type === 'select' && fieldName === 'icon' && fieldDef.options === 'fontawesome_icons') {
                        // Icon dropdown selector
                        const commonIcons = [
                            { value: '', label: 'No Icon' },
                            { value: 'fas fa-link', label: 'Link', icon: 'fa-link' },
                            { value: 'fas fa-home', label: 'Home', icon: 'fa-home' },
                            { value: 'fas fa-envelope', label: 'Email', icon: 'fa-envelope' },
                            { value: 'fas fa-phone', label: 'Phone', icon: 'fa-phone' },
                            { value: 'fas fa-map-marker-alt', label: 'Location', icon: 'fa-map-marker-alt' },
                            { value: 'fas fa-calendar', label: 'Calendar', icon: 'fa-calendar' },
                            { value: 'fas fa-heart', label: 'Heart', icon: 'fa-heart' },
                            { value: 'fas fa-star', label: 'Star', icon: 'fa-star' },
                            { value: 'fas fa-bookmark', label: 'Bookmark', icon: 'fa-bookmark' },
                            { value: 'fas fa-share-alt', label: 'Share', icon: 'fa-share-alt' },
                            { value: 'fas fa-download', label: 'Download', icon: 'fa-download' },
                            { value: 'fas fa-external-link-alt', label: 'External Link', icon: 'fa-external-link-alt' },
                            { value: 'fas fa-arrow-right', label: 'Arrow Right', icon: 'fa-arrow-right' },
                            { value: 'fas fa-play', label: 'Play', icon: 'fa-play' },
                            { value: 'fas fa-music', label: 'Music', icon: 'fa-music' },
                            { value: 'fas fa-image', label: 'Image', icon: 'fa-image' },
                            { value: 'fas fa-video', label: 'Video', icon: 'fa-video' },
                            { value: 'fas fa-podcast', label: 'Podcast', icon: 'fa-podcast' },
                            { value: 'fas fa-microphone', label: 'Microphone', icon: 'fa-microphone' },
                            { value: 'fas fa-headphones', label: 'Headphones', icon: 'fa-headphones' },
                            { value: 'fas fa-shopping-cart', label: 'Shopping Cart', icon: 'fa-shopping-cart' },
                            { value: 'fas fa-credit-card', label: 'Credit Card', icon: 'fa-credit-card' },
                            { value: 'fas fa-dollar-sign', label: 'Dollar', icon: 'fa-dollar-sign' },
                            { value: 'fas fa-file', label: 'File', icon: 'fa-file' },
                            { value: 'fas fa-folder', label: 'Folder', icon: 'fa-folder' },
                            { value: 'fas fa-cog', label: 'Settings', icon: 'fa-cog' },
                            { value: 'fas fa-info-circle', label: 'Info', icon: 'fa-info-circle' },
                            { value: 'fas fa-question-circle', label: 'Question', icon: 'fa-question-circle' },
                            { value: 'fas fa-check-circle', label: 'Check', icon: 'fa-check-circle' },
                            { value: 'fas fa-times-circle', label: 'Close', icon: 'fa-times-circle' },
                            { value: 'fas fa-user', label: 'User', icon: 'fa-user' },
                            { value: 'fas fa-users', label: 'Users', icon: 'fa-users' },
                            { value: 'fab fa-facebook', label: 'Facebook', icon: 'fa-facebook' },
                            { value: 'fab fa-twitter', label: 'Twitter', icon: 'fa-twitter' },
                            { value: 'fab fa-instagram', label: 'Instagram', icon: 'fa-instagram' },
                            { value: 'fab fa-youtube', label: 'YouTube', icon: 'fa-youtube' },
                            { value: 'fab fa-tiktok', label: 'TikTok', icon: 'fa-tiktok' },
                            { value: 'fab fa-linkedin', label: 'LinkedIn', icon: 'fa-linkedin' },
                            { value: 'fab fa-github', label: 'GitHub', icon: 'fa-github' },
                            { value: 'fab fa-spotify', label: 'Spotify', icon: 'fa-spotify' },
                            { value: 'fab fa-apple', label: 'Apple', icon: 'fa-apple' },
                            { value: 'fab fa-google', label: 'Google', icon: 'fa-google' }
                        ];
                        
                        // Find selected icon
                        const selectedIcon = commonIcons.find(ic => ic.value === value) || commonIcons[0];
                        const selectedIconClass = selectedIcon.value || '';
                        const selectedIconLabel = selectedIcon.label || 'No Icon';
                        
                        fieldHTML = `
                            <div class="form-group">
                                <label for="${fieldId}">${fieldDef.label}${required}</label>
                                <input type="hidden" id="${fieldId}" name="${fieldName}" value="${safeValue}">
                                <div class="icon-selector-wrapper" style="position: relative;">
                                    <button type="button" 
                                            id="${fieldId}-button"
                                            class="icon-selector-button"
                                            onclick="toggleIconSelector('${fieldId}')"
                                            style="width: 100%; padding: 0.625rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; text-align: left;">
                                        <i class="${selectedIconClass}" style="width: 20px; text-align: center; flex-shrink: 0;"></i>
                                        <span style="flex: 1;">${selectedIconLabel}</span>
                                        <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: #9ca3af;"></i>
                                    </button>
                                    <div id="${fieldId}-dropdown" 
                                         class="icon-selector-dropdown" 
                                         style="display: none; position: absolute; top: calc(100% + 0.25rem); left: 0; right: 0; background: white; border: 2px solid #e5e7eb; border-radius: 8px;  z-index: 1000; max-height: 300px; overflow-y: auto;">
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; padding: 0.5rem;">
                                            ${commonIcons.map(icon => {
                                                const isSelected = (value === icon.value) ? 'background: #f0f7ff; border-color: #0066ff;' : '';
                                                return `
                                                    <button type="button" 
                                                            class="icon-option-btn"
                                                            data-value="${icon.value.replace(/"/g, '&quot;')}"
                                                            onclick="selectIcon('${fieldId}', '${icon.value.replace(/"/g, '&quot;')}', '${icon.label.replace(/"/g, '&quot;')}')"
                                                            style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; ${isSelected}">
                                                        <i class="${icon.value || ''}" style="width: 20px; text-align: center; flex-shrink: 0; color: ${icon.value ? '#333' : '#999'}"></i>
                                                        <span style="font-size: 0.875rem; flex: 1; text-align: left;">${icon.label}</span>
                                                    </button>
                                                `;
                                            }).join('')}
                                        </div>
                                    </div>
                                </div>
                                ${helpText}
                            </div>
                        `;
                    } else {
                        fieldHTML = `
                            <div class="form-group">
                                <label for="${fieldId}">${fieldDef.label}${required}</label>
                                <input type="${fieldDef.type || 'text'}" 
                                       id="${fieldId}" 
                                       name="${fieldName}" 
                                       value="${safeValue}"
                                       placeholder="${fieldDef.placeholder || ''}"
                                       ${fieldDef.required ? 'required' : ''}
                                       onchange="saveWidgetSettingsInline(${widgetId})"
                                       class="widget-setting-input">
                                ${helpText}
                            </div>
                        `;
                    }
                    
                    formHTML += fieldHTML;
                });
                }
                
                // Featured Effect form removed - effects are managed in Appearance tab
                
                // Add action buttons
                formHTML += `
                <div class="widget-accordion-actions">
                    <button type="button" class="btn btn-secondary" onclick="editWidget(${widgetId}, this)">Edit</button>
                    <button type="button" class="btn btn-danger" onclick="deleteWidget(${widgetId})">Delete</button>
                    <div class="widget-save-indicator" id="widget-save-indicator-${widgetId}"></div>
                </div>
                `;
                
                formHTML += '</form></div>';
                console.log('Setting contentDiv.innerHTML, length:', formHTML.length);
                console.log('contentDiv before setting:', contentDiv, 'isConnected:', contentDiv.isConnected);
                
                // Double-check contentDiv is still in DOM
                const verifyDiv = document.getElementById(`widget-content-${widgetId}`);
                if (!verifyDiv || verifyDiv !== contentDiv) {
                    console.error('contentDiv changed or removed from DOM!');
                    const newDiv = document.getElementById(`widget-content-${widgetId}`);
                    if (newDiv) {
                        newDiv.innerHTML = formHTML;
                        console.log('Content set to new div');
                    } else {
                        console.error('Cannot find contentDiv in DOM!');
                    }
                    return;
                }
                
                console.log('About to set innerHTML, formHTML preview (first 200 chars):', formHTML.substring(0, 200));
                contentDiv.innerHTML = formHTML;
                console.log('innerHTML set! New length:', contentDiv.innerHTML.length);
                console.log('contentDiv.outerHTML preview:', contentDiv.outerHTML.substring(0, 300));
                
                
                // Verify it was set
                setTimeout(() => {
                    const checkDiv = document.getElementById(`widget-content-${widgetId}`);
                    if (checkDiv && checkDiv.innerHTML.includes('widget-settings-form')) {
                        console.log('Content verified - form is in DOM');
                    } else {
                        console.error('Content verification failed - form not found in DOM!');
                    }
                }, 100);
            } catch (error) {
                console.error('Error in renderWidgetSettingsInline:', error);
                console.error('Error stack:', error.stack);
                contentDiv.innerHTML = '<div class="widget-content-inner"><p style="color: #dc3545;">Error rendering widget settings: ' + error.message + '</p></div>';
            }
        };
        
        // Debounce helper for auto-save
        const widgetSaveDebounceTimers = {};
        
        window.saveWidgetSettingsInline = function(widgetId) {
            const form = document.querySelector(`.widget-settings-form[data-widget-id="${widgetId}"]`);
            const indicator = document.getElementById(`widget-save-indicator-${widgetId}`);
            
            if (!form) return;
            
            // Clear existing timer
            if (widgetSaveDebounceTimers[widgetId]) {
                clearTimeout(widgetSaveDebounceTimers[widgetId]);
            }
            
            // Update indicator
            if (indicator) {
                indicator.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Saving...</span>';
                indicator.className = 'widget-save-indicator saving';
            }
            
            // Collect form data
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('widget_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            // Get title
            const titleInput = form.querySelector(`#widget-inline-title-${widgetId}`);
            if (titleInput) {
                formData.append('title', titleInput.value.trim());
            }
            
            // Get widget config fields
            const config = {};
            const inputs = form.querySelectorAll('.widget-setting-input');
            inputs.forEach(input => {
                if (input.name && input.name !== 'title' && input.name !== 'is_featured' && input.name !== 'featured_effect') {
                    if (input.type === 'checkbox') {
                        config[input.name] = input.checked;
                    } else {
                        config[input.name] = input.value;
                    }
                }
            });
            
            formData.append('config_data', JSON.stringify(config));
            
            // Enforce thumbnail/icon exclusivity (thumbnail and icon are mutually exclusive)
            const thumbnailInput = form.querySelector(`#widget-inline-thumbnail_image-${widgetId}`);
            const iconInput = form.querySelector(`#widget-inline-icon-${widgetId}`);
            
            if (thumbnailInput && thumbnailInput.value) {
                // If thumbnail has value, ensure icon is cleared
                if (iconInput) {
                    iconInput.value = '';
                    // Update icon button display
                    const iconButton = document.getElementById(`widget-inline-icon-${widgetId}-button`);
                    if (iconButton) {
                        iconButton.innerHTML = `
                            <span style="width: 20px;"></span>
                            <span style="flex: 1;">No Icon</span>
                            <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: #9ca3af;"></i>
                        `;
                    }
                }
            } else if (iconInput && iconInput.value) {
                // If icon has value, ensure thumbnail is cleared
                if (thumbnailInput) {
                    thumbnailInput.value = '';
                    // Reset thumbnail preview
                    const thumbnailPreview = document.getElementById(`widget-thumbnail-${widgetId}-preview`);
                    if (thumbnailPreview) {
                        thumbnailPreview.innerHTML = `
                            <div style="text-align: center; color: #9ca3af; padding: 1rem;">
                                <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                <small style="font-size: 0.75rem;">No image</small>
                            </div>
                        `;
                    }
                    // Clear file input
                    const thumbnailFileInput = document.getElementById(`widget-thumbnail-${widgetId}-input`);
                    if (thumbnailFileInput) {
                        thumbnailFileInput.value = '';
                    }
                }
            }
            
            // Handle featured widget fields separately (not in config_data)
            const featuredCheckbox = form.querySelector(`#widget-inline-is_featured-${widgetId}`);
            if (featuredCheckbox) {
                formData.append('is_featured', featuredCheckbox.checked ? '1' : '0');
                
                const featuredEffectSelect = form.querySelector(`#widget-inline-featured_effect-${widgetId}`);
                if (featuredEffectSelect) {
                    // Default to 'jiggle' if featured widget has no effect selected
                    const effectValue = featuredEffectSelect.value || '';
                    // If widget is featured and effect is empty, default to 'jiggle'
                    const isFeatured = form.querySelector(`#widget-inline-is_featured-${widgetId}`)?.checked;
                    formData.append('featured_effect', (isFeatured && !effectValue) ? 'jiggle' : effectValue);
                }
            }
            
            // Debounce the save
            widgetSaveDebounceTimers[widgetId] = setTimeout(() => {
                fetch('/api/widgets.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (indicator) {
                            indicator.innerHTML = '<i class="fas fa-check-circle"></i> <span>Saved</span>';
                            indicator.className = 'widget-save-indicator saved';
                            setTimeout(() => {
                                if (indicator) {
                                    indicator.innerHTML = '';
                                    indicator.className = 'widget-save-indicator';
                                }
                            }, 2000);
                        }
                    } else {
                        if (indicator) {
                            indicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>Error</span>';
                            indicator.className = 'widget-save-indicator';
                            indicator.style.color = '#dc3545';
                        }
                        console.error('Save error:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Save error:', error);
                    if (indicator) {
                        indicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>Error</span>';
                        indicator.className = 'widget-save-indicator';
                        indicator.style.color = '#dc3545';
                    }
                });
            }, 500); // 500ms debounce
        };
        
        // Load Widget Analytics
        window.loadWidgetAnalytics = function() {
            const loadingDiv = document.getElementById('analytics-loading');
            const contentDiv = document.getElementById('analytics-content');
            const periodSelect = document.getElementById('analytics-period');
            
            if (!loadingDiv || !contentDiv || !periodSelect) return;
            
            const period = periodSelect.value;
            
            // Show loading
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Fetch analytics data
            fetch(`/api/analytics.php?action=widget_analytics&period=${period}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                
                if (data.success) {
                    contentDiv.style.display = 'block';
                    renderAnalytics(data);
                } else {
                    showToast('Failed to load analytics: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                console.error('Error loading analytics:', error);
                showToast('Error loading analytics', 'error');
            });
        };
        
        function renderAnalytics(data) {
            const summaryDiv = document.getElementById('analytics-summary');
            const tableBody = document.getElementById('analytics-table-body');
            const emptyDiv = document.getElementById('analytics-empty');
            
            if (!summaryDiv || !tableBody) return;
            
            // Render summary cards
            summaryDiv.innerHTML = `
                <div style="background: white; padding: 1.5rem; border-radius: 8px; ">
                    <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Total Clicks</div>
                    <div style="font-size: 2rem; font-weight: 700; color: #111827;">${data.total_clicks || 0}</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; ">
                    <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Page Views</div>
                    <div style="font-size: 2rem; font-weight: 700; color: #111827;">${data.page_views || 0}</div>
                </div>
            `;
            
            // Render widget table
            if (!data.widgets || data.widgets.length === 0) {
                tableBody.innerHTML = '';
                if (emptyDiv) emptyDiv.style.display = 'block';
                return;
            }
            
            if (emptyDiv) emptyDiv.style.display = 'none';
            
            tableBody.innerHTML = data.widgets.map(widget => `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem;">${escapeHtml(widget.title || 'Untitled')}</td>
                    <td style="padding: 0.75rem; color: #6b7280; text-transform: capitalize;">${escapeHtml(widget.widget_type || 'N/A')}</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;">${widget.click_count || 0}</td>
                    <td style="padding: 0.75rem; text-align: right;">${widget.views || 0}</td>
                    <td style="padding: 0.75rem; text-align: right; color: ${(widget.ctr || 0) > 5 ? '#10b981' : '#6b7280'}; font-weight: 600;">${widget.ctr || 0}%</td>
                </tr>
            `).join('');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
            
            toast.innerHTML = `<p class="toast-message">${message}</p>`;
            
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
        
        /* ========================================
           DRAG AND DROP MODULE
           Can be extracted to: /js/editor/drag-drop.js
           ======================================== */
        const EditorDragDrop = (function() {
            'use strict';
            
            let widgetsSortable = null;
            let socialIconsSortable = null;
            
            function saveWidgetOrder() {
                const widgetsContainer = document.getElementById('widgets-list');
                if (!widgetsContainer) return;
                
                const widgets = Array.from(widgetsContainer.querySelectorAll('.accordion-section[data-widget-id]'));
                const widgetOrders = widgets.map((widget, index) => ({
                    widget_id: parseInt(widget.dataset.widgetId),
                    display_order: index + 1
                }));
                
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
                    if (data.success && typeof refreshPreview === 'function') {
                        refreshPreview();
                    } else if (!data.success && typeof showToast === 'function') {
                        showToast('Failed to save widget order', 'error');
                    }
                })
                .catch(error => console.error('Error saving widget order:', error));
            }
            
            function saveSocialIconOrder() {
                const iconsContainer = document.getElementById('directories-list');
                if (!iconsContainer) return;
                
                const icons = Array.from(iconsContainer.querySelectorAll('.accordion-section[data-directory-id]'));
                const iconOrders = icons.map((icon, index) => ({
                    icon_id: parseInt(icon.dataset.directoryId),
                    display_order: index + 1
                }));
                
                const formData = new FormData();
                formData.append('action', 'reorder_social_icons');
                formData.append('icon_orders', JSON.stringify(iconOrders));
                formData.append('csrf_token', csrfToken);
                
                fetch('/api/page.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && typeof refreshPreview === 'function') {
                        refreshPreview();
                    }
                })
                .catch(error => console.error('Error saving social icon order:', error));
            }
            
            function initWidgetsDragAndDrop() {
                const widgetsContainer = document.getElementById('widgets-list');
                if (!widgetsContainer || typeof Draggable === 'undefined') return;
                
                if (widgetsSortable) widgetsSortable.destroy();
                
                widgetsSortable = new Draggable.Sortable(widgetsContainer, {
                    draggable: '.accordion-section[data-widget-id]',
                    handle: '.drag-handle',
                    mirror: { constrainDimensions: true, xAxis: false },
                    delay: 100
                });
                
                widgetsSortable.on('sortable:stop', saveWidgetOrder);
            }
            
            function initSocialIconsDragAndDrop() {
                const iconsContainer = document.getElementById('directories-list');
                if (!iconsContainer || typeof Draggable === 'undefined') return;
                
                if (socialIconsSortable) socialIconsSortable.destroy();
                
                socialIconsSortable = new Draggable.Sortable(iconsContainer, {
                    draggable: '.accordion-section[data-directory-id]',
                    handle: '.drag-handle',
                    mirror: { constrainDimensions: true, xAxis: false },
                    delay: 100
                });
                
                socialIconsSortable.on('sortable:stop', saveSocialIconOrder);
            }
            
            function init() {
                initWidgetsDragAndDrop();
                initSocialIconsDragAndDrop();
            }
            
            function destroy() {
                if (widgetsSortable) { widgetsSortable.destroy(); widgetsSortable = null; }
                if (socialIconsSortable) { socialIconsSortable.destroy(); socialIconsSortable = null; }
            }
            
            return {
                init: init,
                initWidgets: initWidgetsDragAndDrop,
                initSocialIcons: initSocialIconsDragAndDrop,
                destroy: destroy
            };
        })();
        
        // Global reference for backward compatibility
        window.saveWidgetOrder = function() {
            const widgetsContainer = document.getElementById('widgets-list');
            if (!widgetsContainer) return;
            const widgets = Array.from(widgetsContainer.querySelectorAll('.accordion-section[data-widget-id]'));
            const widgetOrders = widgets.map((widget, index) => ({
                widget_id: parseInt(widget.dataset.widgetId),
                display_order: index + 1
            }));
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('widget_orders', JSON.stringify(widgetOrders));
            formData.append('csrf_token', csrfToken);
            fetch('/api/widgets.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => { if (data.success && typeof refreshPreview === 'function') refreshPreview(); });
        };
        
        // Legacy drag and drop removed - replaced by EditorDragDrop module above
        
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
            document.getElementById('custom_' + type + '_color_hex').value = color;
            
            // Update previews if primary color changed
            if (type === 'primary') {
                updatePageFontPreview();
                updateWidgetFontPreview();
            }
        }
        
        function updateColorFromHex(type, hex) {
            // Validate hex color
            hex = hex.trim();
            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                document.getElementById('custom_' + type + '_color').value = hex;
                
                // Update previews if primary color changed
                if (type === 'primary') {
                    updatePageFontPreview();
                    updateWidgetFontPreview();
                }
            } else {
                alert('Please enter a valid hex color (e.g., #000000)');
            }
        }
        
        // Font preview update
        function updateFontPreview() {
            const headingFontEl = document.getElementById('custom_heading_font');
            const bodyFontEl = document.getElementById('custom_body_font');
            
            if (!headingFontEl || !bodyFontEl) {
                console.log('Font preview elements not found');
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
            const previewContainer = previewHeading?.closest('div[style*="margin-top: 1.5rem"]');
            
            if (previewHeading && pagePrimaryFont) {
                previewHeading.style.fontFamily = `'${pagePrimaryFont}', sans-serif`;
            }
            if (previewBody && pageSecondaryFont) {
                previewBody.style.fontFamily = `'${pageSecondaryFont}', sans-serif`;
            }
            
            // Update page styling in preview
            if (previewContainer) {
                const pageBackgroundEl = document.getElementById('page_background');
                const primaryColorEl = document.getElementById('custom_primary_color');
                
                if (pageBackgroundEl && pageBackgroundEl.value) {
                    previewContainer.style.background = pageBackgroundEl.value;
                }
                if (primaryColorEl && primaryColorEl.value) {
                    const headingColor = primaryColorEl.value;
                    if (previewHeading) previewHeading.style.color = headingColor;
                    if (previewBody) previewBody.style.color = headingColor;
                }
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
            const previewContainer = previewHeading?.closest('div[style*="margin-top: 1.5rem"]');
            
            if (previewHeading && finalPrimaryFont) {
                previewHeading.style.fontFamily = `'${finalPrimaryFont}', sans-serif`;
            }
            if (previewBody && finalSecondaryFont) {
                previewBody.style.fontFamily = `'${finalSecondaryFont}', sans-serif`;
            }
            
            // Update widget styling in preview
            if (previewContainer) {
                const widgetBackgroundEl = document.getElementById('widget_background');
                const widgetBorderColorEl = document.getElementById('widget_border_color');
                const borderWidthEl = document.getElementById('widget_border_width');
                const borderEffectEl = document.getElementById('widget_border_effect');
                const shadowIntensityEl = document.getElementById('widget_border_shadow_intensity');
                const shapeEl = document.getElementById('widget_shape');
                const primaryColorEl = document.getElementById('custom_primary_color');
                
                // Update background
                if (widgetBackgroundEl && widgetBackgroundEl.value) {
                    previewContainer.style.background = widgetBackgroundEl.value;
                }
                
                // Update border
                if (widgetBorderColorEl && widgetBorderColorEl.value && borderWidthEl) {
                    const borderWidth = borderWidthEl.value === 'thin' ? '1px' : (borderWidthEl.value === 'thick' ? '3px' : '2px');
                    previewContainer.style.border = `${borderWidth} solid ${widgetBorderColorEl.value}`;
                }
                
                // Update border radius
                if (shapeEl) {
                    const borderRadius = shapeEl.value === 'square' ? '0px' : (shapeEl.value === 'round' ? '50px' : '8px');
                    previewContainer.style.borderRadius = borderRadius;
                }
                
                // Shadow removed - no box-shadow styling
                
                // Update text color
                if (primaryColorEl && primaryColorEl.value) {
                    const headingColor = primaryColorEl.value;
                    if (previewHeading) previewHeading.style.color = headingColor;
                    if (previewBody) previewBody.style.color = headingColor;
                }
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
        
        // Update font preview on font change - wrapped in DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
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
        });
        
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
                        
                        // Update inline font previews
                        if (fieldId === 'page_primary_font') {
                            const preview = document.getElementById('inline-page-primary-preview');
                            if (preview) {
                                preview.style.fontFamily = `'${this.value}', sans-serif`;
                            }
                        } else if (fieldId === 'page_secondary_font') {
                            const preview = document.getElementById('inline-page-secondary-preview');
                            if (preview) {
                                preview.style.fontFamily = `'${this.value}', sans-serif`;
                            }
                        }
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
        
        // Global variables for image cropping
        let croppieInstance = null;
        let currentCropContext = null;
        let currentCropInputId = null;
        let currentCropPreviewId = null;
        let currentCropWidgetId = null;
        
        // Widget thumbnail upload - opens crop modal
        window.uploadWidgetThumbnail = function(widgetId) {
            const inputId = `widget-thumbnail-${widgetId}-input`;
            const previewId = `widget-thumbnail-${widgetId}-preview`;
            const input = document.getElementById(inputId);
            
            if (!input) {
                showToast('File input not found', 'error');
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
                showToast('Please select a valid image file (JPEG, PNG, GIF, or WebP)', 'error');
                return;
            }
            
            // Set context for cropping
            currentCropContext = 'widget-thumbnail';
            currentCropInputId = inputId;
            currentCropPreviewId = previewId;
            currentCropWidgetId = widgetId;
            
            // Open crop modal with image
            openCropModal(file);
        };
        
        // Remove widget thumbnail
        window.removeWidgetThumbnail = function(widgetId) {
            const previewId = `widget-thumbnail-${widgetId}-preview`;
            const inputId = `widget-thumbnail-${widgetId}-input`;
            // Try both modal and inline hidden inputs
            const hiddenInputModal = document.getElementById('widget_config_thumbnail_image');
            const hiddenInputInline = document.getElementById(`widget-inline-thumbnail_image-${widgetId}`);
            const hiddenInput = hiddenInputInline || hiddenInputModal;
            
            // Reset preview
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.innerHTML = `
                    <div style="text-align: center; color: #9ca3af; padding: 1rem;">
                        <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                        <small style="font-size: 0.75rem;">No image</small>
                    </div>
                `;
            }
            
            // Clear file input
            const input = document.getElementById(inputId);
            if (input) {
                input.value = '';
            }
            
            // Clear hidden input (both modal and inline)
            if (hiddenInput) {
                hiddenInput.value = '';
            }
            
            // Remove remove button if it exists (for inline context)
            const removeBtn = document.querySelector(`button[onclick="removeWidgetThumbnail(${widgetId})"]`);
            if (removeBtn) {
                removeBtn.remove();
            }
            
            // Save widget thumbnail removal to config_data
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('widget_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            // Get current config_data and remove thumbnail
            fetch('/api/widgets.php?action=get&widget_id=' + widgetId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.widget) {
                        const currentConfig = typeof data.widget.config_data === 'string' 
                            ? JSON.parse(data.widget.config_data) 
                            : (data.widget.config_data || {});
                        
                        // Remove thumbnail from config
                        delete currentConfig.thumbnail_image;
                        
                        // Save updated config
                        formData.append('config_data', JSON.stringify(currentConfig));
                        
                        return fetch('/api/widgets.php', {
                            method: 'POST',
                            body: formData
                        }).then(r => r.json());
                    }
                })
                .then(result => {
                    if (result && result.success) {
                        showToast('Thumbnail removed', 'success');
                    } else {
                        console.warn('Failed to remove widget thumbnail:', result);
                    }
                })
                .catch(error => {
                    console.error('Error removing widget thumbnail:', error);
                });
        };
        
        // Image upload functionality - opens crop modal
        function uploadImage(type, context = 'appearance') {
            console.log('uploadImage called:', { type, context });
            
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
                console.warn('No file selected');
                showToast('Please select an image file', 'error');
                return;
            }
            
            console.log('File selected:', { name: file.name, size: file.size, type: file.type });
            
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
            
            // Store context for later use
            currentCropContext = context;
            currentCropInputId = inputId;
            currentCropPreviewId = previewId;
            
            // Open crop modal with image
            openCropModal(file);
        }
        
        // Open crop modal with image
        function openCropModal(file) {
            // Check if Croppie is loaded
            if (typeof Croppie === 'undefined') {
                console.error('Croppie library not loaded');
                showToast('Image cropper not available. Please refresh the page.', 'error');
                return;
            }
            
            const overlay = document.getElementById('crop-modal-overlay');
            const container = document.getElementById('croppie-container');
            
            if (!overlay || !container) {
                console.error('Crop modal elements not found');
                showToast('Crop modal not available', 'error');
                return;
            }
            
            // Destroy existing croppie instance if it exists
            if (croppieInstance) {
                try {
                    croppieInstance.destroy();
                } catch (e) {
                    console.warn('Error destroying croppie instance:', e);
                }
            }
            
            // Clear container
            container.innerHTML = '';
            
            try {
                // Initialize Croppie with 1:1 aspect ratio (square crop)
                croppieInstance = new Croppie(container, {
                    viewport: { width: 300, height: 300, type: 'square' },
                    boundary: { width: '100%', height: 400 },
                    showZoomer: true,
                    enableOrientation: true,
                    enforceBoundary: true,
                    enableResize: false // Disable resize to maintain 1:1 ratio
                });
                
                // Bind image to croppie
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        croppieInstance.bind({
                            url: e.target.result
                        });
                        
                        // Show modal after image is loaded
                        overlay.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    } catch (error) {
                        console.error('Error binding image to croppie:', error);
                        showToast('Failed to load image for cropping', 'error');
                    }
                };
                reader.onerror = function() {
                    console.error('Error reading file');
                    showToast('Failed to read image file', 'error');
                };
                reader.readAsDataURL(file);
            } catch (error) {
                console.error('Error initializing Croppie:', error);
                showToast('Failed to initialize image cropper', 'error');
            }
        }
        
        // Close crop modal
        function closeCropModal() {
            const overlay = document.getElementById('crop-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // Clean up
            if (croppieInstance) {
                croppieInstance.destroy();
                croppieInstance = null;
            }
            
            // Clear file input if we have the context
            const inputId = currentCropInputId;
            if (inputId) {
                const input = document.getElementById(inputId);
                if (input) input.value = '';
            }
            
            currentCropContext = null;
            currentCropInputId = null;
            currentCropPreviewId = null;
        }
        
        // Apply crop and upload
        function applyCrop() {
            if (!croppieInstance) {
                showToast('No image to crop', 'error');
                return;
            }
            
            // Show loading state
            showToast('Uploading image...', 'info');
            
            // Determine upload type and filename based on context
            const isWidgetThumbnail = currentCropContext === 'widget-thumbnail';
            const uploadType = isWidgetThumbnail ? 'thumbnail' : 'profile';
            const filename = isWidgetThumbnail ? 'widget-thumbnail.jpg' : 'profile.jpg';
            
            // Get cropped image as blob
            croppieInstance.result('blob', {
                type: 'image/jpeg',
                quality: 0.9,
                size: { width: 400, height: 400 }
            }).then(function(blob) {
                // Upload the cropped blob
                const formData = new FormData();
                formData.append('image', blob, filename);
                formData.append('type', uploadType);
                formData.append('csrf_token', csrfToken);
                
                fetch('/api/upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Upload failed:', text);
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.error || 'Upload failed');
                            } catch (e) {
                                if (e instanceof Error && e.message !== 'Upload failed') {
                                    throw e;
                                }
                                throw new Error(text || 'Upload failed');
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        handleImageUploadSuccess(data, currentCropPreviewId, isWidgetThumbnail);
                        closeCropModal();
                        showToast('Image uploaded successfully!', 'success');
                    } else {
                        showToast(data.error || 'Failed to upload image', 'error');
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    showToast(error.message || 'An error occurred while uploading', 'error');
                });
            }).catch(function(error) {
                console.error('Crop error:', error);
                showToast('Failed to process image', 'error');
            });
        }
        
        // Handle successful image upload
        function handleImageUploadSuccess(data, previewId, isWidgetThumbnail = false) {
            const imageUrl = data.url || data.path;
            
            if (!imageUrl) {
                console.error('No image URL in response');
                return;
            }
            
            // Handle widget thumbnail upload
            if (isWidgetThumbnail && currentCropWidgetId) {
                // Update hidden input - try both modal and inline formats
                const hiddenInputModal = document.getElementById('widget_config_thumbnail_image');
                const hiddenInputInline = document.getElementById(`widget-inline-thumbnail_image-${currentCropWidgetId}`);
                const hiddenInput = hiddenInputInline || hiddenInputModal;
                
                if (hiddenInput) {
                    hiddenInput.value = imageUrl;
                }
                
                // Update preview - try both modal and inline formats
                const preview = document.getElementById(previewId);
                if (preview) {
                    if (preview.tagName === 'IMG') {
                        preview.src = imageUrl + '?t=' + Date.now();
                    } else {
                        // Replace placeholder div with image
                        preview.innerHTML = '';
                        preview.style.background = 'none';
                        preview.style.border = 'none';
                        const img = document.createElement('img');
                        img.src = imageUrl + '?t=' + Date.now();
                        img.alt = 'Thumbnail';
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '6px';
                        preview.appendChild(img);
                    }
                }
                
                // Add remove button if it doesn't exist
                const removeBtnExisting = document.querySelector(`button[onclick="removeWidgetThumbnail(${currentCropWidgetId})"]`);
                if (!removeBtnExisting) {
                    const uploadBtn = document.querySelector(`button[onclick="uploadWidgetThumbnail(${currentCropWidgetId})"]`);
                    if (uploadBtn && uploadBtn.parentElement) {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-danger btn-small';
                        removeBtn.onclick = () => removeWidgetThumbnail(currentCropWidgetId);
                        removeBtn.style.cssText = 'padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.875rem; font-weight: 600;';
                        removeBtn.textContent = 'Remove';
                        uploadBtn.parentElement.appendChild(removeBtn);
                    }
                }
                
                // Clear icon field when thumbnail is uploaded (thumbnail and icon are mutually exclusive)
                const iconSelectButton = document.getElementById(`widget-inline-icon-${currentCropWidgetId}-button`);
                const iconHiddenInput = document.getElementById(`widget-inline-icon-${currentCropWidgetId}`);
                if (iconSelectButton) {
                    // Reset button to "None" state
                    const iconSpan = iconSelectButton.querySelector('span');
                    const iconI = iconSelectButton.querySelector('i');
                    if (iconSpan) iconSpan.textContent = 'None';
                    if (iconI && !iconI.classList.contains('fa-chevron-down')) {
                        iconI.className = 'fas fa-chevron-down';
                        iconI.style.color = '#9ca3af';
                    }
                }
                if (iconHiddenInput) {
                    iconHiddenInput.value = '';
                }
                
                // Save widget thumbnail to config_data
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('widget_id', currentCropWidgetId);
                formData.append('csrf_token', csrfToken);
                
                // Get current config_data (must use POST, not GET)
                const getFormData = new FormData();
                getFormData.append('action', 'get');
                getFormData.append('widget_id', currentCropWidgetId);
                getFormData.append('csrf_token', csrfToken);
                
                fetch('/api/widgets.php', {
                    method: 'POST',
                    body: getFormData
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.widget) {
                            const currentConfig = typeof data.widget.config_data === 'string' 
                                ? JSON.parse(data.widget.config_data) 
                                : (data.widget.config_data || {});
                            
                            // Update thumbnail in config
                            currentConfig.thumbnail_image = imageUrl;
                            
                            // Save updated config
                            formData.append('config_data', JSON.stringify(currentConfig));
                            
                            return fetch('/api/widgets.php', {
                                method: 'POST',
                                body: formData
                            }).then(r => {
                                if (!r.ok) {
                                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                                }
                                return r.json();
                            }).catch(err => {
                                console.error('API call error:', err);
                                return { success: false, error: err.message || 'Network error' };
                            });
                        } else {
                            console.error('Failed to get widget data:', data);
                            return { success: false, error: data?.error || 'Failed to retrieve widget data' };
                        }
                    })
                    .then(result => {
                        // Clear widget context before checking result
                        const savedWidgetId = currentCropWidgetId;
                        currentCropWidgetId = null;
                        
                        if (result && result.success) {
                            console.log('Widget thumbnail saved successfully');
                            // Also trigger inline save to ensure form state is updated
                            if (typeof saveWidgetSettingsInline === 'function' && savedWidgetId) {
                                saveWidgetSettingsInline(savedWidgetId);
                            }
                        } else {
                            const errorMsg = result?.error || (result ? 'API returned failure without error message' : 'No response from API');
                            console.warn('Failed to save widget thumbnail config:', errorMsg);
                            console.warn('Full response:', result);
                        }
                    })
                    .catch(error => {
                        console.error('Error saving widget thumbnail:', error);
                        currentCropWidgetId = null;
                    });
                
                return;
            }
            
            // Update preview (for profile images)
            const preview = document.getElementById(previewId);
            if (preview) {
                if (preview.tagName === 'IMG') {
                    preview.src = imageUrl + '?t=' + Date.now();
                } else if (preview.parentNode) {
                    const img = document.createElement('img');
                    img.id = previewId;
                    img.src = imageUrl;
                    img.alt = 'Profile';
                    img.style.cssText = 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;';
                    preview.parentNode.replaceChild(img, preview);
                }
            }
            
            // Update user menu avatars
            const userMenuAvatars = document.querySelectorAll('.user-menu-avatar');
            userMenuAvatars.forEach(avatar => {
                const existingImg = avatar.querySelector('img');
                if (existingImg) {
                    existingImg.src = imageUrl + '?t=' + Date.now();
                } else {
                    const img = document.createElement('img');
                    img.src = imageUrl + '?t=' + Date.now();
                    img.alt = 'Profile';
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 50%;';
                    avatar.innerHTML = '';
                    avatar.appendChild(img);
                }
            });
            
            // Show remove button if needed
            if (currentCropContext) {
                const inputId = currentCropInputId;
                const input = document.getElementById(inputId);
                
                if (input) {
                    let buttonContainer;
                    if (currentCropContext === 'settings') {
                        buttonContainer = input.closest('div').querySelector('div[style*="display: flex"]');
                    } else {
                        buttonContainer = input.nextElementSibling;
                    }
                    
                    if (buttonContainer) {
                        const existingRemoveBtn = buttonContainer.querySelector('.btn-danger');
                        if (!existingRemoveBtn) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-danger btn-small';
                            removeBtn.textContent = 'Remove';
                            removeBtn.onclick = () => removeImage('profile', currentCropContext);
                            buttonContainer.appendChild(removeBtn);
                        }
                    }
                }
            }
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
                    
                    // Revert user menu avatars to initial letter
                    const userMenuAvatars = document.querySelectorAll('.user-menu-avatar');
                    const userEmailEl = document.querySelector('.user-menu-email');
                    const userEmail = userEmailEl ? userEmailEl.textContent.trim() : '';
                    const initial = userEmail ? userEmail.charAt(0).toUpperCase() : 'U';
                    userMenuAvatars.forEach(avatar => {
                        avatar.innerHTML = initial;
                    });
                    
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
            document.getElementById('directory-id').value = '';
            document.getElementById('directory-modal-title').textContent = 'Add Social Icon';
            document.getElementById('directory-modal').style.display = 'block';
        }
        
        function editDirectory(directoryId, platformName, url) {
            document.getElementById('directory-id').value = directoryId;
            document.getElementById('directory_platform').value = platformName;
            document.getElementById('directory_url').value = url;
            document.getElementById('directory-modal-title').textContent = 'Edit Social Icon';
            document.getElementById('directory-modal').style.display = 'block';
        }
        
        function closeDirectoryModal() {
            document.getElementById('directory-modal').style.display = 'none';
            document.getElementById('directory-form').reset();
            document.getElementById('directory-id').value = '';
        }
        
        // Social icon accordions now use the same toggleAccordion() function as Appearance tab
        
        function saveSocialIcon(event, directoryId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'update_directory');
            formData.append('directory_id', directoryId);
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Social icon updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to update social icon', 'error');
                }
            })
            .catch(() => {
                showToast('An error occurred', 'error');
            });
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
                    showToast('Social icon deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to delete social icon', 'error');
                }
            })
            .catch(() => {
                showToast('An error occurred', 'error');
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
                    showToast('Social icon added successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to add social icon', 'error');
                }
            })
            .catch(() => {
                showToast('An error occurred', 'error');
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
            const hidden = document.getElementById('page_background');
            
            if (!colorPicker || !hexInput || !hidden) {
                console.error('Page background elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            hidden.value = color;
            
            // Update page preview
            updatePageFontPreview();
            
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
                const hidden = document.getElementById('page_background');
                
                colorPicker.value = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                // Update page preview
                updatePageFontPreview();
                
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
            
            if (!startColorEl || !endColorEl || !directionEl || !preview || !hidden) {
                console.error('Gradient elements not found');
                return;
            }
            
            const startColor = startColorEl.value;
            const endColor = endColorEl.value;
            const direction = directionEl.value;
            
            const gradient = `linear-gradient(${direction}, ${startColor} 0%, ${endColor} 100%)`;
            preview.style.background = gradient;
            hidden.value = gradient;
            
            // Update page preview
            updatePageFontPreview();
            
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
            
            // Update widget preview for styling changes
            updateWidgetFontPreview();
            
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
            const hidden = document.getElementById('widget_glow_color_hidden');
            
            if (!colorPicker || !hexInput || !hidden) {
                console.error('Glow color elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
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
                const hidden = document.getElementById('widget_glow_color_hidden');
                
                if (!colorPicker || !hidden) {
                    console.error('Glow color elements not found');
                    return;
                }
                
                colorPicker.value = hex;
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
            const hidden = document.getElementById('widget_background');
            
            if (!colorPicker || !hexInput || !hidden) {
                console.error('Widget background elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
            hidden.value = color;
            
            // Update widget preview
            updateWidgetFontPreview();
            
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
                const hidden = document.getElementById('widget_background');
                
                colorPicker.value = hex;
                hidden.value = hex;
                hexInput.value = hex;
                
                // Update widget preview
                updateWidgetFontPreview();
                
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
            const hidden = document.getElementById('widget_border_color');
            
            if (!colorPicker || !hexInput || !hidden) {
                console.error('Widget border color elements not found');
                return;
            }
            
            const color = colorPicker.value;
            hexInput.value = color;
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
                const hidden = document.getElementById('widget_border_color');
                
                colorPicker.value = hex;
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
            
            saveAppearanceForm();
        }
        
        // Flip gradient colors (swap start and end)
        function flipGradientColors(type) {
            let startColorEl, endColorEl, updateFunction;
            
            if (type === 'page') {
                startColorEl = document.getElementById('gradient_start_color');
                endColorEl = document.getElementById('gradient_end_color');
                updateFunction = updateGradient;
            } else if (type === 'widget') {
                startColorEl = document.getElementById('widget_gradient_start_color');
                endColorEl = document.getElementById('widget_gradient_end_color');
                updateFunction = updateWidgetGradient;
            } else if (type === 'border') {
                startColorEl = document.getElementById('widget_border_gradient_start_color');
                endColorEl = document.getElementById('widget_border_gradient_end_color');
                updateFunction = updateWidgetBorderGradient;
            }
            
            if (!startColorEl || !endColorEl) {
                console.error('Gradient color elements not found for type:', type);
                return;
            }
            
            // Read both values first
            const startColor = startColorEl.value;
            const endColor = endColorEl.value;
            
            // Swap the colors
            startColorEl.value = endColor;
            endColorEl.value = startColor;
            
            // Trigger change event to ensure browser updates
            startColorEl.dispatchEvent(new Event('change', { bubbles: true }));
            endColorEl.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Update the gradient preview
            if (updateFunction) {
                updateFunction();
            }
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
            overlay.style.display = 'block';
            drawer.style.display = 'flex';
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
            const themeNameEl = document.getElementById('theme_name');
            if (!themeNameEl) {
                alert('Theme name field not found');
                return;
            }
            
            const themeName = themeNameEl.value.trim();
            
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
            const primaryColorEl = document.getElementById('custom_primary_color');
            const secondaryColorEl = document.getElementById('custom_secondary_color');
            const accentColorEl = document.getElementById('custom_accent_color');
            if (primaryColorEl) formData.append('custom_primary_color', primaryColorEl.value);
            if (secondaryColorEl) formData.append('custom_secondary_color', secondaryColorEl.value);
            if (accentColorEl) formData.append('custom_accent_color', accentColorEl.value);
            
            // Get current fonts
            const pagePrimaryFontEl = document.getElementById('page_primary_font');
            const pageSecondaryFontEl = document.getElementById('page_secondary_font');
            const widgetPrimaryFontEl = document.getElementById('widget_primary_font');
            const widgetSecondaryFontEl = document.getElementById('widget_secondary_font');
            if (pagePrimaryFontEl) formData.append('page_primary_font', pagePrimaryFontEl.value);
            if (pageSecondaryFontEl) formData.append('page_secondary_font', pageSecondaryFontEl.value);
            if (widgetPrimaryFontEl) formData.append('widget_primary_font', widgetPrimaryFontEl.value);
            if (widgetSecondaryFontEl) formData.append('widget_secondary_font', widgetSecondaryFontEl.value);
            
            // Get page background
            const pageBackgroundEl = document.getElementById('page_background');
            if (pageBackgroundEl && pageBackgroundEl.value) formData.append('page_background', pageBackgroundEl.value);
            
            // Get widget background and border color
            const widgetBackgroundEl = document.getElementById('widget_background');
            const widgetBorderColorEl = document.getElementById('widget_border_color');
            if (widgetBackgroundEl && widgetBackgroundEl.value) formData.append('widget_background', widgetBackgroundEl.value);
            if (widgetBorderColorEl && widgetBorderColorEl.value) formData.append('widget_border_color', widgetBorderColorEl.value);
            
            // Get spatial effect
            const spatialEffectEl = document.getElementById('spatial_effect');
            if (spatialEffectEl && spatialEffectEl.value) formData.append('spatial_effect', spatialEffectEl.value);
            
            // Get widget styles
            const widgetStyles = {
                border_width: '',
                border_effect: '',
                border_shadow_intensity: '',
                border_glow_intensity: '',
                glow_color: '',
                spacing: '',
                shape: ''
            };
            
            const borderWidthEl = document.getElementById('widget_border_width');
            const borderEffectEl = document.getElementById('widget_border_effect');
            const shadowIntensityEl = document.getElementById('widget_border_shadow_intensity');
            const glowIntensityEl = document.getElementById('widget_border_glow_intensity');
            const glowColorEl = document.getElementById('widget_glow_color_hidden');
            const spacingEl = document.getElementById('widget_spacing');
            const shapeEl = document.getElementById('widget_shape');
            
            if (borderWidthEl) widgetStyles.border_width = borderWidthEl.value;
            if (borderEffectEl) widgetStyles.border_effect = borderEffectEl.value;
            if (shadowIntensityEl) widgetStyles.border_shadow_intensity = shadowIntensityEl.value;
            if (glowIntensityEl) widgetStyles.border_glow_intensity = glowIntensityEl.value;
            if (glowColorEl) widgetStyles.glow_color = glowColorEl.value;
            if (spacingEl) widgetStyles.spacing = spacingEl.value;
            if (shapeEl) widgetStyles.shape = shapeEl.value;
            
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
        
        // Icon Selector Functions (for Font Awesome dropdown)
        if (typeof window.iconSelectorFunctionsAdded === 'undefined') {
            window.iconSelectorFunctionsAdded = true;
            
            // Add CSS for icon selector
            if (!document.getElementById('icon-selector-styles')) {
                const style = document.createElement('style');
                style.id = 'icon-selector-styles';
                style.textContent = `
                    .icon-selector-button:hover {
                        border-color: #0066ff !important;
                        background: #f0f7ff !important;
                    }
                    .icon-option-btn:hover {
                        border-color: #0066ff !important;
                        background: #f0f7ff !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Toggle icon selector dropdown
            window.toggleIconSelector = function(fieldId) {
                const dropdown = document.getElementById(fieldId + '-dropdown');
                if (!dropdown) return;
                
                // Close all other dropdowns
                document.querySelectorAll('.icon-selector-dropdown').forEach(dd => {
                    if (dd !== dropdown) {
                        dd.style.display = 'none';
                    }
                });
                
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            };
            
            // Select icon from dropdown
            window.selectIcon = function(fieldId, iconValue, iconLabel) {
                const hiddenInput = document.getElementById(fieldId);
                const button = document.getElementById(fieldId + '-button');
                const dropdown = document.getElementById(fieldId + '-dropdown');
                
                // Extract widget ID from fieldId (format: widget-inline-icon-{widgetId})
                const widgetIdMatch = fieldId.match(/widget-inline-icon-(\d+)/);
                if (widgetIdMatch && widgetIdMatch[1]) {
                    const widgetId = widgetIdMatch[1];
                    // Clear thumbnail when icon is selected (thumbnail and icon are mutually exclusive)
                    const thumbnailHiddenInput = document.getElementById(`widget-inline-thumbnail_image-${widgetId}`);
                    const thumbnailPreview = document.getElementById(`widget-thumbnail-${widgetId}-preview`);
                    const thumbnailFileInput = document.getElementById(`widget-thumbnail-${widgetId}-input`);
                    
                    if (thumbnailHiddenInput) {
                        thumbnailHiddenInput.value = '';
                    }
                    if (thumbnailPreview) {
                        thumbnailPreview.innerHTML = `
                            <div style="text-align: center; color: #9ca3af; padding: 1rem;">
                                <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                <small style="font-size: 0.75rem;">No image</small>
                            </div>
                        `;
                    }
                    if (thumbnailFileInput) {
                        thumbnailFileInput.value = '';
                    }
                    // Remove remove button if it exists
                    const removeBtn = document.querySelector(`button[onclick="removeWidgetThumbnail(${widgetId})"]`);
                    if (removeBtn) {
                        removeBtn.remove();
                    }
                    
                    // Save widget settings after clearing thumbnail
                    if (typeof saveWidgetSettingsInline === 'function') {
                        saveWidgetSettingsInline(parseInt(widgetId));
                    }
                }
                
                if (hiddenInput) {
                    hiddenInput.value = iconValue;
                }
                
                if (button) {
                    const iconClass = iconValue || '';
                    const iconHtml = iconClass ? `<i class="${iconClass}" style="width: 20px; text-align: center; flex-shrink: 0;"></i>` : '<span style="width: 20px;"></span>';
                    button.innerHTML = `
                        ${iconHtml}
                        <span style="flex: 1;">${iconLabel}</span>
                        <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: #9ca3af;"></i>
                    `;
                }
                
                if (dropdown) {
                    dropdown.style.display = 'none';
                }
            };
            
            // Close icon dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.icon-selector-wrapper')) {
                    document.querySelectorAll('.icon-selector-dropdown').forEach(dd => {
                        dd.style.display = 'none';
                    });
                }
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

