<?php
/**
 * Populate Help Center with Documentation
 * Run this script once to seed the support articles
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SupportArticle.php';
require_once __DIR__ . '/../classes/SupportCategory.php';

echo "Starting Help Center population...\n\n";

// ============================================
// CATEGORIES
// ============================================

$categories = [
    [
        'name' => 'Getting Started',
        'slug' => 'getting-started',
        'description' => 'New to PodaBio? Start here to learn the basics.',
        'display_order' => 1
    ],
    [
        'name' => 'Page Setup',
        'slug' => 'page-setup',
        'description' => 'Configure your PodaBio page with your podcast and content.',
        'display_order' => 2
    ],
    [
        'name' => 'Links & Widgets',
        'slug' => 'links-widgets',
        'description' => 'Add links, social icons, and interactive widgets to your page.',
        'display_order' => 3
    ],
    [
        'name' => 'Themes & Customization',
        'slug' => 'themes-customization',
        'description' => 'Customize the look and feel of your page with themes, colors, and fonts.',
        'display_order' => 4
    ],
    [
        'name' => 'Account & Security',
        'slug' => 'account-security',
        'description' => 'Manage your account settings, password, and security options.',
        'display_order' => 5
    ],
    [
        'name' => 'Custom Domains',
        'slug' => 'custom-domains',
        'description' => 'Use your own domain name for your PodaBio page.',
        'display_order' => 6
    ],
    [
        'name' => 'Integrations',
        'slug' => 'integrations',
        'description' => 'Connect your page with email marketing and other services.',
        'display_order' => 7
    ],
    [
        'name' => 'FAQ & Troubleshooting',
        'slug' => 'faq-troubleshooting',
        'description' => 'Common questions and solutions to frequent issues.',
        'display_order' => 8
    ]
];

$categoryIds = [];

foreach ($categories as $cat) {
    // Check if category exists
    $existing = SupportCategory::getBySlug($cat['slug']);
    if ($existing) {
        echo "Category '{$cat['name']}' already exists, skipping...\n";
        $categoryIds[$cat['slug']] = $existing['id'];
    } else {
        $id = SupportCategory::create($cat);
        if ($id) {
            echo "Created category: {$cat['name']}\n";
            $categoryIds[$cat['slug']] = $id;
        } else {
            echo "Failed to create category: {$cat['name']}\n";
        }
    }
}

echo "\n";

// ============================================
// ARTICLES
// ============================================

$articles = [
    // ==========================================
    // GETTING STARTED
    // ==========================================
    [
        'title' => 'Welcome to PodaBio',
        'slug' => 'welcome-to-podabio',
        'category' => 'getting-started',
        'tags' => 'welcome, introduction, overview',
        'content' => <<<'CONTENT'
# Welcome to PodaBio

PodaBio is the link-in-bio platform built specifically for podcasters. Whether you're just starting out or have an established show, PodaBio helps you create a beautiful landing page that showcases your podcast and connects with your audience.

## What Makes PodaBio Different?

Unlike generic link-in-bio tools, PodaBio is designed from the ground up for podcasters:

- **RSS Feed Integration** - Automatically sync your latest episodes from your podcast RSS feed
- **Built-in Podcast Player** - Let visitors listen to your show directly on your page
- **Podcaster-Focused Themes** - Beautiful designs that highlight your audio content
- **Episode Display** - Show your latest episodes with artwork, descriptions, and play buttons

## What Can You Do with PodaBio?

### Create Your Page
Set up a stunning landing page in minutes at `poda.bio/yourname`. Choose from over 49+ themes and customize colors, fonts, and layouts to match your brand.

### Connect Your Podcast
Add your RSS feed URL and PodaBio will automatically pull in your show's artwork, description, and latest episodes. Your page stays fresh without any manual updates.

### Add Your Links
Include links to all your important platforms - podcast directories, social media, merch stores, Patreon, and more. Organize them with custom icons and titles.

### Grow Your Audience
Collect email subscribers directly from your page with built-in integrations for Mailchimp, ConvertKit, MailerLite, and Brevo.

### Track Performance
See how your page is performing with analytics that show page views, link clicks, and subscriber growth.

## Getting Started

Ready to create your page? Here's how to get started:

1. **Sign up** for a free account at poda.bio/signup
2. **Choose your username** - this will be your page URL (poda.bio/yourname)
3. **Add your podcast** RSS feed to automatically import your show
4. **Customize your page** with themes, colors, and your branding
5. **Add your links** to podcast directories and social profiles
6. **Share your page** in your podcast episodes and social media

## Need Help?

Browse our help center for detailed guides on every feature, or contact our support team if you have questions.

Welcome aboard! 🎙️
CONTENT
    ],
    [
        'title' => 'Creating Your Account',
        'slug' => 'creating-your-account',
        'category' => 'getting-started',
        'tags' => 'signup, register, account, email',
        'content' => <<<'CONTENT'
# Creating Your Account

Getting started with PodaBio is quick and easy. You can create an account using your email address or sign up with Google.

## Sign Up with Email

1. Go to **poda.bio/signup**
2. Enter your email address
3. Create a secure password (at least 8 characters)
4. Choose your username - this will be your page URL
5. Click **Create Account**
6. Check your email for a verification link
7. Click the link to verify your email

### Choosing a Username

Your username is important - it becomes your page URL (e.g., `poda.bio/yourname`). Here are some tips:

- **Keep it short and memorable** - easier for listeners to remember
- **Use your podcast name** if it's available
- **Avoid special characters** - only letters, numbers, hyphens, and underscores
- **3-30 characters** length requirement
- **Usernames are unique** - first come, first served!

## Sign Up with Google

For faster signup:

1. Go to **poda.bio/signup**
2. Click **Continue with Google**
3. Select your Google account
4. Choose your username
5. You're all set - no email verification needed!

## After Signing Up

Once your account is created, you'll be taken to the PodaBio Studio where you can:

- Add your podcast RSS feed
- Customize your page theme
- Add links and social icons
- Set up your profile

## Account Security Tips

- Use a strong, unique password
- Enable two-factor authentication (2FA) for extra security
- Keep your email address up to date
- Don't share your login credentials

## Troubleshooting

**Username already taken?**
Try variations like adding numbers or using your full podcast name.

**Didn't receive verification email?**
Check your spam folder. If it's not there, you can request a new verification email from the login page.

**Google sign-up not working?**
Make sure pop-ups aren't blocked in your browser and try again.
CONTENT
    ],
    [
        'title' => 'Verifying Your Email',
        'slug' => 'verifying-your-email',
        'category' => 'getting-started',
        'tags' => 'email, verification, confirm',
        'content' => <<<'CONTENT'
# Verifying Your Email

Email verification helps keep your account secure and ensures you can receive important notifications.

## How to Verify

1. Check your inbox for an email from PodaBio
2. Open the email titled "Verify your PodaBio account"
3. Click the **Verify Email** button
4. You'll be automatically logged in and redirected to your dashboard

## Didn't Receive the Email?

### Check Your Spam Folder
Verification emails sometimes get filtered. Look in your spam, junk, or promotions folder.

### Request a New Email
If you can't find the email:
1. Go to **poda.bio/login**
2. Enter your email and password
3. You'll see an option to resend the verification email
4. Check your inbox again

### Email Still Not Arriving?
- Make sure you entered the correct email address
- Add `noreply@poda.bio` to your contacts
- Check if your email provider is blocking the message
- Try signing up with a different email address

## Verification Link Expired?

Verification links expire after 24 hours for security. If your link has expired:

1. Go to **poda.bio/login**
2. Try to log in with your credentials
3. Request a new verification email

## Why is Verification Required?

Email verification:
- Confirms you own the email address
- Protects your account from unauthorized access
- Ensures you receive important account notifications
- Enables password reset functionality

## Signed Up with Google?

If you signed up using Google, no email verification is needed - Google has already verified your email address.
CONTENT
    ],
    [
        'title' => 'Understanding the Studio',
        'slug' => 'understanding-the-studio',
        'category' => 'getting-started',
        'tags' => 'studio, dashboard, editor, interface',
        'content' => <<<'CONTENT'
# Understanding the Studio

The PodaBio Studio is your command center for creating and managing your page. Here's a tour of what you'll find.

## Main Navigation

The Studio has several tabs on the left sidebar:

### Layers
Manage the content blocks on your page:
- Add new blocks (links, social icons, podcast player)
- Reorder blocks by dragging
- Edit or delete existing blocks
- Toggle visibility of blocks

### Themes
Browse and apply themes to your page:
- Over 49+ professionally designed themes
- Preview themes before applying
- One-click theme application
- Categories: Minimal, Bold, Gradient, Dark, Light

### Podcast / RSS
Connect your podcast:
- Add your RSS feed URL
- View imported episodes
- Configure podcast player settings
- Search and import podcasts

### Integrations
Connect external services:
- Email marketing (Mailchimp, ConvertKit, MailerLite, Brevo)
- Analytics settings
- Social media connections

### Analytics
Track your page performance:
- Page views over time
- Link click statistics
- Top performing content
- Visitor demographics

### Account
Manage your account:
- Profile settings
- Security (password, 2FA)
- Media library
- Billing and subscription

## Live Preview

On the right side of the Studio, you'll see a live preview of your page. Changes appear instantly as you make them, so you always know exactly how your page will look.

### Preview Options
- **Mobile view** - See how your page looks on phones
- **Desktop view** - See the wider layout
- **Open in new tab** - View your live page

## Header Bar

At the top of the Studio:
- **Menu** - Quick navigation
- **Documentation** - Access help articles
- **Theme toggle** - Switch between light and dark mode
- **Account menu** - Logout, settings

## Tips for Using the Studio

1. **Save automatically** - Most changes save automatically
2. **Use keyboard shortcuts** - `Cmd/Ctrl + S` to force save
3. **Preview often** - Check both mobile and desktop views
4. **Undo mistakes** - Use browser back button for quick undo
CONTENT
    ],

    // ==========================================
    // PAGE SETUP
    // ==========================================
    [
        'title' => 'Adding Your Podcast RSS Feed',
        'slug' => 'adding-podcast-rss-feed',
        'category' => 'page-setup',
        'tags' => 'rss, feed, podcast, episodes, sync',
        'content' => <<<'CONTENT'
# Adding Your Podcast RSS Feed

Connecting your podcast RSS feed is one of the most powerful features of PodaBio. It automatically imports your show details and keeps your page updated with your latest episodes.

## What Gets Imported

When you add your RSS feed, PodaBio imports:
- **Podcast name** and description
- **Cover artwork**
- **Episode list** with titles, descriptions, and publication dates
- **Audio files** for playback

## Finding Your RSS Feed URL

### Apple Podcasts
1. Open Apple Podcasts
2. Find your show
3. Click the share button
4. Copy the link
5. Use a tool like getrssfeed.com to extract the RSS URL

### Spotify for Podcasters
1. Log into Spotify for Podcasters
2. Go to Settings
3. Find your RSS feed URL under "Distribution"

### Buzzsprout
1. Log into Buzzsprout
2. Go to Directories
3. Copy your RSS feed URL

### Anchor/Spotify
1. Log into Anchor
2. Go to Settings → Distribution
3. Copy your RSS feed URL

### Other Hosts
Most podcast hosts display your RSS feed URL in your dashboard settings. Look for "RSS Feed", "Distribution", or "Directories" sections.

## Adding Your Feed to PodaBio

1. Go to the **Podcast / RSS** tab in the Studio
2. Paste your RSS feed URL in the input field
3. Click **Import**
4. Wait for PodaBio to fetch your feed
5. Your podcast details will appear automatically

## Feed Sync Settings

PodaBio syncs your RSS feed regularly to catch new episodes:
- **Automatic sync** - New episodes appear within hours of publishing
- **Manual refresh** - Click "Refresh" to force an immediate sync

## Troubleshooting

**Feed not loading?**
- Make sure the URL is a valid RSS feed (should end in .xml or be from a podcast host)
- Check if your feed is publicly accessible
- Try the feed URL in a browser to verify it works

**Wrong podcast imported?**
- Double-check you copied the correct feed URL
- You can remove and re-add the correct feed

**Episodes not showing?**
- RSS feeds can take a few minutes to process
- Try refreshing the feed manually
- Check if your episodes are marked as "published" in your host

## Supported Feed Formats

PodaBio supports standard podcast RSS 2.0 feeds with iTunes extensions. This covers virtually all podcast hosting platforms.
CONTENT
    ],
    [
        'title' => 'Customizing Your Profile',
        'slug' => 'customizing-your-profile',
        'category' => 'page-setup',
        'tags' => 'profile, bio, name, description',
        'content' => <<<'CONTENT'
# Customizing Your Profile

Your profile is the first thing visitors see. Make it count with a compelling bio and professional image.

## Profile Elements

### Display Name
This is the name shown on your page. Options:
- Your podcast name
- Your personal name
- Your brand name

### Bio / Description
A short description that tells visitors what your page/podcast is about. Tips:
- Keep it concise (1-2 sentences work best)
- Include what your podcast covers
- Add a call-to-action if space allows

Example bios:
- "Weekly interviews with tech founders building the future 🚀"
- "True crime stories that will keep you up at night"
- "Marketing tips for small business owners"

### Profile Image
Your main image appears at the top of your page. Recommendations:
- Use your podcast cover art for brand consistency
- Or use a professional headshot
- Square images work best (400x400px minimum)
- Supported formats: JPG, PNG, WebP

## Editing Your Profile

### From the Layers Tab
1. Click on the profile block at the top of your layers
2. Edit the display name and bio
3. Upload or change your profile image

### From Account Settings
1. Go to the **Account** tab
2. Click **Profile**
3. Update your display name and email

## Profile Image Tips

**For Podcasters:**
- Use your official podcast artwork for instant recognition
- Ensure the image is high resolution
- Test how it looks as a circle (some themes crop to circular)

**Image Optimization:**
- Keep file size under 2MB for fast loading
- Use JPG for photographs
- Use PNG for graphics with transparency

## Common Questions

**Can I have different names for my account vs. my page?**
Yes! Your account name (in settings) can be different from your page display name.

**How do I change my username (URL)?**
Currently, usernames cannot be changed after creation. Contact support if you need assistance.

**Can I add multiple bios?**
Your page has one primary bio. Use widget blocks to add additional descriptive text if needed.
CONTENT
    ],
    [
        'title' => 'Publishing Your Page',
        'slug' => 'publishing-your-page',
        'category' => 'page-setup',
        'tags' => 'publish, live, draft, visibility',
        'content' => <<<'CONTENT'
# Publishing Your Page

Control when your page goes live and who can see it.

## Page Status

Your page can be in one of these states:

### Draft
- Page is not visible to the public
- Only you can see it (when logged in)
- Use this while building your page

### Published
- Page is live and visible to everyone
- Accessible at your poda.bio/username URL
- Search engines can index it

## How to Publish

1. Make sure your page content is ready
2. Look for the **Publish** toggle in the Studio
3. Switch from Draft to Published
4. Your page is now live!

## Before You Publish

Checklist before going live:
- ✅ Profile image uploaded
- ✅ Bio/description added
- ✅ RSS feed connected (if applicable)
- ✅ Links added and tested
- ✅ Theme selected and customized
- ✅ Preview checked on mobile and desktop

## Unpublishing Your Page

Need to take your page offline temporarily?
1. Toggle the publish switch to Draft
2. Your page will show a "Coming Soon" or "Not Found" message
3. Your data is preserved - just toggle back to publish again

## Scheduled Publishing

Want to launch at a specific time? Currently, PodaBio doesn't support scheduled publishing, but you can:
1. Prepare your page in draft mode
2. Publish manually at your desired time
3. Share your link immediately

## After Publishing

Once live:
- Share your link everywhere: social bios, email signatures, show notes
- Add it to your podcast episode descriptions
- Include it in your podcast outro
- Update your existing link-in-bio tools to redirect

## Privacy Considerations

Remember:
- Published pages are publicly accessible
- Search engines may index your page
- Your username/URL is visible
- Only include information you're comfortable sharing publicly
CONTENT
    ],

    // ==========================================
    // LINKS & WIDGETS
    // ==========================================
    [
        'title' => 'Adding Custom Links',
        'slug' => 'adding-custom-links',
        'category' => 'links-widgets',
        'tags' => 'links, buttons, urls, custom',
        'content' => <<<'CONTENT'
# Adding Custom Links

Links are the core of your PodaBio page. Add buttons that direct visitors to your important destinations.

## Adding a New Link

1. Go to the **Layers** tab in the Studio
2. Click **Add Block** or the **+** button
3. Select **Link** from the widget gallery
4. Fill in the details:
   - **Title** - Button text (e.g., "Listen on Spotify")
   - **URL** - Full web address (https://...)
   - **Icon** (optional) - Choose an icon
5. Click **Save**

## Link Types

### Podcast Directory Links
Direct listeners to your show on:
- Apple Podcasts
- Spotify
- Google Podcasts
- Amazon Music
- And more...

### Social Media Links
Connect your social profiles:
- Twitter/X
- Instagram
- TikTok
- YouTube
- LinkedIn

### Custom Links
Any URL you want to share:
- Your website
- Merch store
- Patreon/Ko-fi
- Newsletter signup
- Contact form

## Organizing Your Links

### Reordering
Drag and drop links in the Layers panel to change their order. Most important links should be near the top!

### Grouping
Consider organizing by type:
1. Podcast platforms first
2. Social media next
3. Other links at the bottom

### Hiding Links
Toggle the visibility icon to temporarily hide a link without deleting it. Great for seasonal promotions or limited-time offers.

## Link Best Practices

**Keep titles short**
- ✅ "Listen on Spotify"
- ❌ "Click here to listen to my podcast on Spotify"

**Use recognizable icons**
Visitors recognize platform icons faster than reading text.

**Test your links**
Always click through to make sure URLs work correctly.

**Limit total links**
Too many links can be overwhelming. Focus on 5-10 of your most important destinations.

## Analytics

Track which links get clicked most in the Analytics tab. Use this data to optimize your link order and content.

## Affiliate & Disclosure Links

If you include affiliate links:
- Be transparent with your audience
- Add disclosure text where required
- PodaBio supports adding disclosure notices to link blocks
CONTENT
    ],
    [
        'title' => 'Adding Social Media Icons',
        'slug' => 'adding-social-icons',
        'category' => 'links-widgets',
        'tags' => 'social, icons, twitter, instagram, tiktok',
        'content' => <<<'CONTENT'
# Adding Social Media Icons

Social icons provide quick access to your profiles without taking up much space on your page.

## Social Icons vs. Link Blocks

**Social Icons:**
- Compact icon-only display
- Usually grouped in a row
- Perfect for major platforms

**Link Blocks:**
- Full button with text
- More prominent
- Better for calls-to-action

## Adding Social Icons

1. Go to the **Layers** tab
2. Click **Add Block**
3. Select **Social Icons** widget
4. Click **Add Icon**
5. Choose a platform from the list
6. Paste your profile URL
7. Repeat for additional platforms

## Supported Platforms

PodaBio supports 30+ social platforms including:

**Major Platforms:**
- Twitter/X
- Instagram
- TikTok
- YouTube
- Facebook
- LinkedIn
- Threads

**Podcast Platforms:**
- Apple Podcasts
- Spotify
- Google Podcasts

**Creator Platforms:**
- Patreon
- Ko-fi
- Buy Me a Coffee
- Substack
- Medium

**Messaging:**
- Discord
- Telegram
- WhatsApp

**Other:**
- GitHub
- Twitch
- Pinterest
- Snapchat
- Reddit

## Icon Display Options

### Row Layout
Icons display horizontally in a single row - clean and compact.

### Grid Layout
For many icons, a grid layout may work better.

### Icon Size
Some themes offer different icon sizes to match your design.

## Best Practices

**Be selective**
Only include platforms where you're actually active. Empty profiles hurt credibility.

**Keep it current**
Update URLs if your handles change.

**Prioritize by platform**
Put your most active platforms first.

**Consistent branding**
Use the same profile photos/handles across platforms for recognition.

## Finding Your Profile URLs

Most platforms: `https://[platform].com/[yourusername]`

Examples:
- Twitter: `https://twitter.com/yourhandle`
- Instagram: `https://instagram.com/yourhandle`
- TikTok: `https://tiktok.com/@yourhandle`
- YouTube: `https://youtube.com/@yourchannel`
CONTENT
    ],
    [
        'title' => 'Using the Podcast Player Widget',
        'slug' => 'podcast-player-widget',
        'category' => 'links-widgets',
        'tags' => 'podcast, player, episodes, audio, listen',
        'content' => <<<'CONTENT'
# Using the Podcast Player Widget

The podcast player widget lets visitors listen to your show directly on your PodaBio page.

## Setting Up the Player

### Prerequisites
You need to connect your RSS feed first:
1. Go to **Podcast / RSS** tab
2. Add your podcast RSS feed URL
3. Wait for episodes to import

### Adding the Player
1. Go to **Layers** tab
2. Click **Add Block**
3. Select **Podcast Player**
4. Configure display options
5. Save

## Player Features

### Episode Display
- Shows your latest episodes
- Episode titles and descriptions
- Publication dates
- Episode artwork (if available)

### Playback Controls
- Play/pause button
- Progress bar with seeking
- Volume control
- Playback speed options (0.5x, 1x, 1.5x, 2x)

### Episode List
- Scrollable list of episodes
- Click any episode to play
- Shows episode duration

## Player Configuration

### Episodes to Show
Choose how many episodes to display:
- Latest 5 episodes (recommended)
- Latest 10 episodes
- All episodes

### Display Style
- **Compact** - Minimal player, great for pages with lots of content
- **Full** - Larger player with more episode details
- **Featured** - Highlights the latest episode prominently

### Auto-play
By default, audio doesn't auto-play (this is better for user experience and browser compatibility).

## Player Customization

The player automatically matches your theme:
- Colors adapt to your theme
- Fonts match your page design
- Border styles follow theme settings

## Mobile Experience

The podcast player is fully responsive:
- Touch-friendly controls
- Works on all modern mobile browsers
- Continues playing in background (device permitting)

## Troubleshooting

**Episodes not showing?**
- Check that your RSS feed is connected
- Try refreshing the feed manually
- Ensure episodes are published in your host

**Audio won't play?**
- Check that the episode has a valid audio URL
- Some episodes may have restricted access
- Try the episode in your podcast app to verify

**Player looks wrong?**
- Clear your browser cache
- Try a different browser
- Check if your theme is causing conflicts
CONTENT
    ],
    [
        'title' => 'Email Subscription Widget',
        'slug' => 'email-subscription-widget',
        'category' => 'links-widgets',
        'tags' => 'email, subscribe, newsletter, mailchimp, convertkit',
        'content' => <<<'CONTENT'
# Email Subscription Widget

Grow your email list by collecting subscriber emails directly from your PodaBio page.

## Why Collect Emails?

Email is one of the most reliable ways to reach your audience:
- You own your email list (unlike social followers)
- Higher engagement rates than social media
- Direct communication channel
- Not affected by algorithm changes

## Setting Up Email Collection

### Step 1: Connect an Email Service
Go to **Integrations** tab and connect one of:
- Mailchimp
- ConvertKit
- MailerLite
- Brevo (formerly Sendinblue)

### Step 2: Add the Widget
1. Go to **Layers** tab
2. Click **Add Block**
3. Select **Email Subscribe**
4. Configure the widget

### Step 3: Customize
- **Heading** - e.g., "Join the newsletter"
- **Description** - e.g., "Get episode updates and bonus content"
- **Button text** - e.g., "Subscribe"
- **Success message** - What visitors see after subscribing

## Integration Setup

### Mailchimp
1. Get your API key from Mailchimp (Account → API Keys)
2. Paste in PodaBio integrations
3. Select your audience/list
4. Enable double opt-in if desired

### ConvertKit
1. Get your API key from ConvertKit (Settings → Advanced)
2. Paste in PodaBio integrations
3. Select a form or tag for new subscribers

### MailerLite
1. Get your API key from MailerLite (Integrations → API)
2. Paste in PodaBio integrations
3. Select your subscriber group

### Brevo
1. Get your API key from Brevo (SMTP & API → API Keys)
2. Paste in PodaBio integrations
3. Select your contact list

## Double Opt-In

We recommend enabling double opt-in:
- Subscribers confirm their email address
- Better list quality
- Compliant with email regulations (GDPR, CAN-SPAM)
- Reduces spam signups

## Best Practices

**Offer value**
Tell people what they'll get:
- Early episode access
- Bonus content
- Show notes
- Exclusive updates

**Keep it simple**
Only ask for email (no need for name, etc.) to maximize conversions.

**Place strategically**
Put the subscription widget where engaged visitors will see it - typically after your main content.

## Troubleshooting

**Subscribers not appearing in your email service?**
- Verify API key is correct
- Check you selected the right list/audience
- Look in your email service's pending/unconfirmed if using double opt-in

**Widget not showing?**
- Make sure an email service is connected
- Check the widget is enabled/visible
CONTENT
    ],

    // ==========================================
    // THEMES & CUSTOMIZATION
    // ==========================================
    [
        'title' => 'Choosing a Theme',
        'slug' => 'choosing-a-theme',
        'category' => 'themes-customization',
        'tags' => 'theme, design, style, template',
        'content' => <<<'CONTENT'
# Choosing a Theme

PodaBio offers 49+ professionally designed themes to give your page a unique look.

## Browsing Themes

1. Go to the **Themes** tab in the Studio
2. Browse the theme gallery
3. Hover over themes to see a preview
4. Click a theme to apply it

## Theme Categories

### Minimal
Clean, simple designs that let your content shine:
- White backgrounds
- Simple typography
- Focused layouts

### Bold
Eye-catching designs with strong visual impact:
- Vibrant colors
- Large typography
- Dynamic layouts

### Gradient
Modern themes with beautiful color transitions:
- Smooth gradient backgrounds
- Contemporary feel
- Perfect for creative podcasts

### Dark
Sleek dark themes for a professional look:
- Dark backgrounds
- Great for tech/gaming podcasts
- Easy on the eyes

### Light
Bright, friendly themes:
- White or light backgrounds
- Clean and approachable
- Good for broad audiences

## Theme Elements

Each theme defines:
- **Background** - Page color or gradient
- **Text colors** - Primary and secondary
- **Accent color** - Buttons and highlights
- **Fonts** - Heading and body typefaces
- **Border styles** - Rounded or sharp corners
- **Spacing** - Layout density

## Applying a Theme

1. Click on a theme thumbnail
2. The theme applies instantly
3. Check the preview to see how it looks
4. Your page is automatically updated

## Customizing After Theme Selection

After applying a theme, you can further customize:
- Override colors (Pro feature)
- Change fonts (Pro feature)
- Adjust specific elements

## Theme Selection Tips

**Match your brand**
If your podcast has brand colors, find a theme that complements them.

**Consider your audience**
- Professional/business? → Minimal or Dark themes
- Creative/artistic? → Bold or Gradient themes
- Friendly/casual? → Light themes

**Test on mobile**
Preview how your theme looks on smaller screens.

**Don't overthink it**
You can always change themes later - nothing is permanent!

## Free vs. Pro Themes

- **Free plan**: Access to 5 basic themes
- **Pro plan**: All 49+ themes plus custom color/font overrides
CONTENT
    ],
    [
        'title' => 'Customizing Colors',
        'slug' => 'customizing-colors',
        'category' => 'themes-customization',
        'tags' => 'colors, customization, branding, design',
        'content' => <<<'CONTENT'
# Customizing Colors

*Pro Feature* - Custom color overrides require a Pro subscription.

Make your page truly yours by customizing the color scheme.

## What Colors Can You Change?

### Page Background
The main background color of your page. Options:
- Solid color
- Gradient (two colors)
- Keep theme default

### Widget Background
The background color of link blocks and cards:
- Match or contrast with page background
- Transparent for a seamless look
- Solid color for definition

### Text Colors
- **Primary text** - Main headings and important text
- **Secondary text** - Descriptions and supporting text

### Accent Color
Used for:
- Buttons
- Links
- Highlights
- Icons

### Border Color
The color of borders around widgets and cards.

## How to Customize Colors

1. Apply a theme first (as your starting point)
2. Go to **Themes** tab
3. Look for **Customize** options
4. Click on any color to open the color picker
5. Choose your color or enter a hex code
6. Changes appear instantly in the preview

## Using the Color Picker

### Preset Colors
Quick-select from a palette of popular colors.

### Custom Color
Enter a specific hex code (e.g., `#8B5CF6`) for exact brand colors.

### Opacity
Some colors support transparency for layered effects.

## Color Best Practices

### Contrast
Ensure text is readable against backgrounds:
- Light text on dark backgrounds
- Dark text on light backgrounds
- Test with the contrast checker

### Consistency
Stick to 2-3 main colors for a cohesive look:
1. Primary brand color
2. Secondary/accent color
3. Neutral (black/white/gray)

### Accessibility
Consider colorblind users:
- Don't rely only on color to convey information
- Ensure sufficient contrast (4.5:1 ratio for text)

## Brand Color Tips

**Finding your podcast's colors:**
- Use colors from your podcast artwork
- Match your website if you have one
- Use a color picker tool on your logo

**Popular podcast color schemes:**
- Navy blue + gold (authoritative)
- Purple + pink (creative)
- Green + white (fresh, natural)
- Red + black (bold, edgy)

## Resetting Colors

To go back to the theme defaults:
- Click "Reset to theme" for individual colors
- Or re-apply the theme to reset everything
CONTENT
    ],
    [
        'title' => 'Customizing Fonts',
        'slug' => 'customizing-fonts',
        'category' => 'themes-customization',
        'tags' => 'fonts, typography, text, customization',
        'content' => <<<'CONTENT'
# Customizing Fonts

*Pro Feature* - Custom font selection requires a Pro subscription.

Typography sets the tone for your page. Choose fonts that match your podcast's personality.

## Font Types

### Heading Font
Used for:
- Your display name
- Section titles
- Button text
- Important labels

### Body Font
Used for:
- Bio/descriptions
- Episode descriptions
- General text content

## Available Fonts

PodaBio offers a curated selection of Google Fonts:

### Sans-Serif (Clean & Modern)
- **Space Grotesk** - Geometric and technical
- **Inter** - Highly readable
- **Poppins** - Friendly and rounded
- **Montserrat** - Classic and professional
- **Raleway** - Elegant and thin
- **Open Sans** - Universal and neutral

### Serif (Classic & Sophisticated)
- **Playfair Display** - Elegant headlines
- **Merriweather** - Comfortable reading
- **Lora** - Balanced and contemporary
- **Crimson Text** - Book-like quality

### Display (Bold & Unique)
- **Bebas Neue** - Strong and impactful
- **Oswald** - Tall and modern
- **Archivo Black** - Heavy and attention-grabbing

### Monospace (Technical)
- **JetBrains Mono** - Clean code font
- **Fira Code** - Developer favorite

## Changing Fonts

1. Go to the **Themes** tab
2. Find the **Fonts** section
3. Select a heading font
4. Select a body font
5. Preview the changes live

## Font Pairing Tips

**Rule of thumb:** Combine one decorative font with one simple font.

**Good pairings:**
- Playfair Display (heading) + Open Sans (body)
- Bebas Neue (heading) + Lora (body)
- Montserrat (heading) + Merriweather (body)
- Space Grotesk (both) - works alone

**Avoid:**
- Two decorative fonts together
- Fonts that are too similar (creates confusion)
- More than 2 fonts total

## Font Matching Your Podcast

**News/Interview shows:** Serif fonts convey trust
**Comedy/Casual:** Rounded sans-serif fonts feel friendly
**Tech/Business:** Clean sans-serif fonts look professional
**True Crime/Drama:** Bold, impactful display fonts
**Self-help/Wellness:** Soft, approachable fonts

## Font Size

Font sizes are controlled by the theme and automatically adjust for mobile devices. You cannot manually adjust sizes, but different themes have different size scales.

## Performance Note

All fonts are served from Google Fonts CDN, ensuring fast loading worldwide.
CONTENT
    ],

    // ==========================================
    // ACCOUNT & SECURITY
    // ==========================================
    [
        'title' => 'Changing Your Password',
        'slug' => 'changing-your-password',
        'category' => 'account-security',
        'tags' => 'password, security, change, update',
        'content' => <<<'CONTENT'
# Changing Your Password

Keep your account secure by updating your password regularly.

## Changing Your Password (When Logged In)

1. Go to **Account** tab in the Studio
2. Click **Security**
3. Look for **Password** section
4. Click **Change Password**
5. Enter your current password
6. Enter your new password
7. Confirm the new password
8. Click **Update Password**

## Password Requirements

Your password must:
- Be at least 8 characters long
- We recommend including:
  - Uppercase and lowercase letters
  - Numbers
  - Special characters (!@#$%^&*)

## Forgot Your Password?

If you're logged out and forgot your password:

1. Go to **poda.bio/login**
2. Click **Forgot Password?**
3. Enter your email address
4. Check your email for a reset link
5. Click the link (valid for 1 hour)
6. Enter your new password
7. You'll be logged in automatically

## Password Reset Email Not Arriving?

- Check spam/junk folders
- Verify you're using the right email
- Wait a few minutes (sometimes delayed)
- Try requesting another reset

## Google Sign-In Users

If you signed up with Google, you don't have a PodaBio password. You can:
- Continue using Google Sign-In
- Or set a password in Security settings to enable email login

## Password Security Tips

**DO:**
- Use a unique password for PodaBio
- Use a password manager
- Enable two-factor authentication
- Update your password periodically

**DON'T:**
- Reuse passwords from other sites
- Share your password
- Use easily guessed info (birthdays, names)
- Store passwords in plain text

## Secure Password Examples

❌ `password123`
❌ `mypodcast`
❌ `john1985`

✅ `Tr0ub4dor&3horse`
✅ `correcthorsebatterystaple`
✅ `P@ssw0rd_Unique_Poda!`

## Locked Out?

If you can't log in and password reset isn't working:
- Make sure you're using the right email
- Try clearing browser cookies
- Contact support for assistance
CONTENT
    ],
    [
        'title' => 'Setting Up Two-Factor Authentication',
        'slug' => 'two-factor-authentication',
        'category' => 'account-security',
        'tags' => '2fa, security, authentication, totp, backup codes',
        'content' => <<<'CONTENT'
# Setting Up Two-Factor Authentication

Two-factor authentication (2FA) adds an extra layer of security to your account.

## What is 2FA?

With 2FA enabled, logging in requires:
1. Your password (something you know)
2. A verification code (something you have)

Even if someone gets your password, they can't access your account without the second factor.

## 2FA Methods Available

### Authenticator App (Recommended)
Use apps like:
- Google Authenticator
- Authy
- 1Password
- Microsoft Authenticator

These apps generate time-based codes that change every 30 seconds.

### Email Codes
Receive a verification code to your email address. Less secure than authenticator apps but more convenient.

### Both Methods
Use authenticator app as primary, with email as backup.

## Setting Up Authenticator App 2FA

1. Go to **Account** → **Security**
2. Find **Two-Factor Authentication**
3. Click **Enable 2FA**
4. Choose **Authenticator App**
5. Scan the QR code with your authenticator app
6. Enter the 6-digit code from the app
7. Save your backup codes!

## Backup Codes

When you enable 2FA, you'll receive backup codes:
- **Save these somewhere safe!**
- Each code can only be used once
- Use them if you lose access to your authenticator
- You can regenerate codes in Security settings

## Logging In with 2FA

1. Enter your email and password
2. You'll be prompted for a verification code
3. Open your authenticator app
4. Enter the current 6-digit code
5. You're logged in!

## Lost Access to Authenticator?

If you can't access your authenticator app:

**Option 1: Use a backup code**
Enter one of your saved backup codes instead.

**Option 2: Use email verification**
If you set up email as a backup method, choose "Use email instead."

**Option 3: Contact support**
If all else fails, contact support with proof of account ownership.

## Disabling 2FA

If you need to turn off 2FA:
1. Go to **Account** → **Security**
2. Find **Two-Factor Authentication**
3. Click **Disable 2FA**
4. Enter your password to confirm
5. Enter a 2FA code (for verification)

## 2FA Best Practices

- ✅ Use an authenticator app over email
- ✅ Store backup codes offline (not in email)
- ✅ Set up 2FA on a new phone BEFORE wiping old one
- ✅ Consider using a password manager with 2FA support
- ❌ Don't share your 2FA codes
- ❌ Don't screenshot QR codes
CONTENT
    ],
    [
        'title' => 'Linking Google Account',
        'slug' => 'linking-google-account',
        'category' => 'account-security',
        'tags' => 'google, oauth, signin, login',
        'content' => <<<'CONTENT'
# Linking Google Account

Connect your Google account for quick and easy sign-in.

## Benefits of Google Sign-In

- **One-click login** - No password to remember
- **Secure** - Google handles authentication
- **Verified email** - No email verification needed
- **Quick signup** - Create account in seconds

## Linking Google to Existing Account

If you signed up with email and want to add Google sign-in:

1. Go to **Account** → **Security**
2. Find **Connected Accounts**
3. Click **Link Google Account**
4. Sign in with your Google account
5. Authorize PodaBio access
6. Done! You can now log in with either method.

## Unlinking Google Account

To remove Google sign-in:

1. Go to **Account** → **Security**
2. Find **Connected Accounts**
3. Click **Unlink** next to Google

**Important:** You must have a password set before unlinking Google, otherwise you'll be locked out!

## Signing Up with Google

When you sign up with Google:
1. We create an account with your Google email
2. No password is set (you'll always use Google sign-in)
3. You can optionally set a password later

## Setting a Password (Google Users)

If you signed up with Google and want email/password login:

1. Go to **Account** → **Security**
2. Click **Set Password**
3. Create your password
4. Now you can log in either way

## Privacy & Permissions

When you connect Google, we only access:
- Your email address
- Basic profile info (name)

We **never** access:
- Your Google password
- Your Drive, Gmail, or other Google services
- Your contacts

You can revoke PodaBio's access anytime in your Google Account settings.

## Troubleshooting

**Google sign-in popup blocked?**
- Allow popups for poda.bio
- Try disabling ad blockers temporarily

**Wrong Google account linked?**
- Unlink the current account
- Clear browser cookies
- Link the correct account

**"Account already exists" error?**
- An account already exists with that email
- Try logging in with email/password
- Or use password reset if needed
CONTENT
    ],

    // ==========================================
    // CUSTOM DOMAINS
    // ==========================================
    [
        'title' => 'Setting Up a Custom Domain',
        'slug' => 'setting-up-custom-domain',
        'category' => 'custom-domains',
        'tags' => 'domain, custom, url, dns',
        'content' => <<<'CONTENT'
# Setting Up a Custom Domain

*Pro Feature* - Custom domains require a Pro subscription.

Use your own domain name instead of poda.bio/username.

## What is a Custom Domain?

Instead of: `poda.bio/yourpodcast`
You can use: `yourpodcast.com` or `links.yourpodcast.com`

## Requirements

1. **Pro subscription** on PodaBio
2. **A domain you own** (purchased from any registrar)
3. **Access to DNS settings** for that domain

## Setup Process Overview

1. Add your domain in PodaBio settings
2. Configure DNS at your domain registrar
3. Wait for DNS to propagate
4. Verify the connection
5. SSL certificate auto-provisions

## Adding Your Domain in PodaBio

1. Go to **Account** → **Profile**
2. Find **Custom Domain** section
3. Enter your domain (e.g., `links.yourpodcast.com`)
4. Click **Verify**
5. Note the DNS instructions shown

## DNS Configuration

You'll need to add an **A record** at your domain registrar:

**A Record Settings:**
- **Type:** A
- **Host/Name:** `@` (for root domain) or subdomain name
- **Value/Points to:** `156.67.73.201`
- **TTL:** 3600 (or default)

### Examples by Registrar

**GoDaddy:**
1. DNS → Add Record
2. Type: A
3. Name: @ 
4. Value: 156.67.73.201

**Namecheap:**
1. Advanced DNS → Add Record
2. Type: A Record
3. Host: @
4. Value: 156.67.73.201

**Cloudflare:**
1. DNS → Add Record
2. Type: A
3. Name: @
4. IPv4: 156.67.73.201
5. Proxy: DNS only (gray cloud)

## Using a Subdomain

Want `links.yourpodcast.com` instead of `yourpodcast.com`?

Use the subdomain as the Host/Name:
- **Host:** links (not @)
- **Value:** 156.67.73.201

## Verification

After adding DNS:
1. Return to PodaBio
2. Click **Verify Domain**
3. If DNS is correct, you'll see "Verified"
4. SSL certificate provisions automatically

## DNS Propagation

DNS changes can take time to spread globally:
- Usually: 5-30 minutes
- Sometimes: up to 48 hours

You can check propagation at: whatsmydns.net

## SSL Certificate

SSL (HTTPS) is handled automatically:
- Certificate provisions after verification
- Your domain will work with https://
- No manual configuration needed

## Troubleshooting

**Domain not verifying?**
- Double-check DNS records
- Wait for propagation (try again later)
- Ensure no conflicting records

**SSL not working?**
- Allow up to 24 hours for provisioning
- Check domain is verified first
- Contact support if persistent
CONTENT
    ],

    // ==========================================
    // INTEGRATIONS
    // ==========================================
    [
        'title' => 'Email Marketing Integrations',
        'slug' => 'email-marketing-integrations',
        'category' => 'integrations',
        'tags' => 'email, mailchimp, convertkit, mailerlite, brevo',
        'content' => <<<'CONTENT'
# Email Marketing Integrations

Connect your email marketing service to collect subscribers from your PodaBio page.

## Supported Services

PodaBio integrates with:
- **Mailchimp** - Popular all-in-one marketing
- **ConvertKit** - Creator-focused email
- **MailerLite** - Simple and affordable
- **Brevo** (formerly Sendinblue) - Transactional and marketing

## Why Connect Email Marketing?

- Collect subscribers directly from your page
- Build your email list automatically
- Sync with your existing campaigns
- Own your audience relationship

## General Setup Steps

1. Go to **Integrations** tab in Studio
2. Select your email service
3. Enter your API key
4. Select your list/audience
5. Configure options
6. Save and test

## Mailchimp Setup

### Getting Your API Key
1. Log into Mailchimp
2. Click your profile → Account
3. Go to Extras → API Keys
4. Create a new key or copy existing

### Connecting
1. Paste API key in PodaBio
2. Your audiences will load automatically
3. Select the audience for new subscribers
4. Enable/disable double opt-in

## ConvertKit Setup

### Getting Your API Key
1. Log into ConvertKit
2. Go to Settings → Advanced
3. Copy your API Key

### Connecting
1. Paste API key in PodaBio
2. Select a form or tag for subscribers
3. Optionally assign tags to new subscribers

## MailerLite Setup

### Getting Your API Key
1. Log into MailerLite
2. Go to Integrations → API
3. Copy your API key

### Connecting
1. Paste API key in PodaBio
2. Select a subscriber group
3. Configure double opt-in preference

## Brevo Setup

### Getting Your API Key
1. Log into Brevo
2. Go to SMTP & API → API Keys
3. Create or copy your API key

### Connecting
1. Paste API key in PodaBio
2. Select a contact list
3. Configure welcome email (optional)

## Double Opt-In

We recommend enabling double opt-in:
- Subscribers confirm via email
- Better list quality
- GDPR compliant
- Reduces spam signups

## Testing Your Integration

After setup:
1. Go to your live page
2. Enter a test email
3. Submit the form
4. Check your email service for the new subscriber
5. Check for confirmation email (if double opt-in)

## Troubleshooting

**API key not working?**
- Verify you copied the full key
- Check the key hasn't been revoked
- Ensure the key has proper permissions

**Subscribers not appearing?**
- Check correct list/audience is selected
- Look in pending/unconfirmed if using double opt-in
- Verify the API connection is active

**Integration disconnected?**
- API keys can expire; generate a new one
- Re-enter credentials if needed
CONTENT
    ],

    // ==========================================
    // FAQ & TROUBLESHOOTING
    // ==========================================
    [
        'title' => 'Frequently Asked Questions',
        'slug' => 'faq',
        'category' => 'faq-troubleshooting',
        'tags' => 'faq, questions, help, common',
        'content' => <<<'CONTENT'
# Frequently Asked Questions

Quick answers to common questions about PodaBio.

## Account & Access

**How do I change my username/URL?**
Currently, usernames cannot be changed after account creation. Contact support if you need assistance.

**Can I have multiple pages?**
Free accounts include one page. Pro accounts can create multiple pages (coming soon with Enterprise plan).

**How do I delete my account?**
Go to Account → Settings and look for "Delete Account." This is permanent and cannot be undone.

## Page & Content

**How often does my RSS feed sync?**
Feeds sync automatically every few hours. You can also manually refresh from the Podcast tab.

**Why aren't my episodes showing?**
Check that your RSS feed URL is correct and publicly accessible. Try refreshing the feed manually.

**Can I use PodaBio without a podcast?**
Absolutely! You can use PodaBio as a general link-in-bio page without connecting a podcast.

**How many links can I add?**
Free plan: Up to 10 links. Pro plan: Unlimited links.

## Design & Themes

**Can I use my own custom CSS?**
Not currently, but we offer extensive customization through themes, colors, and fonts.

**Will my page work on mobile?**
Yes! All PodaBio pages are fully responsive and optimized for mobile devices.

**Can I preview before publishing?**
Yes, the Studio shows a live preview. Your page is in draft mode until you publish.

## Technical

**What browsers are supported?**
PodaBio works on all modern browsers: Chrome, Firefox, Safari, Edge.

**Is there an API?**
Not currently available for public use. Contact us for enterprise needs.

**Where is my data stored?**
Data is stored securely on servers. We use HTTPS encryption for all connections.

## Billing & Plans

**What's included in the free plan?**
- 1 page
- RSS feed sync
- Podcast player
- Up to 10 links
- 5 basic themes
- Basic analytics

**What's included in Pro?**
- Everything in Free
- Unlimited links
- All 49+ themes
- Custom colors & fonts
- Email integrations
- Custom domain
- Advanced analytics
- Priority support

**How do I cancel my subscription?**
Go to Account → Billing and click "Cancel Subscription."

## Getting Help

**How do I contact support?**
Email us at support@poda.bio or use the contact form.

**Is there a community?**
Join our Discord community for tips, help, and connecting with other podcasters.

**Where can I suggest features?**
We love feedback! Email suggestions to feedback@poda.bio.
CONTENT
    ],
    [
        'title' => 'Troubleshooting Common Issues',
        'slug' => 'troubleshooting-common-issues',
        'category' => 'faq-troubleshooting',
        'tags' => 'troubleshooting, problems, issues, fix',
        'content' => <<<'CONTENT'
# Troubleshooting Common Issues

Solutions to problems you might encounter.

## Login Problems

### Can't Log In
- **Check email/password** - Are you using the correct credentials?
- **Clear browser cache** - Old cookies can cause issues
- **Try incognito mode** - Rules out browser extensions
- **Reset password** - Use "Forgot Password" if needed

### Google Sign-In Not Working
- **Allow popups** - Sign-in uses a popup window
- **Disable ad blockers** temporarily
- **Try a different browser**
- **Check Google account** - Make sure it's the right one

### Two-Factor Authentication Issues
- **Use backup codes** if you lost your authenticator
- **Check time sync** on your device (TOTP is time-sensitive)
- **Request email code** if available

## Page Display Issues

### Page Not Loading
- **Check your internet connection**
- **Clear browser cache**
- **Try a different browser**
- **Check if site is down** (rare)

### Page Looks Wrong
- **Clear cache** (Ctrl/Cmd + Shift + R)
- **Check if changes are saved**
- **Preview in incognito mode**
- **Try a different device**

### Theme Not Applying
- **Wait a moment** - changes may take a few seconds
- **Refresh the page**
- **Re-select the theme**
- **Clear browser cache**

## RSS Feed Problems

### Feed Not Importing
- **Verify the URL** - Open it in a browser to check
- **Check feed format** - Must be valid RSS 2.0
- **Ensure public access** - Private feeds won't work
- **Try a different feed URL** from your host

### Episodes Missing
- **Refresh the feed** manually
- **Check episode publish dates**
- **Verify episodes are public** in your podcast host
- **Wait for sync** - can take a few hours

### Wrong Podcast Imported
- **Remove the feed** and re-add the correct one
- **Double-check the RSS URL** you're using

## Email Subscription Issues

### Subscribers Not Appearing
- **Check API key** is entered correctly
- **Verify correct list** is selected
- **Look in pending/unconfirmed** if using double opt-in
- **Test with your own email**

### Integration Disconnected
- **Re-enter API key** - they can expire
- **Generate new key** from your email service
- **Check service status** - provider may be down

## Image Problems

### Image Not Uploading
- **Check file size** - max 5MB recommended
- **Check format** - JPG, PNG, or WebP only
- **Try a different image**
- **Check internet connection**

### Image Looks Wrong
- **Use square images** for profiles
- **Check resolution** - minimum 400x400px
- **Avoid very large files** - they slow loading

## Performance Issues

### Page Loading Slowly
- **Optimize images** - compress large files
- **Reduce number of links** if excessive
- **Check your internet** connection
- **Clear browser cache**

### Changes Not Saving
- **Check internet connection**
- **Look for error messages**
- **Try refreshing and re-doing**
- **Contact support** if persistent

## Still Having Issues?

If none of these solutions work:

1. **Note the exact error** - Screenshot if possible
2. **List steps to reproduce** the issue
3. **Email support@poda.bio** with details
4. **Include your username** and browser/device info
CONTENT
    ],
    [
        'title' => 'Contact Support',
        'slug' => 'contact-support',
        'category' => 'faq-troubleshooting',
        'tags' => 'support, contact, help, email',
        'content' => <<<'CONTENT'
# Contact Support

We're here to help! Here's how to reach us.

## Email Support

**support@poda.bio**

Best for:
- Account issues
- Technical problems
- Bug reports
- Billing questions

Response time: Within 24-48 hours (usually faster)

## What to Include in Your Message

To help us resolve your issue quickly, please include:

1. **Your PodaBio username** or email
2. **Description of the problem** - What happened?
3. **Steps to reproduce** - How can we see the issue?
4. **Expected behavior** - What should happen?
5. **Screenshots** - If applicable
6. **Browser and device** - Chrome on Mac, Safari on iPhone, etc.

## Example Support Request

> **Subject:** RSS feed not importing episodes
> 
> Hi,
> 
> My username is "awesome-podcast" and I'm having trouble with my RSS feed.
> 
> **Problem:** When I add my feed URL, only 3 episodes import instead of all 50.
> 
> **Feed URL:** https://feeds.example.com/mypodcast.xml
> 
> **Steps:** 
> 1. Go to Podcast tab
> 2. Paste feed URL
> 3. Click Import
> 
> I'm using Chrome on Windows 10.
> 
> Thanks!

## Feature Requests

Have an idea for PodaBio?

Email: **feedback@poda.bio**

We read every suggestion and use feedback to guide our roadmap!

## Bug Reports

Found a bug? Help us fix it:
- Email **bugs@poda.bio**
- Include steps to reproduce
- Note your browser/device
- Screenshots are super helpful

## Business Inquiries

For partnerships, enterprise, or press:

**hello@poda.bio**

## Response Times

- **Critical issues** (can't access account): Same day
- **General support**: 24-48 hours
- **Feature requests**: We read all, but may not reply individually
- **Enterprise inquiries**: 1-3 business days

## Before Contacting Support

Save time by checking:
1. **This Help Center** - Your answer might be here!
2. **FAQ page** - Common questions answered
3. **Troubleshooting guide** - Step-by-step fixes

## Pro User Priority

Pro subscribers receive priority support with faster response times.

---

We appreciate your patience and are committed to providing excellent support. Thank you for using PodaBio! 🎙️
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
echo "Help Center Population Complete!\n";
echo "Categories: " . count($categoryIds) . "\n";
echo "Articles created: {$insertedCount}\n";
echo "Articles skipped: {$skippedCount}\n";
echo "========================================\n";


