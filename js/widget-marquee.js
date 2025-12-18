/**
 * Marquee scrolling for Custom Link widget descriptions
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function () {
    'use strict';

    // Flag to prevent multiple initializations
    const observedElements = new WeakSet();
    window.MarqueeLogs = []; // Diagnostic logs for debugging

    function initWidgetMarquee(element) {
        // Process widget descriptions, titles, and people widget paragraphs
        if (!element.closest('.widget-item')) {
            return;
        }
        // Check if it's a widget title, description or people widget paragraph
        if (!element.classList.contains('widget-description') &&
            !element.classList.contains('people-widget-paragraph') &&
            !element.classList.contains('widget-title')) {
            return;
        }

        // Skip if element contains SVG
        if (element.querySelector('svg')) {
            return;
        }

        // Verify we have a valid width to measure
        const clientWidth = element.clientWidth;

        let shouldForceMarquee = false;

        if (clientWidth <= 0) {
            // Element hidden/not rendered.
            // FALLBACK: If text is long enough, assume it overflows and force marquee.
            // This handles headless browsers and edge cases where layout is late.
            if (element.textContent.length > 25) {
                shouldForceMarquee = true;
            } else {
                return;
            }
        }

        // REMOVED: The check for dataset.marqueeProcessed was preventing re-evaluation
        // when the element resized. We WANT to re-evaluate on every resize.
        // The unwrap logic below handles idempotency.

        // Unwrap content if already processed to get accurate measurements (idempotency)
        const contentSpan = element.querySelector('.marquee-content');
        if (contentSpan) {
            const firstText = contentSpan.querySelector('.marquee-text');
            if (firstText) {
                element.innerHTML = firstText.innerHTML;
            } else {
                element.innerHTML = contentSpan.innerHTML;
            }
            element.classList.remove('marquee');
        }

        // Skip if somehow still has marquee-text
        if (element.querySelector('.marquee-text')) {
            return;
        }

        // MEASUREMENT
        const originalWhiteSpace = element.style.whiteSpace;
        const originalOverflow = element.style.overflow;
        const originalDisplay = element.style.display;

        // Capture initial dimensions
        const initialHeight = element.clientHeight;
        const initialWidth = element.clientWidth;

        // Force styles to detect accurate overflow
        element.style.whiteSpace = 'nowrap';
        element.style.overflow = 'hidden';
        element.style.display = 'block';

        const scrollWidth = element.scrollWidth;
        const newHeight = element.clientHeight;

        // Restore styles
        element.style.whiteSpace = originalWhiteSpace;
        element.style.overflow = originalOverflow;
        element.style.display = originalDisplay;

        // Check for overflow:
        // 1. Width overflow (scrollWidth > initialWidth)
        // 2. Height drop (wrapping -> single line) indicating it was multi-line
        // 3. Heuristic: Text is significantly longer than container (approx 7px per char)
        // 4. Forced fallback (for 0-width headless cases)
        const isWidthOverflow = scrollWidth >= (initialWidth + 1);
        const isHeightOverflow = initialHeight > (newHeight + 5);

        // Conservative heuristic: If estimated width is > 1.2x container, it likely overflows.
        // Or if text is simply very long (> 100 chars), assume it overflows mobile screens.
        const estimatedTextWidth = element.textContent.length * 7;
        const isHeuristicOverflow = (initialWidth > 0 && estimatedTextWidth > (initialWidth * 1.2)) || element.textContent.length > 150;

        if (isWidthOverflow || isHeightOverflow || isHeuristicOverflow || shouldForceMarquee) {
            // Apply marquee
            element.classList.add('marquee');

            const content = element.innerHTML;
            element.innerHTML = '<span class="marquee-content"><span class="marquee-text">' + content + '</span><span class="marquee-text">' + content + '</span></span>';

            const newContentSpan = element.querySelector('.marquee-content');
            if (newContentSpan) {
                // Determine duration (approx 50px/s)
                const effectiveWidth = scrollWidth > 0 ? scrollWidth : (element.textContent.length * 10);
                const duration = Math.max(10, effectiveWidth / 50);
                // FORCE animation inline with !important via cssText to bypass all overrides
                // We preserve other styles (though at this point it's just created)
                newContentSpan.style.cssText = `
                    --marquee-duration: ${duration}s;
                    animation: widgetMarquee ${duration}s linear infinite !important;
                    min-width: 100% !important;
                    display: inline-block !important;
                    white-space: nowrap !important;
                `;
            }

            // Mark as processed
            element.dataset.marqueeProcessed = 'true';
        } else {
            element.dataset.marqueeProcessed = 'true';
        }
    }

    // ResizeObserver callback
    const observer = new ResizeObserver(entries => {
        for (const entry of entries) {
            if (entry.contentRect.width > 0) {
                // Element has size, try to initialize marquee
                // We wrap in requestAnimationFrame to avoid "ResizeObserver loop limit exceeded"
                requestAnimationFrame(() => {
                    initWidgetMarquee(entry.target);
                });
            }
        }
    });

    function setupObserver() {
        const targets = document.querySelectorAll('.widget-item .widget-description, .widget-item .people-widget-paragraph, .widget-item .widget-title');
        targets.forEach(el => {
            if (!observedElements.has(el)) {
                observer.observe(el);
                observedElements.add(el);
                // Run immediate check to handle initial state (including 0-width fallbacks)
                initWidgetMarquee(el);
            }
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupObserver);
    } else {
        setupObserver();
    }

    // Also re-run on specific events if needed (like fonts loaded)
    document.fonts.ready.then(setupObserver);

    // Optional: Observe DOM mutations to catch new widgets added dynamically
    const mutationObserver = new MutationObserver((mutations) => {
        let shouldUpdate = false;
        for (const mutation of mutations) {
            if (mutation.addedNodes.length > 0) {
                shouldUpdate = true;
                break;
            }
        }
        if (shouldUpdate) {
            setupObserver();
        }
    });

    mutationObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

})();
