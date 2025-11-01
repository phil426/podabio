# Manual Deployment Guide - Complete Theme System

## ⚠️ SSH Connection Issue
Automated deployment via SSH/SCP is timing out. Please deploy manually using one of these methods.

---

## Method 1: Git Pull (Fastest - If Available) ⭐

If your server has Git installed and the repo is cloned:

1. **Log into cPanel**
2. **Open Terminal** (if available)
3. **Run:**
   ```bash
   cd domains/getphily.com/public_html
   git pull origin main
   ```

If Terminal is not available, you can also do this via SSH if you have another SSH client or different access method.

---

## Method 2: cPanel File Manager (Recommended)

### Step 1: Upload NEW Files

1. **Log into cPanel**
2. **Open File Manager**
3. **Navigate to:** `public_html`
4. **Create directories if they don't exist:**
   - `database/` (should exist)
   - `classes/` (should exist)
   - `includes/` (should exist)

5. **Upload these NEW files:**

   ```
   database/check_migration.php
   classes/ColorExtractor.php
   classes/ThemeCSSGenerator.php
   classes/WidgetStyleManager.php
   classes/APIResponse.php
   includes/theme-helpers.php
   ```

### Step 2: Replace MODIFIED Files

**Replace these existing files:**
```
api/page.php
api/upload.php
classes/ImageHandler.php
classes/Theme.php
classes/Page.php
page.php
editor.php
```

**Instructions:**
1. In File Manager, navigate to each file's directory
2. **Delete the old file** (or rename to `.backup`)
3. **Upload the new file** from your local repo
4. **Set permissions:** 644 for PHP files, 755 for directories

---

## Method 3: FTP/SFTP Client

Using FileZilla or similar:

1. **Connect to server**
2. **Navigate to:** `/domains/getphily.com/public_html/`
3. **Upload/Replace all files listed above**
4. **Set permissions:** 644 for files, 755 for directories

---

## Complete File List

### New Files (6)
- `database/check_migration.php`
- `classes/ColorExtractor.php`
- `classes/ThemeCSSGenerator.php`
- `classes/WidgetStyleManager.php`
- `classes/APIResponse.php`
- `includes/theme-helpers.php`

### Modified Files (7)
- `api/page.php`
- `api/upload.php`
- `classes/ImageHandler.php`
- `classes/Theme.php`
- `classes/Page.php`
- `page.php`
- `editor.php`

---

## Verification After Deployment

1. **Check Database:** Visit `https://getphily.com/database/check_migration.php`
2. **Test Editor:** Visit `https://getphily.com/editor.php` → Appearance tab
3. **Check for Errors:** Browser console (F12) and PHP error logs

---

**All files are in your local repository and committed to GitHub.**

