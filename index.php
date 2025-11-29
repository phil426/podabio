<?php
/**
 * Homepage - PodaBio Marketing Landing Page
 * Conversion-focused landing page showcasing PodaBio as the podcast-first link-in-bio platform
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
    <title><?php echo h(APP_NAME); ?> - The Link-in-Bio Platform Built for Podcasters</title>
    <meta name="description" content="One beautiful page. All your links, episodes, and resources. Automatically synced from your RSS feed. Built specifically for podcasters.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wdth,wght@75..100,800&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/marketing-dark.css?v=<?php echo filemtime(__DIR__ . '/css/marketing-dark.css'); ?>">
    <?php
    // Load React marketing navigation component
    require_once __DIR__ . '/config/spa-config.php';
    
    $viteDevServerRunning = isViteDevServerRunning();
    $isDev = $viteDevServerRunning || isSPADevMode();
    
    if ($isDev) {
        // Development: Load React Refresh and Vite client first, then the component
        $refreshUrl = getDevServerRefreshUrl();
        $viteClientUrl = getDevServerViteClientUrl();
        ?>
        <script type="module">
            import RefreshRuntime from "<?php echo htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8'); ?>";
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>
        <script type="module" src="<?php echo htmlspecialchars($viteClientUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <script type="module" src="http://localhost:5174/src/marketing-nav.tsx"></script>
        <script type="module" src="http://localhost:5174/src/marketing-icons.tsx"></script>
        <script type="module" src="http://localhost:5174/src/smooth-scroll.tsx"></script>
        <?php
    } else {
        // Production: Load from built files
        $manifestPath = __DIR__ . '/admin-ui/dist/.vite/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['src/marketing-nav.tsx'])) {
                $entry = $manifest['src/marketing-nav.tsx'];
                if (isset($entry['file'])) {
                    echo '<script type="module" src="/admin-ui/dist/' . htmlspecialchars($entry['file']) . '"></script>';
                }
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/admin-ui/dist/' . htmlspecialchars($cssFile) . '">';
                    }
                }
            }
            if (isset($manifest['src/marketing-icons.tsx'])) {
                $entry = $manifest['src/marketing-icons.tsx'];
                if (isset($entry['file'])) {
                    echo '<script type="module" src="/admin-ui/dist/' . htmlspecialchars($entry['file']) . '"></script>';
                }
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/admin-ui/dist/' . htmlspecialchars($cssFile) . '">';
                    }
                }
            }
            if (isset($manifest['src/smooth-scroll.tsx'])) {
                $entry = $manifest['src/smooth-scroll.tsx'];
                if (isset($entry['file'])) {
                    echo '<script type="module" src="/admin-ui/dist/' . htmlspecialchars($entry['file']) . '"></script>';
                }
                if (isset($entry['css']) && is_array($entry['css'])) {
                    foreach ($entry['css'] as $cssFile) {
                        echo '<link rel="stylesheet" href="/admin-ui/dist/' . htmlspecialchars($cssFile) . '">';
                    }
                }
            }
        }
    }
    ?>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Scroll Animation Styles */
        .scroll-animate {
            opacity: 0;
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        
        .scroll-animate.animate {
            opacity: 1;
        }
        
        /* Fade In */
        .scroll-animate[data-animate="fade"] {
            opacity: 0;
        }
        
        .scroll-animate[data-animate="fade"].animate {
            opacity: 1;
        }
        
        /* Slide Up */
        .scroll-animate[data-animate="slide-up"] {
            transform: translateY(60px);
        }
        
        .scroll-animate[data-animate="slide-up"].animate {
            transform: translateY(0);
        }
        
        /* Slide Down */
        .scroll-animate[data-animate="slide-down"] {
            transform: translateY(-60px);
        }
        
        .scroll-animate[data-animate="slide-down"].animate {
            transform: translateY(0);
        }
        
        /* Slide Left */
        .scroll-animate[data-animate="slide-left"] {
            transform: translateX(60px);
        }
        
        .scroll-animate[data-animate="slide-left"].animate {
            transform: translateX(0);
        }
        
        /* Slide Right */
        .scroll-animate[data-animate="slide-right"] {
            transform: translateX(-60px);
        }
        
        .scroll-animate[data-animate="slide-right"].animate {
            transform: translateX(0);
        }
        
        /* Scale */
        .scroll-animate[data-animate="scale"] {
            transform: scale(0.8);
        }
        
        .scroll-animate[data-animate="scale"].animate {
            transform: scale(1);
        }
        
        /* Reveal (clip-path) */
        .scroll-animate[data-animate="reveal"] {
            clip-path: inset(0 0 100% 0);
        }
        
        .scroll-animate[data-animate="reveal"].animate {
            clip-path: inset(0 0 0% 0);
        }
        
        /* Fade + Slide Up (most common) */
        .scroll-animate[data-animate="fade-slide-up"] {
            opacity: 0;
            transform: translateY(40px);
        }
        
        .scroll-animate[data-animate="fade-slide-up"].animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Rotate */
        .scroll-animate[data-animate="rotate"] {
            transform: rotate(-5deg) scale(0.9);
        }
        
        .scroll-animate[data-animate="rotate"].animate {
            transform: rotate(0deg) scale(1);
        }
        
        /* Scale + Rotate */
        .scroll-animate[data-animate="scale-rotate"] {
            opacity: 0;
            transform: scale(0.8) rotate(10deg);
        }
        
        .scroll-animate[data-animate="scale-rotate"].animate {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
        
        /* Slide from corner */
        .scroll-animate[data-animate="slide-corner"] {
            opacity: 0;
            transform: translate(60px, 60px);
        }
        
        .scroll-animate[data-animate="slide-corner"].animate {
            opacity: 1;
            transform: translate(0, 0);
        }
        
        /* Stagger delay classes for grid items */
        .scroll-animate[data-delay="0"] { transition-delay: 0ms; }
        .scroll-animate[data-delay="100"] { transition-delay: 100ms; }
        .scroll-animate[data-delay="200"] { transition-delay: 200ms; }
        .scroll-animate[data-delay="300"] { transition-delay: 300ms; }
        .scroll-animate[data-delay="400"] { transition-delay: 400ms; }
        .scroll-animate[data-delay="500"] { transition-delay: 500ms; }
        .scroll-animate[data-delay="600"] { transition-delay: 600ms; }
        .scroll-animate[data-delay="700"] { transition-delay: 700ms; }
        .scroll-animate[data-delay="800"] { transition-delay: 800ms; }
        .scroll-animate[data-delay="900"] { transition-delay: 900ms; }
        .scroll-animate[data-delay="1000"] { transition-delay: 1000ms; }
        .scroll-animate[data-delay="1100"] { transition-delay: 1100ms; }
        .scroll-animate[data-delay="1200"] { transition-delay: 1200ms; }
        .scroll-animate[data-delay="1300"] { transition-delay: 1300ms; }
        .scroll-animate[data-delay="1400"] { transition-delay: 1400ms; }
        
        /* Homepage-specific styles - Dark Theme */
        
        .homepage-hero {
            background: var(--poda-bg-primary);
            color: var(--poda-text-primary);
            padding: 8rem 2rem 10rem;
            text-align: center;
            position: relative;
            overflow: visible;
        }
        
        .homepage-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6rem;
            background: linear-gradient(to bottom, transparent, var(--poda-bg-primary));
            pointer-events: none;
            z-index: 1;
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
            z-index: 2;
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
        
        .username-claim-container {
            margin-bottom: 8rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }
        
        .username-claim-box {
            background: rgba(26, 26, 26, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        
        .username-claim-box:focus-within {
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(0, 255, 127, 0.15);
        }
        
        .username-input-group {
            display: flex;
            align-items: center;
            flex: 1;
            gap: 0.75rem;
            min-width: 0;
            height: 100%;
            position: relative;
        }
        
        .username-prefix {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
            flex-shrink: 0;
            line-height: 1;
            height: 100%;
        }
        
        .poda-logo-text {
            font-family: var(--poda-font-heading);
            font-weight: 800;
            font-size: 1rem;
            color: var(--poda-text-primary);
            letter-spacing: -0.02em;
            line-height: 1;
            display: inline-block;
        }
        
        .username-slash {
            color: var(--poda-text-secondary);
            font-size: 1rem;
            font-weight: 400;
            line-height: 1;
            display: inline-block;
        }
        
        .username-input {
            background: rgba(18, 18, 18, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            outline: none;
            color: var(--poda-text-primary);
            font-family: var(--poda-font-body);
            font-size: 1rem;
            flex: 1;
            min-width: 120px;
            padding: 0.625rem 0.875rem;
            padding-right: 2.5rem;
            transition: all 0.3s;
            line-height: 1.5;
            height: auto;
        }
        
        .username-input::placeholder {
            color: var(--poda-text-muted);
        }
        
        .username-input:focus {
            border-color: var(--poda-accent-signal-green);
            background: rgba(18, 18, 18, 0.9);
            box-shadow: 0 0 0 2px rgba(0, 255, 127, 0.1);
        }
        
        .username-input.available {
            border-color: var(--poda-accent-signal-green);
        }
        
        .username-input.unavailable {
            border-color: #ff4444;
        }
        
        .username-input.checking {
            border-color: var(--poda-text-secondary);
        }
        
        .username-status {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 2;
            width: 20px;
            height: 20px;
        }
        
        .username-claim-btn {
            background: var(--poda-accent-signal-green);
            color: var(--poda-bg-primary);
            font-family: var(--poda-font-body);
            font-size: 0.95rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: auto;
        }
        
        .username-claim-btn:hover:not(:disabled) {
            background: var(--poda-accent-signal-green-hover);
            box-shadow: 0 0 20px rgba(0, 255, 127, 0.5);
            transform: translateY(-1px);
        }
        
        .username-claim-btn:disabled {
            background: var(--poda-text-muted);
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .username-claim-btn .btn-cursor {
            display: inline-block;
            margin-left: 0.2em;
            animation: blink-cursor 1s infinite;
            font-weight: 400;
            color: var(--poda-bg-primary);
        }
        
        @keyframes blink-cursor {
            0%, 49% {
                opacity: 1;
            }
            50%, 100% {
                opacity: 0;
            }
        }
        
        .hero-ctas-secondary {
            display: flex;
            gap: 1rem;
            justify-content: center;
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
            position: relative;
            z-index: 3;
        }
        
        .hero-phone-mockup::before {
            content: '';
            position: absolute;
            top: -30px;
            left: -30px;
            right: -30px;
            bottom: -30px;
            border-radius: 30px;
            background: radial-gradient(ellipse at center, rgba(0, 255, 127, 0.2) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
            filter: blur(20px);
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
        
        .testimonials-section {
            padding: 6rem 2rem;
            background: var(--poda-bg-primary);
            scroll-behavior: smooth;
        }
        
        /* CSS approach: Apply scroll-snap when section is active - MAXIMUM RESISTANCE */
        html.scroll-in-testimonials,
        body.scroll-in-testimonials {
            scroll-snap-type: y proximity; /* proximity allows scrolling past but creates resistance */
            scroll-padding: 12rem 0; /* MAXIMUM padding = MAXIMUM resistance */
        }
        
        /* Section itself becomes first snap point - activates from the top */
        .testimonials-section.scroll-resistance-active {
            scroll-snap-align: start;
            scroll-snap-stop: always; /* Force stop at section start */
            scroll-margin-top: 10rem; /* MAXIMUM margin = snaps much earlier */
        }
        
        /* Header is an extremely strong snap point at the top */
        .testimonials-header {
            scroll-snap-align: start;
            scroll-snap-stop: always; /* Force stop at header */
            scroll-margin-top: 8rem;
            scroll-margin-bottom: 8rem;
        }
        
        /* Container creates a snap point */
        .testimonials-container {
            scroll-snap-align: start;
            scroll-margin: 5rem 0;
        }
        
        /* Every testimonial card is a snap point with MAXIMUM resistance */
        .testimonial-card {
            scroll-snap-align: start;
            scroll-snap-stop: always; /* Force stop on all cards for maximum resistance */
            scroll-margin: 10rem 0; /* MAXIMUM margins = MAXIMUM snap zones */
        }
        
        /* First 12 cards get EXTREME resistance from the start */
        .testimonial-card:nth-child(-n+12) {
            scroll-margin-top: 12rem;
            scroll-margin-bottom: 10rem;
        }
        
        /* Every 2nd card gets extra snap margin for more stopping points */
        .testimonial-card:nth-child(2n) {
            scroll-margin-top: 11rem;
        }
        
        /* Every 3rd card also gets extra margin */
        .testimonial-card:nth-child(3n) {
            scroll-margin-top: 12rem;
        }
        
        /* First 5 cards get MAXIMUM resistance */
        .testimonial-card:nth-child(-n+5) {
            scroll-margin-top: 15rem;
            scroll-margin-bottom: 12rem;
        }
        
        /* Cards 6-10 get very high resistance */
        .testimonial-card:nth-child(n+6):nth-child(-n+10) {
            scroll-margin-top: 13rem;
            scroll-margin-bottom: 11rem;
        }
        
        /* Last 2 cards - allow easier escape but still some resistance */
        .testimonial-card:nth-last-child(-n+2) {
            scroll-snap-stop: normal; /* Easier to scroll past last cards */
            scroll-margin-bottom: 2rem;
        }
        
        /* Last card should not lock scrolling - remove snap entirely */
        .testimonial-card:last-child {
            scroll-snap-align: none;
            scroll-snap-stop: normal;
        }
        
        .testimonials-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .testimonials-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--poda-text-primary);
        }
        
        .testimonials-header p {
            font-size: 1.25rem;
            color: var(--poda-text-secondary);
        }
        
        .testimonials-mosaic {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            grid-auto-rows: auto;
            /* Add scroll snap points to cards for subtle resistance */
        }
        
        .testimonial-card {
            background: var(--poda-bg-secondary);
            border: 1px solid var(--poda-border-subtle);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            /* Scroll snap properties are set above in the scroll-in-testimonials section */
        }
        
        .testimonial-card:hover {
            border-color: var(--poda-accent-signal-green);
            box-shadow: 0 8px 32px rgba(0, 255, 127, 0.1);
            transform: translateY(-4px) scale(1.02);
            z-index: 10;
        }
        
        /* Card push effect - cards shift when neighbors animate */
        .testimonials-mosaic:has(.testimonial-card.animate) .testimonial-card:not(.animate) {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        
        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--poda-bg-primary);
            border: 2px solid var(--poda-accent-signal-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--poda-accent-signal-green);
            flex-shrink: 0;
        }
        
        .testimonial-info {
            flex: 1;
            min-width: 0;
        }
        
        .testimonial-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--poda-text-primary);
            margin-bottom: 0.25rem;
        }
        
        .testimonial-role {
            font-size: 0.9rem;
            color: var(--poda-text-secondary);
        }
        
        .testimonial-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            color: var(--poda-accent-signal-green);
            font-size: 0.9rem;
        }
        
        .testimonial-text {
            color: var(--poda-text-secondary);
            line-height: 1.6;
            font-size: 1rem;
        }
        
        /* Make some cards taller for mosaic effect */
        .testimonial-card:nth-child(3n+1) {
            grid-row: span 1;
        }
        
        .testimonial-card:nth-child(3n+2) {
            grid-row: span 1;
        }
        
        .testimonial-card:nth-child(3n+3) {
            grid-row: span 2;
        }
        
        @media (max-width: 768px) {
            .testimonials-mosaic {
                grid-template-columns: 1fr;
            }
            
            .testimonial-card:nth-child(n) {
                grid-row: span 1;
            }
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
            
            .username-claim-box {
                flex-direction: column;
                padding: 1rem;
                gap: 0.75rem;
                max-width: 100%;
            }
            
            .username-input-group {
                width: 100%;
                padding: 0;
            }
            
            .username-input {
                width: 100%;
                min-width: 0;
            }
            
            .username-claim-btn {
                width: 100%;
                text-align: center;
                padding: 0.875rem 1.5rem;
            }
            
            .poda-logo-text {
                font-size: 0.95rem;
            }
            
            .username-input {
                font-size: 0.95rem;
                padding: 0.75rem 1rem;
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
    <!-- React Marketing Navigation Mount Point -->
    <div id="marketing-nav-root"></div>

    <!-- Hero Section -->
    <section class="homepage-hero">
        <div class="hero-content">
            <p class="hero-tagline scroll-animate" data-animate="fade-slide-up">More signal, Less noise</p>
            <h1 class="hero-headline scroll-animate" data-animate="fade-slide-up" data-delay="100">The link for listeners</h1>
            <p class="hero-subheadline scroll-animate" data-animate="fade-slide-up" data-delay="200">A link-in-bio tool purpose built specifically for podcasters.</p>
            <div class="username-claim-container scroll-animate" data-animate="fade-slide-up" data-delay="300">
                <div class="username-claim-box">
                    <div class="username-input-group">
                        <div class="username-prefix">
                            <span class="poda-logo-text">poda.bio</span>
                            <span class="username-slash">/</span>
                        </div>
                        <input 
                            type="text" 
                            class="username-input" 
                            placeholder="yourname" 
                            id="hero-username-input"
                        />
                        <span class="username-status" id="username-status"></span>
                    </div>
                    <a href="/signup.php" class="btn username-claim-btn" id="hero-claim-btn">
                        Get your url<span class="btn-cursor">_</span>
                    </a>
            </div>
            </div>
            <div class="hero-phone-mockup scroll-animate" data-animate="scale" data-delay="400">
                <div style="padding: 3rem 2rem; background: rgba(26, 26, 26, 0.5); border: 2px dashed rgba(255, 255, 255, 0.2); border-radius: 12px; text-align: center; color: var(--poda-text-secondary);">
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--poda-accent-signal-green); font-weight: 600;">📁 Folder: /assets/images/hero/</p>
                    <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600;">page-preview-mobile.png</p>
                    <p style="font-size: 0.85rem; line-height: 1.5; max-width: 600px; margin: 0 auto;">AI Prompt: "Screenshot mockup of a beautiful podcast link-in-bio page on mobile device. Show profile image, podcast title, description, social icons, podcast player with play button, and link buttons. Modern, clean design with dark theme. iPhone frame mockup. Signal green accents."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Value Proposition Section -->
    <section class="value-props">
        <div class="value-props-container">
            <h2 class="section-title scroll-animate" data-animate="fade-slide-up">Pod-First</h2>
            <p class="section-subtitle scroll-animate" data-animate="fade-slide-up" data-delay="100">Built specifically for podcasters, not adapted for them</p>
            <div class="value-props-grid">
                <div class="value-prop-card scroll-animate" data-animate="fade-slide-up" data-delay="0">
                    <div class="value-prop-icon"><span class="icon-headphones"></span></div>
                    <h3>Pod-First</h3>
                    <p>Every feature designed with podcasters in mind. RSS sync, built-in player, and episode management.</p>
                </div>
                <div class="value-prop-card scroll-animate" data-animate="fade-slide-up" data-delay="200">
                    <div class="value-prop-icon"><span class="icon-sparkle"></span></div>
                    <h3>Minimalist Design</h3>
                    <p>Clean, uncluttered layouts that put your content first. Beautiful themes that don't distract.</p>
                </div>
                <div class="value-prop-card scroll-animate" data-animate="fade-slide-up" data-delay="400">
                    <div class="value-prop-icon"><span class="icon-broadcast"></span></div>
                    <h3>Clear Signals</h3>
                    <p>One link. All your content. Automatically synced from your RSS feed. No manual updates needed.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="testimonials-container">
            <div class="testimonials-header">
                <h2 class="scroll-animate" data-animate="fade-slide-up">Loved by Podcasters</h2>
                <p class="scroll-animate" data-animate="fade-slide-up" data-delay="100">See what creators are saying about PodaBio</p>
            </div>
            <div class="testimonials-mosaic">
                <!-- Testimonial 1 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-left" data-delay="0">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">SM</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Sarah Martinez</div>
                            <div class="testimonial-role">Tech Talk Podcast</div>
                </div>
            </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"PodaBio has completely transformed how I share my podcast content. The RSS sync means I never have to manually update my page - it's always current!"</p>
        </div>

                <!-- Testimonial 2 -->
                <div class="testimonial-card scroll-animate" data-animate="scale-rotate" data-delay="100">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">JD</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">James Davis</div>
                            <div class="testimonial-role">Business Insights</div>
                </div>
                </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"As a professional podcaster, I need a link-in-bio that actually understands podcasts. PodaBio delivers exactly that. The built-in player is a game-changer."</p>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-right" data-delay="200">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">ER</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Emma Rodriguez</div>
                            <div class="testimonial-role">True Crime Stories</div>
                </div>
                </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"I've tried every link-in-bio tool out there. PodaBio is the first one built specifically for podcasters. The themes are beautiful and the analytics actually matter for audio content. My listeners love the seamless experience when they click through from social media. The RSS integration saves me hours every week!"</p>
                </div>
                
                <!-- Testimonial 4 -->
                <div class="testimonial-card scroll-animate" data-animate="rotate" data-delay="300">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">MW</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Michael Wong</div>
                            <div class="testimonial-role">Startup Stories</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The minimalist design philosophy really shines. My content is the star, not the platform. Plus, the custom domain support gives me the professional look I need."</p>
                </div>
                
                <!-- Testimonial 5 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-corner" data-delay="400">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">LP</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Lisa Park</div>
                            <div class="testimonial-role">Health & Wellness</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"Finally, a platform that gets it. The email subscription integration works flawlessly, and I've grown my list significantly since switching to PodaBio."</p>
                </div>
                
                <!-- Testimonial 6 -->
                <div class="testimonial-card scroll-animate" data-animate="scale" data-delay="500">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">RB</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Ryan Brown</div>
                            <div class="testimonial-role">Sports Central</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The Pro plan is incredibly affordable for what you get. I love having unlimited links and all the themes. The analytics help me understand what my audience engages with most. Best investment I've made for my podcast brand!"</p>
                </div>
                
                <!-- Testimonial 7 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-down" data-delay="600">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">CT</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Chris Thompson</div>
                            <div class="testimonial-role">Gaming News Daily</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"Custom colors and fonts let me match my brand perfectly. It looks like I spent thousands on a custom site."</p>
                </div>
                
                <!-- Testimonial 8 -->
                <div class="testimonial-card scroll-animate" data-animate="fade-slide-up" data-delay="700">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">AJ</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Alex Johnson</div>
                            <div class="testimonial-role">Movie Reviews Weekly</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"Priority support is worth its weight in gold. Whenever I have a question, I get a response within hours. The team really cares about their users."</p>
                </div>
                
                <!-- Testimonial 9 -->
                <div class="testimonial-card scroll-animate" data-animate="scale-rotate" data-delay="800">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">MN</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Maria Nguyen</div>
                            <div class="testimonial-role">Food Culture Podcast</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"I run multiple podcasts and PodaBio makes it so easy to manage everything in one place. The interface is intuitive, and everything just works. No more struggling with generic tools that weren't designed for audio content. This is exactly what the podcasting community needed - a tool built by podcasters, for podcasters!"</p>
                </div>
                
                <!-- Testimonial 10 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-left" data-delay="900">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">DK</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">David Kim</div>
                            <div class="testimonial-role">Science Explained</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The dark theme options are perfect for my brand. It looks professional and modern. My audience comments on how sleek my link-in-bio page looks."</p>
                </div>
                
                <!-- Testimonial 11 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-right" data-delay="1000">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">DK</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">David Kim</div>
                            <div class="testimonial-role">Science Explained</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The dark theme options are perfect for my brand. It looks professional and modern. My audience comments on how sleek my link-in-bio page looks."</p>
                </div>
                
                <!-- Testimonial 12 -->
                <div class="testimonial-card scroll-animate" data-animate="scale-rotate" data-delay="1100">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">SG</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Sam Garcia</div>
                            <div class="testimonial-role">Comedy Hour</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"Switching from Linktree to PodaBio was the best decision. The podcast-specific features make all the difference. My engagement rates have improved significantly."</p>
                </div>
                
                <!-- Testimonial 13 -->
                <div class="testimonial-card scroll-animate" data-animate="slide-left" data-delay="1200">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">JL</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Jessica Lee</div>
                            <div class="testimonial-role">Travel Diaries</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The free plan got me started, and I quickly upgraded to Pro. The value is incredible. All 49+ themes give me so many options to showcase my brand. Plus, the custom domain makes me look like a major player even though I'm just starting out!"</p>
                </div>
                
                <!-- Testimonial 14 -->
                <div class="testimonial-card scroll-animate" data-animate="rotate" data-delay="1300">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">RM</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Robert Miller</div>
                            <div class="testimonial-role">History Deep Dive</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"Advanced analytics help me understand my audience better. I can see what episodes get the most clicks and adjust my content strategy accordingly."</p>
                </div>
                
                <!-- Testimonial 15 -->
                <div class="testimonial-card scroll-animate" data-animate="scale" data-delay="1400">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">TH</div>
                        <div class="testimonial-info">
                            <div class="testimonial-name">Tom Harris</div>
                            <div class="testimonial-role">Investor Insights</div>
                        </div>
                    </div>
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"The no branding option is crucial for my professional image. PodaBio lets me showcase my content without any distracting logos. It's exactly what I needed to level up my podcast presence. The team has built something special here!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Demo Section -->
    <section class="demo-section" id="demo">
        <div class="demo-container">
            <h2 class="scroll-animate" data-animate="fade-slide-up">See It In Action</h2>
            <p class="scroll-animate" data-animate="fade-slide-up" data-delay="100">Beautiful pages that represent your brand perfectly</p>
            <div class="demo-toggle scroll-animate" data-animate="fade-slide-up" data-delay="200">
                <button class="active" onclick="switchDemo('mobile')">Mobile</button>
                <button onclick="switchDemo('desktop')">Desktop</button>
            </div>
            <div class="demo-preview scroll-animate" data-animate="scale" data-delay="300">
                <div id="demo-image-placeholder" style="padding: 3rem 2rem; background: rgba(26, 26, 26, 0.5); border: 2px dashed rgba(255, 255, 255, 0.2); border-radius: 12px; text-align: center; color: var(--poda-text-secondary);">
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--poda-accent-signal-green); font-weight: 600;">📁 Folder: /assets/images/demo/</p>
                    <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600;">page-preview-mobile.png</p>
                    <p style="font-size: 0.85rem; line-height: 1.5; max-width: 600px; margin: 0 auto;">AI Prompt: "Screenshot mockup of a beautiful podcast link-in-bio page on mobile device. Show profile image, podcast title, description, social icons, podcast player with play button, and link buttons. Modern, clean design with dark theme. iPhone frame mockup. Signal green accents."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="social-proof">
        <div class="social-proof-container">
            <h2 class="scroll-animate" data-animate="fade-slide-up">Trusted by Podcasters Everywhere</h2>
            <p class="scroll-animate" data-animate="fade-slide-up" data-delay="100">Join thousands of creators using PodaBio to grow their audience</p>
            <div class="platform-logos scroll-animate" data-animate="fade-slide-up" data-delay="200">
                <div style="padding: 2rem; background: rgba(26, 26, 26, 0.5); border: 2px dashed rgba(255, 255, 255, 0.2); border-radius: 12px; text-align: center; color: var(--poda-text-secondary); margin: 1rem;">
                    <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--poda-accent-signal-green); font-weight: 600;">📁 Folder: /assets/images/social-proof/</p>
                    <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600;">platform-logos.png</p>
                    <p style="font-size: 0.85rem; line-height: 1.5; max-width: 500px; margin: 0 auto;">AI Prompt: "Row of podcast platform logos including Apple Podcasts, Spotify, YouTube Music, iHeart Radio, Amazon Music, Google Podcasts, Pocket Casts, Castro, and Overcast. Clean, modern logos on transparent or dark background. Consistent sizing and spacing. Professional appearance."</p>
                </div>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Apple Podcasts</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Spotify</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">YouTube Music</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Amazon Music</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem; border: 1px solid rgba(255,255,255,0.1);">Google Podcasts</span>
                <span style="display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0.5rem;">+ 20 more</span>
            </div>
        </div>
    </section>

    <!-- Main Content Tabs -->
    <section class="main-content-tabs" id="main-content">
        <div class="tabs-container">
            <div class="tabs-header scroll-animate" data-animate="fade-slide-up">
                <button class="tab-button active" data-tab="features" id="tab-features">Features</button>
                <button class="tab-button" data-tab="pricing" id="tab-pricing">Pricing</button>
                <button class="tab-button" data-tab="examples" id="tab-examples">Examples</button>
                <button class="tab-button" data-tab="about" id="tab-about">About</button>
                </div>
            
            <!-- Features Tab -->
            <div class="tab-content active" id="content-features" data-section="features">
                <div class="tab-inner">
                    <h2 class="tab-title scroll-animate" data-animate="fade-slide-up">Everything You Need to Grow</h2>
                    <p class="tab-subtitle scroll-animate" data-animate="fade-slide-up" data-delay="100">Turn listeners into subscribers, subscribers into fans</p>
                    
                    <!-- Feature Comparison Accordion -->
                    <div class="accordion">
                        <button class="accordion-header" onclick="toggleAccordion(this)">
                            <span>Why PodaBio vs. Generic Link-in-Bio Tools</span>
                            <span class="accordion-icon icon-plus"></span>
                        </button>
                        <div class="accordion-content">
                            <div style="overflow-x: auto; margin-top: 1rem;">
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
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);"><span class="icon-check"></span></td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Built-in Podcast Player</td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);"><span class="icon-check"></span></td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-muted);">✗</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 1rem; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-text-secondary);">Podcast Directory Links</td>
                                            <td style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--poda-border-subtle); color: var(--poda-accent-signal-green);"><span class="icon-check"></span></td>
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
                                            <td style="padding: 1rem; color: var(--poda-text-secondary);">Free Plan</td>
                                            <td style="padding: 1rem; text-align: center; color: var(--poda-accent-signal-green);"><span class="icon-check"></span> Full Features</td>
                                            <td style="padding: 1rem; text-align: center; color: var(--poda-text-secondary);">Limited</td>
                                            <td style="padding: 1rem; text-align: center; color: var(--poda-text-secondary);">Limited</td>
                                        </tr>
                                    </tbody>
                                </table>
                </div>
                </div>
            </div>
                    
                    <!-- Feature Accordions -->
                    <div class="features-accordions">
                        <div class="accordion">
                            <button class="accordion-header" onclick="toggleAccordion(this)">
                                <span><span class="icon-rss"></span> RSS Feed Integration</span>
                                <span class="accordion-icon icon-plus"></span>
                            </button>
                            <div class="accordion-content">
                                <p>Automatically import your podcast information, episodes, and artwork from your RSS feed.</p>
                                <ul class="feature-list">
                                    <li>Auto-populate podcast name, description, and cover art</li>
                                    <li>Import recent episodes with titles and descriptions</li>
                                    <li>Automatic updates when new episodes are published</li>
                                    <li>Episode duration and publish date tracking</li>
                                </ul>
        </div>
                        </div>

                        <div class="accordion">
                            <button class="accordion-header" onclick="toggleAccordion(this)">
                                <span><span class="icon-music"></span> Podcast Player</span>
                                <span class="accordion-icon icon-plus"></span>
                            </button>
                            <div class="accordion-content">
                                <p>Built-in audio player for your episodes using Shikwasa.js.</p>
                                <ul class="feature-list">
                                    <li>Beautiful, accessible podcast player</li>
                                    <li>Episode drawer for easy browsing</li>
                                    <li>Mini player that stays visible while browsing</li>
                                    <li>Theme-aware player design</li>
                                </ul>
            </div>
        </div>
                        
                        <div class="accordion">
                            <button class="accordion-header" onclick="toggleAccordion(this)">
                                <span><span class="icon-palette"></span> Complete Customization</span>
                                <span class="accordion-icon icon-plus"></span>
                            </button>
                            <div class="accordion-content">
                                <p>Make your page truly yours with extensive customization options.</p>
                                <ul class="feature-list">
                                    <li>49+ professionally designed themes</li>
                                    <li>15+ Google Fonts for headings and body text</li>
                                    <li>Custom color pickers for primary, secondary, and accent colors</li>
                                    <li>Pre-built themes with one-click application</li>
                                    <li>Multiple layout options</li>
                                    <li>Profile and background image uploads</li>
                                    <li>Drag-and-drop link reordering</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="accordion">
                            <button class="accordion-header" onclick="toggleAccordion(this)">
                                <span><span class="icon-chart"></span> Analytics</span>
                                <span class="accordion-icon icon-plus"></span>
                            </button>
                            <div class="accordion-content">
                                <p>Track your page performance and audience engagement.</p>
                                <ul class="feature-list">
                                    <li>Page views and unique visitors</li>
                                    <li>Link click tracking</li>
                                    <li>Referrer data</li>
                                    <li>Device and browser analytics</li>
                                    <li>Subscriber growth tracking</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="accordion">
                            <button class="accordion-header" onclick="toggleAccordion(this)">
                                <span><span class="icon-envelope"></span> Email Subscription</span>
                                <span class="accordion-icon icon-plus"></span>
                            </button>
                            <div class="accordion-content">
                                <p>Grow your email list with integrated subscription forms.</p>
                                <ul class="feature-list">
                                    <li>Drawer slider subscription form</li>
                                    <li>Integration with 6 major email services</li>
                                    <li>Mailchimp, Constant Contact, ConvertKit support</li>
                                    <li>AWeber, MailerLite, SendinBlue/Brevo support</li>
                                    <li>Double opt-in support</li>
                                    <li>Subscription analytics</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Tab -->
            <div class="tab-content" id="content-pricing">
                <div class="tab-inner">
                    <h2 class="tab-title scroll-animate" data-animate="fade-slide-up">Simple, Transparent Pricing</h2>
                    <p class="tab-subtitle scroll-animate" data-animate="fade-slide-up" data-delay="100">Choose the plan that's right for your podcast</p>
                    
                    <div class="pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 3rem;">
                        <!-- Free Plan -->
                        <div class="pricing-card scroll-animate" data-animate="fade-slide-up" data-delay="0" style="background: var(--poda-bg-secondary); border: 2px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; transition: all 0.3s;">
                            <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; color: var(--poda-text-primary);">Free</div>
                            <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--poda-text-primary);">$0<span style="font-size: 1rem; color: var(--poda-text-secondary);">/month</span></div>
                            <p style="color: var(--poda-text-secondary); margin-bottom: 2rem;">Perfect for getting started</p>
                            <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> RSS feed auto-sync</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Built-in podcast player</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Up to 10 custom links</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> 5 basic themes</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Basic analytics</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> poda.bio subdomain</li>
                            </ul>
                            <a href="/signup.php" class="btn btn-secondary" style="width: 100%; text-align: center; display: block;">Get Started</a>
                        </div>
                        
                        <!-- Pro Plan -->
                        <div class="pricing-card featured scroll-animate" data-animate="scale" data-delay="200" style="background: var(--poda-bg-secondary); border: 2px solid var(--poda-accent-signal-green); border-radius: 12px; padding: 2rem; transition: all 0.3s; position: relative;">
                            <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--poda-accent-signal-green); color: var(--poda-bg-primary); padding: 0.25rem 1rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">POPULAR</div>
                            <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; margin-top: 1rem; color: var(--poda-text-primary);">Pro</div>
                            <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--poda-text-primary);">$<?php echo number_format(PLAN_PRO_PRICE, 2); ?><span style="font-size: 1rem; color: var(--poda-text-secondary);">/month</span></div>
                            <p style="color: var(--poda-text-secondary); margin-bottom: 2rem;">For professional podcasters</p>
                            <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Everything in Free</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Unlimited custom links</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> All 49+ themes</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Custom colors & fonts</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Advanced analytics</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Email subscription integration</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Custom domain support</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Priority support</li>
                            </ul>
                            <a href="/payment/checkout.php?plan=pro" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">Upgrade to Pro</a>
                        </div>
                        
                        <!-- Agency Plan -->
                        <div class="pricing-card scroll-animate" data-animate="fade-slide-up" data-delay="400" style="background: var(--poda-bg-secondary); border: 2px solid var(--poda-border-subtle); border-radius: 12px; padding: 2rem; transition: all 0.3s; position: relative; opacity: 0.8;">
                            <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--poda-text-secondary); color: var(--poda-bg-primary); padding: 0.25rem 1rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">COMING SOON</div>
                            <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; margin-top: 1rem; color: var(--poda-text-primary);">Agency</div>
                            <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--poda-text-primary);">$99<span style="font-size: 1rem; color: var(--poda-text-secondary);">/month</span></div>
                            <p style="color: var(--poda-text-secondary); margin-bottom: 2rem;">For agencies managing multiple clients</p>
                            <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Everything in Pro</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Multiple Pro pages</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Manage unlimited pages</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> 24/7 Priority support</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> Dedicated account manager</li>
                                <li style="padding: 0.5rem 0; color: var(--poda-text-secondary);"><span class="icon-check"></span> White-label options</li>
                            </ul>
                            <button class="btn btn-secondary" style="width: 100%; text-align: center; display: block; opacity: 0.6; cursor: not-allowed;" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Examples Tab -->
            <div class="tab-content" id="content-examples">
                <div class="tab-inner">
                    <h2 class="tab-title">See It In Action</h2>
                    <p class="tab-subtitle">Beautiful pages created by podcasters like you</p>
                    <div style="text-align: center; padding: 4rem 2rem; color: var(--poda-text-secondary);">
                        <p style="font-size: 1.1rem; margin-bottom: 1rem;">Example pages coming soon</p>
                        <p style="font-size: 0.9rem;">Check back to see real examples of PodaBio pages in action.</p>
                    </div>
                </div>
            </div>
            
            <!-- About Tab -->
            <div class="tab-content" id="content-about">
                <div class="tab-inner">
                    <h2 class="tab-title scroll-animate" data-animate="fade-slide-up">About PodaBio</h2>
                    <p class="tab-subtitle scroll-animate" data-animate="fade-slide-up" data-delay="100">The link-in-bio platform built for podcasters</p>
                    <div style="max-width: 800px; margin: 0 auto;">
                        <div style="margin-bottom: 2rem;">
                            <h3 style="color: var(--poda-text-primary); margin-bottom: 1rem;">Our Mission</h3>
                            <p style="color: var(--poda-text-secondary); line-height: 1.8;">PodaBio was created to solve a simple problem: podcasters need a better way to share all their content in one place. We built a platform that understands podcasts, with features like RSS sync, built-in players, and podcast-specific themes.</p>
                        </div>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="color: var(--poda-text-primary); margin-bottom: 1rem;">Why We Built This</h3>
                            <p style="color: var(--poda-text-secondary); line-height: 1.8;">Generic link-in-bio tools weren't designed for podcasters. They lack RSS integration, podcast players, and the customization options podcasters need. PodaBio fills that gap with a tool built specifically for audio creators.</p>
                        </div>
                        <div class="cta-box" style="background: var(--poda-bg-secondary); border: 1px solid var(--poda-accent-signal-green); border-radius: 12px; padding: 2rem; text-align: center; margin-top: 3rem;">
                            <h3 style="color: var(--poda-text-primary); margin-bottom: 1rem;">Ready to Get Started?</h3>
                            <p style="color: var(--poda-text-secondary); margin-bottom: 1.5rem;">Create your free page in 2 minutes</p>
                            <a href="/signup.php" class="btn btn-primary">Get Started Free</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <div class="final-cta-container">
            <h2 class="scroll-animate" data-animate="fade-slide-up">Ready to Grow Your Podcast?</h2>
            <p class="scroll-animate" data-animate="fade-slide-up" data-delay="100">Create your free page in 2 minutes</p>
            <a href="/signup.php" class="btn btn-primary scroll-animate" data-animate="scale" data-delay="200" style="font-size: 1.25rem; padding: 1.25rem 3rem;">Get Started Free</a>
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
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="/support/">Support</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="#about">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#privacy" onclick="openDrawer('privacy'); return false;">Privacy</a></li>
                    <li><a href="#terms" onclick="openDrawer('terms'); return false;">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Drawer Overlay -->
    <div class="drawer-overlay" id="drawer-overlay"></div>
    
    <!-- Privacy Drawer -->
    <div class="drawer" id="drawer-privacy">
        <div class="drawer-header">
            <h3 class="drawer-title">Privacy Policy</h3>
            <button class="drawer-close icon-close" onclick="closeDrawer()">×</button>
        </div>
        <div class="drawer-content">
            <p>Privacy policy content will be loaded here. This drawer can contain the full privacy policy text.</p>
            <p>For now, you can access the full privacy policy at <a href="/privacy.php" style="color: var(--poda-accent-signal-green);">/privacy.php</a></p>
        </div>
    </div>
    
    <!-- Terms Drawer -->
    <div class="drawer" id="drawer-terms">
        <div class="drawer-header">
            <h3 class="drawer-title">Terms of Service</h3>
            <button class="drawer-close icon-close" onclick="closeDrawer()">×</button>
        </div>
        <div class="drawer-content">
            <p>Terms of service content will be loaded here. This drawer can contain the full terms of service text.</p>
            <p>For now, you can access the full terms at <a href="/terms.php" style="color: var(--poda-accent-signal-green);">/terms.php</a></p>
        </div>
    </div>

    <script>
        function switchDemo(view) {
            const buttons = document.querySelectorAll('.demo-toggle button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const placeholder = document.getElementById('demo-image-placeholder');
            if (view === 'mobile') {
                placeholder.innerHTML = '<p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--poda-accent-signal-green); font-weight: 600;">📁 Folder: /assets/images/demo/</p><p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600;">page-preview-mobile.png</p><p style="font-size: 0.85rem; line-height: 1.5; max-width: 600px; margin: 0 auto;">AI Prompt: "Screenshot mockup of a beautiful podcast link-in-bio page on mobile device. Show profile image, podcast title, description, social icons, podcast player with play button, and link buttons. Modern, clean design with dark theme. iPhone frame mockup. Signal green accents."</p>';
            } else {
                placeholder.innerHTML = '<p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--poda-accent-signal-green); font-weight: 600;">📁 Folder: /assets/images/demo/</p><p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600;">page-preview-desktop.png</p><p style="font-size: 0.85rem; line-height: 1.5; max-width: 600px; margin: 0 auto;">AI Prompt: "Screenshot mockup of a beautiful podcast link-in-bio page on desktop browser. Show profile image, podcast title, description, social icons, podcast player with play button, and link buttons. Modern, clean design with dark theme. Browser window frame. Signal green accents."</p>';
            }
        }

        // Navigation functionality is handled by marketing-nav.js
        
        // Tab Navigation
        (function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Global function to switch tabs (can be called from anywhere)
            window.switchToTab = function(tabName, scrollToSection = true) {
                const targetButton = document.querySelector(`.tab-button[data-tab="${tabName}"]`);
                if (!targetButton) return false;
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to target button and corresponding content
                targetButton.classList.add('active');
                const targetContent = document.getElementById('content-' + tabName);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // Scroll to section if requested
                if (scrollToSection) {
                    const tabsSection = document.getElementById('main-content');
                    if (tabsSection) {
                        const headerOffset = 100;
                        const elementPosition = tabsSection.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
                
                // Update URL hash without triggering scroll
                if (window.location.hash !== '#' + tabName) {
                    history.pushState(null, null, '#' + tabName);
                }
                
                return true;
            };
            
            // Handle tab button clicks
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetTab = button.getAttribute('data-tab');
                    window.switchToTab(targetTab, false); // Don't scroll when clicking tab buttons directly
                });
            });
            
            // Handle hash changes (from navigation links)
            window.addEventListener('hashchange', function() {
                const hash = window.location.hash.substring(1);
                if (hash && ['features', 'pricing', 'examples', 'about'].includes(hash)) {
                    window.switchToTab(hash, true);
            }
            });
            
            // Handle clicks on anchor links (including React navigation)
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href^="#"]');
                if (!link) return;
                
                const href = link.getAttribute('href');
                if (href && href.startsWith('#')) {
                    const targetTab = href.substring(1);
                    if (['features', 'pricing', 'examples', 'about'].includes(targetTab)) {
                        e.preventDefault();
                        window.switchToTab(targetTab, true);
                    }
                }
            });

            // Handle initial hash on page load
            if (window.location.hash) {
                const hash = window.location.hash.substring(1);
                if (['features', 'pricing', 'examples', 'about'].includes(hash)) {
                    setTimeout(() => window.switchToTab(hash, true), 100);
                }
            }
        })();
        
        // Accordion Toggle
        function toggleAccordion(button) {
            const accordion = button.closest('.accordion');
            const content = accordion.querySelector('.accordion-content');
            const iconElement = button.querySelector('.accordion-icon');
            const isActive = button.classList.contains('active');
            
            // Close all accordions in the same group (optional - remove if you want multiple open)
            const allAccordions = accordion.parentElement.querySelectorAll('.accordion');
            allAccordions.forEach(acc => {
                if (acc !== accordion) {
                    const header = acc.querySelector('.accordion-header');
                    const accContent = acc.querySelector('.accordion-content');
                    const accIcon = header.querySelector('.accordion-icon');
                    header.classList.remove('active');
                    accContent.classList.remove('active');
                    if (accIcon) {
                        accIcon.classList.remove('icon-minus');
                        accIcon.classList.add('icon-plus');
                    }
                }
            });
            
            // Toggle current accordion
            button.classList.toggle('active');
            content.classList.toggle('active');
            
            // Toggle icon
            if (iconElement) {
                if (button.classList.contains('active')) {
                    iconElement.classList.remove('icon-plus');
                    iconElement.classList.add('icon-minus');
                } else {
                    iconElement.classList.remove('icon-minus');
                    iconElement.classList.add('icon-plus');
                }
                // Trigger icon re-render
                const event = new CustomEvent('icon-update', { detail: { element: iconElement } });
                document.dispatchEvent(event);
            }
            
        }
        
        // Username Claim Functionality
        const usernameInput = document.getElementById('hero-username-input');
        const claimBtn = document.getElementById('hero-claim-btn');
        const statusIndicator = document.getElementById('username-status');
        
        let checkTimeout = null;
        let isChecking = false;
        let isAvailable = false;
        
        if (usernameInput && claimBtn && statusIndicator) {
            // Function to check username availability
            async function checkUsernameAvailability(username) {
                if (isChecking) return;
                
                // Validate format first
                const usernameRegex = /^[a-zA-Z0-9_-]{3,30}$/;
                if (!usernameRegex.test(username)) {
                    if (username.length > 0) {
                        usernameInput.classList.remove('available', 'unavailable', 'checking');
                        usernameInput.classList.add('unavailable');
                        statusIndicator.innerHTML = '<span class="icon-close" style="color: #ff4444;"></span>';
                        claimBtn.disabled = true;
                        isAvailable = false;
                    } else {
                        usernameInput.classList.remove('available', 'unavailable', 'checking');
                        statusIndicator.innerHTML = '';
                        claimBtn.disabled = true;
                        isAvailable = false;
                    }
                    return;
            }
            
                isChecking = true;
                usernameInput.classList.remove('available', 'unavailable');
                usernameInput.classList.add('checking');
                statusIndicator.innerHTML = '<span style="color: var(--poda-text-secondary); font-size: 0.9rem; display: inline-block; animation: spin 1s linear infinite;">⟳</span>';
                claimBtn.disabled = true;
                
                try {
                    const response = await fetch(`/api/check-username.php?username=${encodeURIComponent(username)}`);
                    const data = await response.json();
                    
                    if (data.success && data.available) {
                        usernameInput.classList.remove('checking', 'unavailable');
                        usernameInput.classList.add('available');
                        statusIndicator.innerHTML = '<span class="icon-check" style="color: var(--poda-accent-signal-green);"></span>';
                        claimBtn.disabled = false;
                        isAvailable = true;
                    } else {
                        usernameInput.classList.remove('checking', 'available');
                        usernameInput.classList.add('unavailable');
                        statusIndicator.innerHTML = '<span class="icon-close" style="color: #ff4444;"></span>';
                        claimBtn.disabled = true;
                        isAvailable = false;
                    }
                } catch (error) {
                    console.error('Error checking username:', error);
                    usernameInput.classList.remove('checking');
                    statusIndicator.innerHTML = '';
                    claimBtn.disabled = true;
                    isAvailable = false;
                } finally {
                    isChecking = false;
                }
            }
            
            // Debounced username check
            usernameInput.addEventListener('input', (e) => {
                const username = e.target.value.trim();
                
                // Clear previous timeout
                if (checkTimeout) {
                    clearTimeout(checkTimeout);
                }
                
                // Clear status if empty
                if (!username) {
                    usernameInput.classList.remove('available', 'unavailable', 'checking');
                    statusIndicator.innerHTML = '';
                    claimBtn.disabled = true;
                    isAvailable = false;
                    return;
                }
                
                // Check availability after 500ms delay
                checkTimeout = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 500);
            });
            
            // Handle Enter key in input
            usernameInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && isAvailable && !claimBtn.disabled) {
                    claimBtn.click();
                }
            });
            
            // Update claim button to include username in URL
            claimBtn.addEventListener('click', (e) => {
                const username = usernameInput.value.trim();
                if (username && isAvailable) {
                    claimBtn.href = `/signup.php?username=${encodeURIComponent(username)}`;
                } else {
                    e.preventDefault();
                    if (!username) {
                        usernameInput.focus();
                    }
                }
            });
            
            // Add focus styles
            usernameInput.addEventListener('focus', () => {
                usernameInput.parentElement.parentElement.style.borderColor = 'var(--poda-accent-signal-green)';
            });
            
            usernameInput.addEventListener('blur', () => {
                if (!usernameInput.value) {
                    usernameInput.parentElement.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.15)';
                }
            });
        }
        
        // Drawer Functions
        function openDrawer(drawerId) {
            const drawer = document.getElementById('drawer-' + drawerId);
            const overlay = document.getElementById('drawer-overlay');
            if (drawer && overlay) {
                drawer.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeDrawer() {
            const drawers = document.querySelectorAll('.drawer');
            const overlay = document.getElementById('drawer-overlay');
            drawers.forEach(drawer => drawer.classList.remove('open'));
            if (overlay) {
                overlay.classList.remove('active');
                }
            document.body.style.overflow = '';
        }
        
        // Close drawer on overlay click
        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('drawer-overlay');
            if (overlay) {
                overlay.addEventListener('click', closeDrawer);
            }
            
            // Close drawer on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeDrawer();
                }
            });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('#privacy') && !href.startsWith('#terms')) {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        const headerOffset = 100;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
        
        // Scroll Animations using Intersection Observer
        (function() {
            // Check if Intersection Observer is supported
            if (!('IntersectionObserver' in window)) {
                // Fallback: show all elements immediately
                document.querySelectorAll('.scroll-animate').forEach(el => {
                    el.classList.add('animate');
                });
                return;
            }
            
            // Create observer with options
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -100px 0px', // Trigger when element is 100px from bottom of viewport
                threshold: 0.1 // Trigger when 10% of element is visible
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                        // Optionally unobserve after animation to improve performance
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe all elements with scroll-animate class
            document.querySelectorAll('.scroll-animate').forEach(el => {
                observer.observe(el);
            });
        })();
        
    </script>
</body>
</html>

