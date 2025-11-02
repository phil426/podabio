# Deploy Migration File - Quick Guide

## Issue
The file `database/migrate_add_featured_widgets.php` is getting a 404 error, which means it hasn't been deployed to the server yet.

## Quick Fix - Deploy Just This File

### Option 1: cPanel File Manager (Fastest)

1. **Log into cPanel**
2. **Open File Manager**
3. **Navigate to:** `public_html/database/`
4. **Upload the file:**
   - File to upload: `database/migrate_add_featured_widgets.php`
   - Make sure it goes in the `database/` folder
5. **Set permissions:** 644 (right-click file → Change Permissions)

### Option 2: Use deploy_widgets.php (Pulls All Files)

1. **Upload `deploy_widgets.php` to server root** (if not already there)
2. **Visit:** `https://getphily.com/deploy_widgets.php?deploy=1`
3. This will pull all files including the migration file
4. **Delete `deploy_widgets.php` after deployment**

### Option 3: Git Pull on Server

If you have SSH or cPanel Terminal access:

```bash
cd /home/u810635266/domains/getphily.com/public_html
git pull origin main
```

### Option 4: Direct File Content

If you can't access the file, here's what needs to be in `database/migrate_add_featured_widgets.php`:

```php
<?php
/**
 * Migration: Add Featured Widget Fields
 * Adds is_featured and featured_effect columns to widgets table
 * 
 * IMPORTANT: Run database_backup_widgets.php on the server BEFORE running this migration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Widget Featured Fields Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>Widget Featured Fields Migration</h1>";

try {
    // Check if columns already exist
    $columns = $pdo->query("SHOW COLUMNS FROM widgets LIKE 'is_featured'")->fetchAll();
    if (!empty($columns)) {
        echo "<div class='info'><strong>Info:</strong> Column 'is_featured' already exists. Skipping migration.</div>";
        exit;
    }
    
    echo "<div class='info'><strong>Starting migration...</strong></div>";
    
    // Add is_featured column
    $pdo->exec("ALTER TABLE widgets ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active");
    echo "<div class='success'>✓ Added 'is_featured' column</div>";
    
    // Add featured_effect column
    $pdo->exec("ALTER TABLE widgets ADD COLUMN featured_effect VARCHAR(50) DEFAULT NULL AFTER is_featured");
    echo "<div class='success'>✓ Added 'featured_effect' column</div>";
    
    // Add index for featured widgets
    $pdo->exec("CREATE INDEX idx_is_featured ON widgets(is_featured)");
    echo "<div class='success'>✓ Added index on 'is_featured'</div>";
    
    echo "<div class='success'><strong>Migration completed successfully!</strong></div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>✗ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(1);
}

echo "</body></html>";
```

Copy this into a file named `migrate_add_featured_widgets.php` in the `database/` folder.

## Verify Deployment

After deploying, verify the file exists:
- Visit: `https://getphily.com/database/migrate_add_featured_widgets.php`
- Should show migration interface or "already exists" message

## Next Step

Once the file is deployed, run the migration:
- Visit: `https://getphily.com/database/migrate_add_featured_widgets.php`

