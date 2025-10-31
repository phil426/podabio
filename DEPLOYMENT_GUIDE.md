# Deployment Guide: Social Icons Migration

## ✅ Code Status
- All code changes committed and pushed to GitHub
- Migration script created and ready

## 🚀 Deployment Steps

### Option 1: Automated Deployment (Recommended)

**Via SSH:**
```bash
# Connect to server
ssh -p 65002 u810635266@82.198.236.40

# Navigate to project directory
cd /home/u810635266/domains/getphily.com/public_html/podnbio/

# Run deployment script
bash deploy.sh
```

### Option 2: Manual Steps

**Step 1: Pull Latest Code**
```bash
ssh -p 65002 u810635266@82.198.236.40
cd /home/u810635266/domains/getphily.com/public_html/podnbio/
git pull origin main
```

**Step 2: Run Database Migration**

**Option A: Via Web Browser (Easiest)**
1. Open: `https://getphily.com/podnbio/database/migrate.php`
2. Click "Run Migration"
3. Verify success message
4. **Delete the migrate.php file after migration**

**Option B: Via Command Line**
```bash
php database/migrate.php
```

**Option C: Direct SQL**
```bash
mysql -h srv556.hstgr.io -u u810635266_podnbio -p u810635266_site_podnbio <<EOF
RENAME TABLE podcast_directories TO social_icons;
ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages';
EOF
```

## ✅ Verification Steps

1. **Check Editor**
   - Go to: `https://getphily.com/podnbio/editor.php`
   - Verify sidebar shows "Social Icons" (with share icon) instead of "Podcast Directories"

2. **Test Adding Icon**
   - Click "Social Icons" tab
   - Click "Add Social Icon"
   - Select a platform (e.g., Facebook, Instagram, Spotify)
   - Add URL and save
   - Verify it appears in the list

3. **Check Public Page**
   - Visit a user's page
   - Verify social icons display correctly (if any exist)

## 🔒 Security: Clean Up After Migration

**IMPORTANT:** After successful migration, delete the migration script:
```bash
rm database/migrate.php
rm database/run_migration.php  # If exists
```

Or via file manager in Hostinger control panel.

## 📝 What Changed

- ✅ Database table: `podcast_directories` → `social_icons`
- ✅ Editor UI: "Podcast Directories" → "Social Icons"
- ✅ Platform list updated with social media + podcast platforms
- ✅ All PHP code updated to use new terminology
- ✅ Legacy methods kept for backwards compatibility

## 🆘 Troubleshooting

**If migration says "already completed":**
- Table was already renamed - this is safe, no action needed

**If git pull fails:**
- Check SSH access
- Verify you're in the correct directory
- Try: `git status` to see current state

**If migration fails:**
- Check database credentials in `config/database.php`
- Verify database connection
- Check error logs

## 📞 Support

If you encounter any issues:
1. Check error messages carefully
2. Verify database connection
3. Ensure all code files are updated
4. Check PHP error logs

