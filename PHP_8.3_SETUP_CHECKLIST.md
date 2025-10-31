# PHP 8.3 Configuration Checklist - Podn.Bio

**Status:** After upgrade to PHP 8.3, configuration reset to defaults  
**Action Required:** Configure settings in Hostinger Control Panel

## 🔴 CRITICAL - Must Enable (Application Won't Work Without These)

### PHP Extensions Tab

Verify these extensions are **ENABLED** (checked):

**Required Extensions:**
- ✅ **gd** - Image processing (ImageHandler)
- ✅ **mysqli** - Database connectivity
- ✅ **pdo_mysql** - PDO MySQL driver
- ✅ **simplexml** - RSS feed parsing (CRITICAL)
- ✅ **json** - API responses
- ✅ **curl** - API integrations (TikTok, Instagram, email services)
- ✅ **openssl** - Security/HTTPS
- ✅ **mbstring** - String handling
- ✅ **session** - User sessions
- ✅ **filter** - Input validation
- ✅ **hash** - Password hashing

**Recommended Extensions:**
- ✅ **imagick** - Advanced image processing
- ✅ **opcache** - Performance (CRITICAL for speed)
- ✅ **dom** - XML/DOM parsing
- ✅ **xml** - XML parsing
- ✅ **zip** - Future features

## 🟡 REQUIRED - Must Configure (PHP Options Tab)

### Enable These Options (Check/Enable):

1. ✅ **allowUrlFopen** - **MUST ENABLE**
   - **Why:** Required for RSS feed parsing (`file_get_contents()` for RSS URLs)
   - **Impact:** RSS feed feature will NOT work without this
   - **Security Note:** Safe to enable, we use `allow_url_include = Off` separately

2. ✅ **fileUploads** - **MUST ENABLE**
   - **Why:** Required for image uploads (profile, background, thumbnails)
   - **Impact:** Image upload feature will NOT work without this

3. ✅ **opcache.enable** - **MUST ENABLE**
   - **Why:** Performance optimization (caches compiled PHP code)
   - **Impact:** Application will be significantly slower without this

4. ✅ **opcache.enableCli** - **RECOMMENDED**
   - **Why:** Command-line performance optimization
   - **Impact:** Faster CLI operations (cron jobs, etc.)

5. ✅ **session.useStrictMode** - **SECURITY: ENABLE**
   - **Why:** Prevents session fixation attacks
   - **Impact:** Better security for user sessions

6. ✅ **session.cookieHttponly** - **SECURITY: ENABLE**
   - **Why:** Prevents JavaScript access to session cookies (XSS protection)
   - **Impact:** Better security for user sessions

### Disable These Options (Uncheck/Disable):

1. ❌ **displayErrors** - **MUST DISABLE**
   - **Why:** Security - don't expose errors to users
   - **Impact:** Errors won't show to visitors (good for production)

2. ❌ **exposePhp** - **MUST DISABLE**
   - **Why:** Security - don't expose PHP version in headers
   - **Impact:** Hides PHP version from HTTP headers

3. ❌ **shortOpenTag** - **RECOMMENDED: DISABLE**
   - **Why:** Best practice - use full `<?php` tags
   - **Impact:** Ensures code compatibility

4. ❌ **allowUrlInclude** - **MUST DISABLE** (if available)
   - **Why:** Security - prevents remote file inclusion attacks
   - **Impact:** Better security (we don't use this feature)

### Optional Settings:

1. ⚠️ **logErrors** - **RECOMMENDED: ENABLE for debugging**
   - **Why:** Log errors to file for debugging
   - **Impact:** Better error tracking during development
   - **Note:** Can disable in production if not needed

2. ⚠️ **session.cookieSecure** - **Enable ONLY with HTTPS**
   - **Why:** Security - sends cookies only over HTTPS
   - **Impact:** Enable when SSL/HTTPS is configured
   - **Current Status:** Wait until HTTPS is set up

3. ⚠️ **zlib.outputCompression** - **OPTIONAL**
   - **Why:** Compresses output (faster page loads)
   - **Impact:** May cause issues with some features
   - **Recommendation:** Test first, then enable if no issues

## 📝 Additional Settings (if available in Hostinger panel)

### date.timezone
- **Set to:** `UTC`
- **Why:** Consistent timezone handling
- **Note:** This may be in a separate field, not a checkbox

### Memory and Limits (if adjustable)
- **memory_limit:** 256M (or leave default if adequate)
- **max_execution_time:** 300 (for RSS parsing)
- **upload_max_filesize:** 10M (for images)
- **post_max_size:** 10M (for form submissions)

## ✅ Quick Setup Checklist

Go to Hostinger Control Panel → PHP Configuration → PHP Options:

**Enable (Check):**
- [ ] allowUrlFopen
- [ ] fileUploads
- [ ] opcache.enable
- [ ] opcache.enableCli
- [ ] session.useStrictMode
- [ ] session.cookieHttponly
- [ ] logErrors (for debugging)

**Disable (Uncheck):**
- [ ] displayErrors
- [ ] exposePhp
- [ ] shortOpenTag
- [ ] allowUrlInclude (if available)

**Conditional:**
- [ ] session.cookieSecure (enable when HTTPS is ready)

## 🔍 Verification Steps

After configuration:

1. **Create a test file:** `public/phpinfo.php`
   ```php
   <?php
   phpinfo();
   ?>
   ```

2. **Check the following:**
   - PHP Version = 8.3
   - All required extensions are loaded
   - `allow_url_fopen` = On
   - `file_uploads` = On
   - `opcache.enable` = 1
   - `session.cookie_httponly` = 1
   - `session.use_strict_mode` = 1
   - `display_errors` = Off

3. **Delete phpinfo.php after verification** (security)

## 🚨 Critical Issues to Watch For

If you see these errors, check the corresponding setting:

- **"fopen(): URL file-access is disabled"** → Enable `allowUrlFopen`
- **"File uploads are disabled"** → Enable `fileUploads`
- **"Call to undefined function imagecreatefromjpeg()"** → Enable `gd` extension
- **"Call to undefined function simplexml_load_string()"** → Enable `simplexml` extension
- **"PDOException: could not find driver"** → Enable `pdo_mysql` extension

## 📋 Default PHP 8.3 Behavior

After reset to defaults, PHP 8.3 typically has:
- ✅ Most extensions enabled by default (check anyway)
- ✅ `allow_url_fopen` = On (usually)
- ✅ `file_uploads` = On (usually)
- ⚠️ `opcache.enable` = May be Off (check!)
- ⚠️ Session security settings may be Off (check!)

## Summary

**Must Configure:**
1. Verify all required extensions are enabled
2. Enable `allowUrlFopen` (RSS feeds)
3. Enable `fileUploads` (image uploads)
4. Enable `opcache.enable` (performance)
5. Enable session security settings

**Total Time:** ~5 minutes in Hostinger control panel

