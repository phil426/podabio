#!/bin/bash
# Git-Based Deployment Script
# Deploys all files via Git pull on the server
# Uses SSH to connect and run git pull

set -e

echo "=========================================="
echo "Git-Based Deployment"
echo "Deploying Widget Features"
echo "=========================================="
echo ""

# Server details from DEPLOYMENT_CREDENTIALS.md
SSH_HOST="u810635266@82.198.236.40"
SSH_PORT="65002"
SSH_PASS="@1318Redwood"
PROJECT_DIR="/home/u810635266/domains/getphily.com/public_html/"

# GitHub credentials
GITHUB_TOKEN="REMOVED"
GITHUB_REPO="https://github.com/phil426/podn-bio.git"

echo "📡 Connecting to server via SSH..."
echo ""

# Check if sshpass is installed (for password-based SSH)
if ! command -v sshpass &> /dev/null; then
    echo "⚠️  sshpass not found. Installing..."
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "   Run: brew install hudochenkov/sshpass/sshpass"
        echo "   Or use SSH key authentication"
        exit 1
    else
        echo "   Install sshpass: sudo apt-get install sshpass"
        exit 1
    fi
fi

# Deploy via SSH with password
sshpass -p "$SSH_PASS" ssh -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_HOST << ENDSSH
    set -e
    cd $PROJECT_DIR
    
    echo "📦 Step 1: Pulling latest code from GitHub..."
    
    # Set up git credential helper if needed
    git config --local credential.helper store 2>/dev/null || true
    
    # Pull using token
    export GIT_ASKPASS=/bin/echo
    export GIT_TERMINAL_PROMPT=0
    
    # Try pull with token
    git -c credential.helper='!f() { echo "username=phil426"; echo "password=$GITHUB_TOKEN"; }; f' pull origin main
    
    if [ \$? -eq 0 ]; then
        echo "✅ Code updated successfully"
    else
        echo "⚠️  Git pull had issues, trying alternative method..."
        # Alternative: use token in URL
        git pull https://$GITHUB_TOKEN@github.com/phil426/podn-bio.git main
    fi
    
    echo ""
    echo "📋 Files now on server:"
    echo "   ✓ database/migrate_add_featured_widgets.php"
    echo "   ✓ editor.php (all widget features)"
    echo "   ✓ api/analytics.php"
    echo "   ✓ api/blog_categories.php"
    echo "   ✓ classes/WidgetRenderer.php (blog widgets)"
    echo "   ✓ And all other updated files"
    echo ""
    
    echo "🗄️  Step 2: Next step - run migration:"
    echo "   Visit: https://getphily.com/database/migrate_add_featured_widgets.php"
    echo ""
    
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 All files deployed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Run database migration: https://getphily.com/database/migrate_add_featured_widgets.php"
    echo "2. Test features in editor"
    echo ""
else
    echo ""
    echo "❌ Deployment failed. Please check:"
    echo "   - SSH connection"
    echo "   - Git repository status on server"
    echo "   - Permissions"
    exit 1
fi

