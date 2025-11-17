// Podcast Player Demo Configuration
const CONFIG = {
    // RSS feed URL - replace with your podcast RSS feed
    rssFeedUrl: 'https://feeds.simplecast.com/54nAGcIl',
    
    // Podcast metadata (optional - will be fetched from RSS if not provided)
    podcastName: null, // Auto-populated from RSS
    podcastDescription: null, // Auto-populated from RSS
    podcastCoverImage: null, // Auto-populated from RSS
    
    // Platform links for Follow section (optional)
    platformLinks: {
        apple: null, // Apple Podcasts URL
        spotify: null, // Spotify URL
        google: null, // Google Podcasts URL
        // Add more as needed
    },
    
    // Review links (optional)
    reviewLinks: {
        apple: null, // Apple Podcasts review URL
        spotify: null, // Spotify rating URL
        google: null, // Google Podcasts review URL
    },
    
    // RSS feed proxy endpoint (for CORS handling)
    rssProxyUrl: 'api/rss-proxy.php',
    
    // Image proxy endpoint (for CORS handling)
    imageProxyUrl: 'api/image-proxy.php',
    
    // Cache settings
    cacheTTL: 3600000, // 1 hour in milliseconds
    
    // Player settings
    defaultPlaybackSpeed: 1.0,
    skipBackwardSeconds: 15,
    skipForwardSeconds: 30,
};

