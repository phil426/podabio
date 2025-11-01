# Database Status Check

## Quick Check Queries

Run these in **phpMyAdmin** → **SQL tab** to check migration status:

---

## Option 1: Quick Status Check

```sql
-- Quick check: Do the new columns exist?
SELECT 
    'themes' AS table_name,
    COUNT(*) AS migration_columns_found
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'themes'
  AND COLUMN_NAME IN ('user_id', 'page_background', 'widget_styles', 'spatial_effect')
UNION ALL
SELECT 
    'pages' AS table_name,
    COUNT(*) AS migration_columns_found
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pages'
  AND COLUMN_NAME IN ('page_background', 'widget_styles', 'spatial_effect');
```

**Expected Results:**
- ✅ **themes**: Should show `4` (user_id, page_background, widget_styles, spatial_effect)
- ✅ **pages**: Should show `3` (page_background, widget_styles, spatial_effect)
- ❌ If showing `0` or less, migration not yet applied

---

## Option 2: Detailed Structure Check

### Check Themes Table:
```sql
DESCRIBE themes;
```

**Look for these columns:**
- ✅ `user_id` (INT UNSIGNED, NULL)
- ✅ `page_background` (VARCHAR(500), NULL)
- ✅ `widget_styles` (JSON, NULL)
- ✅ `spatial_effect` (VARCHAR(50), NULL, DEFAULT 'none')

### Check Pages Table:
```sql
DESCRIBE pages;
```

**Look for these columns:**
- ✅ `page_background` (VARCHAR(500), NULL)
- ✅ `widget_styles` (JSON, NULL)
- ✅ `spatial_effect` (VARCHAR(50), NULL, DEFAULT 'none')

---

## Option 3: Full Status Report

Run the complete check script:

```sql
-- Check themes table columns
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'themes'
  AND COLUMN_NAME IN ('user_id', 'page_background', 'widget_styles', 'spatial_effect')
ORDER BY COLUMN_NAME;

-- Check pages table columns
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pages'
  AND COLUMN_NAME IN ('page_background', 'widget_styles', 'spatial_effect')
ORDER BY COLUMN_NAME;

-- Check index
SHOW INDEX FROM themes WHERE Key_name = 'idx_user_id';
```

---

## Migration Status Interpretation

### ✅ Migration Complete
If you see:
- All columns listed above in both tables
- `idx_user_id` index exists
- Column types match expected values

**Action:** You're ready to use the theme system!

### ❌ Migration Not Applied
If you see:
- Missing columns
- No index on `themes.user_id`
- Column count less than expected

**Action:** Run the migration from `DATABASE_MIGRATION_INSTRUCTIONS.md`

---

## Quick Visual Check in phpMyAdmin

1. **Open phpMyAdmin**
2. **Select your database**
3. **Click on `themes` table**
4. **Go to "Structure" tab**
5. **Look for these columns in the list:**
   - user_id
   - page_background
   - widget_styles
   - spatial_effect

6. **Click on `pages` table**
7. **Go to "Structure" tab**
8. **Look for these columns:**
   - page_background
   - widget_styles
   - spatial_effect

If you see all of them → ✅ Migration complete!

---

## Sample Data Check

After migration, you can also check sample data:

```sql
-- Check if any themes have widget_styles set
SELECT id, name, widget_styles, spatial_effect 
FROM themes 
LIMIT 5;

-- Check if any pages have custom backgrounds
SELECT id, username, page_background, spatial_effect 
FROM pages 
WHERE page_background IS NOT NULL 
LIMIT 5;
```

Most rows will have `NULL` initially (which is correct), but the columns should exist.

