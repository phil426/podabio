<?php
/**
 * Verify Password for Google Account Linking
 * Podn.Bio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Page.php';
require_once __DIR__ . '/config/oauth.php';

$error = '';
$success = false;

// Check if Google account data is in session (from OAuth callback)
$googleData = $_SESSION['pending_google_link'] ?? null;

if (!$googleData) {
    // No pending link, redirect to login
    redirect('/login.php?error=' . urlencode('No Google account linking request found. Please try again.'));
}

$googleId = $googleData['google_id'] ?? '';
$email = $googleData['email'] ?? '';
$userId = $googleData['user_id'] ?? null;

if (empty($googleId) || empty($email) || empty($userId)) {
    unset($_SESSION['pending_google_link']);
    redirect('/login.php?error=' . urlencode('Invalid Google account linking data. Please try again.'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $error = 'Please enter your password';
        } else {
            $user = new User();
            
            // Verify password
            $verifyResult = $user->verifyPasswordForLinking($userId, $password);
            
            if ($verifyResult['success']) {
                // Link Google account
                $linkResult = $user->linkGoogleAccount($userId, $googleId, $email);
                
                if ($linkResult['success']) {
                    // Clear pending link data
                    unset($_SESSION['pending_google_link']);
                    
                    // Log user in
                    $userData = $user->getById($userId);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_email'] = $userData['email'];
                    regenerateSession();
                    
                    // Check if user has a page - if yes, go to editor, otherwise dashboard
                    $pageClass = new Page();
                    $userPage = $pageClass->getByUserId($userId);
                    $successMsg = 'Google account linked successfully!';
                    $redirectTo = $userPage ? '/editor.php?tab=account&success=' . urlencode($successMsg) : '/dashboard.php?success=' . urlencode($successMsg);
                    redirect($redirectTo);
                } else {
                    $error = $linkResult['error'];
                }
            } else {
                $error = $verifyResult['error'];
            }
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
    <title>Verify Password - <?php echo h(APP_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Verify Password</h1>
            
            <p class="auth-info">
                An account with email <strong><?php echo h($email); ?></strong> already exists. 
                Please enter your password to link your Google account.
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary">Verify and Link Account</button>
            </form>
            
            <div class="auth-footer">
                <a href="/login.php">Cancel and return to login</a>
            </div>
        </div>
    </div>
</body>
</html>

