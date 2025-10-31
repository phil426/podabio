#!/bin/bash
# Quick Deployment Script for Hostinger
# Run this script from your local machine - it will SSH into Hostinger and deploy

set -e

echo "=========================================="
echo "Hostinger Deployment Script"
echo "Deploying Social Icons Migration"
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
    echo "🗄️  Step 2: Checking database migration status..."
    
    # Check if podcast_directories table exists
    php -r "
    require_once 'config/database.php';
    try {
        \$pdo = getDB();
        \$tableExists = \$pdo->query(\"SHOW TABLES LIKE 'podcast_directories'\")->rowCount() > 0;
        \$newTableExists = \$pdo->query(\"SHOW TABLES LIKE 'social_icons'\")->rowCount() > 0;
        
        if (\$newTableExists) {
            echo '✅ Migration already completed - social_icons table exists\n';
            exit(0);
        } elseif (\$tableExists) {
            echo '📋 podcast_directories table found, running migration...\n';
            
            // Run migration
            \$pdo->beginTransaction();
            try {
                \$pdo->exec('RENAME TABLE podcast_directories TO social_icons');
                \$pdo->exec(\"ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages'\");
                \$pdo->commit();
                
                \$count = \$pdo->query('SELECT COUNT(*) as count FROM social_icons')->fetch()['count'];
                echo \"✅ Migration completed successfully! Migrated \$count records.\n\";
            } catch (Exception \$e) {
                \$pdo->rollBack();
                throw \$e;
            }
        } else {
            echo '⚠️  Neither table exists. Please check your database.\n';
            exit(1);
        }
    } catch (Exception \$e) {
        echo '❌ Migration failed: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "
    
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
        echo ""
    else
        echo "❌ Migration failed. Please check the error above."
        exit 1
    fi
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 All done! Deployment successful."
else
    echo ""
    echo "❌ Deployment failed. Please check the errors above."
    exit 1
fi

