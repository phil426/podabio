# Theme System Migration Deployment Instructions

## Status
✅ Code has been committed and pushed to GitHub (commit: b4880ac)

## Manual Deployment Steps

Since SSH connection is timing out, follow these steps to deploy manually:

### Option 1: Via cPanel File Manager

1. **Download the migration file from GitHub:**
   - Navigate to: https://github.com/phil426/podn-bio/blob/main/database/migrate_theme_system.php
   - Click "Raw" to download the file
   - Or pull latest from git: `git pull origin main`

2. **Upload via cPanel:**
   - Log into cPanel
   - Go to File Manager
   - Navigate to `domains/getphily.com/public_html/database/`
   - Upload `migrate_theme_system.php`

3. **Run via cPanel Terminal or SSH:**
   ```bash
   cd domains/getphily.com/public_html
   php database/migrate_theme_system.php
   ```

### Option 2: Manual SQL (if PHP method fails)

Run this SQL directly in phpMyAdmin or MySQL client:

```sql
-- Update themes table
ALTER TABLE themes ADD COLUMN user_id INT UNSIGNED NULL AFTER preview_image;
ALTER TABLE themes ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts;
ALTER TABLE themes ADD COLUMN widget_styles JSON NULL AFTER page_background;
ALTER TABLE themes ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles;
CREATE INDEX idx_user_id ON themes(user_id);
ALTER TABLE themes ADD CONSTRAINT fk_themes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
UPDATE themes SET user_id = NULL WHERE user_id IS NULL;

-- Update pages table
ALTER TABLE pages ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts;
ALTER TABLE pages ADD COLUMN widget_styles JSON NULL AFTER page_background;
ALTER TABLE pages ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles;
```

### What This Migration Adds

**Themes Table:**
- `user_id` - Links custom themes to users (NULL = system theme)
- `page_background` - Page background (solid color or gradient)
- `widget_styles` - Widget styling config (JSON)
- `spatial_effect` - Spatial effect name

**Pages Table:**
- `page_background` - Page-specific background override
- `widget_styles` - Page-specific widget styles override
- `spatial_effect` - Page-specific spatial effect override

### Verification

After running the migration, verify with:

```sql
DESCRIBE themes;
DESCRIBE pages;
```

You should see the new columns listed.

### If Migration Already Applied

The migration script will detect if columns already exist and exit gracefully with a warning message.

## Next Steps

After migration is complete:
1. Continue with page.php CSS integration
2. Test theme system on frontend
3. Begin UI/UX implementation for editor

