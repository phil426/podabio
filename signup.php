<?php
/**
 * Signup Page
 * PodaBio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/config/oauth.php';

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
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $user = new User();
            $result = $user->create($email, $password);
            
            if ($result['success']) {
                // Send verification email
                if (sendVerificationEmail($email, $result['verification_token'])) {
                    $success = 'Account created! Please check your email to verify your account.';
                } else {
                    // Account created but email failed - still show success but note email issue
                    $success = 'Account created! However, there was an issue sending the verification email. Please contact support or try logging in.';
                    error_log("Failed to send verification email to: " . $email);
                }
            } else {
                $error = $result['error'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$googleAuthUrl = getGoogleAuthUrl();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Nunito Sans Expanded';
            src: url('https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@100,800&display=swap');
            font-stretch: expanded;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/auth.css?v=<?php echo filemtime(__DIR__ . '/css/auth.css'); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <a href="/" class="auth-logo-link" title="Back to Home">
                    <img src="/assets/images/logo/marketing_logo.png" alt="<?php echo h(APP_NAME); ?>" class="auth-logo-image">
                </a>
                <h1>Sign Up</h1>
                <p class="auth-subtitle">Create your account on <?php echo h(APP_NAME); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="you@example.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Create a password">
                        <button type="button" class="password-toggle" data-target="password" aria-pressed="false">
                            <span class="sr-only">Show password</span>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small>Must be at least 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                        <button type="button" class="password-toggle" data-target="confirm_password" aria-pressed="false">
                            <span class="sr-only">Show password</span>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <span>Sign Up</span>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>OR</span>
            </div>
            
            <a href="<?php echo h($googleAuthUrl); ?>" class="btn btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign up with Google
            </a>
            
            <p class="auth-footer">
                Already have an account? <a href="/login.php">Log in</a>
            </p>
        </div>
    </div>
    <script>
        (function() {
            const toggles = document.querySelectorAll('.password-toggle');
            toggles.forEach((btn) => {
                const targetId = btn.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;

                btn.addEventListener('click', () => {
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');

                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', !shouldShow);
                        icon.classList.toggle('fa-eye-slash', shouldShow);
                    }

                    const srText = btn.querySelector('.sr-only');
                    if (srText) {
                        srText.textContent = shouldShow ? 'Hide password' : 'Show password';
                    }
                });
            });
        })();
    </script>
</body>
</html>
