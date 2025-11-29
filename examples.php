<?php
/**
 * Examples/Showcase Page
 * PodaBio - Showcase of example pages
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examples - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="See real examples of PodaBio pages created by podcasters.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        .examples-page {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            background: var(--poda-bg-primary);
        }
        
        .examples-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .examples-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .examples-header p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--poda-border-subtle);
            background: var(--poda-bg-secondary);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--poda-text-secondary);
            transition: all 0.3s;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--poda-accent-signal-green);
            color: var(--poda-accent-signal-green);
            background: var(--poda-bg-primary);
        }
        
        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .example-card {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .example-card:hover {
            transform: translateY(-5px);
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.2);
        }
        
        .example-screenshot {
            width: 100%;
            height: 400px;
            background: var(--poda-bg-primary);
            border-bottom: 1px solid var(--poda-border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--poda-text-secondary);
            position: relative;
            overflow: hidden;
        }
        
        .example-screenshot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .example-info {
            padding: 1.5rem;
        }
        
        .example-info h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--poda-text-primary);
        }
        
        .example-meta {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--poda-text-secondary);
        }
        
        .example-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .example-features {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .feature-badge {
            background: var(--poda-bg-primary);
            border: 1px solid var(--poda-accent-signal-green);
            color: var(--poda-accent-signal-green);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .placeholder-note {
            text-align: center;
            padding: 2rem;
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            margin: 2rem 0;
            color: var(--poda-text-secondary);
        }
        
        .placeholder-note a {
            color: var(--poda-accent-signal-green);
        }
        
        @media (max-width: 768px) {
            .examples-header h1 {
                font-size: 2rem;
            }
            
            .examples-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo"><?php echo h(APP_NAME); ?></a>
            <div class="nav-segmented">
                <ul class="nav-links">
                    <li><a href="/features.php">Features</a></li>
                    <li><a href="/pricing.php">Pricing</a></li>
                    <li><a href="/examples.php">Examples</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            <div class="nav-actions">
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/signup.php" class="btn btn-primary">Get Started</a>
            </div>
        </nav>
    </header>
    
    <div class="page-header">
        <h1>See PodaBio In Action</h1>
        <p>Real examples from podcasters using PodaBio</p>
    </div>
    
    <div class="examples-page">
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterExamples('all')">All</button>
            <button class="filter-btn" onclick="filterExamples('business')">Business</button>
            <button class="filter-btn" onclick="filterExamples('comedy')">Comedy</button>
            <button class="filter-btn" onclick="filterExamples('technology')">Technology</button>
            <button class="filter-btn" onclick="filterExamples('lifestyle')">Lifestyle</button>
            <button class="filter-btn" onclick="filterExamples('news')">News</button>
        </div>
        
        <div class="placeholder-note">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem; font-weight: 600;">Example Pages Coming Soon</p>
            <p>We're collecting examples from our users. If you'd like to showcase your PodaBio page here, <a href="/support/" style="color: #667eea;">contact us</a>!</p>
        </div>
        
        <div class="examples-grid">
            <!-- Example cards will be populated here -->
            <!-- Placeholder structure for future examples -->
            <div class="example-card" data-category="business">
                <div class="example-screenshot">
                    <div style="text-align: center; padding: 2rem;">
                        <p style="margin-bottom: 0.5rem;">[Example Screenshot]</p>
                        <p style="font-size: 0.9rem;">AI Prompt: "Screenshot of a professional business podcast PodaBio page with clean design, podcast player, and business-focused links."</p>
                    </div>
                </div>
                <div class="example-info">
                    <h3>Business Podcast Example</h3>
                    <p style="color: #6b7280; margin-top: 0.5rem;">Professional theme with focus on credibility and trust.</p>
                    <div class="example-meta">
                        <span>🎨 Theme: Professional</span>
                        <span>📊 Analytics: Enabled</span>
                    </div>
                    <div class="example-features">
                        <span class="feature-badge">RSS Sync</span>
                        <span class="feature-badge">Player</span>
                        <span class="feature-badge">Custom Domain</span>
                    </div>
                </div>
            </div>
            
            <div class="example-card" data-category="comedy">
                <div class="example-screenshot">
                    <div style="text-align: center; padding: 2rem;">
                        <p style="margin-bottom: 0.5rem;">[Example Screenshot]</p>
                        <p style="font-size: 0.9rem;">AI Prompt: "Screenshot of a fun, vibrant comedy podcast PodaBio page with playful colors and engaging design."</p>
                    </div>
                </div>
                <div class="example-info">
                    <h3>Comedy Podcast Example</h3>
                    <p style="color: #6b7280; margin-top: 0.5rem;">Vibrant theme that matches the podcast's personality.</p>
                    <div class="example-meta">
                        <span>🎨 Theme: Vibrant</span>
                        <span>📊 Analytics: Enabled</span>
                    </div>
                    <div class="example-features">
                        <span class="feature-badge">RSS Sync</span>
                        <span class="feature-badge">Player</span>
                        <span class="feature-badge">Social Links</span>
                    </div>
                </div>
            </div>
            
            <div class="example-card" data-category="technology">
                <div class="example-screenshot">
                    <div style="text-align: center; padding: 2rem;">
                        <p style="margin-bottom: 0.5rem;">[Example Screenshot]</p>
                        <p style="font-size: 0.9rem;">AI Prompt: "Screenshot of a tech podcast PodaBio page with modern, minimalist design and tech-focused content."</p>
                    </div>
                </div>
                <div class="example-info">
                    <h3>Technology Podcast Example</h3>
                    <p style="color: #6b7280; margin-top: 0.5rem;">Modern, minimalist design for tech-savvy audiences.</p>
                    <div class="example-meta">
                        <span>🎨 Theme: Modern</span>
                        <span>📊 Analytics: Enabled</span>
                    </div>
                    <div class="example-features">
                        <span class="feature-badge">RSS Sync</span>
                        <span class="feature-badge">Player</span>
                        <span class="feature-badge">Email Subscriptions</span>
                    </div>
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
        function filterExamples(category) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.example-card');
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>

    <script>
        // Segmented Control Navigation - Sliding Indicator
        (function() {
            const navLinksContainer = document.querySelector('.nav-links');
            if (!navLinksContainer) return;
            
            const links = navLinksContainer.querySelectorAll('a');
            let activeLink = null;
            
            function updateIndicator(target) {
                const rect = target.getBoundingClientRect();
                const containerRect = navLinksContainer.getBoundingClientRect();
                
                const left = rect.left - containerRect.left - 0.25rem; // Account for container padding
                const width = rect.offsetWidth;
                
                navLinksContainer.style.setProperty('--indicator-left', left + 'px');
                navLinksContainer.style.setProperty('--indicator-width', width + 'px');
                navLinksContainer.classList.add('has-indicator');
            }
            
            // Set initial active link based on current page
            const currentPath = window.location.pathname;
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath || 
                    (currentPath !== '/' && href !== '/' && currentPath.startsWith(href))) {
                    link.classList.add('active');
                    activeLink = link;
                }
            });

            // Set indicator position for active link on load
            if (activeLink) {
                requestAnimationFrame(() => {
                    updateIndicator(activeLink);
                });
            }
            
            // Handle hover - indicator follows cursor
            links.forEach(link => {
                link.addEventListener('mouseenter', () => {
                    updateIndicator(link);
                });
            });
            
            // Return to active link on mouse leave
            navLinksContainer.addEventListener('mouseleave', () => {
                if (activeLink) {
                    updateIndicator(activeLink);
                } else {
                    navLinksContainer.classList.remove('has-indicator');
                }
            });
            
            // Handle click - set as active
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    links.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    activeLink = link;
                    updateIndicator(link);
                });
            });
        })();
    </script>
</body>
</html>



