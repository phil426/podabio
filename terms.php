<?php
/**
 * Terms of Service
 * PodaBio - Legal Terms and Conditions
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Terms of Service for PodaBio - The link-in-bio platform built for podcasters.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <style>
        .legal-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 4rem 2rem;
            line-height: 1.8;
            color: var(--poda-text-primary);
            background: var(--poda-bg-primary);
        }
        
        .legal-page h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .legal-page .last-updated {
            color: var(--poda-text-secondary);
            font-size: 0.9rem;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--poda-border-subtle);
        }
        
        .legal-page h2 {
            font-size: 1.5rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .legal-page h3 {
            font-size: 1.2rem;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--poda-text-primary);
        }
        
        .legal-page p {
            margin-bottom: 1rem;
            color: var(--poda-text-secondary);
        }
        
        .legal-page ul, .legal-page ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .legal-page li {
            margin-bottom: 0.5rem;
            color: var(--poda-text-secondary);
        }
        
        .legal-page a {
            color: var(--poda-accent-signal-green);
            text-decoration: underline;
        }
        
        .legal-page a:hover {
            color: var(--poda-accent-signal-green-hover);
        }
        
        .contact-info {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
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

    <div class="legal-page">
        <h1>Terms of Service</h1>
        <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>

        <section>
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using <?php echo h(APP_NAME); ?> ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
            <p>These Terms of Service ("Terms") govern your access to and use of <?php echo h(APP_NAME); ?>, a link-in-bio platform designed for podcasters. By creating an account, accessing, or using our Service, you agree to be bound by these Terms.</p>
        </section>

        <section>
            <h2>2. Description of Service</h2>
            <p><?php echo h(APP_NAME); ?> is a web-based platform that allows users to create personalized landing pages ("Pages") to showcase their podcast content, links, and resources. Our Service includes:</p>
            <ul>
                <li>Creation and customization of link-in-bio pages</li>
                <li>RSS feed integration for automatic podcast episode syncing</li>
                <li>Podcast player functionality</li>
                <li>Widget and content management tools</li>
                <li>Analytics and tracking features</li>
                <li>Custom domain support (on paid plans)</li>
                <li>Theme customization options</li>
            </ul>
        </section>

        <section>
            <h2>3. Account Registration and Eligibility</h2>
            <h3>3.1 Eligibility</h3>
            <p>You must be at least 13 years old to use our Service. If you are under 18, you represent that you have your parent's or guardian's permission to use the Service and that they have agreed to these Terms on your behalf.</p>
            
            <h3>3.2 Account Creation</h3>
            <p>To use our Service, you must create an account by providing accurate, current, and complete information. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
            
            <h3>3.3 Account Security</h3>
            <p>You agree to:</p>
            <ul>
                <li>Keep your password secure and confidential</li>
                <li>Notify us immediately of any unauthorized use of your account</li>
                <li>Be responsible for all activities under your account</li>
                <li>Not share your account credentials with others</li>
            </ul>
        </section>

        <section>
            <h2>4. Acceptable Use</h2>
            <p>You agree not to use the Service to:</p>
            <ul>
                <li>Violate any applicable laws, regulations, or third-party rights</li>
                <li>Upload, post, or transmit any content that is illegal, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or otherwise objectionable</li>
                <li>Impersonate any person or entity or falsely state or misrepresent your affiliation with a person or entity</li>
                <li>Upload or transmit any content that infringes on intellectual property rights, including copyrights, trademarks, or patents</li>
                <li>Upload or transmit viruses, malware, or any other malicious code</li>
                <li>Interfere with or disrupt the Service or servers connected to the Service</li>
                <li>Attempt to gain unauthorized access to any portion of the Service or any other accounts, computer systems, or networks</li>
                <li>Use automated systems (bots, scrapers) to access the Service without our express written permission</li>
                <li>Engage in any activity that could damage, disable, or impair the Service</li>
                <li>Use the Service for spam, phishing, or other fraudulent activities</li>
                <li>Collect or harvest information about other users without their consent</li>
            </ul>
        </section>

        <section>
            <h2>5. User Content and Intellectual Property</h2>
            <h3>5.1 Your Content</h3>
            <p>You retain ownership of all content you upload, post, or create through the Service ("User Content"). By using the Service, you grant <?php echo h(APP_NAME); ?> a worldwide, non-exclusive, royalty-free license to use, reproduce, modify, adapt, publish, and display your User Content solely for the purpose of providing and improving the Service.</p>
            
            <h3>5.2 Content Responsibility</h3>
            <p>You are solely responsible for your User Content. You represent and warrant that:</p>
            <ul>
                <li>You own or have the necessary rights to all User Content you post</li>
                <li>Your User Content does not violate any third-party rights, including intellectual property, privacy, or publicity rights</li>
                <li>Your User Content complies with all applicable laws and regulations</li>
            </ul>
            
            <h3>5.3 Our Intellectual Property</h3>
            <p>The Service, including its original content, features, and functionality, is owned by <?php echo h(APP_NAME); ?> and is protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>
            
            <h3>5.4 RSS Feed Content</h3>
            <p>When you connect an RSS feed to your Page, you grant us permission to fetch, cache, and display content from that feed. You represent that you have the right to grant such permission and that the RSS feed content does not violate any third-party rights.</p>
        </section>

        <section>
            <h2>6. Subscription Plans and Payments</h2>
            <h3>6.1 Plans</h3>
            <p>We offer the following subscription plans:</p>
            <ul>
                <li><strong>Free Plan:</strong> Basic features with limited customization - Up to 10 links, 5 basic themes, basic analytics</li>
                <li><strong>Pro Plan:</strong> $2.99/month - Full feature access including unlimited links, all themes, custom colors & fonts, advanced analytics, email subscriptions, and custom domains</li>
                <li><strong>Enterprise Plan:</strong> Custom pricing - Coming soon. Includes team collaboration, API access, white-label options, and dedicated support</li>
            </ul>
            
            <h3>6.2 Payment Terms</h3>
            <p>Subscription fees are billed monthly in advance. By subscribing to a paid plan, you agree to pay the applicable subscription fees. All fees are non-refundable except as required by law or as explicitly stated in our refund policy.</p>
            
            <h3>6.3 Automatic Renewal</h3>
            <p>Subscriptions automatically renew at the end of each billing period unless you cancel before the renewal date. You will be charged the then-current subscription fee at the time of renewal.</p>
            
            <h3>6.4 Cancellation</h3>
            <p>You may cancel your subscription at any time through your account settings. Cancellation takes effect at the end of your current billing period. You will continue to have access to paid features until the end of your billing period.</p>
            
            <h3>6.5 Refunds</h3>
            <p>We offer a 30-day money-back guarantee for new subscriptions. If you are not satisfied with our Service within the first 30 days of your initial subscription, contact us for a full refund. Refunds are not available for renewals or after the 30-day period.</p>
            
            <h3>6.6 Price Changes</h3>
            <p>We reserve the right to modify subscription prices at any time. Price changes will not affect your current billing period but will apply to subsequent renewals. We will provide at least 30 days' notice of any price increases.</p>
        </section>

        <section>
            <h2>7. Service Availability and Modifications</h2>
            <h3>7.1 Service Availability</h3>
            <p>We strive to maintain high availability of the Service but do not guarantee uninterrupted, secure, or error-free operation. The Service may be temporarily unavailable due to maintenance, updates, or circumstances beyond our control.</p>
            
            <h3>7.2 Service Modifications</h3>
            <p>We reserve the right to modify, suspend, or discontinue any part of the Service at any time, with or without notice. We are not liable to you or any third party for any modification, suspension, or discontinuation of the Service.</p>
            
            <h3>7.3 Feature Changes</h3>
            <p>We may add, remove, or modify features of the Service at our discretion. Features may vary by subscription plan.</p>
        </section>

        <section>
            <h2>8. Custom Domains</h2>
            <p>If you use a custom domain with your Page:</p>
            <ul>
                <li>You are responsible for configuring DNS settings correctly</li>
                <li>You represent that you own or have the right to use the domain</li>
                <li>We are not responsible for domain registration, renewal, or DNS issues</li>
                <li>We may remove custom domain support if you violate these Terms</li>
            </ul>
        </section>

        <section>
            <h2>9. Third-Party Services and Integrations</h2>
            <p>Our Service may integrate with third-party services (e.g., RSS feeds, email providers, payment processors). Your use of third-party services is subject to their respective terms and conditions. We are not responsible for the availability, accuracy, or practices of third-party services.</p>
        </section>

        <section>
            <h2>10. Termination</h2>
            <h3>10.1 Termination by You</h3>
            <p>You may terminate your account at any time by deleting your account through your account settings or by contacting us.</p>
            
            <h3>10.2 Termination by Us</h3>
            <p>We may suspend or terminate your account immediately, without prior notice, if you:</p>
            <ul>
                <li>Violate these Terms or our Privacy Policy</li>
                <li>Engage in fraudulent, illegal, or harmful activities</li>
                <li>Fail to pay subscription fees when due</li>
                <li>Use the Service in a manner that could harm us or other users</li>
            </ul>
            
            <h3>10.3 Effect of Termination</h3>
            <p>Upon termination:</p>
            <ul>
                <li>Your right to use the Service immediately ceases</li>
                <li>We may delete your account and User Content</li>
                <li>You remain responsible for any fees incurred before termination</li>
                <li>Sections of these Terms that by their nature should survive will survive</li>
            </ul>
        </section>

        <section>
            <h2>11. Disclaimers and Limitation of Liability</h2>
            <h3>11.1 Service "As Is"</h3>
            <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>
            
            <h3>11.2 Limitation of Liability</h3>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, <?php echo h(APP_NAME); ?> SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR INDIRECTLY, OR ANY LOSS OF DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES RESULTING FROM YOUR USE OF THE SERVICE.</p>
            
            <h3>11.3 Maximum Liability</h3>
            <p>OUR TOTAL LIABILITY FOR ANY CLAIMS ARISING FROM OR RELATED TO THE SERVICE SHALL NOT EXCEED THE AMOUNT YOU PAID US IN THE TWELVE (12) MONTHS PRIOR TO THE EVENT GIVING RISE TO THE LIABILITY.</p>
        </section>

        <section>
            <h2>12. Indemnification</h2>
            <p>You agree to indemnify, defend, and hold harmless <?php echo h(APP_NAME); ?>, its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses, including reasonable attorneys' fees, arising out of or in any way connected with:</p>
            <ul>
                <li>Your use of the Service</li>
                <li>Your violation of these Terms</li>
                <li>Your violation of any third-party rights</li>
                <li>Your User Content</li>
            </ul>
        </section>

        <section>
            <h2>13. Dispute Resolution</h2>
            <h3>13.1 Governing Law</h3>
            <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which <?php echo h(APP_NAME); ?> operates, without regard to its conflict of law provisions.</p>
            
            <h3>13.2 Dispute Resolution Process</h3>
            <p>Any disputes arising from or relating to these Terms or the Service shall be resolved through good faith negotiation. If negotiation fails, disputes shall be resolved through binding arbitration in accordance with applicable arbitration rules, except where prohibited by law.</p>
        </section>

        <section>
            <h2>14. Changes to Terms</h2>
            <p>We reserve the right to modify these Terms at any time. We will notify you of material changes by:</p>
            <ul>
                <li>Posting the updated Terms on this page</li>
                <li>Updating the "Last Updated" date</li>
                <li>Sending an email notification to registered users (for significant changes)</li>
            </ul>
            <p>Your continued use of the Service after changes become effective constitutes acceptance of the modified Terms. If you do not agree to the changes, you must stop using the Service and may terminate your account.</p>
        </section>

        <section>
            <h2>15. General Provisions</h2>
            <h3>15.1 Entire Agreement</h3>
            <p>These Terms, together with our Privacy Policy, constitute the entire agreement between you and <?php echo h(APP_NAME); ?> regarding the Service.</p>
            
            <h3>15.2 Severability</h3>
            <p>If any provision of these Terms is found to be unenforceable or invalid, that provision shall be limited or eliminated to the minimum extent necessary, and the remaining provisions shall remain in full force and effect.</p>
            
            <h3>15.3 Waiver</h3>
            <p>Our failure to enforce any right or provision of these Terms shall not constitute a waiver of such right or provision.</p>
            
            <h3>15.4 Assignment</h3>
            <p>You may not assign or transfer these Terms or your account without our prior written consent. We may assign these Terms without restriction.</p>
            
            <h3>15.5 Contact Information</h3>
            <p>If you have questions about these Terms, please contact us at:</p>
            <div class="contact-info">
                <p><strong><?php echo h(APP_NAME); ?></strong><br>
                Email: support@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.bio<br>
                Website: <?php echo APP_URL; ?></p>
            </div>
        </section>
    </div>

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

