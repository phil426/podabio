<!-- 093b58d1-3d04-46ec-83d3-ebfbb5fc798a 02ba33c0-5491-4843-9c8b-1857eb49c805 -->
# PodaBio Front-End Website Redesign

## Overview

Complete redesign of all marketing pages with dark theme (#121212 background), Signal Green (#00FF7F) accents, and minimalist aesthetic matching the brand image. Research-informed design incorporating best practices from leading competitors.

## Brand Style Guide (from image)

- **Background**: Dark charcoal (#121212)
- **Accent Color**: Signal Green (#00FF7F)
- **Text**: White/light gray for contrast
- **Tagline**: "The signal, clearer"
- **Headline**: "The link for listeners"
- **Subheadline**: "A minimalist link-in-bio tool built for podcasters. Audio-first."
- **Design Philosophy**: Minimalist, clean, modern, audio-focused

## Competitor Analysis Insights

- **Linktree**: Comprehensive feature set, social media integration, 70M+ users
- **Campsite.bio**: Clean organization, trusted by brands, professional aesthetic
- **Beacons.ai**: Strong monetization features, advanced analytics
- **Bio.link**: AI-powered features, high customization

## Implementation Plan

### Phase 1: Design System & CSS Foundation

1. **Create New Marketing CSS File**

   - File: `css/marketing-dark.css`
   - Dark theme color variables
   - Signal Green accent system
   - Typography (modern sans-serif, possibly Space Mono for body)
   - Component styles (buttons, cards, sections)
   - Responsive breakpoints

2. **Brand Color Variables**
   ```css
   --poda-bg-primary: #121212;
   --poda-bg-secondary: #1a1a1a;
   --poda-accent-signal-green: #00FF7F;
   --poda-text-primary: #FFFFFF;
   --poda-text-secondary: #8E8E93;
   --poda-border-subtle: rgba(255, 255, 255, 0.1);
   ```


### Phase 2: Homepage Redesign (index.php)

1. **Hero Section**

   - Dark background with subtle texture/gradient
   - Centered logo: poda.bio with Signal Green icon
   - Tagline: "The signal, clearer" (small, Signal Green)
   - Headline: "The link for listeners" (large, white, bold)
   - Subheadline: "A minimalist link-in-bio tool built for podcasters. Audio-first."
   - CTA Button: Signal Green background, white text, play icon
   - "See live examples" link in Signal Green
   - Phone mockup with glowing Signal Green outline (similar to image)

2. **Features Section**

   - Three-column grid: Audio-First, Minimalist Design, Clear Signals
   - Signal Green icons
   - White text on dark background
   - Minimalist card design

3. **Social Proof Section**

   - Trust indicators
   - User count/statistics
   - Signal Green accents

4. **Final CTA Section**

   - Dark background
   - Prominent Signal Green CTA button

### Phase 3: Features Page (features.php)

1. **Header**

   - Dark theme consistent with homepage
   - Signal Green accents

2. **Feature Grid**

   - Organized by category (Audio, Design, Analytics, etc.)
   - Signal Green icons and highlights
   - Dark cards with subtle borders

3. **Comparison Section**

   - Feature comparison table
   - Signal Green for highlights

### Phase 4: Pricing Page (pricing.php)

1. **Pricing Cards**

   - Dark cards with Signal Green accents
   - Signal Green CTA buttons
   - Clear hierarchy

2. **Feature Lists**

   - Signal Green checkmarks
   - Clean typography

### Phase 5: About Page (about.php)

1. **Content Sections**

   - Dark theme
   - Signal Green accents for emphasis
   - Clean typography

### Phase 6: Examples Page (examples.php)

1. **Example Showcase**

   - Dark theme
   - Live example previews
   - Signal Green accents

### Phase 7: Shared Components

1. **Header/Navigation**

   - Dark background
   - Signal Green logo
   - White navigation links
   - Signal Green hover states
   - Sticky header

2. **Footer**

   - Dark background
   - Signal Green links
   - Clean layout

3. **Buttons**

   - Primary: Signal Green background, white text
   - Secondary: Transparent with Signal Green border
   - Hover effects with Signal Green glow

4. **Forms**

   - Dark inputs with Signal Green focus states
   - Signal Green submit buttons

## Files to Create/Modify

### New Files

- `css/marketing-dark.css` - Complete dark theme stylesheet

### Files to Replace

- `index.php` - Homepage with new dark design
- `features.php` - Features page with dark theme
- `pricing.php` - Pricing page with dark theme
- `about.php` - About page with dark theme
- `examples.php` - Examples page with dark theme

### Files to Update

- `css/marketing.css` - Update or replace with dark theme version
- Header/footer includes (if separate) - Update to dark theme

## Design Principles

1. **Minimalism**: Clean, uncluttered layouts
2. **Audio-First**: Emphasize podcast/RSS feed features
3. **Signal Green**: Use sparingly but prominently for CTAs and accents
4. **Dark Theme**: Consistent #121212 background throughout
5. **Typography**: Modern, readable fonts with good contrast
6. **Mobile-First**: Responsive design for all screen sizes

## Technical Considerations

- Maintain PHP functionality (routing, includes, etc.)
- Preserve SEO meta tags and structure
- Ensure accessibility (WCAG contrast ratios)
- Optimize for performance (minimal CSS, efficient selectors)
- Cross-browser compatibility

## Success Metrics

- Visual consistency with brand image
- Dark theme throughout all marketing pages
- Signal Green used consistently for accents
- Mobile-responsive design
- Fast page load times
- Accessible color contrasts

### To-dos

- [ ] Create design system CSS file (marketing-dark.css) with dark theme variables, Signal Green accents, and component styles
- [ ] Redesign homepage hero section with dark theme, Signal Green accents, and phone mockup
- [ ] Redesign homepage features section with three-column grid (Audio-First, Minimalist Design, Clear Signals)
- [ ] Complete homepage redesign (index.php) with all sections, CTAs, and footer
- [ ] Create shared header and footer components with dark theme and Signal Green accents
- [ ] Redesign features page (features.php) with dark theme and organized feature grid
- [ ] Redesign pricing page (pricing.php) with dark theme pricing cards and Signal Green CTAs
- [ ] Redesign about page (about.php) with dark theme and Signal Green accents
- [ ] Redesign examples page (examples.php) with dark theme showcase
- [ ] Test and refine responsive design across all pages for mobile, tablet, and desktop