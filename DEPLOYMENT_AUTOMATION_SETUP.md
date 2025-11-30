# Deployment Automation Setup

## Current Status
✅ SSH key generated: `~/.ssh/id_ed25519_podabio`
✅ Deployment scripts updated to use SSH keys
⚠️  SSH key needs to be manually added to server (one-time setup)

## One-Time Manual Setup

### Step 1: Manually SSH into Server
```bash
ssh -p 65002 u925957603@195.179.237.142
# Enter password: [REDACTED - Check secure credential storage]
```

### Step 2: Add SSH Key to Server
Once connected, run these commands on the server:

```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAINByOAUm+7uY9JLMixCgxCLUo7Rj9m1FcDQvZbvHZLbb poda.bio-deployment" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
exit
```

### Step 3: Test SSH Key Authentication
From your local machine:
```bash
ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142 "echo 'SSH key works!'"
```

If this works without a password prompt, you're all set!

## Automated Deployment

Once SSH keys are set up, you can use:

### Quick Deployment
```bash
./deploy_poda_bio.sh
```

This will:
1. Connect via SSH key (no password needed)
2. Pull latest code from GitHub
3. Verify files are deployed correctly
4. Check that manifest.json exists

### What Gets Deployed
- `admin/react-admin.php` (fixed manifest path)
- `admin-ui/dist/.vite/manifest.json` (production manifest)
- `admin-ui/dist/assets/*` (built JS/CSS files)

## Troubleshooting

### SSH Key Not Working
1. Verify key exists: `ls -la ~/.ssh/id_ed25519_podabio*`
2. Check key permissions: `chmod 600 ~/.ssh/id_ed25519_podabio`
3. Test connection: `ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142`

### Deployment Fails
1. Check GitHub has latest code: `git log --oneline -5`
2. Verify server can access GitHub: SSH in and run `git pull origin main`
3. Check file permissions on server

### Admin Still Not Loading
1. Verify manifest exists: `ls -la admin-ui/dist/.vite/manifest.json`
2. Check PHP file: `grep manifest admin/react-admin.php`
3. Check browser console for errors
4. Verify files are accessible: `https://poda.bio/admin-ui/dist/.vite/manifest.json`

