<?php
/**
 * Payment Checkout Page
 * Podn.Bio - Subscription checkout
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/PaymentProcessor.php';
require_once __DIR__ . '/../classes/Subscription.php';

// Check authentication
$user = getCurrentUser();
if (!$user) {
    redirect('/login.php?redirect=' . urlencode('/payment/checkout.php'));
}

// Get plan from query string
$planType = sanitizeInput($_GET['plan'] ?? '');
if (!in_array($planType, ['premium', 'pro'])) {
    $planType = 'premium'; // Default
}

// Get plan details
$planPrices = [
    'premium' => PLAN_PREMIUM_PRICE,
    'pro' => PLAN_PRO_PRICE
];

$planNames = [
    'premium' => 'Premium',
    'pro' => 'Pro'
];

$price = $planPrices[$planType];
$planName = $planNames[$planType];

// Check if payment is enabled
if (!PAYMENT_ENABLED) {
    die('Payment processing is currently disabled. Please contact support.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo h(APP_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 2rem 1rem;
        }
        
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .checkout-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .checkout-body {
            padding: 2rem;
        }
        
        .plan-summary {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .plan-summary h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin: 1rem 0;
        }
        
        .plan-features {
            list-style: none;
            margin-top: 1rem;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .plan-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .payment-methods {
            margin-top: 2rem;
        }
        
        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f9faff;
        }
        
        .payment-method.active {
            border-color: #667eea;
            background: #f9faff;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 1rem;
        }
        
        .payment-method label {
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .payment-method p {
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 2rem;
            transition: background 0.3s;
        }
        
        .btn-checkout:hover {
            background: #5568d3;
        }
        
        .btn-checkout:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .venmo-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .venmo-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Complete Your Subscription</h1>
            <p>Choose your payment method</p>
        </div>
        
        <div class="checkout-body">
            <div class="plan-summary">
                <h2><?php echo h($planName); ?> Plan</h2>
                <div class="plan-price">$<?php echo number_format($price, 2); ?><span style="font-size: 1rem; color: #666;">/month</span></div>
                
                <ul class="plan-features">
                    <?php if ($planType === 'premium'): ?>
                        <li>Custom Colors & Fonts</li>
                        <li>Basic Analytics</li>
                        <li>Email Subscription Integration</li>
                        <li>Priority Support</li>
                    <?php else: ?>
                        <li>Everything in Premium</li>
                        <li>Custom Domain Support</li>
                        <li>Affiliate Link Management</li>
                        <li>Advanced Analytics</li>
                        <li>24/7 Priority Support</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <form id="checkout-form" method="POST" action="/api/payment/process.php">
                <input type="hidden" name="plan" value="<?php echo h($planType); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                        <label>
                            <input type="radio" name="payment_method" value="paypal" checked required>
                            PayPal
                        </label>
                        <p>Pay securely with your PayPal account or credit card</p>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('venmo')">
                        <label>
                            <input type="radio" name="payment_method" value="venmo" required>
                            Venmo
                        </label>
                        <p>Send payment via Venmo to <?php echo h(VENMO_BUSINESS_USERNAME ?: 'our business account'); ?></p>
                        <?php if (VENMO_BUSINESS_USERNAME): ?>
                            <div class="venmo-info">
                                <strong>Venmo Username:</strong> <?php echo h(VENMO_BUSINESS_USERNAME); ?>
                                <p style="margin-top: 0.5rem;">After sending payment, we'll verify and activate your subscription within 24 hours.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn-checkout" id="checkout-btn">
                    Continue to Payment
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
            document.querySelector(`input[value="${method}"]`).closest('.payment-method').classList.add('active');
            document.querySelector(`input[value="${method}"]`).checked = true;
        }
        
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('checkout-btn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            const formData = new FormData(this);
            
            fetch('/api/payment/process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.error || 'Payment processing failed');
                    btn.disabled = false;
                    btn.textContent = 'Continue to Payment';
                }
            })
            .catch(() => {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Continue to Payment';
            });
        });
    </script>
</body>
</html>

