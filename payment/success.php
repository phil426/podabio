<?php
/**
 * Payment Success Page
 * Podn.Bio - Payment confirmation
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/PaymentProcessor.php';
require_once __DIR__ . '/../classes/Subscription.php';

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

// Get payment details from query string
$orderId = sanitizeInput($_GET['token'] ?? '');
$planType = sanitizeInput($_GET['plan'] ?? '');

$message = '';
$success = false;

if ($orderId) {
    // Capture PayPal payment
    $processor = new PaymentProcessor();
    $result = $processor->capturePayPalPayment($orderId);
    
    if ($result['success']) {
        // Update subscription with transaction ID
        $subscription = new Subscription();
        $sub = $subscription->getByPaymentId($orderId);
        
        if ($sub) {
            executeQuery(
                "UPDATE subscriptions SET payment_id = ?, status = 'active', updated_at = NOW() WHERE id = ?",
                [$result['transaction_id'], $sub['id']]
            );
            $success = true;
            $message = 'Your subscription has been activated successfully!';
        } else {
            $message = 'Subscription activated, but we could not find your subscription record. Please contact support.';
        }
    } else {
        $message = 'Payment processing encountered an issue. If your payment was successful, please contact support.';
    }
} else {
    $message = 'Payment information not found.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment <?php echo $success ? 'Success' : 'Status'; ?> - <?php echo h(APP_NAME); ?></title>
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
        
        .result-container {
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem;
            color: white;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #ef4444;
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
    <div class="result-container">
        <?php if ($success): ?>
            <div class="success-icon">✓</div>
            <h1>Payment Successful!</h1>
            <p><?php echo h($message); ?></p>
            <a href="/admin/react-admin.php" class="btn">Open Studio</a>
        <?php else: ?>
            <div class="error-icon">✗</div>
            <h1>Payment Status</h1>
            <p><?php echo h($message); ?></p>
            <a href="/admin/react-admin.php" class="btn">Open Studio</a>
            <a href="/payment/checkout.php?plan=<?php echo h($planType); ?>" style="margin-left: 1rem; color: #667eea;">Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>

