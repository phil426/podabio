#!/bin/bash
# Complete Deployment Script
# Deploys all theme system files to server

HOST="u810635266@82.198.236.40"
REMOTE_PATH="domains/getphily.com/public_html"
SSH_PASS="@1318Redwood"

echo "=== Starting Complete Deployment ==="
echo ""

# Function to deploy file
deploy_file() {
    local file=$1
    local remote_dir=$2
    
    echo -n "Deploying $file... "
    
    # Extract directory structure
    dir_path=$(dirname "$file")
    
    # Deploy with sshpass
    sshpass -p "$SSH_PASS" scp -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$file" "$HOST:$REMOTE_PATH/$file" 2>&1
    
    if [ $? -eq 0 ]; then
        echo "✅"
        return 0
    else
        echo "❌ FAILED"
        return 1
    fi
}

# New files to create
echo "📦 Deploying NEW files..."
echo ""

NEW_FILES=(
    "database/check_migration.php"
    "classes/ColorExtractor.php"
    "classes/ThemeCSSGenerator.php"
    "classes/WidgetStyleManager.php"
    "classes/APIResponse.php"
    "includes/theme-helpers.php"
)

# Modified files to update
echo "📝 Deploying MODIFIED files..."
echo ""

MODIFIED_FILES=(
    "api/page.php"
    "api/upload.php"
    "classes/ImageHandler.php"
    "classes/Theme.php"
    "classes/Page.php"
    "page.php"
    "editor.php"
)

# Deploy new files
success_count=0
fail_count=0

for file in "${NEW_FILES[@]}"; do
    if deploy_file "$file"; then
        ((success_count++))
    else
        ((fail_count++))
    fi
    sleep 0.5
done

# Deploy modified files
for file in "${MODIFIED_FILES[@]}"; do
    if deploy_file "$file"; then
        ((success_count++))
    else
        ((fail_count++))
    fi
    sleep 0.5
done

echo ""
echo "=== Deployment Summary ==="
echo "✅ Success: $success_count files"
echo "❌ Failed: $fail_count files"
echo ""

if [ $fail_count -eq 0 ]; then
    echo "🎉 All files deployed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Check database: https://getphily.com/database/check_migration.php"
    echo "2. Test editor: https://getphily.com/editor.php"
else
    echo "⚠️  Some files failed to deploy."
    echo "Please deploy manually via cPanel File Manager or try again."
fi

