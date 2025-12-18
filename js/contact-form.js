/**
 * Contact Form Widget Logic
 * Handles opening/closing drawers and AJAX form submission
 */

(function () {
    'use strict';

    window.openContactDrawer = function (widgetId, suffix = '') {
        const uniqueId = 'contact-drawer-' + widgetId + suffix;
        const uniqueOverlayId = 'contact-overlay-' + widgetId + suffix;
        const drawer = document.getElementById(uniqueId);
        const overlay = document.getElementById(uniqueOverlayId);

        if (drawer && overlay) {
            drawer.classList.add('visible'); // Use visible class instead of open for newer drawer styles if applicable, but sticky to open/active for now?
            // Actually, newer styles in widget-styles.css use .visible for overlay and drawer-bottom transform
            // Let's check what existing styles use. 
            // drawers.css uses .open and .active
            // widget-styles.css uses .visible
            // Let's support BOTH to be safe or migrate to one. 
            // The renderer added 'drawer-bottom' class which uses 'visible' in widget-styles.css
            // But 'contact-drawer' might rely on 'open' from drawers.css?
            // Let's just add ALL classes to be safe.
            drawer.classList.add('open');
            drawer.classList.add('visible');
            overlay.classList.add('active');
            overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeContactDrawer = function (widgetId, suffix = '') {
        const uniqueId = 'contact-drawer-' + widgetId + suffix;
        const uniqueOverlayId = 'contact-overlay-' + widgetId + suffix;
        const drawer = document.getElementById(uniqueId);
        const overlay = document.getElementById(uniqueOverlayId);

        if (drawer && overlay) {
            drawer.classList.remove('open');
            drawer.classList.remove('visible');
            overlay.classList.remove('active');
            overlay.classList.remove('visible');
            document.body.style.overflow = '';
        }
    };

    window.submitContactForm = function (event, widgetId, pageId, suffix = '') {
        event.preventDefault();

        const formId = 'contact-form-' + widgetId + suffix;
        const form = document.getElementById(formId);
        // Fallback if form not found with suffix
        const formEl = form ? form : document.getElementById('contact-form-' + widgetId);

        const statusId = 'contact-message-' + widgetId + '-status' + suffix;
        const statusDiv = document.getElementById(statusId);
        const submitBtn = form.querySelector('button[type="submit"]');

        const formData = new FormData(form);
        formData.append('widget_id', widgetId);
        formData.append('page_id', pageId);

        // Disable button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        statusDiv.style.display = 'none';

        fetch('/api/submit_contact.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                statusDiv.style.display = 'block';
                if (data.success) {
                    statusDiv.textContent = data.message;
                    statusDiv.className = 'form-status success';
                    statusDiv.style.color = '#28a745';
                    form.reset();
                    // Close after delay
                    setTimeout(() => {
                        closeContactDrawer(widgetId);
                        statusDiv.style.display = 'none';
                        // Reset button text
                        submitBtn.textContent = 'Send Message';
                    }, 2000);
                } else {
                    statusDiv.textContent = data.error || 'Failed to send message.';
                    statusDiv.className = 'form-status error';
                    statusDiv.style.color = '#dc3545';
                }
            })
            .catch(err => {
                console.error(err);
                statusDiv.style.display = 'block';
                statusDiv.textContent = 'Network error. Please try again.';
                statusDiv.className = 'form-status error';
                statusDiv.style.color = '#dc3545';
            })
            .finally(() => {
                submitBtn.disabled = false;
                if (submitBtn.textContent === 'Sending...') {
                    submitBtn.textContent = 'Send Message';
                }
            });
    };

    // Global Esc key handler
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Close any open contact drawers
            document.querySelectorAll('.contact-drawer.open').forEach(drawer => {
                // Extract ID
                const id = drawer.id.replace('contact-drawer-', '');
                if (id) window.closeContactDrawer(id);
            });
        }
    });

})();
