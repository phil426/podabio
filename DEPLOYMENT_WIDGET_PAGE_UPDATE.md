# Deployment Guide: Widget & Page Styling Update

This update includes comprehensive enhancements to the theme system with widget/page separation, enhanced theme cards, and a complete theme redesign.

## Files to Deploy

Deploy ALL of the following files to `/public_html/`:

### Core Files
1. `editor.php` - Complete Appearance tab reorganization
2. `page.php` - Widget CSS integration with new CSS variables
3. `api/page.php` - Updated to handle new widget/page fields
4. `api/themes.php` - Theme API (no changes, but should verify)

### Classes
5. `classes/Theme.php` - Widget/page font methods added
6. `classes/ThemeCSSGenerator.php` - New CSS variables for widget styling
7. `classes/Page.php` - Allowed fields updated

### Includes
8. `includes/theme-helpers.php` - New helper functions

### Database
9. `database/redesign_themes.php` - Theme redesign script

## Deployment Steps

### Step 1: Deploy Files

Upload all files using your preferred method (cPanel File Manager, SFTP, etc.)

### Step 2: Verify Database Migration

The database migration should already be complete from the previous deployment. To verify, check that both `themes` and `pages` tables have these columns:

- `widget_background VARCHAR(500)`
- `widget_border_color VARCHAR(500)`
- `widget_primary_font VARCHAR(100)`
- `widget_secondary_font VARCHAR(100)`
- `page_primary_font VARCHAR(100)`
- `page_secondary_font VARCHAR(100)`

### Step 3: Run Theme Redesign Script

Execute the theme redesign script to create the new 12 themes:

**Via SSH:**
```bash
cd /home/u810635266/domains/getphily.com/public_html
php database/redesign_themes.php
```

**Via Browser:**
Navigate to: `https://getphily.com/database/redesign_themes.php`

You should see output like:
```
Theme Redesign Script
=====================

1. Deleting system themes...
   ✓ Deleted X system themes

2. Creating 12 new themes...
   ✓ Created theme: Ocean Breeze
   ✓ Created theme: Sunset Glow
   ✓ Created theme: Forest Canopy
   ... (etc)

3. Verification...
   ✓ Total system themes: 12

✅ Theme redesign complete!
```

### Step 4: Test the Frontend

1. Login to the editor
2. Navigate to the Appearance tab
3. Verify:
   - Theme cards show font previews
   - Widget settings button (⚙️) works on theme cards
   - Drawer slider opens when clicking widget settings
   - Page Styling section includes all new fields
   - Widget Styling section includes all new fields
   - Mobile preview is scaled to 75%

## New Features Summary

### 1. Enhanced Theme Cards
- Font previews showing page and widget fonts
- Widget settings button opens preview drawer
- Improved visual layout with body and footer sections

### 2. Widget Settings Drawer
- Click gear icon on any theme card to preview widget settings
- Shows sample widget with actual fonts and colors
- Displays background, border, font, and structure settings

### 3. Page Styling Section
- Page Background (solid or gradient)
- Page Colors (Primary, Secondary, Accent)
- Page Fonts (Primary/Secondary with preview)
- Layout Option

### 4. Widget Styling Section
- Widget Background (solid or gradient)
- Widget Border Color (solid or gradient)
- Widget Fonts (Primary/Secondary with preview)
- Widget Structure (border width, shadow/glow, spacing, shape)

### 5. New Themes
- 8 Gradient themes: Ocean Breeze, Sunset Glow, Forest Canopy, Midnight Sky, Coral Reef, Purple Dream, Arctic Frost, Golden Hour
- 4 Solid themes: Classic Minimal, Dark Mode, Pastel Dreams, Bold Contrast

## Troubleshooting

### Database Migration Not Complete
If the database columns are missing, run the migration script again:
- Navigate to: `https://getphily.com/database/migrate_widget_page_styling.php`

### Themes Not Creating
Check PHP error logs:
- Location: `/public_html/error_log` or via cPanel Error Log
- Ensure all required columns exist
- Verify database connection is working

### Widget Settings Not Displaying
- Check browser console for JavaScript errors
- Verify `api/themes.php` is returning theme data
- Ensure all new fields are populated in database

### CSS Variables Not Applying
- Verify `ThemeCSSGenerator` is generating correct CSS
- Check that page.php includes the CSS output
- Inspect element to see if CSS variables are present

## Rollback Plan

If issues arise:

1. **Restore Files**: Use cPanel File Manager's backup/versioning to restore previous files
2. **Database**: The new columns are nullable, so existing themes will still work
3. **Re-run Old Migration**: Previous theme structure is preserved in the `fonts` JSON column

## Post-Deployment Checklist

- [ ] All files uploaded successfully
- [ ] Database columns exist
- [ ] Theme redesign script executed
- [ ] 12 themes created
- [ ] Theme cards displaying correctly
- [ ] Widget settings drawer working
- [ ] Page styling controls functional
- [ ] Widget styling controls functional
- [ ] Auto-save working
- [ ] Mobile preview scaled to 75%
- [ ] No JavaScript errors in console
- [ ] No PHP errors in logs

## Support

If you encounter any issues during deployment:
1. Check error logs (PHP and JavaScript console)
2. Verify all files uploaded completely
3. Ensure database migration is complete
4. Test theme redesign script independently
5. Verify API endpoints are returning correct data

