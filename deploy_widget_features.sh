#!/bin/bash
# Deployment Script for Widget Features
# This script deploys all widget improvements via Git pull

set -e

echo "=========================================="
echo "Widget Features Deployment"
echo "=========================================="
echo ""

# Server details
SSH_HOST="u810635266@82.198.236.40"
SSH_PORT="65002"
PROJECT_DIR="/home/u810635266/domains/getphily.com/public_html/"

echo "📡 Connecting to Hostinger server..."
echo ""

# Deploy via SSH
ssh -p $SSH_PORT $SSH_HOST << 'ENDSSH'
    set -e
    cd /home/u810635266/domains/getphily.com/public_html/
    
    echo "📦 Step 1: Pulling latest code from GitHub..."
    git pull origin main
    
    if [ $? -eq 0 ]; then
        echo "✅ Code updated successfully"
    else
        echo "❌ Failed to pull code"
        exit 1
    fi
    
    echo ""
    echo "📋 Step 2: Files deployed:"
    echo "   - editor.php (widget features)"
    echo "   - page.php (featured widgets)"
    echo "   - classes/Page.php (widget methods)"
    echo "   - classes/WidgetRenderer.php (blog widgets)"
    echo "   - classes/WidgetRegistry.php (new widgets)"
    echo "   - classes/Analytics.php (widget analytics)"
    echo "   - api/widgets.php (featured widget support)"
    echo "   - api/analytics.php (analytics API)"
    echo "   - api/blog_categories.php (blog widget support)"
    echo "   - click.php (widget click tracking)"
    echo ""
    
    echo "🗄️  Step 3: Database migration reminder..."
    echo "   Visit: https://getphily.com/database/migrate_add_featured_widgets.php"
    echo "   (Run this to enable featured widget features)"
    echo ""
    
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Run database migration: https://getphily.com/database/migrate_add_featured_widgets.php"
    echo "2. Test new features in editor"
    echo "3. Verify analytics dashboard"
    echo "4. Test blog widgets"
    echo ""
ENDSSH

if [ $? -eq 0 ]; then
    echo "✅ Deployment completed successfully!"
else
    echo "❌ Deployment failed. Please check the error messages above."
    exit 1
fi

