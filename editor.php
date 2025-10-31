<?php
/**
 * Page Editor
 * Podn.Bio - Edit page content, links, and settings
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
if (isset($_GET['linked']) && $_GET['linked'] == '1' && empty($success)) {
    $success = 'Google account linked successfully!';
}

// Get user's page
$pageClass = new Page();
$page = $pageClass->getByUserId($userId);

// If no page exists, we'll show the page creation form instead of redirecting
$pageId = null;
$links = [];
$podcastDirectories = [];

if ($page) {
    $pageId = $page['id'];
    $links = $pageClass->getAllLinks($pageId);
    $podcastDirectories = $pageClass->getPodcastDirectories($pageId);
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
    <link rel="stylesheet" href="/assets/css/style.css">
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
            width: 250px;
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
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0066ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border-left: 3px solid transparent;
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
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .user-profile:hover {
            background: #f9fafb;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0066ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .user-info {
            flex: 1;
            font-size: 0.875rem;
        }
        
        .user-name {
            font-weight: 600;
            color: #111827;
        }
        
        .user-email {
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        /* Center Editor Area */
        .editor-main {
            flex: 1;
            margin-left: 250px;
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
        .links-list {
            list-style: none;
            padding: 0;
        }
        .link-item {
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
        .link-item:hover {
            background: #f0f0f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .link-item.dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        .link-item.drag-over {
            border-top: 3px solid #0066ff;
        }
        .link-item::before {
            content: '☰';
            margin-right: 10px;
            color: #999;
            font-size: 18px;
        }
        .link-info {
            flex: 1;
        }
        .link-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .link-url {
            color: #666;
            font-size: 14px;
            word-break: break-all;
        }
        .link-actions {
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
                    <a href="javascript:void(0)" class="nav-item active" onclick="showSection('links', this)">
                        <i class="fas fa-link"></i>
                        <span>Links</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('podcast-directories', this)">
                        <i class="fas fa-list"></i>
                        <span>Podcast Directories</span>
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
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
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
                    <!-- Links Tab -->
                    <div id="tab-links" class="tab-content active">
            <h2>Manage Links</h2>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="showAddLinkForm()">Add New Link</button>
            </div>
            
            <ul id="links-list" class="links-list">
                <?php if (empty($links)): ?>
                    <li>No links yet. Click "Add New Link" to get started.</li>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <li class="link-item" data-link-id="<?php echo $link['id']; ?>">
                            <div class="link-info">
                                <div class="link-title"><?php echo h($link['title']); ?></div>
                                <div class="link-url"><?php echo h($link['url']); ?></div>
                            </div>
                            <div class="link-actions">
                                <button class="btn btn-secondary btn-small" onclick="editLink(<?php echo $link['id']; ?>)">Edit</button>
                                <button class="btn btn-danger btn-small" onclick="deleteLink(<?php echo $link['id']; ?>)">Delete</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Podcast Directories Tab -->
        <div id="tab-podcast-directories" class="tab-content">
            <h2>Podcast Directory Links</h2>
            <p style="margin-bottom: 20px; color: #666;">Add links to your podcast on popular directories like Apple Podcasts, Spotify, and more.</p>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="showAddDirectoryForm()">Add Directory Link</button>
            </div>
            
            <ul id="directories-list" class="links-list">
                <?php if (empty($podcastDirectories)): ?>
                    <li>No podcast directory links yet. Click "Add Directory Link" to get started.</li>
                <?php else: ?>
                    <?php foreach ($podcastDirectories as $directory): ?>
                        <li class="link-item" data-directory-id="<?php echo $directory['id']; ?>">
                            <div class="link-info">
                                <div class="link-title"><?php echo h($directory['platform_name']); ?></div>
                                <div class="link-url"><?php echo h($directory['url']); ?></div>
                            </div>
                            <div class="link-actions">
                                <button class="btn btn-danger btn-small" onclick="deleteDirectory(<?php echo $directory['id']; ?>)">Delete</button>
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
                    <label for="podcast_name">Podcast Name</label>
                    <input type="text" id="podcast_name" name="podcast_name" value="<?php echo h($page['podcast_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="podcast_description">Description</label>
                    <textarea id="podcast_description" name="podcast_description" rows="4"><?php echo h($page['podcast_description'] ?? ''); ?></textarea>
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
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
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
            <h2>Add Podcast Directory Link</h2>
            <form id="directory-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="directory_platform">Platform</label>
                    <select id="directory_platform" name="platform_name" required>
                        <option value="">Select Platform</option>
                        <?php
                        $platforms = [
                            'apple_podcasts' => 'Apple Podcasts',
                            'spotify' => 'Spotify',
                            'youtube_music' => 'YouTube Music',
                            'amazon_music' => 'Amazon Music',
                            'audible' => 'Audible',
                            'tunein' => 'TuneIn Radio',
                            'castbox' => 'Castbox',
                            'good_pods' => 'Good Pods',
                            'iheart_radio' => 'I Heart Radio',
                            'overcast' => 'Overcast',
                            'pocket_casts' => 'Pocket Casts'
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
                    <button type="submit" class="btn btn-primary">Add Directory</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add/Edit Link Modal (simple form) -->
    <div id="link-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:30px; border-radius:8px; max-width:500px; width:90%;">
            <h2 id="modal-title">Add Link</h2>
            <form id="link-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                <input type="hidden" name="action" id="link-action" value="add">
                <input type="hidden" name="link_id" id="link-id">
                
                <div class="form-group">
                    <label for="link_type">Link Type</label>
                    <select id="link_type" name="type" required>
                        <option value="custom">Custom Link</option>
                        <option value="social">Social Media</option>
                        <option value="affiliate">Affiliate Link</option>
                        <option value="amazon_affiliate">Amazon Affiliate</option>
                        <option value="sponsor">Sponsor Link</option>
                        <option value="email_subscribe">Email Subscribe</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="link_title">Title</label>
                    <input type="text" id="link_title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="link_url">URL</label>
                    <input type="url" id="link_url" name="url" required>
                </div>
                
                <div class="form-group">
                    <label for="link_disclosure">Disclosure Text (for affiliate/sponsor links)</label>
                    <textarea id="link_disclosure" name="disclosure_text" rows="2"></textarea>
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeLinkModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo h($csrfToken); ?>';
        
        function showSection(sectionName, navElement) {
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
        }
        
        // Alias for backward compatibility
        function showTab(tabName, evt) {
            const navElement = evt ? evt.currentTarget : null;
            showSection(tabName, navElement);
        }
        
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
        
        function showAddLinkForm() {
            document.getElementById('modal-title').textContent = 'Add Link';
            document.getElementById('link-action').value = 'add';
            document.getElementById('link-form').reset();
            document.getElementById('link-id').value = '';
            document.getElementById('link-modal').style.display = 'block';
        }
        
        function closeLinkModal() {
            document.getElementById('link-modal').style.display = 'none';
        }
        
        function editLink(linkId) {
            // Fetch link data
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('link_id', linkId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.link) {
                    const link = data.link;
                    document.getElementById('modal-title').textContent = 'Edit Link';
                    document.getElementById('link-action').value = 'update';
                    document.getElementById('link-id').value = link.id;
                    document.getElementById('link_type').value = link.type;
                    document.getElementById('link_title').value = link.title;
                    document.getElementById('link_url').value = link.url;
                    document.getElementById('link_disclosure').value = link.disclosure_text || '';
                    document.getElementById('link-modal').style.display = 'block';
                } else {
                    // Fallback: Get from page load
                    const linkElement = document.querySelector(`[data-link-id="${linkId}"]`);
                    if (linkElement) {
                        const title = linkElement.querySelector('.link-title').textContent;
                        const url = linkElement.querySelector('.link-url').textContent;
                        document.getElementById('modal-title').textContent = 'Edit Link';
                        document.getElementById('link-action').value = 'update';
                        document.getElementById('link-id').value = linkId;
                        document.getElementById('link_title').value = title.trim();
                        document.getElementById('link_url').value = url.trim();
                        document.getElementById('link-modal').style.display = 'block';
                    }
                }
            })
            .catch(() => {
                // Fallback approach
                alert('Could not load link data. Please try refreshing the page.');
            });
        }
        
        function deleteLink(linkId) {
            if (!confirm('Are you sure you want to delete this link?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('link_id', linkId);
            formData.append('csrf_token', csrfToken);
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Success!', 'success');
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
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'alert alert-' + (type === 'error' ? 'error' : 'success');
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        // Handle link form submission
        const linkForm = document.getElementById('link-form');
        if (linkForm) {
            linkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = formData.get('action');
            formData.append('action', action === 'update' ? 'update' : 'add');
            if (action === 'update') {
                formData.append('link_id', document.getElementById('link-id').value);
            }
            
            fetch('/api/links.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeLinkModal();
                    showMessage('Link saved successfully!', 'success');
                    refreshPreview();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred', 'error');
            });
            });
        }
        
        // Close modal on outside click
        const linkModal = document.getElementById('link-modal');
        if (linkModal) {
            linkModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLinkModal();
                }
            });
        }
        
        // Drag and drop functionality
        let draggedElement = null;
        const linksList = document.getElementById('links-list');
        
        if (linksList) {
            // Make links sortable
            Array.from(linksList.children).forEach(item => {
                if (item.classList.contains('link-item')) {
                    item.setAttribute('draggable', 'true');
                    
                    item.addEventListener('dragstart', function(e) {
                        draggedElement = this;
                        this.classList.add('dragging');
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    
                    item.addEventListener('dragend', function() {
                        this.classList.remove('dragging');
                        document.querySelectorAll('.link-item').forEach(el => {
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
                            const allItems = Array.from(linksList.querySelectorAll('.link-item'));
                            const draggedIndex = allItems.indexOf(draggedElement);
                            const targetIndex = allItems.indexOf(this);
                            
                            if (draggedIndex < targetIndex) {
                                linksList.insertBefore(draggedElement, this.nextSibling);
                            } else {
                                linksList.insertBefore(draggedElement, this);
                            }
                            
                            // Save new order
                            saveLinkOrder();
                        }
                        
                        return false;
                    });
                }
            });
        }
        
        function saveLinkOrder() {
            const items = Array.from(linksList.querySelectorAll('.link-item'));
            const linkOrders = items.map((item, index) => ({
                link_id: parseInt(item.getAttribute('data-link-id')),
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
                    showMessage('Failed to save link order', 'error');
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
        function uploadImage(type) {
            const inputId = type === 'profile' ? 'profile-image-input' : 'background-image-input';
            const input = document.getElementById(inputId);
            const file = input.files[0];
            
            if (!file) {
                showMessage('Please select an image file', 'error');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('File size must be less than 5MB', 'error');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Invalid file type. Please use JPEG, PNG, GIF, or WebP', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', type);
            formData.append('csrf_token', csrfToken);
            
            showMessage('Uploading image...', 'info');
            
            fetch('/api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update preview
                    const previewId = type === 'profile' ? 'profile-preview' : 'background-preview';
                    const preview = document.getElementById(previewId);
                    
                    if (preview.tagName === 'IMG') {
                        preview.src = data.url;
                    } else {
                        // Replace div with img
                        const img = document.createElement('img');
                        img.id = previewId;
                        img.src = data.url;
                        img.alt = type === 'profile' ? 'Profile' : 'Background';
                        img.style.cssText = type === 'profile' 
                            ? 'width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;'
                            : 'width: 200px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;';
                        preview.parentNode.replaceChild(img, preview);
                    }
                    
                    // Show remove button if not visible
                    const uploadBtn = input.nextElementSibling;
                    if (uploadBtn && !uploadBtn.nextElementSibling || uploadBtn.nextElementSibling.textContent !== 'Remove') {
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-danger btn-small';
                        removeBtn.textContent = 'Remove';
                        removeBtn.onclick = () => removeImage(type);
                        uploadBtn.parentNode.insertBefore(removeBtn, uploadBtn.nextSibling);
                    }
                    
                    // Clear input
                    input.value = '';
                    
                    showMessage(data.message || 'Image uploaded successfully!', 'success');
                } else {
                    showMessage(data.error || 'Failed to upload image', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred while uploading', 'error');
            });
        }
        
        function removeImage(type) {
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
                    const previewId = type === 'profile' ? 'profile-preview' : 'background-preview';
                    const preview = document.getElementById(previewId);
                    
                    // Replace img with placeholder div
                    const placeholder = document.createElement('div');
                    placeholder.id = previewId;
                    placeholder.style.cssText = type === 'profile'
                        ? 'width: 100px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999;'
                        : 'width: 200px; height: 100px; background: #f0f0f0; border-radius: 8px; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; color: #999;';
                    placeholder.textContent = 'No image';
                    preview.parentNode.replaceChild(placeholder, preview);
                    
                    // Remove remove button
                    const inputId = type === 'profile' ? 'profile-image-input' : 'background-image-input';
                    const uploadBtn = document.getElementById(inputId).nextElementSibling;
                    if (uploadBtn && uploadBtn.nextElementSibling && uploadBtn.nextElementSibling.textContent === 'Remove') {
                        uploadBtn.nextElementSibling.remove();
                    }
                    
                    showMessage(`${type} image removed successfully`, 'success');
                } else {
                    showMessage(data.error || 'Failed to remove image', 'error');
                }
            })
            .catch(() => {
                showMessage('An error occurred', 'error');
            });
        }
        
        // Podcast Directory Management
        function showAddDirectoryForm() {
            document.getElementById('directory-form').reset();
            document.getElementById('directory-modal').style.display = 'block';
        }
        
        function closeDirectoryModal() {
            document.getElementById('directory-modal').style.display = 'none';
        }
        
        function deleteDirectory(directoryId) {
            if (!confirm('Are you sure you want to delete this directory link?')) {
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
                    showMessage('Directory deleted successfully!', 'success');
                    refreshPreview();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to delete directory', 'error');
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
                    showMessage('Directory added successfully!', 'success');
                    refreshPreview();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to add directory', 'error');
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

