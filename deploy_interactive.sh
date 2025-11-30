#!/bin/bash
# Interactive Deployment Script for poda.bio
# This script will prompt you for the SSH password once

set -e

SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_KEY_FILE="$HOME/.ssh/id_ed25519_podabio"

echo "=========================================="
echo "PodaBio Deployment Script"
echo "Deploying to poda.bio (Hostinger)"
echo "=========================================="
echo ""

# Check if SSH key exists
if [ -f "$SSH_KEY_FILE" ]; then
    SSH_OPTS="-i $SSH_KEY_FILE"
    echo "✅ Using SSH key authentication"
else
    SSH_OPTS=""
    echo "⚠️  SSH key not found. You will be prompted for password."
    echo "   Password: ?g-2A+mJV&a%KP$"
    echo "   (Run ./setup_ssh_key_manual.sh first to avoid password prompts)"
    echo ""
fi

# Deploy via SSH
ssh $SSH_OPTS -p $SSH_PORT -o StrictHostKeyChecking=accept-new $SSH_HOST << 'ENDSSH'
    set -e
    cd /home/u925957603/domains/poda.bio/public_html/
    
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
        echo "✅ React app build found with manifest.json"
        ls -lh admin-ui/dist/.vite/manifest.json
    else
        echo "⚠️  Warning: admin-ui/dist/.vite/manifest.json not found."
        echo "   Checking what exists..."
        ls -la admin-ui/dist/ 2>/dev/null || echo "   admin-ui/dist/ directory doesn't exist"
    fi
    
    echo ""
    echo "🔍 Step 3: Verifying admin/userdashboard.php..."
    if [ -f "admin/userdashboard.php" ]; then
        if grep -q "\.vite/manifest\.json" admin/userdashboard.php; then
            echo "✅ admin/userdashboard.php has correct manifest path"
        else
            echo "⚠️  Warning: admin/userdashboard.php may not have updated manifest path"
        fi
    else
        echo "❌ Error: admin/userdashboard.php not found"
        exit 1
    fi
    
    echo ""
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Test admin panel: https://poda.bio/admin/userdashboard.php"
    echo "2. Check browser console for any errors"
    echo "3. Verify React app loads (should not show 'Loading...' message)"
    echo ""
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Deployment successful!"
    echo ""
    echo "Visit https://poda.bio/admin/userdashboard.php to test"
else
    echo ""
    echo "❌ Deployment failed. Please check the errors above."
    exit 1
fi

