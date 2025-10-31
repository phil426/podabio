#!/bin/bash
# Simple Deployment Script - Runs all steps in one command

ssh -p 65002 u810635266@82.198.236.40 << 'DEPLOY'
    cd /home/u810635266/domains/getphily.com/public_html/
    echo "📦 Pulling latest code..."
    git pull origin main
    echo "🗄️  Running database migration..."
    php database/migrate.php
    echo "✅ Deployment complete!"
DEPLOY

