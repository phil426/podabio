# PodInBio Deployment Guide - poda.bio

> **Security Notice**
> This document contains sensitive operational details. Store securely and rotate shared credentials regularly. Do **not** expose publicly.

## Server Information

### SSH Access
- **Host/IP:** `195.179.237.142`
- **Port:** `65002`
- **Username:** `u925957603`
- **Password:** `[REDACTED]`
- **Domain:** `poda.bio`
- **Project Directory:** `/home/u925957603/domains/poda.bio/public_html/` (standard Hostinger path)
- **SSH Command:** `ssh -p 65002 u925957603@195.179.237.142`

### MySQL Database
- **Host:** `srv775.hstgr.io` (or IP: `195.179.237.102`)
- **Database:** `u925957603_podabio`
- **Username:** `u925957603_pab`
- **Password:** `[REDACTED]`
- **phpMyAdmin:** Accessible via Hostinger hPanel (Databases > phpMyAdmin)

### Git Repository
- **URL:** `https://github.com/phil426/podn-bio.git`
- **Branch:** `main`

### SSL Certificate
- **Status:** ✅ Active (Lifetime SSL)
- **Domain:** `poda.bio`
- **Expires:** Never

## Initial Server Setup

### 1. Connect to Server
```bash
ssh -p 65002 u925957603@195.179.237.142
```

### 2. Navigate to Project Directory
```bash
cd /home/u925957603/domains/poda.bio/public_html/
```

### 3. Clone Repository (First Time Only)
```bash
git clone https://github.com/phil426/podn-bio.git .
# Or if directory already exists:
git clone https://github.com/phil426/podn-bio.git temp && mv temp/* . && mv temp/.git . && rmdir temp
```

### 4. Configure Database Connection

Create or update `config/database.php` with the following:

```php
<?php
/**
 * Database Configuration
 * Podn.Bio - poda.bio Production
 */

// Database connection settings
define('DB_HOST', 'srv775.hstgr.io'); // or '195.179.237.102'
define('DB_NAME', 'u925957603_podabio');
define('DB_USER', 'u925957603_pab');
define('DB_PASS', '[REDACTED]');
define('DB_CHARSET', 'utf8mb4');

// PDO connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// ... rest of database.php functions ...
```

### 5. Verify APP_URL Configuration

Ensure `config/constants.php` has:
```php
define('APP_URL', 'https://poda.bio');
```

### 6. Set File Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/backgrounds/
chmod 755 uploads/thumbnails/
chmod 755 uploads/blog/
```

### 7. Import Database Schema

**Option A: Via SSH**
```bash
mysql -h srv775.hstgr.io -u u925957603_pab -p u925957603_podabio < database/schema.sql
# Enter password when prompted: [REDACTED]
```

**Option B: Via phpMyAdmin**
1. Log into Hostinger hPanel
2. Navigate to Databases > phpMyAdmin
3. Select `u925957603_podabio` database
4. Click "Import" tab
5. Choose `database/schema.sql` file
6. Click "Go"

### 8. Import Seed Data (Optional)

```bash
mysql -h srv775.hstgr.io -u u925957603_pab -p u925957603_podabio < database/seed_data.sql
```

## Deployment Workflow

### Standard Deployment Process

1. **Build React App Locally** (if changes were made to admin-ui)
   ```bash
   cd admin-ui
   npm install
   npm run build
   git add admin-ui/dist/
   git commit -m "Build admin-ui for production"
   git push origin main
   ```

2. **Deploy to Server**
   ```bash
   ./deploy_poda_bio.sh
   ```
   
   Or manually:
   ```bash
   ssh -p 65002 u925957603@195.179.237.142
   cd /home/u925957603/domains/poda.bio/public_html/
   git pull origin main
   ```

3. **Run Database Migrations** (if any)
   - Check for new migration files in `database/` directory
   - Run via SSH or phpMyAdmin as needed

4. **Verify Deployment**
   - Test PHP backend: `https://poda.bio/index.php`
   - Test admin panel: `https://poda.bio/admin/react-admin.php`
   - Check browser console for React app errors
   - Test database connectivity
   - Verify file uploads work

## React/Vite Build Process

The React admin-ui is built using Vite:

- **Command:** `npm run build` (runs `tsc -b && vite build`)
- **Output:** `admin-ui/dist/` directory
- **Manifest:** `admin-ui/dist/.vite/manifest.json` (used by `admin/react-admin.php`)
- **Build Location:** Built files are committed to Git for deployment

The PHP file `admin/react-admin.php` reads the manifest to load the correct JS/CSS files:
- Checks for `admin-ui/dist/manifest.json`
- Loads entry point from manifest
- Falls back to dev server (`http://localhost:5174`) if manifest not found

## Troubleshooting

### Database Connection Issues
- Verify MySQL host is correct: `srv775.hstgr.io` or `195.179.237.102`
- Check credentials in `config/database.php`
- Ensure database exists: `u925957603_podabio`
- Test connection via SSH: `mysql -h srv775.hstgr.io -u u925957603_pab -p`

### React App Not Loading
- Verify `admin-ui/dist/` directory exists
- Check `admin-ui/dist/.vite/manifest.json` exists
- Verify file permissions on `admin-ui/dist/` directory
- Check browser console for 404 errors on JS/CSS files
- Ensure `config/constants.php` has correct `APP_URL`

### File Upload Issues
- Check file permissions on `uploads/` directories (should be 755)
- Verify PHP `upload_max_filesize` and `post_max_size` settings
- Check error logs: `error_log` or `logs/error_log`

### Git Pull Issues
- Ensure SSH key is set up or password is correct
- Verify repository URL: `https://github.com/phil426/podn-bio.git`
- Check branch: should be `main`

## Security Best Practices

1. **Credentials Management**
   - Never commit `config/database.php` to Git (already in `.gitignore`)
   - Rotate passwords periodically
   - Use SSH keys instead of passwords when possible

2. **File Permissions**
   - Keep uploads directories at 755 (not 777)
   - Ensure sensitive files are not publicly accessible

3. **SSL Certificate**
   - SSL is already configured (Lifetime SSL)
   - Ensure all URLs use `https://` in production

## Backup Procedures

### Database Backup
```bash
mysqldump -h srv775.hstgr.io -u u925957603_pab -p u925957603_podabio > backup_$(date +%Y%m%d_%H%M%S).sql
```

### File Backup
```bash
cd /home/u925957603/domains/poda.bio/
tar czf ~/podabio_backup_$(date +%Y%m%d_%H%M%S).tar.gz public_html/
```

## Emergency Rollback

1. SSH into server
2. Navigate to project directory
3. Check git log: `git log --oneline -10`
4. Reset to previous commit: `git reset --hard <commit-hash>`
5. Restore database from backup if needed

## Contact Information

- **Project Owner:** Phil (phil624@gmail.com)
- **Hosting Provider:** Hostinger
- **Support:** Via Hostinger hPanel ticket/chat
- **GitHub Repository:** https://github.com/phil426/podn-bio.git

---

**Last Updated:** 2025-01-13
**Server:** poda.bio (Hostinger)
**Status:** Production


