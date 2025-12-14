/**
 * Embed Code Widget Logic
 * Handles opening/closing drawers for modal embed content
 */

(function () {
    'use strict';

    window.openEmbedDrawer = function (widgetId) {
        const drawer = document.getElementById('embed-drawer-' + widgetId);
        const overlay = document.getElementById('embed-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Execute any scripts inside the drawer that might need re-initialization
            // Note: Scripts in innerHTML don't auto-execute, but if they came from PHP they are already in DOM.
            // However, some embeds (like Twitter/IG) might need a refresh signal.
            // For now, we rely on them being present in DOM.
        }
    };

    window.closeEmbedDrawer = function (widgetId) {
        const drawer = document.getElementById('embed-drawer-' + widgetId);
        const overlay = document.getElementById('embed-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Global Esc key handler is already in contact-form.js which handles generic closing,
    // but we should add specific handler here or rely on specific IDs.
    // Let's add specific listener for robustness.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.embed-drawer.open').forEach(drawer => {
                const id = drawer.id.replace('embed-drawer-', '');
                if (id) window.closeEmbedDrawer(id);
            });
        }
    });

})();
