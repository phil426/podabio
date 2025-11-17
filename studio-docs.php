<?php
/**
 * PodaBio Studio Documentation
 * Public-facing feature documentation for the Studio experience
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PodaBio Studio Documentation - <?php echo h(APP_NAME); ?></title>
    <meta name="description" content="Learn how to use every feature of PodaBio Studio – the modern editor for building and managing your podcast link-in-bio page.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #111827;
            background: #f3f4f6;
        }

        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            color: #4f46e5;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.25rem 0;
            border-bottom: 2px solid transparent;
            transition: color 0.2s, border-color 0.2s;
        }

        .nav-links a:hover {
            color: #4f46e5;
            border-color: rgba(79,70,229,0.4);
        }

        .nav-links a.active {
            color: #4f46e5;
            border-color: #4f46e5;
        }

        .nav-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #4f46e5;
            color: #f9fafb;
            box-shadow: 0 10px 25px rgba(79,70,229,0.35);
        }

        .btn-primary:hover {
            background: #4338ca;
            box-shadow: 0 14px 35px rgba(79,70,229,0.45);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: #4f46e5;
            border-color: #e5e7eb;
        }

        .btn-secondary:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }

        .page-hero {
            background: radial-gradient(circle at top, #eef2ff 0, #e5e7eb 40%, #f3f4f6 100%);
            padding: 3.5rem 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr);
            gap: 3rem;
            align-items: center;
        }

        .hero-kicker {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 0.75rem;
        }

        .page-hero h1 {
            font-size: 2.5rem;
            line-height: 1.2;
            margin-bottom: 0.75rem;
            color: #111827;
        }

        .page-hero p {
            font-size: 1rem;
            color: #4b5563;
            max-width: 32rem;
            margin-bottom: 1.5rem;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.5rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hero-meta span::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #10b981;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(79,70,229,0.06);
            border-radius: 999px;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            color: #4f46e5;
            margin-bottom: 1rem;
        }

        .hero-layout-card {
            background: white;
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 18px 45px rgba(15,23,42,0.15);
            border: 1px solid rgba(148,163,184,0.35);
        }

        .hero-layout-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .hero-layout-columns {
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 1rem;
            font-size: 0.8rem;
        }

        .hero-layout-col {
            background: #f9fafb;
            border-radius: 12px;
            padding: 0.75rem;
            border: 1px dashed #e5e7eb;
        }

        .hero-layout-col h4 {
            font-size: 0.8rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.35rem;
        }

        .hero-layout-col p {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0;
        }

        .page-body {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 3.5rem;
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
            gap: 2.5rem;
        }

        .toc-card {
            position: sticky;
            top: 6rem;
            align-self: flex-start;
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 12px 30px rgba(148,163,184,0.25);
            border: 1px solid #e5e7eb;
        }

        .toc-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.75rem;
        }

        .toc-description {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .toc-list {
            list-style: none;
            font-size: 0.85rem;
        }

        .toc-list li {
            margin-bottom: 0.5rem;
        }

        .toc-list a {
            color: #4b5563;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toc-list a:hover {
            color: #4f46e5;
        }

        .toc-list a::before {
            content: '●';
            font-size: 0.55rem;
            color: #d1d5db;
        }

        .toc-list li strong {
            font-weight: 600;
        }

        .docs-column {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .docs-section {
            background: white;
            border-radius: 18px;
            padding: 1.75rem 1.75rem 1.5rem;
            box-shadow: 0 20px 55px rgba(15,23,42,0.08);
            border: 1px solid #e5e7eb;
        }

        .docs-section-header {
            margin-bottom: 1rem;
        }

        .docs-section-kicker {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .docs-section h2 {
            font-size: 1.35rem;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .docs-section-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .docs-section + .docs-section {
            margin-top: 0.25rem;
        }

        .article-body {
            font-size: 0.9rem;
            color: #4b5563;
            margin-top: 0.75rem;
        }

        .article-body p {
            margin-bottom: 0.75rem;
        }

        .article-body ul {
            list-style: none;
            padding-left: 0;
            margin: 0.25rem 0 0.75rem;
        }

        .article-body li {
            padding: 0.25rem 0 0.25rem 1.4rem;
            position: relative;
        }

        .article-body li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #4f46e5;
            font-weight: 700;
        }

        .article-body strong {
            color: #111827;
        }

        .article-taglist {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem 0.5rem;
            margin-top: 0.25rem;
            font-size: 0.75rem;
        }

        .article-tag {
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tips-box {
            margin-top: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            background: #f9fafb;
            border: 1px dashed #e5e7eb;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .tips-box strong {
            font-weight: 600;
            color: #4b5563;
        }

        .plan-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #4b5563;
        }

        .plan-pill span {
            display: inline-block;
        }

        .article-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .article-column {
            padding: 0.75rem;
            border-radius: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 0.85rem;
        }

        .article-column h3 {
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
            color: #111827;
        }

        .footer {
            background: #020617;
            color: #e5e7eb;
            padding: 2.5rem 1.5rem 2rem;
            margin-top: 1.5rem;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.75rem;
            font-size: 0.85rem;
        }

        .footer h4 {
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
            color: #e5e7eb;
        }

        .footer ul {
            list-style: none;
        }

        .footer li {
            margin-bottom: 0.35rem;
        }

        .footer a {
            color: #9ca3af;
            text-decoration: none;
        }

        .footer a:hover {
            color: #f9fafb;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 1.75rem auto 0;
            padding-top: 1.25rem;
            border-top: 1px solid #111827;
            font-size: 0.8rem;
            color: #6b7280;
        }

        @media (max-width: 960px) {
            .page-hero-inner {
                grid-template-columns: minmax(0, 1fr);
            }

            .hero-layout-card {
                margin-top: 1.5rem;
            }

            .page-body {
                grid-template-columns: minmax(0, 1fr);
            }

            .toc-card {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .nav {
                padding-inline: 1rem;
            }

            .page-hero {
                padding-inline: 1rem;
            }

            .page-body {
                padding-inline: 1rem;
            }

            .nav-links {
                display: none;
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
                <li><a href="/studio-docs.php" class="active">Studio docs</a></li>
                <li><a href="/about.php">About</a></li>
                <li><a href="/support/">Support</a></li>
            </ul>
            <div class="nav-actions">
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/signup.php" class="btn btn-primary">Get Started</a>
            </div>
        </nav>
    </header>

    <section class="page-hero">
        <div class="page-hero-inner">
            <div>
                <div class="hero-pill">
                    <span>📘 PodaBio Studio guide</span>
                    <span>Updated for the React-based Studio</span>
                </div>
                <div class="hero-kicker">Documentation</div>
                <h1>Learn every part of PodaBio Studio</h1>
                <p>
                    This guide walks you through the full Studio experience—from setting up your page to advanced themes, analytics,
                    email capture, subscriptions, and custom domains.
                </p>
                <div class="hero-meta">
                    <span>Studio shell & navigation</span>
                    <span>Page editor & widgets</span>
                    <span>Account, billing & plans</span>
                </div>
            </div>
            <aside class="hero-layout-card" aria-hidden="true">
                <div class="hero-layout-title">Studio layout at a glance</div>
                <div class="hero-layout-columns">
                    <div class="hero-layout-col">
                        <h4>Left rail</h4>
                        <p>Global navigation between Dashboard, Pages, Analytics, and Settings.</p>
                    </div>
                    <div class="hero-layout-col">
                        <h4>Center & right panels</h4>
                        <p>Main editor canvas on the left with live preview, detail drawers, and account workspace on the right.</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <main class="page-body">
        <aside class="toc-card" aria-label="Page navigation">
            <div class="toc-title">On this page</div>
            <p class="toc-description">Jump to an article to learn a specific area of PodaBio Studio.</p>
            <ul class="toc-list">
                <li><a href="#studio-shell"><strong>Studio shell & navigation</strong></a></li>
                <li><a href="#page-editor"><strong>Page editor & layout</strong></a></li>
                <li><a href="#links-widgets"><strong>Links, widgets & podcast directories</strong></a></li>
                <li><a href="#rss-player"><strong>RSS import & podcast player</strong></a></li>
                <li><a href="#themes-appearance"><strong>Themes, colors & appearance</strong></a></li>
                <li><a href="#email-subscription"><strong>Email subscriptions & drawer</strong></a></li>
                <li><a href="#analytics"><strong>Analytics & insights</strong></a></li>
                <li><a href="#custom-domains"><strong>Custom domains</strong></a></li>
                <li><a href="#account-workspace"><strong>Account, security & billing</strong></a></li>
                <li><a href="#plans-payments"><strong>Plans, payments & upgrades</strong></a></li>
            </ul>
        </aside>

        <div class="docs-column">
            <section id="studio-shell" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 1</div>
                    <h2>PodaBio Studio shell & navigation</h2>
                    <div class="docs-section-subtitle">
                        Understand the main Studio layout and how to move between editing, analytics, and account tools.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        PodaBio Studio is the modern React-based admin experience that replaces the legacy dashboard.
                        When you log in, Studio opens as a shell around your page editor, analytics, and account tools.
                    </p>
                    <div class="article-columns">
                        <div class="article-column">
                            <h3>Top bar</h3>
                            <p>
                                The top bar shows your workspace name, quick actions, and the account menu. Use the avatar menu
                                to open the <strong>Account workspace</strong> (Profile, Security, Billing).
                            </p>
                        </div>
                        <div class="article-column">
                            <h3>Left navigation</h3>
                            <p>
                                Use the left rail to switch between the Dashboard, Pages, Analytics, and Studio Settings without leaving the shell.
                            </p>
                        </div>
                        <div class="article-column">
                            <h3>Center & preview</h3>
                            <p>
                                The center panel is where you make changes; the live preview on the right shows how your page renders on poda.bio.
                            </p>
                        </div>
                    </div>
                    <div class="article-taglist">
                        <span class="article-tag">Navigation</span>
                        <span class="article-tag">Studio shell</span>
                        <span class="article-tag">Top bar</span>
                    </div>
                    <div class="tips-box">
                        <strong>Tip:</strong> After login or signup, you’ll be redirected straight into Studio. If you still see the legacy editor,
                        contact support and mention the Studio experience.
                    </div>
                </div>
            </section>

            <section id="page-editor" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 2</div>
                    <h2>Page editor & layout</h2>
                    <div class="docs-section-subtitle">
                        Learn how to edit your link-in-bio page, from hero content to sections and layout.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        The page editor is the heart of PodaBio Studio. It lets you manage your profile, hero section, layout, and
                        core content blocks from a single screen, with instant visual feedback.
                    </p>
                    <ul>
                        <li><strong>Profile & headline:</strong> Set your podcast name, tagline, and profile image so visitors immediately recognize your brand.</li>
                        <li><strong>Hero content:</strong> Configure your primary call-to-action, featured links, or latest episode highlight.</li>
                        <li><strong>Sections & order:</strong> Enable/disable content sections and reorder them to match your storytelling flow.</li>
                    </ul>
                    <p>
                        All edits are saved to your PodaBio page in real time or via explicit save, depending on your workspace settings.
                        The preview panel shows mobile-first rendering so you know exactly what listeners will see from social profiles.
                    </p>
                    <div class="article-taglist">
                        <span class="article-tag">Page editor</span>
                        <span class="article-tag">Layout</span>
                    </div>
                </div>
            </section>

            <section id="links-widgets" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 3</div>
                    <h2>Links, widgets & podcast directories</h2>
                    <div class="docs-section-subtitle">
                        Add links, manage widgets, and keep all of your podcast directory buttons in one place.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        PodaBio Studio uses a flexible widget system for links and content blocks. Each row on your page is a widget:
                        links, podcast directory buttons, email subscription, sponsor callouts, and more.
                    </p>
                    <ul>
                        <li><strong>Standard links:</strong> Add custom links for episodes, websites, sponsors, or socials. Reorder them with drag-and-drop.</li>
                        <li><strong>Affiliate & sponsor links:</strong> Use dedicated widgets to add disclosure text and tracking-friendly labels.</li>
                        <li><strong>Podcast directories:</strong> Manage icons and URLs for Apple Podcasts, Spotify, YouTube Music, Amazon Music, Overcast, Pocket Casts, and other platforms.</li>
                    </ul>
                    <p>
                        To edit a widget, select it in the editor list. Use the right-hand configuration drawer to set titles, URLs,
                        thumbnails, and visibility rules (for example, only show certain links on desktop).
                    </p>
                    <div class="article-taglist">
                        <span class="article-tag">Widgets</span>
                        <span class="article-tag">Podcast directories</span>
                        <span class="article-tag">Links</span>
                    </div>
                </div>
            </section>

            <section id="rss-player" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 4</div>
                    <h2>RSS import & podcast player</h2>
                    <div class="docs-section-subtitle">
                        Connect your podcast RSS feed and let Studio build your player and episode list automatically.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        Studio includes a built-in RSS parser and a beautiful Shikwasa.js-based podcast player. Once you connect your feed,
                        PodaBio keeps your episode list in sync with what’s published in your podcast host.
                    </p>
                    <ul>
                        <li><strong>Connect your RSS feed:</strong> Paste your public RSS URL into the Feed settings, then confirm the detected show name and artwork.</li>
                        <li><strong>Automatic episode imports:</strong> New episodes are imported with title, description, publish date, and duration.</li>
                        <li><strong>Player layout:</strong> Visitors get an on-page player with an episode drawer and a mini-player that follows them as they scroll.</li>
                    </ul>
                    <div class="tips-box">
                        <strong>Tip:</strong> If episode data looks out of date, trigger a manual refresh in the RSS section or wait for the next scheduled sync window.
                    </div>
                    <div class="article-taglist">
                        <span class="article-tag">RSS</span>
                        <span class="article-tag">Podcast player</span>
                    </div>
                </div>
            </section>

            <section id="themes-appearance" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 5</div>
                    <h2>Themes, colors & appearance</h2>
                    <div class="docs-section-subtitle">
                        Customize fonts, colors, and layout to match your podcast brand.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        The enhanced theme system in Studio gives you full control over your page’s visual identity while staying easy to use.
                        You can start from a pre-built theme and then tune colors, fonts, and backgrounds.
                    </p>
                    <ul>
                        <li><strong>Theme presets:</strong> Choose from curated themes optimized for podcasters. Each preset sets fonts, color tokens, and spacing.</li>
                        <li><strong>Color pickers:</strong> Use primary, secondary, and accent color pickers with small square swatches that mirror what listeners will see.</li>
                        <li><strong>Fonts:</strong> Pick from 15+ Google Fonts for headings and body text to match your show art.</li>
                        <li><strong>Backgrounds:</strong> Upload background images or pick solid/gradient backgrounds while keeping text accessible.</li>
                    </ul>
                    <p>
                        All theme changes are visible immediately in the live preview, so you can experiment safely before publishing.
                    </p>
                    <div class="article-taglist">
                        <span class="article-tag">Themes</span>
                        <span class="article-tag">Color pickers</span>
                        <span class="article-tag">Fonts</span>
                    </div>
                </div>
            </section>

            <section id="email-subscription" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 6</div>
                    <h2>Email subscriptions & drawer</h2>
                    <div class="docs-section-subtitle">
                        Capture listener emails with a built-in drawer slider and connect to your email service.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        PodaBio Studio includes a subscription drawer that slides in from the side to collect listener email addresses
                        without sending them away from your page.
                    </p>
                    <ul>
                        <li><strong>Enable the subscription widget:</strong> Turn on the email subscription widget in the editor and choose where the entry point appears.</li>
                        <li><strong>Connect your provider:</strong> Integrate with Mailchimp, Constant Contact, ConvertKit, AWeber, MailerLite, or Brevo/SendinBlue.</li>
                        <li><strong>Drawer experience:</strong> When visitors tap your subscribe call-to-action, a drawer opens with name and email fields and any required consent text.</li>
                    </ul>
                    <p>
                        Submissions are tracked in your analytics, so you can attribute subscriber growth back to specific campaigns and links.
                    </p>
                    <div class="article-taglist">
                        <span class="article-tag">Email</span>
                        <span class="article-tag">Subscriptions</span>
                        <span class="article-tag">Drawer</span>
                    </div>
                </div>
            </section>

            <section id="analytics" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 7</div>
                    <h2>Analytics & insights</h2>
                    <div class="docs-section-subtitle">
                        Track page views, link clicks, and subscriber growth to understand what’s working.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        Studio’s analytics layer tracks key metrics for your PodaBio page so you can see which campaigns and links
                        drive the most engagement.
                    </p>
                    <ul>
                        <li><strong>Page views:</strong> Total visits and unique visitors over time.</li>
                        <li><strong>Link clicks:</strong> Per-link click counts so you can identify your top performers.</li>
                        <li><strong>Email performance:</strong> Subscriber signups tied back to sessions on your page.</li>
                    </ul>
                    <p>
                        All plans include basic analytics. Pro plans unlock longer history, richer breakdowns, and export options for deeper analysis.
                    </p>
                    <div class="article-taglist">
                        <span class="article-tag">Analytics</span>
                        <span class="article-tag">Insights</span>
                    </div>
                </div>
            </section>

            <section id="custom-domains" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 8</div>
                    <h2>Custom domains</h2>
                    <div class="docs-section-subtitle">
                        Use your own domain name instead of a poda.bio URL.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        Custom domains are available on paid plans and let you host your PodaBio page at your own brand URL, such as
                        <code>link.yourshow.com</code>.
                    </p>
                    <p class="plan-pill">
                        <span>Included on:</span>
                        <span>Pro plans and above</span>
                    </p>
                    <ul>
                        <li><strong>Add your domain:</strong> Enter the domain you want to use in the Custom Domain settings.</li>
                        <li><strong>DNS verification:</strong> Follow the DNS record instructions (CNAME or A record) to prove ownership.</li>
                        <li><strong>Routing:</strong> Once verified, traffic to your domain is automatically routed to your PodaBio page.</li>
                    </ul>
                    <div class="tips-box">
                        <strong>Tip:</strong> DNS changes can take up to 24 hours to propagate. If your domain doesn’t resolve after that,
                        double-check the records from your Studio instructions.
                    </div>
                    <div class="article-taglist">
                        <span class="article-tag">Custom domains</span>
                        <span class="article-tag">DNS</span>
                    </div>
                </div>
            </section>

            <section id="account-workspace" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 9</div>
                    <h2>Account workspace: profile, security & billing</h2>
                    <div class="docs-section-subtitle">
                        Manage your account details, login methods, and subscription from the Studio account workspace.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        The account workspace in Studio lives behind the avatar menu in the top bar. When you click
                        <strong>Account</strong>, a three-tab workspace opens with Profile, Security, and Billing views.
                    </p>
                    <div class="article-columns">
                        <div class="article-column">
                            <h3>Profile</h3>
                            <p>
                                Review your account email and plan details. Profile editing is rolling out in phases; if you need to change
                                your email or show name and don’t see edit controls yet, contact support.
                            </p>
                        </div>
                        <div class="article-column">
                            <h3>Security</h3>
                            <p>
                                See whether your account has a password set, Google linked, and (upcoming) 2FA status. From here you can
                                link Google, reset your password, or manage login methods via confirmation drawers.
                            </p>
                        </div>
                        <div class="article-column">
                            <h3>Billing</h3>
                            <p>
                                View your current plan, renewal date, payment method, and recent invoices. Upgrade and billing support
                                links open the payment checkout and billing help flows.
                            </p>
                        </div>
                    </div>
                    <div class="tips-box">
                        <strong>Note:</strong> If your account workspace opens in the legacy editor instead of the Studio layout,
                        your workspace may still be on the classic path. Use the Support link to request Studio access.
                    </div>
                    <div class="article-taglist">
                        <span class="article-tag">Account workspace</span>
                        <span class="article-tag">Security</span>
                        <span class="article-tag">Billing</span>
                    </div>
                </div>
            </section>

            <section id="plans-payments" class="docs-section">
                <header class="docs-section-header">
                    <div class="docs-section-kicker">Article 10</div>
                    <h2>Plans, payments & upgrades</h2>
                    <div class="docs-section-subtitle">
                        Understand how subscriptions work and how to change plans from Studio.
                    </div>
                </header>
                <div class="article-body">
                    <p>
                        PodaBio uses subscription plans to unlock additional features like advanced analytics, custom domains,
                        and higher usage limits. You can manage your plan entirely from the Billing tab in the account workspace.
                    </p>
                    <ul>
                        <li><strong>Viewing your plan:</strong> The Billing tab shows your current plan, renewal date, and billing status.</li>
                        <li><strong>Upgrading:</strong> Use the <em>Upgrade plan</em> button to open the secure checkout page. Your Studio session remains active in a separate tab.</li>
                        <li><strong>Invoices:</strong> Review your recent invoices directly in the Billing tab and download copies if enabled for your account.</li>
                        <li><strong>Help with charges:</strong> Use the <em>Contact support</em> link to open the billing help form if you need assistance.</li>
                    </ul>
                    <div class="article-taglist">
                        <span class="article-tag">Plans</span>
                        <span class="article-tag">Payments</span>
                        <span class="article-tag">Billing</span>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <div>
                <h4><?php echo h(APP_NAME); ?></h4>
                <p>The link-in-bio platform built for podcasters.</p>
            </div>
            <div>
                <h4>Product</h4>
                <ul>
                    <li><a href="/features.php">Features</a></li>
                    <li><a href="/pricing.php">Pricing</a></li>
                    <li><a href="/studio-docs.php">Studio docs</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/blog/">Blog</a></li>
                </ul>
            </div>
            <div>
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


