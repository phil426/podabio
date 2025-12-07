# PodaBio Deployment Guide

Complete guide for deploying PodaBio to production servers.

## Table of Contents

1. [Quick Deploy](#quick-deploy)
2. [Server Information](#server-information)
3. [Initial Setup](#initial-setup)
4. [Standard Deployment](#standard-deployment)
5. [Database Migrations](#database-migrations)
6. [Troubleshooting](#troubleshooting)
7. [Security Best Practices](#security-best-practices)

---

## Quick Deploy

### For poda.bio (Production)

**Option 1: Automated Script (Recommended)**
```bash
./deploy_poda_bio.sh
```

This will:
- SSH into Hostinger (poda.bio server)
- Pull latest code from GitHub
- Verify files and permissions
- Show deployment status

**Option 2: Manual SSH Deployment**
```bash
# 1. Connect to server
ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142

# 2. Navigate to project
cd /home/u925957603/domains/poda.bio/public_html/

# 3. Pull code
git pull origin main

# 4. Exit
exit
```

**Option 3: Web-Based Deployment**
1. Pull code via SSH (see Option 2)
2. Run migration via browser: `https://poda.bio/database/migrate.php`
3. Click "Run Migration"
4. **Delete migrate.php after migration**

---

## Server Information

### poda.bio (Production)

**SSH Access:**
- **Host/IP:** `195.179.237.142`
- **Port:** `65002`
- **Username:** `u925957603`
- **Domain:** `poda.bio`
- **Project Directory:** `/home/u925957603/domains/poda.bio/public_html/`
- **SSH Command:** `ssh -p 65002 u925957603@195.179.237.142`

**MySQL Database:**
- **Host:** `srv775.hstgr.io` (or IP: `195.179.237.102`)
- **Database:** `u925957603_podabio`
- **Username:** `u925957603_pab`
- **phpMyAdmin:** Accessible via Hostinger hPanel

**Git Repository:**
- **URL:** `https://github.com/phil426/podabio.git`
- **Branch:** `main`

**SSL Certificate:**
- **Status:** ✅ Active (Lifetime SSL)
- **Domain:** `poda.bio`

---

## Initial Setup

### First-Time Deployment

#### 1. Connect to Server
```bash
ssh -p 65002 u925957603@195.179.237.142
```

#### 2. Navigate to Project Directory
```bash
cd /home/u925957603/domains/poda.bio/public_html/
```

#### 3. Clone Repository
```bash
# If directory is empty:
git clone https://github.com/phil426/podabio.git .

# If directory has files, backup first:
git clone https://github.com/phil426/podabio.git temp
mv temp/* . 2>/dev/null || true
mv temp/.git . 2>/dev/null || true
rmdir temp
```

#### 4. Configure Database Connection

Create `config/database.php`:
```php
<?php
/**
 * Database Configuration
 * PodaBio - poda.bio Production
 */

// Database connection settings
define('DB_HOST', 'srv775.hstgr.io');
define('DB_NAME', 'u925957603_podabio');
define('DB_USER', 'u925957603_pab');
define('DB_PASS', '[REDACTED - Check secure credential storage]');
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

#### 5. Verify APP_URL Configuration

Ensure `config/constants.php` has:
```php
define('APP_URL', 'https://poda.bio');
```

#### 6. Set File Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/backgrounds/
chmod 755 uploads/thumbnails/
chmod 755 uploads/blog/
```

#### 7. Import Database Schema

**Option A: Via SSH**
```bash
mysql -h srv775.hstgr.io -u u925957603_pab -p u925957603_podabio < database/schema.sql
```

**Option B: Via phpMyAdmin**
1. Log into Hostinger hPanel
2. Navigate to Databases > phpMyAdmin
3. Select `u925957603_podabio` database
4. Click "Import" tab
5. Choose `database/schema.sql` file
6. Click "Go"

**Option C: Use Setup Script**
```bash
./database/setup_poda_bio.sh
```

---

## Standard Deployment

### Deployment Workflow

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
   ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142
   cd /home/u925957603/domains/poda.bio/public_html/
   git pull origin main
   exit
   ```

3. **Run Database Migrations** (if any)
   - Check for new migration files in `database/` directory
   - Run via SSH or phpMyAdmin as needed

4. **Verify Deployment**
   - Test PHP backend: `https://poda.bio/index.php`
   - Test admin panel: `https://poda.bio/admin/userdashboard.php`
   - Check browser console for React app errors
   - Test database connectivity
   - Verify file uploads work

---

## Database Migrations

### Running Migrations

**Option A: Via Web Browser (Easiest)**
1. Visit: `https://poda.bio/database/migrate.php`
2. Click "Run Migration"
3. Verify success message
4. **Delete the migrate.php file after migration**

**Option B: Via Command Line**
```bash
ssh -p 65002 u925957603@195.179.237.142
cd /home/u925957603/domains/poda.bio/public_html/
php database/migrate.php
```

**Option C: Direct SQL**
```bash
mysql -h srv775.hstgr.io -u u925957603_pab -p u925957603_podabio <<EOF
-- Your SQL migration commands here
EOF
```

### Security: Clean Up After Migration

**IMPORTANT:** After successful migration, delete the migration script:
```bash
rm database/migrate.php
rm database/run_migration.php  # If exists
```

---

## React/Vite Build Process

The React admin-ui is built using Vite:

- **Command:** `npm run build` (runs `tsc -b && vite build`)
- **Output:** `admin-ui/dist/` directory
- **Manifest:** `admin-ui/dist/.vite/manifest.json` (used by `admin/userdashboard.php`)
- **Build Location:** Built files are committed to Git for deployment

The PHP file `admin/userdashboard.php` reads the manifest to load the correct JS/CSS files:
- Checks for `admin-ui/dist/manifest.json`
- Loads entry point from manifest
- Falls back to dev server (`http://localhost:5174`) if manifest not found

---

## SSH Key Setup (Recommended)

For passwordless authentication:

### Option 1: Use Existing Key
```bash
./setup_ssh_key_poda_bio.sh
```

### Option 2: Manual Setup

1. **Generate SSH Key** (if needed):
   ```bash
   ssh-keygen -t ed25519 -C "poda.bio-deployment" -f ~/.ssh/id_ed25519_podabio
   ```

2. **Copy Key to Server**:
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519_podabio.pub -p 65002 u925957603@195.179.237.142
   ```

3. **Test Connection**:
   ```bash
   ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142 "echo 'SSH key works!'"
   ```

After setup, deployment scripts will use SSH keys automatically.

---

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
- Verify repository URL: `https://github.com/phil426/podabio.git`
- Check branch: should be `main`
- Verify you have access to the repository

### SSH Connection Issues
- **Password Authentication Failing**: Verify password in Hostinger hPanel > SSH Access
- **Host Key Not Accepted**: Manually connect once to accept it
- **SSH Key Not Working**: 
  - Verify key exists: `ls -la ~/.ssh/id_ed25519_podabio*`
  - Check key permissions: `chmod 600 ~/.ssh/id_ed25519_podabio`
  - Test connection: `ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142`

### Migration Issues
- **Migration says "already completed"**: Table was already renamed - this is safe, no action needed
- **Migration fails**: Check database credentials, verify connection, check error logs

---

## Security Best Practices

1. **Credentials Management**
   - Never commit `config/database.php` to Git (already in `.gitignore`)
   - Rotate passwords periodically
   - Use SSH keys instead of passwords when possible
   - Store credentials securely (password manager, secure vault)

2. **File Permissions**
   - Keep uploads directories at 755 (not 777)
   - Ensure sensitive files are not publicly accessible
   - Verify `.gitignore` excludes sensitive config files

3. **SSL Certificate**
   - SSL is already configured (Lifetime SSL)
   - Ensure all URLs use `https://` in production
   - Verify SSL certificate is active

4. **Migration Scripts**
   - Always delete migration scripts after successful migration
   - Never leave migration scripts accessible via web

---

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

---

## Emergency Rollback

1. SSH into server
2. Navigate to project directory
3. Check git log: `git log --oneline -10`
4. Reset to previous commit: `git reset --hard <commit-hash>`
5. Restore database from backup if needed

---

## Verification Checklist

After deployment, verify:

- [ ] PHP backend loads: `https://poda.bio/index.php`
- [ ] Admin panel loads: `https://poda.bio/admin/userdashboard.php`
- [ ] React app loads (check browser console)
- [ ] Database connectivity works
- [ ] File uploads work
- [ ] No 404 errors for JS/CSS files
- [ ] SSL certificate is active
- [ ] Migration scripts deleted (if any were run)

---

## Contact Information

- **Project Owner:** Phil (phil624@gmail.com)
- **Hosting Provider:** Hostinger
- **Support:** Via Hostinger hPanel ticket/chat
- **GitHub Repository:** https://github.com/phil426/podabio.git

---

**Last Updated:** 2025-01-XX  
**Server:** poda.bio (Hostinger)  
**Status:** Production

