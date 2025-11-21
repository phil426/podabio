<?php
/**
 * Homepage - PodaBio Marketing Landing Page
 * Conversion-focused landing page showcasing PodaBio as the podcast-first link-in-bio platform
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h(APP_NAME); ?> - The Link-in-Bio Platform Built for Podcasters</title>
    <meta name="description" content="One beautiful page. All your links, episodes, and resources. Automatically synced from your RSS feed. Built specifically for podcasters.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing.css?v=<?php echo filemtime(__DIR__ . '/css/marketing.css'); ?>">
    <style>
        /* Homepage-specific styles */
        
        .homepage-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .homepage-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/assets/images/hero/podcast-studio-hero.jpg') center/cover;
            opacity: 0.15;
            z-index: 0;
        }
        
        .homepage-hero > * {
            position: relative;
            z-index: 1;
        }
        
        .hero-content {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-headline {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-subheadline {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .hero-ctas {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin-top: 3rem;
        }
        
        .value-props {
            padding: 5rem 2rem;
            background: white;
        }
        
        .value-props-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .value-props-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .value-prop-card {
            text-align: center;
        }
        
        .value-prop-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        .value-prop-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }
        
        .value-prop-card p {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .demo-section {
            padding: 5rem 2rem;
            background: #f9fafb;
        }
        
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .demo-container h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .demo-container p {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 3rem;
        }
        
        .demo-preview {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        
        .demo-toggle {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .demo-toggle button {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }
        
        .demo-toggle button.active {
            border-color: #667eea;
            color: #667eea;
            background: #f3f4f6;
        }
        
        .demo-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .features-section {
            padding: 5rem 2rem;
            background: white;
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .features-container h2 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .features-container > p {
            text-align: center;
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 3rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 2rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }
        
        .feature-card p {
            color: #6b7280;
            line-height: 1.6;
        }
        
        .feature-visual {
            width: 100%;
            height: 200px;
            background: #e5e7eb;
            border-radius: 8px;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 0.9rem;
        }
        
        .social-proof {
            padding: 5rem 2rem;
            background: #1f2937;
            color: white;
        }
        
        .social-proof-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .social-proof-container h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .social-proof-container p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 3rem;
        }
        
        .platform-logos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            align-items: center;
            margin: 3rem 0;
            opacity: 0.8;
        }
        
        .platform-logo {
            height: 40px;
            width: auto;
            filter: grayscale(100%) brightness(2);
        }
        
        .pricing-teaser {
            padding: 5rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        
        .pricing-teaser-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .pricing-teaser h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .pricing-teaser p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .final-cta {
            padding: 5rem 2rem;
            background: white;
            text-align: center;
        }
        
        .final-cta-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .final-cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .final-cta p {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 768px) {
            .hero-headline {
                font-size: 2.5rem;
            }
            
            .hero-subheadline {
                font-size: 1.25rem;
            }
            
            .hero-ctas {
                flex-direction: column;
                align-items: center;
            }
            
            .hero-ctas .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .value-props-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <ul class="nav-links">
                <li><a href="/features.php">Features</a></li>
                <li><a href="/pricing.php">Pricing</a></li>
                <li><a href="/examples.php">Examples</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/support/">Support</a></li>
            </ul>
            <div class="nav-actions">
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/signup.php" class="btn btn-primary">Get Started</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="homepage-hero">
        <div class="hero-content">
            <h1 class="hero-headline">The Link-in-Bio Platform Built for Podcasters</h1>
            <p class="hero-subheadline">One beautiful page. All your links, episodes, and resources. Automatically synced from your RSS feed.</p>
            <div class="hero-ctas">
                <a href="/signup.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">Start Free</a>
                <a href="#demo" class="btn btn-secondary" style="background: rgba(255,255,255,0.1); color: white; border-color: white; font-size: 1.1rem; padding: 1rem 2rem;">See Example</a>
            </div>
            <img src="/assets/images/hero/podcast-studio-hero.jpg" alt="Podcast studio" class="hero-image" onerror="this.style.display='none'">
        </div>
    </section>

    <!-- Value Proposition Section -->
    <section class="value-props">
        <div class="value-props-container">
            <h2 class="section-title">One link. All your content. Automatically synced.</h2>
            <p class="section-subtitle">Built specifically for podcasters, not adapted for them</p>
            <div class="value-props-grid">
                <div class="value-prop-card">
                    <div class="value-prop-icon">📡</div>
                    <h3>RSS Auto-Sync</h3>
                    <p>Connect your feed once, we handle the rest. New episodes appear automatically.</p>
                </div>
                <div class="value-prop-card">
                    <div class="value-prop-icon">🎧</div>
                    <h3>Built-in Player</h3>
                    <p>Listeners can play episodes right on your page. No redirects, no friction.</p>
                </div>
                <div class="value-prop-card">
                    <div class="value-prop-icon">🌐</div>
                    <h3>All Platforms</h3>
                    <p>Links to Apple, Spotify, and 20+ directories. One click to everywhere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Demo Section -->
    <section class="demo-section" id="demo">
        <div class="demo-container">
            <h2>See It In Action</h2>
            <p>Beautiful pages that represent your brand perfectly</p>
            <div class="demo-toggle">
                <button class="active" onclick="switchDemo('mobile')">Mobile</button>
                <button onclick="switchDemo('desktop')">Desktop</button>
            </div>
            <div class="demo-preview">
                <img id="demo-image" src="/assets/images/demo/page-preview-mobile.png" alt="PodaBio page preview" class="demo-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; padding: 4rem; text-align: center; color: #9ca3af;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">Demo preview image placeholder</p>
                    <p style="font-size: 0.9rem;">AI Prompt: "Screenshot mockup of a beautiful podcast link-in-bio page showing profile image, podcast title, description, social icons, podcast player, and link buttons. Modern, clean design. iPhone frame mockup."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Highlights -->
    <section class="features-section">
        <div class="features-container">
            <h2 class="section-title">Everything You Need to Grow</h2>
            <p class="section-subtitle">Turn listeners into subscribers, subscribers into fans</p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📡</div>
                    <h3>RSS Feed Integration</h3>
                    <p>Auto-populate your podcast name, description, cover art, and episodes from your RSS feed.</p>
                    <div class="feature-visual">[AI: Podcast cover art visual]</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎵</div>
                    <h3>Podcast Player</h3>
                    <p>Beautiful, accessible player with episode drawer. Listeners stay on your page.</p>
                    <div class="feature-visual">[AI: Waveform visualization]</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>49+ Beautiful Themes</h3>
                    <p>Choose from professionally designed themes or create your own. Look as polished as you sound.</p>
                    <div class="feature-visual">[AI: Theme preview grid]</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics Dashboard</h3>
                    <p>Track page views, link clicks, and subscriber growth. Know what's working.</p>
                    <div class="feature-visual">[AI: Analytics charts]</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📧</div>
                    <h3>Email Subscriptions</h3>
                    <p>Grow your list with integrated forms. Works with Mailchimp, ConvertKit, and more.</p>
                    <div class="feature-visual">[AI: Email form visual]</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Custom Domains</h3>
                    <p>Use your own domain name. Professional branding for serious podcasters.</p>
                    <div class="feature-visual">[AI: Domain badge visual]</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="social-proof">
        <div class="social-proof-container">
            <h2>Trusted by Podcasters Everywhere</h2>
            <p>Join thousands of creators using PodaBio to grow their audience</p>
            <div class="platform-logos">
                <img src="/assets/images/social-proof/platform-logos.png" alt="Podcast platforms" class="platform-logo" onerror="this.style.display='none'">
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem;">Apple Podcasts</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem;">Spotify</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem;">YouTube Music</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem;">+ 20 more</span>
            </div>
        </div>
    </section>

    <!-- Pricing Teaser -->
    <section class="pricing-teaser">
        <div class="pricing-teaser-container">
            <h2>Start Free, Upgrade When You're Ready</h2>
            <p>Free plan includes RSS sync and basic player. Upgrade for analytics, custom domains, and more.</p>
            <a href="/pricing.php" class="btn btn-primary" style="background: white; color: #667eea; font-size: 1.1rem; padding: 1rem 2rem;">View Pricing</a>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <div class="final-cta-container">
            <h2>Ready to Grow Your Podcast?</h2>
            <p>Create your free page in 2 minutes</p>
            <a href="/signup.php" class="btn btn-primary" style="font-size: 1.25rem; padding: 1.25rem 3rem;">Get Started Free</a>
        </div>
    </section>

    <!-- Footer -->
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

    <script>
        function switchDemo(view) {
            const buttons = document.querySelectorAll('.demo-toggle button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const img = document.getElementById('demo-image');
            if (view === 'mobile') {
                img.src = '/assets/images/demo/page-preview-mobile.png';
            } else {
                img.src = '/assets/images/demo/page-preview-desktop.png';
            }
        }
    </script>
</body>
</html>
