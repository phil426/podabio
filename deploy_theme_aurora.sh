#!/bin/bash
# Aurora Theme Deployment Script
# Connects to the production server and runs the Aurora installer.

set -e

echo "=========================================="
echo "Aurora Theme Deployment"
echo "=========================================="
echo ""

SSH_HOST="u810635266@82.198.236.40"
SSH_PORT="65002"
SSH_PASS="@1318Redwood"
PROJECT_DIR="/home/u810635266/domains/getphily.com/public_html"

if ! command -v sshpass &> /dev/null; then
    echo "⚠️  sshpass not found. Install it (brew install hudochenkov/sshpass/sshpass or apt-get install sshpass)."
    exit 1
fi

echo "📡 Connecting to server via SSH..."
echo ""

sshpass -p "$SSH_PASS" ssh -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_HOST << 'ENDSSH'
    set -e
    cd /home/u810635266/domains/getphily.com/public_html

    echo "Running Aurora theme installer..."
    php database/add_theme_aurora.php

    echo "Checking theme list..."
    mysql -u u810635266_podnbio -p'6;hhwddG' u810635266_site_podnbio -e "SELECT id, name FROM themes WHERE name = 'Aurora Skies';"
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Aurora theme deployment finished."
    echo "   You can now select 'Aurora Skies' in the Appearance editor."
else
    echo ""
    echo "❌ Aurora theme deployment failed."
    exit 1
fi
