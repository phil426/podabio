<?php
/**
 * About Page
 * Podn.Bio - Company information
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Learn about Podn.Bio and our mission to help podcasters grow their audience.">
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
        
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }
        
        .content-section {
            margin-bottom: 3rem;
        }
        
        .content-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .content-section p {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .cta-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 4rem;
        }
        
        .cta-box h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .cta-box p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.95;
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
        <h1>About <?php echo h(APP_NAME); ?></h1>
        <p>Helping podcasters grow their audience, one link at a time</p>
    </div>
    
    <div class="content">
        <section class="content-section">
            <h2>Our Mission</h2>
            <p>
                <?php echo h(APP_NAME); ?> was created with one simple goal: to make it easier for podcasters to share their content and grow their audience. 
                We understand that podcasters need a central hub where they can showcase all their links, episodes, and resources in one beautiful, 
                easy-to-manage page.
            </p>
            <p>
                Whether you're just starting out or you're an established podcaster with thousands of listeners, we provide the tools you need 
                to create a professional link-in-bio page that represents your brand perfectly.
            </p>
        </section>
        
        <section class="content-section">
            <h2>Built for Podcasters</h2>
            <p>
                Unlike generic link-in-bio platforms, <?php echo h(APP_NAME); ?> is designed specifically with podcasters in mind. 
                We've integrated features that podcasters need most:
            </p>
            <ul style="list-style: none; padding-left: 0; margin-top: 1rem;">
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">✓</span>
                    RSS feed integration for automatic episode imports
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">✓</span>
                    Quick links to all major podcast directories
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">✓</span>
                    Built-in podcast player for episode playback
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">✓</span>
                    Email subscription integration to grow your audience
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">✓</span>
                    Analytics to track your page performance
                </li>
            </ul>
        </section>
        
        <section class="content-section">
            <h2>Our Values</h2>
            <p>
                We believe in simplicity, reliability, and putting podcasters first. Our platform is designed to be easy to use 
                while providing powerful features that help you grow your podcast audience.
            </p>
            <p>
                We're committed to:
            </p>
            <ul style="list-style: none; padding-left: 0; margin-top: 1rem;">
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">•</span>
                    Providing a free tier so everyone can get started
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">•</span>
                    Continuously improving based on user feedback
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">•</span>
                    Maintaining high uptime and fast page loads
                </li>
                <li style="padding: 0.5rem 0; padding-left: 1.5rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #667eea;">•</span>
                    Protecting user privacy and data security
                </li>
            </ul>
        </section>
        
        <section class="content-section">
            <h2>Get in Touch</h2>
            <p>
                Have questions, suggestions, or feedback? We'd love to hear from you! 
                Visit our <a href="/support/" style="color: #667eea; text-decoration: none;">Support Center</a> for help, 
                or reach out through our contact form.
            </p>
        </section>
        
        <div class="cta-box">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of podcasters using <?php echo h(APP_NAME); ?> to showcase their content.</p>
            <a href="/signup.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">Create Your Free Account</a>
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

