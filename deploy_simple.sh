#!/bin/bash
# Simple Deployment Script - Runs all steps in one command
# ⚠️  DEPRECATED: This script is for the old getphily.com server
# Use ./deploy_poda_bio.sh for poda.bio deployments
# Uses sshpass for password authentication

set -e

# Server details (OLD SERVER - getphily.com)
SSH_HOST="u810635266@82.198.236.40"
SSH_PORT="65002"

# Check if sshpass is available
SSHPASS_CMD="/opt/homebrew/bin/sshpass"
if [ ! -f "$SSHPASS_CMD" ]; then
    SSHPASS_CMD="sshpass"
    if ! command -v sshpass &> /dev/null; then
        echo "❌ Error: sshpass is not installed."
        echo "   Install it with: brew install hudochenkov/sshpass/sshpass"
        echo "   Or use manual deployment: ssh -p $SSH_PORT $SSH_HOST"
        exit 1
    fi
fi

# Get SSH password
if [ -z "$SSH_PASS" ]; then
    echo "🔐 Enter SSH password for $SSH_HOST:"
    read -s SSH_PASS
    echo ""
fi

echo "📡 Connecting to server..."
echo ""

# Deploy via SSH using sshpass
$SSHPASS_CMD -p "$SSH_PASS" ssh -p $SSH_PORT -o StrictHostKeyChecking=accept-new $SSH_HOST << 'DEPLOY'
    set -e
    cd /home/u810635266/domains/getphily.com/public_html/  # OLD SERVER
    echo "📦 Pulling latest code..."
    git pull origin main
    echo "🗄️  Running database migration..."
    php database/migrate.php 2>/dev/null || echo "⚠️  Migration script not found or already completed"
    echo "✅ Deployment complete!"
DEPLOY

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Deployment successful!"
else
    echo ""
    echo "❌ Deployment failed. Please check the errors above."
    exit 1
fi

