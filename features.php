<?php
/**
 * Features Page
 * Podn.Bio - Detailed features listing
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Discover all the powerful features Podn.Bio offers for podcasters.">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }
        
        .feature-section {
            margin-bottom: 4rem;
        }
        
        .feature-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .feature-section .icon {
            font-size: 2.5rem;
        }
        
        .feature-section p {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .feature-list {
            list-style: none;
            padding-left: 0;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            padding-left: 2rem;
            position: relative;
            color: #4b5563;
        }
        
        .feature-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 1.2rem;
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
        <h1>Powerful Features for Podcasters</h1>
        <p>Everything you need to showcase your podcast and grow your audience</p>
    </div>
    
    <div class="content">
        <section class="feature-section">
            <h2><span class="icon">🎙️</span> RSS Feed Integration</h2>
            <p>Automatically import your podcast information, episodes, and artwork from your RSS feed.</p>
            <ul class="feature-list">
                <li>Auto-populate podcast name, description, and cover art</li>
                <li>Import recent episodes with titles and descriptions</li>
                <li>Automatic updates when new episodes are published</li>
                <li>Episode duration and publish date tracking</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🎵</span> Podcast Directory Links</h2>
            <p>Quick access links to your podcast on all major platforms.</p>
            <ul class="feature-list">
                <li>Apple Podcasts, Spotify, YouTube Music</li>
                <li>Amazon Music, Audible, TuneIn Radio</li>
                <li>Castbox, Good Pods, I Heart Radio</li>
                <li>Overcast, Pocket Casts, and more</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🎧</span> Podcast Player</h2>
            <p>Built-in audio player for your episodes using Shikwasa.js.</p>
            <ul class="feature-list">
                <li>Beautiful, accessible podcast player</li>
                <li>Episode drawer for easy browsing</li>
                <li>Mini player that stays visible while browsing</li>
                <li>Theme-aware player design</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🎨</span> Complete Customization</h2>
            <p>Make your page truly yours with extensive customization options.</p>
            <ul class="feature-list">
                <li>15+ Google Fonts for headings and body text</li>
                <li>Custom color pickers for primary, secondary, and accent colors</li>
                <li>Pre-built themes with one-click application</li>
                <li>Multiple layout options</li>
                <li>Profile and background image uploads</li>
                <li>Drag-and-drop link reordering</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🔗</span> Link Management</h2>
            <p>Add any type of link you need for your podcast.</p>
            <ul class="feature-list">
                <li>Custom links with thumbnails</li>
                <li>Social media links</li>
                <li>Affiliate links (regular and Amazon)</li>
                <li>Sponsor links with disclosure text</li>
                <li>YouTube, TikTok, Instagram integration</li>
                <li>Shopify and Spring store links</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">📧</span> Email Subscription</h2>
            <p>Grow your email list with integrated subscription forms.</p>
            <ul class="feature-list">
                <li>Drawer slider subscription form</li>
                <li>Integration with 6 major email services</li>
                <li>Mailchimp, Constant Contact, ConvertKit support</li>
                <li>AWeber, MailerLite, SendinBlue/Brevo support</li>
                <li>Double opt-in support</li>
                <li>Subscription analytics</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">📊</span> Analytics</h2>
            <p>Track your page performance and audience engagement.</p>
            <ul class="feature-list">
                <li>Page view tracking</li>
                <li>Link click analytics</li>
                <li>Subscriber growth metrics</li>
                <li>Basic analytics on all plans</li>
                <li>Advanced analytics on Pro plan</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🌐</span> Custom Domain</h2>
            <p>Use your own domain name (Pro plan feature).</p>
            <ul class="feature-list">
                <li>Connect your custom domain</li>
                <li>DNS verification tool</li>
                <li>Automatic routing</li>
                <li>Step-by-step setup instructions</li>
            </ul>
        </section>
        
        <section class="feature-section">
            <h2><span class="icon">🔒</span> Security & Reliability</h2>
            <p>Built with security and performance in mind.</p>
            <ul class="feature-list">
                <li>Secure authentication (Email + Google OAuth)</li>
                <li>Password hashing and CSRF protection</li>
                <li>Regular backups and updates</li>
                <li>99.9% uptime guarantee</li>
            </ul>
        </section>
        
        <div class="cta-box">
            <h2>Ready to Get Started?</h2>
            <p>Create your free account and start building your podcast's link-in-bio page today.</p>
            <a href="/signup.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">Start Free</a>
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

