# Instagram Integration Setup Guide

Complete guide for setting up Instagram integration with PodaBio.

## Table of Contents

1. [Current Status (2025)](#current-status-2025)
2. [Integration Options](#integration-options)
3. [Instagram Graph API Setup](#instagram-graph-api-setup)
4. [Instagram oEmbed Setup](#instagram-oembed-setup)
5. [OAuth Configuration](#oauth-configuration)
6. [Troubleshooting](#troubleshooting)

---

## Current Status (2025)

### ⚠️ Critical Update

**Instagram Basic Display API was deprecated on December 4, 2024.**

This affects how you can connect Instagram accounts to PodaBio:
- ❌ Personal Instagram accounts **CANNOT** use OAuth connection anymore
- ❌ "Instagram Basic Display" product may not be available for new apps
- ✅ Need **Instagram Graph API** instead (requires Business/Creator accounts)

---

## Integration Options

### Option 1: Instagram Graph API (For Business/Creator Accounts)

**For users who have:**
- Instagram Business account
- Instagram Creator account
- Account linked to a Facebook Page

**What you can do:**
- Connect account via OAuth
- Display latest posts automatically
- Access user media
- Full API access

**Requirements:**
- Users need Business/Creator accounts (not personal)
- More complex setup
- Requires Meta Developer Console configuration

### Option 2: Instagram oEmbed (For ALL Accounts) ⭐ Recommended

**For users who have:**
- ANY Instagram account (personal, business, creator)
- Just want to show specific posts

**What you can do:**
- Embed individual Instagram posts
- User pastes post URL
- No OAuth needed
- Works immediately

**Best for:**
- Simple post embedding
- No automatic feed needed
- Quick implementation

---

## Recommended Approach for PodaBio

### Phase 1: Start Simple (Recommended)
- Use **oEmbed** (Option 2)
- Users paste Instagram post URLs
- Works for all account types
- No OAuth complexity

### Phase 2: Add Advanced Features
- Implement **Instagram Graph API** (Option 1)
- For users with Business/Creator accounts
- Automatic feed updates
- More complex but powerful

---

## Instagram Graph API Setup

### Prerequisites

- Meta/Facebook Developer Account
- Access to your Meta app dashboard
- Your PodaBio domain: `poda.bio`
- Local development URL: `http://localhost:8080`

### Step 1: Access Meta Developer Console

1. Go to: https://developers.facebook.com/
2. Log in with your Meta/Facebook account
3. Click **"My Apps"** in the top right corner

### Step 2: Create a New App or Select Existing

**Option A: Select Existing App**

1. Click on **"My Apps"** in the top right corner
2. From the dropdown menu, select your existing app (e.g., "PodaBio")
3. You'll be taken directly to the app dashboard
4. Skip to Step 3 to add Instagram Graph API

**Option B: Create New App**

1. Click **"Create App"** button
2. **App Type/Use Case:**
   - Look for **"Business"** (not Consumer)
   - OR **"Build connected experiences"**
   - OR **"Other"** / **"Business Management"**
   - Any option that allows business integrations
3. **App details:**
   - **App Name**: Enter `PodaBio` (or your preferred name, max 30 characters)
   - **App Contact Email**: Enter your email address (e.g., phil624@gmail.com)
4. Click **"Next"** and complete the wizard
5. Click **"Create App"** when done

### Step 3: Add Instagram Graph API Product

**Important:** Since Instagram Basic Display is deprecated, you need to use **Instagram Graph API** instead.

1. After creating/selecting your app, go to **"Add Products"** section
2. Look for **"Instagram Graph API"** (NOT Basic Display)
3. Click **"Set Up"**

**If you don't see Instagram Graph API:**
- It might be under **"Instagram"** product
- Or in **"Products"** section of dashboard
- Look for anything Instagram-related

### Step 4: Configure OAuth

**Instagram Graph API Settings:**

1. Navigate to **Instagram Graph API** → **Settings**
2. **Valid OAuth Redirect URIs:**
   - `http://localhost:8080/auth/instagram/callback.php` (for local development)
   - `https://poda.bio/auth/instagram/callback.php` (for production)
3. Click **"Save Changes"**

**Permissions:**
- `instagram_graph_user_profile`
- `instagram_graph_user_media`

### Step 5: Get App Credentials

1. Go to **Settings** → **Basic**
2. Copy your **App ID**
3. Copy your **App Secret**
4. Add to `config/meta.php`:

```php
<?php
// Instagram/Meta Configuration
define('INSTAGRAM_APP_ID', '738310402631107'); // Your App ID
define('INSTAGRAM_APP_SECRET', 'YOUR_APP_SECRET_HERE'); // Your App Secret
```

### Step 6: Verify App Configuration

Make sure your Instagram Graph API app is configured correctly:

1. **App Type**: Instagram Graph API (NOT Instagram Basic Display)
2. **Valid OAuth Redirect URIs**: Must include both localhost and production URLs
3. **App ID**: Should match what's in your config
4. **App Secret**: Should be set in `config/meta.php`

---

## Instagram oEmbed Setup

### What is oEmbed?

oEmbed is a simple way to embed Instagram posts without OAuth. Users paste Instagram post URLs, and your site embeds them.

### Implementation

**User Flow:**
1. User pastes Instagram post URL in widget
2. Your code calls: `https://graph.instagram.com/oembed?url={post_url}`
3. Display the embed code

**Example API Call:**

```php
$post_url = 'https://www.instagram.com/p/ABC123/';
$oembed_url = 'https://graph.instagram.com/oembed?url=' . urlencode($post_url);
$response = file_get_contents($oembed_url);
$data = json_decode($response, true);
// Use $data['html'] to display the embed
```

### Pros:
- ✅ Simple
- ✅ Works immediately
- ✅ No Meta app setup complexity
- ✅ Works for all accounts (personal, business, creator)

### Cons:
- ❌ Manual (user pastes URLs)
- ❌ Not automatic feed

---

## OAuth Configuration

### Fixed Issues ✅

1. **Function Redeclaration Errors**
   - Wrapped duplicate functions in `function_exists()` checks
   - Prevents PHP fatal errors

2. **500 Internal Server Error**
   - Added try-catch blocks around config loading
   - Returns proper JSON error responses

3. **Dynamic Redirect URI**
   - Now uses `getCurrentBaseUrl()` to automatically detect localhost vs production
   - Matches the pattern used for Google OAuth

### Required Action 🔧

### Add Redirect URIs to Facebook Developer Console

The "Invalid platform app" error occurs because the redirect URI in your OAuth URL doesn't match what's configured in Facebook Developer Console.

**Steps:**

1. Go to: https://developers.facebook.com/apps/YOUR_APP_ID
2. Navigate to: **Instagram Graph API** > **Settings**
3. In the **Valid OAuth Redirect URIs** section, add BOTH:
   - `http://localhost:8080/auth/instagram/callback.php` (for local development)
   - `https://poda.bio/auth/instagram/callback.php` (for production)
4. Click **Save Changes**

### Testing

After adding the redirect URIs:

1. Refresh the integrations page
2. Click "Connect Instagram Account"
3. You should be redirected to Instagram's OAuth page (not an error page)
4. Authorize the app
5. You should be redirected back to your app

---

## Troubleshooting

### "Invalid platform app" Error

**Cause:** Redirect URI doesn't match what's configured in Meta Developer Console.

**Solution:**
1. Go to Meta Developer Console → Instagram Graph API → Settings
2. Add both localhost and production URLs to Valid OAuth Redirect URIs
3. Save changes
4. Try again

### "Function redeclaration" Error

**Cause:** Functions are defined multiple times.

**Solution:** Already fixed - functions are wrapped in `function_exists()` checks.

### "500 Internal Server Error"

**Cause:** Config loading errors.

**Solution:** Already fixed - try-catch blocks added around config loading.

### Instagram Basic Display Not Available

**Cause:** Instagram Basic Display API was deprecated December 4, 2024.

**Solution:** Use Instagram Graph API instead (requires Business/Creator accounts).

### Users Can't Connect Personal Accounts

**Cause:** Instagram Graph API requires Business/Creator accounts.

**Solution:** 
- Use oEmbed for personal accounts (no OAuth needed)
- Or guide users to convert to Business/Creator account

---

## Which Should You Choose?

### Choose Instagram Graph API IF:
- ✅ Users have Business/Creator accounts
- ✅ You want automatic feed updates
- ✅ You want full OAuth integration
- ✅ You're okay with complexity

### Choose oEmbed IF:
- ✅ You want simple solution
- ✅ Users can paste URLs manually
- ✅ You want to launch quickly
- ✅ You want it to work for all accounts

---

## Recommendation

**For PodaBio right now:**
1. **Short-term:** Implement **oEmbed** (simple, works immediately)
2. **Long-term:** Add **Instagram Graph API** (for Business/Creator accounts)

This gives you:
- ✅ Quick launch with oEmbed
- ✅ Advanced features later with Graph API
- ✅ Works for all users (personal + business)

---

## Support

For Instagram/Meta-specific issues:
- Meta Developer Documentation: https://developers.facebook.com/docs/instagram
- Meta Support: https://developers.facebook.com/support

For PodaBio integration issues:
- Check server error logs
- Verify OAuth redirect URIs match
- Ensure App ID and Secret are correct

---

**Last Updated:** 2025-01-XX  
**Status:** Active Development

