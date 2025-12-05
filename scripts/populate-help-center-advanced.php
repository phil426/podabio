<?php
/**
 * Populate Help Center with Advanced Documentation
 * Additional articles for comprehensive coverage
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SupportArticle.php';
require_once __DIR__ . '/../classes/SupportCategory.php';

echo "Adding advanced Help Center articles...\n\n";

// Get category IDs
$categories = SupportCategory::getAll();
$categoryIds = [];
foreach ($categories as $cat) {
    $categoryIds[$cat['slug']] = $cat['id'];
}

$articles = [
    // More Getting Started
    [
        'title' => 'Quick Start Guide: Launch in 5 Minutes',
        'slug' => 'quick-start-guide',
        'category' => 'getting-started',
        'tags' => 'quick start, tutorial, beginner, fast',
        'content' => <<<'CONTENT'
# Quick Start Guide: Launch Your Page in 5 Minutes

Get your PodaBio page live in just 5 minutes with this speed-run guide.

## Minute 1: Create Your Account

1. Go to **poda.bio/signup**
2. Enter email and password
3. Choose your username (this becomes poda.bio/yourname)
4. Click **Create Account**

## Minute 2: Add Your Podcast

1. In the Studio, go to **Podcast / RSS** tab
2. Paste your podcast RSS feed URL
3. Click **Import**
4. Watch your podcast details populate automatically

## Minute 3: Choose a Theme

1. Go to **Themes** tab
2. Browse the gallery
3. Click any theme you like
4. It applies instantly

## Minute 4: Add Your Links

1. Go to **Layers** tab
2. Click **Add Block** → **Link**
3. Add your Apple Podcasts link
4. Add your Spotify link
5. Add any other important links

## Minute 5: Publish & Share

1. Toggle **Publish** to make your page live
2. Visit poda.bio/yourname
3. Copy your URL
4. Add it to your social media bios

## You're Live! 🎉

That's it! Your podcast link-in-bio page is now live.

### What to Do Next

- Add social media icons
- Customize colors (Pro)
- Set up email collection
- Check your analytics in a few days

### Pro Tips

- Put your most important link first
- Use your podcast artwork as your profile image
- Update your podcast bio to mention your PodaBio URL
- Add your URL to show notes for every episode
CONTENT
    ],

    // Analytics
    [
        'title' => 'Understanding Your Analytics',
        'slug' => 'understanding-analytics',
        'category' => 'page-setup',
        'tags' => 'analytics, statistics, views, clicks, data',
        'content' => <<<'CONTENT'
# Understanding Your Analytics

Track your page performance with PodaBio's built-in analytics.

## Accessing Analytics

1. Go to the **Analytics** tab in Studio
2. Select your time range (7 days, 30 days, all time)
3. View your metrics

## Key Metrics

### Page Views
The total number of times your page was viewed.

**What it tells you:**
- How much traffic you're getting
- Impact of your promotion efforts
- Growth trends over time

### Unique Visitors
Individual people who visited your page (based on session/IP).

**Why it matters:**
- More accurate than raw page views
- Shows actual audience reach
- Helps calculate engagement rates

### Link Clicks
How many times each link was clicked.

**Use this to:**
- Identify your most popular links
- Optimize link order
- Remove underperforming links

### Click-Through Rate (CTR)
Percentage of visitors who click a link.

**Good CTR benchmarks:**
- 10-20% = Good
- 20-40% = Great
- 40%+ = Excellent

## Understanding Your Data

### Traffic Sources
See where your visitors come from:
- **Direct** - Typed your URL or bookmarked
- **Social** - Clicked from social media
- **Referral** - Came from another website
- **Search** - Found via search engine

### Device Breakdown
- **Mobile** - Visitors on phones
- **Desktop** - Visitors on computers
- **Tablet** - Visitors on tablets

### Geographic Data
See which countries/regions your audience is from.

## Using Analytics to Improve

### Low page views?
- Promote your link more actively
- Add it to all your social bios
- Mention it in podcast episodes
- Include in email signatures

### Low click rate?
- Improve link titles (be specific)
- Reduce number of links (less overwhelming)
- Put best links at top
- Add icons for recognition

### High bounce rate?
- Improve page design/theme
- Add more relevant content
- Make call-to-action clearer

## Analytics vs. Privacy

PodaBio analytics are privacy-friendly:
- No personal data collected
- No cookies required
- GDPR compliant
- Aggregate data only
CONTENT
    ],

    // Media Library
    [
        'title' => 'Using the Media Library',
        'slug' => 'media-library',
        'category' => 'page-setup',
        'tags' => 'media, images, upload, library, photos',
        'content' => <<<'CONTENT'
# Using the Media Library

The Media Library stores all your uploaded images in one place.

## Accessing the Media Library

1. Go to **Account** tab
2. Click **Media Library**
3. View all your uploaded images

## Uploading Images

### From Media Library
1. Open Media Library
2. Click **Upload Image**
3. Select file from your computer
4. Image uploads and appears in library

### From Page Elements
When editing profile image or link thumbnails:
1. Click the image placeholder
2. Choose **Upload New** or **Select from Library**
3. Pick your image

## Image Guidelines

### Supported Formats
- JPG/JPEG - Best for photos
- PNG - Best for graphics/logos
- WebP - Modern format, smaller files

### Size Recommendations
- **Profile images**: 400x400px minimum
- **Link thumbnails**: 200x200px minimum
- **Max file size**: 5MB

### Optimization Tips
- Compress images before upload
- Use JPG for photos (smaller file size)
- Use PNG for graphics with transparency
- Square images work best for most uses

## Organizing Your Library

### Viewing Options
- Grid view for visual browsing
- List view for details

### Image Details
Each image shows:
- Thumbnail preview
- File name
- Upload date
- Dimensions
- File size

## Using Library Images

Once uploaded, use images across your page:
- Profile picture
- Link thumbnails
- Widget backgrounds
- Episode artwork overrides

## Deleting Images

1. Open Media Library
2. Find the image
3. Click the delete/trash icon
4. Confirm deletion

**Note:** Deleting an image from the library may break any page elements using that image.

## Storage Limits

- **Free plan**: Up to 50MB total storage
- **Pro plan**: Up to 500MB total storage

## Best Practices

1. **Name files descriptively** before upload
2. **Compress images** to save storage space
3. **Delete unused images** to free up space
4. **Keep backup copies** of important images
CONTENT
    ],

    // More Widgets
    [
        'title' => 'Adding Video Content',
        'slug' => 'adding-video-content',
        'category' => 'links-widgets',
        'tags' => 'video, youtube, embed, media',
        'content' => <<<'CONTENT'
# Adding Video Content

Embed videos on your PodaBio page to engage visitors.

## Adding YouTube Videos

1. Go to **Layers** tab
2. Click **Add Block**
3. Select **Video Embed**
4. Paste your YouTube video URL
5. Configure display options
6. Save

### Supported URL Formats
- `https://youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://youtube.com/embed/VIDEO_ID`

## Video Display Options

### Embed Size
- **Compact** - Smaller, fits alongside other content
- **Standard** - Medium size, good balance
- **Large** - Full-width, immersive

### Autoplay
By default, videos don't autoplay (better user experience). You can enable autoplay for muted videos if desired.

### Show Controls
Display or hide video player controls.

## When to Use Video

**Good uses:**
- Podcast trailer/intro video
- Episode highlight clips
- Behind-the-scenes content
- Course/product previews

**Consider:**
- Videos increase page load time
- Mobile data usage for visitors
- May distract from main content

## Alternative: Video Links

Instead of embedding, you can add video links:
1. Add a regular **Link** block
2. Set the URL to your YouTube video
3. Use YouTube icon
4. Title like "Watch on YouTube"

Benefits:
- Faster page loading
- Less cluttered design
- YouTube handles playback

## Video Best Practices

1. **Use thumbnails** that grab attention
2. **Keep videos short** - trailers work best
3. **Add context** with a description
4. **Don't embed too many** - slows page
5. **Test on mobile** - ensure it displays well

## Other Video Platforms

While YouTube is most common, you can link to:
- Vimeo
- TikTok
- Instagram Reels
- Facebook videos

For platforms other than YouTube, use link blocks to direct visitors to the native player.
CONTENT
    ],

    // Blocks and Layout
    [
        'title' => 'Working with Blocks',
        'slug' => 'working-with-blocks',
        'category' => 'links-widgets',
        'tags' => 'blocks, widgets, layout, organize',
        'content' => <<<'CONTENT'
# Working with Blocks

Your PodaBio page is made up of blocks. Learn how to add, arrange, and manage them.

## What Are Blocks?

Blocks are the building components of your page:
- Profile block (name, image, bio)
- Link blocks
- Social icons block
- Podcast player block
- Email subscription block
- Text blocks
- Dividers

## Adding Blocks

1. Go to **Layers** tab
2. Click **Add Block** (+ button)
3. Choose block type from the gallery
4. Configure the block
5. Save

## Block Gallery

### Essential Blocks
- **Link** - Clickable button with URL
- **Social Icons** - Row of social platform icons
- **Text** - Add custom text/description
- **Divider** - Visual separator

### Podcast Blocks
- **Podcast Player** - Play episodes on page
- **Episode List** - Show recent episodes
- **Subscribe Links** - Podcast directory buttons

### Growth Blocks
- **Email Subscribe** - Collect email addresses
- **Call to Action** - Prominent action button

## Arranging Blocks

### Drag and Drop
1. Hover over a block in Layers
2. Click and hold the drag handle
3. Drag to new position
4. Release to drop

### Block Order Tips
- Put most important content first
- Podcast player near the top
- Subscribe options before visitors scroll away
- Social icons at bottom

## Editing Blocks

1. Click on the block in Layers
2. Modify settings in the panel
3. Changes preview instantly
4. Save when done

## Hiding Blocks

Don't want to delete but need to hide temporarily?
1. Find the block in Layers
2. Click the visibility icon (eye)
3. Block is hidden but preserved

Use this for:
- Seasonal promotions
- Limited-time offers
- A/B testing content

## Deleting Blocks

1. Click on the block
2. Click the delete/trash icon
3. Confirm deletion

**Warning:** Deletion is permanent. Hide blocks if you might need them later.

## Block Limits

- **Free plan**: Up to 10 blocks
- **Pro plan**: Unlimited blocks

## Best Practices

1. **Less is more** - Don't overwhelm visitors
2. **Prioritize** - Important content first
3. **Group logically** - Related items together
4. **Use dividers** - Separate sections clearly
5. **Mobile-first** - Most visitors are on phones
CONTENT
    ],

    // Pro Features
    [
        'title' => 'Pro Plan Features',
        'slug' => 'pro-plan-features',
        'category' => 'account-security',
        'tags' => 'pro, premium, subscription, features, upgrade',
        'content' => <<<'CONTENT'
# Pro Plan Features

Unlock the full power of PodaBio with a Pro subscription.

## Pro Features Overview

### Unlimited Content
- **Unlimited links** (Free: 10 max)
- **Unlimited blocks** (Free: 10 max)
- **More storage** for media (500MB vs 50MB)

### Full Theme Access
- **All 49+ themes** (Free: 5 basic themes)
- **Custom color overrides** - Match your brand exactly
- **Custom font selection** - Choose from 50+ fonts

### Advanced Customization
- **Custom CSS** (coming soon)
- **Remove PodaBio branding**
- **Custom domain support**

### Integrations
- **Email marketing** integrations (Mailchimp, ConvertKit, etc.)
- **Advanced analytics** with more data points

### Support
- **Priority support** - Faster response times
- **Feature requests** considered first

## Pricing

### Monthly
$4.99/month - Cancel anytime

### Annual
$53.89/year - Save 10%!

## Free Trial

Not sure? Try Pro free for 14 days:
- Full access to all features
- No charge during trial
- Cancel before trial ends = no charge
- Add payment method to start trial

## How to Upgrade

1. Go to **Account** tab
2. Click **Billing**
3. Select **Upgrade to Pro**
4. Choose monthly or annual
5. Enter payment details
6. Enjoy Pro features!

## Managing Your Subscription

### Billing
- View current plan
- See next billing date
- Download invoices

### Changing Plans
- Switch between monthly/annual
- Changes take effect at next billing cycle

### Canceling
- Cancel anytime from Billing
- Keep Pro until period ends
- Downgrade to Free after

## What Happens if I Cancel?

If you cancel Pro:
- Keep Pro until the end of your paid period
- Then revert to Free plan limits
- Extra content beyond limits is hidden (not deleted)
- Custom domain disconnected
- Can re-upgrade anytime to restore

## Is Pro Worth It?

Pro is great if you:
- Need more than 10 links
- Want full theme customization
- Collect email subscribers
- Want your own domain
- Prefer ad-free branding

Free is fine if you:
- Have a simple page
- Don't need custom colors/fonts
- Have fewer than 10 links
- Don't need email collection
CONTENT
    ],

    // Backup and Export
    [
        'title' => 'Backing Up Your Page',
        'slug' => 'backing-up-your-page',
        'category' => 'account-security',
        'tags' => 'backup, export, data, save',
        'content' => <<<'CONTENT'
# Backing Up Your Page

Keep your data safe with these backup practices.

## What Data Can You Backup?

- Page settings and configuration
- Link URLs and titles
- Profile information
- Theme selections

## Automatic Backups

PodaBio automatically saves:
- Every change you make
- Version history (limited)
- Previous settings

This protects against accidental changes, but not account deletion.

## Manual Backup Methods

### Screenshot Your Settings
1. Take screenshots of your Layers tab
2. Screenshot each block's configuration
3. Save to your computer

### Document Your Links
Keep a spreadsheet with:
- Link titles
- Full URLs
- Order/priority

### Export Feature (Coming Soon)
We're working on a full export feature that will let you download:
- All page data as JSON
- Image assets
- Complete configuration

## RSS Feed Backup

Your podcast data comes from your RSS feed, which is:
- Stored with your podcast host
- Automatically synced
- Not lost if you delete PodaBio

## What's Not Backed Up

### Analytics History
Historical analytics data may be limited. Export periodically if you need records.

### Uploaded Images
Keep copies of images you upload. If you delete from Media Library, they're gone.

## Recovery Options

### Undo Recent Changes
Browser back button can undo very recent changes (before save).

### Contact Support
For accidental deletion or major issues:
- Email support@poda.bio
- We may be able to help recover recent data
- Not guaranteed - backups are limited

## Best Practices

1. **Keep image originals** on your computer
2. **Document your links** in a spreadsheet
3. **Screenshot configurations** periodically
4. **Note your theme** name and customizations
5. **Save color codes** you've customized

## Future Improvements

We're building:
- Full data export
- Import from backup
- Page version history
- One-click restore points

Stay tuned for updates!
CONTENT
    ],

    // Mobile App
    [
        'title' => 'Mobile Access',
        'slug' => 'mobile-access',
        'category' => 'faq-troubleshooting',
        'tags' => 'mobile, app, phone, iphone, android',
        'content' => <<<'CONTENT'
# Mobile Access

Manage your PodaBio page from your phone.

## Is There a Mobile App?

Currently, PodaBio doesn't have a dedicated mobile app. However, the web interface works great on mobile browsers!

## Using PodaBio on Mobile

### Access the Studio
1. Open your mobile browser (Safari, Chrome)
2. Go to **poda.bio/login**
3. Log into your account
4. Full Studio access on mobile!

### Mobile-Optimized Interface
The PodaBio Studio is responsive:
- Touch-friendly controls
- Swipe navigation
- Mobile-appropriate layouts
- All features available

## Add to Home Screen

For app-like access:

### iPhone (Safari)
1. Go to poda.bio
2. Tap Share button
3. Tap "Add to Home Screen"
4. Name it "PodaBio"
5. Tap Add

### Android (Chrome)
1. Go to poda.bio
2. Tap menu (three dots)
3. Tap "Add to Home screen"
4. Name it "PodaBio"
5. Tap Add

Now you have a home screen icon that opens PodaBio instantly!

## What You Can Do on Mobile

✅ Edit profile and bio
✅ Add and edit links
✅ Change themes
✅ View analytics
✅ Manage account settings
✅ Upload images (from camera roll)

## Tips for Mobile Editing

1. **Use landscape mode** for larger preview
2. **Pinch to zoom** on small elements
3. **Upload from phone** - great for on-the-go photos
4. **Preview often** - check your page looks right

## Mobile-Specific Features

### Camera Upload
Take photos and upload directly:
1. Tap image upload
2. Choose "Take Photo"
3. Snap picture
4. Upload instantly

### Share Sheet Integration
Copy your PodaBio URL easily to paste in other apps.

## Future App Plans

We're considering a native mobile app. Features would include:
- Push notifications for analytics
- Quick link adding
- Faster performance
- Offline viewing

Sign up for our newsletter for updates on mobile app development!

## Mobile Browser Support

Works best on:
- Safari (iOS 14+)
- Chrome (Android 8+)
- Firefox Mobile
- Samsung Internet

Older browsers may have limited functionality.
CONTENT
    ],

    // Deleting Account
    [
        'title' => 'Deleting Your Account',
        'slug' => 'deleting-your-account',
        'category' => 'account-security',
        'tags' => 'delete, remove, account, cancel, close',
        'content' => <<<'CONTENT'
# Deleting Your Account

How to permanently delete your PodaBio account.

## Before You Delete

**Deletion is permanent and cannot be undone.** Consider these alternatives:

### Alternatives to Deletion
- **Unpublish your page** - Makes it private without losing data
- **Cancel Pro** - Downgrade to free if cost is the issue
- **Hide content** - Remove visible content but keep account
- **Take a break** - Your account stays safe if unused

## What Gets Deleted

When you delete your account:
- ❌ Your page (poda.bio/username)
- ❌ All links and content
- ❌ Profile and settings
- ❌ Media library images
- ❌ Analytics history
- ❌ Account credentials

## What's NOT Deleted

- Your podcast (stays with your host)
- External links (still work)
- Email subscribers (in your email service)
- Anything outside PodaBio

## How to Delete Your Account

1. Go to **Account** tab
2. Click **Settings** or **Security**
3. Scroll to **Danger Zone** or **Delete Account**
4. Click **Delete Account**
5. Enter your password to confirm
6. Type "DELETE" to confirm
7. Click final confirmation

## Canceling Active Subscription

If you have a Pro subscription:
1. Cancel the subscription first in Billing
2. Wait for subscription to end, OR
3. Delete account (subscription auto-cancels)

**Note:** No refunds for remaining subscription time after account deletion.

## Freeing Your Username

When you delete your account:
- Your username is NOT immediately available
- Usernames may be held temporarily
- Not guaranteed to become available again

## Cool-off Period

After requesting deletion:
- 14-day grace period before permanent deletion
- Cancel deletion by logging in during this period
- After 14 days, deletion is final

## Data Export First

Before deleting, consider:
1. Screenshot your page
2. Save your link list
3. Export any analytics you need
4. Download your media files

## Re-joining After Deletion

If you want to come back:
- Create a new account
- Previous data is NOT recoverable
- May need a new username
- Start fresh

## Need Help?

Having trouble with your account? Contact support@poda.bio before deleting - we might be able to help!

Common issues we can help with:
- Account access problems
- Billing questions
- Technical issues
- Feature requests
CONTENT
    ],

    // API (for developers)
    [
        'title' => 'Developer Information',
        'slug' => 'developer-information',
        'category' => 'integrations',
        'tags' => 'api, developer, integration, technical',
        'content' => <<<'CONTENT'
# Developer Information

Technical information for developers and power users.

## Public API

Currently, PodaBio does not offer a public API for external integrations. We're evaluating this for future development.

### Interested in API Access?

If you have a specific use case:
1. Email **api@poda.bio**
2. Describe your integration needs
3. We'll evaluate and respond

## RSS Feed Integration

### Your Podcast RSS
PodaBio reads standard RSS 2.0 feeds with iTunes podcast extensions. Requirements:
- Public URL (no authentication)
- Valid XML format
- iTunes namespace for podcast data
- HTTPS recommended

### Feed Elements We Parse
```xml
<channel>
  <title>Podcast Name</title>
  <description>Podcast description</description>
  <itunes:image href="artwork.jpg" />
  <item>
    <title>Episode Title</title>
    <description>Episode description</description>
    <enclosure url="audio.mp3" type="audio/mpeg" />
    <pubDate>Publication date</pubDate>
    <itunes:duration>Duration</itunes:duration>
  </item>
</channel>
```

## Embedding Your Page

### Simple Link
```html
<a href="https://poda.bio/yourname">Visit my page</a>
```

### Button Badge
```html
<a href="https://poda.bio/yourname" 
   style="background:#8B5CF6;color:white;padding:10px 20px;
          border-radius:8px;text-decoration:none;">
  My PodaBio
</a>
```

### QR Code
Generate a QR code for your poda.bio URL using any QR generator.

## Custom Domain Technical Details

### DNS Configuration
```
Type: A
Name: @ (or subdomain)
Value: 156.67.73.201
TTL: 3600
```

### SSL
- Automatic SSL provisioning
- Let's Encrypt certificates
- Auto-renewal handled

## Analytics Tracking

### What We Track
- Page views (anonymized)
- Link clicks
- Referrer information
- Device/browser type
- Geographic region

### Privacy Compliance
- No cookies required
- No personal data stored
- GDPR compliant
- IP addresses not logged

## Email Integration

### Supported Platforms
- Mailchimp (API v3)
- ConvertKit (API v3)
- MailerLite (API v2)
- Brevo (API v3)

### Data Sent
- Email address
- Optional: Name (if collected)
- Subscription timestamp
- Source: "podabio"

## Webhooks (Coming Soon)

Future webhook support for:
- New subscriber notifications
- Link click events
- Page view milestones

## Technical Support

For technical integration questions:
- Email: **tech@poda.bio**
- Include your use case
- Provide technical details

## Open Source

PodaBio is not currently open source, but we use and appreciate open source projects:
- React
- PHP
- MySQL
- And many others

We contribute back where possible.
CONTENT
    ],

    // Accessibility
    [
        'title' => 'Accessibility Features',
        'slug' => 'accessibility-features',
        'category' => 'faq-troubleshooting',
        'tags' => 'accessibility, a11y, screen reader, keyboard',
        'content' => <<<'CONTENT'
# Accessibility Features

PodaBio is committed to making our platform accessible to everyone.

## Our Commitment

We strive to meet WCAG 2.1 AA guidelines to ensure PodaBio works for users with:
- Visual impairments
- Motor disabilities
- Hearing impairments
- Cognitive differences

## Accessibility Features

### Screen Reader Support
- Semantic HTML structure
- ARIA labels on interactive elements
- Descriptive link text
- Alt text for images

### Keyboard Navigation
- All features accessible via keyboard
- Logical tab order
- Visible focus indicators
- Escape key closes modals

### Visual Accessibility
- Sufficient color contrast (4.5:1 ratio)
- Text resizing support
- No color-only information
- Reduced motion options

### Audio Content
- Podcast players have visible controls
- Progress indicators
- Volume control

## Making Your Page Accessible

### Use Descriptive Link Text
❌ "Click here"
✅ "Listen on Apple Podcasts"

### Add Alt Text
When uploading images, describe them for screen readers.

### Choose Accessible Themes
Some themes have higher contrast than others. Test with accessibility tools.

### Keep It Simple
Clear, organized layouts are easier to navigate.

## Testing Your Page

### Manual Testing
1. Try navigating with only keyboard (Tab, Enter, Space)
2. Test with a screen reader
3. Check text size at 200% zoom
4. Verify color contrast

### Tools
- WAVE accessibility evaluator
- axe DevTools
- Chrome Accessibility Inspector
- Screen reader: NVDA (Windows), VoiceOver (Mac/iOS)

## Reporting Issues

Found an accessibility barrier? Please tell us!

Email: **accessibility@poda.bio**

Include:
- What you were trying to do
- What happened
- Your browser and assistive technology
- Screenshots if helpful

## Continuous Improvement

We regularly:
- Audit our interfaces
- Test with assistive technologies
- Gather user feedback
- Update to meet new standards

## Known Limitations

We're working on improvements for:
- Mobile screen reader optimization
- Live region announcements
- Complex widget accessibility
- Theme accessibility audits

## Resources

### Learn About Web Accessibility
- W3C WCAG Guidelines
- WebAIM resources
- A11y Project checklist

### Assistive Technology
- NVDA screen reader (free)
- VoiceOver (built into Apple devices)
- JAWS screen reader
CONTENT
    ],

    // Performance
    [
        'title' => 'Page Performance Tips',
        'slug' => 'page-performance-tips',
        'category' => 'faq-troubleshooting',
        'tags' => 'performance, speed, loading, optimization',
        'content' => <<<'CONTENT'
# Page Performance Tips

Make your PodaBio page load fast for all visitors.

## Why Speed Matters

- Better user experience
- Lower bounce rates
- Better SEO rankings
- Faster mobile loading
- Improved conversions

## Optimize Your Images

### File Size
- Keep images under 500KB
- Profile images: ~100-200KB ideal
- Use compression tools

### Format
- JPG for photos
- PNG for graphics with transparency
- WebP for best compression (if supported)

### Resolution
- Don't upload 4000x4000px images
- 800x800px is plenty for most uses
- PodaBio auto-resizes, but smaller originals load faster

### Tools to Compress Images
- TinyPNG (web)
- ImageOptim (Mac)
- Squoosh (web)

## Limit Embedded Content

### Videos
- Embed only essential videos
- Use link blocks instead when possible
- YouTube embeds add significant load time

### Audio Players
- The podcast player is optimized
- Don't embed multiple players
- Audio loads on-demand (not at page load)

## Link Optimization

### Fewer Links = Faster Page
- Quality over quantity
- Focus on 5-10 important links
- Remove outdated links

### Simple Link Text
- Short titles load faster
- Icons render quickly

## Theme Choices

### Simpler Themes Load Faster
- Solid colors > complex gradients
- Fewer animations = faster
- System fonts load instantly

### Custom Fonts
- Each font adds load time
- Stick to 1-2 font families

## Technical Performance

### What PodaBio Does
- CDN delivery for global speed
- Image optimization
- Minified CSS/JS
- Browser caching
- Lazy loading for images

### What You Control
- Image file sizes
- Number of embeds
- Amount of content
- External resources

## Testing Your Page Speed

### Simple Test
Open your page on mobile 4G. Does it feel fast?

### Tools
- Google PageSpeed Insights
- GTmetrix
- WebPageTest

### Target Metrics
- First paint: < 1.5 seconds
- Fully loaded: < 3 seconds
- Time to interactive: < 2.5 seconds

## Mobile Performance

Mobile is critical - most visitors are on phones!

### Mobile-Specific Tips
- Test on actual phones, not just desktop
- Check on slower connections (3G)
- Reduce total page content

## Performance Checklist

- [ ] Images compressed under 500KB each
- [ ] No unnecessary video embeds
- [ ] Under 20 links total
- [ ] Simple theme selected
- [ ] Page tested on mobile

## When Performance Is Slow

If your page is slow despite optimization:
1. Clear browser cache and test again
2. Try incognito mode
3. Test on different network
4. Contact support if persistent
CONTENT
    ],
];

// Insert articles
$insertedCount = 0;
$skippedCount = 0;

foreach ($articles as $article) {
    // Check if article exists
    $existing = SupportArticle::getBySlug($article['slug']);
    if ($existing) {
        echo "Article '{$article['title']}' already exists, skipping...\n";
        $skippedCount++;
        continue;
    }
    
    // Get category ID
    $categoryId = $categoryIds[$article['category']] ?? null;
    
    $data = [
        'title' => $article['title'],
        'slug' => $article['slug'],
        'content' => $article['content'],
        'category_id' => $categoryId,
        'tags' => $article['tags'],
        'published' => 1
    ];
    
    $id = SupportArticle::create($data);
    
    if ($id) {
        echo "Created article: {$article['title']}\n";
        $insertedCount++;
    } else {
        echo "Failed to create article: {$article['title']}\n";
    }
}

echo "\n========================================\n";
echo "Advanced Articles Complete!\n";
echo "Articles created: {$insertedCount}\n";
echo "Articles skipped: {$skippedCount}\n";
echo "========================================\n";

