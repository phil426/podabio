#!/bin/bash
# Simple Git Deployment (if server has Git configured)
# Run this directly on the server or via SSH

echo "=========================================="
echo "Git Deployment - Widget Features"
echo "=========================================="
echo ""

PROJECT_DIR="/home/u810635266/domains/getphily.com/public_html/"

cd "$PROJECT_DIR" || exit 1

echo "📦 Pulling latest code from GitHub..."
echo ""

# Use token for authentication
GITHUB_TOKEN="REMOVED"

# Pull using token in URL
git pull https://$GITHUB_TOKEN@github.com/phil426/podn-bio.git main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Code updated successfully!"
    echo ""
    echo "📋 Files deployed:"
    ls -la database/migrate_add_featured_widgets.php 2>/dev/null && echo "   ✓ Migration file exists" || echo "   ⚠️  Migration file missing"
    echo ""
    echo "Next step: Run migration"
    echo "   Visit: https://getphily.com/database/migrate_add_featured_widgets.php"
else
    echo ""
    echo "❌ Git pull failed"
    echo "   Check Git configuration on server"
    exit 1
fi

