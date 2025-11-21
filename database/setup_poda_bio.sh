#!/bin/bash
# Database Setup Script for poda.bio
# Run this script on the server via SSH after initial deployment

set -e

echo "=========================================="
echo "PodaBio Database Setup - poda.bio"
echo "=========================================="
echo ""

# Database credentials
DB_HOST="srv775.hstgr.io"
DB_NAME="u925957603_podabio"
DB_USER="u925957603_pab"
DB_PASS="?g-2A+mJV&a%KP$"

echo "📊 Step 1: Importing database schema..."
if [ -f "database/schema.sql" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
    if [ $? -eq 0 ]; then
        echo "✅ Database schema imported successfully"
    else
        echo "❌ Failed to import schema"
        exit 1
    fi
else
    echo "❌ Error: database/schema.sql not found"
    exit 1
fi

echo ""
echo "📊 Step 2: Importing seed data (optional)..."
if [ -f "database/seed_data.sql" ]; then
    read -p "Do you want to import seed data? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/seed_data.sql
        if [ $? -eq 0 ]; then
            echo "✅ Seed data imported successfully"
        else
            echo "⚠️  Warning: Failed to import seed data (non-critical)"
        fi
    else
        echo "⏭️  Skipping seed data import"
    fi
else
    echo "ℹ️  No seed_data.sql found, skipping"
fi

echo ""
echo "🔍 Step 3: Verifying database connection..."
php -r "
require_once 'config/database.php';
try {
    \$pdo = getDB();
    \$tables = \$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo '✅ Database connection successful\n';
    echo '   Found ' . count(\$tables) . ' tables\n';
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo ""
echo "=========================================="
echo "✅ Database setup complete!"
echo "=========================================="





















