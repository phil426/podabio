<?php
/**
 * Privacy Policy
 * PodaBio - Privacy Policy and Data Protection
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Privacy Policy for PodaBio - How we collect, use, and protect your data.">
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
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--poda-border-subtle);
        }
        
        .data-table th {
            background: var(--poda-bg-primary);
            font-weight: 600;
            color: var(--poda-text-primary);
        }
        
        .data-table td {
            color: var(--poda-text-secondary);
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
        <h1>Privacy Policy</h1>
        <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>

        <section>
            <h2>1. Introduction</h2>
            <p>At <?php echo h(APP_NAME); ?> ("we," "us," or "our"), we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our link-in-bio platform and services (the "Service").</p>
            <p>By using our Service, you agree to the collection and use of information in accordance with this Privacy Policy. If you do not agree with our policies and practices, please do not use our Service.</p>
        </section>

        <section>
            <h2>2. Information We Collect</h2>
            <h3>2.1 Information You Provide</h3>
            <p>We collect information that you provide directly to us, including:</p>
            <ul>
                <li><strong>Account Information:</strong> Name, email address, username, password, and profile information</li>
                <li><strong>Page Content:</strong> Text, images, links, podcast information, RSS feed URLs, and other content you add to your Page</li>
                <li><strong>Payment Information:</strong> Billing address, payment method details (processed securely through third-party payment processors)</li>
                <li><strong>Communication Data:</strong> Messages, support requests, and other communications you send to us</li>
                <li><strong>Custom Domain Information:</strong> Domain names you connect to your Page</li>
            </ul>
            
            <h3>2.2 Automatically Collected Information</h3>
            <p>When you use our Service, we automatically collect certain information, including:</p>
            <ul>
                <li><strong>Usage Data:</strong> Pages visited, features used, time spent on pages, click patterns, and navigation paths</li>
                <li><strong>Device Information:</strong> IP address, browser type and version, device type, operating system, and device identifiers</li>
                <li><strong>Analytics Data:</strong> Page views, link clicks, visitor locations (country/city level), referral sources, and engagement metrics</li>
                <li><strong>Technical Data:</strong> Log files, error reports, performance data, and system information</li>
                <li><strong>Cookies and Tracking Technologies:</strong> See Section 8 for details</li>
            </ul>
            
            <h3>2.3 Information from Third-Party Services</h3>
            <p>If you connect third-party services to your account, we may receive information from those services, such as:</p>
            <ul>
                <li><strong>RSS Feed Data:</strong> Podcast episodes, titles, descriptions, artwork, and metadata from your RSS feed</li>
                <li><strong>Social Media Data:</strong> Profile information if you authenticate through social media platforms</li>
                <li><strong>Email Service Data:</strong> Contact lists and subscriber information if you integrate email marketing services</li>
            </ul>
        </section>

        <section>
            <h2>3. How We Use Your Information</h2>
            <p>We use the information we collect for the following purposes:</p>
            <ul>
                <li><strong>Service Provision:</strong> To create, maintain, and operate your account and Pages</li>
                <li><strong>RSS Feed Processing:</strong> To fetch, parse, and display content from your RSS feeds</li>
                <li><strong>Customization:</strong> To apply your selected themes, settings, and preferences</li>
                <li><strong>Analytics:</strong> To provide you with analytics and insights about your Page performance</li>
                <li><strong>Communication:</strong> To send you service-related notifications, updates, and support responses</li>
                <li><strong>Payment Processing:</strong> To process subscription payments and manage billing</li>
                <li><strong>Security:</strong> To detect, prevent, and address security issues and fraudulent activity</li>
                <li><strong>Improvement:</strong> To analyze usage patterns and improve our Service</li>
                <li><strong>Legal Compliance:</strong> To comply with legal obligations and enforce our Terms of Service</li>
                <li><strong>Marketing:</strong> To send you promotional communications (with your consent, where required)</li>
            </ul>
        </section>

        <section>
            <h2>4. How We Share Your Information</h2>
            <p>We do not sell your personal information. We may share your information in the following circumstances:</p>
            
            <h3>4.1 Public Information</h3>
            <p>Content you publish on your Page is publicly accessible. This includes:</p>
            <ul>
                <li>Your Page URL and username</li>
                <li>Content displayed on your Page (text, images, links, podcast episodes)</li>
                <li>Public profile information you choose to display</li>
            </ul>
            
            <h3>4.2 Service Providers</h3>
            <p>We may share information with third-party service providers who perform services on our behalf, including:</p>
            <ul>
                <li><strong>Hosting Providers:</strong> To host and deliver our Service</li>
                <li><strong>Payment Processors:</strong> To process subscription payments (e.g., PayPal)</li>
                <li><strong>Analytics Services:</strong> To analyze usage and improve our Service</li>
                <li><strong>Email Services:</strong> To send transactional and marketing emails</li>
                <li><strong>CDN Providers:</strong> To deliver content efficiently</li>
            </ul>
            <p>These service providers are contractually obligated to protect your information and use it only for the purposes we specify.</p>
            
            <h3>4.3 Legal Requirements</h3>
            <p>We may disclose your information if required by law or in response to:</p>
            <ul>
                <li>Legal processes, such as court orders or subpoenas</li>
                <li>Government requests or regulatory requirements</li>
                <li>Protection of our rights, property, or safety</li>
                <li>Protection of our users' rights, property, or safety</li>
            </ul>
            
            <h3>4.4 Business Transfers</h3>
            <p>If we are involved in a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction. We will notify you of any such change in ownership.</p>
        </section>

        <section>
            <h2>5. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your information, including:</p>
            <ul>
                <li>Encryption of data in transit (SSL/TLS)</li>
                <li>Secure password storage using industry-standard hashing</li>
                <li>Regular security assessments and updates</li>
                <li>Access controls and authentication measures</li>
                <li>Secure payment processing through certified providers</li>
            </ul>
            <p>However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.</p>
        </section>

        <section>
            <h2>6. Your Rights and Choices</h2>
            <h3>6.1 Access and Correction</h3>
            <p>You can access and update your account information at any time through your account settings.</p>
            
            <h3>6.2 Data Deletion</h3>
            <p>You can delete your account and associated data at any time through your account settings. Upon deletion:</p>
            <ul>
                <li>Your account will be permanently deleted</li>
                <li>Your Page and all associated content will be removed</li>
                <li>We will delete your personal information, subject to legal retention requirements</li>
            </ul>
            
            <h3>6.3 Marketing Communications</h3>
            <p>You can opt out of marketing emails by:</p>
            <ul>
                <li>Clicking the unsubscribe link in any marketing email</li>
                <li>Updating your email preferences in your account settings</li>
                <li>Contacting us directly</li>
            </ul>
            <p>You cannot opt out of service-related communications (e.g., account updates, security alerts).</p>
            
            <h3>6.4 Cookies and Tracking</h3>
            <p>You can control cookies through your browser settings. See Section 8 for more information.</p>
            
            <h3>6.5 Regional Rights</h3>
            <p>Depending on your location, you may have additional rights under applicable data protection laws (e.g., GDPR, CCPA), including:</p>
            <ul>
                <li>Right to access your personal data</li>
                <li>Right to rectification (correction)</li>
                <li>Right to erasure ("right to be forgotten")</li>
                <li>Right to restrict processing</li>
                <li>Right to data portability</li>
                <li>Right to object to processing</li>
                <li>Right to withdraw consent</li>
            </ul>
            <p>To exercise these rights, please contact us using the information in Section 12.</p>
        </section>

        <section>
            <h2>7. Data Retention</h2>
            <p>We retain your information for as long as necessary to:</p>
            <ul>
                <li>Provide our Service to you</li>
                <li>Comply with legal obligations</li>
                <li>Resolve disputes and enforce our agreements</li>
                <li>Maintain security and prevent fraud</li>
            </ul>
            <p>When you delete your account, we will delete or anonymize your personal information within 30 days, except where we are required to retain it for legal purposes.</p>
            <p>Analytics and aggregated data may be retained longer for business and analytical purposes, but in anonymized form that cannot identify you.</p>
        </section>

        <section>
            <h2>8. Cookies and Tracking Technologies</h2>
            <h3>8.1 Types of Cookies We Use</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Purpose</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Essential Cookies</td>
                        <td>Required for the Service to function (authentication, security, preferences)</td>
                        <td>Session or up to 1 year</td>
                    </tr>
                    <tr>
                        <td>Analytics Cookies</td>
                        <td>Help us understand how users interact with our Service</td>
                        <td>Up to 2 years</td>
                    </tr>
                    <tr>
                        <td>Functional Cookies</td>
                        <td>Remember your preferences and settings</td>
                        <td>Up to 1 year</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>8.2 Third-Party Tracking</h3>
            <p>We may use third-party analytics services (such as Google Analytics) that use cookies and similar technologies to collect and analyze information about Service usage. These services may have their own privacy policies.</p>
            
            <h3>8.3 Managing Cookies</h3>
            <p>You can control cookies through your browser settings. Note that disabling certain cookies may affect the functionality of our Service.</p>
        </section>

        <section>
            <h2>9. Children's Privacy</h2>
            <p>Our Service is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.</p>
            <p>If we become aware that we have collected personal information from a child under 13, we will take steps to delete such information promptly.</p>
        </section>

        <section>
            <h2>10. International Data Transfers</h2>
            <p>Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that differ from those in your country.</p>
            <p>By using our Service, you consent to the transfer of your information to these countries. We take appropriate measures to ensure your information receives adequate protection in accordance with this Privacy Policy.</p>
        </section>

        <section>
            <h2>11. Third-Party Links and Services</h2>
            <p>Our Service may contain links to third-party websites or integrate with third-party services. We are not responsible for the privacy practices of these third parties. We encourage you to review their privacy policies before providing any information.</p>
            <p>When you click on links on your Page or use integrated services (e.g., RSS feeds, email providers), you are subject to those third parties' privacy policies and terms of service.</p>
        </section>

        <section>
            <h2>12. Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of material changes by:</p>
            <ul>
                <li>Posting the updated Privacy Policy on this page</li>
                <li>Updating the "Last Updated" date</li>
                <li>Sending an email notification to registered users (for significant changes)</li>
            </ul>
            <p>Your continued use of the Service after changes become effective constitutes acceptance of the updated Privacy Policy. If you do not agree to the changes, you must stop using the Service and may delete your account.</p>
        </section>

        <section>
            <h2>13. Contact Us</h2>
            <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
            <div class="contact-info">
                <p><strong><?php echo h(APP_NAME); ?></strong><br>
                Email: privacy@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.bio<br>
                Support: support@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.bio<br>
                Website: <?php echo APP_URL; ?></p>
            </div>
            <p>For data protection inquiries or to exercise your privacy rights, please include "Privacy Request" in your subject line and provide details about your request.</p>
        </section>

        <section>
            <h2>14. California Privacy Rights (CCPA)</h2>
            <p>If you are a California resident, you have the following rights under the California Consumer Privacy Act (CCPA):</p>
            <ul>
                <li><strong>Right to Know:</strong> Request information about the categories and specific pieces of personal information we collect, use, and disclose</li>
                <li><strong>Right to Delete:</strong> Request deletion of your personal information</li>
                <li><strong>Right to Opt-Out:</strong> Opt out of the sale of personal information (we do not sell personal information)</li>
                <li><strong>Right to Non-Discrimination:</strong> We will not discriminate against you for exercising your privacy rights</li>
            </ul>
            <p>To exercise these rights, please contact us using the information in Section 13.</p>
        </section>

        <section>
            <h2>15. European Privacy Rights (GDPR)</h2>
            <p>If you are located in the European Economic Area (EEA), you have the following rights under the General Data Protection Regulation (GDPR):</p>
            <ul>
                <li>Right to access your personal data</li>
                <li>Right to rectification of inaccurate data</li>
                <li>Right to erasure ("right to be forgotten")</li>
                <li>Right to restrict processing</li>
                <li>Right to data portability</li>
                <li>Right to object to processing</li>
                <li>Right to withdraw consent</li>
                <li>Right to lodge a complaint with a supervisory authority</li>
            </ul>
            <p>Our legal basis for processing your information includes:</p>
            <ul>
                <li>Performance of a contract (providing our Service)</li>
                <li>Legitimate interests (security, analytics, service improvement)</li>
                <li>Consent (marketing communications, where applicable)</li>
                <li>Legal obligations (compliance with laws)</li>
            </ul>
            <p>To exercise these rights, please contact us using the information in Section 13.</p>
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

