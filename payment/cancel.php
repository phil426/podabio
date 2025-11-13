<?php
/**
 * Payment Cancel Page
 * Podn.Bio - Payment cancellation
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = getCurrentUser();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - <?php echo h(APP_NAME); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
        }
        
        .cancel-container {
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }
        
        .cancel-icon {
            width: 80px;
            height: 80px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem;
            color: white;
        }
        
        h1 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        p {
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-icon">!</div>
        <h1>Payment Cancelled</h1>
        <p>Your payment was cancelled. No charges were made to your account.</p>
        <a href="/admin/react-admin.php" class="btn">Return to Studio</a>
    </div>
</body>
</html>

