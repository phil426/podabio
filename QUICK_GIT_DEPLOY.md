# Quick Git Deployment Guide

## All Files Deployed via Git! ✅

The deployment script (`deploy_git.sh`) successfully pulled all files from GitHub to your server.

## Verified Credentials

✅ **SSH Connection:**
- Host: `u810635266@82.198.236.40`
- Port: `65002`
- Password: `@1318Redwood`
- Path: `/home/u810635266/domains/getphily.com/public_html/`

✅ **GitHub Access:**
- Token: `REMOVED`
- Repository: `https://github.com/phil426/podn-bio.git`

## Files Now Deployed

All files have been pulled including:
- ✅ `database/migrate_add_featured_widgets.php` - **This was the missing file!**
- ✅ `editor.php` - All widget features
- ✅ `api/analytics.php` - Analytics API
- ✅ `api/blog_categories.php` - Blog widget support
- ✅ `classes/WidgetRenderer.php` - Blog widgets
- ✅ `classes/WidgetRegistry.php` - New widgets
- ✅ `classes/Analytics.php` - Widget analytics
- ✅ `page.php` - Featured widgets rendering
- ✅ And all other updated files

## Next Step: Run Migration

Now that the file is deployed, run the migration:

1. **Visit:** `https://getphily.com/database/migrate_add_featured_widgets.php`
2. The page should load (no more 404!)
3. It will add the featured widget columns to your database

## Future Deployments

To deploy in the future, simply run:

```bash
./deploy_git.sh
```

Or manually via SSH:

```bash
ssh -p 65002 u810635266@82.198.236.40
cd /home/u810635266/domains/getphily.com/public_html/
git pull https://REMOVED@github.com/phil426/podn-bio.git main
```

## Security Note

The credentials are stored in:
- `DEPLOYMENT_CREDENTIALS.md` (local only, not in Git)
- `deploy_git.sh` (contains credentials, but only for deployment)

Consider using SSH keys instead of passwords for better security in production.

