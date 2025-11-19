/**
 * Marquee scrolling for Custom Link widget descriptions
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function() {
    'use strict';
    
    function initWidgetMarquee(element) {
        // Only process widget descriptions within Custom Link widgets
        if (!element.closest('.widget-item') || !element.classList.contains('widget-description')) {
            return;
        }
        
        // Reset processed flag to allow re-evaluation
        delete element.dataset.marqueeProcessed;
        
        // Skip if element contains SVG
        if (element.querySelector('svg')) {
            return;
        }
        
        // Unwrap content first to get accurate measurements
        const contentSpan = element.querySelector('.marquee-content');
        if (contentSpan) {
            // Extract original content from first marquee-text if it exists
            const firstText = contentSpan.querySelector('.marquee-text');
            if (firstText) {
                element.innerHTML = firstText.innerHTML;
            } else {
                element.innerHTML = contentSpan.innerHTML;
            }
            element.classList.remove('marquee');
        }
        
        // Skip if already has marquee-text (already processed)
        if (element.querySelector('.marquee-text')) {
            return;
        }
        
        // Get container width - measure parent container to know available space
        // Do this BEFORE any style changes
        const parentContainer = element.parentElement; // .widget-content
        const containerWidth = parentContainer ? parentContainer.clientWidth : element.clientWidth;
        
        if (containerWidth <= 0) {
            // Container not ready yet, skip
            return;
        }
        
        // Use a temporary span to measure text width without affecting layout
        const tempSpan = document.createElement('span');
        tempSpan.style.position = 'absolute';
        tempSpan.style.visibility = 'hidden';
        tempSpan.style.whiteSpace = 'nowrap';
        tempSpan.style.fontSize = window.getComputedStyle(element).fontSize;
        tempSpan.style.fontFamily = window.getComputedStyle(element).fontFamily;
        tempSpan.style.fontWeight = window.getComputedStyle(element).fontWeight;
        tempSpan.style.letterSpacing = window.getComputedStyle(element).letterSpacing;
        tempSpan.textContent = element.textContent;
        
        document.body.appendChild(tempSpan);
        const textWidth = tempSpan.offsetWidth;
        document.body.removeChild(tempSpan);
        
        if (textWidth > containerWidth && containerWidth > 0) {
            // Text overflows when on single line - apply marquee
            element.classList.add('marquee');
            
            // Wrap content in marquee-content span and duplicate for seamless loop
            const content = element.innerHTML;
            // Duplicate content for seamless scrolling
            element.innerHTML = '<span class="marquee-content"><span class="marquee-text">' + content + '</span><span class="marquee-text">' + content + '</span></span>';
            
            const newContentSpan = element.querySelector('.marquee-content');
            if (newContentSpan) {
                // Force a reflow to get accurate measurements
                void newContentSpan.offsetWidth;
                const firstText = newContentSpan.querySelector('.marquee-text');
                if (firstText) {
                    const textWidth = firstText.scrollWidth;
                    const duration = Math.max(8, Math.min(20, (textWidth / 40))); // 8-20 seconds based on length
                    
                    // Set CSS variables for animation
                    // Scroll by exactly one text width so the duplicate seamlessly continues
                    newContentSpan.style.setProperty('--marquee-distance', `-${textWidth}px`);
                    newContentSpan.style.setProperty('--marquee-duration', `${duration}s`);
                }
            }
        }
        
        element.dataset.marqueeProcessed = 'true';
    }
    
    let isProcessing = false;
    let debounceTimer = null;
    
    function applyWidgetMarquee() {
        // Prevent infinite loops
        if (isProcessing) {
            return;
        }
        
        isProcessing = true;
        
        try {
            // Only target widget descriptions within Custom Link widgets
            document.querySelectorAll('.widget-item .widget-description').forEach(element => {
                // Skip if already has marquee-text (already fully processed)
                if (element.querySelector('.marquee-text')) {
                    return;
                }
                // Skip if already processed and has marquee-content
                if (element.dataset.marqueeProcessed === 'true' && element.querySelector('.marquee-content')) {
                    return;
                }
                initWidgetMarquee(element);
            });
        } finally {
            isProcessing = false;
        }
    }
    
    // Debounced version for observer
    function debouncedApplyWidgetMarquee() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            // Only reset flags for elements that actually changed
            document.querySelectorAll('.widget-item .widget-description').forEach(el => {
                // Only reset if it's not currently being processed
                if (!isProcessing) {
                    delete el.dataset.marqueeProcessed;
                }
            });
            applyWidgetMarquee();
        }, 100); // 100ms debounce
    }
    
    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyWidgetMarquee);
    } else {
        applyWidgetMarquee();
    }
    
    // Watch for dynamic content changes (only Custom Link widget descriptions)
    // Use debounced version to prevent infinite loops
    const observer = new MutationObserver((mutations) => {
        // Only process if mutations are not from our own code
        let shouldProcess = false;
        for (const mutation of mutations) {
            // Skip if the mutation is just attribute changes (like dataset)
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-marquee-processed') {
                continue;
            }
            // Skip if mutation is from adding marquee-content or marquee-text (our own changes)
            if (mutation.addedNodes.length > 0) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        // Skip our own marquee elements
                        if (node.classList && (node.classList.contains('marquee-content') || node.classList.contains('marquee-text'))) {
                            continue;
                        }
                        // Also check if it's a child of a marquee element
                        if (node.closest && (node.closest('.marquee-content') || node.closest('.marquee-text'))) {
                            continue;
                        }
                    }
                    shouldProcess = true;
                    break;
                }
            } else {
                shouldProcess = true;
            }
            if (shouldProcess) break;
        }
        
        if (shouldProcess) {
            debouncedApplyWidgetMarquee();
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: false // Don't watch attributes to avoid our own changes
    });
})();

