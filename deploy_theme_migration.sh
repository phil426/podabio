#!/bin/bash
# Deploy Theme System Migration
# This script uploads the migration file and runs it on the server

HOST="82.198.236.40"
USER="u810635266"
PASSWORD="@1318Redwood"
REMOTE_DIR="domains/getphily.com/public_html"

echo "================================================"
echo "Theme System Migration Deployment"
echo "================================================"
echo ""

# Upload migration file
echo "1. Uploading migration file..."
sshpass -p "$PASSWORD" scp -o StrictHostKeyChecking=no \
    database/migrate_theme_system.php \
    ${USER}@${HOST}:${REMOTE_DIR}/database/

if [ $? -eq 0 ]; then
    echo "   ✓ Migration file uploaded"
else
    echo "   ✗ Failed to upload migration file"
    exit 1
fi

echo ""
echo "2. Running database migration..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    ${USER}@${HOST} "cd ${REMOTE_DIR} && php database/migrate_theme_system.php"

if [ $? -eq 0 ]; then
    echo ""
    echo "================================================"
    echo "✅ Migration completed successfully!"
    echo "================================================"
else
    echo ""
    echo "================================================"
    echo "⚠️  Migration completed with warnings"
    echo "================================================"
fi

