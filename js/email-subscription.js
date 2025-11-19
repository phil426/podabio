/**
 * Email Subscription Drawer
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function() {
    'use strict';
    
    function openEmailDrawer() {
        const drawer = document.getElementById('email-drawer');
        const overlay = document.getElementById('email-overlay');
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeEmailDrawer() {
        const drawer = document.getElementById('email-drawer');
        const overlay = document.getElementById('email-overlay');
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    function subscribeEmail(event) {
        event.preventDefault();
        
        const form = event.target;
        const email = form.querySelector('#subscribe-email').value;
        const messageDiv = document.getElementById('subscribe-message');
        const pageId = window.emailSubscriptionPageId;
        
        if (!email) {
            messageDiv.textContent = 'Please enter an email address';
            messageDiv.style.display = 'block';
            messageDiv.style.color = 'var(--color-accent-primary)';
            return;
        }
        
        const formData = new FormData();
        formData.append('page_id', pageId);
        formData.append('email', email);
        
        fetch('/api/subscribe.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.style.display = 'block';
            if (data.success) {
                messageDiv.textContent = data.message || 'Successfully subscribed!';
                messageDiv.style.color = 'green';
                form.reset();
            } else {
                messageDiv.textContent = data.error || 'Failed to subscribe';
                messageDiv.style.color = 'red';
            }
        })
        .catch(() => {
            messageDiv.style.display = 'block';
            messageDiv.textContent = 'An error occurred. Please try again.';
            messageDiv.style.color = 'red';
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('email-subscribe-form');
            if (form) {
                form.addEventListener('submit', subscribeEmail);
            }
            
            // Close drawer on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEmailDrawer();
                }
            });
        });
    } else {
        const form = document.getElementById('email-subscribe-form');
        if (form) {
            form.addEventListener('submit', subscribeEmail);
        }
        
        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEmailDrawer();
            }
        });
    }
    
    // Expose functions globally for inline event handlers
    window.openEmailDrawer = openEmailDrawer;
    window.closeEmailDrawer = closeEmailDrawer;
})();

