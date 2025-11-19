<?php
/**
 * Admin Panel Selector
 * PodaBio - Choose your admin panel experience
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

requireAuth();

// Handle panel selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Always redirect to Lefty - it's now the only admin panel
        $_SESSION['admin_panel'] = 'lefty';
        redirect('/admin/userdashboard.php');
        exit;
    }
}

// Always redirect to Lefty - it's now the only admin panel
$_SESSION['admin_panel'] = 'lefty';
redirect('/admin/userdashboard.php');
exit;

$csrfToken = generateCSRFToken();
$user = getCurrentUser();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Admin Panel - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
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
        
        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(360deg); }
        }
        
        .selector-container {
            width: 100%;
            max-width: 900px;
            position: relative;
            z-index: 1;
        }
        
        .selector-box {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .selector-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .selector-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .selector-header p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .panels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .panel-card {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .panel-card:hover {
            border-color: #0066ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 102, 255, 0.15);
        }
        
        .panel-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content {
            background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
            color: white;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content .panel-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content .panel-badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
        
        .panel-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .panel-icon {
            width: 64px;
            height: 64px;
            background: #e0e7ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #0066ff;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content .panel-icon {
            background: rgba(255, 255, 255, 0.25);
        }
        
        .panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content .panel-title {
            color: white;
        }
        
        .panel-description {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 0.75rem;
        }
        
        .panel-card input[type="radio"]:checked + .panel-content .panel-description {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .panel-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dbeafe;
            color: #0066ff;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .panel-badge.coming-soon {
            background: #fef3c7;
            color: #d97706;
        }
        
        .panel-badge.active {
            background: #d1fae5;
            color: #059669;
        }
        
        .submit-button {
            width: 100%;
            padding: 1rem;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        
        .submit-button:hover:not(:disabled) {
            background: #0052cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.4);
        }
        
        .submit-button:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .submit-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
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
        
        @media (max-width: 768px) {
            .selector-box {
                padding: 2rem 1.5rem;
            }
            
            .selector-header h1 {
                font-size: 1.625rem;
            }
            
            .panels-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <div class="selector-box">
            <div class="selector-header">
                <h1>Admin Dashboard</h1>
                <p>Loading your admin experience...</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div class="panel-icon" style="margin: 0 auto 1.5rem;">
                    <i class="fas fa-compress"></i>
                </div>
                <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #111827;">Lefty Dashboard</h2>
                <p style="color: #6b7280; margin-bottom: 2rem;">Contemporary dark sidebar layout with collapsible navigation and streamlined editing</p>
                <p style="color: #9ca3af; font-size: 0.875rem;">Redirecting you now...</p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-redirect to Lefty dashboard
        (function() {
            setTimeout(function() {
                window.location.href = '/admin/userdashboard.php';
            }, 500);
        })();
    </script>
</body>
</html>

