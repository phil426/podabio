# Deployment Checklist - Theme System

## Files to Deploy (via cPanel File Manager or Git Pull)

### New Files (Create/Upload)
1. ✅ `database/check_migration.php` - Database status checker
2. ✅ `classes/ColorExtractor.php` - Color extraction class
3. ✅ `classes/ThemeCSSGenerator.php` - CSS generator class
4. ✅ `classes/WidgetStyleManager.php` - Widget style manager
5. ✅ `classes/APIResponse.php` - API response handler
6. ✅ `includes/theme-helpers.php` - Theme helper functions

### Modified Files (Update Existing)
1. ✅ `api/page.php` - Enhanced with theme endpoints
2. ✅ `api/upload.php` - Added theme_image type support
3. ✅ `classes/ImageHandler.php` - Added theme_image directory
4. ✅ `classes/Theme.php` - Extended with new methods
5. ✅ `classes/Page.php` - Added new fields support
6. ✅ `page.php` - Integrated ThemeCSSGenerator
7. ✅ `editor.php` - Complete theme UI implementation

---

## Quick Deployment Methods

### Method 1: Git Pull (Fastest) ⭐
If you have Git access on the server:

```bash
cd domains/getphily.com/public_html
git pull origin main
```

### Method 2: cPanel File Manager
1. Log into cPanel
2. Open File Manager
3. Navigate to `public_html`
4. Upload new files to their respective directories
5. Replace modified files

### Method 3: FTP/SFTP Client
1. Connect to server via FTP/SFTP
2. Navigate to `public_html` directory
3. Upload/replace files as listed above

---

## Verification Steps

### 1. Check Database Status
Visit: `https://getphily.com/database/check_migration.php`

Should show:
- ✅ All themes columns (4/4)
- ✅ All pages columns (3/3)
- ✅ Index exists

### 2. Test Theme Features
1. Log into editor: `https://getphily.com/editor.php`
2. Go to Appearance tab
3. Test features:
   - [ ] Theme selection (cards)
   - [ ] Page background (solid color)
   - [ ] Page background (gradient)
   - [ ] Widget border width
   - [ ] Widget shadow/glow toggle
   - [ ] Widget spacing
   - [ ] Widget shape
   - [ ] Spatial effects
   - [ ] Save theme
   - [ ] Color extraction (upload)
   - [ ] Color extraction (URL)

### 3. Check for Errors
- Check browser console (F12) for JavaScript errors
- Check PHP error logs in cPanel
- Verify all API endpoints respond correctly

---

## Database Migration

**Before testing, ensure database migration is complete!**

If `check_migration.php` shows incomplete:
1. Run SQL from `DATABASE_MIGRATION_INSTRUCTIONS.md` in phpMyAdmin
2. Re-check status at `check_migration.php`

---

## File Permissions

Ensure proper permissions:
- PHP files: `644` or `755`
- Directories: `755`
- Database folder: `755`

---

## Troubleshooting

### Issue: "Class not found" errors
**Solution:** Verify all class files are uploaded to correct directories

### Issue: "Database column doesn't exist"
**Solution:** Run database migration SQL

### Issue: Features not appearing in editor
**Solution:** Clear browser cache, verify `editor.php` is updated

### Issue: API endpoints returning errors
**Solution:** Check PHP error logs, verify file permissions

---

## Post-Deployment

✅ Database migration complete  
✅ All files deployed  
✅ Status checker working  
✅ Theme features functional  

**Status:** Ready for user testing! 🚀

