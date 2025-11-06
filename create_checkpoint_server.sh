#!/bin/bash
# Version Checkpoint and Backup Script (Server)
# Podn.Bio - Server Database Backup
# Run this on the server via SSH

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get current version
CURRENT_VERSION=$(cat VERSION 2>/dev/null || echo "1.4.0")
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
CHECKPOINT_DIR="checkpoints"
BACKUP_DIR="$CHECKPOINT_DIR/v${CURRENT_VERSION}_${TIMESTAMP}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Version Checkpoint & Backup (Server)${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Create checkpoint directory
mkdir -p "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR/database"

echo -e "${GREEN}✓ Checkpoint directory created: $BACKUP_DIR${NC}"
echo ""

# Step 1: Database Backup
echo -e "${YELLOW}Step 1: Creating database backup...${NC}"

# Load database credentials from config
DB_HOST="srv556.hstgr.io"
DB_NAME="u810635266_site_podnbio"
DB_USER="u810635266_podnbio"
DB_PASS="6;hhwddG"

DB_BACKUP_FILE="$BACKUP_DIR/database/full_backup_${TIMESTAMP}.sql"

# Full database backup
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  > "$DB_BACKUP_FILE" 2>&1

if [ $? -eq 0 ]; then
    DB_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
    echo -e "${GREEN}✓ Database backup completed: $DB_BACKUP_FILE${NC}"
    echo -e "  Size: $DB_SIZE"
else
    echo -e "${RED}✗ Database backup failed${NC}"
    exit 1
fi

# Schema-only backup
SCHEMA_BACKUP_FILE="$BACKUP_DIR/database/schema_only_${TIMESTAMP}.sql"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  --no-data \
  --routines \
  --triggers \
  --events \
  > "$SCHEMA_BACKUP_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Schema-only backup completed: $SCHEMA_BACKUP_FILE${NC}"
fi

echo ""

# Step 2: Create checkpoint manifest
echo -e "${YELLOW}Step 2: Creating checkpoint manifest...${NC}"

MANIFEST_FILE="$BACKUP_DIR/CHECKPOINT_MANIFEST.txt"

cat > "$MANIFEST_FILE" << EOF
========================================
Version Checkpoint Manifest (Server)
========================================

Version: ${CURRENT_VERSION}
Timestamp: ${TIMESTAMP}
Date: $(date)

Database:
- Host: ${DB_HOST}
- Database: ${DB_NAME}
- Backup File: full_backup_${TIMESTAMP}.sql
- Schema File: schema_only_${TIMESTAMP}.sql

Backup Location: ${BACKUP_DIR}
Created: $(date)

To restore:
mysql -h ${DB_HOST} -u ${DB_USER} -p ${DB_NAME} < ${DB_BACKUP_FILE}
EOF

echo -e "${GREEN}✓ Manifest created: $MANIFEST_FILE${NC}"
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Server Checkpoint Complete!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "Version: ${GREEN}${CURRENT_VERSION}${NC}"
echo -e "Timestamp: ${TIMESTAMP}"
echo -e "Backup Directory: ${BACKUP_DIR}"
echo ""
echo -e "Backup Contents:"
echo -e "  - Database: ${GREEN}✓${NC}"
echo -e "  - Schema: ${GREEN}✓${NC}"
echo -e "  - Manifest: ${GREEN}✓${NC}"
echo ""

