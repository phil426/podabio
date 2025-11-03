<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flame Effect Demo - Featured Widget</title>
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

        .featured-widget {
            position: relative;
            width: 100%;
        }

        .widget-item {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1;
            min-height: 120px;
        }

        .widget-thumbnail-wrapper {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .widget-content {
            flex: 1;
        }

        .widget-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .widget-description {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .flame-canvas-wrapper {
            position: absolute;
            top: -40px;
            left: -40px;
            right: -40px;
            bottom: -40px;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            border-radius: 20px;
        }

        .info {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 0.875rem;
            z-index: 100;
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .info a {
            color: #60a5fa;
            text-decoration: none;
        }

        .info a:hover {
            text-decoration: underline;
        }

        h1 {
            color: white;
            margin-bottom: 1rem;
            text-align: center;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="demo-wrapper">
        <div>
            <h1>🔥 Flame Effect Demo</h1>
            <p class="subtitle">This demonstrates how the flame effect will appear around featured widgets</p>
        </div>
        
        <div class="featured-widget">
            <div class="flame-canvas-wrapper">
                <canvas id="flame-canvas"></canvas>
            </div>
            
            <div class="widget-item">
                <div class="widget-thumbnail-wrapper">
                    🔥
                </div>
                <div class="widget-content">
                    <div class="widget-title">Featured Widget with Flame Effect</div>
                    <div class="widget-description">Watch the animated flames dance around this widget!</div>
                </div>
            </div>
        </div>
    </div>

    <div class="info">
        Flame effect adapted from <a href="https://codepen.io/ste-vg/pen/MReVYj" target="_blank">CodePen by Steve Gardner</a>
    </div>

    <!-- PIXI.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/pixi.js@7.3.2/dist/pixi.min.js"></script>

    <script>
        // Adapted from CodePen: https://codepen.io/ste-vg/pen/MReVYj
        let pixelate = false;
        let background = false;

        // Position class
        class Position {
            constructor(x, y) {
                this.x = x;
                this.y = y;
            }
        }

        // Ember class - creates animated flame particles
        class Ember {
            constructor(colors, app, pixelate = false, canvasWidth, canvasHeight) {
                this.emberBlobs = [];
                this.embers = new PIXI.Container();
                this.canvasWidth = canvasWidth;
                this.canvasHeight = canvasHeight;

                if (pixelate) {
                    this.embers.filters = [new PIXI.filters.PixelateFilter()];
                }

                // Create multiple ember particles - more particles for better effect
                // Each color creates multiple particles
                colors.forEach(color => {
                    // Create 8-12 particles per color for denser flames
                    const particleCount = 8 + Math.floor(Math.random() * 5);
                    for (let i = 0; i < particleCount; i++) {
                        const circle = new PIXI.Graphics();
                        // Use gradient-like effect - vary alpha for each particle
                        const alpha = 0.6 + Math.random() * 0.4;
                        circle.beginFill(color, alpha);
                        circle.drawCircle(0, 0, 12 + Math.random() * 12);
                        circle.endFill();
                        
                        // Start particles at bottom of widget, spread across width
                        const blob = {
                            circle: circle,
                            position: new Position(
                                Math.random() * canvasWidth,
                                canvasHeight - 5 + Math.random() * 15
                            ),
                            velocity: new Position(
                                (Math.random() - 0.5) * 1.2,
                                -Math.random() * 2.2 - 0.8
                            ),
                            life: 0.7 + Math.random() * 0.3, // Vary starting life
                            decay: Math.random() * 0.012 + 0.006,
                            size: Math.random() * 0.5 + 0.5,
                            color: color
                        };
                        
                        circle.alpha = blob.life;
                        circle.scale.set(blob.size);
                        circle.position.set(blob.position.x, blob.position.y);
                        
                        this.emberBlobs.push(blob);
                        this.embers.addChild(circle);
                    }
                });

                app.stage.addChild(this.embers);
            }

            update() {
                this.emberBlobs.forEach(blob => {
                    // Update position
                    blob.position.x += blob.velocity.x;
                    blob.position.y += blob.velocity.y;
                    
                    // Update life (fade out)
                    blob.life -= blob.decay;
                    
                    // Add turbulence to velocity
                    blob.velocity.x += (Math.random() - 0.5) * 0.15;
                    blob.velocity.y -= Math.random() * 0.08;
                    
                    // Update visual properties
                    blob.circle.alpha = blob.life;
                    blob.circle.position.set(blob.position.x, blob.position.y);
                    blob.circle.scale.set(blob.size * blob.life);
                    
                    // Reset particles that fade out or go too high
                    if (blob.life <= 0 || blob.position.y < -30) {
                        // Reset to bottom of widget
                        blob.position.x = Math.random() * this.canvasWidth;
                        blob.position.y = this.canvasHeight - 10 + Math.random() * 20;
                        blob.life = 1.0;
                        blob.velocity.x = (Math.random() - 0.5) * 1.5;
                        blob.velocity.y = -Math.random() * 2.5 - 0.5;
                        blob.size = Math.random() * 0.4 + 0.6;
                    }
                });
            }

            destroy() {
                this.emberBlobs = [];
                if (this.embers && this.embers.parent) {
                    this.embers.parent.removeChild(this.embers);
                }
            }
        }

        // Initialize flame effect when page loads
        function initFlameEffect() {
            const flameWrapper = document.querySelector('.flame-canvas-wrapper');
            const canvasEl = document.getElementById('flame-canvas');
            
            if (!flameWrapper || !canvasEl) return;

            // Get dimensions of the wrapper (widget + padding)
            const rect = flameWrapper.getBoundingClientRect();
            const canvasWidth = rect.width;
            const canvasHeight = rect.height;

            // Initialize PIXI application
            const app = new PIXI.Application({
                view: canvasEl,
                width: canvasWidth,
                height: canvasHeight,
                backgroundColor: 0x000000,
                backgroundAlpha: 0, // Transparent background
                antialias: true,
                resolution: window.devicePixelRatio || 1,
                autoDensity: true
            });

            // Flame colors (orange, red, yellow spectrum)
            const flameColors = [
                0xFF4500, // OrangeRed
                0xFF6347, // Tomato
                0xFF7F50, // Coral
                0xFFA500, // Orange
                0xFFD700, // Gold
                0xFFFF00, // Yellow
            ];

            // Create ember system with widget dimensions
            const ember = new Ember(flameColors, app, pixelate, canvasWidth, canvasHeight);

            // Animation loop
            app.ticker.add(() => {
                ember.update();
            });

            // Handle window resize
            window.addEventListener('resize', () => {
                const newRect = flameWrapper.getBoundingClientRect();
                app.renderer.resize(newRect.width, newRect.height);
                // Note: Would need to recreate embers with new dimensions for production
            });
        }

        // Wait for PIXI.js to load, then initialize
        function checkPIXIAndInit() {
            if (typeof PIXI !== 'undefined' && PIXI.Application) {
                initFlameEffect();
            } else {
                setTimeout(checkPIXIAndInit, 50);
            }
        }

        // Start checking when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkPIXIAndInit);
        } else {
            checkPIXIAndInit();
        }
    </script>
</body>
</html>

