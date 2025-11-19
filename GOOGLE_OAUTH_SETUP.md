# Google OAuth Setup - PodaBio

## Developer Account Credentials

**Account Email:** phil@redwoodempiremedia.com  
**Account Password:** [Stored securely - not in version control]

**Purpose:** Google Developer Account with AI Agentic Features for implementing and testing PodaBio features

## Setup Instructions

### 1. Access Google Cloud Console

1. Go to: https://console.cloud.google.com/
2. Sign in with: phil@redwoodempiremedia.com
3. Use password provided for account access

### 2. Create/Select Project

1. Create new project: "PodaBio" (or use existing)
2. Note the Project ID for reference

### 3. Enable APIs

1. Navigate to "APIs & Services" > "Library"
2. Enable the following APIs:
   - Google+ API (for OAuth user info)
   - People API (for profile data)

### 4. Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. If prompted, configure OAuth consent screen first:
   - User Type: External
   - App name: PodaBio
   - User support email: phil@redwoodempiremedia.com
   - Developer contact: phil@redwoodempiremedia.com
4. Create OAuth client ID:
   - Application type: Web application
   - Name: PodaBio Web Client
   - Authorized redirect URIs:
     - Development: `https://getphily.com/auth/google/callback.php`
     - Production: `https://poda.bio/auth/google/callback.php`

### 5. Save Credentials

1. Copy the Client ID
2. Copy the Client Secret
3. Update `config/oauth.php` with these values:
   ```php
   define('GOOGLE_CLIENT_ID', 'your-client-id.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
   ```

## OAuth Consent Screen Configuration

### Required Scopes

- `openid` - Required for OpenID Connect
- `email` - Access to user's email address
- `profile` - Access to user's profile information

### Testing Users

For testing, you can add test users in OAuth consent screen:
- test.serial@poda.bio
- test.radiolab@poda.bio
- cursor@poda.bio
- Or use any email for testing

## Security Notes

⚠️ **IMPORTANT:**
- Never commit `GOOGLE_CLIENT_SECRET` to version control
- Keep credentials in `config/oauth.php` (already in .gitignore)
- Use environment variables in production (future enhancement)
- Rotate credentials if compromised
- Monitor OAuth usage in Google Cloud Console

## Testing OAuth Flow

1. Visit: `https://getphily.com/login.php`
2. Click "Sign in with Google"
3. Should redirect to Google authentication
4. After approval, redirect back to PodaBio Studio (`/admin/userdashboard.php`)
5. User account should be created/linked automatically

## Troubleshooting

### Common Issues:

**"Redirect URI mismatch"**
- Verify redirect URI exactly matches in Google Console
- Check for trailing slashes
- Ensure HTTPS (not HTTP)

**"Invalid client"**
- Verify Client ID is correct
- Check that OAuth consent screen is published (or add test users)

**"Access blocked"**
- Check OAuth consent screen is configured
- Verify scopes are requested correctly

## Production Migration

When moving to poda.bio:
1. Update redirect URI in Google Console
2. Update `APP_URL` in `config/constants.php`
3. Update `GOOGLE_REDIRECT_URI` in `config/oauth.php`
4. Test OAuth flow on production domain


