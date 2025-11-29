# Constants.php File Reference

**Last Updated**: 2025-01-XX  
**File Location**: `/config/constants.php`

## Overview

`constants.php` is the central configuration file for the PodaBio application. It defines all core application constants, paths, URLs, security settings, and platform configurations that are used throughout the entire PHP backend.

## Usage

This file is loaded by almost every PHP file in the application via:
```php
require_once __DIR__ . '/config/constants.php';
```

Since constants are defined using PHP's `define()` function, they can be accessed globally from any file that includes this file.

## Key Constants Defined

### Application Settings
- **`APP_NAME`**: Application name (`'Podn.Bio'`)
- **`APP_VERSION`**: Current application version (e.g., `'2.1.0'`)
- **`APP_URL`**: Base URL for the application (`'https://poda.bio'` in production)

### Server Configuration
- **`SERVER_IP`**: Hostinger server IP address

### File System Paths
- **`ROOT_PATH`**: Absolute path to application root directory
- **`PUBLIC_PATH`**: Path to public directory
- **`UPLOAD_PATH`**: Path to uploads directory
- **`ASSETS_PATH`**: Path to assets directory

### Upload Directories
- **`UPLOAD_PROFILES`**: Path for profile images
- **`UPLOAD_BACKGROUNDS`**: Path for background images
- **`UPLOAD_THUMBNAILS`**: Path for thumbnail images
- **`UPLOAD_BLOG`**: Path for blog images
- **`UPLOAD_MEDIA`**: Path for user media library (new in v2.1.0)

### URL Paths (Derived from APP_URL)
- **`PUBLIC_URL`**: Base public URL (equals `APP_URL`)
- **`UPLOAD_URL`**: URL for uploaded files (`APP_URL . '/uploads'`)
- **`ASSETS_URL`**: URL for static assets (`APP_URL . '/assets'`)

### Security Settings
- **`SESSION_LIFETIME`**: Session timeout in seconds (3600 = 1 hour)
- **`CSRF_TOKEN_EXPIRY`**: CSRF token expiration time
- **`VERIFICATION_TOKEN_EXPIRY`**: Email verification token expiration
- **`RESET_TOKEN_EXPIRY`**: Password reset token expiration

### File Upload Settings
- **`MAX_FILE_SIZE`**: Maximum file upload size (5MB = 5 * 1024 * 1024 bytes)
- **`ALLOWED_IMAGE_TYPES`**: Array of allowed MIME types
- **`ALLOWED_IMAGE_EXTENSIONS`**: Array of allowed file extensions

### Image Dimensions
- **`PROFILE_IMAGE_WIDTH/HEIGHT`**: Profile image dimensions (400x400)
- **`THUMBNAIL_WIDTH/HEIGHT`**: Thumbnail dimensions (400x400)
- **`BACKGROUND_IMAGE_MAX_WIDTH/HEIGHT`**: Background image max dimensions (1920x1080)

### Pagination
- **`ITEMS_PER_PAGE`**: Default items per page (20)
- **`EPISODES_PER_PAGE`**: Episodes per page (10)
- **`MEDIA_PER_PAGE`**: Media library items per page (24)

### Platform Lists
- **`SOCIAL_PLATFORMS`**: Array of supported social media platforms
- **`PODCAST_PLATFORMS`**: Array of podcast platforms
- **`EMAIL_SERVICES`**: Array of email service providers

### Subscription Plans
- **`PLAN_FREE`**: Free plan identifier
- **`PLAN_PREMIUM`**: Premium plan identifier
- **`PLAN_PRO`**: Pro plan identifier

### Theme Defaults
- **`THEME_DEFAULT_PRIMARY_COLOR`**: Default primary color
- **`THEME_DEFAULT_SECONDARY_COLOR`**: Default secondary color
- **`THEME_DEFAULT_ACCENT_COLOR`**: Default accent color
- **`THEME_DEFAULT_FONT`**: Default font family

## Local Development Override

The file automatically loads `config/local.php` if it exists (this file is gitignored). This allows you to override any constants for local development:

```php
// config/local.php (gitignored)
<?php
define('APP_URL', 'http://localhost:8080');
define('SERVER_IP', '127.0.0.1');
// Override any other constants as needed
```

## Important Notes

### APP_URL Usage

The `APP_URL` constant is critical because:

1. **Derived URLs**: `UPLOAD_URL`, `PUBLIC_URL`, and `ASSETS_URL` all derive from `APP_URL`
2. **URL Generation**: Used throughout the app to generate absolute URLs
3. **Image Normalization**: Used in `normalizeImageUrl()` to convert between local and production URLs
4. **OAuth Redirects**: Used in OAuth callback URLs
5. **Email Links**: Used in email verification and password reset links

### Production vs Development

- **Production**: `APP_URL` should be `'https://poda.bio'`
- **Local Development**: Override in `config/local.php` with your local URL (e.g., `'http://localhost:8080'`)

### Version Synchronization

When updating the version:
- Update `APP_VERSION` in this file
- Also update `/VERSION` file
- Also update `admin-ui/package.json` version
- See `docs/VERSIONING_STRATEGY.md` for details

## Files That Depend on constants.php

Almost every PHP file includes this file, but key dependencies include:

- **API Endpoints**: All files in `/api/` directory
- **Page Rendering**: `page.php`, `index.php`, etc.
- **Helper Functions**: `includes/helpers.php` uses many constants
- **Classes**: All PHP classes in `/classes/` directory
- **Authentication**: `includes/session.php`, `includes/auth.php`
- **Upload Handlers**: `classes/ImageHandler.php`, `classes/MediaLibrary.php`

## Change Log

- **2025-01-XX**: Updated `APP_URL` from `'https://getphily.com'` to `'https://poda.bio'` for production
- **v2.1.0**: Added `UPLOAD_MEDIA` constant for media library feature
- **v2.1.0**: Added `MEDIA_PER_PAGE` constant for media library pagination

