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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        /* Homepage-specific styles - Dark Theme */
        
        .homepage-hero {
            background: var(--poda-bg-primary);
            color: var(--poda-text-primary);
            padding: 8rem 2rem 6rem;
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
            background: radial-gradient(circle at 50% 50%, rgba(0, 255, 127, 0.05) 0%, transparent 70%);
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
        
        .hero-tagline {
            font-size: 0.9rem;
            color: var(--poda-accent-signal-green);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .hero-headline {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            color: var(--poda-text-primary);
        }
        
        .hero-subheadline {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            color: var(--poda-text-secondary);
            line-height: 1.6;
            font-weight: 400;
        }
        
        .hero-ctas {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }
        
        .hero-cta-primary {
            background: var(--poda-accent-signal-green);
            color: var(--poda-bg-primary);
            font-size: 1.1rem;
            padding: 1rem 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .hero-cta-primary:hover {
            box-shadow: 0 0 24px rgba(0, 255, 127, 0.6);
        }
        
        .hero-cta-secondary {
            background: transparent;
            color: var(--poda-accent-signal-green);
            border: 2px solid var(--poda-accent-signal-green);
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }
        
        .hero-phone-mockup {
            max-width: 300px;
            height: auto;
            margin: 3rem auto 0;
            border-radius: 24px;
            border: 2px solid var(--poda-accent-signal-green);
            box-shadow: 0 0 40px rgba(0, 255, 127, 0.4), 0 0 80px rgba(0, 255, 127, 0.2);
            padding: 1rem;
            background: var(--poda-bg-secondary);
        }
        
        .hero-phone-mockup img {
            width: 100%;
            height: auto;
            border-radius: 16px;
            display: block;
        }
        
        .value-props {
            padding: 6rem 2rem;
            background: var(--poda-bg-primary);
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
            padding: 2rem;
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .value-prop-card:hover {
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.2);
        }
        
        .value-prop-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--poda-bg-primary);
            border: 2px solid var(--poda-accent-signal-green);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--poda-accent-signal-green);
        }
        
        .value-prop-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--poda-text-primary);
        }
        
        .value-prop-card p {
            color: var(--poda-text-secondary);
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .demo-section {
            padding: 6rem 2rem;
            background: var(--poda-bg-secondary);
        }
        
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .demo-container h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .demo-container p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
            margin-bottom: 3rem;
        }
        
        .demo-preview {
            background: var(--poda-bg-primary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
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
            border: 2px solid var(--poda-border-subtle);
            background: var(--poda-bg-secondary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--poda-text-secondary);
            transition: all 0.3s;
        }
        
        .demo-toggle button.active {
            border-color: var(--poda-accent-signal-green);
            color: var(--poda-accent-signal-green);
            background: var(--poda-bg-primary);
        }
        
        .demo-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .features-section {
            padding: 6rem 2rem;
            background: var(--poda-bg-primary);
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .features-container h2 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .features-container > p {
            text-align: center;
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
            margin-bottom: 3rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.2);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--poda-accent-signal-green);
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--poda-text-primary);
        }
        
        .feature-card p {
            color: var(--poda-text-secondary);
            line-height: 1.6;
        }
        
        .feature-visual {
            width: 100%;
            height: 200px;
            background: var(--poda-bg-primary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 8px;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--poda-text-secondary);
            font-size: 0.9rem;
        }
        
        .social-proof {
            padding: 6rem 2rem;
            background: var(--poda-bg-secondary);
            color: var(--poda-text-primary);
        }
        
        .social-proof-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .social-proof-container h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .social-proof-container p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
            margin-bottom: 3rem;
        }
        
        .platform-logos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            align-items: center;
            margin: 3rem 0;
        }
        
        .platform-logo {
            height: 40px;
            width: auto;
            filter: brightness(0) invert(1);
            opacity: 0.6;
        }
        
        .pricing-teaser {
            padding: 6rem 2rem;
            background: var(--poda-bg-primary);
            color: var(--poda-text-primary);
            text-align: center;
            border-top: 1px solid var(--poda-border-subtle);
        }
        
        .pricing-teaser-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .pricing-teaser h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .pricing-teaser p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            color: var(--poda-text-secondary);
        }
        
        .final-cta {
            padding: 6rem 2rem;
            background: var(--poda-bg-secondary);
            text-align: center;
        }
        
        .final-cta-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .final-cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .final-cta p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
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
    <!-- Logo - Top Left -->
    <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
    
    <!-- Header -->
    <header class="header">
        <nav class="nav">
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
            <p class="hero-tagline">The signal, clearer</p>
            <h1 class="hero-headline">The link for listeners</h1>
            <p class="hero-subheadline">A minimalist link-in-bio tool built for podcasters. Audio-first.</p>
            <div class="hero-ctas">
                <a href="/signup.php" class="btn hero-cta-primary">
                    <span>▶</span> Start Free
                </a>
                <a href="#demo" class="btn hero-cta-secondary">See live examples</a>
            </div>
            <div class="hero-phone-mockup">
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0, 255, 127, 0.1); border: 2px dashed rgba(0, 255, 127, 0.3); border-radius: 12px; color: var(--poda-text-secondary); font-size: 0.9rem; padding: 2rem; text-align: center;">
                    <div>
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📱</div>
                        <div>Page Preview</div>
                        <div style="font-size: 0.75rem; margin-top: 0.25rem; opacity: 0.7;">Image placeholder</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Value Proposition Section -->
    <section class="value-props">
        <div class="value-props-container">
            <h2 class="section-title">Audio-First</h2>
            <p class="section-subtitle">Built specifically for podcasters, not adapted for them</p>
            <div class="value-props-grid">
                <div class="value-prop-card">
                    <div class="value-prop-icon">🎧</div>
                    <h3>Audio-First</h3>
                    <p>Every feature designed with podcasters in mind. RSS sync, built-in player, and episode management.</p>
                </div>
                <div class="value-prop-card">
                    <div class="value-prop-icon">✨</div>
                    <h3>Minimalist Design</h3>
                    <p>Clean, uncluttered layouts that put your content first. Beautiful themes that don't distract.</p>
                </div>
                <div class="value-prop-card">
                    <div class="value-prop-icon">📡</div>
                    <h3>Clear Signals</h3>
                    <p>One link. All your content. Automatically synced from your RSS feed. No manual updates needed.</p>
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
                <div id="demo-placeholder" style="display:flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem; text-align: center; color: var(--poda-text-secondary); background: rgba(0, 255, 127, 0.05); border: 2px dashed rgba(0, 255, 127, 0.2); border-radius: 12px; min-height: 400px;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📱</div>
                    <p id="demo-placeholder-title" style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--poda-text-primary);">Mobile Preview</p>
                    <p style="font-size: 0.9rem; opacity: 0.7;">Image placeholder - will show page preview when available</p>
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
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Apple Podcasts</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Spotify</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">YouTube Music</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Amazon Music</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Google Podcasts</span>
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
                <a href="/pricing.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">View Pricing</a>
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
            
            const placeholder = document.getElementById('demo-placeholder');
            const titleElement = document.getElementById('demo-placeholder-title');
            
            if (placeholder && titleElement) {
                const viewText = view === 'mobile' ? 'Mobile' : 'Desktop';
                titleElement.textContent = viewText + ' Preview';
            }
        }

        // Segmented Control Navigation
        (function() {
            'use strict';
            
            const SELECTORS = {
                container: '.nav-links',
                link: '.nav-links a'
            };
            
            const container = document.querySelector(SELECTORS.container);
            if (!container) return;
            
            const links = container.querySelectorAll(SELECTORS.link);
            let activeLink = null;
            
            /**
             * Updates the sliding indicator position
             * @param {HTMLElement} target - The link element to position indicator under
             */
            function updateIndicator(target) {
                if (!target) return;
                
                const targetRect = target.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                
                const left = targetRect.left - containerRect.left;
                const width = targetRect.width;
                
                if (width > 0) {
                    container.style.setProperty('--indicator-left', `${left}px`);
                    container.style.setProperty('--indicator-width', `${width}px`);
                    container.classList.add('has-indicator');
                }
            }
            
            /**
             * Finds and sets the active link based on current page URL
             * @returns {HTMLElement|null} The active link element
             */
            function findActiveLink() {
                const currentPath = window.location.pathname;
                
                for (const link of links) {
                    const href = link.getAttribute('href');
                    const isActive = href === currentPath || 
                                   (currentPath !== '/' && href !== '/' && currentPath.startsWith(href));
                    
                    if (isActive) {
                        link.classList.add('active');
                        return link;
                    }
                }
                
                return null;
            }
            
            /**
             * Initializes the segmented control
             */
            function init() {
                // Find active link
                activeLink = findActiveLink();
                
                // If no active link found, default to first link
                if (!activeLink && links.length > 0) {
                    links[0].classList.add('active');
                    activeLink = links[0];
                }
                
                // Update indicator position
                if (activeLink) {
                    // Use requestAnimationFrame to ensure layout is complete
                    requestAnimationFrame(() => {
                        updateIndicator(activeLink);
                    });
                }
            }
            
            /**
             * Handles link hover - moves indicator to hovered link
             */
            function handleLinkHover(e) {
                updateIndicator(e.currentTarget);
            }
            
            /**
             * Handles container mouse leave - returns indicator to active link
             */
            function handleContainerLeave() {
                if (activeLink) {
                    updateIndicator(activeLink);
                } else {
                    container.classList.remove('has-indicator');
                }
            }
            
            /**
             * Handles link click - sets clicked link as active
             */
            function handleLinkClick(e) {
                // Remove active class from all links
                links.forEach(link => link.classList.remove('active'));
                
                // Set clicked link as active
                e.currentTarget.classList.add('active');
                activeLink = e.currentTarget;
                
                // Update indicator
                updateIndicator(activeLink);
            }
            
            // Initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            
            // Re-initialize on window load (fallback for slow-loading content)
            window.addEventListener('load', () => {
                if (!activeLink || !container.classList.contains('has-indicator')) {
                    init();
                }
            });
            
            // Attach event listeners
            links.forEach(link => {
                link.addEventListener('mouseenter', handleLinkHover);
                link.addEventListener('click', handleLinkClick);
            });
            
            container.addEventListener('mouseleave', handleContainerLeave);
        })();
    </script>
</body>
</html>
