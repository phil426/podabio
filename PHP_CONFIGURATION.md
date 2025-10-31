# PHP Configuration Recommendations - Podn.Bio

## PHP Version Recommendation

### Recommended: PHP 8.3

**Why PHP 8.3 over 8.2 or 8.4?**

- **PHP 8.2:** Stable, but PHP 8.3 offers better performance and new features
- **PHP 8.3:** 
  - ✅ Latest stable release (as of 2024)
  - ✅ Better performance than 8.2 (~5-10% improvement)
  - ✅ Improved type system
  - ✅ Better error handling
  - ✅ Good security patches
  - ✅ Full compatibility with Podn.Bio codebase
- **PHP 8.4:** 
  - ⚠️ May have breaking changes
  - ⚠️ Less tested in production
  - ❌ Not recommended for production launch (consider after stability)

### Minimum Requirements

- **Minimum:** PHP 8.0 (for modern features)
- **Recommended:** PHP 8.3
- **Maximum:** PHP 8.3 (stay stable for production)

## Required PHP Extensions

Based on Podn.Bio requirements, ensure these extensions are **ENABLED**:

### Critical Extensions (Must Have)

1. **gd** - Image processing (ImageHandler class)
2. **mysqli / PDO** - Database connectivity
3. **pdo_mysql** - PDO MySQL driver
4. **simplexml** - RSS feed parsing
5. **json** - API responses and data handling
6. **curl** - API integrations (TikTok, Instagram, email services, etc.)
7. **openssl** - Security/encryption, HTTPS
8. **mbstring** - Multibyte string handling
9. **session** - User sessions
10. **filter** - Input validation
11. **hash** - Password hashing

### Recommended Extensions (Nice to Have)

1. **imagick** - Advanced image processing (better quality than GD)
2. **opcache** - Performance optimization (should be enabled)
3. **zip** - For future features (backup/export)
4. **xml** - XML parsing
5. **dom** - DOM manipulation

### Current Hostinger Status

✅ All required extensions are already enabled
✅ imagick is enabled (great!)
✅ opcache is enabled (essential for performance)

## PHP Configuration Settings

### Performance Settings

```ini
; Memory limit (adequate for image processing)
memory_limit = 256M

; Maximum execution time (for RSS parsing, API calls)
max_execution_time = 300
max_input_time = 300

; File upload limits
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20

; OpCache (should be enabled)
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 1  ; Set to 0 in production after testing
```

### Security Settings

```ini
; Error display (production should be OFF)
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; File upload security
file_uploads = On
upload_tmp_dir = /tmp

; URL access (needed for RSS feeds and API calls)
allow_url_fopen = On  ; Required for RSS feed parsing and API integrations
allow_url_include = Off  ; Should always be OFF for security

; Session security
session.cookie_httponly = On
session.cookie_secure = On  ; Enable when using HTTPS
session.cookie_samesite = Strict
session.use_strict_mode = On  ; Recommended for security

; Timezone
date.timezone = UTC
```

### Disabled Functions (Security)

These functions are already disabled on Hostinger (good):
- system, exec, shell_exec, passthru
- mysql_list_dbs (deprecated)
- ini_alter
- dl
- symlink, link
- chgrp
- leak, popen
- apache_child_terminate, virtual

**Recommendation:** Keep these disabled for security. Our codebase doesn't use any of these.

### Current Hostinger Settings Analysis

**Already Optimized:**
- ✅ allowUrlFopen: Enabled (needed for RSS feeds)
- ✅ fileUploads: Enabled (required)
- ✅ opcache.enable: Enabled (performance)
- ✅ displayErrors: Disabled (security)
- ✅ date.timezone: UTC (correct)

**Should Enable:**
- ⚠️ session.useStrictMode: Should be enabled (security)
- ⚠️ session.cookieHttponly: Should be enabled (security)
- ⚠️ session.cookieSecure: Enable when HTTPS is configured

**Should Review:**
- ⚠️ logErrors: Currently disabled - consider enabling for debugging
- ℹ️ errorReporting: Should be configured appropriately

## PHP Options for Hostinger Control Panel

Based on current settings and Podn.Bio needs:

### Recommended Settings

**Enable (Check):**
- ✅ `allowUrlFopen` - Required for RSS feeds and API calls
- ✅ `fileUploads` - Required for image uploads
- ✅ `opcache.enable` - Performance optimization
- ✅ `opcache.enableCli` - Command-line performance
- ✅ `session.useStrictMode` - **RECOMMENDED: Enable for security**
- ✅ `session.cookieHttponly` - **RECOMMENDED: Enable for security**

**Disable (Uncheck):**
- ❌ `exposePhp` - Security: Don't expose PHP version (already disabled)
- ❌ `displayErrors` - Security: Don't show errors to users (already disabled)
- ❌ `shortOpenTag` - Best practice: Use full <?php tags
- ❌ `logErrors` - Consider: Enable for debugging, disable for production

**Conditional:**
- `session.cookieSecure` - Enable ONLY when HTTPS is configured
- `zlib.outputCompression` - Optional: Can improve performance but may cause issues

## Memory and Resource Limits

### Current Requirements

Based on Podn.Bio operations:
- **RSS Feed Parsing:** Moderate memory (~32-64MB)
- **Image Processing:** Moderate memory (~64-128MB per image)
- **API Calls:** Low memory (~16-32MB)
- **Database Queries:** Low memory (~16-32MB)

### Recommended Limits

```ini
memory_limit = 256M  ; Adequate for all operations
max_execution_time = 300  ; For RSS parsing and long API calls
post_max_size = 10M  ; For multiple image uploads
upload_max_filesize = 10M  ; For large images
```

Current Hostinger default is likely adequate, but verify in PHP Info.

## Performance Optimization

### OpCache Configuration

Since OpCache is enabled, ensure optimal settings:

```ini
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2  ; Check for file changes every 2 seconds (development)
opcache.validate_timestamps = 1  ; Set to 0 in production after deployment
opcache.save_comments = 1
```

### Production Recommendations

1. **Enable OpCache** ✅ (already enabled)
2. **Set validate_timestamps = 0** after code is stable (faster)
3. **Use Redis/Memcached** for session storage (future optimization)
4. **Enable GZIP compression** via .htaccess or server config
5. **Use CDN** for static assets (future optimization)

## Compatibility Check

### Code Compatibility

✅ **PHP 8.0+ Features Used:**
- Type hints (parameters and returns)
- Nullable types
- Arrow functions (not used, but compatible)
- JSON handling
- Namespaces

✅ **No Deprecated Features:**
- No mysql_* functions (using PDO/mysqli)
- No ereg functions
- Modern password hashing (password_hash)

### Breaking Changes to Watch

**None for PHP 8.0 → 8.3 upgrade** - Our codebase is fully compatible.

## Summary & Action Items

### ⚠️ IMPORTANT: After PHP 8.3 Upgrade Reset

If you just upgraded to PHP 8.3 and configuration was reset, see:
- **Quick Setup:** `QUICK_PHP_SETUP.md` (one-page guide)
- **Detailed Checklist:** `PHP_8.3_SETUP_CHECKLIST.md` (complete reference)

### Immediate Actions

1. **Verify PHP Version:** 
   - ✅ Should be PHP 8.3 (you've already done this)

2. **CRITICAL - Enable Required Features:**
   - ✅ Enable `allowUrlFopen` (RSS feeds won't work without this!)
   - ✅ Enable `fileUploads` (image uploads won't work without this!)
   - ✅ Enable `opcache.enable` (performance critical!)

3. **Enable Security Settings:**
   - ✅ Enable `session.useStrictMode`
   - ✅ Enable `session.cookieHttponly`
   - ⏳ Enable `session.cookieSecure` (after HTTPS setup)

4. **Verify Extensions:**
   - ✅ All required extensions should be enabled by default
   - ✅ Verify gd, mysqli, pdo_mysql, simplexml, curl, opcache

5. **Disable for Security:**
   - ✅ Disable `displayErrors`
   - ✅ Disable `exposePhp`
   - ✅ Disable `shortOpenTag` (best practice)

### Long-term Optimizations

1. Set OpCache `validate_timestamps = 0` after deployment
2. Configure proper error logging location
3. Monitor memory usage and adjust if needed
4. Consider Redis for session storage (scaling)

## Verification

After configuration changes, verify with:

```php
<?php
phpinfo();
?>
```

Check:
- PHP version
- All required extensions loaded
- Memory limits
- OpCache status
- Session settings
- Security settings

