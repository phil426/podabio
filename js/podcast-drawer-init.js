/**
 * Podcast Player Drawer Initialization
 * Extracted from page.php for better caching and maintainability
 * PodaBio
 */

(function() {
    'use strict';
    
    const drawer = document.getElementById('podcast-top-drawer');
    const toggleBtn = document.getElementById('podcast-drawer-toggle');
    const closeBtn = document.getElementById('podcast-drawer-close');
    const banner = document.getElementById('podcast-top-banner');
    
    if (!drawer || !toggleBtn) return;
    
    // Namespace for drawer functions
    const PodcastDrawerController = {
        isPeeking: false,
        
        openDrawer: function() {
            drawer.style.display = 'flex';
            // Force reflow
            void drawer.offsetWidth;
            drawer.classList.remove('peek');
            drawer.classList.add('open');
            // Update banner state
            if (banner) {
                banner.classList.remove('drawer-peek');
                banner.classList.add('drawer-open');
            }
            document.body.style.overflow = 'hidden';
            this.isPeeking = false;
        },
        
        closeDrawer: function() {
            // Force hide scrollbars immediately
            document.body.style.overflow = 'hidden';
            document.body.style.overflowY = 'hidden';
            document.body.style.overflowX = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.overflowY = 'hidden';
            document.documentElement.style.overflowX = 'hidden';
            
            drawer.classList.remove('open');
            drawer.classList.remove('peek');
            // Update banner state
            if (banner) {
                banner.classList.remove('drawer-open', 'drawer-peek');
            }
            setTimeout(() => {
                if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                    drawer.style.display = 'flex';
                }
                // Restore body overflow after animation completes
                document.body.style.overflow = '';
                document.body.style.overflowY = '';
                document.body.style.overflowX = '';
                document.documentElement.style.overflow = '';
                document.documentElement.style.overflowY = '';
                document.documentElement.style.overflowX = '';
            }, 350); // Slightly longer than transition to ensure it's complete
            this.isPeeking = false;
        },
        
        peekDrawer: function() {
            if (this.isPeeking || drawer.classList.contains('open')) return;
            
            drawer.style.display = 'flex';
            // Force reflow
            void drawer.offsetWidth;
            drawer.classList.add('peek');
            // Update banner state
            if (banner) {
                banner.classList.remove('drawer-open');
                banner.classList.add('drawer-peek');
            }
            this.isPeeking = true;
            
            // Close after showing peek
            setTimeout(() => {
                if (drawer.classList.contains('peek') && !drawer.classList.contains('open')) {
                    drawer.classList.remove('peek');
                    // Update banner state
                    if (banner) {
                        banner.classList.remove('drawer-peek');
                    }
                    setTimeout(() => {
                        if (!drawer.classList.contains('open') && !drawer.classList.contains('peek')) {
                            drawer.style.display = 'flex';
                        }
                    }, 300);
                    this.isPeeking = false;
                }
            }, 1500); // Show peek for 1.5 seconds
        }
    };
    
    // Initialize player when drawer opens
    let playerInitialized = false;
    const initPlayer = function() {
        if (!playerInitialized) {
            // Prepare config with RSS feed and social icons
            const rssFeedUrl = window.podcastConfig?.rssFeedUrl;
            const savedCoverImage = window.podcastConfig?.savedCoverImage || '';
            const socialIcons = window.podcastConfig?.socialIcons || [];
            
            if (!rssFeedUrl) {
                console.error('RSS feed URL is not set');
                return;
            }
            
            const config = {
                rssFeedUrl: rssFeedUrl,
                rssProxyUrl: '/api/rss-proxy.php',
                imageProxyUrl: '/api/podcast-image-proxy.php',
                savedCoverImage: savedCoverImage,
                platformLinks: {
                    apple: null,
                    spotify: null,
                    google: null
                },
                reviewLinks: {
                    apple: null,
                    spotify: null,
                    google: null
                },
                socialIcons: socialIcons,
                cacheTTL: 3600000
            };
            
            // Initialize player
            try {
                console.log('Initializing podcast player with RSS feed:', rssFeedUrl);
                window.podcastPlayerApp = new PodcastPlayerApp(config, drawer);
                playerInitialized = true;
                console.log('Podcast player initialized successfully');
            } catch (error) {
                console.error('Failed to initialize podcast player:', error);
            }
        }
    };
    
    // Open drawer and initialize player when toggle is clicked
    toggleBtn.addEventListener('click', function() {
        PodcastDrawerController.openDrawer();
        // Wait for scripts to load before initializing
        if (typeof PodcastPlayerApp === 'undefined') {
            console.error('PodcastPlayerApp class not loaded. Check script loading order.');
            return;
        }
        // Small delay to ensure drawer is visible before initializing
        setTimeout(initPlayer, 100);
    });
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            PodcastDrawerController.closeDrawer();
        });
    }
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && (drawer.classList.contains('open') || drawer.classList.contains('peek'))) {
            PodcastDrawerController.closeDrawer();
        }
    });
    
    // Peek animation: Open drawer 30% after 4 seconds, then close
    const shouldAutoPeek = window.matchMedia('(max-width: 600px)').matches;
    const alreadyPeeked = (typeof sessionStorage !== 'undefined') && sessionStorage.getItem('podcastDrawerAutoPeeked') === 'true';
    if (shouldAutoPeek && !alreadyPeeked) {
        setTimeout(function() {
            PodcastDrawerController.peekDrawer();
            try {
                sessionStorage.setItem('podcastDrawerAutoPeeked', 'true');
            } catch (err) {
                console.warn('Unable to persist auto-peek flag:', err);
            }
        }, 4000);
    }
    
    // Expose controller to window for debugging (optional)
    window.PodcastDrawerController = PodcastDrawerController;
})();

