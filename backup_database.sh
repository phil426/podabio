#!/bin/bash
# Database Backup Script for Widget Improvements Feature Branch
# Backs up widgets table and related tables before migration

# Load database credentials from config
DB_HOST="srv556.hstgr.io"
DB_NAME="u810635266_site_podnbio"
DB_USER="u810635266_podnbio"
DB_PASS="6;hhwddG"

# Create backup directory
BACKUP_DIR="database_backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/widgets_backup_$TIMESTAMP.sql"

mkdir -p "$BACKUP_DIR"

echo "Creating database backup..."
echo "Backup file: $BACKUP_FILE"

# Backup widgets table and analytics_events table (for widget analytics)
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  widgets \
  analytics_events \
  > "$BACKUP_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "✓ Backup completed successfully: $BACKUP_FILE"
    echo "Backup size: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    echo "✗ Backup failed. Check error messages above."
    exit 1
fi

# Also create a rollback SQL file for the migration
ROLLBACK_FILE="$BACKUP_DIR/rollback_widgets_$TIMESTAMP.sql"
cat > "$ROLLBACK_FILE" << 'EOF'
-- Rollback script for widgets table featured fields
-- Run this if you need to revert the migration

-- Remove featured fields
ALTER TABLE widgets 
DROP COLUMN IF EXISTS featured_effect,
DROP COLUMN IF EXISTS is_featured;

-- If analytics_events doesn't have widget_id, no action needed
-- If it does and you need to remove it:
-- ALTER TABLE analytics_events DROP COLUMN IF EXISTS widget_id;
EOF

echo "✓ Rollback script created: $ROLLBACK_FILE"

