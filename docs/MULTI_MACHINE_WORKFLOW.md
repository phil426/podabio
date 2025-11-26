# Multi-Machine Development Workflow Guide

## Overview

This guide helps you work safely across multiple computers (laptop and main computer) without mixing up environments or losing work.

## Key Principles

1. **Git is the source of truth** - Always commit and push changes before switching machines
2. **Never commit sensitive files** - Database configs, API keys, etc. are gitignored
3. **Environment-specific configs** - Each machine needs its own local config files
4. **Dependency consistency** - Keep package versions in sync

---

## Setup Checklist for New Machine

### 1. Clone Repository
```bash
git clone https://github.com/phil426/podabio.git
cd podabio
```

### 2. Install Dependencies

**PHP (via Homebrew):**
```bash
brew install php
```

**Node.js (via Homebrew):**
```bash
brew install node
```

**Verify installations:**
```bash
php --version  # Should show PHP 8.5.0+
node --version # Should show v25.2.1+
npm --version  # Should show 11.6.2+
```

### 3. Install Node Dependencies
```bash
cd admin-ui
npm install
cd ..
```

### 4. Create Local Configuration Files

**Database Config** (`config/database.php`):
```php
<?php
// Local development database configuration
// DO NOT COMMIT THIS FILE (it's in .gitignore)

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'podabio_dev');  // Your local database name
define('DB_USER', 'root');          // Your local MySQL user
define('DB_PASS', '');              // Your local MySQL password
define('DB_CHARSET', 'utf8mb4');
```

**OAuth Config** (`config/oauth.php`):
```php
<?php
// Copy from your main computer or create new credentials
// DO NOT COMMIT THIS FILE (it's in .gitignore)

define('GOOGLE_CLIENT_ID', 'your-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
```

**Update Constants for Local Dev** (`config/constants.php`):
- Change `APP_URL` to `http://localhost:8080` for local development
- Or create a local override file (see below)

### 5. Set Up Local Database
```bash
# Create local database
mysql -u root -e "CREATE DATABASE podabio_dev;"

# Import schema (if needed)
mysql -u root podabio_dev < database/schema.sql
```

---

## Daily Workflow

### Before Switching Machines

1. **Check git status:**
   ```bash
   git status
   ```

2. **Commit all changes:**
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

3. **Verify push succeeded:**
   ```bash
   git log -1  # Check your commit is there
   ```

### On New Machine

1. **Pull latest changes:**
   ```bash
   git pull origin main
   ```

2. **Check for uncommitted changes:**
   ```bash
   git status
   ```

3. **If there are conflicts or uncommitted changes:**
   - Review what changed
   - Resolve conflicts if any
   - Don't overwrite local config files (database.php, oauth.php)

---

## Environment-Specific Files

These files are **NOT** in git (they're in `.gitignore`):

- `config/database.php` - Database credentials (different per machine)
- `config/oauth.php` - OAuth credentials
- `config/payments.php` - Payment API keys
- `*.env` files - Environment variables
- `node_modules/` - Dependencies (reinstall with `npm install`)
- `uploads/` - User-uploaded files

**Important:** Each machine needs its own copies of these files.

---

## Recommended: Environment Detection

You can create a simple environment detection system:

### Option 1: Local Override File (Recommended)

Create `config/local.php` (gitignored) that overrides constants:

```php
<?php
// config/local.php - Local development overrides
// Add to .gitignore if not already there

// Override APP_URL for local development
define('APP_URL', 'http://localhost:8080');
```

Then in `config/constants.php`, add at the end:
```php
// Load local overrides if they exist
if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}
```

### Option 2: Environment Variable

Set an environment variable:
```bash
export PODABIO_ENV=local
```

Then check in your PHP code:
```php
$env = getenv('PODABIO_ENV') ?: 'production';
if ($env === 'local') {
    define('APP_URL', 'http://localhost:8080');
}
```

---

## Common Issues & Solutions

### Issue: "Database connection failed"
**Solution:** Check `config/database.php` has correct local credentials

### Issue: "npm packages missing"
**Solution:** Run `cd admin-ui && npm install`

### Issue: "PHP/Node not found"
**Solution:** 
- Verify Homebrew is installed: `which brew`
- Add to PATH: `export PATH="/opt/homebrew/bin:$PATH"`
- Add to `~/.zshrc` or `~/.bash_profile` for persistence

### Issue: "Git conflicts on config files"
**Solution:** 
- Config files should be gitignored
- If accidentally committed, remove: `git rm --cached config/database.php`
- Each machine should have its own copy

### Issue: "Changes lost between machines"
**Solution:**
- Always commit and push before switching
- Use `git stash` for temporary work: `git stash` before switching, `git stash pop` after pulling

---

## Best Practices

1. **Always pull before starting work:**
   ```bash
   git pull origin main
   ```

2. **Commit frequently:**
   - Small, logical commits
   - Descriptive commit messages
   - Push regularly

3. **Use feature branches for larger work:**
   ```bash
   git checkout -b feature/my-feature
   # ... work ...
   git push origin feature/my-feature
   ```

4. **Keep dependencies in sync:**
   - `admin-ui/package.json` is committed (versions are tracked)
   - Run `npm install` after pulling if package.json changed

5. **Document machine-specific setup:**
   - Note any special configurations
   - Keep this file updated

---

## Quick Reference Commands

### Start Development
```bash
./dev-start.sh
```

### Stop Development
```bash
./dev-stop.sh
```

### Sync with Remote
```bash
git pull origin main    # Get latest changes
git push origin main   # Send your changes
```

### Check What's Different
```bash
git status              # Uncommitted changes
git diff                # See what changed
git log --oneline -10   # Recent commits
```

---

## Machine-Specific Notes

### Main Computer
- Location: [Add your main computer path]
- Database: [Add database name]
- Special configs: [Any notes]

### Laptop
- Location: `/Users/philybarrolaza/Documents/Assets/podabio/podabio`
- Database: `podabio_dev` (local)
- Special configs: Uses Homebrew PHP/Node

---

## Emergency: Recovering Lost Work

If you forgot to commit/push:

1. **On the machine with the work:**
   ```bash
   git stash
   git stash list  # Note the stash number
   ```

2. **Push the stash reference** (if possible) or manually copy files

3. **On the other machine:**
   ```bash
   git pull
   git stash pop stash@{0}  # Use the stash number from step 1
   ```

---

## Questions?

If you're unsure about something:
1. Check `git status` first
2. Review this guide
3. When in doubt, commit and push before switching machines

