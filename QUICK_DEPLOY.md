# Quick Deployment Instructions

## Option 1: Run Deployment Script (Easiest)

From your local machine:
```bash
./deploy_now.sh
```

This will:
1. SSH into Hostinger
2. Pull latest code from GitHub
3. Run database migration automatically
4. Verify everything works

**Note:** You'll need to enter your SSH password when prompted.

## Option 2: Manual SSH Deployment

```bash
# 1. Connect to server
ssh -p 65002 u810635266@82.198.236.40

# 2. Navigate to project
cd /home/u810635266/domains/getphily.com/public_html/

# 3. Pull code
git pull origin main

# 4. Run migration via web browser:
#    Visit: https://getphily.com/database/migrate.php
#    Click "Run Migration"

# OR run migration via PHP CLI:
php database/migrate.php
```

## Option 3: Web-Based Deployment

1. **Pull Code via SSH:**
   ```bash
   ssh -p 65002 u810635266@82.198.236.40
   cd /home/u810635266/domains/getphily.com/public_html/
   git pull origin main
   ```

2. **Run Migration via Browser:**
   - Visit: `https://getphily.com/database/migrate.php`
   - Click "Run Migration"
   - Verify success
   - **Delete the migrate.php file after migration**

## Verification

After deployment:
1. Visit: `https://getphily.com/editor.php`
2. Check sidebar for "Social Icons" tab (with share icon)
3. Test adding a social icon
4. Verify it displays on your public page

