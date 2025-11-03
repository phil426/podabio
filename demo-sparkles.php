<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sparkles & Fizzy Effects Demo - Featured Widget</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            user-select: none;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .demo-wrapper {
            position: relative;
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .demo-wrapper > div:first-child {
            text-align: center;
            color: white;
        }

        .demo-wrapper h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .demo-wrapper .subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .featured-widget {
            position: relative;
            width: 100%;
        }

        .widget-item {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .widget-thumbnail-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .widget-content {
            position: relative;
        }

        .widget-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .widget-description {
            font-size: 1rem;
            color: #666;
            line-height: 1.5;
        }

        /* Sparkle SVG styling */
        .sparkle {
            position: absolute;
            width: 24px;
            height: 24px;
            pointer-events: none;
            z-index: 2;
            opacity: 0;
        }

        .sparkle svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.8));
        }

        .sparkle.active {
            opacity: 1;
            animation: sparkleAnim 2s ease-in-out forwards;
        }

        @keyframes sparkleAnim {
            0% {
                opacity: 0;
                transform: scale(0) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: scale(1.2) rotate(180deg);
            }
            100% {
                opacity: 0;
                transform: scale(0) rotate(360deg);
            }
        }

        /* Shine effect on widget */
        .widget-item::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 70%
            );
            transform: rotate(45deg);
            animation: shine 3s infinite;
            pointer-events: none;
        }

        @keyframes shine {
            0% {
                left: -50%;
            }
            100% {
                left: 150%;
            }
        }

        .info-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            text-align: center;
            max-width: 500px;
            margin-top: 2rem;
        }

        .info-box p {
            margin: 0.5rem 0;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Fizzy Particle Button */
        .fizzy-button-container {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .fizzy-button-wrapper {
            position: relative;
            display: inline-block;
        }

        /* Sparkles on fizzy button */
        .fizzy-sparkle {
            position: absolute;
            width: 18px;
            height: 18px;
            pointer-events: none;
            z-index: 10;
            opacity: 0;
        }

        .fizzy-sparkle svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 4px rgba(255, 215, 0, 0.8));
        }

        .fizzy-sparkle.active {
            opacity: 1;
            animation: fizzySparkleAnim 1.8s ease-in-out forwards;
        }

        @keyframes fizzySparkleAnim {
            0% {
                opacity: 0;
                transform: scale(0) rotate(0deg);
            }
            50% {
                opacity: 1;
                transform: scale(1.2) rotate(180deg);
            }
            100% {
                opacity: 0;
                transform: scale(0) rotate(360deg);
            }
        }

        .fizzy-input {
            display: none;
        }

        .fizzy-label {
            display: inline-block;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .fizzy-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .fizzy-input:checked + .fizzy-label {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        .fizzy-input:checked + .fizzy-label:hover {
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.6);
        }

        .fizzy-particles {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            pointer-events: none;
            transform: translate(-50%, -50%);
        }

        .fizzy-spot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            opacity: 0;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 6px rgba(255, 255, 255, 0.8);
        }

        .fizzy-input:checked ~ .fizzy-particles .fizzy-spot {
            animation: fizzyPop 0.8s ease-out forwards;
        }

        @keyframes fizzyPop {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0) translateX(0) translateY(0);
            }
            30% {
                opacity: 1;
            }
            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.5) translateX(0) translateY(0);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.3) translateX(var(--move-x)) translateY(var(--move-y));
            }
        }

        .fizzy-info {
            margin-top: 1rem;
            text-align: center;
            color: white;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="demo-wrapper">
        <div>
            <h1>✨ Sparkles & Fizzy Effects Demo</h1>
            <p class="subtitle">Demonstrating sparkles and fizzy particle effects for featured widgets</p>
        </div>
        
        <div class="featured-widget">
            <div class="widget-item">
                <div class="widget-thumbnail-wrapper">
                    ✨
                </div>
                <div class="widget-content">
                    <div class="widget-title">Featured Widget with Sparkles Effect</div>
                    <div class="widget-description">Watch the animated sparkles dance around this widget!</div>
                </div>
            </div>
        </div>

        <div class="info-box">
            <p><strong>Effect Details:</strong></p>
            <p>✨ Sparkles randomly appear and fade around the widget</p>
            <p>🌟 Sparkles use golden/yellow colors with glow effects</p>
            <p>✨ Each sparkle has a unique animation timing</p>
            <p>🌟 The widget has a subtle shine animation overlay</p>
        </div>

        <div class="fizzy-button-container">
            <div class="fizzy-button-wrapper" id="fizzy-button-wrapper">
                <input type="checkbox" id="fizzy-button" class="fizzy-input">
                <label for="fizzy-button" class="fizzy-label">
                    Click for Fizzy Particles! 🎉
                </label>
                <div class="fizzy-particles" id="fizzy-particles"></div>
            </div>
        </div>

        <div class="fizzy-info">
            <p>💫 Click the button above to see fizzy particles explode outward!</p>
        </div>
    </div>

    <script>
        // SVG sparkle path (star shape)
        const sparkleSVG = `
            <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M93.781 51.578C95 50.969 96 49.359 96 48c0-1.375-1-2.969-2.219-3.578 0 0-22.868-1.514-31.781-10.422-8.915-8.91-10.438-31.781-10.438-31.781C50.969 1 49.375 0 48 0s-2.969 1-3.594 2.219c0 0-1.5 22.87-10.438 31.781-8.938 8.908-31.781 10.422-31.781 10.422C1 44.031 0 45.625 0 48c0 1.359 1 2.969 2.219 3.578 0 0 22.843 1.514 31.781 10.422 8.938 8.911 10.438 31.781 10.438 31.781C45.031 95 46.625 96 48 96s2.969-1 3.578-2.219c0 0 1.514-22.87 10.438-31.781 8.913-8.908 31.781-10.422 31.781-10.422C94 51.031 95 49.359 95 48c0-1.375-1-2.969-2.219-3.578z" fill="#FFD700"/>
            </svg>
        `;

        const widgetItem = document.querySelector('.widget-item');
        const widgetTitle = document.querySelector('.widget-title');

        // Create sparkles around the widget
        function createSparkle() {
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';
            sparkle.innerHTML = sparkleSVG;
            
            // Random position around the widget
            const widgetRect = widgetItem.getBoundingClientRect();
            const maxX = widgetRect.width;
            const maxY = widgetRect.height;
            
            // Position sparkle randomly but ensure it's visible
            const x = Math.random() * (maxX - 48) + 24;
            const y = Math.random() * (maxY - 48) + 24;
            
            sparkle.style.left = x + 'px';
            sparkle.style.top = y + 'px';
            
            // Random delay and duration
            const delay = Math.random() * 2;
            const duration = 1.5 + Math.random() * 1;
            sparkle.style.animationDelay = delay + 's';
            sparkle.style.animationDuration = duration + 's';
            
            widgetItem.appendChild(sparkle);
            
            // Activate sparkle
            setTimeout(() => {
                sparkle.classList.add('active');
            }, 10);
            
            // Remove sparkle after animation
            setTimeout(() => {
                if (sparkle.parentNode) {
                    sparkle.parentNode.removeChild(sparkle);
                }
            }, (delay + duration) * 1000);
        }

        // Create multiple sparkles continuously
        function startSparkles() {
            // Create initial sparkles
            for (let i = 0; i < 5; i++) {
                setTimeout(() => {
                    createSparkle();
                }, i * 400);
            }
            
            // Continue creating sparkles at random intervals
            setInterval(() => {
                if (Math.random() > 0.3) { // 70% chance to create a sparkle
                    createSparkle();
                }
            }, 800 + Math.random() * 1200);
        }

        // Start sparkles when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startSparkles);
        } else {
            startSparkles();
        }

        // Also create sparkles on title hover for extra effect
        widgetTitle.addEventListener('mouseenter', () => {
            for (let i = 0; i < 3; i++) {
                setTimeout(() => {
                    createSparkle();
                }, i * 100);
            }
        });

        // Fizzy Particle Effect
        const fizzyInput = document.getElementById('fizzy-button');
        const fizzyParticles = document.getElementById('fizzy-particles');
        const fizzyLabel = document.querySelector('.fizzy-label');

        // Create 52 particle spots (like the CodePen)
        for (let i = 1; i <= 52; i++) {
            const spot = document.createElement('div');
            spot.className = 'fizzy-spot';
            
            // Start from center, explode outward
            spot.style.left = '50%';
            spot.style.top = '50%';
            
            // Calculate movement direction (outward from center)
            const angle = (i / 52) * Math.PI * 2;
            const distance = 100 + Math.random() * 50; // Random distance 100-150px
            const moveX = Math.cos(angle) * distance;
            const moveY = Math.sin(angle) * distance;
            
            // Set CSS custom properties for the animation
            spot.style.setProperty('--move-x', moveX + 'px');
            spot.style.setProperty('--move-y', moveY + 'px');
            
            // Random delay for staggered animation
            spot.style.animationDelay = `${Math.random() * 0.3}s`;
            
            // Random size variation
            const size = 6 + Math.random() * 4;
            spot.style.width = size + 'px';
            spot.style.height = size + 'px';
            
            fizzyParticles.appendChild(spot);
        }

        // Reset checkbox after animation completes
        fizzyInput.addEventListener('change', function() {
            if (this.checked) {
                setTimeout(() => {
                    this.checked = false;
                }, 600); // Reset after animation completes
            }
        });

        // Add sparkles around fizzy button
        const fizzyButtonWrapper = document.getElementById('fizzy-button-wrapper');

        function createFizzySparkle() {
            const sparkle = document.createElement('div');
            sparkle.className = 'fizzy-sparkle';
            sparkle.innerHTML = sparkleSVG;
            
            // Get button dimensions
            const buttonRect = fizzyLabel.getBoundingClientRect();
            
            // Position sparkle randomly ON the button surface
            const buttonWidth = buttonRect.width;
            const buttonHeight = buttonRect.height;
            
            // Random position within button bounds (with padding from edges)
            const padding = 15; // Padding from button edges
            const x = padding + Math.random() * (buttonWidth - padding * 2);
            const y = padding + Math.random() * (buttonHeight - padding * 2);
            
            sparkle.style.left = x + 'px';
            sparkle.style.top = y + 'px';
            
            // Random delay and duration
            const delay = Math.random() * 1.5;
            const duration = 1.5 + Math.random() * 1;
            sparkle.style.animationDelay = delay + 's';
            sparkle.style.animationDuration = duration + 's';
            
            fizzyLabel.appendChild(sparkle);
            
            // Activate sparkle
            setTimeout(() => {
                sparkle.classList.add('active');
            }, 10);
            
            // Remove sparkle after animation
            setTimeout(() => {
                if (sparkle.parentNode) {
                    sparkle.parentNode.removeChild(sparkle);
                }
            }, (delay + duration) * 1000);
        }

        // Create sparkles continuously around the button
        function startFizzySparkles() {
            // Create initial sparkles
            for (let i = 0; i < 4; i++) {
                setTimeout(() => {
                    createFizzySparkle();
                }, i * 500);
            }
            
            // Continue creating sparkles at random intervals
            setInterval(() => {
                if (Math.random() > 0.4) { // 60% chance to create a sparkle
                    createFizzySparkle();
                }
            }, 1000 + Math.random() * 1500);
        }

        // Start sparkles when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startFizzySparkles);
        } else {
            startFizzySparkles();
        }

        // Also create extra sparkles on button hover
        fizzyLabel.addEventListener('mouseenter', () => {
            for (let i = 0; i < 3; i++) {
                setTimeout(() => {
                    createFizzySparkle();
                }, i * 80);
            }
        });
    </script>
</body>
</html>

