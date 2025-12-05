<?php
/**
 * Login Page
 * PodaBio
 */

// Error handling - set before any includes
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Custom error handler to catch fatal errors
function handleFatalError() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        error_log('Login page FATAL ERROR: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>System Error</title></head>
        <body style="font-family: Arial, sans-serif; padding: 2rem; background: #1a1a1a; color: #fff;">
            <h1>System Error</h1>
            <p>An error occurred while loading the login page. Please try again later.</p>
            <p><a href="/" style="color: #00ff7f;">Return to Home</a></p>
        </body>
        </html>
        <?php
        exit;
    }
}
register_shutdown_function('handleFatalError');

// Load files one by one with error checking
if (!@include_once __DIR__ . '/config/constants.php') {
    error_log('Login: Failed to load constants.php');
    http_response_code(500);
    die('Configuration error. Please contact support.');
}

if (!@include_once __DIR__ . '/includes/session.php') {
    error_log('Login: Failed to load session.php');
    http_response_code(500);
    die('Session error. Please contact support.');
}

if (!@include_once __DIR__ . '/includes/helpers.php') {
    error_log('Login: Failed to load helpers.php');
    http_response_code(500);
    die('System error. Please contact support.');
}

if (!@include_once __DIR__ . '/classes/User.php') {
    error_log('Login: Failed to load User.php');
    http_response_code(500);
    die('System error. Please contact support.');
}

if (!@include_once __DIR__ . '/classes/TwoFactorAuth.php') {
    error_log('Login: Failed to load TwoFactorAuth.php');
    http_response_code(500);
    die('System error. Please contact support.');
}

// OAuth config is optional
if (file_exists(__DIR__ . '/config/oauth.php')) {
    @include_once __DIR__ . '/config/oauth.php';
}

// Redirect if already logged in
if (isLoggedIn()) {
    // Lefty is now the only admin panel
    $_SESSION['admin_panel'] = 'lefty';
    redirect('/admin/userdashboard.php');
}

$error = '';
$message = $_GET['message'] ?? '';
$requires2FA = false;
$twoFactorMethod = null;
$show2FAInput = isset($_SESSION['2fa_pending_user_id']);

// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['2fa_code'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $code = sanitizeInput($_POST['2fa_code'] ?? '');
        $method = sanitizeInput($_POST['2fa_method'] ?? 'totp');
        
        if (empty($code) || strlen($code) !== 6) {
            $error = 'Please enter a valid 6-digit code';
        } elseif (!isset($_SESSION['2fa_pending_user_id'])) {
            $error = '2FA session expired. Please log in again.';
        } else {
            $userId = $_SESSION['2fa_pending_user_id'];
            $user = fetchOne("
                SELECT two_factor_method, two_factor_secret, two_factor_email_code, 
                       two_factor_email_code_expires, two_factor_backup_codes
                FROM users WHERE id = ?
            ", [$userId]);
            
            if (!$user) {
                $error = 'User not found';
            } else {
                $codeValid = false;
                
                if ($method === 'totp') {
                    if (empty($user['two_factor_secret'])) {
                        $error = 'TOTP not configured';
                    } else {
                        $codeValid = TwoFactorAuth::verifyTOTPCode($user['two_factor_secret'], $code);
                    }
                } elseif ($method === 'email') {
                    if (empty($user['two_factor_email_code']) || 
                        empty($user['two_factor_email_code_expires']) ||
                        strtotime($user['two_factor_email_code_expires']) < time()) {
                        $error = 'Email code expired. Please request a new code.';
                    } else {
                        $codeValid = ($user['two_factor_email_code'] === $code);
                        if ($codeValid) {
                            // Clear email code
                            executeQuery("UPDATE users SET two_factor_email_code = NULL, two_factor_email_code_expires = NULL WHERE id = ?", [$userId]);
                        }
                    }
                } elseif ($method === 'backup') {
                    $codeValid = TwoFactorAuth::verifyBackupCode($userId, $code);
                }
                
                if ($codeValid) {
                    // Complete login
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_email'] = $_SESSION['2fa_pending_email'];
                    unset($_SESSION['2fa_pending_user_id']);
                    unset($_SESSION['2fa_pending_email']);
                    regenerateSession();
                    $_SESSION['admin_panel'] = 'lefty';
                    redirect('/admin/userdashboard.php');
                } else {
                    $error = 'Invalid verification code';
                }
            }
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['2fa_code'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } else {
            $user = new User();
            $result = $user->login($email, $password);
            
            if ($result['success']) {
                if ($result['requires_2fa']) {
                    $requires2FA = true;
                    $twoFactorMethod = $result['two_factor_method'] ?? 'totp';
                    $show2FAInput = true;
                    
                    // If email method, send code
                    if ($twoFactorMethod === 'email' || $twoFactorMethod === 'both') {
                        $userData = fetchOne("SELECT email FROM users WHERE id = ?", [$_SESSION['2fa_pending_user_id']]);
                        if ($userData) {
                            $emailCode = TwoFactorAuth::generateEmailCode();
                            $expiresAt = date('Y-m-d H:i:s', time() + 600);
                            executeQuery("
                                UPDATE users SET 
                                    two_factor_email_code = ?,
                                    two_factor_email_code_expires = ?
                                WHERE id = ?
                            ", [$emailCode, $expiresAt, $_SESSION['2fa_pending_user_id']]);
                            TwoFactorAuth::sendEmailCode($userData['email'], $emailCode);
                        }
                    }
                } else {
                    $_SESSION['admin_panel'] = 'lefty';
                    redirect('/admin/userdashboard.php');
                }
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Check if we're in 2FA mode from session
if ($show2FAInput && isset($_SESSION['2fa_pending_user_id'])) {
    $user = fetchOne("SELECT two_factor_method, email FROM users WHERE id = ?", [$_SESSION['2fa_pending_user_id']]);
    if ($user) {
        $twoFactorMethod = $user['two_factor_method'] ?? 'totp';
        $requires2FA = true;
        
        // Handle resend email code
        if (isset($_GET['resend_email']) && ($twoFactorMethod === 'email' || $twoFactorMethod === 'both')) {
            $emailCode = TwoFactorAuth::generateEmailCode();
            $expiresAt = date('Y-m-d H:i:s', time() + 600);
            executeQuery("
                UPDATE users SET 
                    two_factor_email_code = ?,
                    two_factor_email_code_expires = ?
                WHERE id = ?
            ", [$emailCode, $expiresAt, $_SESSION['2fa_pending_user_id']]);
            TwoFactorAuth::sendEmailCode($user['email'], $emailCode);
            $message = 'Verification code sent to your email.';
        }
    }
}

$csrfToken = generateCSRFToken();

// Generate Google Auth URL with error handling
$googleAuthUrl = '#';
if (function_exists('getGoogleAuthUrl')) {
    try {
        $googleAuthUrl = getGoogleAuthUrl();
    } catch (Throwable $e) {
        error_log('Failed to generate Google Auth URL: ' . $e->getMessage());
        $googleAuthUrl = '#'; // Fallback to prevent 500 error
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - <?php echo h(APP_NAME); ?></title>
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
                <h1>Log In</h1>
                <p class="auth-subtitle">Welcome back to <?php echo h(APP_NAME); ?></p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-info">
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($show2FAInput && $requires2FA): ?>
                <form method="POST" action="" id="2fa-form">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="2fa_method" value="<?php echo h($twoFactorMethod === 'both' ? 'totp' : $twoFactorMethod); ?>" id="2fa-method-input">
                    
                    <div class="form-group">
                        <h2 style="margin: 0 0 0.5rem; font-size: 1.25rem;">Two-Factor Authentication</h2>
                        <p style="margin: 0 0 1.5rem; color: #64748b; font-size: 0.875rem;">
                            <?php if ($twoFactorMethod === 'email'): ?>
                                Enter the 6-digit code sent to your email.
                            <?php elseif ($twoFactorMethod === 'both'): ?>
                                Choose your verification method:
                            <?php else: ?>
                                Enter the 6-digit code from your authenticator app.
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($twoFactorMethod === 'both'): ?>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="method-select">Verification Method</label>
                                <select id="method-select" name="2fa_method" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                                    <option value="totp">Use Authenticator App</option>
                                    <option value="email">Use Email Code</option>
                                    <option value="backup">Use Backup Code</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="2fa_code">Verification Code</label>
                            <input 
                                type="text" 
                                id="2fa_code" 
                                name="2fa_code" 
                                required 
                                maxlength="6" 
                                pattern="[0-9]*"
                                inputmode="numeric"
                                placeholder="000000"
                                autocomplete="one-time-code"
                                style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem; font-family: monospace; font-weight: 600;"
                                autofocus
                            >
                        </div>
                        
                        <?php if ($twoFactorMethod === 'email' || $twoFactorMethod === 'both'): ?>
                            <div class="form-group-link" style="text-align: center; margin-top: 1rem;">
                                <a href="?resend_email=1" class="forgot-password">Resend email code</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <span>Verify</span>
                    </button>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="/login.php" style="color: #64748b; font-size: 0.875rem; text-decoration: none;">Back to login</a>
                    </div>
                </form>
                
                
                <script>
                    (function() {
                        const methodSelect = document.getElementById('method-select');
                        const methodInput = document.getElementById('2fa-method-input');
                        if (methodSelect && methodInput) {
                            methodSelect.addEventListener('change', function() {
                                methodInput.value = this.value;
                            });
                        }
                        
                        const codeInput = document.getElementById('2fa_code');
                        if (codeInput) {
                            codeInput.addEventListener('input', function(e) {
                                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                            });
                        }
                    })();
                </script>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="you@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                            <button type="button" class="password-toggle" data-target="password" aria-pressed="false">
                                <span class="sr-only">Show password</span>
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group-link">
                        <a href="/forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <span>Log In</span>
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($googleAuthUrl !== '#'): ?>
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
                Sign in with Google
            </a>
            <?php endif; ?>
            
            <p class="auth-footer">
                Don't have an account? <a href="/signup.php">Sign up</a>
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

