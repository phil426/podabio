# Instagram OAuth Setup - Step-by-Step Walkthrough (2025)

This guide walks you through setting up Instagram Basic Display API for PodaBio using the **current Meta Developer Console interface**.

---

## Prerequisites

- Meta/Facebook Developer Account
- Access to https://developers.facebook.com/
- Your app ready (or we'll create one)

---

## Step 1: Access Meta Developer Console

1. **Go to:** https://developers.facebook.com/
2. **Log in** with your Meta/Facebook account
3. Click **"My Apps"** in the top right corner

---

## Step 2: Create or Select Your App

### If Creating a New App:

1. Click **"Create App"** button
2. You'll see a 5-step wizard:

#### Step 2.1: App Details
- **App Name**: Enter `PodaBio` (max 30 characters)
- **App Contact Email**: Your email (may be pre-filled)
- Click **"Next"** (blue button at bottom right)

#### Step 2.2: Use Cases
- **For PodaBio, select:** "Consumer" (if available) OR "Build connected experiences"
- These are best for link-in-bio tools that display user content
- You can select multiple or just one
- Click **"Next"**

#### Step 2.3: Business Account (Optional)
- Link a business account if you have one
- Or skip if not needed
- Click **"Next"**

#### Step 2.4: Requirements
- Review terms and requirements
- Check any required boxes
- Click **"Next"**

#### Step 2.5: Overview
- Review your app configuration
- Click **"Create App"** when ready

### If Using Existing App:

1. Click **"My Apps"** dropdown
2. Select your existing app (e.g., "PodaBio")
3. You'll go directly to the app dashboard

---

## Step 3: Navigate to Products Section

**Important:** After creating/selecting your app, you may see different views:

### Option A: You see "Use Cases" page
- This is asking what your app will do
- **Either:** Select use cases and continue, OR
- **Or:** Look for left sidebar menu → Click **"Products"**

### Option B: You're on the Dashboard
- Look for left sidebar menu
- Find **"Products"** or **"Add Product"** link
- Click it

### Option C: You see "Add Products" section
- Look for cards/tiles showing available products
- Scroll to find **"Instagram Basic Display"**

**The key:** You need to get to the **Products** section, not Use Cases. Products are what you add to your app.

---

## Step 4: Add Instagram Basic Display Product

1. In the **Products** section, look for **"Instagram Basic Display"**
   - It may be in a list or as a card/tile
   - Should show with other Meta products (Facebook Login, Instagram Graph API, etc.)

2. Click **"Set Up"** or **"Get Started"** button next to Instagram Basic Display

3. You'll be taken to the Instagram Basic Display configuration page

**If you don't see Instagram Basic Display:**
- Make sure you're in Products, not Use Cases
- Try refreshing the page
- Check that your app type supports it

---

## Step 5: Configure Instagram Basic Display Settings

1. In the left sidebar, make sure you're in:
   - **Instagram Basic Display** → **Settings**
   - OR **Basic Display** → **Settings**

2. You'll see configuration options

---

## Step 6: Add Valid OAuth Redirect URIs

This is the **MOST CRITICAL STEP** - get this wrong and OAuth won't work!

1. Scroll down to **"Valid OAuth Redirect URIs"** section
   - May also be called "Authorized Redirect URIs" or "Redirect URIs"

2. Click **"Add URI"** or the input field

3. Add **BOTH** of these URIs (one at a time):

   **For Local Development:**
   ```
   http://localhost:8080/auth/instagram/callback.php
   ```

   **For Production:**
   ```
   https://poda.bio/auth/instagram/callback.php
   ```

4. **CRITICAL REQUIREMENTS:**
   - Must match EXACTLY (including http vs https)
   - Must include port number for localhost (`:8080`)
   - No trailing slashes
   - Each URI on separate line

5. After adding each URI, click **"Add"** or press Enter

6. Verify both URIs appear in the list

---

## Step 7: Add Optional Callback URLs

While you're in Settings, you can also add:

### Deauthorize Callback URL (Optional but Recommended)
```
https://poda.bio/auth/instagram/deauthorize.php
```

### Data Deletion Request Callback URL (Optional)
```
https://poda.bio/auth/instagram/data-deletion.php
```

---

## Step 8: Save Settings

1. Scroll down to the bottom of the Settings page
2. Click **"Save Changes"** button
3. Wait for confirmation message
4. Settings are now saved

---

## Step 9: Get Your App Credentials

1. In the left sidebar, click **"Settings"** → **"Basic"**
   - This is the main app settings, not Instagram-specific settings

2. You'll see:

### App ID
- A long number (e.g., `738310402631107`)
- **Copy this number** - you'll need it

### App Secret
- Click the **"Show"** button next to App Secret
- You may need to enter your Facebook password
- **Copy this value** - you'll need it

---

## Step 10: Verify App Configuration

Check that:
- ✅ Instagram Basic Display product is added
- ✅ Redirect URIs are saved
- ✅ App ID is copied
- ✅ App Secret is copied

---

## Step 11: Update PodaBio Configuration

1. Open: `config/meta.php`

2. Update these values (they may already be set):

```php
// Facebook App ID (from Step 9)
define('FACEBOOK_APP_ID', 'YOUR_APP_ID_HERE');

// Facebook App Secret (from Step 9)
define('FACEBOOK_APP_SECRET', 'YOUR_APP_SECRET_HERE');
```

Replace:
- `YOUR_APP_ID_HERE` with your App ID from Step 9
- `YOUR_APP_SECRET_HERE` with your App Secret from Step 9

**Note:** Instagram credentials automatically use the same values:
```php
define('INSTAGRAM_APP_ID', FACEBOOK_APP_ID);
define('INSTAGRAM_APP_SECRET', FACEBOOK_APP_SECRET);
```

---

## Step 12: Create Test Users (For Development Mode)

If your app is in Development Mode:

1. In left sidebar, go to **"Instagram Basic Display"** → **"User Token Generator"**
   - OR **"Basic Display"** → **"Roles"**

2. Click **"Add or Remove Instagram Testers"**

3. Click **"Add Instagram Testers"**

4. Enter Instagram usernames of accounts you want to test with

5. Each tester needs to:
   - Check their Instagram app
   - Go to **Settings** → **Apps and Websites** → **Tester Invites**
   - Accept your app invitation

---

## Step 13: Test the Integration

1. **Start your dev server:**
   ```bash
   php -S localhost:8080 router.php
   ```

2. **Log into PodaBio admin panel**

3. **Navigate to Integrations tab**

4. **Click on the Instagram card**

5. **Click "Connect Instagram Account"**

6. **You should:**
   - Be redirected to Instagram's authorization page
   - See a login prompt (if not logged in)
   - See permission request for your app
   - Authorize the app

7. **After authorizing:**
   - You'll be redirected back to PodaBio
   - Should see "Connected" status

---

## Troubleshooting

### "Invalid platform app" Error

**Cause:** Redirect URI doesn't match what's configured

**Solution:**
1. Go back to Step 6
2. Verify redirect URIs match EXACTLY:
   - Check for `http://` vs `https://`
   - Check for port numbers (`:8080`)
   - Check for trailing slashes (shouldn't be any)
3. Clear browser cache
4. Try again

### Can't Find Instagram Basic Display

**Cause:** Not in Products section, or app type doesn't support it

**Solution:**
1. Make sure you're looking at Products, not Use Cases
2. Use left sidebar navigation → Products
3. If still not there, you may need to create a Consumer app type

### Redirect URI Mismatch

**Cause:** URI in code doesn't match Meta Console

**Solution:**
1. Check what redirect URI your code is generating
2. Look in browser DevTools → Network tab when clicking Connect
3. See the full OAuth URL
4. Extract the redirect_uri parameter
5. Make sure it exactly matches Meta Console

---

## Current Status Checklist

- [ ] App created/selected in Meta Developer Console
- [ ] Instagram Basic Display product added
- [ ] Redirect URIs configured (localhost + production)
- [ ] Settings saved
- [ ] App ID copied
- [ ] App Secret copied
- [ ] Credentials added to `config/meta.php`
- [ ] Test users added (if in Development Mode)
- [ ] OAuth flow tested

---

## Next Steps After Setup

1. ✅ Test OAuth flow works
2. ✅ Add Instagram carousel/grid widgets
3. ✅ Set up token refresh (tokens expire after 60 days)
4. ✅ Test in production
5. ✅ Monitor token expiration and refresh logic

---

## Visual Guide Notes

Since you're already in the process:
- You're currently at: **App Details** step (Step 2.1)
- Next: Click **"Next"** → **Use Cases** (Step 2.2)
- Then: Continue through wizard
- Finally: Get to **Products** section

---

*Last updated: January 2025*
*Based on current Meta Developer Console interface*

