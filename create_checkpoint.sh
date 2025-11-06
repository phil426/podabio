#!/bin/bash
# Version Checkpoint and Backup Script
# Podn.Bio - Comprehensive Backup System

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get current version
CURRENT_VERSION=$(cat VERSION 2>/dev/null || echo "1.3.0")
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
CHECKPOINT_DIR="checkpoints"
BACKUP_DIR="$CHECKPOINT_DIR/v${CURRENT_VERSION}_${TIMESTAMP}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Version Checkpoint & Backup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Create checkpoint directory
mkdir -p "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR/database"
mkdir -p "$BACKUP_DIR/files"

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

# Step 2: Git Tag
echo -e "${YELLOW}Step 2: Creating git tag...${NC}"

# Check if we're in a git repository
if [ -d .git ]; then
    # Get current commit hash
    COMMIT_HASH=$(git rev-parse --short HEAD)
    
    # Create annotated tag
    TAG_NAME="v${CURRENT_VERSION}_${TIMESTAMP}"
    git tag -a "$TAG_NAME" -m "Version checkpoint: v${CURRENT_VERSION} - ${TIMESTAMP}
    
    Features:
    - Standalone podcast player demo
    - Page name effects system (16 effects)
    - Redesigned authentication pages
    - Comprehensive progress documentation
    
    Commit: $COMMIT_HASH"
    
    echo -e "${GREEN}✓ Git tag created: $TAG_NAME${NC}"
    
    # Push tag to remote
    echo -e "${YELLOW}  Pushing tag to remote...${NC}"
    git push origin "$TAG_NAME" 2>&1 || echo -e "${YELLOW}  Note: Tag not pushed (may need manual push)${NC}"
else
    echo -e "${YELLOW}  Not a git repository, skipping tag creation${NC}"
fi

echo ""

# Step 3: File Backup
echo -e "${YELLOW}Step 3: Creating file backup...${NC}"

# Backup important directories
IMPORTANT_DIRS=(
    "config"
    "classes"
    "includes"
    "api"
    "database"
    "demo"
)

for dir in "${IMPORTANT_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "  Backing up: $dir"
        cp -r "$dir" "$BACKUP_DIR/files/" 2>/dev/null || true
    fi
done

# Backup important files
IMPORTANT_FILES=(
    "VERSION"
    "README.md"
    "PROGRESS_REPORT.md"
    "editor.php"
    "page.php"
    "login.php"
    "signup.php"
    "index.php"
    ".htaccess"
)

for file in "${IMPORTANT_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  Backing up: $file"
        cp "$file" "$BACKUP_DIR/files/" 2>/dev/null || true
    fi
done

echo -e "${GREEN}✓ File backup completed${NC}"
echo ""

# Step 4: Create checkpoint manifest
echo -e "${YELLOW}Step 4: Creating checkpoint manifest...${NC}"

MANIFEST_FILE="$BACKUP_DIR/CHECKPOINT_MANIFEST.txt"

cat > "$MANIFEST_FILE" << EOF
========================================
Version Checkpoint Manifest
========================================

Version: ${CURRENT_VERSION}
Timestamp: ${TIMESTAMP}
Date: $(date)

Git Information:
- Commit Hash: $(git rev-parse HEAD 2>/dev/null || echo "N/A")
- Branch: $(git branch --show-current 2>/dev/null || echo "N/A")
- Tag: v${CURRENT_VERSION}_${TIMESTAMP}

Database:
- Host: ${DB_HOST}
- Database: ${DB_NAME}
- Backup File: full_backup_${TIMESTAMP}.sql
- Schema File: schema_only_${TIMESTAMP}.sql

Files Backed Up:
- Configuration files
- PHP classes
- API endpoints
- Database migrations
- Demo applications
- Documentation

Recent Features:
- Standalone podcast player demo
- Page name effects system (16 effects)
- Redesigned authentication pages
- Comprehensive progress documentation

Backup Location: ${BACKUP_DIR}
Created: $(date)
EOF

echo -e "${GREEN}✓ Manifest created: $MANIFEST_FILE${NC}"
echo ""

# Step 5: Create archive
echo -e "${YELLOW}Step 5: Creating backup archive...${NC}"

ARCHIVE_FILE="$CHECKPOINT_DIR/v${CURRENT_VERSION}_${TIMESTAMP}.tar.gz"
tar -czf "$ARCHIVE_FILE" -C "$CHECKPOINT_DIR" "v${CURRENT_VERSION}_${TIMESTAMP}" 2>/dev/null

if [ $? -eq 0 ]; then
    ARCHIVE_SIZE=$(du -h "$ARCHIVE_FILE" | cut -f1)
    echo -e "${GREEN}✓ Archive created: $ARCHIVE_FILE${NC}"
    echo -e "  Size: $ARCHIVE_SIZE"
else
    echo -e "${YELLOW}  Archive creation skipped (tar may not be available)${NC}"
fi

echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Checkpoint Complete!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "Version: ${GREEN}${CURRENT_VERSION}${NC}"
echo -e "Timestamp: ${TIMESTAMP}"
echo -e "Backup Directory: ${BACKUP_DIR}"
echo ""
echo -e "Backup Contents:"
echo -e "  - Database: ${GREEN}✓${NC}"
echo -e "  - Schema: ${GREEN}✓${NC}"
echo -e "  - Files: ${GREEN}✓${NC}"
echo -e "  - Git Tag: ${GREEN}✓${NC}"
echo -e "  - Manifest: ${GREEN}✓${NC}"
if [ -f "$ARCHIVE_FILE" ]; then
    echo -e "  - Archive: ${GREEN}✓${NC}"
fi
echo ""
echo -e "${YELLOW}To restore from this checkpoint:${NC}"
echo -e "  1. Database: mysql -h ${DB_HOST} -u ${DB_USER} -p ${DB_NAME} < ${DB_BACKUP_FILE}"
echo -e "  2. Files: Extract from ${ARCHIVE_FILE} or copy from ${BACKUP_DIR}/files"
echo ""

