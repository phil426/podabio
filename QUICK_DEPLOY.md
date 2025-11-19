# Quick Deployment Instructions - poda.bio

## Option 1: Run Deployment Script (Easiest)

From your local machine:
```bash
./deploy_poda_bio.sh
```

This will:
1. SSH into Hostinger (poda.bio server)
2. Pull latest code from GitHub
3. Verify files and permissions
4. Show deployment status

**Note:** Uses SSH key authentication (no password needed if key is set up)

## Option 2: Manual SSH Deployment

```bash
# 1. Connect to server
ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142

# 2. Navigate to project
cd /home/u925957603/domains/poda.bio/public_html/

# 3. Pull code
git pull origin main

# 4. Run migration via web browser (if needed):
#    Visit: https://poda.bio/database/migrate.php
#    Click "Run Migration"

# OR run migration via PHP CLI:
php database/migrate.php
```

## Option 3: Web-Based Deployment

1. **Pull Code via SSH:**
   ```bash
   ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142
   cd /home/u925957603/domains/poda.bio/public_html/
   git pull origin main
   ```

2. **Run Migration via Browser:**
   - Visit: `https://poda.bio/database/migrate.php`
   - Click "Run Migration"
   - Verify success
   - **Delete the migrate.php file after migration**

## Verification

After deployment:
1. Visit: `https://poda.bio/admin/userdashboard.php`
2. Verify Studio loads correctly
3. Test admin functionality
4. Verify public pages work

