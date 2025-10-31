<?php
/**
 * User Dashboard
 * Podn.Bio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Page.php';
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
                    // Redirect to refresh page and clear any cached user data
                    redirect('/dashboard.php?success=' . urlencode($success));
                    exit;
                } else {
                    $error = $result['error'];
                }
                break;
                
            case 'remove_password':
                $result = $userObj->removePassword($userId);
                if ($result['success']) {
                    $success = 'Password removed successfully. You can now only log in with Google.';
                    // Redirect to refresh page and clear any cached user data
                    redirect('/dashboard.php?success=' . urlencode($success));
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
                        redirect('/editor.php');
                        exit;
                    } else {
                        $error = $result['error'];
                    }
                }
                break;
                
            default:
                $error = 'Invalid action';
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
if (isset($_GET['linked'])) {
    $success = 'Google account linked successfully!';
}
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

$csrfToken = generateCSRFToken();
$googleLinkUrl = getGoogleAuthUrl('link');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="container">
                <h1><?php echo h(APP_NAME); ?> Dashboard</h1>
                <nav>
                    <a href="/logout.php">Logout</a>
                </nav>
            </div>
        </header>
        
        <main class="dashboard-main">
            <div class="container">
                <h2>Welcome, <?php echo h($user['email']); ?>!</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo h($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo h($error); ?></div>
                <?php endif; ?>
                
                <section class="dashboard-section">
                    <h3>Account Settings</h3>
                    
                    <div class="account-status">
                        <h4>Login Methods</h4>
                        <p>Your account can be accessed using:</p>
                        <ul class="login-methods">
                            <?php if ($hasPassword): ?>
                                <li>
                                    <strong>Email/Password</strong>
                                    <?php if ($hasGoogle): ?>
                                        <form method="POST" action="?action=remove_password" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove your password? You will only be able to log in with Google.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                            <button type="submit" class="btn btn-small btn-secondary">Remove Password</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($hasGoogle): ?>
                                <li>
                                    <strong>Google OAuth</strong>
                                    <?php if ($hasPassword): ?>
                                        <form method="POST" action="?action=unlink_google" style="display:inline;" onsubmit="return confirm('Are you sure you want to unlink your Google account? You will only be able to log in with email/password.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                            <button type="submit" class="btn btn-small btn-secondary">Unlink Google</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php else: ?>
                                <li>
                                    <strong>Google OAuth</strong> - Not linked
                                    <a href="<?php echo h($googleLinkUrl); ?>" class="btn btn-small btn-primary">Link Google Account</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <?php if (empty($methods)): ?>
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> You must have at least one login method. Please link a Google account or set a password.
                            </div>
                        <?php elseif (count($methods) === 1): ?>
                            <div class="alert alert-info">
                                <strong>Info:</strong> You currently have only one login method. Consider adding another for account recovery.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Page Management -->
                <section class="dashboard-section">
                    <h3>Your Page</h3>
                    <?php
                    require_once __DIR__ . '/classes/Page.php';
                    $pageClass = new Page();
                    $userPage = $pageClass->getByUserId($userId);
                    ?>
                    <?php if ($userPage): ?>
                        <div class="page-info">
                            <p><strong>Username:</strong> <?php echo h($userPage['username']); ?></p>
                            <p><strong>Page URL:</strong> <a href="/<?php echo h($userPage['username']); ?>" target="_blank"><?php echo h(APP_URL); ?>/<?php echo h($userPage['username']); ?></a></p>
                            <div style="margin-top: 15px;">
                                <a href="/editor.php" class="btn btn-primary">Edit Page</a>
                                <a href="/<?php echo h($userPage['username']); ?>" target="_blank" class="btn btn-secondary">View Page</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="page-info">
                            <p>You haven't created a page yet.</p>
                            <form method="POST" action="/dashboard.php" id="create-page-form" style="margin-top: 15px;">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                <input type="hidden" name="action" value="create_page">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label for="username">Choose a username for your page:</label>
                                    <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_-]{3,30}" 
                                           placeholder="your-username" style="padding: 8px; width: 200px; border: 1px solid #ddd; border-radius: 4px;">
                                    <small style="display: block; margin-top: 5px; color: #666;">3-30 characters, letters, numbers, underscores, and hyphens only</small>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Page</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </section>
                
                <section class="dashboard-section">
                    <h3>Quick Actions</h3>
                    <div style="margin-top: 1rem;">
                        <?php if ($userPage): ?>
                            <a href="/editor.php" class="btn btn-primary">Go to Editor</a>
                            <a href="/editor.php?tab=account" class="btn btn-secondary">Account Settings</a>
                        <?php else: ?>
                            <p>Create a page to access the editor.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Subscription Management -->
                <section class="dashboard-section">
                    <h3>Subscription</h3>
                    <?php
                    $subscription = new Subscription();
                    $activeSubscription = $subscription->getActive($userId);
                    ?>
                    <?php if ($activeSubscription): ?>
                        <div class="subscription-info">
                            <p><strong>Current Plan:</strong> <span style="text-transform: capitalize;"><?php echo h($activeSubscription['plan_type']); ?></span></p>
                            <?php if ($activeSubscription['expires_at']): ?>
                                <p><strong>Expires:</strong> <?php echo h(formatDate($activeSubscription['expires_at'], 'F j, Y')); ?></p>
                            <?php else: ?>
                                <p><strong>Status:</strong> Active (no expiration)</p>
                            <?php endif; ?>
                            <?php if ($activeSubscription['payment_method']): ?>
                                <p><strong>Payment Method:</strong> <span style="text-transform: capitalize;"><?php echo h($activeSubscription['payment_method']); ?></span></p>
                            <?php endif; ?>
                            
                            <?php if ($activeSubscription['plan_type'] === 'free'): ?>
                                <div style="margin-top: 15px;">
                                    <a href="/payment/checkout.php?plan=premium" class="btn btn-primary">Upgrade to Premium</a>
                                    <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                                </div>
                            <?php elseif ($activeSubscription['plan_type'] === 'premium'): ?>
                                <div style="margin-top: 15px;">
                                    <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                                    <button type="button" class="btn btn-secondary" onclick="alert('To cancel your subscription, please contact support.')">Cancel Subscription</button>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 15px;">
                                    <button type="button" class="btn btn-secondary" onclick="alert('To cancel your subscription, please contact support.')">Cancel Subscription</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="subscription-info">
                            <p>No active subscription. Start with a free plan!</p>
                            <div style="margin-top: 15px;">
                                <a href="/payment/checkout.php?plan=premium" class="btn btn-primary">Upgrade to Premium</a>
                                <a href="/payment/checkout.php?plan=pro" class="btn btn-primary">Upgrade to Pro</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

