<?php
/**
 * Choose Username Page
 * PodaBio - For new Google OAuth signups to select their username
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Page.php';

// Check for pending Google signup
if (!isset($_SESSION['pending_google_signup'])) {
    redirect('/login.php');
}

$pendingData = $_SESSION['pending_google_signup'];
$googleId = $pendingData['google_id'] ?? '';
$email = $pendingData['email'] ?? '';
$name = $pendingData['name'] ?? '';

if (empty($googleId) || empty($email)) {
    unset($_SESSION['pending_google_signup']);
    redirect('/login.php?error=' . urlencode('Invalid signup session. Please try again.'));
}

$error = '';
$suggestedUsername = '';

// Generate a suggested username from name or email
if (!empty($name)) {
    $suggestedUsername = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $name)));
} else {
    $suggestedUsername = strtolower(explode('@', $email)[0]);
    $suggestedUsername = preg_replace('/[^a-z0-9_-]/', '', $suggestedUsername);
}
$suggestedUsername = substr($suggestedUsername, 0, 30);
if (strlen($suggestedUsername) < 3) {
    $suggestedUsername = '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        
        // Validate username
        if (empty($username)) {
            $error = 'Please enter a username';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
            $error = 'Username must be 3-30 characters and contain only letters, numbers, underscores, and hyphens';
        } else {
            $pageClass = new Page();
            if (!$pageClass->isUsernameAvailable($username)) {
                $error = 'Username is already taken. Please choose another.';
            } else {
                // Create user with Google
                $user = new User();
                $result = $user->loginWithGoogle(
                    $googleId,
                    $email,
                    [
                        'name' => $name,
                        'picture' => $pendingData['picture'] ?? ''
                    ]
                );
                
                if ($result['success']) {
                    $userId = $result['user']['id'];
                    
                    // Create page with username
                    $pageResult = $pageClass->create($userId, $username);
                    
                    if (!$pageResult['success']) {
                        error_log("Failed to create page for Google user {$userId}: " . ($pageResult['error'] ?? 'Unknown error'));
                        // Continue anyway - user can create page later
                    }
                    
                    // Clear pending data
                    unset($_SESSION['pending_google_signup']);
                    
                    // Set admin panel preference
                    $_SESSION['admin_panel'] = 'lefty';
                    
                    // Redirect to dashboard
                    redirect('/admin/userdashboard.php');
                    exit;
                } else {
                    $error = $result['error'] ?? 'Failed to create account. Please try again.';
                }
            }
        }
    }
}

// Option to skip username selection
if (isset($_POST['skip']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    // Create user without page
    $user = new User();
    $result = $user->loginWithGoogle(
        $googleId,
        $email,
        [
            'name' => $name,
            'picture' => $pendingData['picture'] ?? ''
        ]
    );
    
    if ($result['success']) {
        unset($_SESSION['pending_google_signup']);
        $_SESSION['admin_panel'] = 'lefty';
        redirect('/admin/userdashboard.php#/account/profile');
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to create account. Please try again.';
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Username - <?php echo h(APP_NAME); ?></title>
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
                <h1>Almost there!</h1>
                <p class="auth-subtitle">Choose a username for your PodaBio page</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <strong>Welcome, <?php echo h($name ?: $email); ?>!</strong><br>
                Your Google account is ready. Now choose a username to create your public page.
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="username-input-wrapper">
                        <span class="username-prefix">poda.bio/</span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo h($_POST['username'] ?? $suggestedUsername); ?>" 
                            placeholder="yourname" 
                            pattern="[a-zA-Z0-9_-]{3,30}"
                            minlength="3"
                            maxlength="30"
                            required
                            autofocus
                            autocomplete="username"
                        >
                        <span class="username-status" id="username-status"></span>
                    </div>
                    <small>This will be your public page URL. Use letters, numbers, underscores, or hyphens.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <span>Create My Page</span>
                </button>
            </form>
            
            <form method="POST" action="" style="margin-top: 1rem;">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                <input type="hidden" name="skip" value="1">
                <button type="submit" class="btn btn-secondary" style="background: transparent; border: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">
                    Skip for now
                </button>
            </form>
            
            <p class="auth-footer" style="margin-top: 1.5rem;">
                <small>You can always set up your page later from your dashboard.</small>
            </p>
        </div>
    </div>
    <script>
        (function() {
            const usernameInput = document.getElementById('username');
            const usernameWrapper = usernameInput?.closest('.username-input-wrapper');
            const statusIndicator = document.getElementById('username-status');
            
            if (!usernameInput || !usernameWrapper || !statusIndicator) return;
            
            let checkTimeout = null;
            let isChecking = false;
            
            function sanitizeUsername(value) {
                return value.toLowerCase().replace(/[^a-z0-9_-]/g, '').substring(0, 30);
            }
            
            async function checkUsernameAvailability(username) {
                if (isChecking) return;
                
                const usernameRegex = /^[a-zA-Z0-9_-]{3,30}$/;
                if (!usernameRegex.test(username)) {
                    if (username.length > 0) {
                        usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                        usernameWrapper.classList.add('unavailable');
                        statusIndicator.innerHTML = '<span class="icon-close">✕</span>';
                    } else {
                        usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                        statusIndicator.innerHTML = '';
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
                    } else {
                        usernameWrapper.classList.remove('checking', 'available');
                        usernameWrapper.classList.add('unavailable');
                        statusIndicator.innerHTML = '<span class="icon-close">✕</span>';
                    }
                } catch (error) {
                    console.error('Error checking username:', error);
                    usernameWrapper.classList.remove('checking');
                    statusIndicator.innerHTML = '';
                } finally {
                    isChecking = false;
                }
            }
            
            usernameInput.addEventListener('input', (e) => {
                const sanitized = sanitizeUsername(e.target.value);
                if (sanitized !== e.target.value) {
                    e.target.value = sanitized;
                }
                
                const username = sanitized.trim();
                
                if (checkTimeout) {
                    clearTimeout(checkTimeout);
                }
                
                if (!username) {
                    usernameWrapper.classList.remove('available', 'unavailable', 'checking');
                    statusIndicator.innerHTML = '';
                    return;
                }
                
                checkTimeout = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 500);
            });
            
            // Check initial value
            if (usernameInput.value) {
                setTimeout(() => checkUsernameAvailability(usernameInput.value), 100);
            }
            
            // Add spin animation
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

