# Quick PHP 8.3 Setup Guide - Podn.Bio

## 🎯 One-Page Quick Reference

### Step 1: Go to Hostinger Control Panel
- Navigate to: **Websites → getphily.com → Advanced → PHP Configuration**

### Step 2: PHP Extensions Tab
**Verify these are ENABLED (all should be checked by default):**
- gd, mysqli, pdo_mysql, simplexml, json, curl, openssl, mbstring, session, filter, hash, opcache, imagick

### Step 3: PHP Options Tab

**ENABLE (Check the boxes):**
```
☑ allowUrlFopen          ← RSS feeds won't work without this!
☑ fileUploads            ← Image uploads won't work without this!
☑ opcache.enable         ← Site will be slow without this!
☑ opcache.enableCli      ← Recommended for performance
☑ session.useStrictMode  ← Security: prevents session attacks
☑ session.cookieHttponly ← Security: prevents XSS cookie theft
☑ logErrors              ← Recommended: helps with debugging
```

**DISABLE (Uncheck the boxes):**
```
☐ displayErrors          ← Security: don't show errors to users
☐ exposePhp              ← Security: don't expose PHP version
☐ shortOpenTag           ← Best practice: use full <?php tags
```

**SET WHEN READY:**
```
☑ session.cookieSecure    ← Enable ONLY when HTTPS is configured
```

### Step 4: Save Changes

### Step 5: Verify
Create `public/phpinfo.php` with:
```php
<?php phpinfo(); ?>
```
Visit: `https://getphily.com/phpinfo.php`
Check that all settings are correct, then **DELETE this file**.

## ✅ Done!

Your PHP 8.3 configuration is now optimized for Podn.Bio.


