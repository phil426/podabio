<?php
/**
 * Email Verification Page
 * Podn.Bio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification token';
} else {
    $user = new User();
    $result = $user->verifyEmail($token);
    
    if ($result['success']) {
        $success = 'Email verified successfully! You can now log in.';
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
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Verify Email</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
                <p class="auth-footer">
                    <a href="/signup.php">Sign up again</a> | <a href="/login.php">Log in</a>
                </p>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
                <p class="auth-footer">
                    <a href="/login.php" class="btn btn-primary">Log In Now</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

