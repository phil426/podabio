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

// Get themes
$themes = fetchAll("SELECT * FROM themes WHERE is_active = 1 ORDER BY name ASC");

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
            margin-right: 400px;
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
        
        /* Right Preview Panel */
        .preview-panel {
            width: 400px;
            background: #1f2937;
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #374151;
        }
        
        .preview-header {
            padding: 1rem;
            background: #111827;
            border-bottom: 1px solid #374151;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .preview-header h3 {
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .preview-device {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .phone-frame {
            width: 320px;
            height: 568px;
            background: #000000;
            border-radius: 24px;
            padding: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            position: relative;
        }
        
        .phone-screen {
            width: 100%;
            height: 100%;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }
        
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            transform: scale(1);
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .preview-panel {
                display: none;
            }
            .editor-main {
                margin-right: 0;
            }
        }
        
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
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('rss', this)">
                        <i class="fas fa-rss"></i>
                        <span>RSS Feed</span>
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
                <?php if (empty($links)): ?>
                    <li>No widgets yet. Click "Add Widget" to browse the widget gallery and add content to your page.</li>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <li class="widget-item" data-widget-id="<?php echo $link['id']; ?>">
                            <div class="widget-info">
                                <div class="widget-title"><?php echo h($link['title']); ?></div>
                                <div class="widget-url"><?php echo h($link['url']); ?></div>
                            </div>
                            <div class="widget-actions">
                                <button class="btn btn-secondary btn-small" onclick="editWidget(<?php echo $link['id']; ?>, this)">Edit</button>
                                <button class="btn btn-danger btn-small" onclick="deleteWidget(<?php echo $link['id']; ?>)">Delete</button>
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
                                <button type="button" class="btn btn-secondary btn-small" onclick="uploadImage('profile', 'settings')">Upload Profile Image</button>
                                <?php if ($page['profile_image']): ?>
                                    <button type="button" class="btn btn-danger btn-small" onclick="removeImage('profile', 'settings')">Remove</button>
                                <?php endif; ?>
                            </div>
                            <small style="display: block; color: #666;">Recommended: 400x400px, square image. Max 5MB</small>
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
                    <label for="theme_id">Theme</label>
                    <select id="theme_id" name="theme_id" onchange="handleThemeChange()">
                        <option value="">No Theme (Custom)</option>
                        <?php foreach ($themes as $theme): ?>
                            <option value="<?php echo $theme['id']; ?>" <?php echo ($page['theme_id'] == $theme['id']) ? 'selected' : ''; ?>>
                                <?php echo h($theme['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select a theme to automatically apply colors and fonts, or choose "Custom" to set your own.</small>
                </div>
                
                <div class="form-group">
                    <label for="layout_option">Layout</label>
                    <select id="layout_option" name="layout_option">
                        <option value="layout1" <?php echo ($page['layout_option'] == 'layout1') ? 'selected' : ''; ?>>Layout 1</option>
                        <option value="layout2" <?php echo ($page['layout_option'] == 'layout2') ? 'selected' : ''; ?>>Layout 2</option>
                    </select>
                </div>
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <h3 style="margin-top: 0;">Custom Colors</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Customize colors if no theme is selected or to override theme colors.</small>
                
                <?php
                $pageColors = $page['colors'] ? json_decode($page['colors'], true) : [];
                $customPrimary = $pageColors['primary'] ?? '#000000';
                $customSecondary = $pageColors['secondary'] ?? '#ffffff';
                $customAccent = $pageColors['accent'] ?? '#0066ff';
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
                
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #ddd;">
                
                <h3 style="margin-top: 0;">Custom Fonts</h3>
                <small style="display: block; margin-bottom: 1rem; color: #666;">Choose fonts for headings and body text. Popular Google Fonts are included.</small>
                
                <?php
                $pageFonts = $page['fonts'] ? json_decode($page['fonts'], true) : [];
                $customHeadingFont = $pageFonts['heading'] ?? 'Inter';
                $customBodyFont = $pageFonts['body'] ?? 'Inter';
                
                // Popular Google Fonts
                $googleFonts = [
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
                    'Crimson Text' => 'Crimson Text'
                ];
                ?>
                
                <div class="form-group">
                    <label for="custom_heading_font">Heading Font</label>
                    <select id="custom_heading_font" name="custom_heading_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($customHeadingFont == $fontValue) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for titles and headings</small>
                </div>
                
                <div class="form-group">
                    <label for="custom_body_font">Body Font</label>
                    <select id="custom_body_font" name="custom_body_font" style="width: 100%; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <?php foreach ($googleFonts as $fontValue => $fontName): ?>
                            <option value="<?php echo h($fontValue); ?>" <?php echo ($customBodyFont == $fontValue) ? 'selected' : ''; ?>>
                                <?php echo h($fontName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 0.5rem;">Used for body text and descriptions</small>
                </div>
                
                <!-- Font Preview -->
                <div style="margin-top: 1.5rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                    <h4 style="margin-top: 0;">Font Preview</h4>
                    <h3 id="font-preview-heading" style="font-family: '<?php echo h($customHeadingFont); ?>', sans-serif; margin: 0.5rem 0;">Sample Heading Text</h3>
                    <p id="font-preview-body" style="font-family: '<?php echo h($customBodyFont); ?>', sans-serif; margin: 0.5rem 0; color: #666;">This is a preview of how your body text will look with the selected font.</p>
                </div>
                
                <!-- Profile Image Upload -->
                <div class="form-group">
                    <label>Profile Image</label>
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <?php if ($page['profile_image']): ?>
                                <img id="profile-preview" src="<?php echo h($page['profile_image']); ?>" alt="Profile" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                            <?php else: ?>
                                <div id="profile-preview" style="width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image</div>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" id="profile-image-input" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-bottom: 10px;">
                            <button type="button" class="btn btn-secondary btn-small" onclick="uploadImage('profile')">Upload Profile Image</button>
                            <?php if ($page['profile_image']): ?>
                                <button type="button" class="btn btn-danger btn-small" onclick="removeImage('profile')">Remove</button>
                            <?php endif; ?>
                            <small style="display: block; margin-top: 5px;">Recommended: 400x400px, square image</small>
                        </div>
                    </div>
                </div>
                
                <!-- Background Image Upload -->
                <div class="form-group">
                    <label>Background Image</label>
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <?php if ($page['background_image']): ?>
                                <img id="background-preview" src="<?php echo h($page['background_image']); ?>" alt="Background" style="width: 200px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;">
                            <?php else: ?>
                                <div id="background-preview" style="width: 200px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image</div>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" id="background-image-input" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-bottom: 10px;">
                            <button type="button" class="btn btn-secondary btn-small" onclick="uploadImage('background')">Upload Background Image</button>
                            <?php if ($page['background_image']): ?>
                                <button type="button" class="btn btn-danger btn-small" onclick="removeImage('background')">Remove</button>
                            <?php endif; ?>
                            <small style="display: block; margin-top: 5px;">Recommended: 1920x1080px or similar wide format</small>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Appearance</button>
            </form>
        </div>
        
        <!-- RSS Feed Tab -->
        <div id="tab-rss" class="tab-content">
            <h2>RSS Feed</h2>
            <form id="rss-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="rss_feed_url">RSS Feed URL</label>
                    <input type="url" id="rss_feed_url" name="rss_feed_url" value="<?php echo h($page['rss_feed_url'] ?? ''); ?>" placeholder="https://example.com/podcast.rss">
                    <small>Enter your podcast RSS feed URL to auto-populate episodes and information.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Save & Import RSS</button>
            </form>
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
        
        <!-- Right Preview Panel -->
        <?php if ($page): ?>
        <aside class="preview-panel">
            <div class="preview-header">
                <h3>Mobile Preview</h3>
                <button onclick="refreshPreview()" style="background: none; border: none; color: #9ca3af; cursor: pointer; padding: 0.25rem 0.5rem;" title="Refresh Preview">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="preview-device">
                <div class="phone-frame">
                    <div class="phone-screen">
                        <iframe id="preview-iframe" class="preview-iframe" src="/<?php echo h($page['username']); ?>?preview=1" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </aside>
        <?php endif; ?>
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
    
        <!-- Edit Widget Modal (only shown on Widgets tab) -->
        <?php if ($page): ?>
        <div id="widget-modal-overlay" class="modal-overlay" onclick="closeWidgetModal()">
            <div class="modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 id="modal-title">Edit Widget</h2>
                    <button class="modal-close" onclick="closeWidgetModal()" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-content">
                    <form id="widget-form" onsubmit="event.preventDefault(); handleWidgetFormSubmit(this);">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                        <input type="hidden" name="action" id="widget-action" value="update">
                        <input type="hidden" name="link_id" id="widget-id">
                        
                        <div class="form-group">
                            <label for="widget_type">Widget Type</label>
                            <select id="widget_type" name="type" required>
                                <option value="custom">Custom Link</option>
                                <option value="social">Social Media</option>
                                <option value="affiliate">Affiliate Link</option>
                                <option value="amazon_affiliate">Amazon Affiliate</option>
                                <option value="sponsor">Sponsor Link</option>
                                <option value="email_subscribe">Email Subscribe</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="widget_title">Title</label>
                            <input type="text" id="widget_title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="widget_url">URL</label>
                            <input type="url" id="widget_url" name="url" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="widget_disclosure">Disclosure Text (for affiliate/sponsor links)</label>
                            <textarea id="widget_disclosure" name="disclosure_text" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeWidgetModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('widget-form').dispatchEvent(new Event('submit'))">Save Changes</button>
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
        
        // Refresh preview iframe
        function refreshPreview() {
            const iframe = document.getElementById('preview-iframe');
            if (iframe) {
                iframe.src = iframe.src; // Reload iframe
            }
        }
        
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
        
        // Make functions globally accessible
        window.showAddWidgetForm = function() {
            // Add a blank new widget item to the top of the list
            const widgetsList = document.getElementById('widgets-list');
            if (!widgetsList) return;
            
            // Remove "no widgets" message if present
            const noWidgetsMsg = widgetsList.querySelector('li:not(.widget-item)');
            if (noWidgetsMsg && !noWidgetsMsg.classList.contains('widget-item')) {
                noWidgetsMsg.remove();
            }
            
            // Generate a temporary ID (negative number to indicate it's new)
            const tempId = -(Date.now());
            
            // Create new widget item
            const newWidgetItem = document.createElement('li');
            newWidgetItem.className = 'widget-item new';
            newWidgetItem.setAttribute('data-widget-id', tempId);
            newWidgetItem.innerHTML = `
                <div class="widget-info">
                    <div class="widget-title">New Widget</div>
                    <div class="widget-url">https://</div>
                </div>
                <div class="widget-actions">
                    <button class="btn btn-secondary btn-small" onclick="editWidget(${tempId}, this)">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deleteTempWidget(this)">Delete</button>
                </div>
            `;
            
            // Insert at the top
            widgetsList.insertBefore(newWidgetItem, widgetsList.firstChild);
            
            // Scroll to top and focus the new item
            newWidgetItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Populate form for new widget
            const form = document.getElementById('widget-form');
            const titleInput = document.getElementById('widget_title');
            const urlInput = document.getElementById('widget_url');
            const typeSelect = document.getElementById('widget_type');
            const disclosureInput = document.getElementById('widget_disclosure');
            const actionInput = document.getElementById('widget-action');
            const widgetIdInput = document.getElementById('widget-id');
            const modalTitle = document.getElementById('modal-title');
            
            if (form && titleInput && urlInput) {
                actionInput.value = 'add';
                widgetIdInput.value = tempId;
                titleInput.value = 'New Widget';
                urlInput.value = 'https://';
                typeSelect.value = 'custom';
                
                if (disclosureInput) {
                    disclosureInput.value = '';
                }
                
                if (modalTitle) {
                    modalTitle.textContent = 'Add New Widget';
                }
            }
            
            // Open modal for new widget item
            openWidgetModal();
        };
        
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
        
        window.handleWidgetFormSubmit = function(form) {
            const formData = new FormData(form);
            const action = formData.get('action');
            formData.append('action', action === 'update' ? 'update' : 'add');
            if (action === 'update') {
                formData.append('link_id', form.querySelector('#widget-id').value);
            }
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeWidgetModal();
                    showToast('Widget saved successfully!', 'success');
                    refreshPreview();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
            });
        };
        
        
        window.editWidget = function(widgetId, buttonElement) {
            const widgetItem = buttonElement ? buttonElement.closest('.widget-item') : document.querySelector(`[data-widget-id="${widgetId}"]`);
            
            if (!widgetItem) return;
            
            // Check if it's a new temporary widget (negative ID)
            if (parseInt(widgetId) < 0) {
                // New widget - populate with defaults
                document.getElementById('modal-title').textContent = 'Add New Widget';
                document.getElementById('widget-action').value = 'add';
                document.getElementById('widget-id').value = widgetId;
                document.getElementById('widget_type').value = 'custom';
                document.getElementById('widget_title').value = 'New Widget';
                document.getElementById('widget_url').value = 'https://';
                document.getElementById('widget_disclosure').value = '';
                
                openWidgetModal();
                return;
            }
            
            // Existing widget - fetch data
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('link_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.link) {
                    const link = data.link;
                    document.getElementById('modal-title').textContent = 'Edit Widget';
                    document.getElementById('widget-action').value = 'update';
                    document.getElementById('widget-id').value = link.id;
                    document.getElementById('widget_type').value = link.type || 'custom';
                    document.getElementById('widget_title').value = link.title || '';
                    document.getElementById('widget_url').value = link.url || '';
                    document.getElementById('widget_disclosure').value = link.disclosure_text || '';
                    
                    openWidgetModal();
                } else {
                    // Fallback: Get from page load
                    const titleEl = widgetItem.querySelector('.widget-title');
                    const urlEl = widgetItem.querySelector('.widget-url');
                    
                    if (titleEl && urlEl) {
                        document.getElementById('modal-title').textContent = 'Edit Widget';
                        document.getElementById('widget-action').value = 'update';
                        document.getElementById('widget-id').value = widgetId;
                        document.getElementById('widget_type').value = 'custom';
                        document.getElementById('widget_title').value = titleEl.textContent.trim();
                        document.getElementById('widget_url').value = urlEl.textContent.trim();
                        document.getElementById('widget_disclosure').value = '';
                        
                        openWidgetModal();
                    }
                }
            })
            .catch(() => {
                // Fallback approach
                const titleEl = widgetItem.querySelector('.widget-title');
                const urlEl = widgetItem.querySelector('.widget-url');
                
                if (titleEl && urlEl) {
                    document.getElementById('modal-title').textContent = 'Edit Widget';
                    document.getElementById('widget-action').value = 'update';
                    document.getElementById('widget-id').value = widgetId;
                    document.getElementById('widget_type').value = 'custom';
                    document.getElementById('widget_title').value = titleEl.textContent.trim();
                    document.getElementById('widget_url').value = urlEl.textContent.trim();
                    document.getElementById('widget_disclosure').value = '';
                    
                    openWidgetModal();
                }
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
            formData.append('link_id', widgetId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Widget deleted successfully!', 'success');
                    refreshPreview();
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
        const widgetForm = document.getElementById('widget-form');
        if (widgetForm) {
            widgetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleWidgetFormSubmit(this);
            });
        }
        
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
            const items = Array.from(widgetsList.querySelectorAll('.widget-item'));
            const linkOrders = items.map((item, index) => ({
                link_id: parseInt(item.getAttribute('data-widget-id')),
                display_order: index + 1
            }));
            
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('link_orders', JSON.stringify(linkOrders));
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showMessage('Failed to save widget order', 'error');
                    setTimeout(() => location.reload(), 500);
                } else {
                    refreshPreview();
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
        
        // Handle page settings form
        const pageSettingsForm = document.getElementById('page-settings-form');
        if (pageSettingsForm) {
            pageSettingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_settings');
            
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
                    refreshPreview();
                } else {
                    showMessage(data.error || 'Failed to save settings', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
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
            const headingFont = document.getElementById('custom_heading_font').value;
            const bodyFont = document.getElementById('custom_body_font').value;
            
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
            
            previewHeading.style.fontFamily = `'${headingFont}', sans-serif`;
            previewBody.style.fontFamily = `'${bodyFont}', sans-serif`;
        }
        
        // Handle theme change
        function handleThemeChange() {
            const themeId = document.getElementById('theme_id').value;
            if (themeId) {
                // Load theme data via AJAX
                fetch('/api/themes.php?id=' + themeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.theme) {
                            const colors = JSON.parse(data.theme.colors);
                            const fonts = JSON.parse(data.theme.fonts);
                            
                            // Apply theme colors
                            if (colors.primary) {
                                updateColorSwatch('primary', colors.primary);
                                document.getElementById('custom_primary_color').value = colors.primary;
                                document.getElementById('custom_primary_color_hex').value = colors.primary;
                            }
                            if (colors.secondary) {
                                updateColorSwatch('secondary', colors.secondary);
                                document.getElementById('custom_secondary_color').value = colors.secondary;
                                document.getElementById('custom_secondary_color_hex').value = colors.secondary;
                            }
                            if (colors.accent) {
                                updateColorSwatch('accent', colors.accent);
                                document.getElementById('custom_accent_color').value = colors.accent;
                                document.getElementById('custom_accent_color_hex').value = colors.accent;
                            }
                            
                            // Apply theme fonts
                            if (fonts.heading) {
                                document.getElementById('custom_heading_font').value = fonts.heading;
                                updateFontPreview();
                            }
                            if (fonts.body) {
                                document.getElementById('custom_body_font').value = fonts.body;
                                updateFontPreview();
                            }
                        }
                    })
                    .catch(() => {
                        console.error('Failed to load theme');
                    });
            }
        }
        
        // Update font preview on font change
        document.getElementById('custom_heading_font').addEventListener('change', updateFontPreview);
        document.getElementById('custom_body_font').addEventListener('change', updateFontPreview);
        
        // Load fonts on page load
        updateFontPreview();
        
        // Handle appearance form
        const appearanceForm = document.getElementById('appearance-form');
        if (appearanceForm) {
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
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Appearance updated successfully!', 'success');
                    refreshPreview();
                } else {
                    showMessage(data.error || 'Failed to update appearance', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
            });
        }
        
        // Handle email settings form
        const emailForm = document.getElementById('email-form');
        if (emailForm) {
            emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_email_settings');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Email settings saved successfully!', 'success');
                    refreshPreview();
                } else {
                    showMessage(data.error || 'Failed to save email settings', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
            });
        }
        
        // Handle RSS form
        const rssForm = document.getElementById('rss-form');
        if (rssForm) {
            rssForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'import_rss');
            
            showMessage('Importing RSS feed...', 'info');
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'RSS feed imported successfully!', 'success');
                    refreshPreview();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to import RSS feed', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
            });
        }
        
        // Image upload functionality
        function uploadImage(type, context = 'appearance') {
            const inputId = type === 'profile' 
                ? (context === 'settings' ? 'profile-image-input-settings' : 'profile-image-input')
                : 'background-image-input';
            const previewId = type === 'profile' 
                ? (context === 'settings' ? 'profile-preview-settings' : 'profile-preview')
                : 'background-preview';
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
                        img.alt = type === 'profile' ? 'Profile' : 'Background';
                        img.style.cssText = type === 'profile' 
                            ? 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;'
                            : 'width: 200px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;';
                        preview.parentNode.replaceChild(img, preview);
                        console.log('Preview image replaced successfully');
                    } else {
                        console.error('Preview element or parent not found');
                        console.error('Preview:', preview);
                        console.error('Preview parent:', preview ? preview.parentNode : 'null');
                    }
                    
                    // Show remove button if not visible
                    let uploadBtnContainer;
                    if (context === 'settings') {
                        // In settings context, find the button container
                        const buttonContainer = input.closest('div').querySelector('div[style*="display: flex"]');
                        uploadBtnContainer = buttonContainer;
                        const uploadBtn = buttonContainer ? buttonContainer.querySelector('.btn-secondary') : null;
                        
                        if (uploadBtn && !uploadBtn.nextElementSibling || (uploadBtn.nextElementSibling && !uploadBtn.nextElementSibling.classList.contains('btn-danger'))) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-danger btn-small';
                            removeBtn.textContent = 'Remove';
                            removeBtn.onclick = () => removeImage(type, context);
                            
                            // Insert after the upload button
                            uploadBtn.parentNode.insertBefore(removeBtn, uploadBtn.nextSibling);
                        }
                    } else {
                        const uploadBtn = input.nextElementSibling;
                        if (uploadBtn && (!uploadBtn.nextElementSibling || !uploadBtn.nextElementSibling.classList.contains('btn-danger'))) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-danger btn-small';
                            removeBtn.textContent = 'Remove';
                            removeBtn.onclick = () => removeImage(type, context);
                            uploadBtn.parentNode.insertBefore(removeBtn, uploadBtn.nextSibling);
                        }
                    }
                    
                    // Clear input
                    input.value = '';
                    
                    showToast(data.message || 'Image uploaded successfully!', 'success');
                    refreshPreview();
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
            if (!confirm(`Are you sure you want to remove the ${type} image?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'remove_image');
            formData.append('type', type);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/page.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const previewId = type === 'profile' 
                        ? (context === 'settings' ? 'profile-preview-settings' : 'profile-preview')
                        : 'background-preview';
                    const preview = document.getElementById(previewId);
                    
                    if (preview) {
                        // Replace img with placeholder div
                        const placeholder = document.createElement('div');
                        placeholder.id = previewId;
                        placeholder.style.cssText = type === 'profile'
                            ? 'width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;'
                            : 'width: 200px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999;';
                        placeholder.textContent = 'No image';
                        preview.parentNode.replaceChild(placeholder, preview);
                    }
                    
                    // Remove remove button
                    const inputId = type === 'profile' 
                        ? (context === 'settings' ? 'profile-image-input-settings' : 'profile-image-input')
                        : 'background-image-input';
                    const input = document.getElementById(inputId);
                    if (input) {
                        const uploadBtn = input.nextElementSibling;
                        if (uploadBtn && uploadBtn.nextElementSibling && uploadBtn.nextElementSibling.textContent === 'Remove') {
                            uploadBtn.nextElementSibling.remove();
                        }
                    }
                    
                    showMessage(`${type} image removed successfully`, 'success');
                    refreshPreview();
                } else {
                    showMessage(data.error || 'Failed to remove image', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
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
                    refreshPreview();
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
                    refreshPreview();
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

