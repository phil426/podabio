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

    <div id="root" style="min-height: calc(100vh - 200px);">
        <div style="display: flex; align-items: center; justify-content: center; min-height: 400px; color: #6b7280;">
            <p>Loading documentation...</p>
        </div>
    </div>

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

    <?php
    // Load React app for documentation viewer
    $manifestPath = __DIR__ . '/admin-ui/dist/.vite/manifest.json';
    $scriptSrc = null;
    $cssHref = null;

    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (isset($manifest['index.html'])) {
            $entry = $manifest['index.html'];
            $scriptSrc = '/admin-ui/dist/' . $entry['file'];
            if (!empty($entry['css'])) {
                $cssHref = '/admin-ui/dist/' . $entry['css'][0];
            }
        }
    }

    // Check if Vite dev server is running (for development)
    $isDev = !$scriptSrc;
    if (!$isDev) {
        $devServerRunning = @fsockopen('localhost', 5174, $errno, $errstr, 0.1);
        if ($devServerRunning) {
            fclose($devServerRunning);
            $isDev = true;
            $scriptSrc = null;
        }
    }
    if ($isDev) {
        $scriptSrc = 'http://localhost:5174/src/main.tsx';
    }
    ?>

    <?php if ($cssHref): ?>
        <link rel="stylesheet" href="<?php echo h($cssHref); ?>">
    <?php endif; ?>

    <script>
        // Set up routing for documentation viewer
        if (window.location.pathname === '/studio-docs.php' || window.location.pathname === '/studio-docs') {
            // Redirect to React app route
            if (!window.location.search && !window.location.hash) {
                window.history.replaceState({}, '', '/studio-docs');
            }
        }
    </script>

    <?php if ($isDev): ?>
        <script type="module" src="<?php echo h($scriptSrc); ?>"></script>
    <?php else: ?>
        <script type="module" src="<?php echo h($scriptSrc); ?>"></script>
    <?php endif; ?>
</body>
</html>


