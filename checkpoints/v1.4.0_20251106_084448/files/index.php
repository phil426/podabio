<?php
/**
 * Landing Page
 * Podn.Bio - Home page
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h(APP_NAME); ?> - Link-in-Bio for Podcasters</title>
    <meta name="description" content="Create a beautiful link-in-bio page for your podcast. Share all your podcast links, episodes, and more in one place.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .features {
            padding: 6rem 2rem;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 4rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }
        
        .feature-card {
            padding: 2rem;
            background: #f9fafb;
            border-radius: 12px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .feature-card p {
            color: #6b7280;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cta-section p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .footer {
            background: #1f2937;
            color: white;
            padding: 3rem 2rem 2rem;
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
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero-cta {
                flex-direction: column;
            }
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
    
    <section class="hero">
        <div class="hero-content">
            <h1>Your Podcast's Link-in-Bio Hub</h1>
            <p>Share all your podcast links, episodes, and content in one beautiful page. Built specifically for podcasters.</p>
            <div class="hero-cta">
                <a href="/signup.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">Start Free</a>
                <a href="/features.php" class="btn btn-secondary" style="background: transparent; border: 2px solid white; color: white; font-size: 1.1rem; padding: 1rem 2rem;">Learn More</a>
            </div>
        </div>
    </section>
    
    <section class="features">
        <div class="container">
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-subtitle">Powerful features designed for podcasters</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎙️</div>
                    <h3>RSS Feed Integration</h3>
                    <p>Auto-populate your page with podcast episodes, descriptions, and artwork from your RSS feed.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎵</div>
                    <h3>Podcast Directories</h3>
                    <p>Quick links to Apple Podcasts, Spotify, YouTube Music, and all major podcast platforms.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>Customizable Design</h3>
                    <p>Choose colors, fonts, themes, and layouts to match your podcast brand perfectly.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔗</div>
                    <h3>Custom Links</h3>
                    <p>Add any links you want: social media, websites, affiliate links, and more.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics</h3>
                    <p>Track page views, link clicks, and subscriber growth with built-in analytics.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📧</div>
                    <h3>Email Lists</h3>
                    <p>Collect email subscriptions with integrations for Mailchimp, ConvertKit, and more.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of podcasters sharing their content with Podn.Bio</p>
            <a href="/signup.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">Create Your Page Free</a>
        </div>
    </section>
    
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

