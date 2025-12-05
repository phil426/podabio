<?php
/**
 * Forgot Password Page
 * PodaBio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/admin/userdashboard.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address';
        } else {
            $user = new User();
            $result = $user->generateResetToken($email);
            
            // Send reset email if token was generated (user exists)
            if ($result['success'] && !empty($result['token'])) {
                sendPasswordResetEmail($email, $result['token']);
            }
            
            // Always show success message for security (don't reveal if email exists)
            $success = 'If an account exists with that email, a password reset link has been sent.';
        }
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zalando+Sans+Expanded:ital,wght@0,200..900;1,200..900&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth.css?v=<?php echo filemtime(__DIR__ . '/css/auth.css'); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <a href="/" class="auth-logo-link" title="Back to Home">
                    <img src="/assets/images/logo/marketing_logo.png" alt="<?php echo h(APP_NAME); ?>" class="auth-logo-image">
                </a>
                <h1>Reset Password</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php else: ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="you@example.com" autofocus autocomplete="email">
                    <small>Enter your email address and we'll send you a password reset link.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            <?php endif; ?>
            
            <p class="auth-footer">
                <a href="/login.php">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>

