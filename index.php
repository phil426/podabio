<?php
/**
 * Under Construction Page
 * Podn.Bio - Home page
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h(APP_NAME); ?> - Coming Soon</title>
    <meta name="description" content="Pod A Bio is under construction. Join our beta to be the first to experience the future of podcast link-in-bio pages.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-x: hidden;
            position: relative;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            width: 100%;
        }
        
        .logo {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeInDown 1s ease-out;
        }
        
        .domain {
            font-size: 1.5rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: fadeInDown 1s ease-out 0.1s both;
            letter-spacing: 0.1em;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .construction-icon {
            font-size: 8rem;
            margin: 2rem 0;
            animation: bounce 2s ease-in-out infinite;
            filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }
        
        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out 0.2s both;
            line-height: 1.2;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 3rem;
            font-weight: 400;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out 0.4s both;
        }
        
        .description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            line-height: 1.8;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease-out 0.8s both;
        }
        
        .btn {
            padding: 1.25rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn span {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }
        
        .features-preview {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 1s ease-out 1s both;
        }
        
        .features-preview h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .features-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            list-style: none;
        }
        
        .features-list li {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .features-list li::before {
            content: '✓';
            background: rgba(255, 255, 255, 0.2);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .footer {
            margin-top: 3rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            animation: fadeIn 1s ease-out 1.2s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .logo {
                font-size: 2rem;
            }
            
            .domain {
                font-size: 1.2rem;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .construction-icon {
                font-size: 5rem;
            }
            
            .btn {
                padding: 1rem 2rem;
                font-size: 1rem;
                width: 100%;
                max-width: 300px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .features-preview {
                padding: 1.5rem;
            }
            
            .features-list {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="logo">Pod A Bio</div>
        <div class="domain">poda.bio</div>
        
        <div class="construction-icon">🚧</div>
        
        <h1>We're Building Something Amazing</h1>
        
        <p class="subtitle">Under Construction</p>
        
        <p class="description">
            We're working hard to bring you the best podcast link-in-bio platform. 
            Join our beta program to get early access and help shape the future of Pod A Bio!
        </p>
        
        <div class="cta-buttons">
            <a href="/login.php" class="btn btn-secondary">
                <span>Beta Login</span>
            </a>
            <a href="/signup.php" class="btn btn-primary">
                <span>Beta Signup</span>
            </a>
        </div>
        
        <div class="features-preview">
            <h3>What's Coming</h3>
            <ul class="features-list">
                <li>RSS Feed Integration</li>
                <li>Custom Podcast Player</li>
                <li>Beautiful Themes</li>
                <li>Analytics Dashboard</li>
                <li>Social Media Links</li>
                <li>Email Subscriptions</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 60 + 20;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
    </script>
</body>
</html>
