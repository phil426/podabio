# Standalone Mobile Podcast Player Demo

A highly polished, mobile-only podcast player demo showcasing premium design and functionality.

## Features

- **Profile Layout**: Vertical scrolling profile-style layout optimized for mobile devices
- **RSS Integration**: Fetches and parses podcast RSS feeds
- **Chapters**: Displays podcast chapters with interactive navigation
- **Show Notes**: Rich text rendering with clickable timestamps
- **Sharing**: Native Web Share API with fallback to custom drawer
- **Following**: Platform links, RSS subscription, and email signup
- **Review CTAs**: Prominent call-to-action buttons for podcast reviews
- **Playback Controls**: Full control with speed adjustment and sleep timer
- **Dark Mode**: Automatically adapts to system preference
- **Responsive Animations**: Smooth, polished transitions throughout

## Setup

1. Update the RSS feed URL in `config.js`:
   ```javascript
   rssFeedUrl: 'https://your-podcast-rss-feed-url.com'
   ```

2. Optionally configure platform links and review URLs in `config.js`

3. Serve the files via a web server (for CORS handling):
   ```bash
   # Using PHP built-in server
   php -S localhost:8000
   
   # Or using Python
   python -m http.server 8000
   ```

4. Open `index.html` in a mobile browser or use browser dev tools with mobile emulation

## File Structure

```
demo/podcast-player/
├── index.html              # Main HTML structure
├── config.js               # Configuration file
├── css/
│   ├── style.css           # Main styles (mobile-only)
│   └── player.css          # Player-specific styles
├── js/
│   ├── app.js              # Main application logic
│   ├── player.js           # Audio player controller
│   ├── rss-parser.js       # RSS feed parser
│   └── utils.js            # Utility functions
├── api/
│   └── rss-proxy.php       # PHP proxy for RSS (CORS handling)
└── VISUAL_DESIGN.md        # Detailed design documentation
```

## Browser Support

- Modern mobile browsers (iOS Safari, Chrome Android)
- Requires JavaScript enabled
- Uses Web Audio API and localStorage

## Customization

- **Primary Color**: Automatically extracted from podcast artwork, or set in CSS custom properties
- **RSS Feed**: Update `CONFIG.rssFeedUrl` in `config.js`
- **Platform Links**: Add URLs in `CONFIG.platformLinks`
- **Review Links**: Add URLs in `CONFIG.reviewLinks`

## Notes

- This is a standalone demo and not integrated with the main Podn.Bio codebase
- Designed exclusively for mobile devices (320px-414px width)
- No tablet or desktop considerations
- Uses localStorage for caching and preferences
- RSS feed is cached for 1 hour by default

