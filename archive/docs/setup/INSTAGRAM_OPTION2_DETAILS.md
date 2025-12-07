# Option 2: Instagram Post Embeds - What You Can Display

## Overview

With **Option 2**, users can embed specific Instagram posts by pasting post URLs. This works similar to how YouTube videos are embedded - just paste the URL and it displays!

---

## What Content Can Be Displayed

### ✅ Individual Instagram Posts
- **Photos**: Single image posts
- **Videos**: Video posts with playback controls
- **Carousel Posts**: Multi-image/video posts (displayed as first item or full carousel)
- **Reels**: Instagram Reels (short videos)
- **IGTV**: Long-form videos (if still supported)

### ✅ What Gets Displayed
- Full post embed (image/video + caption)
- Like count
- Comment count (sometimes)
- User profile info
- Caption text
- Post timestamp
- Direct link to view on Instagram

---

## Display Options

### Option 2A: Official Instagram oEmbed (Recommended)

**How it works:**
1. User pastes Instagram post URL: `https://www.instagram.com/p/ABC123xyz/`
2. Your app fetches embed code from Instagram oEmbed API
3. Displays official Instagram embed (iframe)
4. Fully responsive and styled by Instagram

**Pros:**
- ✅ Official Instagram embed
- ✅ Always up-to-date styling
- ✅ Includes likes, comments, profile info
- ✅ No OAuth needed
- ✅ Works with any public Instagram post
- ✅ Mobile-friendly

**Cons:**
- ✗ Requires server-side fetch (PHP)
- ✗ Slight performance overhead
- ✗ Depends on Instagram's embed service

**API Endpoint:**
```
https://graph.facebook.com/v18.0/instagram_oembed?url=POST_URL&access_token=YOUR_ACCESS_TOKEN
```

**OR** (no access token needed for public posts):
```
https://api.instagram.com/oembed?url=POST_URL
```

**Embed Code Format:**
```html
<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="14">
  <!-- Instagram provides this HTML -->
</blockquote>
<script async src="//www.instagram.com/embed.js"></script>
```

---

### Option 2B: Simple Link Preview

**How it works:**
1. User pastes Instagram post URL
2. Extract post ID from URL
3. Display thumbnail image + link
4. Clicking opens Instagram post in new tab

**Pros:**
- ✅ Super simple
- ✅ No API calls needed
- ✅ Fast loading
- ✅ Just show preview card

**Cons:**
- ✗ Not a true embed
- ✗ Users click through to Instagram
- ✗ Less engaging

**Display:**
- Post thumbnail image
- Caption preview (if we can scrape)
- "View on Instagram" button
- Link to original post

---

### Option 2C: Manual Embed HTML

**How it works:**
1. User gets embed code from Instagram (copy/paste)
2. Paste into your HTML widget
3. Displays exactly as Instagram provides

**Pros:**
- ✅ Zero server-side processing
- ✅ Exact Instagram styling
- ✅ User has full control

**Cons:**
- ✗ Not user-friendly (technical)
- ✗ Requires users to get embed code themselves
- ✗ Harder to manage

---

### Option 2D: Custom Instagram Post Widget (Our Recommendation)

**How it works:**
1. User pastes Instagram post URL in widget config
2. Widget automatically:
   - Fetches embed code from Instagram oEmbed API
   - Displays embedded post
   - Handles responsive layout
3. Multiple posts = multiple widgets (or one widget with multiple URLs)

**Features:**
- Single post embed
- Multiple posts (user adds multiple URLs)
- Grid layout for multiple posts
- Carousel layout option
- Responsive design

**Widget Config Fields:**
- Post URL(s) (single URL or multiple)
- Layout (single, grid, carousel)
- Caption display (show/hide)
- Spacing options

---

## Implementation Approaches

### Approach 1: oEmbed API Fetch (Best UX)

**Step 1:** User enters Instagram post URL in widget
**Step 2:** Backend fetches embed HTML from Instagram oEmbed API
**Step 3:** Store embed HTML or fetch on render
**Step 4:** Display embedded post

**Example Flow:**
```
User Input: https://www.instagram.com/p/ABC123xyz/
    ↓
PHP: Fetch from https://api.instagram.com/oembed?url=...
    ↓
Instagram Returns: {html: "<blockquote>...", width: 612, ...}
    ↓
Display: Render the HTML embed
```

**Pros:**
- Official Instagram embed
- Full post interaction (likes, comments)
- Automatic updates if Instagram changes format

---

### Approach 2: Direct iframe Embed

**Step 1:** User enters post URL
**Step 2:** Convert URL to embed URL
**Step 3:** Display in iframe

**URL Conversion:**
```
https://www.instagram.com/p/ABC123xyz/
    ↓
https://www.instagram.com/p/ABC123xyz/embed/
```

**Pros:**
- Very simple
- No API calls
- Fast

**Cons:**
- Less control over styling
- May have iframe limitations

---

### Approach 3: Third-Party Service

**Services:**
- Instagram Feed widgets (require external service)
- Snapppt, Taggbox, etc.

**Pros:**
- Pre-built solutions
- Multiple posts automatically

**Cons:**
- External dependency
- Costs money (usually)
- Less control

---

## Display Layout Options

### Single Post
- One post, full width
- Best for featured content

### Grid Layout
- Multiple posts in a grid
- User adds multiple URLs
- Responsive columns (2, 3, 4 per row)

### Carousel/Slider
- Multiple posts in horizontal scroll
- Navigation arrows
- Dots indicator
- Auto-play option

### Masonry
- Pinterest-style layout
- Posts at different heights
- Fills space efficiently

---

## Recommended Implementation

### Widget: "Instagram Post Embed"

**Config Fields:**
```
- Post URL (required) - text input
- Show Caption (checkbox) - default: true
- Layout (dropdown) - Single, Grid, Carousel
- Number of Posts (if grid/carousel) - number input
```

**Features:**
1. User pastes Instagram post URL(s)
2. Backend validates URL format
3. Fetches embed from Instagram oEmbed API
4. Caches embed HTML (optional, for performance)
5. Displays embedded post(s)
6. Responsive design

**Example User Flow:**
1. Add widget → "Instagram Post"
2. Paste URL: `https://www.instagram.com/p/ABC123xyz/`
3. Choose layout: "Single" or "Grid"
4. Save
5. Post appears embedded on page

---

## Technical Details

### Instagram Post URL Formats

**Standard Post:**
```
https://www.instagram.com/p/ABC123xyz/
https://instagram.com/p/ABC123xyz/
```

**Short URL:**
```
https://www.instagram.com/p/ABC123xyz/
```

**Reels:**
```
https://www.instagram.com/reel/ABC123xyz/
```

### oEmbed API Response

```json
{
  "version": "1.0",
  "type": "rich",
  "html": "<blockquote class=\"instagram-media\"...>...</blockquote>",
  "width": 612,
  "height": 710,
  "author_name": "username",
  "author_url": "https://www.instagram.com/username/",
  "thumbnail_url": "https://...",
  "thumbnail_width": 640,
  "thumbnail_height": 640
}
```

### Embed HTML Structure

```html
<blockquote class="instagram-media" 
            data-instgrm-captioned 
            data-instgrm-version="14"
            style="background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);">
  <div style="padding:16px;">
    <!-- Instagram-provided content -->
  </div>
</blockquote>
<script async src="//www.instagram.com/embed.js"></script>
```

---

## Comparison with OAuth Approach

| Feature | Option 2 (Embeds) | Option 3 (OAuth) |
|---------|------------------|------------------|
| Setup Complexity | ⭐ Simple | ⭐⭐⭐ Complex |
| User Experience | Paste URL | Connect account |
| Content Display | Specific posts | Automatic feed |
| Auto-Update | ❌ Manual | ✅ Automatic |
| Maintenance | ⭐ Low | ⭐⭐⭐ High |
| OAuth Required | ❌ No | ✅ Yes |

---

## Recommended Implementation Plan

### Phase 1: Single Post Embed (MVP)
- Widget accepts one Instagram post URL
- Fetches embed from oEmbed API
- Displays embedded post
- Simple, works immediately

### Phase 2: Multiple Posts
- Allow multiple URLs
- Grid or carousel layout
- Better for showcasing multiple posts

### Phase 3: Enhanced Features (Optional)
- Caption customization
- Layout options
- Styling controls
- Cache embeds for performance

---

## Code Example

### Widget Config (WidgetRegistry.php)
```php
'instagram_embed' => [
    'widget_id' => 'instagram_embed',
    'name' => 'Instagram Post',
    'description' => 'Embed an Instagram post by pasting its URL',
    'category' => 'social',
    'requires_api' => false,  // No OAuth needed!
    'config_fields' => [
        'post_url' => [
            'type' => 'url',
            'label' => 'Instagram Post URL',
            'required' => true,
            'help' => 'Paste the URL of the Instagram post you want to embed',
            'placeholder' => 'https://www.instagram.com/p/ABC123xyz/'
        ],
        'show_caption' => [
            'type' => 'checkbox',
            'label' => 'Show Caption',
            'required' => false,
            'default' => true
        ]
    ]
]
```

### Render Function (WidgetRenderer.php)
```php
private static function renderInstagramEmbed($widget, $configData) {
    $postUrl = $configData['post_url'] ?? '';
    
    if (empty($postUrl)) {
        return '<div class="widget-item">Please provide an Instagram post URL</div>';
    }
    
    // Validate Instagram URL
    if (!preg_match('/instagram\.com\/(p|reel)\/([A-Za-z0-9_-]+)/', $postUrl, $matches)) {
        return '<div class="widget-item">Invalid Instagram post URL</div>';
    }
    
    // Fetch embed from Instagram oEmbed API
    $embedUrl = 'https://api.instagram.com/oembed?url=' . urlencode($postUrl);
    $embedData = @file_get_contents($embedUrl);
    
    if (!$embedData) {
        return '<div class="widget-item">Unable to load Instagram post</div>';
    }
    
    $embed = json_decode($embedData, true);
    
    if (!isset($embed['html'])) {
        return '<div class="widget-item">Invalid response from Instagram</div>';
    }
    
    // Return embed HTML
    return '<div class="widget-item widget-instagram-embed">' . $embed['html'] . '</div>';
}
```

---

## Next Steps

1. **Decide on approach** (oEmbed vs iframe vs manual)
2. **Create widget** in WidgetRegistry
3. **Implement render function** in WidgetRenderer
4. **Add to admin UI** (inspector for configuring)
5. **Test with real Instagram posts**

Want me to implement this?

