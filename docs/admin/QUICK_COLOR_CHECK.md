# Quick Color Check Guide

## Run the Database Check Script

After changing a color in the UI, run this script to verify if the colors are being saved:

```bash
php check_theme_colors.php
```

Or if you know your user ID:

```bash
php check_theme_colors.php YOUR_USER_ID
```

## What to Look For

The script will show:

1. **All your themes** with their IDs and names
2. **Typography tokens colors** - Should show:
   - `color.heading: #YOUR_COLOR` ✅
   - `color.body: #YOUR_COLOR` ✅
3. **Color tokens** (for comparison)
4. **Which pages use which themes**

## Expected Output

If colors are being saved correctly, you should see:

```
Typography Tokens:
  ✅ color.heading: #FF0000
  ✅ color.body: #00FF00
```

If colors are NOT being saved, you'll see:

```
Typography Tokens:
  ❌ typography_tokens.color NOT FOUND
```

## Next Steps

1. **Change a color** in the UI (Colors tab → Page title or Page body color)
2. **Wait for auto-save** (about 1 second)
3. **Run the check script**: `php check_theme_colors.php`
4. **Share the output** so we can see what's happening

## Troubleshooting

If the script shows "NOT SET" or "NOT FOUND":
- The colors aren't being saved to the database
- Check the PHP error log for save errors
- Verify the `typography_tokens` column exists in the `themes` table

If the script shows the colors correctly but they're not appearing on the page:
- The issue is in CSS generation or application
- Check the PHP error log for CSS generation logs
- Verify the CSS variables are being generated

