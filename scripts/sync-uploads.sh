#!/bin/bash
# Sync uploads between local and production
# Usage: ./scripts/sync-uploads.sh [pull|push]
#   pull - Download uploads from production to local
#   push - Upload local uploads to production (CAUTION!)

set -e

# Server details (same as deploy script)
SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_KEY_FILE="$HOME/.ssh/id_ed25519_podabio"
REMOTE_UPLOADS="/home/u925957603/domains/poda.bio/public_html/uploads/"
LOCAL_UPLOADS="./uploads/"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check for SSH key
if [ -f "$SSH_KEY_FILE" ]; then
    SSH_OPTS="-i $SSH_KEY_FILE -p $SSH_PORT"
else
    SSH_OPTS="-p $SSH_PORT"
    echo -e "${YELLOW}⚠️  SSH key not found at $SSH_KEY_FILE${NC}"
    echo "You may be prompted for password."
fi

ACTION=${1:-help}

case $ACTION in
    pull)
        echo "=========================================="
        echo "Downloading uploads from production..."
        echo "=========================================="
        echo ""
        echo "Source: $SSH_HOST:$REMOTE_UPLOADS"
        echo "Destination: $LOCAL_UPLOADS"
        echo ""
        
        # Create local uploads directory if it doesn't exist
        mkdir -p "$LOCAL_UPLOADS"
        
        # Use rsync to download (preserves existing files, only downloads new/changed)
        rsync -avz --progress \
            -e "ssh $SSH_OPTS" \
            "$SSH_HOST:$REMOTE_UPLOADS" \
            "$LOCAL_UPLOADS"
        
        echo ""
        echo -e "${GREEN}✅ Downloads complete!${NC}"
        echo ""
        echo "Local uploads directory now has production files."
        ;;
        
    push)
        echo "=========================================="
        echo -e "${RED}CAUTION: Uploading local files to production${NC}"
        echo "=========================================="
        echo ""
        echo "Source: $LOCAL_UPLOADS"
        echo "Destination: $SSH_HOST:$REMOTE_UPLOADS"
        echo ""
        echo -e "${YELLOW}This will sync local uploads TO production.${NC}"
        echo "Files that exist only on production will NOT be deleted."
        echo ""
        read -p "Are you sure you want to continue? (y/N) " -n 1 -r
        echo ""
        
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            # Use rsync to upload (preserves remote files, only uploads new/changed)
            rsync -avz --progress \
                -e "ssh $SSH_OPTS" \
                "$LOCAL_UPLOADS" \
                "$SSH_HOST:$REMOTE_UPLOADS"
            
            echo ""
            echo -e "${GREEN}✅ Upload complete!${NC}"
        else
            echo "Cancelled."
            exit 0
        fi
        ;;
        
    status)
        echo "=========================================="
        echo "Checking uploads status..."
        echo "=========================================="
        echo ""
        
        echo "Local uploads:"
        if [ -d "$LOCAL_UPLOADS" ]; then
            LOCAL_COUNT=$(find "$LOCAL_UPLOADS" -type f | wc -l)
            LOCAL_SIZE=$(du -sh "$LOCAL_UPLOADS" 2>/dev/null | cut -f1)
            echo "  Files: $LOCAL_COUNT"
            echo "  Size: $LOCAL_SIZE"
        else
            echo "  Directory does not exist"
        fi
        echo ""
        
        echo "Production uploads:"
        ssh $SSH_OPTS "$SSH_HOST" "
            if [ -d '$REMOTE_UPLOADS' ]; then
                REMOTE_COUNT=\$(find '$REMOTE_UPLOADS' -type f | wc -l)
                REMOTE_SIZE=\$(du -sh '$REMOTE_UPLOADS' 2>/dev/null | cut -f1)
                echo \"  Files: \$REMOTE_COUNT\"
                echo \"  Size: \$REMOTE_SIZE\"
            else
                echo \"  Directory does not exist\"
            fi
        "
        echo ""
        ;;
        
    *)
        echo "Usage: $0 [pull|push|status]"
        echo ""
        echo "Commands:"
        echo "  pull    - Download uploads from production to local"
        echo "  push    - Upload local uploads to production (CAUTION!)"
        echo "  status  - Show upload directory stats"
        echo ""
        echo "Examples:"
        echo "  $0 pull      # Get production images for local development"
        echo "  $0 status    # Check file counts"
        echo ""
        exit 1
        ;;
esac

