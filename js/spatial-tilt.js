/**
 * Accelerometer-based Tilt Effect
 * Only activates if spatial-tilt class is present
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function() {
    'use strict';
    
    // Only activate if spatial-tilt class is present
    if (!document.body.classList.contains('spatial-tilt')) {
        return; // Exit if tilt effect is not enabled
    }
    
    // Check if Device Orientation API is available
    if (typeof DeviceOrientationEvent === 'undefined' || 
        typeof DeviceOrientationEvent.requestPermission === 'function') {
        // iOS 13+ requires permission
        const permissionButton = document.createElement('button');
        permissionButton.textContent = 'Enable Tilt Effect';
        permissionButton.style.position = 'fixed';
        permissionButton.style.bottom = '20px';
        permissionButton.style.right = '20px';
        permissionButton.style.padding = '12px 24px';
        permissionButton.style.background = 'var(--color-accent-primary)';
        permissionButton.style.color = 'var(--color-text-inverse)';
        permissionButton.style.border = 'none';
        permissionButton.style.borderRadius = 'var(--shape-corner-md, 0.75rem)';
        permissionButton.style.cursor = 'pointer';
        permissionButton.style.zIndex = '1000';
        permissionButton.style.fontWeight = '600';
        permissionButton.style.boxShadow = 'var(--shadow-level-2, 0 6px 16px rgba(15, 23, 42, 0.16))';
        permissionButton.onclick = function() {
            DeviceOrientationEvent.requestPermission()
                .then(response => {
                    if (response === 'granted') {
                        permissionButton.remove();
                        initTiltEffect();
                    }
                })
                .catch(() => {
                    permissionButton.textContent = 'Permission Denied';
                    permissionButton.style.background = 'var(--color-state-danger)';
                });
        };
        document.body.appendChild(permissionButton);
    } else {
        // API available, initialize immediately
        initTiltEffect();
    }
    
    function initTiltEffect() {
        const widgets = document.querySelectorAll('.widget-item');
        if (widgets.length === 0) return;
        
        let lastUpdate = 0;
        const throttleMs = 16; // ~60fps
        let rafId = null;
        
        function handleOrientation(event) {
            const now = Date.now();
            if (now - lastUpdate < throttleMs) {
                return; // Throttle updates
            }
            lastUpdate = now;
            
            // Cancel previous animation frame
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            
            // Schedule update
            rafId = requestAnimationFrame(() => {
                applyTiltTransforms(event, widgets);
            });
        }
        
        function applyTiltTransforms(event, widgetElements) {
            // Get tilt values (beta: front-to-back, gamma: left-to-right)
            const beta = event.beta || 0;  // -180 to 180
            const gamma = event.gamma || 0; // -90 to 90
            
            // Normalize values and scale for subtle movement
            const maxTilt = 15; // Maximum pixels to move
            const xOffset = (gamma / 90) * maxTilt;  // Left-right tilt
            const yOffset = (beta / 180) * maxTilt;   // Front-back tilt
            
            // Apply transforms with parallax effect (each widget moves slightly differently)
            widgetElements.forEach((widget, index) => {
                // Create subtle parallax by varying movement amount
                const parallaxFactor = 0.7 + (index % 3) * 0.1; // 0.7, 0.8, or 0.9
                const widgetX = xOffset * parallaxFactor;
                const widgetY = yOffset * parallaxFactor;
                
                widget.style.transform = `translate(${widgetX}px, ${widgetY}px)`;
            });
        }
        
        // Listen for device orientation events
        window.addEventListener('deviceorientation', handleOrientation, true);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            window.removeEventListener('deviceorientation', handleOrientation, true);
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
        });
    }
})();

