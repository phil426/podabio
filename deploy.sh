#!/bin/bash
# Deployment Script for Social Icons Migration
# Run this on your production server via SSH

set -e  # Exit on error

echo "=========================================="
echo "Podn.Bio Deployment Script"
echo "Social Icons Migration"
echo "=========================================="
echo ""

# Server path - CORRECT: Root is /public_html/ (no podnbio subdirectory)
PROJECT_DIR="/home/u810635266/domains/getphily.com/public_html"
cd "$PROJECT_DIR" || exit 1

echo "📦 Step 1: Pulling latest code from GitHub..."
git pull origin main

if [ $? -eq 0 ]; then
    echo "✅ Code updated successfully"
else
    echo "❌ Failed to pull code. Please check your git credentials."
    exit 1
fi

echo ""
echo "🗄️  Step 2: Running database migration..."
php database/migrate.php

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✅ Deployment Complete!"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Test the editor at: https://getphily.com/editor.php"
    echo "2. Verify 'Social Icons' tab appears in the sidebar"
    echo "3. Test adding a social icon"
    echo "4. Delete database/migrate.php for security"
    echo ""
else
    echo "❌ Migration failed. Please check the error messages above."
    exit 1
fi

