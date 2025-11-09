# Podn.Bio - Link-in-Bio Platform for Podcasters

A comprehensive link-in-bio platform designed for all content creators, with special focus on podcasters.

## Project Status

### ✅ Completed Features

1. **Database Schema** - Complete MySQL schema with all required tables
2. **Authentication System** - Email/password signup with verification, Google OAuth 2.0, password reset
3. **Google Account Linking** - Link/unlink Google accounts with password verification, account management UI
4. **RSS Feed Parser** - Parse podcast RSS feeds to extract name, description, cover image, and episodes
5. **Page Display System** - Dynamic username-based pages with responsive design
6. **Analytics Tracking** - Page views, link clicks, and email subscription tracking
7. **Basic Classes** - User, Page, Analytics, Subscription, ImageHandler, RSSParser
8. **User Dashboard** - Basic dashboard with account management (login methods, linking/unlinking)
9. **Page Editor** - Full-featured page editor with drag-and-drop link reordering, link management, RSS feed import, image uploads (profile/background), podcast directory management, email subscription configuration, and settings/appearance customization
10. **Shikwasa.js Podcast Player** - Integrated web audio player for podcast episodes with theme support
11. **Email Subscription System** - Drawer slider for email subscriptions with integration for 6 major email service providers
12. **Enhanced Theme System** - Full color pickers (primary, secondary, accent) with swatches, font selectors (15 Google Fonts), live preview, and theme auto-population
13. **Custom Domain Support** - Custom domain configuration with DNS verification, domain validation, and automatic routing
14. **Payment Integration** - PayPal and Venmo payment processing, subscription management, checkout pages, webhook handlers, and plan-based feature access
15. **Marketing Website** - Professional landing page, features page, pricing page, and about page with responsive design
16. **Admin Panel** - Comprehensive admin dashboard with user management, page management, subscription management, analytics, and system settings
17. **Knowledge Base** - Self-hosted support center with articles, categories, search, and admin management
18. **Company Blog** - Blog system with posts, categories, pagination, and admin management

### 🚧 In Progress / To Be Implemented
- Additional feature enhancements and optimizations

## Setup Instructions

### 1. Database Setup

**Via SSH:**
```bash
# Connect via SSH
# Password: [Set/Change via Hostinger Control Panel - SSH Access section]
ssh -p 65002 u810635266@82.198.236.40

# Navigate to project directory (root is /public_html/, no podnbio subdirectory)
cd /home/u810635266/domains/getphily.com/public_html/

# Connect to MySQL
mysql -h srv556.hstgr.io -u u810635266_podnbio -p u810635266_site_podnbio

# Import schema (from project directory)
mysql -h srv556.hstgr.io -u u810635266_podnbio -p u810635266_site_podnbio < database/schema.sql

# Import seed data
mysql -h srv556.hstgr.io -u u810635266_podnbio -p u810635266_site_podnbio < database/seed_data.sql
```

**Via phpMyAdmin:**
- Access via Hostinger control panel
- Use database credentials from config

**Load Test Accounts (Optional):**
```bash
# Import test accounts for development/testing
mysql -h srv556.hstgr.io -u u810635266_podnbio -p u810635266_site_podnbio < database/test_accounts.sql
```
- See `TEST_ACCOUNTS.md` for test account credentials

### 2. Configuration

1. Edit `config/database.php` with your database credentials
2. Edit `config/constants.php` and set `APP_URL` to your domain
3. Edit `config/oauth.php` and add your Google OAuth credentials:
   - Get credentials from [Google Cloud Console](https://console.cloud.google.com/)
   - Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`

### 3. Directory Permissions

**Via SSH:**
```bash
# Connect via SSH
# Password: [Set/Change via Hostinger Control Panel - SSH Access section]
ssh -p 65002 u810635266@82.198.236.40

# Navigate to project directory (root is /public_html/, no podnbio subdirectory)
cd /home/u810635266/domains/getphily.com/public_html/

# Set permissions
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/backgrounds/
chmod 755 uploads/thumbnails/
chmod 755 uploads/blog/
```

### 4. Web Server Configuration

#### Apache (.htaccess included)

The `.htaccess` file is included and should work with Apache mod_rewrite enabled.

#### Nginx

Add to your server block:

```nginx
location / {
    try_files $uri $uri/ /public/page.php?username=$1;
}

location ~ ^/(index|features|pricing|about|blog|signup|login) {
    try_files $uri /public/$1.php;
}
```

## Local → GitHub → Hostinger Workflow

1. **Sync from Hostinger (one-time baseline)**
   - `ssh -p 65002 u810635266@82.198.236.40`
   - Archive the live site: `cd /home/u810635266/domains/getphily.com && tar czf ~/podnbio_files_$(date +%Y%m%d%H%M%S).tar.gz public_html`
   - Dump the DB: `mysqldump -u u810635266_podnbio -p'6;hhwddG' u810635266_site_podnbio > ~/podnbio_db_$(date +%Y%m%d%H%M%S).sql`
   - `scp` both files to your workstation and extract/import into `~/.cursor/podinbio`
2. **Develop locally**
   - Update `config/database.php` for `127.0.0.1` and `podnbio_dev`
   - Set `APP_URL` to `http://127.0.0.1:8080`
   - Run Apache (or `php -S 127.0.0.1:8080 router.php`) and work against the mirrored data
   - Commit changes to git (keep secrets out by ignoring `config/database.php`)
3. **Push to GitHub (source of truth)**
   - `git push origin main` (or PR branches)
   - GitHub now reflects the live snapshot + your changes
4. **Deploy to Hostinger**
   - SSH in and `git pull origin main`
   - Run DB migrations as needed (`php database/migrate_xyz.php`)
   - Clear caches/logs if applicable
5. **Promote to new `podn.bio` host**
   - Provision new Hostinger account pointing at the same repo
   - Clone from GitHub, copy `.env`/config secrets
   - Import the latest DB dump
   - Update DNS when ready to switch domains

## File Structure

```
/
├── config/          # Configuration files
├── classes/         # PHP classes (User, Page, etc.)
├── includes/        # Helper functions and utilities
├── api/             # Third-party API integrations
├── assets/          # CSS, JS, images
├── uploads/         # User uploaded files
├── public/          # Public-facing pages
├── support/         # Knowledge base
├── admin/           # Admin panel
├── database/        # SQL schema and seeds
└── auth/            # OAuth callbacks
```

## Development

- **PHP Version**: 8.0+ (Recommended: 8.3)
- **MySQL Version**: 5.7+ / MariaDB 10.3+
- **Modular Architecture**: Vanilla PHP with class-based structure
- **PHP Configuration**: See `PHP_CONFIGURATION.md` for detailed recommendations

## Security Features

- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention (prepared statements)
- File upload validation
- Rate limiting support
- Session security

## Next Steps

1. ✅ User dashboard (basic version with account management - completed)
2. ✅ Page editor with drag-and-drop (completed)
3. ✅ Link management (completed - full CRUD with drag-and-drop)
4. Enhanced theme customization with color/font pickers
5. Image uploads UI for profile/background images
6. Podcast directory links management
7. Email subscription integration
8. Episode drawer slider
9. Admin panel
10. Marketing website
11. Payment processing integration

## License

Proprietary - All rights reserved

