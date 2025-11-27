<?php
/**
 * Features Page
 * PodaBio - Detailed features listing
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
    <meta name="description" content="Discover all the powerful features PodaBio offers for podcasters.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        /* Page-specific styles for features page - Dark Theme */
        
        .content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            background: var(--poda-bg-primary);
        }
        
        .feature-section {
            margin-bottom: 4rem;
        }
        
        .feature-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .feature-section .icon {
            font-size: 2.5rem;
            color: var(--poda-accent-signal-green);
        }
        
        .feature-section p {
            font-size: 1.1rem;
            color: var(--poda-text-secondary);
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
            color: var(--poda-text-secondary);
        }
        
        .feature-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--poda-accent-signal-green);
            font-weight: bold;
            font-size: 1.2rem;
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
        
        table {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
        }
        
        table th {
            background: var(--poda-bg-primary);
            color: var(--poda-text-primary);
            border-bottom: 1px solid var(--poda-border-subtle);
        }
        
        table td {
            color: var(--poda-text-secondary);
            border-bottom: 1px solid var(--poda-border-subtle);
        }
        
        .feature-section div[style*="background: #f9fafb"] {
            background: var(--poda-bg-primary) !important;
            border: 1px solid var(--poda-border-subtle) !important;
            color: var(--poda-text-secondary) !important;
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
        <h1>Powerful Features for Podcasters</h1>
        <p>Everything you need to showcase your podcast and grow your audience</p>
    </div>
    
    <div class="content">
        <!-- Feature Comparison Table -->
        <section class="feature-section" style="margin-bottom: 4rem;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Why PodaBio vs. Generic Link-in-Bio Tools</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: var(--poda-bg-secondary); border-radius: 12px; overflow: hidden; border: 1px solid var(--poda-border-subtle);">
                    <thead>
                        <tr style="background: var(--poda-bg-primary);">
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Feature</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">PodaBio</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Linktree</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Beacons</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">RSS Feed Auto-Sync</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Built-in Podcast Player</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Podcast Directory Links</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Podcast-Specific Themes</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">49+ Themes</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Limited</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Limited</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Email Subscription Integration</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">6 Services</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Basic</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Basic</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; color: var(--poda-text-secondary);">Free Plan</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-accent-signal-green);">✓ Full Features</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-text-secondary);">Limited</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-text-secondary);">Limited</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="feature-section">
            <h2><span class="icon">🎙️</span> RSS Feed Integration</h2>
            <p>Automatically import your podcast information, episodes, and artwork from your RSS feed.</p>
            <div style="background: var(--poda-bg-primary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center; color: var(--poda-text-secondary);">
                <p style="margin-bottom: 0.5rem;">[Visual: RSS feed integration screenshot]</p>
                <p style="font-size: 0.9rem;">AI Prompt: "Screenshot showing RSS feed URL input and auto-populated podcast information with cover art, name, and description fields filled automatically."</p>
            </div>
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
            <div style="background: var(--poda-bg-primary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center; color: var(--poda-text-secondary);">
                <p style="margin-bottom: 0.5rem;">[Visual: Podcast player interface]</p>
                <p style="font-size: 0.9rem;">AI Prompt: "Modern podcast player interface with waveform visualization, play/pause controls, and episode list drawer. Clean, minimalist design."</p>
            </div>
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
            <div style="background: var(--poda-bg-primary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center; color: var(--poda-text-secondary);">
                <p style="margin-bottom: 0.5rem;">[Visual: Theme preview grid]</p>
                <p style="font-size: 0.9rem;">AI Prompt: "Grid showing 4 different beautiful website themes side by side. Each with different color schemes. Clean, modern design."</p>
            </div>
            <ul class="feature-list">
                <li>49+ professionally designed themes</li>
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
            <a href="/signup.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">Start Free</a>
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

