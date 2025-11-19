<?php
/**
 * Pricing Page
 * PodaBio - Subscription plans and pricing
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/payments.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Choose the perfect plan for your podcast. Free, Premium, and Pro plans available.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .nav-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.25rem;
            opacity: 0.95;
        }
        
        .pricing {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .pricing-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 2rem;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .pricing-card.featured {
            border-color: #667eea;
            border-width: 3px;
            position: relative;
        }
        
        .pricing-card.featured:before {
            content: 'POPULAR';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #667eea;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .plan-price {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin: 1rem 0;
        }
        
        .plan-price .period {
            font-size: 1rem;
            color: #6b7280;
            font-weight: 400;
        }
        
        .plan-description {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            padding: 0.75rem 0;
            padding-left: 1.5rem;
            position: relative;
            color: #4b5563;
        }
        
        .plan-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .plan-features li.unavailable {
            color: #9ca3af;
            text-decoration: line-through;
        }
        
        .plan-features li.unavailable:before {
            content: '✗';
            color: #ef4444;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 2rem 2rem;
            margin-top: 4rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #374151;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <ul class="nav-links">
                <li><a href="/features.php">Features</a></li>
                <li><a href="/pricing.php">Pricing</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/support/">Support</a></li>
            </ul>
            <div class="nav-actions">
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/signup.php" class="btn btn-primary">Get Started</a>
            </div>
        </nav>
    </header>
    
    <div class="page-header">
        <h1>Simple, Transparent Pricing</h1>
        <p>Choose the plan that's right for your podcast</p>
    </div>
    
    <div class="pricing">
        <div class="pricing-grid">
            <!-- Free Plan -->
            <div class="pricing-card">
                <div class="plan-name">Free</div>
                <div class="plan-price">$0<span class="period">/month</span></div>
                <p class="plan-description">Perfect for getting started</p>
                <ul class="plan-features">
                    <li>Basic links</li>
                    <li>Basic themes</li>
                    <li class="unavailable">Custom colors & fonts</li>
                    <li class="unavailable">Analytics</li>
                    <li class="unavailable">Email subscriptions</li>
                    <li class="unavailable">Custom domain</li>
                </ul>
                <a href="/signup.php" class="btn btn-secondary" style="width: 100%; text-align: center;">Get Started</a>
            </div>
            
            <!-- Premium Plan -->
            <div class="pricing-card featured">
                <div class="plan-name">Premium</div>
                <div class="plan-price">$<?php echo number_format(PLAN_PREMIUM_PRICE, 2); ?><span class="period">/month</span></div>
                <p class="plan-description">For serious podcasters</p>
                <ul class="plan-features">
                    <li>Basic links</li>
                    <li>Basic themes</li>
                    <li>Custom colors & fonts</li>
                    <li>Basic analytics</li>
                    <li>Email subscriptions</li>
                    <li>Priority support</li>
                    <li class="unavailable">Custom domain</li>
                </ul>
                <a href="/payment/checkout.php?plan=premium" class="btn btn-primary" style="width: 100%; text-align: center;">Upgrade to Premium</a>
            </div>
            
            <!-- Pro Plan -->
            <div class="pricing-card">
                <div class="plan-name">Pro</div>
                <div class="plan-price">$<?php echo number_format(PLAN_PRO_PRICE, 2); ?><span class="period">/month</span></div>
                <p class="plan-description">For professional podcasters</p>
                <ul class="plan-features">
                    <li>Everything in Premium</li>
                    <li>Custom domain support</li>
                    <li>Affiliate link management</li>
                    <li>Advanced analytics</li>
                    <li>24/7 Priority support</li>
                    <li>All features</li>
                </ul>
                <a href="/payment/checkout.php?plan=pro" class="btn btn-primary" style="width: 100%; text-align: center;">Upgrade to Pro</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4><?php echo h(APP_NAME); ?></h4>
                <p>The link-in-bio platform built for podcasters.</p>
            </div>
            
            <div class="footer-section">
                <h4>Product</h4>
                <ul>
                    <li><a href="/features.php">Features</a></li>
                    <li><a href="/pricing.php">Pricing</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/blog/">Blog</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="/privacy.php">Privacy</a></li>
                    <li><a href="/terms.php">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

