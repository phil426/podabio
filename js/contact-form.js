/**
 * Contact Form Widget Logic
 * Handles opening/closing drawers and AJAX form submission
 */

(function () {
    'use strict';

    window.openContactDrawer = function (widgetId) {
        const drawer = document.getElementById('contact-drawer-' + widgetId);
        const overlay = document.getElementById('contact-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeContactDrawer = function (widgetId) {
        const drawer = document.getElementById('contact-drawer-' + widgetId);
        const overlay = document.getElementById('contact-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    window.submitContactForm = function (event, widgetId, pageId) {
        event.preventDefault();

        const form = document.getElementById('contact-form-' + widgetId);
        const statusDiv = document.getElementById('contact-message-' + widgetId + '-status');
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
