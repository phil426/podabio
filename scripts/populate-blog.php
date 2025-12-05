<?php
/**
 * Populate Blog with Articles
 * PodaBio - Initial blog content
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/BlogPost.php';
require_once __DIR__ . '/../classes/BlogCategory.php';

echo "Starting Blog population...\n\n";

// Get admin user ID (phil624@gmail.com or first user)
$adminUser = fetchOne("SELECT id FROM users WHERE email = 'phil624@gmail.com' LIMIT 1");
if (!$adminUser) {
    $adminUser = fetchOne("SELECT id FROM users ORDER BY id ASC LIMIT 1");
}
if (!$adminUser) {
    die("Error: No users found in database. Please create a user first.\n");
}
$authorId = $adminUser['id'];
echo "Using author ID: {$authorId}\n\n";

// ============================================
// CATEGORIES
// ============================================

$categories = [
    [
        'name' => 'Product Updates',
        'slug' => 'product-updates',
        'description' => 'New features, improvements, and announcements from the PodaBio team.',
        'display_order' => 1
    ],
    [
        'name' => 'Podcasting Tips',
        'slug' => 'podcasting-tips',
        'description' => 'Advice and strategies for growing your podcast.',
        'display_order' => 2
    ],
    [
        'name' => 'Creator Stories',
        'slug' => 'creator-stories',
        'description' => 'Inspiring stories from podcasters using PodaBio.',
        'display_order' => 3
    ],
    [
        'name' => 'Industry News',
        'slug' => 'industry-news',
        'description' => 'The latest news and trends in podcasting.',
        'display_order' => 4
    ]
];

$categoryIds = [];

foreach ($categories as $cat) {
    $existing = BlogCategory::getBySlug($cat['slug']);
    if ($existing) {
        echo "Category '{$cat['name']}' already exists, skipping...\n";
        $categoryIds[$cat['slug']] = $existing['id'];
    } else {
        $id = BlogCategory::create($cat);
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
// BLOG POSTS
// ============================================

$posts = [
    // Product Updates
    [
        'title' => 'Introducing PodaBio: The Link-in-Bio Built for Podcasters',
        'slug' => 'introducing-podabio',
        'category' => 'product-updates',
        'excerpt' => 'We built PodaBio because podcasters deserve better than generic link-in-bio tools. Here\'s our story and vision.',
        'featured' => 1,
        'content' => <<<'CONTENT'
# Introducing PodaBio: The Link-in-Bio Built for Podcasters

We're thrilled to officially launch PodaBio – the link-in-bio platform designed specifically for podcasters.

## Why We Built PodaBio

As podcasters ourselves, we were frustrated with existing link-in-bio tools. They worked fine for influencers and businesses, but they didn't understand what podcasters actually need:

- **RSS feed integration** that automatically updates with new episodes
- **Built-in podcast players** so listeners can sample your show
- **Themes designed for audio content** that showcase your podcast beautifully
- **Email collection** to build your audience beyond algorithms

Generic tools forced us to manually update links, couldn't display our episodes, and looked like everyone else's page. We knew there had to be a better way.

## What Makes PodaBio Different

### Automatic Episode Sync
Connect your RSS feed once, and PodaBio automatically imports your podcast details and keeps your episode list fresh. No more manual updates when you publish new content.

### Play Episodes Directly
Visitors can listen to your podcast right on your page. The built-in player supports all your episodes with full playback controls.

### Podcaster-First Design
Our 49+ themes are designed with podcasters in mind. They highlight your show artwork, make episode browsing easy, and look professional without any design skills required.

### Grow Your Audience
Collect email subscribers directly from your page with integrations for Mailchimp, ConvertKit, MailerLite, and Brevo. Own your audience relationship.

## Our Vision

We believe every podcaster deserves a professional home base on the internet – a single link that showcases their show, connects listeners to all platforms, and helps grow their audience.

PodaBio is that home base.

## Get Started Free

Creating your PodaBio page is free and takes just minutes:

1. Sign up at poda.bio/signup
2. Add your podcast RSS feed
3. Choose a theme
4. Share your link everywhere

We can't wait to see what you create.

Happy podcasting! 🎙️

*– The PodaBio Team*
CONTENT
    ],
    [
        'title' => 'New Feature: 49+ Premium Themes Now Available',
        'slug' => 'premium-themes-launch',
        'category' => 'product-updates',
        'excerpt' => 'We\'ve added 49 professionally designed themes to help your podcast page stand out. Explore minimal, bold, gradient, and dark themes.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# New Feature: 49+ Premium Themes Now Available

Your podcast is unique, and your link-in-bio page should be too. Today we're excited to announce the launch of our expanded theme library with 49+ professionally designed themes.

## Theme Categories

### Minimal
Clean, simple designs that put your content front and center. Perfect for podcasts that want a professional, understated look.

### Bold
Eye-catching designs with vibrant colors and strong typography. Great for entertainment, comedy, and high-energy shows.

### Gradient
Modern themes with beautiful color transitions. These contemporary designs work wonderfully for creative and artistic podcasts.

### Dark
Sleek dark themes for a sophisticated, modern feel. Popular with tech podcasts, gaming shows, and late-night content.

### Light
Bright, friendly themes that feel approachable and welcoming. Ideal for lifestyle, wellness, and family-friendly content.

## Pro Customization

With a Pro subscription, you can go beyond themes:

- **Custom colors** – Match your exact brand colors
- **Custom fonts** – Choose from 50+ carefully selected typefaces
- **Override any theme** – Start with a theme and make it yours

## How to Choose

Not sure which theme to pick? Here are some tips:

1. **Consider your brand** – Does your podcast have existing colors or style?
2. **Think about your audience** – What aesthetic would resonate with them?
3. **Test on mobile** – Most visitors view on phones
4. **Don't overthink it** – You can always change later!

## Browse Themes Now

Head to the Themes tab in your PodaBio Studio to explore the full collection. Click any theme to preview it instantly on your page.

Happy designing!
CONTENT
    ],
    [
        'title' => 'Email Collection is Here: Grow Your Audience',
        'slug' => 'email-collection-feature',
        'category' => 'product-updates',
        'excerpt' => 'Connect your email marketing service and start collecting subscribers directly from your PodaBio page.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# Email Collection is Here: Grow Your Audience

One of the most requested features is now live: email subscriber collection directly on your PodaBio page.

## Why Email Matters for Podcasters

Social media followers are great, but you don't own that relationship. Algorithms decide who sees your posts, and platforms can change the rules anytime.

Email is different:

- **You own your list** – No algorithm between you and your audience
- **Higher engagement** – Email open rates typically beat social reach
- **Direct communication** – Announce new episodes, share behind-the-scenes content
- **Platform-proof** – Your list travels with you

## Supported Integrations

We've integrated with the most popular email marketing platforms:

- **Mailchimp** – The classic choice
- **ConvertKit** – Creator-focused and powerful
- **MailerLite** – Simple and affordable
- **Brevo** (formerly Sendinblue) – Great free tier

## How to Set It Up

1. Go to **Integrations** in your PodaBio Studio
2. Select your email service
3. Enter your API key
4. Choose your list or audience
5. Add the Email Subscribe widget to your page

That's it! New subscribers go directly to your email list.

## Best Practices

**Offer something valuable:**
- Early episode access
- Bonus content
- Show notes delivered to inbox
- Exclusive updates

**Keep it simple:**
- Just ask for email (not name, phone, etc.)
- Clear call-to-action
- Explain what they'll get

**Promote it:**
- Mention in your podcast episodes
- Share on social media
- Include in your email signature

## Start Growing Today

Email collection is included in all Pro plans. Connect your email service and add the widget today.

Your future self will thank you for building that list.
CONTENT
    ],

    // Podcasting Tips
    [
        'title' => '10 Ways to Promote Your Podcast in 2024',
        'slug' => '10-ways-promote-podcast-2024',
        'category' => 'podcasting-tips',
        'excerpt' => 'Practical strategies to grow your podcast audience this year, from social media tactics to cross-promotion opportunities.',
        'featured' => 1,
        'content' => <<<'CONTENT'
# 10 Ways to Promote Your Podcast in 2024

Growing a podcast audience takes consistent effort. Here are 10 proven strategies to help you reach more listeners this year.

## 1. Optimize Your Link-in-Bio

Your link-in-bio is often the first impression potential listeners get. Make sure it:
- Has a clear call-to-action to listen
- Shows your latest episodes
- Links to all major platforms
- Looks professional and on-brand

(Shameless plug: PodaBio does all this automatically 😉)

## 2. Create Video Clips for Social

Short video clips from your episodes perform incredibly well on social media:
- TikTok and Instagram Reels (30-60 seconds)
- YouTube Shorts
- Twitter/X video posts

Use tools like Descript, Opus Clip, or Headliner to create clips easily.

## 3. Be a Guest on Other Podcasts

Cross-promotion through guest appearances is one of the most effective growth strategies:
- Find podcasts in adjacent niches
- Offer value to their audience
- Mention your show naturally
- Connect with hosts on social media first

## 4. Leverage Your Email List

If you have an email list (and you should!):
- Send new episode announcements
- Share behind-the-scenes content
- Ask subscribers to share with friends
- Include easy listen links

## 5. Submit to Podcast Directories

Make sure you're listed everywhere:
- Apple Podcasts
- Spotify
- Google Podcasts
- Amazon Music
- Pocket Casts
- Overcast
- And more...

## 6. Ask for Reviews

Reviews help with discoverability, especially on Apple Podcasts:
- Ask at the end of episodes
- Make it easy (link directly to review page)
- Thank reviewers publicly

## 7. Join Podcasting Communities

Engage authentically in communities where podcasters gather:
- Reddit (r/podcasting, r/podcasts)
- Facebook groups
- Discord servers
- Twitter/X podcaster community

Don't just promote – add value and build relationships.

## 8. Collaborate with Other Creators

Beyond guest appearances:
- Co-host special episodes
- Create crossover content
- Share each other's episodes
- Joint giveaways

## 9. Repurpose Your Content

One episode can become:
- Blog post or show notes
- Social media posts
- Newsletter content
- YouTube video
- Audiogram graphics
- Quote images

## 10. Be Consistent

The most important strategy is consistency:
- Regular release schedule
- Reliable quality
- Ongoing promotion
- Patient growth

Overnight success is rare. Most successful podcasts grew slowly over years of consistent effort.

## Start Today

Pick 2-3 strategies from this list and commit to them for the next 3 months. Track your results and adjust.

Growth is possible – it just takes persistence.

Happy podcasting!
CONTENT
    ],
    [
        'title' => 'How to Write Podcast Show Notes That Actually Help',
        'slug' => 'write-better-show-notes',
        'category' => 'podcasting-tips',
        'excerpt' => 'Show notes are often an afterthought, but good ones can boost discoverability and listener engagement. Here\'s how to write them.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# How to Write Podcast Show Notes That Actually Help

Show notes are the unsung hero of podcast marketing. Done well, they improve SEO, provide value to listeners, and save you from answering the same questions repeatedly.

## Why Show Notes Matter

### Discoverability
Search engines can't listen to your audio, but they can index your show notes. Good notes help people find your episodes through Google.

### Listener Value
Notes give listeners:
- Quick episode overview
- Links to resources mentioned
- Timestamps for navigation
- Way to reference later

### Professionalism
Detailed notes signal that you care about your content and your audience.

## What to Include

### Episode Summary
2-3 sentences explaining what the episode covers. Be specific enough to be useful, concise enough to be scannable.

**Example:**
"In this episode, we break down the five biggest mistakes new podcasters make and how to avoid them. Plus, our guest shares how she grew her show to 50,000 downloads in one year."

### Key Timestamps
Help listeners jump to sections they care about:
```
[0:00] Introduction
[2:30] Guest introduction
[5:15] Mistake #1: Inconsistent schedule
[12:40] Mistake #2: Poor audio quality
[22:00] Growth strategies that worked
[35:15] Rapid fire questions
[40:00] Where to find our guest
```

### Links and Resources
Everything mentioned in the episode:
- Guest's website and social
- Books or articles referenced
- Tools and software mentioned
- Your own relevant content

### Guest Bio
If you have a guest, include:
- Who they are
- Why they're relevant
- Where to find them

### Call to Action
What do you want listeners to do?
- Subscribe on their platform
- Leave a review
- Join your email list
- Check out a sponsor

## Show Notes Template

```
Episode Title

[2-3 sentence summary]

In This Episode:
- [Key point 1]
- [Key point 2]
- [Key point 3]

Timestamps:
[0:00] - Topic

Links & Resources:
- [Link 1]
- [Link 2]

Connect with [Guest Name]:
- Website:
- Twitter:
- LinkedIn:

Connect with Us:
- Website: poda.bio/yourshow
- Email list: 
- Twitter: @yourshow

If you enjoyed this episode, please leave a review!
```

## Quick Tips

1. **Write notes while editing** – Details are fresh in your mind
2. **Use bullet points** – Easy to scan
3. **Include keywords** – Think about what people search for
4. **Be consistent** – Same format every episode
5. **Keep updating** – Links break, information changes

## Your Turn

Good show notes don't take long once you have a system. Start with the template above and adjust for your show.

Your listeners (and Google) will thank you!
CONTENT
    ],
    [
        'title' => 'The Best Podcast Equipment for Every Budget',
        'slug' => 'best-podcast-equipment-2024',
        'category' => 'podcasting-tips',
        'excerpt' => 'From budget-friendly starter kits to professional setups, here\'s our guide to podcast equipment at every price point.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# The Best Podcast Equipment for Every Budget

Good audio quality matters, but you don't need to spend thousands to sound professional. Here's our equipment guide for every budget.

## Budget Tier: Under $100

You can start a podcast for less than you'd spend on a nice dinner out.

### Microphone: Samson Q2U ($70)
- USB and XLR connections
- Great for beginners
- Decent sound quality
- Built-in headphone jack

### Alternative: Audio-Technica ATR2100x ($79)
- Similar to Samson Q2U
- Slightly better build quality

### Headphones: Any wired headphones
- Use what you have
- Avoid wireless (latency issues)
- Over-ear is better than earbuds

### Recording: Free software
- Audacity (free, cross-platform)
- GarageBand (free, Mac)
- Anchor (free, mobile)

**Total: ~$70-100**

## Mid-Range: $200-500

Investment that will last years and significantly improve your sound.

### Microphone: Shure SM58 or SM7B
- **SM58** (~$100): Industry standard, nearly indestructible
- **SM7B** (~$400): Broadcast quality, famous for good reason

### Audio Interface: Focusrite Scarlett Solo ($120)
- Clean preamps
- Easy setup
- Reliable

### Headphones: Audio-Technica ATH-M50x ($150)
- Industry standard
- Accurate sound
- Comfortable for long sessions

### Boom Arm: Rode PSA1 ($100)
- Sturdy and smooth
- Gets mic off desk (reduces vibration noise)

### Pop Filter: Any basic option ($15-30)

**Total: ~$385-800**

## Professional: $1000+

For those who want the best or are generating revenue.

### Microphone: Shure SM7B ($400) or Electro-Voice RE20 ($450)
- Broadcast standard
- Rich, professional sound
- Great for any voice type

### Audio Interface: RodeCaster Pro II ($700)
- All-in-one solution
- Built-in processing
- Multiple inputs
- Sound pads

### Headphones: Beyerdynamic DT 770 Pro ($150)
- Reference quality
- Extremely comfortable

### Acoustic Treatment: Basic panels ($200-500)
- Significantly improves room sound
- DIY options available

**Total: ~$1500-2000+**

## What Actually Matters

### 1. Room acoustics (free to improve)
- Record in a quiet space
- Soft surfaces reduce echo
- Closets often sound great
- Avoid rooms with hard floors and bare walls

### 2. Microphone technique (free)
- 4-6 inches from mic
- Speak past the mic, not directly into it
- Consistent distance
- Pop filter for plosives

### 3. Consistent levels
- Not too quiet, not clipping
- Process in post if needed

## Our Recommendation

**For most new podcasters: Samson Q2U + Audacity**

Start simple. Upgrade when you know:
1. You'll stick with podcasting
2. What specifically needs improvement
3. You have budget to invest

The best equipment is what you'll actually use consistently.

## One More Thing

Great content beats great audio. Listeners will tolerate imperfect sound for compelling content, but perfect audio won't save boring episodes.

Focus on making great content first, then improve your setup over time.

Happy recording! 🎙️
CONTENT
    ],

    // Industry News
    [
        'title' => 'The State of Podcasting in 2024: Trends and Insights',
        'slug' => 'state-of-podcasting-2024',
        'category' => 'industry-news',
        'excerpt' => 'A look at where podcasting stands today: growth numbers, platform changes, monetization trends, and what to expect ahead.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# The State of Podcasting in 2024: Trends and Insights

Podcasting continues to evolve rapidly. Here's our analysis of where the industry stands and where it's heading.

## By the Numbers

### Audience Growth
- Over 500 million podcast listeners worldwide
- ~100 million monthly listeners in the US alone
- Gen Z podcast listening growing fastest
- Average listener subscribes to 7 shows

### Content Volume
- Over 5 million podcasts exist
- But only ~500,000 are "active" (published in last 90 days)
- Quality increasingly matters for discoverability
- Niche content thriving

## Platform Dynamics

### Spotify's Dominance
Spotify has become the #1 platform for podcast consumption in many markets:
- Heavy investment in exclusive content
- Video podcasts growing
- Improved discovery features
- Creator tools expanding

### Apple Podcasts Evolution
Apple remains crucial:
- Still important for many demographics
- Subscription features for creators
- Improved analytics
- Focus on editorial curation

### YouTube's Rise
YouTube is now a major podcast platform:
- Video podcasts mainstream
- Strong search and discovery
- Podcast RSS integration
- Monetization options

### New Players
- Amazon Music/Audible growing
- TikTok as discovery platform
- AI-powered podcast apps emerging

## Monetization Trends

### Advertising
- Programmatic ads growing
- Host-read ads still premium
- CPMs stabilizing
- Brand safety concerns

### Subscriptions
- Paid podcast tiers working for some
- Patreon/Memberful popular
- Apple/Spotify subscription features
- Bonus content model common

### Diversification
Smart podcasters are diversifying:
- Courses and digital products
- Live events and tours
- Merchandise
- Consulting and speaking
- Community memberships

## Content Trends

### Video First
Many new shows plan for video from day one:
- YouTube as distribution channel
- Clips for social media
- Higher production requirements
- But audio-first still viable

### Short-Form
- Daily news podcasts thriving
- Micro-content for social
- Snackable episodes alongside long-form

### AI Integration
- AI-assisted editing
- Transcription and show notes
- Translation and dubbing
- Discovery and recommendations

## Challenges

### Discovery
Finding audience remains hard:
- Overcrowded marketplace
- Algorithm-dependent
- Cross-promotion essential

### Burnout
Creator sustainability is real concern:
- Publishing pace pressure
- Monetization challenges
- Audience expectations

### Measurement
Attribution still difficult:
- Download counts imperfect
- ROI hard to prove
- Standards slowly improving

## Looking Ahead

### What We Expect
- Video integration increases
- AI tools become standard
- Direct audience relationships matter more
- Quality differentiation increases
- Niche podcasts continue thriving

### Opportunities
- Underserved topics and audiences
- International markets
- B2B and enterprise podcasting
- Education and training
- Community-powered shows

## For Podcasters

Our advice:

1. **Focus on your audience** – Build direct relationships
2. **Quality over quantity** – Better to be great than frequent
3. **Diversify platforms** – Don't depend on any single one
4. **Build community** – Engaged fans beat passive listeners
5. **Think long-term** – Sustainable beats viral

The podcasting opportunity is bigger than ever for those willing to put in the work.

What trends are you seeing? Let us know!
CONTENT
    ],

    // Creator Stories
    [
        'title' => 'How These Podcasters Use PodaBio to Grow Their Shows',
        'slug' => 'podcaster-success-stories',
        'category' => 'creator-stories',
        'excerpt' => 'Real stories from podcasters who have used PodaBio to streamline their online presence and grow their audience.',
        'featured' => 0,
        'content' => <<<'CONTENT'
# How These Podcasters Use PodaBio to Grow Their Shows

We love hearing how podcasters use PodaBio. Here are some inspiring stories from our community.

## From Scattered Links to Streamlined Hub

**The Challenge:** Most podcasters start by sharing different links in different places – Apple Podcasts link on Instagram, Spotify on Twitter, website somewhere else. It's confusing for listeners and a hassle to manage.

**The Solution:** A single PodaBio link that goes everywhere, with all platforms listed in one place.

**The Result:** Listeners find their preferred platform easily, and the podcaster only needs to share one link.

## Building an Email List from Day One

**The Challenge:** A new podcaster knew they should build an email list but didn't have a website set up yet. They didn't want to wait to start collecting subscribers.

**The Solution:** Added the PodaBio email subscription widget and connected it to ConvertKit. Mentioned the link in every episode.

**The Result:** Built a list of 500+ subscribers in the first 6 months – all from the PodaBio page.

## Professional Presence Without a Website

**The Challenge:** Building and maintaining a website felt overwhelming. The podcaster just wanted a professional online presence without the complexity.

**The Solution:** PodaBio became their "website" – professional themes, podcast info, all important links, email collection.

**The Result:** A polished online presence that took 10 minutes to set up, with zero maintenance.

## Cross-Promotion Made Easy

**The Challenge:** When appearing as a guest on other podcasts, needed an easy way to share everything about the show in one place.

**The Solution:** "Just visit poda.bio/myshow" became the standard call-to-action for guest appearances.

**The Result:** Guest appearances started converting better because listeners had one simple URL to remember.

## Tracking What Works

**The Challenge:** No idea which platforms listeners actually used or which links were most effective.

**The Solution:** PodaBio analytics showing link clicks and traffic sources.

**The Result:** Discovered that despite promoting Spotify heavily, most listeners actually used Apple Podcasts. Adjusted strategy accordingly.

## Common Patterns We See

### What Works
- **Mentioning PodaBio URL in every episode** – Consistency builds recognition
- **Short, memorable usernames** – Easy to say out loud
- **Regular updates** – Fresh content keeps the page engaging
- **Email collection** – Building owned audience
- **Pro themes** – Standing out visually

### Quick Wins
1. Add your PodaBio to all social bios immediately
2. Include in podcast show notes
3. Create a memorable username
4. Enable email collection from day one
5. Choose a theme that matches your podcast vibe

## Share Your Story

We'd love to hear how you use PodaBio! Send your story to hello@poda.bio for a chance to be featured.

Every podcaster's journey is different, but we're here to help make yours easier.

Happy podcasting! 🎙️
CONTENT
    ],
];

// Insert posts
$insertedCount = 0;
$skippedCount = 0;

foreach ($posts as $post) {
    // Check if post exists
    $existing = BlogPost::getBySlug($post['slug']);
    if ($existing) {
        echo "Post '{$post['title']}' already exists, skipping...\n";
        $skippedCount++;
        continue;
    }
    
    // Get category ID
    $categoryId = $categoryIds[$post['category']] ?? null;
    
    $data = [
        'title' => $post['title'],
        'slug' => $post['slug'],
        'content' => $post['content'],
        'excerpt' => $post['excerpt'],
        'category_id' => $categoryId,
        'author_id' => $authorId,
        'published' => 1
    ];
    
    $id = BlogPost::create($data);
    
    if ($id) {
        echo "Created post: {$post['title']}\n";
        $insertedCount++;
    } else {
        echo "Failed to create post: {$post['title']}\n";
    }
}

echo "\n========================================\n";
echo "Blog Population Complete!\n";
echo "Categories: " . count($categoryIds) . "\n";
echo "Posts created: {$insertedCount}\n";
echo "Posts skipped: {$skippedCount}\n";
echo "========================================\n";

