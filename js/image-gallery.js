/**
 * Image Gallery Widget Logic
 * Handles 2-stage navigation: Button -> Thumbnails Modal -> Full Screen Swipe Deck
 */

(function () {
    'use strict';

    // State
    const galleries = {};
    let activeWidgetId = null;

    // Helper to get gallery data
    function getGalleryData(widgetId) {
        if (galleries[widgetId]) return galleries[widgetId];

        let widgetBtn = document.querySelector(`.widget-gallery-btn[data-widget-id="${widgetId}"]`);
        // Fallback for re-renders or if button not found easily (should depend on renderer)
        if (!widgetBtn) return null;

        const imagesStr = widgetBtn.getAttribute('data-images');
        try {
            const images = JSON.parse(imagesStr);
            galleries[widgetId] = { images: images, currentIndex: 0 };
            return galleries[widgetId];
        } catch (e) {
            console.error('Failed to parse gallery images', e);
            return null;
        }
    }

    // --- Stage 1: Thumbnails Modal ---

    window.openGalleryModal = function (widgetId) {
        // Initialize data
        getGalleryData(widgetId);

        const drawer = document.getElementById('gallery-drawer-' + widgetId);
        const overlay = document.getElementById('gallery-drawer-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            activeWidgetId = widgetId;
        }
    };

    window.closeGalleryModal = function (widgetId) {
        const drawer = document.getElementById('gallery-drawer-' + widgetId);
        const overlay = document.getElementById('gallery-drawer-overlay-' + widgetId);
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            activeWidgetId = null;
        }
    };


    // --- Stage 2: Swipe Deck (Full Screen) ---

    window.openSwipeDeck = function (widgetId, initialIndex) {
        const data = getGalleryData(widgetId);
        if (!data || !data.images.length) return;

        data.currentIndex = initialIndex >= 0 && initialIndex < data.images.length ? initialIndex : 0;

        // ensure parent modal stays open but perhaps hidden or behind?
        // User wants: "Closing deck takes you back to thumbnails"
        // So we just layer the swipe deck ON TOP of the thumbnails modal.

        let deckOverlay = document.getElementById('gallery-swipe-deck');
        if (!deckOverlay) {
            deckOverlay = document.createElement('div');
            deckOverlay.id = 'gallery-swipe-deck';
            deckOverlay.className = 'gallery-swipe-deck';
            deckOverlay.innerHTML = `
                <button class="deck-close" onclick="closeSwipeDeck()"><i class="fas fa-times"></i></button>
                <div class="deck-container">
                    <div class="deck-track"></div>
                </div>
                <button class="deck-nav prev" onclick="navigateDeck(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="deck-nav next" onclick="navigateDeck(1)"><i class="fas fa-chevron-right"></i></button>
                <div class="deck-counter"></div>
                <div class="deck-controls">
                    <button class="deck-action-btn" onclick="closeSwipeDeck()" title="Back to Grid"><i class="fas fa-th"></i> Grid</button>
                </div>
            `;
            document.body.appendChild(deckOverlay);

            // Add swipe handlers
            initSwipe(deckOverlay);
        }

        updateDeckView();
        deckOverlay.classList.add('active');
        // Body overflow is already hidden from first modal, so no change needed
    };

    window.closeSwipeDeck = function () {
        const deckOverlay = document.getElementById('gallery-swipe-deck');
        if (deckOverlay) {
            deckOverlay.classList.remove('active');
            // Do NOT body overflow = '' because we are going back to thumb modal which needs it hidden
        }
    };

    window.navigateDeck = function (direction) {
        if (!activeWidgetId) return;
        const data = galleries[activeWidgetId];
        if (!data) return;

        let newIndex = data.currentIndex + direction;
        if (newIndex < 0) newIndex = data.images.length - 1;
        if (newIndex >= data.images.length) newIndex = 0;

        data.currentIndex = newIndex;
        updateDeckView();
    };

    function updateDeckView() {
        if (!activeWidgetId) return;
        const data = galleries[activeWidgetId];
        const deckOverlay = document.getElementById('gallery-swipe-deck');
        const track = deckOverlay.querySelector('.deck-track');
        const counter = deckOverlay.querySelector('.deck-counter');

        track.innerHTML = '';

        const img = document.createElement('img');
        img.src = data.images[data.currentIndex];
        img.className = 'deck-image fade-in';
        track.appendChild(img);

        counter.textContent = `${data.currentIndex + 1} / ${data.images.length}`;
    }

    function initSwipe(element) {
        let touchStartX = 0;
        let touchEndX = 0;

        element.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        element.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleGesture();
        }, { passive: true });

        function handleGesture() {
            if (activeWidgetId === null) return;
            if (touchEndX < touchStartX - 50) navigateDeck(1);
            if (touchEndX > touchStartX + 50) navigateDeck(-1);
        }
    }

    // Keyboard
    document.addEventListener('keydown', function (e) {
        const deck = document.getElementById('gallery-swipe-deck');
        const isDeckOpen = deck && deck.classList.contains('active');

        if (isDeckOpen) {
            if (e.key === 'Escape') closeSwipeDeck();
            if (e.key === 'ArrowLeft') navigateDeck(-1);
            if (e.key === 'ArrowRight') navigateDeck(1);
            return;
        }

        // If specific gallery drawer is open
        if (activeWidgetId && !isDeckOpen) {
            if (e.key === 'Escape') closeGalleryModal(activeWidgetId);
        }
    });

})();
