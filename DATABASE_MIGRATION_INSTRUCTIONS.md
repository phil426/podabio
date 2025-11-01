# Database Migration Instructions

## Quick Migration Options

Since SSH connection is timing out, here are 3 ways to run the migration:

---

## Option 1: phpMyAdmin (Easiest) ⭐ RECOMMENDED

1. **Log into cPanel**
2. **Open phpMyAdmin**
3. **Select your database** (usually `u810635266_podn` or similar)
4. **Click "SQL" tab**
5. **Copy and paste the SQL below:**
6. **Click "Go"**

```sql
-- Theme System Migration
ALTER TABLE themes ADD COLUMN user_id INT UNSIGNED NULL AFTER preview_image;
ALTER TABLE themes ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts;
ALTER TABLE themes ADD COLUMN widget_styles JSON NULL AFTER page_background;
ALTER TABLE themes ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles;
CREATE INDEX idx_user_id ON themes(user_id);
UPDATE themes SET user_id = NULL WHERE user_id IS NULL;

ALTER TABLE pages ADD COLUMN page_background VARCHAR(500) NULL AFTER fonts;
ALTER TABLE pages ADD COLUMN widget_styles JSON NULL AFTER page_background;
ALTER TABLE pages ADD COLUMN spatial_effect VARCHAR(50) NULL DEFAULT 'none' AFTER widget_styles;
```

**Note:** If you get "Duplicate column" errors, the migration has already been run. That's fine!

---

## Option 2: cPanel Terminal

1. **Log into cPanel**
2. **Open Terminal** (if available)
3. **Navigate to your site:**
   ```bash
   cd domains/getphily.com/public_html
   ```
4. **Run the migration:**
   ```bash
   php database/migrate_theme_system.php
   ```

---

## Option 3: Direct MySQL Command

If you have MySQL command line access:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/migrate_theme_system_direct.sql
```

---

## Verification

After running the migration, verify with:

```sql
DESCRIBE themes;
DESCRIBE pages;
```

You should see these new columns:
- `themes.user_id`
- `themes.page_background`
- `themes.widget_styles`
- `themes.spatial_effect`
- `pages.page_background`
- `pages.widget_styles`
- `pages.spatial_effect`

---

## What This Adds

### Themes Table
- **user_id** - Links custom themes to users (NULL = system theme)
- **page_background** - Background color or gradient
- **widget_styles** - JSON config for widget styling
- **spatial_effect** - Spatial effect name

### Pages Table
- **page_background** - Page-specific background override
- **widget_styles** - Page-specific widget styles override
- **spatial_effect** - Page-specific spatial effect override

---

## Troubleshooting

### Error: "Duplicate column name"
**Solution:** Migration already applied. Safe to ignore.

### Error: "Cannot add foreign key constraint"
**Solution:** Users table might not exist or have different structure. Comment out the foreign key line in the SQL.

### Error: "Table doesn't exist"
**Solution:** Check your database name and table names match exactly.

---

## After Migration

1. ✅ Refresh your editor page
2. ✅ Test theme selection
3. ✅ Test page background changes
4. ✅ Test widget styling
5. ✅ Test spatial effects
6. ✅ Test save theme functionality

---

## Need Help?

If you encounter issues:
1. Copy the exact error message
2. Check which SQL statement failed
3. Verify table structure matches expected schema

