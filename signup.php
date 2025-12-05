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
        $username = sanitizeInput($_POST['username'] ?? '');
        
        // Validate inputs
        if (empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $user = new User();
            $result = $user->create($email, $password, $username);
            
            if ($result['success']) {
                // Send verification email
                if (sendVerificationEmail($email, $result['verification_token'])) {
                    if (!empty($username)) {
                        $success = 'Account created! Your page is ready at poda.bio/' . h($username) . '. Please check your email to verify your account.';
                    } else {
                        $success = 'Account created! Please check your email to verify your account.';
                    }
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
    <link href="https://fonts.googleapis.com/css2?family=Zalando+Sans+Expanded:ital,wght@0,200..900;1,200..900&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
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
                    <label for="username">Username <span class="optional">(Optional)</span></label>
                    <div class="username-input-wrapper">
                        <span class="username-prefix">poda.bio/</span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo h($_POST['username'] ?? $_GET['username'] ?? ''); ?>" 
                            placeholder="yourname" 
                            pattern="[a-zA-Z0-9_-]{3,30}"
                            minlength="3"
                            maxlength="30"
                            autocomplete="username"
                        >
                        <span class="username-status" id="username-status"></span>
                    </div>
                    <small>Choose a unique username for your page URL. You can set this later if you skip it now.</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Create a password" autocomplete="new-password">
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
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password" autocomplete="new-password">
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
            // Password toggle functionality
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
            
            // Username availability checking
            const usernameInput = document.getElementById('username');
            const usernameWrapper = usernameInput?.closest('.username-input-wrapper');
            const statusIndicator = document.getElementById('username-status');
            
            if (usernameInput && usernameWrapper && statusIndicator) {
                let checkTimeout = null;
                let isChecking = false;
                let isAvailable = false;
                
                function sanitizeUsername(value) {
                    return value.toLowerCase().replace(/[^a-z0-9_-]/g, '').substring(0, 30);
                }
                
                async function checkUsernameAvailability(username) {
                    if (isChecking) return;
                    
                    // Validate format first
                    const usernameRegex = /^[a-zA-Z0-9_-]{3,30}$/;
                    if (!usernameRegex.test(username)) {
                        if (username.length > 0) {
                            usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                            usernameWrapper.classList.add('unavailable');
                            statusIndicator.innerHTML = '<span class="icon-close">✕</span>';
                            isAvailable = false;
                        } else {
                            usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                            statusIndicator.innerHTML = '';
                            isAvailable = false;
                        }
                        return;
                    }
                    
                    isChecking = true;
                    usernameWrapper.classList.remove('available', 'unavailable');
                    usernameWrapper.classList.add('checking');
                    statusIndicator.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⟳</span>';
                    
                    try {
                        const response = await fetch(`/api/check-username.php?username=${encodeURIComponent(username)}`);
                        const data = await response.json();
                        
                        if (data.success && data.available) {
                            usernameWrapper.classList.remove('checking', 'unavailable');
                            usernameWrapper.classList.add('available');
                            statusIndicator.innerHTML = '<span class="icon-check">✓</span>';
                            isAvailable = true;
                        } else {
                            usernameWrapper.classList.remove('checking', 'available');
                            usernameWrapper.classList.add('unavailable');
                            statusIndicator.innerHTML = '<span class="icon-close">✕</span>';
                            isAvailable = false;
                        }
                    } catch (error) {
                        console.error('Error checking username:', error);
                        usernameWrapper.classList.remove('checking');
                        statusIndicator.innerHTML = '';
                        isAvailable = false;
                    } finally {
                        isChecking = false;
                    }
                }
                
                // Auto-sanitize username input
                usernameInput.addEventListener('input', (e) => {
                    const sanitized = sanitizeUsername(e.target.value);
                    if (sanitized !== e.target.value) {
                        e.target.value = sanitized;
                    }
                    
                    const username = sanitized.trim();
                    
                    // Clear previous timeout
                    if (checkTimeout) {
                        clearTimeout(checkTimeout);
                    }
                    
                    // Clear status if empty
                    if (!username) {
                        usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                        statusIndicator.innerHTML = '';
                        isAvailable = false;
                        return;
                    }
                    
                    // Check availability after 500ms delay
                    checkTimeout = setTimeout(() => {
                        checkUsernameAvailability(username);
                    }, 500);
                });
            }
            
            // Add spin animation for loading indicator
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        })();
    </script>
</body>
</html>
