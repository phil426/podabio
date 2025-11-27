<?php
/**
 * About Page
 * PodaBio - Company information
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
    <meta name="description" content="Learn about PodaBio and our mission to help podcasters grow their audience.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        /* Page-specific styles for about page - Dark Theme */
        
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem 2rem;
            background: var(--poda-bg-primary);
        }
        
        .content-section {
            margin-bottom: 3rem;
        }
        
        .content-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .content-section p {
            font-size: 1.1rem;
            color: var(--poda-text-secondary);
            margin-bottom: 1rem;
        }
        
        .cta-box {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-accent-signal-green);
            color: var(--poda-text-primary);
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 4rem;
            box-shadow: 0 0 30px rgba(0, 255, 127, 0.2);
        }
        
        .cta-box h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .cta-box p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: var(--poda-text-secondary);
        }
        
        ul li span {
            color: var(--poda-accent-signal-green) !important;
        }
        
        a {
            color: var(--poda-accent-signal-green);
        }
        
    </style>
</head>
<body>
    <!-- Logo - Top Left -->
    <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
    
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
            <a href="/signup.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">Create Your Free Account</a>
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

    <script>
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
            
            function init() {
                activeLink = findActiveLink();
                
                if (!activeLink && links.length > 0) {
                    links[0].classList.add('active');
                    activeLink = links[0];
                }
                
                if (activeLink) {
                    requestAnimationFrame(() => {
                        updateIndicator(activeLink);
                    });
                }
            }
            
            function handleLinkHover(e) {
                updateIndicator(e.currentTarget);
            }
            
            function handleContainerLeave() {
                if (activeLink) {
                    updateIndicator(activeLink);
                } else {
                    container.classList.remove('has-indicator');
                }
            }
            
            function handleLinkClick(e) {
                links.forEach(link => link.classList.remove('active'));
                e.currentTarget.classList.add('active');
                activeLink = e.currentTarget;
                updateIndicator(activeLink);
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            
            window.addEventListener('load', () => {
                if (!activeLink || !container.classList.contains('has-indicator')) {
                    init();
                }
            });
            
            links.forEach(link => {
                link.addEventListener('mouseenter', handleLinkHover);
                link.addEventListener('click', handleLinkClick);
            });
            
            container.addEventListener('mouseleave', handleContainerLeave);
        })();
    </script>
</body>
</html>

