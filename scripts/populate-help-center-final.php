<?php
/**
 * Final batch of Help Center articles
 * Specialized topics and common scenarios
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SupportArticle.php';
require_once __DIR__ . '/../classes/SupportCategory.php';

echo "Adding final Help Center articles...\n\n";

// Get category IDs
$categories = SupportCategory::getAll();
$categoryIds = [];
foreach ($categories as $cat) {
    $categoryIds[$cat['slug']] = $cat['id'];
}

$articles = [
    // SEO and Sharing
    [
        'title' => 'SEO and Social Sharing',
        'slug' => 'seo-social-sharing',
        'category' => 'page-setup',
        'tags' => 'seo, meta, social, sharing, twitter, facebook, og',
        'content' => <<<'CONTENT'
# SEO and Social Sharing

Optimize how your PodaBio page appears in search results and social media.

## Search Engine Optimization (SEO)

### How Your Page Gets Indexed
When you publish your page, search engines like Google can find and index it. This means people searching for your podcast name might find your PodaBio page.

### What PodaBio Does Automatically
- **Page title**: Your display name
- **Meta description**: Your bio
- **Canonical URL**: poda.bio/yourname
- **Mobile-friendly**: All pages pass mobile tests
- **Fast loading**: Optimized for speed (ranking factor)

### How to Improve SEO

**Write a good bio**
- Include your podcast topic/niche
- Mention key terms people search for
- Keep it natural (not keyword-stuffed)

**Use a descriptive display name**
- Include your podcast name
- Be consistent with other platforms

**Keep your page active**
- Fresh content signals relevance
- Regularly update links

## Social Media Sharing

### Open Graph (Facebook, LinkedIn)
When someone shares your link on Facebook or LinkedIn, they see:
- Your profile image (og:image)
- Your display name (og:title)
- Your bio (og:description)

### Twitter Cards
When shared on Twitter:
- Summary card with image
- Your page title
- Your description

### How to Preview Your Share

Before sharing widely, test how it looks:
- **Facebook**: Use Facebook Sharing Debugger
- **Twitter**: Use Twitter Card Validator
- **LinkedIn**: Use LinkedIn Post Inspector

### Updating Cached Images

Social platforms cache your preview. If you update your image and it doesn't change:
1. Use the platform's debugger tool
2. "Scrape Again" or "Re-fetch"
3. New preview will appear

## Best Practices

### Profile Image
- Use high-quality podcast artwork
- Square format (at least 1200x1200 for best results on social)
- Consistent across platforms

### Bio/Description
- First 150 characters most important
- Include call to action
- Be specific about your podcast topic

### Page URL
- Share your clean URL: poda.bio/yourname
- Don't add tracking parameters to your main link

## Custom Domain SEO

If using a custom domain:
- Same SEO benefits
- Professional appearance
- Brand consistency
- May take time for search engines to discover

## Common Questions

**Will my page rank in Google?**
It can! Factors include your content quality, how many people link to you, and competition for your topics.

**How long until Google indexes my page?**
Usually days to weeks. You can submit your URL to Google Search Console for faster indexing.

**Should I add my page to my sitemap?**
Your PodaBio URL can be included in your podcast website's sitemap if you have one.
CONTENT
    ],

    // Copyright and Legal
    [
        'title' => 'Copyright and Legal Information',
        'slug' => 'copyright-legal',
        'category' => 'faq-troubleshooting',
        'tags' => 'copyright, legal, terms, privacy, dmca',
        'content' => <<<'CONTENT'
# Copyright and Legal Information

Important legal information for PodaBio users.

## Your Content Rights

### You Own Your Content
- Your profile information
- Your link titles and descriptions
- Your uploaded images (that you own rights to)
- Your podcast content (hosted elsewhere)

### Content License
By uploading content to PodaBio, you grant us license to:
- Display it on your page
- Use it for the service features
- Cache and distribute for performance

You retain all ownership.

## What You Can Upload

### Allowed
- Images you created or own rights to
- Licensed stock images
- Your podcast artwork (if you own it)
- Your own photos

### Not Allowed
- Copyrighted images without permission
- Trademarked logos (unless you own them)
- Other people's artwork
- Illegal content

## DMCA Policy

PodaBio respects intellectual property rights.

### If You Find Your Work on PodaBio
If someone is using your copyrighted work without permission:
1. Email **dmca@poda.bio**
2. Identify the copyrighted work
3. Identify where on PodaBio it appears
4. Provide your contact information
5. Statement of good faith belief
6. Signature

### Counter-Notice
If you receive a DMCA takedown and believe it's incorrect, you may file a counter-notice.

## User Responsibilities

By using PodaBio, you agree to:
- Not post illegal content
- Respect others' copyrights
- Not misrepresent yourself
- Not spam or abuse the platform
- Follow our Terms of Service

## Privacy

### Your Data
- We collect minimal data needed to operate
- We don't sell your personal information
- See our Privacy Policy for details

### Visitor Data
- Anonymous analytics only
- No personal visitor tracking
- GDPR compliant

## Terms of Service

Full Terms of Service available at:
poda.bio/terms

Key points:
- Account responsibilities
- Acceptable use
- Service limitations
- Dispute resolution

## Privacy Policy

Full Privacy Policy available at:
poda.bio/privacy

Key points:
- Data we collect
- How we use it
- Your rights
- Data retention

## Age Requirements

You must be at least 13 years old to use PodaBio (or older if required by your jurisdiction).

## Podcast Content

Your podcast audio/video content:
- Hosted by your podcast host (not PodaBio)
- Subject to your host's terms
- Embedded via RSS feed (not uploaded)
- Your responsibility to have rights

## Questions?

For legal inquiries: **legal@poda.bio**

This page is informational. See our official Terms and Privacy Policy for binding agreements.
CONTENT
    ],

    // RSS Troubleshooting
    [
        'title' => 'RSS Feed Troubleshooting',
        'slug' => 'rss-feed-troubleshooting',
        'category' => 'faq-troubleshooting',
        'tags' => 'rss, feed, troubleshooting, errors, import',
        'content' => <<<'CONTENT'
# RSS Feed Troubleshooting

Common RSS feed issues and how to fix them.

## Feed Won't Import

### Check the URL
1. Copy your RSS feed URL
2. Paste it directly in your browser
3. You should see XML content

If it doesn't load, the URL is wrong or the feed is private.

### Common URL Mistakes
- ❌ Website URL (yourpodcast.com)
- ❌ Apple Podcasts link
- ✅ RSS feed URL (usually ends in .xml or /feed/)

### Where to Find Your RSS URL

**By Host:**
- **Buzzsprout**: Dashboard → Directories → RSS Feed
- **Anchor/Spotify**: Settings → Distribution
- **Libsyn**: Destinations → Your RSS URL
- **Podbean**: Settings → RSS Feed
- **Transistor**: Dashboard → RSS Feed
- **Captivate**: Distribution → RSS Feed
- **Apple Podcasts**: Use a tool like getrssfeed.com

### Feed Format Issues
Your feed must be:
- Valid RSS 2.0 format
- Include iTunes podcast namespace
- Well-formed XML

Test your feed at: podba.se/validate

## Episodes Not Appearing

### Recently Published?
- Feeds sync every few hours
- New episodes may take time to appear
- Click "Refresh Feed" to force sync

### Marked as Draft?
Check your podcast host:
- Episode must be "Published"
- Not scheduled for future
- Not marked as private

### Limited Episodes?
Some hosts limit RSS feed to recent episodes:
- Default: 10-50 episodes
- Check host settings to increase

### Wrong Episodes?
If wrong podcast is showing:
- Double-check RSS URL
- Remove and re-add correct feed
- Check for redirect issues

## Missing Information

### No Podcast Artwork
Your RSS feed needs:
```xml
<itunes:image href="https://yoursite.com/artwork.jpg" />
```

Or in `<channel>`:
```xml
<image>
  <url>https://yoursite.com/artwork.jpg</url>
</image>
```

### No Episode Descriptions
Each `<item>` needs:
```xml
<description>Episode description here</description>
```

Or:
```xml
<itunes:summary>Episode summary here</itunes:summary>
```

### Audio Not Playing
Check your `<enclosure>` tag:
```xml
<enclosure url="https://..." type="audio/mpeg" length="12345" />
```

Must be:
- HTTPS URL
- Valid audio file
- Accessible (not behind paywall)

## Feed Redirect Issues

If your feed has moved:
- Use the new feed URL
- 301 redirects should work
- Temporary redirects may cause issues

## CORS and Security

### "Failed to fetch" Error
The feed URL must:
- Allow cross-origin requests
- Not block our servers
- Be publicly accessible

If your host blocks access, contact them.

### HTTPS Required
We strongly recommend HTTPS feeds:
- More secure
- Better browser support
- Required by some hosts

## Still Not Working?

1. Test your feed URL directly in a browser
2. Validate at podba.se/validate
3. Check your podcast host settings
4. Contact support@poda.bio with:
   - Your RSS feed URL
   - Error message you see
   - Screenshot if helpful
CONTENT
    ],

    // Account Settings Deep Dive
    [
        'title' => 'Account Settings Overview',
        'slug' => 'account-settings-overview',
        'category' => 'account-security',
        'tags' => 'account, settings, profile, preferences',
        'content' => <<<'CONTENT'
# Account Settings Overview

Complete guide to your PodaBio account settings.

## Accessing Settings

1. Click **Account** tab in Studio
2. Choose the section you want to edit

## Profile Settings

### Email Address
Your login email. To change:
1. Go to Account → Profile
2. Enter new email
3. Verify the new email address
4. Old email deactivated

### Display Name
This appears on your page header. Keep it:
- Recognizable
- Consistent with your brand
- Appropriate length

### Username
Your page URL (poda.bio/username).
- Set during signup
- Cannot be changed (currently)
- Contact support if you need assistance

## Security Settings

### Password
- Change anytime from Security tab
- Use strong, unique password
- Update periodically

### Two-Factor Authentication
- Enable for extra security
- Uses authenticator app or email
- Save backup codes!

### Connected Accounts
- Link/unlink Google
- Manage OAuth connections

### Active Sessions
- See where you're logged in
- Log out other sessions

## Notification Settings

### Email Notifications
Control what emails you receive:
- Product updates
- New features
- Analytics summaries
- Security alerts (always on)

## Billing Settings
(Pro users)

### Subscription
- View current plan
- See renewal date
- Change billing frequency

### Payment Method
- Update credit card
- Change payment method

### Invoices
- View past invoices
- Download for records

### Cancel Subscription
- Cancel anytime
- Keep Pro until period ends
- Downgrade to Free after

## Page Settings

### Page Visibility
- Published: Live to all
- Draft: Only you can see

### Custom Domain
(Pro only)
- Add your domain
- Configure DNS
- Verify connection

## Data & Privacy

### Export Data
(Coming soon)
- Download your information
- Export page configuration

### Delete Account
- Permanently remove everything
- Cannot be undone
- 14-day grace period

## Preferences

### Studio Theme
- Light mode
- Dark mode
- System (match your device)

### Editor Settings
- Auto-save (always on)
- Preview position

## Quick Reference

| Setting | Location | Can Change? |
|---------|----------|-------------|
| Email | Profile | Yes (verify) |
| Password | Security | Yes |
| Username | N/A | No |
| 2FA | Security | Yes |
| Plan | Billing | Yes |
| Domain | Profile | Yes (Pro) |

## Tips

1. **Keep email current** - Required for account recovery
2. **Enable 2FA** - Protects your account
3. **Review sessions** - Log out unused devices
4. **Update password** - Especially if shared
5. **Save backup codes** - For 2FA recovery
CONTENT
    ],

    // Link Building Strategies
    [
        'title' => 'Link Building Strategies',
        'slug' => 'link-building-strategies',
        'category' => 'links-widgets',
        'tags' => 'links, strategy, promotion, growth',
        'content' => <<<'CONTENT'
# Link Building Strategies

Maximize the effectiveness of your PodaBio links.

## Essential Links Every Podcaster Needs

### Podcast Platforms (Priority 1)
- Apple Podcasts
- Spotify
- Google Podcasts / YouTube Music
- Amazon Music / Audible
- Your main listening platform

### Social Media (Priority 2)
- Primary social platform (where you're most active)
- Secondary platform
- Newsletter or community (Discord, etc.)

### Support/Monetization (Priority 3)
- Patreon / Ko-fi / Buy Me a Coffee
- Merch store
- Tip jar

### Additional (As Needed)
- Website / blog
- Contact / booking
- Sponsor inquiries

## Link Organization Best Practices

### The Priority Order
Put your most important links first:
1. **Primary call to action** - Where you want people to go most
2. **Listen links** - Podcast directories
3. **Connect links** - Social media
4. **Support links** - Monetization
5. **Everything else**

### Why Order Matters
- Most visitors see only top 3-5 links
- Mobile users scroll less
- First link gets most clicks
- Decision fatigue reduces clicks on later links

## Writing Effective Link Titles

### Be Specific
❌ "Listen here"
✅ "Listen on Apple Podcasts"

### Include Action Words
❌ "My Spotify"
✅ "Follow on Spotify"

### Keep It Short
❌ "Click here to subscribe to my podcast on YouTube"
✅ "Subscribe on YouTube"

### Match Platform Style
Use terms people recognize:
- "Follow" (Spotify)
- "Subscribe" (YouTube, Apple)
- "Support" (Patreon)
- "Join" (Discord, community)

## Using Icons

Icons help visitors recognize platforms instantly.

### Best Practice
- Use official platform icons
- Keep consistent style
- Don't use icons that confuse

### Icon + Text
Combine icon with short text:
🎵 "Apple Podcasts"
🟢 "Spotify"
📺 "YouTube"

## Seasonal and Campaign Links

### Time-Limited Links
- New episode promotion
- Special offers
- Event registration
- Holiday content

### Managing Seasonal Links
1. Add new seasonal link at top
2. Set as "featured" if available
3. Remove or hide after campaign ends
4. Don't leave outdated links

## A/B Testing Your Links

### What to Test
- Link order
- Link titles
- With vs without icons
- Number of links

### How to Test
1. Make one change
2. Wait 1-2 weeks
3. Check analytics
4. Compare click rates
5. Keep winner, test next change

## Advanced Strategies

### Link Grouping
Group related links:
- All podcast platforms together
- All social together
- Visual dividers between groups

### Reducing Options
Paradox of choice: too many links = fewer clicks
- Test removing your lowest-performing links
- Focus on quality over quantity

### Smart Links
Use smart link services that detect platform:
- Listener clicks one link
- Service detects their device
- Redirects to right platform

Examples: pod.link, linktr.ee for music, etc.

## Tracking Success

### Metrics to Watch
- Total link clicks
- Click-through rate
- Top-performing links
- Trends over time

### Using Data
- Promote high performers
- Fix or remove low performers
- Test changes systematically
CONTENT
    ],

    // Growing Your Audience
    [
        'title' => 'Growing Your Audience with PodaBio',
        'slug' => 'growing-your-audience',
        'category' => 'page-setup',
        'tags' => 'growth, audience, promotion, marketing',
        'content' => <<<'CONTENT'
# Growing Your Audience with PodaBio

Use your PodaBio page to build and engage your podcast audience.

## The Power of Link-in-Bio

Your PodaBio page is a hub for:
- Converting social followers to listeners
- Directing listeners to subscribe
- Building email lists
- Creating community

## Promotion Strategies

### Social Media Bio
Add your PodaBio URL to:
- Instagram bio
- TikTok bio
- Twitter/X bio
- LinkedIn profile
- YouTube description
- All social platforms!

### In Your Podcast
Mention your link:
- At episode beginning
- During call-to-action sections
- In episode outro
- Keep URL short and memorable

**Script example:**
"Visit poda.bio/yourshow for links to follow us everywhere and join our newsletter."

### Show Notes
Include your PodaBio link in:
- Episode descriptions
- Show notes
- Timestamps section

### Email Signature
Add to your professional email:
```
Listen to [Podcast Name]: poda.bio/yourshow
```

### Business Cards & Physical
If you do in-person events:
- QR code to your page
- Simple URL on cards
- Promotional materials

## Building Your Email List

### Why Email Matters
- You own your email list
- Higher engagement than social
- Direct communication
- Not algorithm-dependent

### How to Grow It
1. **Enable email widget** on your page
2. **Offer value** - What do subscribers get?
3. **Mention in episodes** - Remind listeners to subscribe
4. **Promote on social** - Direct followers to sign up

### What to Offer Subscribers
- Early episode access
- Bonus content
- Show notes delivered
- Exclusive updates
- Community access

## Converting Visitors to Listeners

### Make It Easy
- Put podcast links first
- Use familiar platforms
- One-click to listen

### Build Trust
- Professional-looking page
- Good profile image
- Clear description of your show

### Create Urgency
- "New episode every [day]"
- "Latest episode: [title]"
- Time-sensitive content

## Community Building

### Discord / Community Links
Link to your community spaces:
- Discord server
- Facebook group
- Subreddit
- Patreon community

### Engage Beyond Episodes
- Your page is a gathering point
- Link to interactive content
- Build relationships

## Measuring Growth

### Key Metrics
1. **Page views** - Are people finding you?
2. **Link clicks** - Are they taking action?
3. **Subscribers** - Are you building your list?
4. **Trend over time** - Is it growing?

### What Success Looks Like
- Consistent traffic from social
- Growing click-through rates
- Email list growing weekly
- Increased listener feedback

## Growth Checklist

- [ ] PodaBio URL in all social bios
- [ ] Mentioned in podcast episodes
- [ ] Email collection enabled
- [ ] Primary links are obvious
- [ ] Page looks professional
- [ ] Updated regularly

## Common Mistakes

### Avoid These
- Too many links (overwhelming)
- No email collection (missed opportunity)
- Never mentioning your link in episodes
- Outdated links
- No call to action

### Instead Do
- Focus on key links
- Collect emails from day one
- Regularly promote your page
- Keep links current
- Tell visitors what to do
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
echo "Final Articles Complete!\n";
echo "Articles created: {$insertedCount}\n";
echo "Articles skipped: {$skippedCount}\n";
echo "========================================\n";

// Show total count
$total = SupportArticle::count(true);
echo "Total published articles: {$total}\n";

