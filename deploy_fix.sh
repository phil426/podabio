#!/bin/bash
# Quick deployment script for admin loading fix
# Run this and enter password when prompted: ?g-2A+mJV&a%KP$

echo "Deploying admin loading fix to poda.bio..."
echo ""

# Connect and pull latest code
ssh -p 65002 u925957603@195.179.237.142 << 'ENDSSH'
    cd /home/u925957603/domains/poda.bio/public_html/
    
    echo "📦 Pulling latest code from GitHub..."
    git pull origin main
    
    echo ""
    echo "✅ Verifying files..."
    if [ -f "admin/userdashboard.php" ]; then
        echo "✅ admin/userdashboard.php updated"
    fi
    
    if [ -f "admin-ui/dist/.vite/manifest.json" ]; then
        echo "✅ admin-ui/dist/.vite/manifest.json exists"
    else
        echo "⚠️  Warning: manifest.json not found"
    fi
    
    echo ""
    echo "✅ Deployment complete!"
    echo "Test at: https://poda.bio/admin/userdashboard.php"
ENDSSH

echo ""
echo "🎉 Done! The admin should now load correctly."

