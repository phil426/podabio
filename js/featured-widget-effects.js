/**
 * Featured Widget Effects
 * Random timing for movement-based effects
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function() {
    'use strict';
    
    // Random timing for movement-based Featured Widget effects
    // Creates illusion of "something alive" inside occasionally causing movement
    (function() {
        const movementEffects = ['jiggle', 'shake', 'pulse', 'rotating-glow'];
        const featuredWidgets = document.querySelectorAll('.featured-widget');
        
        featuredWidgets.forEach(widget => {
            const effectClass = Array.from(widget.classList).find(cls => cls.startsWith('featured-effect-'));
            if (!effectClass) return;
            
            const effect = effectClass.replace('featured-effect-', '');
            if (!movementEffects.includes(effect)) return; // Static effects (burn, blink) continue as normal
            
            function triggerAnimation() {
                widget.classList.add('active');
                setTimeout(() => {
                    widget.classList.remove('active');
                }, effect === 'rotating-glow' ? 2000 : (effect === 'pulse' ? 1000 : 600));
            }
            
            // Initial trigger after random delay (0.5-2 seconds)
            setTimeout(triggerAnimation, 500 + Math.random() * 1500);
            
            // Continue triggering at random intervals (2-8 seconds)
            function scheduleNext() {
                const delay = 2000 + Math.random() * 6000; // 2-8 seconds
                setTimeout(() => {
                    triggerAnimation();
                    scheduleNext();
                }, delay);
            }
            scheduleNext();
        });
    })();
    
    // Sparkles Effect for Featured Widgets
    (function() {
        // SVG sparkle path (star shape)
        const sparkleSVG = `
            <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M93.781 51.578C95 50.969 96 49.359 96 48c0-1.375-1-2.969-2.219-3.578 0 0-22.868-1.514-31.781-10.422-8.915-8.91-10.438-31.781-10.438-31.781C50.969 1 49.375 0 48 0s-2.969 1-3.594 2.219c0 0-1.5 22.87-10.438 31.781-8.938 8.908-31.781 10.422-31.781 10.422C1 44.031 0 45.625 0 48c0 1.359 1 2.969 2.219 3.578 0 0 22.843 1.514 31.781 10.422 8.938 8.911 10.438 31.781 10.438 31.781C45.031 95 46.625 96 48 96s2.969-1 3.578-2.219c0 0 1.514-22.87 10.438-31.781 8.913-8.908 31.781-10.422 31.781-10.422C94 51.031 95 49.359 95 48c0-1.375-1-2.969-2.219-3.578z" fill="var(--color-accent-primary)"/>
            </svg>
        `;
        
        const sparklesWidgets = document.querySelectorAll('.featured-widget.featured-effect-sparkles');
        
        sparklesWidgets.forEach(widget => {
            const widgetItem = widget.querySelector('.widget-item');
            if (!widgetItem) return;
            
            function createSparkle() {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle';
                sparkle.innerHTML = sparkleSVG;
                
                // Get widget dimensions
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
        });
    })();
})();

