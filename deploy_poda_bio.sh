#!/bin/bash
# Deployment Script for poda.bio (Hostinger)
# Run this script from your local machine - it will SSH into Hostinger and deploy

set -e

echo "=========================================="
echo "PodInBio Deployment Script"
echo "Deploying to poda.bio (Hostinger)"
echo "=========================================="
echo ""

# Server details
SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_KEY_FILE="$HOME/.ssh/id_ed25519_podabio"
PROJECT_DIR="/home/u925957603/domains/poda.bio/public_html/"

echo "📡 Connecting to Hostinger server (poda.bio)..."
echo ""

# Check if SSH key exists
if [ -f "$SSH_KEY_FILE" ]; then
    SSH_OPTS="-i $SSH_KEY_FILE"
    echo "✅ Using SSH key authentication"
else
    SSH_OPTS=""
    echo "⚠️  SSH key not found. Using password authentication."
    echo "   Run ./setup_ssh_key_manual.sh to set up SSH keys for passwordless access."
    echo ""
    read -p "Press Enter to continue with password authentication..."
fi

# Deploy via SSH
ssh $SSH_OPTS -p $SSH_PORT -o StrictHostKeyChecking=accept-new $SSH_HOST << 'ENDSSH'
    set -e
    cd /home/u925957603/domains/poda.bio/public_html/
    
    echo "🔍 Step 0: Verifying git remote configuration..."
    CURRENT_REMOTE=$(git remote get-url origin)
    EXPECTED_REMOTE="https://github.com/phil426/podabio.git"
    
    if [ "$CURRENT_REMOTE" != "$EXPECTED_REMOTE" ]; then
        echo "⚠️  Warning: Git remote is incorrect!"
        echo "   Current: $CURRENT_REMOTE"
        echo "   Expected: $EXPECTED_REMOTE"
        echo "   Fixing remote URL..."
        git remote set-url origin "$EXPECTED_REMOTE"
        echo "✅ Remote URL updated"
    else
        echo "✅ Git remote is correct"
    fi
    echo ""
    
    echo "📦 Step 1: Pulling latest code from GitHub..."
    git pull origin main
    
    if [ $? -eq 0 ]; then
        echo "✅ Code updated successfully"
    else
        echo "❌ Failed to pull code"
        exit 1
    fi
    
    echo ""
    echo "📁 Step 2: Verifying admin-ui/dist directory..."
    if [ -d "admin-ui/dist" ] && [ -f "admin-ui/dist/.vite/manifest.json" ]; then
        echo "✅ React app build found"
    else
        echo "⚠️  Warning: admin-ui/dist not found. React app may not load."
        echo "   Make sure to build and commit admin-ui/dist before deploying."
    fi
    
    echo ""
    echo "🔐 Step 3: Setting file permissions for uploads directories..."
    chmod 755 uploads/ 2>/dev/null || echo "   uploads/ directory not found (will be created on first upload)"
    chmod 755 uploads/profiles/ 2>/dev/null || echo "   uploads/profiles/ directory not found"
    chmod 755 uploads/backgrounds/ 2>/dev/null || echo "   uploads/backgrounds/ directory not found"
    chmod 755 uploads/thumbnails/ 2>/dev/null || echo "   uploads/thumbnails/ directory not found"
    chmod 755 uploads/blog/ 2>/dev/null || echo "   uploads/blog/ directory not found"
    echo "✅ File permissions set"
    
    echo ""
    echo "🔍 Step 4: Verifying configuration files..."
    if [ -f "config/database.php" ]; then
        echo "✅ config/database.php exists"
    else
        echo "⚠️  Warning: config/database.php not found."
        echo "   Create this file with database credentials (see docs/DEPLOYMENT_PODA_BIO.md)"
    fi
    
    if [ -f "config/constants.php" ]; then
        if grep -q "https://poda.bio" config/constants.php; then
            echo "✅ config/constants.php has correct APP_URL"
        else
            echo "⚠️  Warning: config/constants.php may not have correct APP_URL"
        fi
    else
        echo "❌ Error: config/constants.php not found"
        exit 1
    fi
    
    echo ""
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Test PHP backend: https://poda.bio/index.php"
    echo "2. Test admin panel: https://poda.bio/admin/react-admin.php"
    echo "3. Verify React app loads (check browser console)"
    echo "4. Test database connectivity"
    echo "5. Test file uploads"
    echo ""
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 All done! Deployment successful."
    echo ""
    echo "Note: If this is the first deployment, make sure to:"
    echo "  - Create config/database.php with MySQL credentials"
    echo "  - Import database schema (database/schema.sql)"
    echo "  - Set up file permissions for uploads directories"
    echo ""
    echo "See docs/DEPLOYMENT_PODA_BIO.md for detailed instructions."
else
    echo ""
    echo "❌ Deployment failed. Please check the errors above."
    exit 1
fi

