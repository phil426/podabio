<?php
/**
 * Venmo Payment Pending Page
 * Podn.Bio - Waiting for Venmo payment verification
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

$planType = sanitizeInput($_GET['plan'] ?? 'premium');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Pending - <?php echo h(APP_NAME); ?></title>
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
        
        .pending-container {
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }
        
        .pending-icon {
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
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        h1 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        p {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .venmo-details {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .venmo-details strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #92400e;
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
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="pending-container">
        <div class="pending-icon">⏳</div>
        <h1>Payment Pending Verification</h1>
        <p>Your subscription request has been received and is pending verification.</p>
        
        <?php if (VENMO_BUSINESS_USERNAME): ?>
            <div class="venmo-details">
                <strong>Next Steps:</strong>
                <ol style="margin: 1rem 0; padding-left: 1.5rem; color: #92400e;">
                    <li>Send your payment via Venmo to: <strong><?php echo h(VENMO_BUSINESS_USERNAME); ?></strong></li>
                    <li>Include a note with your email: <?php echo h($user['email']); ?></li>
                    <li>Your subscription will be activated within 24 hours after payment verification</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 2rem;">You'll receive an email confirmation once your payment is verified and your subscription is activated.</p>
        
        <a href="/admin/react-admin.php" class="btn">Return to Studio</a>
    </div>
</body>
</html>

