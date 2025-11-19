#!/bin/bash
# Complete Setup and Deployment Script
# This will guide you through SSH key setup, then deploy automatically

set -e

SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_KEY_FILE="$HOME/.ssh/id_ed25519_podabio"
PUBLIC_KEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAINByOAUm+7uY9JLMixCgxCLUo7Rj9m1FcDQvZbvHZLbb poda.bio-deployment"

echo "=========================================="
echo "PodaBio - SSH Key Setup & Deployment"
echo "=========================================="
echo ""

# Check if SSH key exists
if [ ! -f "$SSH_KEY_FILE" ]; then
    echo "❌ SSH key not found. Generating..."
    ssh-keygen -t ed25519 -C "poda.bio-deployment" -f "$SSH_KEY_FILE" -N "" -q
    echo "✅ SSH key generated"
fi

# Test if SSH key authentication works
echo "🔐 Testing SSH key authentication..."
if ssh -i "$SSH_KEY_FILE" -p $SSH_PORT -o ConnectTimeout=5 -o StrictHostKeyChecking=no -o BatchMode=yes $SSH_HOST "echo 'SSH key works'" 2>/dev/null; then
    echo "✅ SSH key authentication is already set up!"
    KEY_SETUP_DONE=true
else
    echo "⚠️  SSH key not set up on server yet."
    echo ""
    echo "📋 ONE-TIME MANUAL SETUP REQUIRED:"
    echo "   You need to manually add the SSH key to the server."
    echo ""
    echo "   Run these commands:"
    echo ""
    echo "   1. Connect to server:"
    echo "      ssh -p $SSH_PORT $SSH_HOST"
    echo "      (Password: *1318Redwood)"
    echo ""
    echo "   2. Once connected, run:"
    echo "      mkdir -p ~/.ssh"
    echo "      chmod 700 ~/.ssh"
    echo "      echo '$PUBLIC_KEY' >> ~/.ssh/authorized_keys"
    echo "      chmod 600 ~/.ssh/authorized_keys"
    echo "      exit"
    echo ""
    echo "   3. Then come back and run this script again!"
    echo ""
    read -p "Press Enter after you've completed the setup above, or Ctrl+C to cancel..."
    
    # Test again
    if ssh -i "$SSH_KEY_FILE" -p $SSH_PORT -o ConnectTimeout=5 -o StrictHostKeyChecking=no -o BatchMode=yes $SSH_HOST "echo 'SSH key works'" 2>/dev/null; then
        echo "✅ SSH key authentication verified!"
        KEY_SETUP_DONE=true
    else
        echo "❌ SSH key authentication still not working."
        echo "   Please verify you completed the setup steps correctly."
        exit 1
    fi
fi

echo ""
echo "=========================================="
echo "Deploying to poda.bio..."
echo "=========================================="
echo ""

# Deploy via SSH
ssh -i "$SSH_KEY_FILE" -p $SSH_PORT -o StrictHostKeyChecking=accept-new $SSH_HOST << 'ENDSSH'
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
    echo "📁 Step 2: Verifying deployment files..."
    
    # Check manifest
    if [ -f "admin-ui/dist/.vite/manifest.json" ]; then
        echo "✅ admin-ui/dist/.vite/manifest.json exists"
        echo "   Content:"
        cat admin-ui/dist/.vite/manifest.json | head -5
    else
        echo "❌ ERROR: admin-ui/dist/.vite/manifest.json not found!"
        echo "   This means the React app won't load."
        exit 1
    fi
    
    # Check PHP file
    if [ -f "admin/userdashboard.php" ]; then
        if grep -q "\.vite/manifest\.json" admin/userdashboard.php; then
            echo "✅ admin/userdashboard.php has correct manifest path"
        else
            echo "⚠️  WARNING: admin/userdashboard.php may not have updated manifest path"
        fi
    else
        echo "❌ ERROR: admin/userdashboard.php not found"
        exit 1
    fi
    
    # Check built assets
    if [ -d "admin-ui/dist/assets" ] && [ "$(ls -A admin-ui/dist/assets/*.js 2>/dev/null)" ]; then
        echo "✅ Built JavaScript files found"
        ls -lh admin-ui/dist/assets/*.js | head -2
    else
        echo "⚠️  WARNING: No JavaScript files found in admin-ui/dist/assets/"
    fi
    
    if [ -d "admin-ui/dist/assets" ] && [ "$(ls -A admin-ui/dist/assets/*.css 2>/dev/null)" ]; then
        echo "✅ Built CSS files found"
        ls -lh admin-ui/dist/assets/*.css | head -1
    else
        echo "⚠️  WARNING: No CSS files found in admin-ui/dist/assets/"
    fi
    
    echo ""
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Visit: https://poda.bio/admin/userdashboard.php"
    echo "2. Check browser console for any errors"
    echo "3. Verify React app loads (should NOT show 'Loading...' message)"
    echo ""
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Deployment successful!"
    echo ""
    echo "✅ SSH key authentication is set up"
    echo "✅ Code deployed to server"
    echo "✅ Future deployments: Just run ./setup_and_deploy.sh"
    echo ""
    echo "Test the admin panel: https://poda.bio/admin/userdashboard.php"
else
    echo ""
    echo "❌ Deployment failed. Please check the errors above."
    exit 1
fi

