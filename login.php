<?php
/**
 * Login Page
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                redirect('/editor.php');
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
    <title>Log In - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Auth Page Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 440px;
        }
        
        .auth-box {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .auth-box h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 2rem 0;
            text-align: center;
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
        }
        
        .auth-box .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .forgot-password:hover {
            color: #764ba2;
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
        }
        
        .auth-box .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .auth-box .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
        }
        
        .auth-box .btn-google {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .auth-box .btn-google:hover {
            background: #f9fafb;
            border-color: #667eea;
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
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .auth-footer a:hover {
            color: #764ba2;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 480px) {
            .auth-box {
                padding: 2rem 1.5rem;
            }
            
            .auth-box h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Log In</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo h($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <a href="/forgot-password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary">Log In</button>
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
                Sign in with Google
            </a>
            
            <p class="auth-footer">
                Don't have an account? <a href="/signup.php">Sign up</a>
            </p>
        </div>
    </div>
</body>
</html>

