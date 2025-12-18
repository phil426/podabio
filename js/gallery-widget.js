/**
 * Gallery Widget Interaction Logic
 * Handles opening/closing of Thumbnail Drawer and Swipe Deck (Lightbox)
 */

(function () {
    'use strict';

    // Prevent duplicate initialization
    if (window.podabioGalleryInitialized) return;
    window.podabioGalleryInitialized = true;

    // --- Gallery Modal (Thumbnail Drawer) ---

    window.openGalleryModal = function (id, suffix = "") {
        const drawerId = "gallery-drawer-" + id + suffix;
        const overlayId = "gallery-drawer-overlay-" + id + suffix;

        const drawer = document.getElementById(drawerId);
        const overlay = document.getElementById(overlayId);

        if (drawer) {
            drawer.classList.add("visible");
            drawer.classList.add("open"); // Support both class conventions
        }

        if (overlay) {
            overlay.classList.add("visible");
            overlay.classList.add("active");
        }

        document.body.style.overflow = "hidden"; // Prevent background scrolling
    };

    window.closeGalleryModal = function (id, suffix = "") {
        const drawerId = "gallery-drawer-" + id + suffix;
        const overlayId = "gallery-drawer-overlay-" + id + suffix;

        const drawer = document.getElementById(drawerId);
        const overlay = document.getElementById(overlayId);

        if (drawer) {
            drawer.classList.remove("visible");
            drawer.classList.remove("open");
        }

        if (overlay) {
            overlay.classList.remove("visible");
            overlay.classList.remove("active");
        }

        // Only restore scrolling if no other modals are open
        // Simplified: just restore it for now
        document.body.style.overflow = "";
    };

    // --- Swipe Deck (Full Screen Lightbox) ---

    window.openSwipeDeck = function (id, index, suffix = "") {
        const deckId = "gallery-swipe-deck-" + id + suffix;
        const deck = document.getElementById(deckId);

        if (deck) {
            deck.classList.add("visible");

            // Scroll to the selected image
            // We use a small timeout to ensure display:flex has applied and layout is calculated
            setTimeout(() => {
                const itemId = "gallery-item-" + id + "-" + index + suffix;
                const item = document.getElementById(itemId);
                if (item) {
                    item.scrollIntoView({
                        behavior: "auto", // Instant jump, not smooth for initial open
                        inline: "center",
                        block: "center"
                    });
                }
            }, 10);
        }
    };

    window.closeSwipeDeck = function (id, suffix = "") {
        const deckId = "gallery-swipe-deck-" + id + suffix;
        const deck = document.getElementById(deckId);

        if (deck) {
            deck.classList.remove("visible");
        }
    };

    // Global Esc Key Handler for Gallery
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Close all visible decks
            document.querySelectorAll('.gallery-swipe-deck.visible').forEach(deck => {
                deck.classList.remove("visible");
            });

            // Close all visible drawers
            document.querySelectorAll('.gallery-drawer.visible, .gallery-drawer.open').forEach(drawer => {
                // Try to parse ID from id string "gallery-drawer-{id}{suffix}"
                // Or just remove class directly
                drawer.classList.remove("visible");
                drawer.classList.remove("open");
                document.body.style.overflow = "";
            });

            // Hide Overlays
            document.querySelectorAll('.drawer-overlay.visible, .drawer-overlay.active').forEach(overlay => {
                overlay.classList.remove("visible");
                overlay.classList.remove("active");
            });
        }
    });

})();
