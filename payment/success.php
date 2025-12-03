<?php
/**
 * Payment Success Page
 * PodaBio - Stripe checkout confirmation
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/StripeProcessor.php';
require_once __DIR__ . '/../classes/Subscription.php';

$user = getCurrentUser();
if (!$user) {
    redirect('/login.php');
}

// Get Stripe checkout session ID from query string
$sessionId = sanitizeInput($_GET['session_id'] ?? '');

$message = '';
$success = false;
$isTrial = false;
$trialDaysRemaining = null;

if ($sessionId) {
    // Verify checkout session with Stripe
    $processor = new StripeProcessor();
    $session = $processor->getCheckoutSession($sessionId);
    
    if ($session && $session->payment_status === 'paid') {
        // Checkout was successful
        $subscriptionClass = new Subscription();
        
        // Get subscription from Stripe
        if (isset($session->subscription)) {
            $stripeSubscriptionId = $session->subscription;
            $subscription = $subscriptionClass->getByStripeSubscriptionId($stripeSubscriptionId);
            
            // Wait a moment for webhook to process (if it hasn't already)
            if (!$subscription) {
                sleep(2);
                $subscription = $subscriptionClass->getByStripeSubscriptionId($stripeSubscriptionId);
            }
            
            if ($subscription) {
                $success = true;
                
                // Check if it's a trial
                if ($subscription['is_trial'] && $subscription['trial_ends_at']) {
                    $isTrial = true;
                    $trialEnd = new DateTime($subscription['trial_ends_at']);
                    $now = new DateTime();
                    $diff = $now->diff($trialEnd);
                    $trialDaysRemaining = $diff->days;
                    
                    $message = "Your 14-day free trial has started! ";
                    $message .= "You have {$trialDaysRemaining} day" . ($trialDaysRemaining !== 1 ? 's' : '') . " remaining. ";
                    $message .= "After the trial ends, you'll be automatically charged for the Pro plan.";
                } else {
                    $message = 'Your Pro subscription has been activated successfully!';
                }
            } else {
                // Subscription not found yet (webhook might still be processing)
                $success = true;
                $message = 'Payment processed successfully! Your subscription is being activated. This may take a few moments.';
            }
        } else {
            $success = true;
            $message = 'Payment processed successfully! Your subscription is being activated.';
        }
    } else {
        $message = 'Payment session not found or not completed. Please contact support if you were charged.';
    }
} else {
    $message = 'Payment session information not found.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment <?php echo $success ? 'Success' : 'Status'; ?> - <?php echo h(APP_NAME); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zalando+Sans+Expanded:ital,wght@0,200..900;1,200..900&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
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
            <h1><?php echo $isTrial ? 'Trial Started!' : 'Payment Successful!'; ?></h1>
            <p><?php echo h($message); ?></p>
            <?php if ($isTrial && $trialDaysRemaining !== null): ?>
                <p style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                    <strong>Trial Days Remaining: <?php echo $trialDaysRemaining; ?></strong>
                </p>
            <?php endif; ?>
            <a href="/admin/userdashboard.php" class="btn">Open Studio</a>
        <?php else: ?>
            <div class="error-icon">✗</div>
            <h1>Payment Status</h1>
            <p><?php echo h($message); ?></p>
            <a href="/admin/userdashboard.php" class="btn">Open Studio</a>
        <?php endif; ?>
    </div>
</body>
</html>

