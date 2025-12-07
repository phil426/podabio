# Instagram Basic Display API Setup Guide for PodaBio

This guide will walk you through creating and configuring an Instagram app on Meta Developer Console for PodaBio integration.

## Prerequisites

- Meta/Facebook Developer Account
- Access to your Meta app dashboard
- Your PodaBio domain: `poda.bio`
- Local development URL: `http://localhost:8080`

---

## Step 1: Access Meta Developer Console

1. Go to: https://developers.facebook.com/
2. Log in with your Meta/Facebook account
3. Click **"My Apps"** in the top right corner

---

## Step 2: Create a New App or Select Existing

### Option A: Select Existing App

1. Click on **"My Apps"** in the top right corner
2. From the dropdown menu, select your existing app (e.g., "PodaBio")
3. You'll be taken directly to the app dashboard
4. Skip to Step 3 to add Instagram Basic Display

### Option B: Create New App

1. Click **"Create App"** button
2. You'll see a multi-step wizard. The first step is **"App details"**:
   - **App Name**: Enter `PodaBio` (or your preferred name, max 30 characters)
   - **App Contact Email**: Enter your email address (e.g., phil624@gmail.com)
3. Click **"Next"** button (blue button at bottom right)
4. Continue through the wizard:
   - **Use cases**: Select what your app will do
   - **Business**: Link business account (optional)
   - **Requirements**: Review and accept
   - **Overview**: Final review
5. Click **"Create App"** when done

---

## Step 3: Add Instagram Basic Display Product

**Important:** The Meta Developer Console interface shows **"Use Cases"** first. Use Cases are different from Products. You need to get to the Products section to add Instagram Basic Display.

### Navigating to Products:

1. After creating or selecting your app, you may see a **"Use Cases"** page or section
   - This is asking what your app will do (e.g., "Build", "Connect", "Play")
   - **You can skip this or select any relevant use cases**

2. To get to Products, look for one of these:
   - **Left sidebar menu**: Look for **"Products"** or **"Add Product"** link
   - **Dashboard**: Scroll down or look for **"Add Products to Your App"** section
   - **Navigation bar**: Click **"Products"** if visible in the top navigation

3. Once you're in the **Products** section, you'll see a list of available Meta products:
   - Facebook Login
   - Instagram Basic Display ← **This is what you need!**
   - Instagram Graph API
   - Messenger
   - And others...

4. Find **"Instagram Basic Display"** in the products list

5. Click **"Set Up"** or **"Get Started"** button next to Instagram Basic Display

6. You'll be taken to the Instagram Basic Display configuration page

**Tip:** If you're stuck on the Use Cases page, try clicking through it or look for a "Skip" option, then navigate to Products using the left sidebar menu.

---

## Step 4: Configure Instagram Basic Display Settings

1. Click on **"Instagram Basic Display"** in the left sidebar (under Products)
2. Click on **"Settings"** (or **"Basic Display"** > **"Settings"**)

### 4a. Add Valid OAuth Redirect URIs

In the **"Valid OAuth Redirect URIs"** section, add the following URIs:

**For Local Development:**
```
http://localhost:8080/auth/instagram/callback.php
```

**For Production:**
```
https://poda.bio/auth/instagram/callback.php
```

**Important:**
- Add each URI on a separate line
- Must match EXACTLY (including http vs https, port numbers, trailing slashes)
- Click **"Add URI"** or press Enter after each one

### 4b. Add Deauthorize Callback URL (Optional but Recommended)

Add your production domain:
```
https://poda.bio/auth/instagram/deauthorize.php
```

### 4c. Add Data Deletion Request Callback URL (Optional)

Add your production domain:
```
https://poda.bio/auth/instagram/data-deletion.php
```

### 4d. Save Settings

- Scroll down and click **"Save Changes"**
- Wait for confirmation that settings have been saved

---

## Step 5: Get Your App Credentials

### 5a. Get App ID and App Secret

1. In the left sidebar, click **"Settings"** > **"Basic"**
2. You'll see:
   - **App ID**: A long number (e.g., `1234567890123456`)
   - **App Secret**: Click **"Show"** to reveal it (you may need to enter your password)

**⚠️ IMPORTANT:** Copy both values - you'll need them next!

### 5b. Verify App Type

Make sure you're using **Instagram Basic Display** API (NOT Instagram Graph API):
- Instagram Basic Display: For accessing a user's own Instagram account
- Instagram Graph API: For managing business Instagram accounts (different use case)

---

## Step 6: Configure Your PodaBio Application

Now you need to add these credentials to your PodaBio configuration.

### 6a. Open Config File

Open: `config/meta.php`

### 6b. Update Facebook App Credentials

Find or add these lines near the top of the file:

```php
// Facebook App ID (from Meta Developer Console)
define('FACEBOOK_APP_ID', 'YOUR_APP_ID_HERE');

// Facebook App Secret (from Meta Developer Console)
define('FACEBOOK_APP_SECRET', 'YOUR_APP_SECRET_HERE');
```

Replace:
- `YOUR_APP_ID_HERE` with your App ID from Step 5a
- `YOUR_APP_SECRET_HERE` with your App Secret from Step 5a

**Note:** The Instagram App ID and Secret will automatically use the same values:
```php
define('INSTAGRAM_APP_ID', FACEBOOK_APP_ID);
define('INSTAGRAM_APP_SECRET', FACEBOOK_APP_SECRET);
```

---

## Step 7: Create Test Users (Important for Testing)

1. In Meta Developer Console, go to **"Instagram Basic Display"** > **"User Token Generator"**
2. Click **"Add or Remove Instagram Testers"**
3. Click **"Add Instagram Testers"**
4. Enter Instagram usernames of accounts you want to use for testing
5. Each tester needs to:
   - Accept the invitation in their Instagram app
   - Go to Settings > Apps and Websites > Tester Invites
   - Accept your app

---

## Step 8: Test the Integration

1. Make sure your PHP dev server is running: `php -S localhost:8080 router.php`
2. Log into PodaBio admin panel
3. Navigate to **Integrations** tab
4. Click on the **Instagram** card
5. Click **"Connect Instagram Account"**
6. You should be redirected to Instagram's authorization page
7. Authorize the app
8. You should be redirected back to PodaBio

---

## Troubleshooting

### "Invalid platform app" Error

**Cause:** Redirect URI doesn't match Facebook Developer Console

**Solution:**
1. Verify redirect URIs in Meta Console match exactly:
   - Check for `http://` vs `https://`
   - Check for port numbers (`:8080`)
   - Check for trailing slashes
2. Clear browser cache and cookies
3. Try again

### "App Not Setup" Error

**Cause:** Instagram Basic Display product not added to app

**Solution:**
1. Go back to Meta Developer Console
2. Make sure "Instagram Basic Display" product is added
3. Check that settings are saved

### Redirect URI Mismatch

**Cause:** Redirect URI in code doesn't match Meta Console

**Solution:**
1. Check what redirect URI is being generated:
   - Open browser DevTools Console
   - Look for the OAuth URL being generated
   - Verify it matches Meta Console exactly
2. Update Meta Console if needed

### Can't Generate User Token

**Cause:** App is in Development Mode and needs test users

**Solution:**
1. Add Instagram testers (Step 7)
2. Have testers accept invitation in Instagram app
3. Then generate tokens

---

## Security Notes

⚠️ **IMPORTANT:**
- Never commit your App Secret to version control
- Keep `config/meta.php` in `.gitignore` (it should already be)
- Rotate secrets if they're exposed
- Use environment variables in production (future enhancement)

---

## Current Configuration Check

After setup, verify your configuration:

**App ID:** Check `config/meta.php` → `FACEBOOK_APP_ID`  
**App Secret:** Check `config/meta.php` → `FACEBOOK_APP_SECRET`  
**Redirect URIs:** Should match Meta Console exactly  
**Product:** Must be "Instagram Basic Display" (not Graph API)

---

## Next Steps After Setup

1. Test the OAuth flow in development
2. Add production redirect URI to Meta Console
3. Test in production
4. Set up Instagram carousel widget to display posts
5. Configure token refresh (tokens expire after 60 days)

---

## Support Resources

- [Instagram Basic Display API Docs](https://developers.facebook.com/docs/instagram-basic-display-api)
- [Meta Developer Console](https://developers.facebook.com/apps/)
- [OAuth Troubleshooting](https://developers.facebook.com/docs/instagram-basic-display-api/overview#instagram-app-setup)

---

## Quick Reference Checklist

- [ ] Created/selected app in Meta Developer Console
- [ ] Added "Instagram Basic Display" product
- [ ] Added redirect URIs (localhost and production)
- [ ] Copied App ID
- [ ] Copied App Secret
- [ ] Added credentials to `config/meta.php`
- [ ] Added test users (for development mode)
- [ ] Tested OAuth flow
- [ ] Verified integration works

---

*Last updated: January 2025*

