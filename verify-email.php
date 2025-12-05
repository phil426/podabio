<?php
/**
 * Email Verification Page
 * PodaBio
 * 
 * After successful verification, auto-logs in the user and redirects to dashboard.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Page.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$autoRedirect = false;
$redirectUrl = '/admin/userdashboard.php';

if (empty($token)) {
    $error = 'Invalid verification token';
} else {
    $user = new User();
    $result = $user->verifyEmail($token);
    
    if ($result['success']) {
        // Auto-login the user
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_email'] = $result['email'];
        $_SESSION['admin_panel'] = 'lefty';
        regenerateSession();
        
        // Check if user has a page
        $pageClass = new Page();
        $userPage = $pageClass->getByUserId($result['user_id']);
        
        if ($userPage) {
            // User has a page - go to dashboard
            $success = 'Email verified! Redirecting to your dashboard...';
            $redirectUrl = '/admin/userdashboard.php';
        } else {
            // No page yet - go to account profile to create one
            $success = 'Email verified! Let\'s set up your page...';
            $redirectUrl = '/admin/userdashboard.php#/account/profile';
        }
        
        $autoRedirect = true;
    } else {
        $error = $result['error'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zalando+Sans+Expanded:ital,wght@0,200..900;1,200..900&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css?v=<?php echo filemtime(__DIR__ . '/css/auth.css'); ?>">
    <?php if ($autoRedirect): ?>
    <meta http-equiv="refresh" content="2;url=<?php echo h($redirectUrl); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <a href="/" class="auth-logo-link" title="Back to Home">
                    <img src="/assets/images/logo/marketing_logo.png" alt="<?php echo h(APP_NAME); ?>" class="auth-logo-image">
                </a>
                <h1>Email Verification</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
                <p class="auth-footer">
                    <a href="/signup.php">Sign up again</a> | <a href="/login.php">Log in</a>
                </p>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
                <?php if ($autoRedirect): ?>
                <p class="auth-footer">
                    <span class="redirect-note">Redirecting automatically...</span>
                    <br><br>
                    <a href="<?php echo h($redirectUrl); ?>" class="btn btn-primary">Go to Dashboard Now</a>
                </p>
                <?php else: ?>
                <p class="auth-footer">
                    <a href="/login.php" class="btn btn-primary">Log In Now</a>
                </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($autoRedirect): ?>
    <script>
        // Fallback redirect in case meta refresh doesn't work
        setTimeout(function() {
            window.location.href = '<?php echo h($redirectUrl); ?>';
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>

