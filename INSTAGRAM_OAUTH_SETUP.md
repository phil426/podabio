# Instagram OAuth Setup Guide

## Fixed Issues ✅

1. **Function Redeclaration Errors**
   - Wrapped duplicate functions in `function_exists()` checks
   - Prevents PHP fatal errors

2. **500 Internal Server Error**
   - Added try-catch blocks around config loading
   - Returns proper JSON error responses

3. **Dynamic Redirect URI**
   - Now uses `getCurrentBaseUrl()` to automatically detect localhost vs production
   - Matches the pattern used for Google OAuth

## Required Action 🔧

### Add Redirect URIs to Facebook Developer Console

The "Invalid platform app" error occurs because the redirect URI in your OAuth URL doesn't match what's configured in Facebook Developer Console.

**Steps:**

1. Go to: https://developers.facebook.com/apps/738310402631107
2. Navigate to: **Instagram Basic Display** > **Settings**
3. In the **Valid OAuth Redirect URIs** section, add BOTH:
   - `http://localhost:8080/auth/instagram/callback.php` (for local development)
   - `https://poda.bio/auth/instagram/callback.php` (for production)
4. Click **Save Changes**

### Verify App Configuration

Make sure your Instagram Basic Display app is configured correctly:

1. **App Type**: Instagram Basic Display API (NOT Instagram Graph API)
2. **Valid OAuth Redirect URIs**: Must include both localhost and production URLs
3. **App ID**: 738310402631107
4. **App Secret**: Should be set in `config/meta.php`

## Testing

After adding the redirect URIs:

1. Refresh the integrations page
2. Click "Connect Instagram Account"
3. You should be redirected to Instagram's OAuth page (not an error page)
4. Authorize the app
5. You should be redirected back to your app

## Troubleshooting

### Still seeing "Invalid platform app"?
- Verify redirect URIs match exactly (including http vs https, trailing slashes)
- Check that the app is using Instagram Basic Display (not Instagram Graph API)
- Make sure the redirect URI in the code matches what's in Facebook Console

### Getting 500 errors?
- Check PHP error logs
- Verify `INSTAGRAM_APP_ID` and `INSTAGRAM_APP_SECRET` are set in `config/meta.php`
- Ensure all functions are properly loaded

### OAuth URL generation issues?
- The redirect URI now automatically detects localhost vs production
- If you need to override, check `config/meta.php` function `getInstagramAuthUrl()`
