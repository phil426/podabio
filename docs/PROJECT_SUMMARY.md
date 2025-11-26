# PodaBio - Project Summary

**Version:** 2.0.1  
**Status:** Production Ready  
**Last Updated:** November 2024

---

## Overview

**PodaBio** is a comprehensive link-in-bio platform designed specifically for podcasters and content creators. It combines the simplicity of traditional link-in-bio tools with powerful podcast-specific features, including built-in RSS feed integration, native podcast player, episode management, and advanced analytics.

The platform enables creators to build beautiful, customizable pages that serve as a central hub for their podcast content, social media links, and audience engagement tools—all in one mobile-optimized destination.

---

## Technology Stack

### Backend
- **Language:** PHP 8.3+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Architecture:** Modular, class-based vanilla PHP
- **Server:** Apache with mod_rewrite (Nginx compatible)
- **Authentication:** Email/password with verification, Google OAuth 2.0
- **Payment Processing:** PayPal & Venmo integration

### Frontend
- **Admin Interface:** React 18.3 with TypeScript
- **Build Tool:** Vite 7.2
- **UI Libraries:** 
  - Radix UI (Tabs, Popover, Dialog, ScrollArea, Select, Slider, Tooltip)
  - Framer Motion (animations)
  - TanStack Query (data fetching)
  - React Colorful (color pickers)
- **State Management:** Zustand
- **Drag & Drop:** @dnd-kit
- **Icons:** Phosphor Icons, React Icons
- **Styling:** CSS Modules

### Infrastructure
- **Hosting:** Hostinger (poda.bio)
- **Version Control:** GitHub
- **SSL:** Lifetime SSL certificate
- **CDN:** Ready for integration

---

## Codebase Statistics

- **PHP Files:** 232 files
- **React/TypeScript Components:** 143 TSX files
- **CSS Files:** 104 stylesheets
- **JavaScript Files:** 11938 lines (including dependencies)
- **Database Migration Scripts:** 90+ theme and data scripts
- **API Endpoints:** 20+ RESTful endpoints
- **Widget Types:** 20+ customizable widget types

---

## Core Features

### 1. Page Management
- Dynamic username-based URLs (`poda.bio/username`)
- Custom domain support with DNS verification
- Responsive, mobile-first design
- Real-time preview with device presets (iPhone, Samsung, Pixel)

### 2. Podcast Integration
- **RSS Feed Parser:** Automatic import of podcast metadata, episodes, and cover art
- **Native Podcast Player:** Built-in web audio player with:
  - Episode playback controls (play, pause, skip forward/back)
  - Playback speed control (0.5x - 2.5x)
  - Sleep timer functionality
  - Show notes display
  - Chapter navigation
  - Social sharing
- **Episode Management:** Automatic sync with RSS feed, episode cards, and episode analytics

### 3. Theme System
- **49+ Professional Themes:** Pre-designed themes with unique color palettes, typography, and effects
- **Custom Theme Builder:** Full control over:
  - Color tokens (primary, secondary, accent)
  - Typography (15+ Google Fonts)
  - Page backgrounds (solid, gradient, image)
  - Widget styling (borders, shadows, glows)
  - Spatial effects (glass morphism, tilt, etc.)
- **Live Preview:** Real-time theme customization with instant visual feedback
- **Theme Library:** Browse, apply, clone, and customize themes

### 4. Widget System
- **20+ Widget Types:** Links, videos, text blocks, email forms, social embeds, e-commerce, and more
- **Drag & Drop Reordering:** Intuitive interface for arranging content
- **Featured Widgets:** Highlight important content with special effects
- **Widget Gallery:** Browse and add widgets with one click
- **Custom Configuration:** Each widget type has specific settings and options

### 5. Analytics Dashboard
- **Page Views:** Track total and unique visitors
- **Link Clicks:** Monitor click-through rates for all links
- **Episode Analytics:** Track episode plays and engagement
- **Time Period Selection:** View data by day, week, or month
- **Visual Charts:** Interactive graphs and data visualizations

### 6. Email Subscription
- **6 Email Service Providers:** Integration with Mailchimp, ConvertKit, AWeber, GetResponse, Constant Contact, and custom webhooks
- **Drawer Slider UI:** Non-intrusive subscription interface
- **Subscriber Management:** Track and manage email subscribers

### 7. Social Media Integration
- **Social Icons:** Support for 30+ platforms (Spotify, Apple Podcasts, YouTube, Instagram, Twitter, etc.)
- **Drag & Drop Reordering:** Arrange social links easily
- **Custom Platform Support:** Add any custom social link

### 8. Payment & Subscriptions
- **Payment Processors:** PayPal and Venmo
- **Subscription Management:** Multiple plan tiers with feature-based access
- **Webhook Handlers:** Automated subscription status updates
- **Checkout Flow:** Secure payment processing

### 9. Admin Interface ("Lefty")
- **React-Based SPA:** Modern, responsive admin experience
- **Tab-Based Navigation:** Organized interface with multiple panels
- **Real-Time Updates:** Live preview and instant save
- **Comprehensive Settings:** Account, page, theme, and subscription management

### 10. Marketing Website
- **Landing Page:** Professional homepage with feature highlights
- **Features Page:** Detailed feature showcase
- **Pricing Page:** Clear pricing tiers and plans
- **About Page:** Company information and values
- **Blog System:** Content management for company blog
- **Support Center:** Self-hosted knowledge base with search

---

## Security Features

- Password hashing with bcrypt
- CSRF protection on all forms
- SQL injection prevention (prepared statements)
- File upload validation and sanitization
- Rate limiting support
- Secure session management
- OAuth 2.0 secure authentication
- XSS protection

---

## Database Architecture

- **Modular Schema:** Well-organized tables for users, pages, themes, widgets, analytics, subscriptions
- **JSON Configuration:** Flexible widget and theme configuration storage
- **Optimized Queries:** Indexed tables for performance
- **Migration System:** Version-controlled database changes

---

## Development Workflow

1. **Local Development:** PHP built-in server or Apache
2. **Version Control:** GitHub (private repository)
3. **Deployment:** SSH-based deployment to Hostinger
4. **Database Sync:** Automated migration scripts
5. **Frontend Build:** Vite production builds for React admin

---

## Key Differentiators

1. **Podcast-First Design:** Built specifically for podcasters with native RSS integration
2. **Native Player:** No third-party embeds required—everything works seamlessly
3. **Advanced Theming:** 49+ themes with full customization capabilities
4. **Comprehensive Analytics:** Track everything from page views to episode plays
5. **Mobile-Optimized:** Perfect experience on all devices
6. **Self-Hosted:** Full control over data and customization

---

## Project Status

### ✅ Completed
- Full authentication system (email + OAuth)
- Page creation and management
- RSS feed integration
- Podcast player with full features
- Theme system (49+ themes)
- Widget system (20+ types)
- Analytics dashboard
- Email subscription integration
- Payment processing
- Custom domain support
- Admin interface (React SPA)
- Marketing website
- Support center / Knowledge base
- Blog system

### 🚧 Future Enhancements
- Additional widget types
- Enhanced analytics features
- Mobile app (iOS/Android)
- API for third-party integrations
- Advanced SEO features
- A/B testing capabilities

---

## Performance

- **Fast Page Loads:** Optimized CSS and JavaScript
- **Efficient Database Queries:** Indexed tables and optimized queries
- **Caching:** Ready for CDN integration
- **Image Optimization:** Automatic image handling and resizing
- **Mobile Performance:** Lightweight, responsive design

---

## License

Proprietary - All rights reserved

---

**For more information, see:**
- `README.md` - Setup and development guide
- `docs/` - Comprehensive documentation
- GitHub Repository: Private







