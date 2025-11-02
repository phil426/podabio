# Database Migration Instructions - Featured Widget Fields

## Migration Required

This migration adds support for featured widgets with special effects. It adds two new columns to the `widgets` table:
- `is_featured` (TINYINT, default 0) - Marks a widget as featured
- `featured_effect` (VARCHAR(50), nullable) - Stores the effect name (jiggle, burn, rotating-glow, blink, pulse, shake)

---

## Option 1: Run via Web Browser (Recommended) ⭐

1. **Backup first** (important!):
   - Visit: `https://getphily.com/database_backup_widgets.php`
   - This will create a backup in `/database_backups/widgets_backup_[timestamp].sql`

2. **Run migration**:
   - Visit: `https://getphily.com/database/migrate_add_featured_widgets.php`
   - The page will show success messages if everything works
   - If you see "Column 'is_featured' already exists", the migration was already run (safe to ignore)

**Advantages:**
- Automatic checks for existing columns
- Visual feedback with success/error messages
- No manual SQL needed

---

## Option 2: Run via phpMyAdmin

1. **Backup first**:
   - Log into cPanel → phpMyAdmin
   - Select your database (`u810635266_site_podnbio`)
   - Click on `widgets` table
   - Click "Export" tab
   - Choose "Quick" method
   - Click "Go" to download backup

2. **Run SQL**:
   - In phpMyAdmin, click "SQL" tab
   - Copy and paste the contents of `database/migrate_add_featured_widgets_direct.sql`
   - Click "Go"
   - Check for any errors (ignore "Column already exists" if shown)

**SQL Commands:**
```sql
ALTER TABLE widgets ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active;
ALTER TABLE widgets ADD COLUMN featured_effect VARCHAR(50) DEFAULT NULL AFTER is_featured;
CREATE INDEX IF NOT EXISTS idx_is_featured ON widgets(is_featured);
```

---

## Option 3: Command Line (if you have SSH access)

```bash
# Navigate to your site directory
cd ~/public_html  # or wherever your site files are

# Backup first
php database_backup_widgets.php

# Run migration
php database/migrate_add_featured_widgets.php
```

---

## Verification

After running the migration, verify it worked:

**Via phpMyAdmin:**
- Go to `widgets` table
- Click "Structure" tab
- Look for columns: `is_featured` and `featured_effect`

**Via SQL:**
```sql
DESCRIBE widgets;
```

You should see:
- `is_featured` - tinyint(1), default 0
- `featured_effect` - varchar(50), nullable

---

## Rollback (if needed)

If something goes wrong and you need to rollback:

```sql
ALTER TABLE widgets DROP COLUMN is_featured;
ALTER TABLE widgets DROP COLUMN featured_effect;
DROP INDEX idx_is_featured ON widgets;
```

Then restore from backup if necessary.

---

## Troubleshooting

**Error: "Duplicate column name 'is_featured'"**
- This means the migration already ran. Safe to ignore.

**Error: "Table 'widgets' doesn't exist"**
- Your database structure may be different. Check table name.

**Error: Connection issues**
- Verify database credentials in `config/database.php`
- Check database server is accessible

---

## What This Enables

After migration, users can:
- Mark any widget as "featured" (only one at a time)
- Add special effects to featured widgets:
  - Jiggle - Subtle wiggling motion
  - Burn - Glowing ember effect
  - Rotating Glow - Color-changing glow
  - Blink - Opacity pulsing
  - Pulse - Subtle scaling effect
  - Shake - Horizontal shake animation

