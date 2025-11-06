<?php
/**
 * Signup Page
 * Podn.Bio
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/config/oauth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/editor.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Modern Auth Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 12.5%, #f093fb 25%, #4facfe 37.5%, #00f2fe 50%, #87ceeb 62.5%, #ffd700 75%, #ffb366 87.5%, #ffa07a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            position: relative;
            overflow: hidden;
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: drift 25s linear infinite;
            pointer-events: none;
        }
        
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            animation: pulseGlow 8s ease-in-out infinite;
        }
        
        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(360deg); }
        }
        
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        .auth-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }
        
        .auth-box {
            background: white;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo-link {
            display: inline-block;
            text-decoration: none;
            transition: transform 0.2s;
        }
        
        .auth-logo-link:hover {
            transform: scale(1.05);
        }
        
        .auth-logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.3);
            transition: box-shadow 0.2s;
        }
        
        .auth-logo-link:hover .auth-logo-icon {
            box-shadow: 0 6px 16px rgba(0, 102, 255, 0.4);
        }
        
        .auth-box h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem 0;
            text-align: center;
        }
        
        .auth-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        
        .auth-box .form-group {
            margin-bottom: 1.25rem;
        }
        
        .auth-box .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .auth-box .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            background: #ffffff;
        }
        
        .auth-box .form-group input:focus {
            outline: none;
            border-color: #0066ff;
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
        }
        
        .auth-box .form-group input::placeholder {
            color: #9ca3af;
        }
        
        .auth-box .form-group small {
            display: block;
            margin-top: 0.375rem;
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .auth-box .btn {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .auth-box .btn-primary {
            background: #0066ff;
            color: white;
        }
        
        .auth-box .btn-primary:hover {
            background: #0052cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.4);
        }
        
        .auth-box .btn-primary:active {
            transform: translateY(0);
        }
        
        .auth-divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }
        
        .auth-divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: #e5e7eb;
        }
        
        .auth-divider span {
            position: relative;
            background: white;
            padding: 0 1rem;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .auth-box .btn-google {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
            gap: 0.75rem;
        }
        
        .auth-box .btn-google:hover {
            background: #f9fafb;
            border-color: #0066ff;
            box-shadow: 0 2px 8px rgba(0, 102, 255, 0.1);
        }
        
        .auth-box .btn-google svg {
            flex-shrink: 0;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .auth-footer a {
            color: #0066ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .auth-footer a:hover {
            color: #0052cc;
            text-decoration: underline;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error::before {
            content: '⚠';
            font-size: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success::before {
            content: '✓';
            font-size: 1rem;
        }
        
        @media (max-width: 480px) {
            .auth-box {
                padding: 2.5rem 1.5rem;
            }
            
            .auth-box h1 {
                font-size: 1.625rem;
            }
            
            .auth-logo-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <a href="/" class="auth-logo-link" title="Back to Home">
                    <div class="auth-logo-icon">
                        <i class="fas fa-podcast"></i>
                    </div>
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
                    <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                    <small>Must be at least 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter your password">
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
</body>
</html>
