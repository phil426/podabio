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
    <meta name="description" content="Choose the perfect plan for your podcast. Free, Pro, and Enterprise plans available.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        /* Page-specific styles for pricing page - Dark Theme */
        
        .pricing {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            background: var(--poda-bg-primary);
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .pricing-card {
            background: var(--poda-bg-secondary);
            border: 2px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 0 30px rgba(0, 255, 127, 0.2);
        }
        
        .pricing-card.featured {
            border-color: var(--poda-accent-signal-green);
            border-width: 3px;
            position: relative;
        }
        
        .pricing-card.featured:before {
            content: 'MOST POPULAR';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--poda-accent-signal-green);
            color: var(--poda-bg-primary);
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .pricing-card.coming-soon {
            opacity: 0.8;
            position: relative;
        }
        
        .pricing-card.coming-soon:before {
            content: 'COMING SOON';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--poda-text-secondary);
            color: var(--poda-bg-primary);
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .pricing-card.coming-soon .btn {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--poda-text-primary);
        }
        
        .plan-price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--poda-accent-signal-green);
            margin: 1rem 0;
        }
        
        .plan-price .period {
            font-size: 1rem;
            color: var(--poda-text-secondary);
            font-weight: 400;
        }
        
        .plan-description {
            color: var(--poda-text-secondary);
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
            color: var(--poda-text-secondary);
        }
        
        .plan-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--poda-accent-signal-green);
            font-weight: bold;
        }
        
        .plan-features li.unavailable {
            color: var(--poda-text-muted);
            text-decoration: line-through;
        }
        
        .plan-features li.unavailable:before {
            content: '✗';
            color: var(--poda-text-muted);
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
        
        div[style*="background: white"] {
            background: var(--poda-bg-secondary) !important;
            border: 1px solid var(--poda-border-subtle) !important;
        }
        
        h2, h3 {
            color: var(--poda-text-primary) !important;
        }
        
        p {
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
                    <li>RSS feed auto-sync</li>
                    <li>Built-in podcast player</li>
                    <li>Up to 10 custom links</li>
                    <li>5 basic themes</li>
                    <li>Basic analytics (page views, link clicks)</li>
                    <li>poda.bio subdomain</li>
                    <li>Community support</li>
                </ul>
                <a href="/signup.php" class="btn btn-secondary" style="width: 100%; text-align: center;">Get Started</a>
            </div>
            
            <!-- Pro Plan -->
            <div class="pricing-card featured">
                <div class="plan-name">Pro</div>
                <div class="plan-price">$<?php echo number_format(PLAN_PRO_PRICE, 2); ?><span class="period">/month</span></div>
                <p class="plan-description">For professional podcasters</p>
                <ul class="plan-features">
                    <li>Everything in Free</li>
                    <li>Unlimited custom links</li>
                    <li>All 49+ themes</li>
                    <li>Custom colors & fonts</li>
                    <li>Advanced analytics (referrers, device data, insights)</li>
                    <li>Email subscription integration (6 providers)</li>
                    <li>Custom domain support</li>
                    <li>Priority support</li>
                    <li>No PodaBio branding</li>
                </ul>
                <a href="/payment/checkout.php?plan=pro" class="btn btn-primary" style="width: 100%; text-align: center;">Upgrade to Pro</a>
            </div>
            
            <!-- Enterprise Plan -->
            <div class="pricing-card coming-soon">
                <div class="plan-name">Enterprise</div>
                <div class="plan-price">Custom<span class="period" style="font-size: 0.75rem;"> pricing</span></div>
                <p class="plan-description">For teams and organizations</p>
                <ul class="plan-features">
                    <li>Everything in Pro</li>
                    <li>Team collaboration</li>
                    <li>Advanced analytics dashboard</li>
                    <li>API access</li>
                    <li>White-label options</li>
                    <li>Dedicated account manager</li>
                    <li>Custom integrations</li>
                </ul>
                <a href="/support/" class="btn btn-secondary" style="width: 100%; text-align: center;">Contact Us</a>
            </div>
        </div>
        
        <!-- Feature Comparison Matrix -->
        <div style="margin-top: 4rem; background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--poda-text-primary);">Feature Comparison</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle);">
                    <thead>
                        <tr style="background: var(--poda-bg-primary);">
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Feature</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Free</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Pro</th>
                            <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-primary);">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">RSS Feed Auto-Sync</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Podcast Player</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Custom Links</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Up to 10</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">Unlimited</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">Unlimited</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Themes</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">5 basic</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">49+ themes</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">49+ themes</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Custom Colors & Fonts</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Analytics</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Basic</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">Advanced</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">Advanced Dashboard</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Email Subscriptions</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Custom Domain</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Team Collaboration</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">API Access</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; color: var(--poda-text-secondary);">Support</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-text-secondary);">Community</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-accent-signal-green);">Priority</td>
                            <td style="padding: 1rem; text-align: center; color: var(--poda-accent-signal-green);">Dedicated Manager</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div style="margin-top: 4rem;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #1f2937;">Frequently Asked Questions</h2>
            <div style="max-width: 800px; margin: 0 auto;">
                <div style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; color: var(--poda-text-primary);">Can I use PodaBio for free?</h3>
                    <p style="color: var(--poda-text-secondary); line-height: 1.6;">Yes! Our free plan includes RSS feed auto-sync and the built-in podcast player. Perfect for getting started.</p>
                </div>
                <div style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; color: var(--poda-text-primary);">What's included in Pro?</h3>
                    <p style="color: var(--poda-text-secondary); line-height: 1.6;">Pro includes unlimited links, all 49+ themes, custom colors & fonts, advanced analytics, email subscriptions, custom domain support, and priority support.</p>
                </div>
                <div style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; color: var(--poda-text-primary);">When will Enterprise be available?</h3>
                    <p style="color: var(--poda-text-secondary); line-height: 1.6;">Enterprise plans are coming soon! Contact us to be notified when Enterprise features become available, or to discuss custom enterprise solutions.</p>
                </div>
                <div style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; color: var(--poda-text-primary);">Can I cancel anytime?</h3>
                    <p style="color: var(--poda-text-secondary); line-height: 1.6;">Absolutely. Cancel your subscription at any time. You'll continue to have access until the end of your billing period.</p>
                </div>
                <div style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 0.75rem; color: var(--poda-text-primary);">Do you offer refunds?</h3>
                    <p style="color: var(--poda-text-secondary); line-height: 1.6;">We offer a 30-day money-back guarantee. If you're not satisfied, contact us for a full refund.</p>
                </div>
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

